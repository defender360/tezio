#!/bin/bash
set -e

echo "🚀 Installing Laravel ITSM Platform..."

# Colors
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
RED='\033[0;31m'
NC='\033[0m'

# Check if we're inside the container
if [ ! -f /.dockerenv ]; then
    echo -e "${RED}This script should be run inside the Docker container!${NC}"
    echo "Use: docker-compose exec backend /scripts/install-laravel.sh"
    exit 1
fi

cd /var/www/html

# Check if Laravel is already installed
if [ -f "vendor/autoload.php" ] && [ -f ".env" ]; then
    echo -e "${YELLOW}Laravel appears to be already installed.${NC}"
    echo "If you want to reinstall, please remove the vendor directory first."
    exit 0
fi

echo -e "${GREEN}Step 1/5:${NC} Installing Laravel dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

# Check if .env exists, if not copy from .env.example
if [ ! -f ".env" ]; then
    echo -e "${GREEN}Step 2/5:${NC} Creating .env file..."
    cp .env.example .env
else
    echo -e "${YELLOW}Step 2/5:${NC} .env file already exists, skipping..."
fi

# Generate application key
echo -e "${GREEN}Step 3/5:${NC} Generating application key..."
php artisan key:generate

# Clear and cache configurations
echo -e "${GREEN}Step 4/5:${NC} Optimizing configuration..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Wait for database to be ready
echo -e "${GREEN}Step 5/5:${NC} Setting up database..."
echo "Waiting for PostgreSQL to be ready..."
while ! pg_isready -h postgres -p 5432 -U itsm_user > /dev/null 2>&1; do
    echo -n "."
    sleep 1
done
echo ""

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Run seeders
echo "Seeding database..."
php artisan db:seed --force

# Create storage link
echo "Creating storage link..."
php artisan storage:link || true

# Set permissions
echo "Setting permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Install additional packages if horizon/telescope are configured
if grep -q "laravel/horizon" composer.json; then
    echo "Publishing Horizon assets..."
    php artisan horizon:publish
fi

if grep -q "laravel/telescope" composer.json; then
    echo "Publishing Telescope assets..."
    php artisan telescope:publish
    php artisan telescope:install
fi

if grep -q "laravel/pulse" composer.json; then
    echo "Publishing Pulse assets..."
    php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
fi

# Final optimization for development
if [ "$APP_ENV" != "production" ]; then
    echo "Development environment detected, skipping production optimizations..."
else
    echo "Optimizing for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

echo -e "${GREEN}✅ Laravel ITSM Platform installation completed!${NC}"
echo ""
echo "Default credentials:"
echo "  Super Admin: superadmin@defender360.com / SuperAdmin@2024!"
echo "  Demo Admin: admin@demo.defender360.com / DemoAdmin@2024!"
echo "  Other users: Password@2024!"
echo ""
echo "Access the application at: http://localhost:8000"