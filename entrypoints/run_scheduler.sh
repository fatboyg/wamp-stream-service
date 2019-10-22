#!/bin/bash
# Runs the job worker
#
# Preparation
RUN_USER="${RUN_USER:-runuser}";

cd "$APP_HOME";

# Main command
while [ true ]
do
  /usr/src/app/entrypoints/run_artisan.sh schedule:run --quiet --no-interaction
  sleep 10
done

echo "Service stopped";
