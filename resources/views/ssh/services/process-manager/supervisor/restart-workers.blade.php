@foreach($workerIds as $workerId)
if ! sudo supervisorctl restart {{ $workerId }}:*; then
    echo 'HITECHCLOUDPANEL_SSH_ERROR' && exit 1
fi
@endforeach

echo "Workers restarted successfully."
