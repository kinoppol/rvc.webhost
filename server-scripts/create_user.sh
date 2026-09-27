#!/bin/bash
# สร้างบัญชีนักศึกษา (คู่มือหัวข้อ 6)
# ใช้งาน: create_user.sh <username> <soft_quota_mb> <hard_quota_mb>
set -euo pipefail
USERNAME="$1"
SOFT_MB="${2:-500}"
HARD_MB="${3:-550}"
HOME_FS="${HOME_FS:-/home}"

[[ "$USERNAME" =~ ^[a-z0-9]{4,16}$ ]] || { echo "username ไม่ถูกต้อง" >&2; exit 2; }
if id "$USERNAME" &>/dev/null; then echo "ผู้ใช้ $USERNAME มีอยู่แล้ว" >&2; exit 1; fi

useradd -m -g sftp-users -s /usr/sbin/nologin "$USERNAME"
mkdir -p /home/$USERNAME/{public_html,logs,backup,tmp}
chown "$USERNAME:sftp-users" /home/$USERNAME/{public_html,logs,backup,tmp}
chown root:root /home/$USERNAME
chmod 755 /home/$USERNAME

# หมายเหตุ: ไม่ใช้ `chage -d 0` เพราะผู้ใช้ที่ถูกบังคับ ForceCommand internal-sftp
# จะเปลี่ยนรหัสผ่านตอนล็อกอินไม่ได้ ทำให้เข้า SFTP ไม่ได้เลย — ให้เปลี่ยนผ่านพอร์ทัลแทน
SSH_PASS=$(openssl rand -base64 12)
echo "$USERNAME:$SSH_PASS" | chpasswd

setquota -u "$USERNAME" $((SOFT_MB * 1024)) $((HARD_MB * 1024)) 0 0 "$HOME_FS"

DB_PASS=$(openssl rand -base64 12)
mysql -u root <<SQL
CREATE DATABASE \`${USERNAME}\`;
CREATE USER '${USERNAME}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${USERNAME}\`.* TO '${USERNAME}'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "=================================="
echo "สร้างบัญชีสำเร็จ: $USERNAME"
echo "SSH/SFTP password : $SSH_PASS"
echo "Database name      : $USERNAME"
echo "Database user      : $USERNAME"
echo "Database password  : $DB_PASS"
echo "=================================="
