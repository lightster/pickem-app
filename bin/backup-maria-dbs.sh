#!/bin/bash

set -e
set -o pipefail

export MYSQL_PWD="${MYSQL_PASSWORD}"

YEAR=$(date +%Y)
WEEK=$(date +%U)
HOUR=$(date +%H)
DAY_OF_MONTH=$(date +%d)

DROPBOX_DIR="/maria-backups"
DROPBOX_CURRENT_DIR="${DROPBOX_DIR}/latest"
DROPBOX_DAILY_DIR="${DROPBOX_DIR}/historical/${YEAR}/daily-${DAY_OF_MONTH}"
DROPBOX_WEEKLY_DIR="${DROPBOX_DIR}/historical/${YEAR}/weekly-${WEEK}"
DROPBOX_HOURLY_DIR="${DROPBOX_DIR}/historical/${YEAR}/hourly-${HOUR}"
HISTORICAL_DIRS=("${DROPBOX_DAILY_DIR}" "${DROPBOX_WEEKLY_DIR}" "${DROPBOX_HOURLY_DIR}")

echo -en "=====\n\nBacking up MariaDB databases @ $(date)\n\n--"

for DATABASE in $(mysql -u "${MYSQL_USER}" -N -e 'show databases'); do
  if [[ $DATABASE == "information_schema" || $DATABASE == "performance_schema" ]]; then
    continue
  fi

  CURRENT_SNAPSHOT_PATH="${DROPBOX_CURRENT_DIR}/${DATABASE}.sql.gz"

  echo -e "\n\nSnapshotting '${DATABASE}' and sending it to Dropbox"
  mysqldump -u "${MYSQL_USER}" "${DATABASE}" | gzip | http https://content.dropboxapi.com/2/files/upload \
    Authorization:"Bearer ${DROPBOX_TOKEN}" \
    Content-Type:application/octet-stream \
    Dropbox-API-Arg:"{\"path\": \"${CURRENT_SNAPSHOT_PATH}\", \"mode\": \"overwrite\", \"mute\": true}"

  for HISTORICAL_DIR in "${HISTORICAL_DIRS[@]}"; do
    SNAPSHOT_PATH="${HISTORICAL_DIR}/${DATABASE}.sql.gz"

    echo -e "\n\nDeleting historical existing ${SNAPSHOT_PATH} snapshot"
    http --ignore-stdin https://api.dropboxapi.com/2/files/delete_v2 \
      Authorization:"Bearer ${DROPBOX_TOKEN}" \
      path="${SNAPSHOT_PATH}" \
    || true

    echo -e "\n\nCopying current snapshot to ${SNAPSHOT_PATH}"
    http --ignore-stdin https://api.dropboxapi.com/2/files/copy_v2 \
      Authorization:"Bearer ${DROPBOX_TOKEN}" \
      from_path="${CURRENT_SNAPSHOT_PATH}" \
      to_path="${SNAPSHOT_PATH}"
  done

  echo -en "\n\n--"
done

echo -e "\n\nNotifying Honeybadger of completion"
http --ignore-stdin "${HONEYBADGER_MARIA_BACKUPS_CHECKIN}"

echo -en "\n\n--\n\nAll done @ $(date)\n\n"
