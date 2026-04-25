if ! sudo mysql -e "DROP USER IF EXISTS '{{ $username }}'@'{{ $host }}'"; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo mysql -e "FLUSH PRIVILEGES"; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

echo "Command executed"
