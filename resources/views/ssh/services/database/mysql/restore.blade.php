if ! DEBIAN_FRONTEND=noninteractive unzip {{ $file }}.zip; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo DEBIAN_FRONTEND=noninteractive mysql -u root {{ $database }} < {{ $file }}.sql; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! rm {{ $file }}.sql {{ $file }}.zip; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi
