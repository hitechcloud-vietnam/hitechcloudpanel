if ! cd {{ $path }}; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! git fetch origin; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi
