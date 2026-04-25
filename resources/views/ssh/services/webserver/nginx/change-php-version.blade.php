if ! sudo sed -i 's/php{{ $oldVersion }}/php{{ $newVersion }}/g' /etc/nginx/sites-available/{{ $domain }}; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! sudo service nginx restart; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

echo "PHP Version Changed to {{ $newVersion }}"
