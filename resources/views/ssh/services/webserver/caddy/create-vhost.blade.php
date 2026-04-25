if ! sudo ln -s /etc/caddy/sites-available/{{ $domain }} /etc/caddy/sites-enabled/; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo service caddy restart; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi
