@if($sudo) sudo @endif tee {!! $path !!} << 'HITECHCLOUDPANEL_SSH_EOF' > /dev/null
{!! $content !!}
HITECHCLOUDPANEL_SSH_EOF

if [ $? -eq 0 ]; then
    echo "Successfully wrote to {{ $path }}"
else
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi
