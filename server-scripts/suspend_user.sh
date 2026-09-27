#!/bin/bash
# ระงับ/ยกเลิกระงับ: suspend_user.sh <username> lock|unlock
set -euo pipefail
USERNAME="$1"; ACTION="$2"
[[ "$USERNAME" =~ ^[a-z0-9]{4,16}$ ]] || exit 2
id -nG "$USERNAME" | grep -qw sftp-users || exit 1
if [ "$ACTION" = "lock" ]; then
  usermod -L "$USERNAME"
  mysql -u root -e "ALTER USER '${USERNAME}'@'localhost' ACCOUNT LOCK;"
  chmod 000 "/home/$USERNAME/public_html"
else
  usermod -U "$USERNAME"
  mysql -u root -e "ALTER USER '${USERNAME}'@'localhost' ACCOUNT UNLOCK;"
  chmod 755 "/home/$USERNAME/public_html"
fi
