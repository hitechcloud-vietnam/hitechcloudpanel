if ! sudo -u postgres psql -c "CREATE ROLE \"{{ $username }}\" WITH LOGIN PASSWORD '{{ $password }}';"; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

echo "User {{ $username }} created"
