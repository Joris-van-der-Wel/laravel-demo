#!/usr/bin/env bash
set -e
cd "$(dirname "$0")"
cd sharetool
exec ./vendor/bin/sail "$@"
