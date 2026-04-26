set -euo pipefail

arch=$(uname -m)

if [ "$arch" == "x86_64" ]; then
    asset="hitechcloudpanel-linux-amd64"
elif [ "$arch" == "i686" ] || [ "$arch" == "i386" ]; then
    asset="hitechcloudpanel-linux-386"
elif [ "$arch" == "armv7l" ]; then
    asset="hitechcloudpanel-linux-arm"
elif [ "$arch" == "aarch64" ]; then
    asset="hitechcloudpanel-linux-arm64"
else
    asset="hitechcloudpanel-linux-amd64"
fi

wget -O ./${asset}.tar.gz {{ $downloadUrl }}/${asset}.tar.gz

tar -xzf ./${asset}.tar.gz

chmod +x ./$asset

sudo mv ./$asset /usr/local/bin/hitechcloudpanel-agent
sudo mkdir -p /var/log/hitechcloudpanel-agent

export HITECHCLOUDPANELAGENT_SERVICE="
[Unit]
Description=HitechCloudPanel Agent
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=root
ExecStart=/usr/local/bin/hitechcloudpanel-agent
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
"
echo "${HITECHCLOUDPANELAGENT_SERVICE}" | sudo tee /etc/systemd/system/hitechcloudpanel-agent.service

sudo mkdir -p /etc/hitechcloudpanel-agent

export HITECHCLOUDPANELAGENT_CONFIG="
{
    \"url\": \"{{ $configUrl }}\",
    \"secret\": \"{{ $configSecret }}\"
}
"

echo "${HITECHCLOUDPANELAGENT_CONFIG}" | sudo tee /etc/hitechcloudpanel-agent/config.json

sudo systemctl daemon-reload
sudo systemctl enable hitechcloudpanel-agent
sudo systemctl restart hitechcloudpanel-agent

echo "HitechCloudPanel Agent installed successfully"