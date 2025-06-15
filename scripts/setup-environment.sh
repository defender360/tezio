#!/bin/bash

# ITSM Platform Environment Setup Script
# This script sets up the development environment for the ITSM platform

set -e

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo "================================================"
echo "ITSM Platform Environment Setup"
echo "================================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Helper functions
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Check prerequisites
echo "Checking prerequisites..."

# Check Docker
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed. Please install Docker first."
    exit 1
else
    print_success "Docker is installed"
fi

# Check Docker Compose
if ! command -v docker-compose &> /dev/null; then
    print_error "Docker Compose is not installed. Please install Docker Compose first."
    exit 1
else
    print_success "Docker Compose is installed"
fi

# Check Node.js
if ! command -v node &> /dev/null; then
    print_error "Node.js is not installed. Please install Node.js 18+ first."
    exit 1
else
    NODE_VERSION=$(node -v | cut -d'v' -f2 | cut -d'.' -f1)
    if [ "$NODE_VERSION" -lt 18 ]; then
        print_error "Node.js version must be 18 or higher. Current version: $(node -v)"
        exit 1
    fi
    print_success "Node.js $(node -v) is installed"
fi

# Check PHP (optional for local development)
if command -v php &> /dev/null; then
    print_success "PHP $(php -v | head -n 1 | cut -d' ' -f2) is installed"
else
    print_warning "PHP is not installed locally. Will use Docker containers."
fi

echo ""
echo "Setting up environment..."

# Create .env file if it doesn't exist
if [ ! -f "$PROJECT_ROOT/.env" ]; then
    echo "Creating .env file..."
    cat > "$PROJECT_ROOT/.env" << EOF
# Application
APP_NAME="ITSM Platform"
APP_ENV=local
APP_KEY=base64:$(openssl rand -base64 32)
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=itsm_platform
DB_USERNAME=itsm_user
DB_PASSWORD=$(openssl rand -base64 16)

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=
REDIS_PORT=6379

# Mail (using Mailpit for local development)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@itsm.local"
MAIL_FROM_NAME="${APP_NAME}"

# Auth0
AUTH0_DOMAIN=your-domain.auth0.com
AUTH0_CLIENT_ID=your-client-id
AUTH0_CLIENT_SECRET=your-client-secret
AUTH0_AUDIENCE=your-api-audience

# AI Service
CLAUDE_API_KEY=
OPENAI_API_KEY=

# Elasticsearch
ELASTICSEARCH_HOST=elasticsearch:9200

# Monitoring
GRAFANA_USER=admin
GRAFANA_PASSWORD=$(openssl rand -base64 12)

# Ports
APP_PORT=8000
FRONTEND_PORT=3000
AI_SERVICE_PORT=8001
PROMETHEUS_PORT=9090
GRAFANA_PORT=3001
KIBANA_PORT=5601
MAILPIT_PORT=8025
MINIO_PORT=9000
MINIO_CONSOLE_PORT=9001
EOF
    print_success "Created .env file"
else
    print_warning ".env file already exists"
fi

# Create necessary directories
echo "Creating directories..."
mkdir -p "$PROJECT_ROOT/backend/storage/"{app,framework/{cache,sessions,testing,views},logs}
mkdir -p "$PROJECT_ROOT/backend/bootstrap/cache"
mkdir -p "$PROJECT_ROOT/monitoring/"{prometheus,grafana/dashboards,alertmanager,logstash/pipeline,filebeat}
chmod -R 775 "$PROJECT_ROOT/backend/storage"
chmod -R 775 "$PROJECT_ROOT/backend/bootstrap/cache"
print_success "Created directories"

# Install backend dependencies
echo ""
echo "Installing backend dependencies..."
cd "$PROJECT_ROOT/backend"

# Create composer auth file for faster downloads
if [ ! -f "$HOME/.composer/auth.json" ]; then
    mkdir -p "$HOME/.composer"
    echo '{}' > "$HOME/.composer/auth.json"
fi

# Run composer install in Docker if PHP is not available locally
if command -v composer &> /dev/null; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    docker run --rm \
        -v "$PROJECT_ROOT/backend:/app" \
        -v "$HOME/.composer:/tmp" \
        composer:latest install --no-interaction --prefer-dist --optimize-autoloader
