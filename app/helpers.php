<?php
declare(strict_types=1);

function e(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(string $path = ''): string
{
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $dir . '/' . ltrim($path, '/');
}

function url(string $page, array $params = []): string
{
    return base_url('index.php') . '?' . http_build_query(['p' => $page] + $params);
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $t = $_POST['_csrf'] ?? '';
    if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(419);
        exit('CSRF token ไม่ถูกต้อง กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง');
    }
}

function flash(string $msg, string $type = 'ok'): void
{
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['uid'])) {
            $st = db()->prepare('SELECT * FROM users WHERE id = ? AND status <> "deleted"');
            $st->execute([$_SESSION['uid']]);
            $user = $st->fetch() ?: null;
        }
    }
    return $user;
}

function require_login(?string $role = null): array
{
    $u = current_user();
    if (!$u) redirect(url('login'));
    if ($role && $u['role'] !== $role) {
        http_response_code(403);
        exit('ไม่มีสิทธิ์เข้าถึงหน้านี้');
    }
    return $u;
}

function login_user(array $u): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id'];
    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$u['id']]);
}

function audit(string $action, string $target = '', string $detail = ''): void
{
    try {
        $u = current_user();
        db()->prepare('INSERT INTO audit_log (actor, action, target, detail, ip) VALUES (?,?,?,?,?)')
            ->execute([$u['username'] ?? 'system', $action, $target, $detail, $_SERVER['REMOTE_ADDR'] ?? 'cli']);
    } catch (Throwable) {
        // audit must never break the main action
    }
}

function thai_date(?string $dt, bool $withTime = false): string
{
    if (!$dt) return '-';
    $m = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $ts = strtotime($dt);
    $s = date('j', $ts) . ' ' . $m[(int)date('n', $ts) - 1] . ' ' . ((int)date('Y', $ts) + 543);
    return $withTime ? $s . ' ' . date('H:i', $ts) . ' น.' : $s;
}

/** Status key used for colors: ok | warn | over | suspended | pending */
function user_status_key(array $u): string
{
    if (in_array($u['status'], ['suspended', 'pending'], true)) return $u['status'];
    $soft = max(1, (int)$u['quota_soft_mb']);
    $p = (float)$u['disk_used_mb'] / $soft * 100;
    return $p >= 100 ? 'over' : ($p >= 85 ? 'warn' : 'ok');
}

function status_cfg(string $key): array
{
    return [
        'ok'        => ['label' => 'ปกติ', 'cls' => 'ok'],
        'warn'      => ['label' => 'ใกล้เต็มโควตา', 'cls' => 'warn'],
        'over'      => ['label' => 'เกินโควตา', 'cls' => 'over'],
        'suspended' => ['label' => 'ระงับ', 'cls' => 'over'],
        'pending'   => ['label' => 'รอยืนยัน', 'cls' => 'pending'],
    ][$key];
}

function usage_pct(array $u): int
{
    return (int)round((float)$u['disk_used_mb'] / max(1, (int)$u['quota_soft_mb']) * 100);
}

function random_password(int $len = 12): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!#%+=';
    $out = '';
    for ($i = 0; $i < $len; $i++) $out .= $chars[random_int(0, strlen($chars) - 1)];
    return $out;
}

const RESERVED_USERNAMES = ['admin', 'root', 'www', 'mail', 'ftp', 'mysql', 'sftp', 'daemon', 'bin', 'sys',
    'sync', 'games', 'man', 'lp', 'news', 'uucp', 'proxy', 'backup', 'list', 'irc', 'nobody', 'sshd', 'hosting'];

function validate_username(string $u): ?string
{
    if (!preg_match('/^[a-z0-9]+$/', $u)) return 'ใช้ได้เฉพาะ a-z และ 0-9';
    if (strlen($u) < 4 || strlen($u) > 16) return 'ความยาวต้องอยู่ระหว่าง 4–16 ตัวอักษร';
    if (in_array($u, RESERVED_USERNAMES, true)) return 'username นี้สงวนไว้สำหรับระบบ';
    $st = db()->prepare('SELECT 1 FROM users WHERE username = ?');
    $st->execute([$u]);
    if ($st->fetchColumn()) return 'username นี้ถูกใช้แล้ว';
    return null;
}
