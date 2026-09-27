<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

if (!is_installed()) redirect(base_url('install.php'));

$page   = preg_replace('/[^a-z_]/', '', (string)($_GET['p'] ?? 'landing'));
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($isPost) csrf_check();

function view(string $name, array $vars = [], string $layout = 'public'): never
{
    extract($vars);
    ob_start();
    require BASE_PATH . "/views/$name.php";
    $content = ob_get_clean();
    require BASE_PATH . "/views/layout_$layout.php";
    exit;
}

function seats(): array
{
    $max  = Settings::int('max_users');
    $used = (int)db()->query("SELECT COUNT(*) FROM users WHERE role='student' AND status IN ('pending','active','suspended')")->fetchColumn();
    $open = Settings::get('registration_open') === '1' && $used < $max;
    return ['max' => $max, 'used' => $used, 'left' => max(0, $max - $used), 'open' => $open];
}

function site_url(string $username): string
{
    return 'http://' . $username . '.' . Settings::get('domain') . '/';
}

function conn_rows(array $u, ?array $creds = null): array
{
    $rows = [['Base URL', site_url($u['username'])], ['SFTP Host', Settings::get('sftp_host')], ['Port', '22'], ['SSH/SFTP User', $u['username']]];
    if ($creds) $rows[] = ['รหัสผ่าน SFTP', $creds['ssh_password']];
    $rows[] = ['ฐานข้อมูล (Database)', $u['username']];
    $rows[] = ['ผู้ใช้ฐานข้อมูล', $u['username']];
    $rows[] = ['DB Host', 'localhost'];
    if ($creds) $rows[] = ['รหัสผ่านฐานข้อมูล', $creds['db_password']];
    return $rows;
}

function find_user(string $username): array
{
    $st = db()->prepare("SELECT * FROM users WHERE username = ? AND role = 'student' AND status <> 'deleted'");
    $st->execute([$username]);
    $u = $st->fetch();
    if (!$u) {
        flash('ไม่พบผู้ใช้ ' . $username, 'err');
        redirect(url('admin_users'));
    }
    return $u;
}

function send_otp(array $u): ?string
{
    db()->prepare('DELETE FROM otp_codes WHERE user_id = ?')->execute([$u['id']]);
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    db()->prepare('INSERT INTO otp_codes (user_id, code_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))')
        ->execute([$u['id'], password_hash($code, PASSWORD_DEFAULT)]);
    $_SESSION['otp_sent_at'] = time();
    if (Settings::get('otp_mode') === 'display') return $code;

    $subject = '=?UTF-8?B?' . base64_encode('รหัสยืนยัน Student Hosting Portal') . '?=';
    $body = "รหัสยืนยันของคุณคือ $code\nรหัสนี้หมดอายุใน 10 นาที\n\nusername: {$u['username']}\n";
    $headers = 'From: ' . Settings::get('mail_from') . "\r\nContent-Type: text/plain; charset=UTF-8";
    if (!@mail((string)$u['email'], $subject, $body, $headers)) {
        flash('ส่งอีเมลไม่สำเร็จ กรุณาติดต่อผู้ดูแลระบบ (ตรวจการตั้งค่า MTA)', 'err');
    }
    return null;
}

/** Provision a pending user on the server. Returns one-time credentials. */
function activate_user(array $u): array
{
    $creds = Provisioner::create($u['username'], (int)$u['quota_soft_mb'], (int)$u['quota_hard_mb']);
    db()->prepare("UPDATE users SET status='active', provisioned_at=NOW() WHERE id=?")->execute([$u['id']]);
    db()->prepare('DELETE FROM otp_codes WHERE user_id = ?')->execute([$u['id']]);
    audit('user.provision', $u['username'], Provisioner::isExec() ? 'exec' : 'simulate');
    return $creds;
}

function remove_user(array $u): void
{
    if ($u['status'] !== 'pending') Provisioner::delete($u['username']);
    db()->prepare('DELETE FROM users WHERE id = ?')->execute([$u['id']]);
    audit('user.delete', $u['username']);
}

function pending_counts(): array
{
    static $c = null;
    if ($c === null) {
        $c = ['migrations' => 0, 'runbook' => null];
        try { $c['migrations'] = count((new Migrator(db()))->pending()); } catch (Throwable) { }
    }
    return $c;
}

