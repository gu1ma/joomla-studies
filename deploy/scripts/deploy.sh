#!/bin/bash

# Joomla Deployment Script for Google Cloud VM
# Usage: ./deploy.sh <environment> <mysql_root_password> <mysql_database> <mysql_user> <mysql_password> <image_tag>

set -e

ENVIRONMENT=$1
MYSQL_ROOT_PASSWORD=$2
MYSQL_DATABASE=$3
MYSQL_USER=$4
MYSQL_PASSWORD=$5
IMAGE_TAG=$6

# Validate parameters
if [ $# -ne 6 ]; then
    echo "Usage: $0 <environment> <mysql_root_password> <mysql_database> <mysql_user> <mysql_password> <image_tag>"
    echo "Example: $0 staging mypassword joomla_db joomla_user userpass abc123"
    exit 1
fi

if [ "$ENVIRONMENT" != "staging" ] && [ "$ENVIRONMENT" != "production" ]; then
    echo "Error: Environment must be 'staging' or 'production'"
    exit 1
fi

echo "=== Joomla Deployment Script ==="
echo "Environment: $ENVIRONMENT"
echo "Database: $MYSQL_DATABASE"
echo "Image Tag: $IMAGE_TAG"
echo "Starting deployment at $(date)"
echo

# Set working directory
DEPLOY_DIR="/opt/joomla"

# Create directory if it doesn't exist
sudo mkdir -p "$DEPLOY_DIR"
sudo chown -R $USER:$USER "$DEPLOY_DIR"

cd "$DEPLOY_DIR"

# Stop existing containers if running
echo "Stopping existing containers..."
docker-compose down --remove-orphans 2>/dev/null || true

# Clean up old images (keep last 3)
echo "Cleaning up old Docker images..."
docker images --format "table {{.Repository}}\t{{.Tag}}\t{{.ID}}" | grep "joomla-app" | tail -n +4 | awk '{print $3}' | xargs -r docker rmi 2>/dev/null || true

# Load the new Docker image
if [ -f "/tmp/deployment/joomla-image.tar" ]; then
    echo "Loading new Joomla Docker image..."
    docker load < /tmp/deployment/joomla-image.tar
    
    # Tag the image appropriately
    LOADED_IMAGE=$(docker images --format "{{.Repository}}:{{.Tag}}" | head -n1)
    docker tag "$LOADED_IMAGE" "joomla-app:$IMAGE_TAG"
else
    echo "Warning: No Docker image found at /tmp/deployment/joomla-image.tar"
    echo "Using existing image or will pull from registry"
fi

# Copy configuration files
echo "Copying configuration files..."
if [ -d "/opt/joomla/configs/mysql-config" ]; then
    echo "Using existing MySQL config"
else
    mkdir -p /opt/joomla/configs/mysql-config
fi

if [ -d "/opt/joomla/configs/nginx-config" ]; then
    echo "Using existing Nginx config"
else
    mkdir -p /opt/joomla/configs/nginx-config
fi

# Copy docker-compose file
if [ -f "/tmp/deployment/docker-compose.yml" ]; then
    cp /tmp/deployment/docker-compose.yml ./
else
    echo "Error: docker-compose.yml not found in deployment package"
    exit 1
fi

# Create environment file
echo "Creating environment configuration..."
cat > .env << EOF
# Database Configuration
MYSQL_ROOT_PASSWORD=$MYSQL_ROOT_PASSWORD
MYSQL_DATABASE=$MYSQL_DATABASE
MYSQL_USER=$MYSQL_USER
MYSQL_PASSWORD=$MYSQL_PASSWORD

# Joomla Configuration
JOOMLA_IMAGE=joomla-app
IMAGE_TAG=$IMAGE_TAG
JOOMLA_SITE_NAME=Joomla Site - $ENVIRONMENT

# Environment
ENVIRONMENT=$ENVIRONMENT
EOF

# Create data directories if they don't exist
echo "Creating data directories..."
mkdir -p data/mysql
mkdir -p data/joomla
mkdir -p logs

# Set proper permissions
echo "Setting permissions..."
sudo chown -R 999:999 data/mysql  # MySQL user in container
sudo chown -R www-data:www-data data/joomla logs 2>/dev/null || sudo chown -R 33:33 data/joomla logs

# Backup existing database if it exists and this is production
if [ "$ENVIRONMENT" = "production" ] && docker ps -a --format '{{.Names}}' | grep -q "joomla-mysql"; then
    echo "Creating backup before deployment..."
    BACKUP_FILE="backups/pre-deploy-$(date +%Y%m%d_%H%M%S).sql"
    mkdir -p backups
    
    # Start MySQL temporarily if not running
    docker start joomla-mysql 2>/dev/null || true
    sleep 10
    
    docker exec joomla-mysql mysqladump -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" > "$BACKUP_FILE" 2>/dev/null || echo "Warning: Could not create backup (database may not exist yet)"
fi

# Start the services
echo "Starting services..."
docker-compose up -d

# Wait for services to be healthy
echo "Waiting for services to start..."
sleep 30

# Check service health
MAX_ATTEMPTS=60
ATTEMPT=0

echo "Checking MySQL health..."
while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    if docker exec joomla-mysql mysqladmin ping -h localhost -u root -p"$MYSQL_ROOT_PASSWORD" --silent 2>/dev/null; then
        echo "MySQL is healthy!"
        break
    fi
    ATTEMPT=$((ATTEMPT + 1))
    echo "Waiting for MySQL... ($ATTEMPT/$MAX_ATTEMPTS)"
    sleep 5
done

if [ $ATTEMPT -eq $MAX_ATTEMPTS ]; then
    echo "Error: MySQL failed to start properly"
    docker logs joomla-mysql --tail 20
    exit 1
fi

echo "Checking Joomla health..."
ATTEMPT=0
while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    if curl -f http://localhost:80/ >/dev/null 2>&1; then
        echo "Joomla is responding!"
        break
    fi
    ATTEMPT=$((ATTEMPT + 1))
    echo "Waiting for Joomla... ($ATTEMPT/$MAX_ATTEMPTS)"
    sleep 5
done

if [ $ATTEMPT -eq $MAX_ATTEMPTS ]; then
    echo "Warning: Joomla may not be fully ready yet"
    echo "Check logs: docker logs joomla-app"
fi

# Show deployment status
echo
echo "=== Deployment Status ==="
docker-compose ps

echo
echo "=== Service URLs ==="
EXTERNAL_IP=$(curl -s ifconfig.me 2>/dev/null || curl -s ipinfo.io/ip 2>/dev/null || echo "localhost")
echo "Joomla: http://$EXTERNAL_IP"
echo "Direct access: http://$EXTERNAL_IP:80"

echo
echo "=== Container Health ==="
for container in joomla-mysql joomla-app; do
    if docker ps --format '{{.Names}}' | grep -q "$container"; then
        health=$(docker inspect --format='{{.State.Health.Status}}' "$container" 2>/dev/null || echo "no health check")
        echo "$container: $health"
    else
        echo "$container: not running"
    fi
done

# Setup backup timer for production
if [ "$ENVIRONMENT" = "production" ]; then
    echo
    echo "Setting up automatic backups..."
    
    if command -v systemctl >/dev/null 2>&1 && [ -d /run/systemd/system ]; then
        sudo systemctl enable joomla-backup.timer 2>/dev/null && echo "Systemd backup timer enabled" || echo "Backup timer setup failed"
        sudo systemctl start joomla-backup.timer 2>/dev/null && echo "Systemd backup timer started" || echo "Backup timer start failed"
    else
        echo "Using cron-based backup (already configured during setup)"
    fi
    
    echo "Automatic daily backups configured"
fi

# Show useful commands
echo
echo "=== Useful Commands ==="
echo "View logs: docker-compose logs -f"
echo "Restart services: docker-compose restart"
echo "Update services: docker-compose pull && docker-compose up -d"
echo "Backup database: docker exec joomla-mysql mysqldump -u root -p'$MYSQL_ROOT_PASSWORD' '$MYSQL_DATABASE' > backup.sql"
echo "Monitor: /opt/joomla/monitor.sh"

# Cleanup deployment files
echo
echo "Cleaning up deployment files..."
rm -rf /tmp/deployment

echo
echo "=== Deployment Complete ==="
echo "Environment: $ENVIRONMENT"
echo "Completed at: $(date)"
echo "Joomla should be available at: http://$EXTERNAL_IP"