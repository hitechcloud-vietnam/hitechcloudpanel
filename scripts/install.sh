#!/bin/bash

set -euo pipefail

echo "
 __      ___ _        _____             _
 \ \    / (_) |      |  __ \           | |
  \ \  / / _| |_ ___ | |  | | ___ _ __ | | ___  _   _
   \ \/ / | | __/ _ \| |  | |/ _ \ '_ \| |/ _ \| | | |
    \  /  | | || (_) | |__| |  __/ |_) | | (_) | |_| |
     \/   |_|\__\___/|_____/ \___| .__/|_|\___/ \__, |
                                 | |             __/ |
                                 |_|            |___/
"

export HITECHCLOUDPANEL_VERSION="main"
export DEBIAN_FRONTEND=noninteractive
export NEEDRESTART_MODE=a

systemctl_safe() {
  local action="$1"
  local unit="$2"

  if command -v systemctl >/dev/null 2>&1; then
    systemctl "$action" "$unit"
  else
    service "$unit" "$action"
  fi
}

normalize_app_url() {
  local url="$1"

  if [[ "$url" =~ ^https?:// ]]; then
    echo "$url"
  else
    echo "http://$url"
  fi
}

if [[ -z "${V_PASSWORD:-}" ]]; then
  export V_PASSWORD=$(openssl rand -base64 12)
fi

if [[ -z "${HITECHCLOUDPANEL_APP_URL:-}" ]]; then
  export DEFAULT_HITECHCLOUDPANEL_APP_URL=http://$(curl -s https://free.freeipapi.com -4)
  read -p "Enter the APP_URL [$DEFAULT_HITECHCLOUDPANEL_APP_URL]: " HITECHCLOUDPANEL_APP_URL
  export HITECHCLOUDPANEL_APP_URL=${HITECHCLOUDPANEL_APP_URL:-$DEFAULT_HITECHCLOUDPANEL_APP_URL}
  export HITECHCLOUDPANEL_APP_URL=$(normalize_app_url "$HITECHCLOUDPANEL_APP_URL")
  echo "APP_URL is set to: $HITECHCLOUDPANEL_APP_URL\n"
fi

if [[ -n "${HITECHCLOUDPANEL_APP_URL:-}" ]]; then
  export HITECHCLOUDPANEL_APP_URL=$(normalize_app_url "$HITECHCLOUDPANEL_APP_URL")
fi

if [[ -z "${V_ADMIN_EMAIL:-}" ]]; then
  read -p "Enter admin's email address: " V_ADMIN_EMAIL
fi

if [[ -z "${V_ADMIN_EMAIL:-}" ]]; then
  echo "Error: V_ADMIN_EMAIL environment variable is not set."
  exit 1
fi

if [[ -z "${V_ADMIN_PASSWORD:-}" ]]; then
  read -p "Enter a password for the admin user: " V_ADMIN_PASSWORD
fi

if [[ -z "${V_ADMIN_PASSWORD:-}" ]]; then
  echo "Error: V_ADMIN_PASSWORD environment variable is not set."
  exit 1
fi

apt remove needrestart -y

if ! id -u hitechcloudpanel >/dev/null 2>&1; then
  useradd -m -s /bin/bash -p "$(openssl passwd -1 "${V_PASSWORD}")" hitechcloudpanel
else
  usermod -p "$(openssl passwd -1 "${V_PASSWORD}")" hitechcloudpanel
fi
usermod -aG sudo hitechcloudpanel
install -d -m 0750 /etc/sudoers.d
echo "hitechcloudpanel ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/hitechcloudpanel
chmod 0440 /etc/sudoers.d/hitechcloudpanel
mkdir -p /home/hitechcloudpanel/.ssh
chown -R hitechcloudpanel:hitechcloudpanel /home/hitechcloudpanel
chsh -s /bin/bash "hitechcloudpanel"
if [[ ! -f /home/hitechcloudpanel/.ssh/id_rsa ]]; then
  su - "hitechcloudpanel" -c "ssh-keygen -t rsa -N '' -f ~/.ssh/id_rsa"
fi

# upgrade
apt clean
apt update
apt upgrade -y
apt autoremove -y

# requirements
apt install -y software-properties-common curl zip unzip git gcc

# certbot
apt install certbot python3-certbot-nginx -y

# nginx
export V_NGINX_CONFIG="
    user hitechcloudpanel;
    worker_processes auto;
    pid /run/nginx.pid;
    include /etc/nginx/modules-enabled/*.conf;
    events {
        worker_connections 768;
    }
    http {
        sendfile on;
        tcp_nopush on;
        tcp_nodelay on;
        keepalive_timeout 65;
        types_hash_max_size 2048;
        include /etc/nginx/mime.types;
        default_type application/octet-stream;
        ssl_protocols TLSv1 TLSv1.1 TLSv1.2; # Dropping SSLv3, ref: POODLE
        ssl_prefer_server_ciphers on;
        access_log /var/log/nginx/access.log;
        error_log /var/log/nginx/error.log;
        gzip on;
        include /etc/nginx/conf.d/*.conf;
        include /etc/nginx/sites-enabled/*;
    }
"
apt install nginx -y
if ! echo "${V_NGINX_CONFIG}" | tee /etc/nginx/nginx.conf; then
  echo "Can't configure nginx!" && exit 1
fi
systemctl_safe enable nginx
systemctl_safe start nginx

# nodejs
export V_NODE_VERSION="20.x"
curl -fsSL https://deb.nodesource.com/setup_${V_NODE_VERSION} | sudo -E bash -
apt install -y nodejs

# php
export V_PHP_VERSION="8.4"
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php${V_PHP_VERSION} php${V_PHP_VERSION}-fpm php${V_PHP_VERSION}-mbstring php${V_PHP_VERSION}-mcrypt php${V_PHP_VERSION}-gd php${V_PHP_VERSION}-xml php${V_PHP_VERSION}-curl php${V_PHP_VERSION}-gettext php${V_PHP_VERSION}-zip php${V_PHP_VERSION}-bcmath php${V_PHP_VERSION}-soap php${V_PHP_VERSION}-redis php${V_PHP_VERSION}-sqlite3 php${V_PHP_VERSION}-intl
if ! sed -i "s/www-data/hitechcloudpanel/g" /etc/php/${V_PHP_VERSION}/fpm/pool.d/www.conf; then
  echo 'Error installing PHP' && exit 1
fi
systemctl_safe enable php${V_PHP_VERSION}-fpm
systemctl_safe start php${V_PHP_VERSION}-fpm
apt install -y php${V_PHP_VERSION}-ssh2
systemctl_safe restart php${V_PHP_VERSION}-fpm
sed -i "s/memory_limit = .*/memory_limit = 1G/" /etc/php/${V_PHP_VERSION}/fpm/php.ini
sed -i "s/upload_max_filesize = .*/upload_max_filesize = 1G/" /etc/php/${V_PHP_VERSION}/fpm/php.ini
sed -i "s/post_max_size = .*/post_max_size = 1G/" /etc/php/${V_PHP_VERSION}/fpm/php.ini
systemctl_safe restart php${V_PHP_VERSION}-fpm

# composer
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# redis
apt install redis-server -y
systemctl_safe enable redis-server
systemctl_safe start redis-server

# setup website
export COMPOSER_ALLOW_SUPERUSER=1
export V_REPO="https://github.com/hitechcloud-vietnam/hitechcloudpanel.git"
export V_VHOST_CONFIG="
server {
    listen 80;
    listen [::]:80;
    server_name _;
    root /home/hitechcloudpanel/hitechcloudpanel/public;

    add_header X-Frame-Options \"SAMEORIGIN\";
    add_header X-Content-Type-Options \"nosniff\";

    client_max_body_size 100M;

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${V_PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
"
rm -rf /home/hitechcloudpanel/hitechcloudpanel
mkdir -p /home/hitechcloudpanel/hitechcloudpanel
chown -R hitechcloudpanel:hitechcloudpanel /home/hitechcloudpanel/hitechcloudpanel
chmod -R 755 /home/hitechcloudpanel/hitechcloudpanel
rm -f /etc/nginx/sites-available/default
rm -f /etc/nginx/sites-enabled/default
echo "${V_VHOST_CONFIG}" | tee /etc/nginx/sites-available/hitechcloudpanel
ln -sfn /etc/nginx/sites-available/hitechcloudpanel /etc/nginx/sites-enabled/hitechcloudpanel
systemctl_safe restart nginx
rm -rf /home/hitechcloudpanel/hitechcloudpanel
git config --global core.fileMode false
git clone -b ${HITECHCLOUDPANEL_VERSION} ${V_REPO} /home/hitechcloudpanel/hitechcloudpanel
find /home/hitechcloudpanel/hitechcloudpanel -type d -exec chmod 755 {} \;
find /home/hitechcloudpanel/hitechcloudpanel -type f -exec chmod 644 {} \;
chown -R hitechcloudpanel:hitechcloudpanel /home/hitechcloudpanel/hitechcloudpanel
cd /home/hitechcloudpanel/hitechcloudpanel && git config core.fileMode false
cd /home/hitechcloudpanel/hitechcloudpanel
LATEST_TAG=$(git tag -l --merged ${HITECHCLOUDPANEL_VERSION} --sort=-v:refname | head -n 1)
if [[ -n "${LATEST_TAG}" ]]; then
  git checkout "${LATEST_TAG}"
fi
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
if [[ -f .env.prod ]]; then
  cp .env.prod .env
else
  cp .env.example .env
fi
sed -i "s|^APP_URL=.*|APP_URL=${HITECHCLOUDPANEL_APP_URL}|" .env
mkdir -p /home/hitechcloudpanel/hitechcloudpanel/storage
touch /home/hitechcloudpanel/hitechcloudpanel/storage/database.sqlite
chown -R hitechcloudpanel:hitechcloudpanel /home/hitechcloudpanel/hitechcloudpanel/storage
php artisan key:generate --force
php artisan storage:link || true
php artisan migrate --force
php artisan user:create HiTechCloudPanel ${V_ADMIN_EMAIL} ${V_ADMIN_PASSWORD}
if [[ ! -f /home/hitechcloudpanel/hitechcloudpanel/storage/ssh-private.pem || ! -f /home/hitechcloudpanel/hitechcloudpanel/storage/ssh-public.key ]]; then
  php artisan ssh-key:generate
fi
chown -R hitechcloudpanel:hitechcloudpanel /home/hitechcloudpanel/hitechcloudpanel/storage/ssh-private.pem
chown -R hitechcloudpanel:hitechcloudpanel /home/hitechcloudpanel/hitechcloudpanel/storage/ssh-public.key

# optimize
php artisan optimize

# cleanup
chown -R hitechcloudpanel:hitechcloudpanel /home/hitechcloudpanel

# setup supervisor
export V_WORKER_CONFIG="
[program:worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/hitechcloudpanel/hitechcloudpanel/artisan horizon
autostart=1
autorestart=1
user=hitechcloudpanel
redirect_stderr=true
stdout_logfile=/home/hitechcloudpanel/.logs/workers/worker.log
stopwaitsecs=3600
"
apt-get install supervisor -y
systemctl_safe enable supervisor
systemctl_safe start supervisor
mkdir -p /home/hitechcloudpanel/.logs
mkdir -p /home/hitechcloudpanel/.logs/workers
touch /home/hitechcloudpanel/.logs/workers/worker.log
echo "${V_WORKER_CONFIG}" | tee /etc/supervisor/conf.d/worker.conf
supervisorctl reread
supervisorctl update

# start worker
supervisorctl restart worker:* || supervisorctl start worker:*

# setup cronjobs
echo "* * * * * cd /home/hitechcloudpanel/hitechcloudpanel && php artisan schedule:run >> /dev/null 2>&1" | sudo -u hitechcloudpanel crontab -

# print info
echo "🎉 Congratulations!"
echo "✅ You can access HiTechCloudPanel at: ${HITECHCLOUDPANEL_APP_URL}"
echo "✅ SSH User: hitechcloudpanel"
echo "✅ SSH Password: ${V_PASSWORD}"
echo "✅ Admin Email: ${V_ADMIN_EMAIL}"
echo "✅ Admin Password: ${V_ADMIN_PASSWORD}"
