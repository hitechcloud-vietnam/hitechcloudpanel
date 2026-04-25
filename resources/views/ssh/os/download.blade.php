if ! wget {{ $url }} -O {{ $path }}; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi
