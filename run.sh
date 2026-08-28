#!/usr/bin/env bash
#
# Connects to the docker container and runs the given command.
#
# Usage:
#   ./run.sh <command>    runs the command inside the php container

set -e

cd "$(dirname "$0")"

if docker compose version > /dev/null 2>&1; then
    COMPOSE="docker compose"
else
    COMPOSE="docker-compose"
fi

if [ $# -eq 0 ]; then
    echo 'Error: missing command. Usage: ./run.sh <command>' >&2
    exit 1
fi

# run in a new container, --rm removes it on exit (no dead containers left behind)
$COMPOSE run --rm php bash -c "$(printf '%q ' "$@")"