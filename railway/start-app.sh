#!/usr/bin/env sh

set -eu

media_bundle="/app/railway/catalog-media"
media_target="${RAILWAY_VOLUME_MOUNT_PATH:-/app/storage/app/public}"
media_marker="${media_target}/.catalog-media-v1"

if [ "${CATALOG_BOOTSTRAP_ENABLED:-false}" = "true" ] && [ -d "${media_bundle}" ] && [ ! -f "${media_marker}" ]; then
    echo "Copying the initial catalog media to persistent storage ..."
    mkdir -p "${media_target}"
    cp -R "${media_bundle}/." "${media_target}/"
    touch "${media_marker}"
    echo "Initial catalog media is ready."
fi

exec /start-container.sh
