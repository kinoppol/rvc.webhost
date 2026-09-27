#!/bin/bash
# ปรับโควตา: set_quota.sh <username> <soft_mb> <hard_mb>
set -euo pipefail
USERNAME="$1"; SOFT_MB="$2"; HARD_MB="$3"
[[ "$USERNAME" =~ ^[a-z0-9]{4,16}$ && "$SOFT_MB" =~ ^[0-9]+$ && "$HARD_MB" =~ ^[0-9]+$ ]] || exit 2
id -nG "$USERNAME" | grep -qw sftp-users || exit 1
setquota -u "$USERNAME" $((SOFT_MB * 1024)) $((HARD_MB * 1024)) 0 0 "${HOME_FS:-/home}"
