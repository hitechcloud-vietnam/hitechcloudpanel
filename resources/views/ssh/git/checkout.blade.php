if ! cd {{ $path }}; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! git checkout -f {{ $branch }}; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi
