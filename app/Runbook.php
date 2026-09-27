<?php
declare(strict_types=1);

/**
 * Server readiness checklist based on installation-runbook.md (sections 1–8).
 *
 * Each item may have an automatic probe. Probe results:
 *   pass    – verified on this server
 *   fail    – verified NOT done
 *   unknown – cannot be verified from PHP (not Linux, no permission, ...) → admin ticks it manually
 *   manual  – no probe exists (physical/network work, E2E tests) → admin ticks it manually
 * An item counts as done when the probe passes OR an admin has ticked it.
 */
final class Runbook
{
    public static function sections(): array
    {
        $domain = Settings::get('domain');
        $home   = Settings::get('home_path') ?: '/home';
        $dir    = rtrim(Settings::get('script_dir'), '/');

        return [
            ['no' => 1, 'title' => 'เตรียมเซิร์ฟเวอร์และติดตั้ง TurnKey Linux LAMP', 'items' => [
                ['key' => '1.download', 'title' => 'ดาวน์โหลดอิมเมจ TurnKey LAMP (ISO/OVA)', 'cmd' => 'https://www.turnkeylinux.org/lamp'],
                ['key' => '1.install', 'title' => 'ติดตั้งลงเครื่อง/VM และตั้งรหัสผ่าน root (จดเก็บในที่ปลอดภัย)', 'probe' => fn() => self::probeOs()],
                ['key' => '1.static_ip', 'title' => 'ตั้งค่า Network เป็น Static IP ภายในเครือข่ายสถาบัน', 'probe' => fn() => self::probeStaticIp()],
                ['key' => '1.ssh', 'title' => 'ทดสอบเข้าเซิร์ฟเวอร์ผ่าน SSH จากเครื่องผู้ดูแลระบบ', 'cmd' => 'ssh root@<IP เซิร์ฟเวอร์>', 'probe' => fn() => self::probeService('ssh')],
                ['key' => '1.upgrade', 'title' => 'อัปเดตระบบให้เป็นรุ่นล่าสุด', 'cmd' => 'apt update && apt -y upgrade', 'probe' => fn() => self::probeUpgrades()],
                ['key' => '1.dns', 'title' => "ตั้งค่า DNS: A record ของ $domain และ Wildcard *.$domain ชี้มาที่เซิร์ฟเวอร์", 'probe' => fn() => self::probeDns($domain)],
            ]],
            ['no' => 2, 'title' => 'ติดตั้งแพ็กเกจที่จำเป็นเพิ่มเติม', 'items' => [
                ['key' => '2.quota_tools', 'title' => 'ติดตั้ง quota tools', 'cmd' => 'apt -y install quota quotatool', 'probe' => fn() => self::probeBinaries(['setquota', 'repquota', 'quotacheck', 'quotaon'])],
                ['key' => '2.services', 'title' => 'Apache, MariaDB, PHP, OpenSSH ทำงานอยู่', 'cmd' => 'systemctl status apache2 mariadb ssh', 'probe' => fn() => self::probeServices(['apache2', 'mariadb', 'ssh'])],
            ]],
            ['no' => 3, 'title' => 'เปิดใช้งานระบบโควตาพื้นที่ดิสก์ (Disk Quota)', 'items' => [
                ['key' => '3.fstab', 'title' => "เพิ่มตัวเลือก usrquota ให้พาร์ทิชันที่เก็บ $home ใน /etc/fstab", 'cmd' => '/dev/sda1  /home  ext4  defaults,usrquota  0  2', 'probe' => fn() => self::probeFstab($home)],
                ['key' => '3.remount', 'title' => 'Remount และสร้างฐานข้อมูลโควตา', 'cmd' => "mount -o remount $home\nquotacheck -avum\nquotaon -avu", 'probe' => fn() => self::probeMountedQuota($home)],
                ['key' => '3.repquota', 'title' => 'ทดสอบโควตา: repquota -a ต้องแสดงตารางโดยไม่มี error', 'cmd' => 'repquota -a', 'probe' => fn() => self::probeSudo("$dir/usage_report.sh", 'repquota ผ่านสคริปต์ usage_report.sh')],
            ]],
            ['no' => 4, 'title' => 'สร้างกลุ่มผู้ใช้และตั้งค่า SFTP Chroot Jail', 'items' => [
                ['key' => '4.group', 'title' => 'สร้างกลุ่ม sftp-users', 'cmd' => 'groupadd sftp-users', 'probe' => fn() => self::probeGroup('sftp-users')],
                ['key' => '4.sshd', 'title' => 'เพิ่มบล็อก Match Group sftp-users ใน /etc/ssh/sshd_config', 'cmd' => "Match Group sftp-users\n    ChrootDirectory %h\n    ForceCommand internal-sftp\n    AllowTcpForwarding no\n    X11Forwarding no", 'probe' => fn() => self::probeSshd()],
                ['key' => '4.sshd_test', 'title' => 'ตรวจ sshd -t ไม่มี error แล้ว restart SSH', 'cmd' => "sshd -t\nsystemctl restart ssh", 'warn' => 'ห้าม restart SSH ถ้า sshd -t แจ้ง error'],
            ]],
            ['no' => 5, 'title' => 'เปิดใช้งาน Apache Virtual Host แบบไดนามิก (mod_vhost_alias)', 'items' => [
                ['key' => '5.module', 'title' => 'เปิดใช้งานโมดูล vhost_alias', 'cmd' => 'a2enmod vhost_alias', 'probe' => fn() => self::probeApacheModule()],
                ['key' => '5.conf', 'title' => 'สร้าง /etc/apache2/sites-available/student-hosting.conf (VirtualDocumentRoot)', 'probe' => fn() => self::probeVhostConf('/etc/apache2/sites-available/student-hosting.conf', $domain)],
                ['key' => '5.enable', 'title' => 'เปิดใช้งานไซต์ ตรวจ configtest ต้องขึ้น Syntax OK และ reload Apache', 'cmd' => "a2ensite student-hosting.conf\napache2ctl configtest\nsystemctl reload apache2", 'probe' => fn() => self::probeFile('/etc/apache2/sites-enabled/student-hosting.conf', 'เปิดใช้งานไซต์แล้ว', 'ยังไม่ได้ a2ensite')],
                ['key' => '5.open_basedir', 'title' => 'จำกัด open_basedir รายผู้ใช้ (ผ่าน PHP-FPM pool หรือสคริปต์เสริม)', 'warn' => 'mod_php ไม่รองรับตัวแปร ${username} ใน php_admin_value — บรรทัดตัวอย่างในคู่มือจะไม่ทำงานตามที่ตั้งใจ ต้องใช้ PHP-FPM pool แยกต่อผู้ใช้ มิฉะนั้นสคริปต์ PHP ของนักศึกษาจะอ่านไฟล์ของคนอื่นได้'],
            ]],
            ['no' => 6, 'title' => 'สคริปต์สร้างบัญชีผู้ใช้อัตโนมัติ (Provisioning Script)', 'items' => [
                ['key' => '6.script', 'title' => "ติดตั้งสคริปต์ใน $dir (create_user.sh, delete_user.sh, set_quota.sh, suspend_user.sh, set_password.sh, usage_report.sh)", 'cmd' => "mkdir -p $dir\ncp server-scripts/*.sh $dir/", 'probe' => fn() => self::probeScripts($dir)],
                ['key' => '6.chmod', 'title' => 'ให้สิทธิ์รันสคริปต์ (chmod 700 เจ้าของ root)', 'cmd' => "chown root:root $dir/*.sh\nchmod 700 $dir/*.sh", 'probe' => fn() => self::probeScriptPerms("$dir/create_user.sh")],
                ['key' => '6.sudoers', 'title' => 'อนุญาตให้เว็บพอร์ทัล (www-data) เรียกสคริปต์ผ่าน sudo', 'cmd' => "cp server-scripts/sudoers-hosting-portal /etc/sudoers.d/hosting-portal\nchmod 440 /etc/sudoers.d/hosting-portal\nvisudo -cf /etc/sudoers.d/hosting-portal", 'probe' => fn() => self::probeSudo("$dir/create_user.sh", 'sudo ไปยัง create_user.sh'), 'extra' => true],
                ['key' => '6.mode', 'title' => 'เปลี่ยนโหมดสร้างบัญชีของพอร์ทัลเป็น exec (ตั้งค่าระบบ)', 'probe' => fn() => Provisioner::isExec() ? ['pass', 'provision_mode = exec'] : ['fail', 'ยังเป็นโหมด simulate — บัญชีที่สมัครจะไม่ถูกสร้างบนเซิร์ฟเวอร์จริง'], 'extra' => true],
                ['key' => '6.test', 'title' => 'ทดสอบสร้างบัญชีทดลอง ต้องเห็น SSH password, Database password, Base URL ครบ', 'cmd' => "$dir/create_user.sh student01 800 1024"],
            ]],
            ['no' => 7, 'title' => 'ทดสอบระบบแบบครบวงจร (End-to-End)', 'items' => [
                ['key' => '7.sftp', 'title' => 'เชื่อมต่อ SFTP ด้วย FileZilla → เห็นเฉพาะโฟลเดอร์ของตนเอง (public_html, logs, backup, tmp)'],
                ['key' => '7.web', 'title' => "อัปโหลด index.php ใน public_html แล้วเปิดที่ http://student01.$domain/"],
                ['key' => '7.db', 'title' => 'ทดสอบเชื่อมต่อฐานข้อมูลจากไฟล์ PHP ด้วยข้อมูลที่ได้รับ'],
                ['key' => '7.db_isolation', 'title' => 'ผู้ใช้เชื่อมต่อฐานข้อมูลของผู้ใช้อื่นไม่ได้', 'cmd' => 'mysql -u student01 -p another_user_db'],
                ['key' => '7.quota', 'title' => 'อัปโหลดไฟล์จนเกิน Hard Limit ต้องขึ้น Disk quota exceeded'],
                ['key' => '7.cleanup', 'title' => 'ลบบัญชีทดลองออกหลังทดสอบเสร็จ', 'cmd' => "$dir/delete_user.sh student01", 'probe' => fn() => self::probeNoTestUser('student01')],
            ]],
            ['no' => 8, 'title' => 'งานดูแลระบบต่อเนื่อง (Ongoing Operations)', 'items' => [
                ['key' => '8.sync_cron', 'title' => 'ตั้ง cron ซิงก์การใช้พื้นที่เข้าพอร์ทัลทุก 15 นาที', 'cmd' => '*/15 * * * * www-data php ' . BASE_PATH . '/cron/sync_usage.php', 'probe' => fn() => self::probeSyncFresh(), 'extra' => true],
                ['key' => '8.backup', 'title' => 'สำรองข้อมูลไฟล์และฐานข้อมูลผู้ใช้ทุกวัน (mysqldump + rsync /home)', 'probe' => fn() => self::probeCron(['mysqldump', 'rsync'], 'งานสำรองข้อมูล')],
                ['key' => '8.ssl', 'title' => "ติดตั้ง Let's Encrypt (certbot) และต่ออายุอัตโนมัติ", 'cmd' => 'certbot renew --dry-run', 'probe' => fn() => self::probeBinaries(['certbot'])],
                ['key' => '8.disk', 'title' => 'ตรวจสอบพื้นที่ดิสก์รวม (df -h /home) — พื้นที่ว่างเหลือมากกว่า 10%', 'probe' => fn() => self::probeDiskFree($home)],
                ['key' => '8.mail', 'title' => 'ตั้งค่าการส่งอีเมล OTP (MTA/sendmail) และเปลี่ยน otp_mode เป็น mail', 'probe' => fn() => self::probeMail(), 'extra' => true],
                ['key' => '8.installer', 'title' => 'ลบหรือปิดการเข้าถึงไฟล์ install.php หลังติดตั้งเสร็จ', 'probe' => fn() => is_file(BASE_PATH . '/install.php') ? ['fail', 'ยังพบไฟล์ install.php (ต้องใช้รหัสผ่านฐานข้อมูลเพื่อติดตั้งซ้ำ แต่แนะนำให้ย้ายออกเมื่อใช้งานจริง)'] : ['pass', 'ไม่พบ install.php'], 'extra' => true],
                ['key' => '8.https', 'title' => 'เข้าใช้งานพอร์ทัลผ่าน HTTPS', 'probe' => fn() => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? ['pass', 'หน้านี้เปิดผ่าน HTTPS'] : ['fail', 'หน้านี้เปิดผ่าน HTTP'], 'extra' => true],
            ]],
        ];
    }

    /** Evaluates all probes and merges manual ticks. */
    public static function evaluate(): array
    {
        $manual = [];
        foreach (db()->query('SELECT * FROM runbook_checks')->fetchAll() as $r) $manual[$r['item_key']] = $r;

        $sections = self::sections();
        $total = $done = 0;
        foreach ($sections as &$s) {
            $s['done'] = 0;
            foreach ($s['items'] as &$it) {
                if (isset($it['probe'])) {
                    try {
                        [$state, $msg] = ($it['probe'])();
                    } catch (Throwable $ex) {
                        [$state, $msg] = ['unknown', 'ตรวจไม่ได้: ' . $ex->getMessage()];
                    }
                } else {
                    [$state, $msg] = ['manual', 'ต้องตรวจสอบด้วยตนเองแล้วติ๊กยืนยัน'];
                }
                $m = $manual[$it['key']] ?? null;
                $it['state']      = $state;
                $it['msg']        = $msg;
                $it['ticked']     = (bool)($m['done'] ?? false);
                $it['note']       = $m['note'] ?? '';
                $it['updated_by'] = $m['updated_by'] ?? null;
                $it['updated_at'] = $m['updated_at'] ?? null;
                $it['done']       = $state === 'pass' || $it['ticked'];
                $total++;
                if ($it['done']) { $done++; $s['done']++; }
            }
            unset($it);
        }
        unset($s);
        return ['sections' => $sections, 'total' => $total, 'done' => $done];
    }