try {
switch ($page) {

// ============================================================ public
case 'landing':
    $u = current_user();
    view('landing', ['seats' => seats(), 'me' => $u], 'bare');

case 'terms':
    redirect(url('signup'));

case 'api_username':
    header('Content-Type: application/json; charset=utf-8');
    $err = validate_username(strtolower(trim((string)($_GET['u'] ?? ''))));
    echo json_encode(['ok' => $err === null, 'msg' => $err ?? 'ใช้ได้'], JSON_UNESCAPED_UNICODE);
    exit;

case 'signup':
    $seats = seats();
    $old = [];
    $errors = [];
    if ($isPost && $seats['open']) {
        $old = ['full_name' => post('full_name'), 'student_id' => post('student_id'), 'email' => strtolower(post('email')),
                'username' => strtolower(post('username')), 'course' => post('course')];
        $pw = (string)($_POST['password'] ?? '');
        // Free up usernames/emails held by abandoned sign-ups
        db()->exec("DELETE FROM users WHERE status='pending' AND role='student' AND created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");

        if (mb_strlen($old['full_name']) < 3) $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
        if (!preg_match('/^[0-9]{5,20}$/', $old['student_id'])) $errors[] = 'รหัสนักศึกษาต้องเป็นตัวเลข 5–20 หลัก';
        $dom = Settings::get('email_domain');
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'อีเมลไม่ถูกต้อง';
        elseif ($dom !== '' && !str_ends_with($old['email'], '@' . $dom)) $errors[] = "ต้องใช้อีเมลสถาบัน (@$dom)";
        if ($e = validate_username($old['username'])) $errors[] = 'Username: ' . $e;
        if (strlen($pw) < 8) $errors[] = 'รหัสผ่านเข้าพอร์ทัลต้องมีอย่างน้อย 8 ตัวอักษร';
        if ($pw !== ($_POST['password2'] ?? '')) $errors[] = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
        if (empty($_POST['agree'])) $errors[] = 'กรุณายอมรับข้อตกลงการใช้งาน';
        $st = db()->prepare("SELECT 1 FROM users WHERE (email = ? OR student_id = ?) AND status <> 'deleted'");
        $st->execute([$old['email'], $old['student_id']]);
        if ($st->fetchColumn()) $errors[] = 'อีเมลหรือรหัสนักศึกษานี้มีบัญชีอยู่แล้ว';

        if (!$errors) {
            db()->prepare("INSERT INTO users (username, password_hash, role, full_name, student_id, email, course, academic_year, status, quota_soft_mb, quota_hard_mb)
                           VALUES (?,?,'student',?,?,?,?,?,'pending',?,?)")
                ->execute([$old['username'], password_hash($pw, PASSWORD_DEFAULT), $old['full_name'], $old['student_id'], $old['email'],
                           $old['course'] ?: null, (string)((int)date('Y') + 543), Settings::int('default_soft_mb'), Settings::int('default_hard_mb')]);
            $id = (int)db()->lastInsertId();
            $_SESSION['signup_uid'] = $id;
            $u = db()->query("SELECT * FROM users WHERE id = $id")->fetch();
            $code = send_otp($u);
            audit('user.signup', $u['username']);
            flash('ส่งรหัสยืนยันไปที่ ' . $u['email'] . ' แล้ว' . ($code ? " (โหมดทดสอบ: รหัสคือ $code)" : ''));
            redirect(url('otp'));
        }
    }
    view('signup', ['seats' => $seats, 'old' => $old, 'errors' => $errors,
        'courses' => array_filter(array_map('trim', explode(',', Settings::get('courses'))))]);

case 'otp':
    $uid = (int)($_SESSION['signup_uid'] ?? 0);
    $st = db()->prepare("SELECT * FROM users WHERE id = ? AND status = 'pending'");
    $st->execute([$uid]);
    $u = $st->fetch();
    if (!$u) redirect(url('signup'));
    $error = null;
    if ($isPost && post('action') === 'resend') {
        if (time() - (int)($_SESSION['otp_sent_at'] ?? 0) < 60) {
            flash('กรุณารอสักครู่ก่อนขอรหัสใหม่', 'warn');
        } else {
            $code = send_otp($u);
            flash('ส่งรหัสใหม่แล้ว' . ($code ? " (โหมดทดสอบ: $code)" : ''));
        }
        redirect(url('otp'));
    }
    if ($isPost) {
        $st = db()->prepare('SELECT * FROM otp_codes WHERE user_id = ? ORDER BY id DESC LIMIT 1');
        $st->execute([$u['id']]);
        $otp = $st->fetch();
        if (!$otp || strtotime($otp['expires_at']) < time()) {
            $error = 'รหัสหมดอายุแล้ว กรุณากดส่งรหัสใหม่';
        } elseif ($otp['attempts'] >= 5) {
            $error = 'กรอกรหัสผิดเกินจำนวนครั้งที่กำหนด กรุณากดส่งรหัสใหม่';
        } elseif (!password_verify(post('otp'), $otp['code_hash'])) {
            db()->prepare('UPDATE otp_codes SET attempts = attempts + 1 WHERE id = ?')->execute([$otp['id']]);
            $error = 'รหัสไม่ถูกต้อง กรุณาลองอีกครั้ง';
        } else {
            if (!seats()['open'] && seats()['used'] > seats()['max']) {
                $error = 'ขณะนี้ระบบเต็มโควตา';
            } else {
                try {
                    $creds = activate_user($u);
                } catch (Throwable $ex) {
                    audit('user.provision_failed', $u['username'], $ex->getMessage());
                    view('otp', ['u' => $u, 'error' => 'สร้างบัญชีบนเซิร์ฟเวอร์ไม่สำเร็จ กรุณาติดต่อผู้ดูแลระบบ', 'wait' => 0]);
                }
                unset($_SESSION['signup_uid']);
                login_user($u);
                $_SESSION['new_creds'] = $creds;
                redirect(url('success'));
            }
        }
    }
    $wait = max(0, 60 - (time() - (int)($_SESSION['otp_sent_at'] ?? 0)));
    view('otp', ['u' => $u, 'error' => $error, 'wait' => $wait]);

case 'success':
    $u = require_login('student');
    $creds = $_SESSION['new_creds'] ?? null;
    if (!$creds) redirect(url('dash'));
    view('success', ['u' => $u, 'rows' => conn_rows($u, $creds)]);

case 'login':
    if ($u = current_user()) redirect(url($u['role'] === 'admin' ? 'admin' : 'dash'));
    $error = null;
    $role = ($_GET['role'] ?? post('role')) === 'admin' ? 'admin' : 'student';
    if ($isPost) {
        $_SESSION['login_fail'] = $_SESSION['login_fail'] ?? 0;
        if ($_SESSION['login_fail'] >= 5 && time() - ($_SESSION['login_fail_at'] ?? 0) < 300) {
            $error = 'พยายามเข้าสู่ระบบผิดหลายครั้ง กรุณารอ 5 นาที';
        } else {
            $st = db()->prepare("SELECT * FROM users WHERE username = ? AND status <> 'deleted'");
            $st->execute([strtolower(post('username'))]);
            $u = $st->fetch();
            if ($u && password_verify((string)($_POST['password'] ?? ''), $u['password_hash'])) {
                if ($u['status'] === 'pending') {
                    $_SESSION['signup_uid'] = (int)$u['id'];
                    redirect(url('otp'));
                }
                $_SESSION['login_fail'] = 0;
                login_user($u);
                audit('login', $u['username']);
                if ($u['status'] === 'suspended') flash('บัญชีของคุณถูกระงับชั่วคราว กรุณาติดต่อผู้ดูแลระบบ', 'warn');
                redirect(url($u['role'] === 'admin' ? 'admin' : 'dash'));
            }
            $_SESSION['login_fail']++;
            $_SESSION['login_fail_at'] = time();
            $error = 'Username หรือรหัสผ่านไม่ถูกต้อง';
        }
    }
    view('login', ['error' => $error, 'role' => $role]);

case 'logout':
    if ($isPost) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    redirect(url('login'));

// ============================================================ student
case 'dash':
    $u = require_login();
    if ($u['role'] === 'admin') redirect(url('admin'));
    unset($_SESSION['new_creds']);
    view('student_dash', ['u' => $u, 'rows' => conn_rows($u), 'title' => 'แดชบอร์ด', 'nav' => 'dash'], 'app');

case 'guide':
    $u = require_login('student');
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="filezilla-guide-' . $u['username'] . '.txt"');
    $h = Settings::get('sftp_host');
    echo "คู่มือเชื่อมต่อ FileZilla — Student Hosting Portal\r\n\r\n"
       . "1. ดาวน์โหลด FileZilla Client จาก https://filezilla-project.org/\r\n"
       . "2. เปิดเมนู File > Site Manager > New site\r\n"
       . "3. Protocol: SFTP - SSH File Transfer Protocol\r\n"
       . "4. Host: $h   Port: 22\r\n"
       . "5. Logon Type: Normal   User: {$u['username']}   Password: รหัสผ่าน SFTP ของคุณ\r\n"
       . "6. กด Connect แล้วอัปโหลดไฟล์เว็บไซต์ไปที่โฟลเดอร์ public_html\r\n"
       . "7. เปิดเว็บไซต์ที่ " . site_url($u['username']) . "\r\n\r\n"
       . "ฐานข้อมูล: host=localhost  database={$u['username']}  user={$u['username']}\r\n"
       . "หมายเหตุ: โฟลเดอร์ logs, backup, tmp ใช้เก็บบันทึก สำรองข้อมูล และไฟล์ชั่วคราว\r\n";
    exit;

case 'settings':
    $u = require_login('student');
    if ($isPost) {
        $a = post('action');
        try {
            if (in_array($a, ['pw_ssh', 'pw_db', 'pw_portal'], true)) {
                $pw = (string)($_POST['password'] ?? '');
                if (strlen($pw) < 8 || $pw !== ($_POST['password2'] ?? '')) throw new RuntimeException('รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษรและตรงกันทั้งสองช่อง');
                if ($u['status'] !== 'active' && $a !== 'pw_portal') throw new RuntimeException('บัญชีถูกระงับ เปลี่ยนรหัสผ่านไม่ได้');
                if ($a === 'pw_portal') {
                    if (!password_verify((string)($_POST['current'] ?? ''), $u['password_hash'])) throw new RuntimeException('รหัสผ่านปัจจุบันไม่ถูกต้อง');
                    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($pw, PASSWORD_DEFAULT), $u['id']]);
                } else {
                    if (preg_match('/[\r\n\x00]/', $pw)) throw new RuntimeException('รหัสผ่านมีอักขระที่ไม่อนุญาต');
                    Provisioner::setPassword($u['username'], $a === 'pw_ssh' ? 'ssh' : 'db', $pw);
                }
                audit('user.' . $a, $u['username']);
                flash('เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
            } elseif ($a === 'self_delete') {
                if (post('confirm') !== $u['username']) throw new RuntimeException('พิมพ์ username ไม่ถูกต้อง');
                remove_user($u);
                $_SESSION = [];
                session_regenerate_id(true);
                flash('ลบบัญชีเรียบร้อยแล้ว');
                redirect(url('landing'));
            }
        } catch (Throwable $ex) {
            flash($ex->getMessage(), 'err');
        }
        redirect(url('settings'));
    }
    view('student_settings', ['u' => $u, 'title' => 'ตั้งค่าบัญชี', 'nav' => 'settings'], 'app');

// ============================================================ admin
case 'admin':
    $me = require_login('admin');
    if ($isPost && post('action') === 'sync') {
        require BASE_PATH . '/cron/sync_lib.php';
        try {
            $n = sync_usage();
            flash($n === null ? 'โหมด simulate: ไม่มีข้อมูลโควตาจริงให้ซิงก์ (บันทึกสถิติรายวันแล้ว)' : "ซิงก์การใช้พื้นที่ $n บัญชีแล้ว");
        } catch (Throwable $ex) {
            flash('ซิงก์ไม่สำเร็จ: ' . $ex->getMessage(), 'err');
        }
        redirect(url('admin'));
    }
    $users = db()->query("SELECT * FROM users WHERE role='student' AND status <> 'deleted'")->fetchAll();
    $hist = array_reverse(db()->query('SELECT * FROM disk_history ORDER BY day DESC LIMIT 14')->fetchAll());
    view('admin_overview', ['users' => $users, 'hist' => $hist, 'seats' => seats(), 'rb' => Runbook::evaluate(),
        'title' => 'ภาพรวมระบบ', 'nav' => 'admin'], 'app');

case 'admin_users':
    require_login('admin');
    if ($isPost) {
        $u = find_user(post('username'));
        $a = post('action');
        try {
            switch ($a) {
                case 'suspend':
                case 'unsuspend':
                    if ($u['status'] === 'pending') throw new RuntimeException('บัญชียังไม่ได้ยืนยัน');
                    Provisioner::suspend($u['username'], $a === 'suspend');
                    db()->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$a === 'suspend' ? 'suspended' : 'active', $u['id']]);
                    audit('user.' . $a, $u['username']);
                    flash(($a === 'suspend' ? 'ระงับบัญชี ' : 'เปิดใช้งานบัญชี ') . $u['username'] . ' แล้ว');
                    break;
                case 'delete':
                    if (post('confirm') !== $u['username']) throw new RuntimeException('พิมพ์ username ไม่ถูกต้อง');
                    remove_user($u);
                    flash('ลบบัญชี ' . $u['username'] . ' แล้ว');
                    redirect(url('admin_users'));
                case 'quota':
                    $soft = (int)post('soft'); $hard = (int)post('hard');
                    if (!($soft > 0 && $hard >= $soft && $hard <= 5000)) throw new RuntimeException('ค่าโควตาไม่ถูกต้อง');
                    if ($u['status'] !== 'pending') Provisioner::setQuota($u['username'], $soft, $hard);
                    db()->prepare('UPDATE users SET quota_soft_mb = ?, quota_hard_mb = ? WHERE id = ?')->execute([$soft, $hard, $u['id']]);
                    audit('user.quota', $u['username'], "$soft/$hard");
                    flash('ปรับโควตาของ ' . $u['username'] . ' แล้ว');
                    break;
                case 'approve':
                    if ($u['status'] !== 'pending') throw new RuntimeException('บัญชีนี้ไม่ได้อยู่ในสถานะรอยืนยัน');
                    $creds = activate_user($u);
                    $_SESSION['admin_creds'] = ['username' => $u['username'], 'rows' => conn_rows($u, $creds)];
                    flash('อนุมัติและสร้างบัญชี ' . $u['username'] . ' แล้ว — ส่งข้อมูลเชื่อมต่อให้นักศึกษา (แสดงครั้งเดียว)');
                    break;
            }
        } catch (Throwable $ex) {
            flash($ex->getMessage(), 'err');
        }
        redirect(post('back') === 'user' ? url('admin_user', ['u' => $u['username']]) : url('admin_users') . (preg_match('/^[A-Za-z0-9_=&%.+-]+$/', post('qs')) ? '&' . post('qs') : ''));
    }
    $f = ['q' => trim((string)($_GET['q'] ?? '')), 'course' => (string)($_GET['course'] ?? ''), 'year' => (string)($_GET['year'] ?? ''),
          'status' => (string)($_GET['status'] ?? ''), 'sort' => (string)($_GET['sort'] ?? 'created'), 'dir' => ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc'];
    $users = db()->query("SELECT * FROM users WHERE role='student' AND status <> 'deleted'")->fetchAll();
    view('admin_users', ['users' => $users, 'f' => $f, 'title' => 'จัดการผู้ใช้', 'nav' => 'admin_users'], 'app');

case 'admin_user':
    require_login('admin');
    $u = find_user((string)($_GET['u'] ?? ''));
    $creds = $_SESSION['admin_creds'] ?? null;
    unset($_SESSION['admin_creds']);
    if ($creds && $creds['username'] !== $u['username']) $creds = null;
    $st = db()->prepare('SELECT * FROM audit_log WHERE target = ? ORDER BY id DESC LIMIT 20');
    $st->execute([$u['username']]);
    view('admin_user', ['u' => $u, 'creds' => $creds, 'log' => $st->fetchAll(), 'title' => 'ผู้ใช้ ' . $u['username'], 'nav' => 'admin_users'], 'app');

case 'admin_runbook':
    $me = require_login('admin');
    if ($isPost) {
        $key = post('key');
        if (in_array($key, Runbook::keys(), true)) {
            Runbook::tick($key, post('done') === '1', post('note'), $me['username']);
            audit('runbook.' . (post('done') === '1' ? 'tick' : 'untick'), $key, post('note'));
            flash(post('done') === '1' ? 'บันทึกว่าดำเนินการแล้ว' : 'ยกเลิกการติ๊กแล้ว');
        }
        redirect(url('admin_runbook') . '#' . rawurlencode($key));
    }
    view('admin_runbook', ['rb' => Runbook::evaluate(), 'title' => 'เช็กลิสต์ติดตั้งเซิร์ฟเวอร์', 'nav' => 'admin_runbook'], 'app');

case 'admin_migrations':
    require_login('admin');
    $m = new Migrator(db());
    if ($isPost) {
        $a = post('action');
        try {
            if ($a === 'migrate') {
                $done = $m->migrate(post('name') ?: null);
                audit('migration.up', implode(',', $done));
                flash($done ? 'รัน migration สำเร็จ: ' . implode(', ', $done) : 'ไม่มี migration ที่ต้องรัน');
            } elseif ($a === 'rollback') {
                $done = $m->rollback(post('name') ?: null);
                audit('migration.down', implode(',', $done));
                flash($done ? 'ย้อนกลับสำเร็จ: ' . implode(', ', $done) : 'ไม่มี migration ให้ย้อนกลับ');
            } elseif ($a === 'create') {
                $desc = post('description');
                if ($desc === '') throw new RuntimeException('กรุณาระบุคำอธิบาย');
                $name = $m->create($desc);
                audit('migration.create', $name);
                flash("สร้างไฟล์ migrations/$name.php แล้ว — แก้ไขคำสั่ง up/down ในไฟล์ก่อนรัน");
            }
        } catch (Throwable $ex) {
            flash('ผิดพลาด: ' . $ex->getMessage(), 'err');
        }
        redirect(url('admin_migrations'));
    }
    view('admin_migrations', ['rows' => $m->status(), 'writable' => is_writable(MIGRATIONS_PATH),
        'dbVersion' => db()->query('SELECT VERSION()')->fetchColumn(), 'title' => 'Migrations ฐานข้อมูล', 'nav' => 'admin_migrations'], 'app');

case 'admin_settings':
    require_login('admin');
    if ($isPost) {
        $errors = [];
        foreach (array_keys(Settings::DEFS) as $k) {
            if (!isset($_POST[$k])) continue;
            $v = post($k);
            if (in_array($k, ['max_users', 'disk_total_gb', 'default_soft_mb', 'default_hard_mb'], true) && (!ctype_digit($v) || (int)$v < 1)) { $errors[] = "$k ต้องเป็นจำนวนเต็มบวก"; continue; }
            if ($k === 'provision_mode' && !in_array($v, ['simulate', 'exec'], true)) continue;
            if ($k === 'otp_mode' && !in_array($v, ['mail', 'display'], true)) continue;
            if ($k === 'registration_open') $v = $v === '1' ? '1' : '0';
            if (in_array($k, ['script_dir', 'home_path'], true) && !preg_match('#^/[A-Za-z0-9._/-]+$#', $v)) { $errors[] = "$k ต้องเป็น path แบบ absolute"; continue; }
            if (in_array($k, ['domain', 'sftp_host', 'email_domain'], true) && $v !== '' && !preg_match('/^[a-z0-9.-]+$/i', $v)) { $errors[] = "$k ไม่ใช่ชื่อโดเมนที่ถูกต้อง"; continue; }
            Settings::set($k, $v);
        }
        if ((int)Settings::get('default_hard_mb') < (int)Settings::get('default_soft_mb')) $errors[] = 'Hard limit เริ่มต้นต้องไม่น้อยกว่า Soft limit';
        audit('settings.update');
        $errors ? flash(implode(' · ', $errors), 'err') : flash('บันทึกการตั้งค่าแล้ว');
        redirect(url('admin_settings'));
    }
    view('admin_settings', ['title' => 'ตั้งค่าระบบ', 'nav' => 'admin_settings'], 'app');

case 'admin_audit':
    require_login('admin');
    $log = db()->query('SELECT * FROM audit_log ORDER BY id DESC LIMIT 300')->fetchAll();
    view('admin_audit', ['log' => $log, 'title' => 'บันทึกการใช้งาน', 'nav' => 'admin_audit'], 'app');

default:
    http_response_code(404);
    view('notfound', [], 'public');
}
} catch (PDOException $ex) {
    http_response_code(500);
    error_log('[hosting-portal] ' . $ex->getMessage());
    $pending = 0;
    try { $pending = count((new Migrator(db()))->pending()); } catch (Throwable) { }
    exit('<meta charset="utf-8"><p style="font-family:sans-serif">เกิดข้อผิดพลาดกับฐานข้อมูล'
        . ($pending ? " — มี migration ค้างอยู่ $pending รายการ กรุณาให้ผู้ดูแลรันที่เมนู Migrations หรือรัน install.php ซ้ำ" : '') . '</p>');
}
