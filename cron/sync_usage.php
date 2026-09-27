<?php
// ซิงก์การใช้พื้นที่ดิสก์จาก repquota เข้าพอร์ทัล — ตั้ง cron ทุก 15 นาที:
//   */15 * * * * www-data php /path/to/portal/cron/sync_usage.php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/sync_lib.php';
if (!is_installed()) { fwrite(STDERR, "portal not installed\n"); exit(1); }
try {
    $n = sync_usage();
    echo date('c') . ' synced ' . ($n === null ? '(simulate mode)' : "$n users") . "\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