    public static function tick(string $key, bool $done, string $note, string $by): void
    {
        db()->prepare('INSERT INTO runbook_checks (item_key, done, note, updated_by) VALUES (?,?,?,?)
                       ON DUPLICATE KEY UPDATE done = VALUES(done), note = VALUES(note), updated_by = VALUES(updated_by)')
            ->execute([$key, $done ? 1 : 0, mb_substr($note, 0, 500), $by]);
    }

    public static function keys(): array
    {
        $out = [];
        foreach (self::sections() as $s) foreach ($s['items'] as $it) $out[] = $it['key'];
        return $out;
    }

    // ---------------------------------------------------------------- probes

    private static function linux(): bool
    {
        return PHP_OS_FAMILY === 'Linux';
    }

    private static function notLinux(): array
    {
        return ['unknown', 'เซิร์ฟเวอร์นี้ไม่ใช่ Linux (' . PHP_OS_FAMILY . ') — ตรวจอัตโนมัติไม่ได้'];
    }

    /** Runs a command without a shell. Returns [exitCode, stdout] or null if proc_open unavailable. */
    private static function exec(array $cmd): ?array
    {
        if (!function_exists('proc_open')) return null;
        $p = @proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($p)) return null;
        $out = stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($p), (string)$out];
    }

    private static function which(string $bin): ?string
    {
        foreach (['/usr/sbin', '/usr/bin', '/sbin', '/bin', '/usr/local/sbin', '/usr/local/bin', '/snap/bin'] as $d) {
            if (@is_file("$d/$bin")) return "$d/$bin";
        }
        return null;
    }

    private static function readable(string $file): ?string
    {
        return @is_readable($file) ? (string)@file_get_contents($file) : null;
    }

    private static function probeOs(): array
    {
        if (!self::linux()) return self::notLinux();
        $rel = self::readable('/etc/os-release') ?? '';
        preg_match('/PRETTY_NAME="?([^"\n]+)/', $rel, $m);
        $turnkey = @is_file('/etc/turnkey_version') ? trim((string)@file_get_contents('/etc/turnkey_version')) : '';
        $name = $m[1] ?? 'Linux';
        return $turnkey !== ''
            ? ['pass', "TurnKey $turnkey ($name)"]
            : ['unknown', "ระบบปฏิบัติการ: $name (ไม่พบ /etc/turnkey_version)"];
    }

    private static function probeStaticIp(): array
    {
        if (!self::linux()) return self::notLinux();
        $ifs = self::readable('/etc/network/interfaces') ?? '';
        if (preg_match('/iface\s+\S+\s+inet\s+static/', $ifs)) return ['pass', 'พบ inet static ใน /etc/network/interfaces'];
        foreach (glob('/etc/netplan/*.yaml') ?: [] as $f) {
            $c = self::readable($f) ?? '';
            if (preg_match('/dhcp4:\s*(no|false)/', $c) && str_contains($c, 'addresses')) return ['pass', "ตั้งค่า static ใน $f"];
        }
        if (preg_match('/inet\s+dhcp/', $ifs)) return ['fail', 'ยังใช้ DHCP ใน /etc/network/interfaces'];
        return ['unknown', 'ไม่พบการตั้งค่าเครือข่ายที่ตรวจได้'];
    }

    private static function probeService(string $name): array
    {
        if (!self::linux()) return self::notLinux();
        $r = self::exec(['systemctl', 'is-active', $name]);
        if ($r === null) return ['unknown', 'เรียก systemctl ไม่ได้'];
        return trim($r[1]) === 'active' ? ['pass', "$name กำลังทำงาน"] : ['fail', "$name สถานะ: " . (trim($r[1]) ?: 'ไม่ทราบ')];
    }

    private static function probeServices(array $names): array
    {
        if (!self::linux()) {
            // Still report what we can see from PHP itself
            $v = self::dbVersion();
            return ['unknown', 'PHP ' . PHP_VERSION . ', ' . $v . ' — ตรวจสถานะบริการ Linux ไม่ได้บนระบบนี้'];
        }
        $bad = [];
        foreach ($names as $n) {
            $r = self::exec(['systemctl', 'is-active', $n]);
            if ($r === null) return ['unknown', 'เรียก systemctl ไม่ได้'];
            if (trim($r[1]) !== 'active') $bad[] = $n;
        }
        return $bad ? ['fail', 'ไม่ทำงาน: ' . implode(', ', $bad)] : ['pass', implode(', ', $names) . ' ทำงานปกติ · PHP ' . PHP_VERSION . ' · ' . self::dbVersion()];
    }

    private static function dbVersion(): string
    {
        try {
            return 'DB ' . db()->query('SELECT VERSION()')->fetchColumn();
        } catch (Throwable) {
            return 'DB ?';
        }
    }

    private static function probeUpgrades(): array
    {
        if (!self::linux()) return self::notLinux();
        $stamp = @filemtime('/var/lib/apt/periodic/update-success-stamp') ?: @filemtime('/var/cache/apt/pkgcache.bin');
        if (!$stamp) return ['unknown', 'ไม่พบข้อมูลการ apt update'];
        $days = (int)floor((time() - $stamp) / 86400);
        return $days <= 7
            ? ['pass', "apt update ล่าสุดเมื่อ $days วันก่อน"]
            : ['fail', "apt update ล่าสุดเมื่อ $days วันก่อน — ควรอัปเดตระบบ"];
    }

    private static function probeDns(string $domain): array
    {
        $ip = gethostbyname($domain);
        if ($ip === $domain) return ['fail', "ยังไม่พบ A record ของ $domain"];
        $probe = 'dnscheck' . random_int(1000, 9999) . '.' . $domain;
        $wip = gethostbyname($probe);
        if ($wip === $probe) return ['fail', "$domain → $ip แต่ Wildcard *.$domain ยังไม่ทำงาน"];
        $local = $_SERVER['SERVER_ADDR'] ?? null;
        $note = ($local && $local !== $ip && !in_array($local, ['127.0.0.1', '::1'], true)) ? " (เซิร์ฟเวอร์นี้คือ $local — ตรวจสอบว่าชี้ถูกเครื่อง)" : '';
        return ['pass', "$domain → $ip, *.$domain → $wip$note"];
    }

    private static function probeBinaries(array $bins): array
    {
        if (!self::linux()) return self::notLinux();
        $missing = array_filter($bins, fn($b) => self::which($b) === null);
        return $missing ? ['fail', 'ไม่พบคำสั่ง: ' . implode(', ', $missing)] : ['pass', 'พบ ' . implode(', ', $bins)];
    }

    private static function probeFstab(string $home): array
    {
        if (!self::linux()) return self::notLinux();
        $fstab = self::readable('/etc/fstab');
        if ($fstab === null) return ['unknown', 'อ่าน /etc/fstab ไม่ได้'];
        $best = null;
        foreach (preg_split('/\R/', $fstab) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            $f = preg_split('/\s+/', $line);
            if (count($f) < 4) continue;
            if (($f[1] === $home || str_starts_with($home, rtrim($f[1], '/') . '/') || $f[1] === '/') &&
                ($best === null || strlen($f[1]) > strlen($best[1]))) $best = $f;
        }
        if (!$best) return ['unknown', "ไม่พบรายการ mount ที่ครอบคลุม $home"];
        return preg_match('/(^|,)(usrquota|usrjquota=|quota)/', $best[3])
            ? ['pass', "{$best[1]} มีตัวเลือก {$best[3]}"]
            : ['fail', "{$best[1]} ยังไม่มี usrquota (ตัวเลือกปัจจุบัน: {$best[3]})"];
    }

    private static function probeMountedQuota(string $home): array
    {
        if (!self::linux()) return self::notLinux();
        $mounts = self::readable('/proc/mounts');
        if ($mounts === null) return ['unknown', 'อ่าน /proc/mounts ไม่ได้'];
        $best = null;
        foreach (preg_split('/\R/', $mounts) as $line) {
            $f = preg_split('/\s+/', trim($line));
            if (count($f) < 4) continue;
            if (($f[1] === $home || $f[1] === '/' || str_starts_with($home, rtrim($f[1], '/') . '/')) &&
                ($best === null || strlen($f[1]) > strlen($best[1]))) $best = $f;
        }
        if (!$best) return ['unknown', 'ไม่พบข้อมูล mount'];
        if (!preg_match('/usrquota|usrjquota/', $best[3])) return ['fail', "{$best[1]} ถูก mount โดยยังไม่มี usrquota — ต้อง remount"];
        foreach (['aquota.user', 'quota.user'] as $qf) {
            if (@file_exists(rtrim($best[1], '/') . "/$qf")) return ['pass', "mount พร้อม usrquota และพบไฟล์ $qf"];
        }
        return ['unknown', 'mount พร้อม usrquota แล้ว แต่ตรวจไฟล์ aquota.user ไม่ได้ (อาจยังไม่ได้ quotacheck)'];
    }

    private static function probeSudo(string $script, string $label): array
    {
        if (!self::linux()) return self::notLinux();
        $r = self::exec(['sudo', '-n', '-l', $script]);
        if ($r === null) return ['unknown', 'เรียก sudo ไม่ได้ (proc_open ถูกปิด?)'];
        return $r[0] === 0 ? ['pass', "$label: อนุญาตแล้ว"] : ['fail', "$label: ยังไม่ได้รับอนุญาต (ติดตั้ง sudoers)"];
    }

    private static function probeGroup(string $group): array
    {
        if (function_exists('posix_getgrnam')) {
            return posix_getgrnam($group) ? ['pass', "พบกลุ่ม $group"] : ['fail', "ยังไม่มีกลุ่ม $group"];
        }
        if (!self::linux()) return self::notLinux();
        $g = self::readable('/etc/group');
        if ($g === null) return ['unknown', 'อ่าน /etc/group ไม่ได้'];
        return preg_match('/^' . preg_quote($group, '/') . ':/m', $g) ? ['pass', "พบกลุ่ม $group"] : ['fail', "ยังไม่มีกลุ่ม $group"];
    }

    private static function probeSshd(): array
    {
        if (!self::linux()) return self::notLinux();
        $files = array_merge(['/etc/ssh/sshd_config'], glob('/etc/ssh/sshd_config.d/*.conf') ?: []);
        $content = '';
        $readAny = false;
        foreach ($files as $f) {
            $c = self::readable($f);
            if ($c !== null) { $content .= "\n" . $c; $readAny = true; }
        }
        if (!$readAny) return ['unknown', 'อ่าน sshd_config ไม่ได้'];
        if (!preg_match('/^\s*Match\s+Group\s+sftp-users/mi', $content)) return ['fail', 'ยังไม่มีบล็อก Match Group sftp-users'];
        $need = ['ChrootDirectory' => '/ChrootDirectory\s+%h/i', 'ForceCommand internal-sftp' => '/ForceCommand\s+internal-sftp/i'];
        $missing = array_keys(array_filter($need, fn($re) => !preg_match($re, $content)));
        return $missing ? ['fail', 'บล็อก Match ยังขาด: ' . implode(', ', $missing)] : ['pass', 'ตั้งค่า Chroot + internal-sftp แล้ว'];
    }

    private static function probeApacheModule(): array
    {
        if (function_exists('apache_get_modules')) {
            return in_array('mod_vhost_alias', apache_get_modules(), true) ? ['pass', 'โหลด mod_vhost_alias แล้ว'] : ['fail', 'ยังไม่ได้โหลด mod_vhost_alias'];
        }
        if (!self::linux()) return self::notLinux();
        return self::probeFile('/etc/apache2/mods-enabled/vhost_alias.load', 'เปิดใช้งาน vhost_alias แล้ว', 'ยังไม่ได้ a2enmod vhost_alias');
    }

    private static function probeVhostConf(string $file, string $domain): array
    {
        if (!self::linux()) return self::notLinux();
        $c = self::readable($file);
        if ($c === null) return @file_exists($file) ? ['unknown', 'มีไฟล์แต่อ่านไม่ได้'] : ['fail', "ยังไม่มี $file"];
        $missing = [];
        if (!preg_match('/VirtualDocumentRoot\s+\S*%1/i', $c)) $missing[] = 'VirtualDocumentRoot';
        if (!str_contains($c, "*.$domain")) $missing[] = "ServerAlias *.$domain";
        return $missing ? ['fail', 'ไฟล์ยังขาด: ' . implode(', ', $missing)] : ['pass', 'ตั้งค่า VirtualDocumentRoot และ ServerAlias แล้ว'];
    }

    private static function probeFile(string $file, string $ok, string $bad): array
    {
        if (!self::linux()) return self::notLinux();
        return @file_exists($file) ? ['pass', $ok] : ['fail', $bad];
    }

    private static function probeScripts(string $dir): array
    {
        if (!self::linux()) return self::notLinux();
        if (!@is_dir($dir)) return ['fail', "ยังไม่มีโฟลเดอร์ $dir"];
        $need = ['create_user.sh', 'delete_user.sh', 'set_quota.sh', 'suspend_user.sh', 'set_password.sh', 'usage_report.sh'];
        $list = @scandir($dir);
        if ($list === false) {
            // chmod 700 on the dir blocks listing for www-data; fall back to per-file existence
            $missing = array_filter($need, fn($f) => !@file_exists("$dir/$f"));
        } else {
            $missing = array_diff($need, $list);
        }
        return $missing ? ['fail', 'ยังไม่มี: ' . implode(', ', $missing)] : ['pass', 'พบสคริปต์ครบ ' . count($need) . ' ไฟล์'];
    }

    private static function probeScriptPerms(string $file): array
    {
        if (!self::linux()) return self::notLinux();
        $st = @stat($file);
        if (!$st) return ['unknown', "ตรวจสิทธิ์ $file ไม่ได้"];
        $mode = $st['mode'] & 0777;
        if ($st['uid'] !== 0) return ['fail', 'เจ้าของไฟล์ไม่ใช่ root'];
        if (!($mode & 0100)) return ['fail', sprintf('ไฟล์ยังรันไม่ได้ (mode %o)', $mode)];
        if ($mode & 0022) return ['fail', sprintf('ผู้อื่นแก้ไขไฟล์ได้ (mode %o) — ควรเป็น 700', $mode)];
        return ['pass', sprintf('root, mode %o', $mode)];
    }

    private static function probeNoTestUser(string $u): array
    {
        if (!self::linux()) return self::notLinux();
        if (function_exists('posix_getpwnam')) {
            return posix_getpwnam($u) ? ['fail', "ยังพบบัญชีทดลอง $u"] : ['unknown', "ไม่พบบัญชี $u (ลบแล้ว หรือยังไม่ได้ทดสอบ) — ติ๊กยืนยันเมื่อทดสอบครบ"];
        }
        return ['unknown', 'ตรวจไม่ได้'];
    }

    private static function probeSyncFresh(): array
    {
        $last = Settings::get('last_usage_sync');
        if ($last === '') return ['fail', 'ยังไม่เคยซิงก์การใช้พื้นที่'];
        $mins = (int)round((time() - strtotime($last)) / 60);
        return $mins <= 30 ? ['pass', "ซิงก์ล่าสุด $mins นาทีที่แล้ว"] : ['fail', "ซิงก์ล่าสุด $mins นาทีที่แล้ว — cron อาจไม่ทำงาน"];
    }

    private static function probeCron(array $needles, string $label): array
    {
        if (!self::linux()) return self::notLinux();
        $files = array_merge(['/etc/crontab'], glob('/etc/cron.d/*') ?: [], glob('/etc/cron.daily/*') ?: []);
        $found = [];
        $readAny = false;
        foreach ($files as $f) {
            $c = self::readable($f);
            if ($c === null) continue;
            $readAny = true;
            foreach ($needles as $n) if (str_contains($c, $n)) $found[$n] = basename($f);
        }
        if (!$readAny) return ['unknown', 'อ่านไฟล์ cron ไม่ได้'];
        $missing = array_diff($needles, array_keys($found));
        if (!$missing) return ['pass', "$label: พบใน " . implode(', ', array_unique($found))];
        return ['unknown', "ไม่พบ " . implode(', ', $missing) . ' ใน /etc/cron* (อาจอยู่ใน crontab ของ root ซึ่งตรวจไม่ได้)'];
    }

    private static function probeDiskFree(string $home): array
    {
        $path = @is_dir($home) ? $home : BASE_PATH;
        $free = @disk_free_space($path);
        $total = @disk_total_space($path);
        if (!$free || !$total) return ['unknown', 'อ่านพื้นที่ดิสก์ไม่ได้'];
        $pct = $free / $total * 100;
        $txt = sprintf('%s: ว่าง %.1f / %.1f GB (%.0f%%)', $path, $free / 1073741824, $total / 1073741824, $pct);
        return $pct > 10 ? ['pass', $txt] : ['fail', $txt . ' — พื้นที่ใกล้เต็ม'];
    }

    private static function probeMail(): array
    {
        if (Settings::get('otp_mode') !== 'mail') return ['fail', 'otp_mode = display (แสดง OTP บนจอ ใช้ได้เฉพาะการทดสอบ)'];
        $sm = (string)ini_get('sendmail_path');
        if (self::linux()) {
            $bin = strtok($sm, ' ') ?: '/usr/sbin/sendmail';
            return @is_file($bin) ? ['pass', "พบ $bin"] : ['fail', "ไม่พบ $bin — ติดตั้ง MTA (เช่น postfix หรือ msmtp)"];
        }
        return ['unknown', 'ตรวจ MTA บนระบบนี้ไม่ได้ ให้ทดสอบส่ง OTP จริง'];
    }
}
