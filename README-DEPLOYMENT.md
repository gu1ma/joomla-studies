# Joomla CI/CD Setup Guide

This guide explains how to set up and use the CI/CD pipeline for your Joomla project.

## 🚀 Features

- **Continuous Integration (CI)**: Automated testing, code quality checks, and security scans
- **Continuous Deployment (CD)**: Automated deployment to staging and production environments
- **Database Backups**: Automated daily backups with cloud storage
- **Docker Support**: Containerized development and deployment
- **Zero-downtime Deployments**: Blue-green deployment strategy for production

## 📁 Structure

```
.github/workflows/
├── ci.yml          # CI pipeline (tests, validation, security)
├── cd.yml          # CD pipeline (deployment)
└── backup.yml      # Database backup automation

deploy/
├── docker/         # Docker configuration
│   ├── Dockerfile
│   ├── docker-compose.yml
│   └── apache/
└── scripts/
    └── deploy.sh   # Manual deployment script

.env.example        # Environment variables template
```

## ⚙️ Setup Instructions

### 1. Repository Secrets

Add these secrets in your GitHub repository settings (Settings > Secrets and variables > Actions):

#### SSH Configuration
- `SSH_PRIVATE_KEY`: Private key for server access
- `STAGING_HOST`: Staging server hostname
- `STAGING_USER`: SSH username for staging
- `STAGING_PATH`: Path to staging deployment directory
- `PROD_HOST`: Production server hostname  
- `PROD_USER`: SSH username for production
- `PROD_PATH`: Path to production deployment directory

#### Database Configuration
- `DB_HOST`: Database host
- `DB_NAME`: Database name
- `DB_USER`: Database username
- `DB_PASSWORD`: Database password

#### Backup Configuration (Optional)
- `AWS_ACCESS_KEY_ID`: AWS access key for S3 backups
- `AWS_SECRET_ACCESS_KEY`: AWS secret key
- `S3_BACKUP_BUCKET`: S3 bucket name for backups

### 2. Environment Configuration

1. Copy `.env.example` to `.env.staging` and `.env.production`
2. Update values for each environment
3. **Never commit environment files to version control**

### 3. Server Preparation

#### Staging/Production Server Setup:

```bash
# Create deployment directory
sudo mkdir -p /var/www/joomla
sudo chown $USER:www-data /var/www/joomla

# Install required software
sudo apt update
sudo apt install -y apache2 php8.2 php8.2-mysql php8.2-gd php8.2-xml php8.2-mbstring php8.2-zip mysql-client

# Configure Apache virtual host
# See deploy/docker/apache/000-default.conf for configuration example
```

## 🔄 Workflows

### CI Pipeline (`ci.yml`)

**Triggers**: Push/PR to `main`, `master`, `develop`

**Jobs**:
- **PHP Tests**: Multi-version PHP testing (8.1, 8.2, 8.3)
- **Security Scan**: Vulnerability scanning with Composer audit
- **Code Quality**: PHPStan static analysis
- **Joomla Validation**: Joomla-specific structure and compatibility checks
- **Frontend Build**: Asset compilation (if applicable)

### CD Pipeline (`cd.yml`)

**Triggers**: 
- Push to `main`/`master` (production)
- Push to `develop` (staging)
- Manual workflow dispatch
- Version tags

**Jobs**:
- **Build**: Create deployment artifacts
- **Deploy Staging**: Deploy to staging environment
- **Deploy Production**: Zero-downtime deployment to production
- **Notify**: Deployment status notifications

### Backup Pipeline (`backup.yml`)

**Triggers**:
- Daily schedule (2 AM UTC)
- Manual workflow dispatch

**Features**:
- MySQL database dump
- Compression and cloud storage
- Retention policy (30 days)
- Failure notifications

## 🐳 Docker Development

### Quick Start

```bash
# Start development environment
cd deploy/docker
docker-compose up -d

# Access services:
# - Joomla: http://localhost:8080
# - phpMyAdmin: http://localhost:8081
# - MySQL: localhost:3306
```

### Production Docker Deployment

```bash
# Build production image
docker build -f deploy/docker/Dockerfile -t joomla-app:latest .

# Run with docker-compose
docker-compose -f deploy/docker/docker-compose.yml up -d
```

## 🚀 Manual Deployment

Use the deployment script for manual deployments:

```bash
# Deploy to staging
./deploy/scripts/deploy.sh staging

# Deploy to production
./deploy/scripts/deploy.sh production
```

## 📊 Monitoring and Maintenance

### Health Checks

The CI/CD pipeline includes automated health checks:
- HTTP response validation
- Database connectivity
- File permissions verification

### Rollback Procedure

Production deployments use symlink switching for instant rollbacks:

```bash
# On production server
cd /var/www/joomla
ls -la releases/  # List available releases
ln -nfs releases/PREVIOUS_RELEASE current  # Rollback
sudo systemctl reload apache2
```

### Database Migrations

Add database migration commands to your deployment scripts:

```bash
# In cd.yml or deploy.sh
cd current
php cli/joomla.php database:migrate
```

## 🔒 Security Best Practices

- Environment files are never committed
- Secrets are managed through GitHub Secrets
- SSH keys have restricted permissions
- Database backups are encrypted
- Security headers configured in Apache
- Regular dependency vulnerability scanning

## 🎯 Branch Strategy

- `main`/`master`: Production-ready code
- `develop`: Integration branch for staging
- `feature/*`: Feature development branches
- `hotfix/*`: Emergency production fixes

## 📝 Customization

### Adding New Environments

1. Create `.env.newenv` configuration
2. Add secrets to GitHub repository
3. Update workflow conditions in `cd.yml`
4. Add deployment job for new environment

### Custom Notifications

Modify the `notify` job in `cd.yml` to add:
- Slack notifications
- Discord webhooks  
- Email alerts
- Custom integrations

### Additional Tests

Extend `ci.yml` with:
- Browser testing (Selenium)
- Performance testing
- Accessibility testing
- Custom Joomla extension tests

## 🆘 Troubleshooting

### Common Issues

1. **SSH Permission Denied**
   - Verify SSH key is added to server
   - Check file permissions (600 for private key)
   - Ensure user has deployment permissions

2. **Database Connection Errors**
   - Verify database credentials in secrets
   - Check network connectivity
   - Confirm database exists

3. **File Permission Issues**
   - Ensure web server user owns files
   - Check directory permissions (755/777)
   - Verify SELinux/AppArmor policies

4. **Deployment Timeouts**
   - Increase timeout values in workflows
   - Optimize deployment package size
   - Check server resources

### Getting Help

- Check GitHub Actions logs for detailed error messages
- Review server logs: `/var/log/apache2/` or `/var/log/nginx/`
- Verify Joomla logs: `administrator/logs/`
- Test database connectivity manually

## 📈 Next Steps

- Set up monitoring (New Relic, DataDog)
- Implement automated testing for custom extensions
- Add performance benchmarking
- Configure CDN integration
- Set up log aggregation (ELK stack)