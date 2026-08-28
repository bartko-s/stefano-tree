#!/usr/bin/env bash
#
# Connects to the selected docker container and runs the given command.
#
# Usage:
#   ./run.sh <container> <command>    runs the command inside the given container (php8.2 - php8.5)

set -e

cd "$(dirname "$0")"

if docker compose version > /dev/null 2>&1; then
    COMPOSE="docker compose"
else
    COMPOSE="docker-compose"
fi

if [ $# -lt 2 ]; then
    echo 'Error: missing arguments. Usage: ./run.sh <php8.2|php8.3|php8.4|php8.5> <command>' >&2
    exit 1
fi

CONTAINER="$1"
shift

# validate the container name (all images are alpine-based and have no bash, only sh)
case "$CONTAINER" in
    php8.2|php8.3|php8.4|php8.5) ;;
    *)
        echo "Error: unknown container '$CONTAINER'. Usage: ./run.sh <php8.2|php8.3|php8.4|php8.5> <command>" >&2
        exit 1
        ;;
esac

# run in a new container, --rm removes it on exit (no dead containers left behind)
$COMPOSE run --rm "$CONTAINER" sh -c "$(printf '%q ' "$@")"