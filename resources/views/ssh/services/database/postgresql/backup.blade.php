if ! sudo -u postgres pg_dump -d {{ $database }} -f /var/lib/postgresql/{{ $file }}.sql; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo mv /var/lib/postgresql/{{ $file }}.sql /home/hitechcloudpanel/{{ $file }}.sql; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo chown hitechcloudpanel:hitechcloudpanel /home/hitechcloudpanel/{{ $file }}.sql; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! DEBIAN_FRONTEND=noninteractive zip {{ $file }}.zip {{ $file }}.sql; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! rm {{ $file }}.sql; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi
