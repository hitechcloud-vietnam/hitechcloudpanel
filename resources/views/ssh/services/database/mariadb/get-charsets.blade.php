if ! sudo mariadb -e "SHOW COLLATION;";
then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi
