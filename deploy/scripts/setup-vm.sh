#!/bin/bash

# Google Cloud VM Setup Script for Joomla Deployment
# This script prepares a fresh Ubuntu VM for Docker-based Joomla deployment

set -e

echo "=== Google Cloud VM Setup for Joomla ==="

# Update system packages
echo "Updating system packages..."
sudo apt-get update
sudo apt-get upgrade -y

# Install required packages
echo "Installing required packages..."
sudo apt-get install -y \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    software-properties-common \
    ufw \
    fail2ban \
    htop \
    nano \
    git

# Install Docker
echo "Installing Docker..."
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu \
  $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Install Docker Compose (standalone)
echo "Installing Docker Compose..."
DOCKER_COMPOSE_VERSION=$(curl -s https://api.github.com/repos/docker/compose/releases/latest | grep 'tag_name' | cut -d\" -f4)
sudo curl -L "https://github.com/docker/compose/releases/download/${DOCKER_COMPOSE_VERSION}/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Add current user to docker group
echo "Adding user to docker group..."
sudo usermod -aG docker $USER

# Start and enable Docker
echo "Starting Docker service..."
sudo systemctl start docker
sudo systemctl enable docker

# Configure firewall
echo "Configuring firewall..."
sudo ufw --force enable
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Configure fail2ban
echo "Configuring fail2ban..."
sudo systemctl start fail2ban
sudo systemctl enable fail2ban

# Create application directories
echo "Creating application directories..."
sudo mkdir -p /opt/joomla
sudo mkdir -p /opt/joomla/backups
sudo mkdir -p /opt/joomla/logs
sudo mkdir -p /opt/joomla/configs
sudo chown -R $USER:$USER /opt/joomla

# Create MySQL configuration directory
mkdir -p /opt/joomla/configs/mysql-config

# Create custom MySQL configuration
cat > /opt/joomla/configs/mysql-config/my.cnf << 'EOF'
[mysqld]
# Basic settings
user = mysql
default-storage-engine = InnoDB
socket = /var/lib/mysql/mysql.sock
pid-file = /var/lib/mysql/mysql.pid

# Safety
max-allowed-packet = 256M
max-connections = 200
wait_timeout = 600
interactive_timeout = 600

# Performance
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 1
innodb_lock_wait_timeout = 120

# Character set
collation-server = utf8mb4_unicode_ci
character-set-server = utf8mb4

# Query cache
query_cache_type = 1
query_cache_size = 32M

# Logging
slow_query_log = 1
long_query_time = 2
slow_query_log_file = /var/lib/mysql/mysql-slow.log

[mysql]
default-character-set = utf8mb4

[client]
default-character-set = utf8mb4
EOF

# Create nginx configuration directory
mkdir -p /opt/joomla/configs/nginx-config

# Create nginx main configuration
cat > /opt/joomla/configs/nginx-config/nginx.conf << 'EOF'
user nginx;
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

events {
    worker_connections 1024;
    use epoll;
    multi_accept on;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;
    
    # Logging
    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';
    
    access_log /var/log/nginx/access.log main;
    
    # Basic settings
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    client_max_body_size 64M;
    
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    
    include /etc/nginx/conf.d/*.conf;
}
EOF

# Create nginx site configuration
cat > /opt/joomla/configs/nginx-config/default.conf << 'EOF'
upstream joomla_backend {
    server joomla:80;
    keepalive 32;
}

server {
    listen 80;
    server_name _;
    
    # Security
    server_tokens off;
    
    # Logs
    access_log /var/log/nginx/joomla.access.log;
    error_log /var/log/nginx/joomla.error.log;
    
    # File upload limit
    client_max_body_size 64M;
    
    # Static files served directly by nginx
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|eot|svg)$ {
        proxy_pass http://joomla_backend;
        proxy_cache_valid 200 1h;
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
    
    # Joomla specific security
    location ~* \.(txt|xml|md)$ {
        deny all;
    }
    
    location ~ /\.ht {
        deny all;
    }
    
    location ~ ^/(configuration\.php|.*\.log)$ {
        deny all;
    }
    
    # Proxy all other requests to Joomla
    location / {
        proxy_pass http://joomla_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
    
    # Health check
    location /nginx-health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }
}
EOF

# Create backup script
cat > /opt/joomla/backup.sh << 'EOF'
#!/bin/bash

# Joomla Backup Script
BACKUP_DIR="/opt/joomla/backups"
DATE=$(date +%Y%m%d_%H%M%S)

echo "Starting backup at $(date)"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Backup database
echo "Backing up database..."
docker exec joomla-mysql mysqldump -u root -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE > "$BACKUP_DIR/database_$DATE.sql"

# Backup Joomla files
echo "Backing up Joomla files..."
docker exec joomla-app tar -czf - /var/www/html/images /var/www/html/configuration.php > "$BACKUP_DIR/files_$DATE.tar.gz"

# Remove backups older than 7 days
echo "Cleaning old backups..."
find "$BACKUP_DIR" -name "*.sql" -mtime +7 -delete
find "$BACKUP_DIR" -name "*.tar.gz" -mtime +7 -delete

echo "Backup completed at $(date)"
EOF

chmod +x /opt/joomla/backup.sh

# Create systemd service for automatic backups
sudo cat > /etc/systemd/system/joomla-backup.service << 'EOF'
[Unit]
Description=Joomla Backup Service
After=docker.service

[Service]
Type=oneshot
User=root
Environment=MYSQL_ROOT_PASSWORD=%i
ExecStart=/opt/joomla/backup.sh
EOF

sudo cat > /etc/systemd/system/joomla-backup.timer << 'EOF'
[Unit]
Description=Run Joomla backup daily
Requires=joomla-backup.service

[Timer]
OnCalendar=daily
Persistent=true

[Install]
WantedBy=timers.target
EOF

sudo systemctl daemon-reload

# Set up log rotation
sudo cat > /etc/logrotate.d/joomla << 'EOF'
/opt/joomla/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 644 www-data www-data
    postrotate
        docker restart joomla-nginx 2>/dev/null || true
    endscript
}
EOF

# Create monitoring script
cat > /opt/joomla/monitor.sh << 'EOF'
#!/bin/bash

# Simple monitoring script for Joomla deployment
echo "=== Joomla Deployment Status ==="
echo "Date: $(date)"
echo

echo "=== Docker Containers ==="
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo

echo "=== Container Health ==="
for container in joomla-mysql joomla-app joomla-nginx; do
    if docker ps | grep -q "$container"; then
        health=$(docker inspect --format='{{.State.Health.Status}}' "$container" 2>/dev/null || echo "no health check")
        echo "$container: $health"
    else
        echo "$container: not running"
    fi
done
echo

echo "=== Disk Usage ==="
df -h /opt/joomla
echo

echo "=== Memory Usage ==="
free -h
echo

echo "=== Service URLs ==="
echo "Joomla: http://$(curl -s ifconfig.me || echo 'localhost')"
echo "Direct access: http://$(curl -s ifconfig.me || echo 'localhost'):80"
EOF

chmod +x /opt/joomla/monitor.sh

# Final system optimization
echo "Applying system optimizations..."

# Increase file limits
echo "* soft nofile 65536" | sudo tee -a /etc/security/limits.conf
echo "* hard nofile 65536" | sudo tee -a /etc/security/limits.conf

# Optimize TCP settings
cat >> /etc/sysctl.conf << 'EOF'

# Network optimizations
net.core.rmem_max = 16777216
net.core.wmem_max = 16777216
net.ipv4.tcp_rmem = 4096 12582912 16777216
net.ipv4.tcp_wmem = 4096 12582912 16777216
net.core.netdev_max_backlog = 5000
EOF

sudo sysctl -p

echo "=== VM Setup Complete ==="
echo "Please log out and log back in for group changes to take effect"
echo "Or run: newgrp docker"
echo ""
echo "To deploy Joomla, run the deploy.sh script with appropriate parameters"
echo "To monitor the deployment, run: /opt/joomla/monitor.sh"