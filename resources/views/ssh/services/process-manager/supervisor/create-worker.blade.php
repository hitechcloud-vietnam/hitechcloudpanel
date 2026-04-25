if ! sudo mkdir -p "$(dirname {{ $logFile }})"; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo touch {{ $logFile }}; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo chown {{ $user }}:{{ $user }} {{ $logFile }}; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo supervisorctl reread; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo supervisorctl update; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo supervisorctl start {{ $id }}:*; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi
