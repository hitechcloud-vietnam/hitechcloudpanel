set -e

CURRENT_HOSTNAME=$(hostname)
if [ -n "$CURRENT_HOSTNAME" ]; then
    sudo sed -i "s/127.0.1.1\s\+$CURRENT_HOSTNAME/127.0.1.1\t{{ $hostname }}/g" /etc/hosts || true
fi

if grep -q '^127.0.1.1' /etc/hosts; then
    sudo sed -i "s/^127.0.1.1.*/127.0.1.1\t{{ $hostname }}/" /etc/hosts
else
    echo "127.0.1.1	{{ $hostname }}" | sudo tee -a /etc/hosts >/dev/null
fi

sudo hostnamectl set-hostname {{ $hostname }}
