if ! DEBIAN_FRONTEND=noninteractive unzip {{ $file }}.zip; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo -u postgres psql -d {{ $database }} -f {{ $file }}.sql; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! rm {{ $file }}.sql {{ $file }}.zip; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi
