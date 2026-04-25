if ! rm -rf phpmyadmin; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! rm -rf {{ $path }}; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! wget https://files.phpmyadmin.net/phpMyAdmin/{{ $version }}/phpMyAdmin-{{ $version }}-all-languages.zip; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! unzip phpMyAdmin-{{ $version }}-all-languages.zip; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! rm -rf phpMyAdmin-{{ $version }}-all-languages.zip; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! mv phpMyAdmin-{{ $version }}-all-languages {{ $path }}; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! mv {{ $path }}/config.sample.inc.php {{ $path }}/config.inc.php; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

echo "PHPMyAdmin installed!"
