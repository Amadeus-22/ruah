#!/bin/sh
# Backup do MySQL todo dia às 03:00 (horário de Brasília), comprimido, guardando 14 dias.
# Manual: docker compose -f compose.prod.yaml --env-file .env.production exec backup sh /backup.sh agora
set -eu

dump() {
  file="/backups/ruah-$(date +%Y%m%d-%H%M).sql.gz"
  if MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqldump -h db -u root --single-transaction --routines "$MYSQL_DATABASE" | gzip > "$file.tmp"; then
    mv "$file.tmp" "$file"
    echo "backup ok: $file"
  else
    rm -f "$file.tmp"
    echo "backup FALHOU" >&2
  fi
  find /backups -name 'ruah-*.sql.gz' -mtime +14 -delete
}

if [ "${1:-}" = "agora" ]; then
  dump
  exit 0
fi

while true; do
  now=$(date +%s)
  next=$(date -d "$(date +%F) 03:00" +%s 2>/dev/null || echo 0)
  [ "$next" -gt "$now" ] || next=$((next + 86400))
  sleep $((next - now))
  dump
done
