<?php
return [
    'description' => 'สร้างตารางหลัก: users, settings, otp_codes, audit_log',
    'up' => [
        "CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(32) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('student','admin') NOT NULL DEFAULT 'student',
            full_name VARCHAR(150) NOT NULL DEFAULT '',
            student_id VARCHAR(20) NULL,
            email VARCHAR(190) NULL,
            course VARCHAR(150) NULL,
            academic_year VARCHAR(4) NULL,
            status ENUM('pending','active','suspended','deleted') NOT NULL DEFAULT 'pending',
            quota_soft_mb INT UNSIGNED NOT NULL DEFAULT 500,
            quota_hard_mb INT UNSIGNED NOT NULL DEFAULT 550,
            disk_used_mb DECIMAL(10,1) NOT NULL DEFAULT 0,
            disk_synced_at DATETIME NULL,
            provisioned_at DATETIME NULL,
            last_login_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_status (status),
            KEY idx_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS settings (
            name VARCHAR(100) NOT NULL PRIMARY KEY,
            value TEXT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS otp_codes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            code_hash VARCHAR(255) NOT NULL,
            attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_user (user_id),
            CONSTRAINT fk_otp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS audit_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            actor VARCHAR(32) NOT NULL,
            action VARCHAR(64) NOT NULL,
            target VARCHAR(64) NOT NULL DEFAULT '',
            detail TEXT NULL,
            ip VARCHAR(45) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        'DROP TABLE IF EXISTS otp_codes',
        'DROP TABLE IF EXISTS audit_log',
        'DROP TABLE IF EXISTS settings',
        'DROP TABLE IF EXISTS users',
    ],
];
