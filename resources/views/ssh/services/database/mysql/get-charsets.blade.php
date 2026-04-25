if ! sudo mysql -e "SHOW COLLATION;"; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi
