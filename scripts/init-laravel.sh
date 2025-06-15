#!/bin/bash
set -e

echo "🚀 Initializing Laravel ITSM Platform..."

# Wait for PostgreSQL to be ready
echo "⏳ Waiting for PostgreSQL..."
/scripts/wait-for-it.sh postgres:5432 -t 60

# Wait for Redis to be ready
echo "⏳ Waiting for Redis..."
/scripts/wait-for-it.sh redis:6379 -t 60

# Change to Laravel directory
cd /var/www/html

# Check if Laravel is already installed
if [ ! -f "artisan" ]; then
    echo "❌ Laravel not found. Please install Laravel first."
    exit 1
fi

# Generate application key if not exists
if [ -z "$APP_KEY" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --ansi
fi

# Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations
echo "📊 Running database migrations..."
php artisan migrate --force

# Run seeders
echo "🌱 Seeding database..."
php artisan db:seed --force

# Cache configurations for production
if [ "$APP_ENV" == "production" ]; then
    echo "🚀 Optimizing for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    php artisan icons:cache
fi

# Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link

# Set permissions
echo "🔒 Setting permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Install Horizon assets
if [ -d "vendor/laravel/horizon" ]; then
    echo "📊 Publishing Horizon assets..."
    php artisan horizon:publish
fi

# Install Telescope assets
if [ -d "vendor/laravel/telescope" ]; then
    echo "🔭 Publishing Telescope assets..."
    php artisan telescope:publish
fi

# Install Pulse assets
if [ -d "vendor/laravel/pulse" ]; then
    echo "📈 Publishing Pulse assets..."
    php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
fi

# Health check
echo "🏥 Running health check..."
php artisan health:check || true

echo "✅ Laravel ITSM Platform initialization completed!"