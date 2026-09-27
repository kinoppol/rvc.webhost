<?php
declare(strict_types=1);

final class Settings
{
    /** name => [default, label, help] */
    public const DEFS = [
        'domain'            => ['hosting.college.ac.th', 'โดเมนหลักของบริการ', 'ใช้สร้าง URL เว็บไซต์ username.<โดเมน> (ต้องตั้ง Wildcard DNS)'],
        'sftp_host'         => ['sftp.hosting.college.ac.th', 'SFTP Host', 'ชื่อโฮสต์ที่นักศึกษาใช้เชื่อมต่อ FileZilla'],
        'email_domain'      => ['college.ac.th', 'โดเมนอีเมลสถาบัน', 'รับสมัครเฉพาะอีเมลที่ลงท้ายด้วยโดเมนนี้ (เว้นว่างเพื่อไม่จำกัด)'],
        'max_users'         => ['300', 'จำนวนบัญชีสูงสุด', 'อ้างอิงจากไฟล์คำนวณ Capacity Planning'],
        'disk_total_gb'     => ['150', 'ขีดจำกัดพื้นที่ดิสก์รวม (GB)', 'ใช้แสดงเส้นขีดจำกัดในกราฟภาพรวม'],
        'default_soft_mb'   => ['500', 'Soft limit เริ่มต้น (MB)', ''],
        'default_hard_mb'   => ['550', 'Hard limit เริ่มต้น (MB)', ''],
        'registration_open' => ['1', 'เปิดรับสมัคร', '1 = เปิด, 0 = ปิด'],
        'provision_mode'    => ['simulate', 'โหมดสร้างบัญชี', 'simulate = บันทึกในฐานข้อมูลอย่างเดียว (ทดสอบ), exec = เรียกสคริปต์บนเซิร์ฟเวอร์จริงผ่าน sudo'],
        'script_dir'        => ['/opt/hosting', 'โฟลเดอร์สคริปต์ Provisioning', 'ต้องมี create_user.sh และ delete_user.sh ตามคู่มือหัวข้อ 6 และ 8'],
        'home_path'         => ['/home', 'พาร์ทิชันที่เปิดโควตา', 'ตามคู่มือหัวข้อ 3'],
        'otp_mode'          => ['mail', 'การส่ง OTP', 'mail = ส่งผ่าน mail() ของ PHP, display = แสดงรหัสบนหน้าจอ (ใช้ทดสอบเท่านั้น)'],
        'courses'           => ['การพัฒนาเว็บเบื้องต้น,PHP และ MariaDB,โครงงานคอมพิวเตอร์', 'รายวิชา', 'คั่นด้วยเครื่องหมายจุลภาค — ใช้ในแบบฟอร์มสมัครและตัวกรองผู้ใช้'],
        'mail_from'        => ['no-reply@hosting.college.ac.th', 'อีเมลผู้ส่ง', ''],
    ];

    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = array_map(fn($d) => $d[0], self::DEFS);
            try {
                foreach (db()->query('SELECT name, value FROM settings')->fetchAll() as $r) {
                    self::$cache[$r['name']] = $r['value'];
                }
            } catch (Throwable) {
                // table may not exist yet (before first migration)
            }
        }
        return self::$cache;
    }

    public static function get(string $name): string
    {
        return (string)(self::all()[$name] ?? '');
    }

    public static function int(string $name): int
    {
        return (int)self::get($name);
    }

    public static function set(string $name, string $value): void
    {
        db()->prepare('INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)')
            ->execute([$name, $value]);
        self::$cache = null;
    }
}
