if ! sudo certbot certonly --force-renewal --nginx --noninteractive --agree-tos --cert-name {{ $name }} -m {{ $email }} {{ $domains }} --verbose; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi
