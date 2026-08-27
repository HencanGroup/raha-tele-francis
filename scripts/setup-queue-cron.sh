#!/bin/bash
# setup-queue-cron.sh
# Installs a cron job that runs queue:work every minute for the RAHA app.
# On shared hosting, persistent daemons aren't possible — cron replaces them.
#
# Usage: bash scripts/setup-queue-cron.sh <app_dir>
# Example: bash scripts/setup-queue-cron.sh ~/softwares/staging.raha-tele

APP_DIR="${1:?Usage: $0 <app_dir>}"
CRON_TAG="raha-queue-worker"

# Build the cron line — run queue:work every minute, stop when empty,
# only process the default queue, retry on failure.
CRON_LINE="* * * * * cd ${APP_DIR} && /usr/local/bin/php artisan queue:work --stop-when-empty --tries=3 --timeout=120 >> storage/logs/queue.log 2>&1 # ${CRON_TAG}"

# Remove any existing cron entry for this app (idempotent)
EXISTING=$(crontab -l 2>/dev/null | grep -v "# ${CRON_TAG}" | grep -v "queue:work.*${CRON_TAG}")
# If there's a stale line without the tag, also clean it
EXISTING=$(echo "$EXISTING" | grep -v "queue:work.*${APP_DIR}")

# Add the new line
echo "${EXISTING}
${CRON_LINE}" | crontab -

echo "✓ Cron job installed for ${APP_DIR}"
echo "  → Runs: queue:work --stop-when-empty every minute"
echo "  → Logs: ${APP_DIR}/storage/logs/queue.log"
echo ""
crontab -l 2>/dev/null | grep "${CRON_TAG}"
