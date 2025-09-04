#!/bin/bash

# Joomla Deployment Script
# Usage: ./deploy.sh [staging|production]

set -e

ENVIRONMENT=${1:-staging}
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$(dirname "$SCRIPT_DIR")")"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Logging
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

warn() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
    exit 1
}

# Load environment variables
if [ -f "$PROJECT_ROOT/.env.$ENVIRONMENT" ]; then
    source "$PROJECT_ROOT/.env.$ENVIRONMENT"
    log "Loaded environment configuration for $ENVIRONMENT"
else
    error "Environment file .env.$ENVIRONMENT not found"
fi

# Validate required variables
required_vars=("DEPLOY_HOST" "DEPLOY_USER" "DEPLOY_PATH" "DB_NAME")
for var in "${required_vars[@]}"; do
    if [ -z "${!var}" ]; then
        error "Required variable $var is not set"
    fi
done

log "Starting deployment to $ENVIRONMENT environment"

# Create deployment package
create_package() {
    log "Creating deployment package..."
    
    cd "$PROJECT_ROOT"
    
    # Create temporary directory
    TEMP_DIR=$(mktemp -d)
    PACKAGE_DIR="$TEMP_DIR/joomla-deploy"
    
    # Copy files
    rsync -av \
        --exclude='.git/' \
        --exclude='.github/' \
        --exclude='node_modules/' \
        --exclude='tests/' \
        --exclude='deploy/' \
        --exclude='*.log' \
        --exclude='.env*' \
        --exclude='tmp/*' \
        --exclude='cache/*' \
        --exclude='administrator/cache/*' \
        --exclude='administrator/logs/*' \
        . "$PACKAGE_DIR/"
    
    # Create archive
    cd "$TEMP_DIR"
    tar -czf "joomla-${ENVIRONMENT}-$(date +%Y%m%d-%H%M%S).tar.gz" joomla-deploy/
    
    PACKAGE_PATH="$TEMP_DIR/joomla-${ENVIRONMENT}-$(date +%Y%m%d-%H%M%S).tar.gz"
    log "Package created: $PACKAGE_PATH"
    
    echo "$PACKAGE_PATH"
}

# Deploy to server
deploy_to_server() {
    local package_path=$1
    
    log "Deploying to server: $DEPLOY_HOST"
    
    # Upload package
    scp "$package_path" "$DEPLOY_USER@$DEPLOY_HOST:/tmp/"
    
    PACKAGE_NAME=$(basename "$package_path")
    
    # Execute deployment on remote server
    ssh "$DEPLOY_USER@$DEPLOY_HOST" << EOF
        set -e
        
        echo "Starting remote deployment..."
        
        cd "$DEPLOY_PATH"
        
        # Create backup of current version
        if [ -d "current" ]; then
            BACKUP_DIR="backup-\$(date +%Y%m%d-%H%M%S)"
            cp -r current "\$BACKUP_DIR"
            echo "Backup created: \$BACKUP_DIR"
        fi
        
        # Extract new version
        rm -rf /tmp/joomla-deploy
        cd /tmp
        tar -xzf "$PACKAGE_NAME"
        
        cd "$DEPLOY_PATH"
        
        # Preserve configuration and uploads
        if [ -d "current" ]; then
            cp current/configuration.php /tmp/joomla-deploy/ 2>/dev/null || true
            cp -r current/images/uploads /tmp/joomla-deploy/images/ 2>/dev/null || true
        fi
        
        # Switch to new version
        rm -rf current
        mv /tmp/joomla-deploy current
        
        # Set permissions
        sudo chown -R www-data:www-data current
        sudo chmod -R 755 current
        sudo chmod 644 current/configuration.php
        sudo chmod -R 777 current/tmp
        sudo chmod -R 777 current/cache
        sudo chmod -R 777 current/administrator/cache
        sudo chmod -R 777 current/administrator/logs
        
        # Clear Joomla cache
        rm -rf current/cache/*
        rm -rf current/tmp/*
        rm -rf current/administrator/cache/*
        
        # Restart web server
        sudo systemctl reload apache2 || sudo systemctl reload nginx
        
        echo "Deployment completed successfully"
EOF
    
    log "Deployment completed successfully"
}

# Database backup
backup_database() {
    log "Creating database backup..."
    
    BACKUP_FILE="db-backup-${ENVIRONMENT}-$(date +%Y%m%d-%H%M%S).sql"
    
    ssh "$DEPLOY_USER@$DEPLOY_HOST" << EOF
        mysqldump -h ${DB_HOST:-localhost} -u $DB_USER -p$DB_PASSWORD $DB_NAME > /tmp/$BACKUP_FILE
        echo "Database backup created: /tmp/$BACKUP_FILE"
EOF
    
    # Download backup
    scp "$DEPLOY_USER@$DEPLOY_HOST:/tmp/$BACKUP_FILE" "$PROJECT_ROOT/backups/"
    
    log "Database backup completed: backups/$BACKUP_FILE"
}

# Health check
health_check() {
    log "Performing health check..."
    
    HEALTH_URL="${SITE_URL}/index.php"
    
    if curl -f -s "$HEALTH_URL" > /dev/null; then
        log "Health check passed"
    else
        error "Health check failed - site is not responding"
    fi
}

# Main deployment process
main() {
    log "=== Joomla Deployment Started ==="
    
    # Create backups directory
    mkdir -p "$PROJECT_ROOT/backups"
    
    # Backup database
    if [ "$ENVIRONMENT" = "production" ]; then
        backup_database
    fi
    
    # Create and deploy package
    PACKAGE_PATH=$(create_package)
    deploy_to_server "$PACKAGE_PATH"
    
    # Clean up
    rm -f "$PACKAGE_PATH"
    
    # Health check
    sleep 5
    health_check
    
    log "=== Deployment Completed Successfully ==="
}

# Check if running with correct parameters
if [ "$ENVIRONMENT" != "staging" ] && [ "$ENVIRONMENT" != "production" ]; then
    error "Invalid environment. Use 'staging' or 'production'"
fi

# Run deployment
main