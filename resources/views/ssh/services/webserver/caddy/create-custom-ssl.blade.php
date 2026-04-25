if ! sudo mkdir -p {{ $path }}; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! echo "{{ $certificate }}" | sudo tee {{ $certificatePath }}; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

if ! echo "{{ $pk }}" | sudo tee {{ $pkPath }}; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi

echo "Successfully received certificate"
