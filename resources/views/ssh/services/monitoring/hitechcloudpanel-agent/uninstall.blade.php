sudo systemctl stop hitechcloudpanel-agent || true

sudo systemctl disable hitechcloudpanel-agent || true

sudo rm -f /usr/local/bin/hitechcloudpanel-agent

sudo rm -f /etc/systemd/system/hitechcloudpanel-agent.service

sudo rm -rf /etc/hitechcloudpanel-agent

sudo systemctl daemon-reload

echo "HitechCloudPanel Agent uninstalled successfully"