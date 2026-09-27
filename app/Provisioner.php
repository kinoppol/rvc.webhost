<?php
declare(strict_types=1);

/**
 * Bridges the portal to the server-side provisioning scripts (runbook sections 6 and 8).
 *
 * provision_mode = simulate : nothing touches the server; only the portal DB changes (for testing/dev).
 * provision_mode = exec     : runs `sudo -n <script_dir>/<script>` — requires the sudoers entry
 *                             from server-scripts/sudoers-hosting-portal.
 */
final class Provisioner
{
    public static function isExec(): bool
    {
        return Settings::get('provision_mode') === 'exec';
    }

    /** @return array{ssh_password:string, db_password:string} */
    public static function create(string $username, int $softMb, int $hardMb): array
    {
        if (!self::isExec()) {
            return ['ssh_password' => random_password(), 'db_password' => random_password()];
        }
        $out = self::run('create_user.sh', [$username, (string)$softMb, (string)$hardMb]);
        preg_match('/SSH\/SFTP password\s*:\s*(\S+)/', $out, $ssh);
        preg_match('/Database password\s*:\s*(\S+)/', $out, $dbp);
        if (!$ssh || !$dbp) throw new RuntimeException('อ่านผลลัพธ์จาก create_user.sh ไม่ได้: ' . $out);
        return ['ssh_password' => $ssh[1], 'db_password' => $dbp[1]];
    }

    public static function delete(string $username): void
    {
        if (self::isExec()) self::run('delete_user.sh', [$username]);
    }

    public static function setQuota(string $username, int $softMb, int $hardMb): void
    {
        if (self::isExec()) self::run('set_quota.sh', [$username, (string)$softMb, (string)$hardMb]);
    }

    public static function suspend(string $username, bool $suspend): void
    {
        if (self::isExec()) self::run('suspend_user.sh', [$username, $suspend ? 'lock' : 'unlock']);
    }

    /** $kind = ssh | db ; password is passed on stdin so it never appears in the process list */
    public static function setPassword(string $username, string $kind, string $password): void
    {
        if (self::isExec()) self::run('set_password.sh', [$username, $kind], $password . "\n");
    }

    /** Returns [username => used_mb] from `repquota`, or null when unavailable. */
    public static function usage(): ?array
    {
        if (!self::isExec()) return null;
        $out = self::run('usage_report.sh', []);
        $res = [];
        foreach (preg_split('/\R/', $out) as $line) {
            // repquota -u line: user  --  used_kb soft hard grace files ...
            if (preg_match('/^([a-z0-9]+)\s+[-+]{2}\s+(\d+)/', trim($line), $m)) {
                $res[$m[1]] = round((int)$m[2] / 1024, 1);
            }
        }
        return $res;
    }

    private static function run(string $script, array $args, ?string $stdin = null): string
    {
        foreach ($args as $a) {
            if (!preg_match('/^[a-z0-9]+$/', $a)) throw new InvalidArgumentException('อาร์กิวเมนต์ไม่ถูกต้อง');
        }
        $path = rtrim(Settings::get('script_dir'), '/') . '/' . $script;
        $cmd = array_merge(['sudo', '-n', $path], $args);
        $proc = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($proc)) throw new RuntimeException('เรียกใช้คำสั่งบนเซิร์ฟเวอร์ไม่ได้ (proc_open ถูกปิด?)');
        if ($stdin !== null) fwrite($pipes[0], $stdin);
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);
        if ($code !== 0) {
            throw new RuntimeException("$script ล้มเหลว (exit $code): " . trim($err ?: $out));
        }
        return (string)$out;
    }
}
