export DEBIAN_FRONTEND=noninteractive

sudo systemctl stop unattended-upgrades >/dev/null 2>&1 || true
sudo pkill -f unattended-upgrade >/dev/null 2>&1 || true
sudo pkill -f 'apt|apt-get|dpkg' >/dev/null 2>&1 || true
sudo rm -f /var/lib/dpkg/lock-frontend /var/lib/dpkg/lock /var/cache/apt/archives/lock
sudo dpkg --configure -a
sudo apt-get -f install -y
sudo systemctl start unattended-upgrades >/dev/null 2>&1 || true
