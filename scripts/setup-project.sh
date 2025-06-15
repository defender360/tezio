#!/bin/bash

# ITSM Platform - Project Setup Script
# Este script cria a estrutura inicial do projeto

set -e

echo "🚀 Setting up ITSM Platform project structure..."

# Create main directories
mkdir -p {backend,frontend,ai-service,docs,assets,docker,scripts,tests}

# Backend structure (Laravel)
mkdir -p backend/{app,bootstrap,config,database,public,resources,routes,storage,tests}
mkdir -p backend/app/{Console,Core,Domains,Exceptions,Http,Models,Providers}
mkdir -p backend/app/Domains/{Incident,ServiceRequest,User,Tenant}
mkdir -p backend/app/Core/{Auth,Tenant,Traits,Services}
mkdir -p backend/database/{factories,migrations,seeders}
mkdir -p backend/tests/{Feature,Unit}
mkdir -p backend/storage/{app,framework,logs}
mkdir -p backend/storage/framework/{cache,sessions,testing,views}

# Frontend structure (Vue.js)
mkdir -p frontend/{public,src,tests}
mkdir -p frontend/src/{assets,components,composables,layouts,modules,plugins,router,services,stores,styles,utils,views}
mkdir -p frontend/src/modules/{incident-management,service-catalog,dashboard,knowledge-base}
mkdir -p frontend/src/components/{common,forms,charts,tables}
mkdir -p frontend/tests/{unit,e2e}

# AI Service structure (Python/FastAPI)
mkdir -p ai-service/{app,tests,scripts,models}
mkdir -p ai-service/app/{api,core,ml,services,integrations,models,schemas}
mkdir -p ai-service/app/api/{endpoints,middleware}
mkdir -p ai-service/app/ml/{models,pipelines,utils}
mkdir -p ai-service/app/integrations/{claude,openai}
mkdir -p ai-service/tests/{unit,integration}

# Documentation structure
mkdir -p docs/{setup,architecture,api,guides,claude}
mkdir -p docs/claude/prompts

# Assets structure
mkdir -p assets/{diagrams,screenshots,logos,mockups}

# Docker structure
mkdir -p docker/{nginx,postgres,redis}

# Create base configuration files
touch .env.example
touch .gitignore
touch README.md
touch CONTRIBUTING.md
touch docker-compose.yml
touch Makefile

# Backend files
touch backend/.env.example
touch backend/.gitignore
touch backend/composer.json
touch backend/artisan
touch backend/phpunit.xml
touch backend/Dockerfile.dev
touch backend/Dockerfile

# Frontend files
touch frontend/.env.example
touch frontend/.gitignore
touch frontend/package.json
touch frontend/tsconfig.json
touch frontend/vite.config.ts
touch frontend/tailwind.config.js
touch frontend/Dockerfile.dev
touch frontend/Dockerfile

# AI Service files
touch ai-service/.env.example
touch ai-service/.gitignore
touch ai-service/requirements.txt
touch ai-service/requirements-dev.txt
touch ai-service/pyproject.toml
touch ai-service/Dockerfile.dev
touch ai-service/Dockerfile
touch ai-service/alembic.ini

# Create .gitignore files
cat > .gitignore << 'EOF'
# IDE
.idea/
.vscode/
*.swp
*.swo
*~
.DS_Store

# Environment
.env
.env.local
.env.*.local

# Logs
*.log
logs/

# Dependencies
node_modules/
vendor/
__pycache__/
*.pyc
.pytest_cache/
venv/
.venv/

# Build artifacts
dist/
build/
*.egg-info/

# Docker volumes
postgres_data/
redis_data/

# Temp files
*.tmp
*.temp
.cache/
EOF

# Create initial README content
cat > README.md << 'EOF'
# 🚀 ITSM Platform

Enterprise IT Service Management platform with AI integration.

## Quick Start

```bash
# Using Docker
docker-compose up -d

# Using Make
make setup
make up
```

See [docs/setup/local-setup.md](docs/setup/local-setup.md) for detailed instructions.
EOF

# Create example environment file
cat > .env.example << 'EOF'
# Application
APP_ENV=local
APP_DEBUG=true

# URLs
BACKEND_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000
AI_SERVICE_URL=http://localhost:8001

# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=itsm_platform
DB_USERNAME=itsm_user
DB_PASSWORD=secret

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=secret

# Auth Provider (auth0 or supabase)
AUTH_PROVIDER=supabase
SUPABASE_URL=
SUPABASE_ANON_KEY=
SUPABASE_SERVICE_KEY=

# AI Services
CLAUDE_API_KEY=
OPENAI_API_KEY=

# External Integrations
DATTO_API_KEY=
BITDEFENDER_API_KEY=
EOF

# Create backend composer.json
cat > backend/composer.json << 'EOF'
{
    "name": "itsm/backend",
    "type": "project",
    "description": "ITSM Platform Backend API",
    "require": {
        "php": "^8.3",
        "laravel/framework": "^11.0",
        "laravel/sanctum": "^3.3",
        "laravel/octane": "^2.0",
        "spatie/laravel-multitenancy": "^3.0",
        "spatie/laravel-permission": "^6.0",
        "spatie/laravel-data": "^3.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.0",
        "laravel/pint": "^1.0",
        "pestphp/pest": "^2.0",
        "pestphp/pest-plugin-laravel": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ]
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
EOF

# Create frontend package.json
cat > frontend/package.json << 'EOF'
{
  "name": "itsm-frontend",
  "version": "1.0.0",
  "private": true,
  "scripts": {
    "dev": "vite",
    "build": "vue-tsc && vite build",
    "preview": "vite preview",
    "test:unit": "vitest",
    "test:e2e": "cypress run",
    "lint": "eslint . --ext .vue,.js,.jsx,.cjs,.mjs,.ts,.tsx,.cts,.mts --fix --ignore-path .gitignore",
    "format": "prettier --write src/"
  },
  "dependencies": {
    "vue": "^3.4.0",
    "vue-router": "^4.2.0",
    "pinia": "^2.1.0",
    "@vueuse/core": "^10.7.0",
    "axios": "^1.6.0",
    "@tanstack/vue-query": "^5.17.0",
    "@supabase/supabase-js": "^2.39.0"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.0.0",
    "vite": "^5.0.0",
    "typescript": "^5.3.0",
    "vue-tsc": "^1.8.0",
    "@types/node": "^20.10.0",
    "tailwindcss": "^3.4.0",
    "autoprefixer": "^10.4.0",
    "postcss": "^8.4.0",
    "eslint": "^8.56.0",
    "prettier": "^3.1.0",
    "vitest": "^1.1.0",
    "cypress": "^13.6.0"
  }
}
EOF

# Create AI service requirements.txt
cat > ai-service/requirements.txt << 'EOF'
# Core
fastapi==0.104.1
uvicorn[standard]==0.25.0
python-multipart==0.0.6
python-dotenv==1.0.0

# Database
sqlalchemy==2.0.23
alembic==1.13.0
asyncpg==0.29.0

# AI/ML
scikit-learn==1.3.2
pandas==2.1.4
numpy==1.26.2
transformers==4.36.2
torch==2.1.2

# Integration
anthropic==0.8.1
openai==1.6.1
httpx==0.25.2

# Utils
pydantic==2.5.3
pydantic-settings==2.1.0
redis==5.0.1
celery==5.3.4

# Testing
pytest==7.4.3
pytest-asyncio==0.21.1
pytest-cov==4.1.0
EOF

# Create Docker files
cat > backend/Dockerfile.dev << 'EOF'
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev

RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 8000
EOF

cat > frontend/Dockerfile.dev << 'EOF'
FROM node:20-alpine

WORKDIR /app

RUN npm install -g pnpm

EXPOSE 3000

CMD ["npm", "run", "dev"]
EOF

cat > ai-service/Dockerfile.dev << 'EOF'
FROM python:3.11-slim

WORKDIR /app

RUN apt-get update && apt-get install -y \
    gcc \
    g++ \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

EXPOSE 8000

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000", "--reload"]
EOF

echo "✅ Project structure created successfully!"
echo ""
echo "📝 Next steps:"
echo "1. Copy your documentation files to the /docs folder"
echo "2. Copy your assets to the /assets folder"
echo "3. Run 'chmod +x scripts/setup-project.sh' to make this script executable"
echo "4. Run 'docker-compose up -d' to start the development environment"
echo "5. Check docs/setup/local-setup.md for detailed setup instructions"
echo ""
echo "🚀 Happy coding!"
