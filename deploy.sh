#!/bin/bash

# Interior Project Management System Deployment Script
# Usage: ./deploy.sh [production|staging]

set -e

ENVIRONMENT=${1:-production}
PROJECT_NAME="interior-pms"
BACKUP_DIR="/var/backups/$PROJECT_NAME"
APP_DIR="/var/www/$PROJECT_NAME"
LOG_DIR="/var/log/$PROJECT_NAME"

echo "🚀 Starting deployment for $ENVIRONMENT environment..."

# Create necessary directories
sudo mkdir -p $BACKUP_DIR
sudo mkdir -p $LOG_DIR
sudo chown -R www-data:www-data $LOG_DIR

# Backup existing installation
if [ -d "$APP_DIR" ]; then
    echo "📦 Creating backup..."
    sudo tar -czf "$BACKUP_DIR/backup-$(date +%Y%m%d-%H%M%S).tar.gz" -C "$APP_DIR" .
fi

# Update system packages
echo "📦 Updating system packages..."
sudo apt update && sudo apt upgrade -y

# Install required packages
echo "📦 Installing required packages..."
sudo apt install -y nginx mysql-server php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-zip php8.2-curl php8.2-gd php8.2-intl php8.2-bcmath redis-server

# Install Composer if not exists
if ! command -v composer &> /dev/null; then
    echo "📦 Installing Composer..."
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
fi

# Install Docker if using containerized deployment
if [ "$ENVIRONMENT" = "production" ] && command -v docker &> /dev/null; then
    echo "🐳 Setting up Docker deployment..."
    docker-compose down || true
    docker-compose pull
    docker-compose up -d --build
    
    # Wait for services to be ready
    echo "⏳ Waiting for services to start..."
    sleep 30
    
    # Run database migrations
    docker-compose exec php php database/migrate.php
else
    # Traditional server setup
    echo "🔧 Setting up traditional server deployment..."
    
    # Copy application files
    sudo cp -r . $APP_DIR/
    sudo chown -R www-data:www-data $APP_DIR
    sudo chmod -R 755 $APP_DIR
    sudo chmod -R 777 $APP_DIR/uploads
    sudo chmod -R 777 $APP_DIR/storage/logs
    
    # Install PHP dependencies
    cd $APP_DIR
    composer install --no-dev --optimize-autoloader
    
    # Set up database
    if [ "$ENVIRONMENT" = "production" ]; then
        mysql -u root -p < database/schema.sql
        mysql -u root -p < database/data.sql
    fi
    
    # Configure Nginx
    sudo cp nginx.conf /etc/nginx/sites-available/$PROJECT_NAME
    sudo ln -sf /etc/nginx/sites-available/$PROJECT_NAME /etc/nginx/sites-enabled/
    sudo nginx -t && sudo systemctl reload nginx
    
    # Configure PHP-FPM
    sudo systemctl enable php8.2-fpm
    sudo systemctl start php8.2-fpm
fi

# Set up SSL certificate (using Let's Encrypt)
if [ "$ENVIRONMENT" = "production" ]; then
    echo "🔒 Setting up SSL certificate..."
    sudo apt install -y certbot python3-certbot-nginx
    # sudo certbot --nginx -d your-domain.com -d www.your-domain.com
fi

# Set up cron jobs
echo "⏰ Setting up cron jobs..."
(crontab -l 2>/dev/null; echo "0 9 * * * /usr/bin/php $APP_DIR/crons/deadline_reminders.php") | crontab -
(crontab -l 2>/dev/null; echo "0 2 * * * /usr/bin/php $APP_DIR/crons/cleanup_old_files.php") | crontab -
(crontab -l 2>/dev/null; echo "0 3 * * 0 /usr/bin/php $APP_DIR/crons/weekly_reports.php") | crontab -

# Set up log rotation
sudo tee /etc/logrotate.d/$PROJECT_NAME > /dev/null <<EOF
$LOG_DIR/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload nginx
    endscript
}
EOF

# Performance optimizations
echo "⚡ Applying performance optimizations..."

# PHP optimizations
sudo tee -a /etc/php/8.2/fpm/php.ini > /dev/null <<EOF
; Performance settings
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.validate_timestamps=0
realpath_cache_size=4096k
realpath_cache_ttl=600
EOF

# MySQL optimizations
sudo tee -a /etc/mysql/mysql.conf.d/mysqld.cnf > /dev/null <<EOF
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
query_cache_type = 1
query_cache_size = 64M
tmp_table_size = 64M
max_heap_table_size = 64M
EOF

# Restart services
echo "🔄 Restarting services..."
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
sudo systemctl restart mysql
sudo systemctl restart redis-server

# Run health checks
echo "🏥 Running health checks..."
curl -f http://localhost/health.php || echo "⚠️ Health check failed"

# Set up monitoring
echo "📊 Setting up monitoring..."
sudo apt install -y htop iotop nethogs

# Create admin user if in production
if [ "$ENVIRONMENT" = "production" ]; then
    echo "👤 Creating admin user..."
    php $APP_DIR/scripts/create_admin.php
fi

echo "✅ Deployment completed successfully!"
echo ""
echo "🎉 Interior Project Management System is now deployed!"
echo "📍 Application URL: https://your-domain.com"
echo "📋 Admin Panel: https://your-domain.com/modules/auth/login.php"
echo "📊 Logs: $LOG_DIR"
echo "💾 Backups: $BACKUP_DIR"
echo ""
echo "🔧 Next steps:"
echo "1. Configure your domain name in nginx.conf"
echo "2. Update database credentials in config/production.php"
echo "3. Set up SSL certificate with: sudo certbot --nginx"
echo "4. Configure email settings in config/production.php"
echo "5. Test all functionality"
echo ""
echo "📞 Support: Contact your system administrator"