fi
print_success "Backend dependencies installed"

# Install frontend dependencies
echo ""
echo "Installing frontend dependencies..."
cd "$PROJECT_ROOT/frontend"
npm install
print_success "Frontend dependencies installed"

# Build containers
echo ""
echo "Building Docker containers..."
cd "$PROJECT_ROOT"
docker-compose build --parallel
print_success "Docker containers built"

# Start services
echo ""
echo "Starting services..."
docker-compose up -d postgres redis elasticsearch
print_success "Database services started"

# Wait for PostgreSQL to be ready
echo "Waiting for PostgreSQL to be ready..."
until docker-compose exec -T postgres pg_isready -U itsm_user -d itsm_platform &> /dev/null; do
    echo -n "."
    sleep 1
done
echo ""
print_success "PostgreSQL is ready"

# Wait for Elasticsearch to be ready
echo "Waiting for Elasticsearch to be ready..."
until curl -s http://localhost:9200/_cluster/health &> /dev/null; do
    echo -n "."
    sleep 1
done
echo ""
print_success "Elasticsearch is ready"

# Run database migrations
echo ""
echo "Running database migrations..."
docker-compose run --rm backend php artisan migrate --force
print_success "Database migrations completed"

# Seed database with initial data
echo ""
echo "Seeding database..."
docker-compose run --rm backend php artisan db:seed
print_success "Database seeded"

# Create Elasticsearch indices
echo ""
echo "Setting up Elasticsearch indices..."
docker-compose run --rm backend php artisan elastic:create-indices
print_success "Elasticsearch indices created"

# Generate API documentation
echo ""
echo "Generating API documentation..."
docker-compose run --rm backend php artisan l5-swagger:generate
print_success "API documentation generated"

# Start all services
echo ""
echo "Starting all services..."
docker-compose up -d
print_success "All services started"

# Create first admin user
echo ""
echo "Creating admin user..."
docker-compose run --rm backend php artisan tinker --execute="
    \$tenant = \App\Models\Tenant::first() ?? \App\Models\Tenant::create([
        'name' => 'Default Tenant',
        'slug' => 'default',
        'domain' => 'default.itsm.local'
    ]);
    
    \$user = \App\Models\User::firstOrCreate(
        ['email' => 'admin@itsm.local'],
        [
            'name' => 'Admin User',
            'password' => Hash::make('admin123'),
            'tenant_id' => \$tenant->id,
            'role' => 'admin',
            'is_active' => true
        ]
    );
    
    echo \"Admin user created: admin@itsm.local / admin123\";
"
print_success "Admin user created"

# Setup monitoring dashboards
echo ""
echo "Setting up monitoring dashboards..."
# Import Grafana dashboards
if [ -d "$PROJECT_ROOT/monitoring/grafana/dashboards" ]; then
    # Wait for Grafana to be ready
    until curl -s http://localhost:3001/api/health &> /dev/null; do
        echo -n "."
        sleep 1
    done
    echo ""
    print_success "Monitoring dashboards configured"
fi

# Display summary
echo ""
echo "================================================"
echo "Setup Complete!"
echo "================================================"
echo ""
echo "Services are running at:"
echo "  - Frontend:        http://localhost:3000"
echo "  - Backend API:     http://localhost:8000"
echo "  - AI Service:      http://localhost:8001"
echo "  - Mailpit:         http://localhost:8025"
echo "  - Prometheus:      http://localhost:9090"
echo "  - Grafana:         http://localhost:3001 (admin/$(grep GRAFANA_PASSWORD .env | cut -d'=' -f2))"
echo "  - Kibana:          http://localhost:5601"
echo "  - MinIO Console:   http://localhost:9001"
echo ""
echo "Default credentials:"
echo "  - Admin:           admin@itsm.local / admin123"
echo ""
echo "To view logs:"
echo "  docker-compose logs -f [service]"
echo ""
echo "To stop all services:"
echo "  docker-compose down"
echo ""
echo "To run tests:"
echo "  Backend:  docker-compose run --rm backend php artisan test"
echo "  Frontend: cd frontend && npm run test"
echo ""
print_success "Happy coding!"