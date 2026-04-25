arch=$(uname -m)

if [ "$arch" == "x86_64" ]; then
    executable="hitechcloudpanelagent-linux-amd64"
elif [ "$arch" == "i686" ]; then
    executable="hitechcloudpanelagent-linux-amd"
elif [ "$arch" == "armv7l" ]; then
    executable="hitechcloudpanelagent-linux-arm"
elif [ "$arch" == "aarch64" ]; then
    executable="hitechcloudpanelagent-linux-arm64"
else
    executable="hitechcloudpanelagent-linux-amd64"
fi

wget {{ $downloadUrl }}/$executable

chmod +x ./$executable

sudo mv ./$executable /usr/local/bin/hitechcloudpanel-agent

# create service
export HITECHCLOUDPANELAGENT_SERVICE="
[Unit]
Description=HitechCloudPanel Agent
After=network.target

[Service]
Type=simple
User=root
ExecStart=/usr/local/bin/hitechcloudpanel-agent
Restart=on-failure

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
sudo systemctl start hitechcloudpanel-agent

echo "HitechCloudPanel Agent installed successfully"
