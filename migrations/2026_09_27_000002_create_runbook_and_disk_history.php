<?php
return [
    'description' => 'สร้างตาราง runbook_checks (เช็กลิสต์ติดตั้งเซิร์ฟเวอร์) และ disk_history (ประวัติการใช้ดิสก์รวม)',
    'up' => [
        "CREATE TABLE IF NOT EXISTS runbook_checks (
            item_key VARCHAR(64) NOT NULL PRIMARY KEY,
            done TINYINT(1) NOT NULL DEFAULT 0,
            note VARCHAR(500) NULL,
            updated_by VARCHAR(32) NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS disk_history (
            day DATE NOT NULL PRIMARY KEY,
            used_mb DECIMAL(14,1) NOT NULL DEFAULT 0,
            total_mb DECIMAL(14,1) NOT NULL DEFAULT 0,
            users_count INT UNSIGNED NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        'DROP TABLE IF EXISTS disk_history',
        'DROP TABLE IF EXISTS runbook_checks',
    ],
];
