# Student Hosting Portal — การติดตั้งพอร์ทัล

ต้องใช้ PHP 8.0+ (pdo_mysql, mbstring, openssl) และ MariaDB 10+

1. วางโค้ดใน DocumentRoot แล้วให้ `www-data` เขียนโฟลเดอร์ `config/`, `storage/` (และ `migrations/` ถ้าจะสร้าง migration จากหน้าเว็บ)
2. เปิด `install.php` → ตรวจแพ็กเกจ/สิทธิ์ไฟล์ → กรอกฐานข้อมูล → ตั้งผู้ดูแลระบบ
3. เข้าสู่ระบบผู้ดูแล → เมนู **เช็กลิสต์ติดตั้งเซิร์ฟเวอร์** จะแสดงงานที่เหลือตามคู่มือ (installation-runbook.md หัวข้อ 1–8)
4. คัดลอก `server-scripts/*.sh` ไป `/opt/hosting/` และ `server-scripts/sudoers-hosting-portal` ไป `/etc/sudoers.d/` แล้วเปลี่ยนโหมดสร้างบัญชีเป็น `exec`
5. ตั้ง cron: `*/15 * * * * www-data php /path/to/portal/cron/sync_usage.php`

**ติดตั้งซ้ำ:** เปิด `install.php` อีกครั้ง (ต้องล็อกอินเป็นผู้ดูแล หรือกรอกผู้ใช้/รหัสผ่านฐานข้อมูลใน `config/config.php`) แล้วเลือก "อัปเกรด" (เก็บข้อมูล) หรือ "ติดตั้งใหม่ทั้งหมด"

**Migrations:** ไฟล์ใน `migrations/` จัดการได้ที่เมนู Migrations (รัน / ย้อนกลับ / สร้างไฟล์ใหม่)
