#!/bin/bash
# ลบบัญชีนักศึกษา (คู่มือหัวข้อ 8)
set -euo pipefail
USERNAME="$1"
[[ "$USERNAME" =~ ^[a-z0-9]{4,16}$ ]] || { echo "username ไม่ถูกต้อง" >&2; exit 2; }
id -nG "$USERNAME" 2>/dev/null | grep -qw sftp-users || { echo "$USERNAME ไม่ใช่ผู้ใช้ในกลุ่ม sftp-users" >&2; exit 1; }
mysql -u root -e "DROP DATABASE IF EXISTS \`${USERNAME}\`; DROP USER IF EXISTS '${USERNAME}'@'localhost';"
deluser --remove-home "$USERNAME"
echo "ลบบัญชี $USERNAME เรียบร้อย"
