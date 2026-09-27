#!/bin/bash
# เปลี่ยนรหัสผ่าน: echo "newpass" | set_password.sh <username> ssh|db
set -euo pipefail
USERNAME="$1"; KIND="$2"
[[ "$USERNAME" =~ ^[a-z0-9]{4,16}$ ]] || exit 2
id -nG "$USERNAME" | grep -qw sftp-users || exit 1
IFS= read -r NEWPASS
[ ${#NEWPASS} -ge 8 ] || exit 3
if [ "$KIND" = "ssh" ]; then
  echo "$USERNAME:$NEWPASS" | chpasswd
else
  ESC=$(printf '%s' "$NEWPASS" | sed "s/\\/\\\\/g; s/'/\\'/g")
  mysql -u root -e "ALTER USER '${USERNAME}'@'localhost' IDENTIFIED BY '${ESC}';"
fi
