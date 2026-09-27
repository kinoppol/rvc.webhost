<?php
declare(strict_types=1);

/**
 * Pulls per-user disk usage from repquota (exec mode) and records the daily total.
 * Returns number of users updated, or null when no real quota data is available (simulate mode).
 */
function sync_usage(): ?int
{
    $usage = Provisioner::usage();
    $n = null;
    if ($usage !== null) {
        $n = 0;
        $st = db()->prepare("UPDATE users SET disk_used_mb = ?, disk_synced_at = NOW() WHERE username = ? AND role = 'student'");
        foreach ($usage as $user => $mb) {
            $st->execute([$mb, $user]);
            $n += $st->rowCount();
        }
    }
    $row = db()->query("SELECT COALESCE(SUM(disk_used_mb),0) used, COUNT(*) c FROM users WHERE role='student' AND status IN ('active','suspended')")->fetch();
    db()->prepare('INSERT INTO disk_history (day, used_mb, total_mb, users_count) VALUES (CURDATE(), ?, ?, ?)
                   ON DUPLICATE KEY UPDATE used_mb = VALUES(used_mb), total_mb = VALUES(total_mb), users_count = VALUES(users_count)')
        ->execute([$row['used'], Settings::int('disk_total_gb') * 1024, $row['c']]);
    Settings::set('last_usage_sync', date('Y-m-d H:i:s'));
    return $n;
}
