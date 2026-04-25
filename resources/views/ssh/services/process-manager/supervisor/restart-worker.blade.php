if ! sudo supervisorctl restart {{ $id }}:*; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi
