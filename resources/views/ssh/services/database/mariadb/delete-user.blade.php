if ! sudo mariadb -e "DROP USER IF EXISTS '{{ $username }}'@'{{ $host }}'"; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo mariadb -e "FLUSH PRIVILEGES"; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

echo "Command executed"
