#!/bin/bash

# Script để fix permissions cho Laravel trên AApanel
# Chạy: bash fix-permissions.sh

echo "🔧 Fixing Laravel permissions..."

# Đường dẫn project (thay đổi nếu cần)
PROJECT_PATH="/www/wwwroot/keki.snacksoft.net"

cd $PROJECT_PATH || exit 1

# Kiểm tra user của web server
WEB_USER=$(ps aux | grep -E 'php-fpm|nginx' | grep -v grep | head -1 | awk '{print $1}')
if [ -z "$WEB_USER" ]; then
    WEB_USER="www"
fi

echo "📋 Detected web server user: $WEB_USER"

# Set ownership
echo "👤 Setting ownership..."
chown -R $WEB_USER:$WEB_USER storage bootstrap/cache
chown -R $WEB_USER:$WEB_USER .

# Set permissions
echo "🔐 Setting permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chmod -R 777 storage/framework
chmod -R 777 storage/logs
chmod -R 777 storage/framework/views
chmod -R 777 storage/framework/cache
chmod -R 777 storage/framework/sessions

# Clear cache
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Recreate cache with correct permissions
echo "📦 Recreating caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Done! Permissions fixed."

