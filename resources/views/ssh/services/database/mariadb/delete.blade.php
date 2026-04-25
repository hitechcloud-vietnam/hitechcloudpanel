if ! sudo mariadb -e "DROP DATABASE IF EXISTS {{ $name }}"; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

echo "Command executed"
