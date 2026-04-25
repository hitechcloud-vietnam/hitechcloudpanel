if ! sudo supervisorctl restart all; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

echo "All workers restarted successfully."
