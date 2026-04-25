if ! sudo ufw default deny incoming; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo ufw default allow outgoing; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo ufw allow from 0.0.0.0/0 to any proto tcp port 22; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo ufw allow from 0.0.0.0/0 to any proto tcp port 80; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo ufw allow from 0.0.0.0/0 to any proto tcp port 443; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo ufw --force enable; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo ufw reload; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi
