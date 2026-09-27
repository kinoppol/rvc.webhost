#!/bin/bash
# รายงานการใช้พื้นที่ (ใช้โดย cron/sync_usage.php)
exec repquota -u "${HOME_FS:-/home}"
