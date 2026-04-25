if ! sudo supervisorctl stop {{ $id }}:*; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo rm -rf ~/.logs/workers/{{ $id }}.log; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo rm -rf /etc/supervisor/conf.d/{{ $id }}.conf; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo supervisorctl reread; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo supervisorctl update; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi
