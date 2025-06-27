#!/bin/bash

# Get the absolute path of the script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Create the cron job command
# Run at 9:00 AM every day
CRON_CMD="0 9 * * * /usr/bin/php ${SCRIPT_DIR}/cron.php >> ${SCRIPT_DIR}/cron.log 2>&1"

# Remove any existing cron job for this script
(crontab -l 2>/dev/null | grep -v "cron.php") | crontab -

# Add the new cron job
(crontab -l 2>/dev/null; echo "$CRON_CMD") | crontab -

echo "✅ CRON job has been set up successfully!"
echo "📅 Comics will be sent daily at 9:00 AM"
echo "📝 Logs will be written to: ${SCRIPT_DIR}/cron.log"