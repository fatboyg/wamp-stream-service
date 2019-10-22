#!/bin/bash

RUN_USER="${RUN_USER:-runuser}";
# Preparation
cd "$APP_HOME";

exec gosu "$RUN_USER" php artisan "$@"



