# 🚀 Guia de Deploy - ITSM Platform

Este guia cobre o processo completo de deploy da plataforma ITSM, desde desenvolvimento até produção.

## 📋 Visão Geral

### Ambientes

| Ambiente | URL | Propósito | Deploy |
|----------|-----|-----------|--------|
| **Local** | http://localhost:3000 | Desenvolvimento | Manual |
| **Dev** | https://dev.itsm-platform.app | Testes de integração | Automático (develop) |
| **Staging** | https://staging.itsm-platform.app | UAT e demos | Manual (tags) |
| **Production** | https://itsm-platform.app | Produção | Manual (aprovado) |

### Estratégia de Deploy

- **Blue-Green Deployment**: Zero downtime
- **Rolling Updates**: Para workers e serviços
- **Database Migrations**: Separadas do deploy
- **Feature Flags**: Rollout gradual de features

## 🏗️ Preparação para Deploy

### 1. Checklist Pré-Deploy

- [ ] Todos os testes passando
- [ ] Code review aprovado
- [ ] Documentação atualizada
- [ ] Migrations testadas (up e down)
- [ ] Performance testada
- [ ] Segurança verificada
- [ ] Variáveis de ambiente documentadas
- [ ] Backup do banco realizado

### 2. Versionamento

Seguimos [Semantic Versioning](https://semver.org/):

```
MAJOR.MINOR.PATCH

1.2.3
│ │ └── Bugfixes
│ └──── New features (backwards compatible)
└────── Breaking changes
```

### 3. Build dos Artefatos

```bash
# Backend
cd backend
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Frontend
cd frontend
npm ci --production
npm run build

# AI Service
cd ai-service
pip install -r requirements.txt --no-cache-dir
python -m compileall app/
```

## 🚂 Deploy com Railway

### Configuração Inicial

1. **Instale Railway CLI**:
```bash
npm install -g @railway/cli
railway login
```

2. **Configure o projeto**:
```bash
railway link [project-id]
railway environment staging
```

### railway.toml Configuration

```toml
# railway.toml
[build]
builder = "NIXPACKS"

[deploy]
healthcheckPath = "/health"
healthcheckTimeout = 300
restartPolicyType = "ON_FAILURE"
restartPolicyMaxRetries = 3

# Backend Service
[[services]]
name = "backend"
source = "backend"
builder = "DOCKERFILE"
buildCommand = "composer install --no-dev"
startCommand = "php artisan octane:start --host=0.0.0.0 --port=$PORT"

[services.backend.healthcheck]
path = "/api/health"
timeout = 30

# Frontend Service
[[services]]
name = "frontend"
source = "frontend"
buildCommand = "npm ci && npm run build"
startCommand = "npm run preview -- --host 0.0.0.0 --port $PORT"

# AI Service
[[services]]
name = "ai-service"
source = "ai-service"
builder = "DOCKERFILE"
startCommand = "uvicorn app.main:app --host 0.0.0.0 --port $PORT"

# Workers
[[services]]
name = "queue-worker"
source = "backend"
startCommand = "php artisan horizon"
replicas = 3

[[services]]
name = "scheduler"
source = "backend"
startCommand = "php artisan schedule:work"
```

### Deploy Commands

```bash
# Deploy to staging
railway up --environment staging

# Deploy to production
railway up --environment production

# Deploy specific service
railway up --service backend

# Rollback
railway rollback

# View logs
railway logs --service backend --tail
```

## 📦 Docker Production Setup

### Multi-stage Dockerfile (Backend)

```dockerfile
# backend/Dockerfile
FROM php:8.3-fpm-alpine AS base

# Install dependencies
RUN apk add --no-cache \
    postgresql-dev \
    redis \
    supervisor \
    nginx

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql opcache pcntl

# Configure PHP
COPY docker/php/php.ini /usr/local/etc/php/
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/

# Build stage
FROM base AS builder

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install dependencies
RUN composer install --no-dev --no-scripts --no-autoloader

# Copy application
COPY . .

# Generate autoloader
RUN composer dump-autoload --optimize

# Production stage
FROM base AS production

WORKDIR /var/www/html

# Copy from builder
COPY --from=builder /app .

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Configure nginx
COPY docker/nginx/default.conf /etc/nginx/http.d/

# Configure supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/

# Health check
HEALTHCHECK --interval=30s --timeout=3s --retries=3 \
    CMD curl -f http://localhost/api/health || exit 1

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

### Docker Compose Production

```yaml
# docker-compose.prod.yml
version: '3.9'

services:
  nginx:
    image: nginx:alpine
    volumes:
      - ./docker/nginx/nginx.conf:/etc/nginx/nginx.conf:ro
      - static_files:/var/www/static
    ports:
      - "80:80"
      - "443:443"
    depends_on:
      - backend
      - frontend
    networks:
      - itsm-network

  backend:
    build:
      context: ./backend
      dockerfile: Dockerfile
      target: production
    environment:
      APP_ENV: production
      APP_DEBUG: false
    volumes:
      - storage_data:/var/www/html/storage
    networks:
      - itsm-network
    deploy:
      replicas: 3
      resources:
        limits:
          cpus: '1'
          memory: 1G
        reservations:
          cpus: '0.5'
          memory: 512M

  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
      target: production
    volumes:
      - static_files:/app/dist
    networks:
      - itsm-network

  ai-service:
    build:
      context: ./ai-service
      dockerfile: Dockerfile
      target: production
    networks:
      - itsm-network
    deploy:
      replicas: 2
      resources:
        limits:
          cpus: '2'
          memory: 2G

  postgres:
    image: postgres:16-alpine
    environment:
      POSTGRES_PASSWORD_FILE: /run/secrets/db_password
    secrets:
      - db_password
    volumes:
      - postgres_data:/var/lib/postgresql/data
    networks:
      - itsm-network
    deploy:
      placement:
        constraints:
          - node.labels.db == true

  redis:
    image: redis:7-alpine
    command: redis-server --requirepass ${REDIS_PASSWORD}
    volumes:
      - redis_data:/data
    networks:
      - itsm-network

volumes:
  postgres_data:
  redis_data:
  storage_data:
  static_files:

networks:
  itsm-network:
    driver: overlay
    encrypted: true

secrets:
  db_password:
    external: true
```

## 🔄 CI/CD Pipeline

### GitHub Actions Workflow

```yaml
# .github/workflows/deploy.yml
name: Deploy

on:
  push:
    branches: [main]
    tags: ['v*']
  workflow_dispatch:

env:
  RAILWAY_TOKEN: ${{ secrets.RAILWAY_TOKEN }}

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        service: [backend, frontend, ai-service]
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Run tests
        run: |
          cd ${{ matrix.service }}
          make test
      
      - name: Security scan
        uses: aquasecurity/trivy-action@master
        with:
          scan-type: 'fs'
          scan-ref: '${{ matrix.service }}'

  build:
    needs: test
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3
      
      - name: Build images
        run: |
          docker buildx build --platform linux/amd64,linux/arm64 \
            -t itsm-backend:${{ github.sha }} \
            -f backend/Dockerfile \
            --push \
            backend/

  deploy-staging:
    needs: build
    if: github.ref == 'refs/heads/main'
    runs-on: ubuntu-latest
    environment: staging
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Install Railway
        run: npm install -g @railway/cli
      
      - name: Deploy to Railway Staging
        run: |
          railway link ${{ secrets.RAILWAY_PROJECT_ID }}
          railway environment staging
          railway up --detach

  deploy-production:
    needs: build
    if: startsWith(github.ref, 'refs/tags/v')
    runs-on: ubuntu-latest
    environment: production
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Deploy to Railway Production
        run: |
          railway link ${{ secrets.RAILWAY_PROJECT_ID }}
          railway environment production
          railway up --detach
      
      - name: Run smoke tests
        run: |
          npm run test:smoke
      
      - name: Notify deployment
        uses: 8398a7/action-slack@v3
        with:
          status: ${{ job.status }}
          text: 'Production deployment ${{ github.ref }}'
        env:
          SLACK_WEBHOOK_URL: ${{ secrets.SLACK_WEBHOOK }}
```

## 🗄️ Database Migrations

### Safe Migration Strategy

1. **Sempre teste rollback**:
```php
// database/migrations/2024_01_15_add_priority_to_tickets.php
public function up()
{
    Schema::table('tickets', function (Blueprint $table) {
        $table->string('priority')->nullable()->after('status');
    });
    
    // Backfill data
    DB::table('tickets')->update(['priority' => 'medium']);
    
    // Make non-nullable after backfill
    Schema::table('tickets', function (Blueprint $table) {
        $table->string('priority')->nullable(false)->change();
    });
}

public function down()
{
    Schema::table('tickets', function (Blueprint $table) {
        $table->dropColumn('priority');
    });
}
```

2. **Deploy migrations separadamente**:
```bash
# Step 1: Deploy code that works with both schemas
railway run --service backend php artisan migrate

# Step 2: Verify migration success
railway run --service backend php artisan migrate:status

# Step 3: Deploy code that requires new schema
railway up --service backend
```

### Zero-Downtime Migrations

Para mudanças complexas, use o padrão expand-contract:

1. **Expand**: Adicione nova coluna/tabela
2. **Migrate**: Migre dados gradualmente
3. **Contract**: Remova coluna/tabela antiga

```php
// Step 1: Add new column
Schema::table('users', function (Blueprint $table) {
    $table->string('email_normalized')->nullable();
});

// Step 2: Backfill in batches (job)
User::chunk(1000, function ($users) {
    foreach ($users as $user) {
        $user->email_normalized = Str::lower($user->email);
        $user->save();
    }
});

// Step 3: Switch to new column (next deploy)
// Step 4: Drop old column (future deploy)
```

## 🔍 Monitoring e Observability

### Health Checks

```php
// routes/api.php
Route::get('/health', function () {
    $checks = [
        'database' => DB::connection()->getPdo() ? 'ok' : 'failed',
        'redis' => Redis::ping() ? 'ok' : 'failed',
        'queue' => Queue::size() < 1000 ? 'ok' : 'warning',
    ];
    
    $status = in_array('failed', $checks) ? 500 : 200;
    
    return response()->json([
        'status' => $status === 200 ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'version' => config('app.version'),
        'timestamp' => now()->toIso8601String(),
    ], $status);
});
```

### Monitoring Setup

```yaml
# docker-compose.monitoring.yml
services:
  prometheus:
    image: prom/prometheus:latest
    volumes:
      - ./monitoring/prometheus.yml:/etc/prometheus/prometheus.yml
    ports:
      - "9090:9090"

  grafana:
    image: grafana/grafana:latest
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=admin
    volumes:
      - ./monitoring/dashboards:/etc/grafana/provisioning/dashboards
    ports:
      - "3001:3000"

  loki:
    image: grafana/loki:latest
    ports:
      - "3100:3100"

  promtail:
    image: grafana/promtail:latest
    volumes:
      - /var/log:/var/log
      - ./monitoring/promtail.yml:/etc/promtail/config.yml
```

### Application Metrics

```php
// app/Http/Middleware/MetricsMiddleware.php
class MetricsMiddleware
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = microtime(true) - $start;
        
        Prometheus::histogram('http_request_duration_seconds')
            ->observe($duration, [
                'method' => $request->method(),
                'route' => $request->route()->getName(),
                'status' => $response->status(),
            ]);
            
        return $response;
    }
}
```

## 🔐 Security Hardening

### Production Environment Variables

```bash
# .env.production
APP_ENV=production
APP_DEBUG=false
APP_URL=https://itsm-platform.app

# Security
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
SESSION_SAME_SITE=strict

# Headers
SECURE_HEADERS_ENABLED=true
CONTENT_SECURITY_POLICY="default-src 'self'"
X_FRAME_OPTIONS=DENY
X_CONTENT_TYPE_OPTIONS=nosniff

# Rate Limiting
RATE_LIMIT_PER_MINUTE=60
RATE_LIMIT_BURST=100

# Encryption
APP_KEY=${SECURE_APP_KEY}
DB_ENCRYPT=true
```

### SSL/TLS Configuration

```nginx
# docker/nginx/ssl.conf
server {
    listen 443 ssl http2;
    server_name itsm-platform.app;

    ssl_certificate /etc/nginx/ssl/cert.pem;
    ssl_certificate_key /etc/nginx/ssl/key.pem;
    
    # Modern configuration
    ssl_protocols TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers off;
    
    # HSTS
    add_header Strict-Transport-Security "max-age=63072000" always;
    
    # Security headers
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
}
```

## 🔄 Rollback Strategy

### Automated Rollback

```yaml
# .github/workflows/rollback.yml
name: Rollback

on:
  workflow_dispatch:
    inputs:
      environment:
        description: 'Environment to rollback'
        required: true
        type: choice
        options:
          - staging
          - production

jobs:
  rollback:
    runs-on: ubuntu-latest
    environment: ${{ github.event.inputs.environment }}
    
    steps:
      - name: Rollback Railway
        run: |
          railway link ${{ secrets.RAILWAY_PROJECT_ID }}
          railway environment ${{ github.event.inputs.environment }}
          railway rollback
      
      - name: Verify rollback
        run: |
          ./scripts/smoke-tests.sh ${{ github.event.inputs.environment }}
```

### Manual Rollback Steps

1. **Identificar versão anterior**:
```bash
railway deployments list
```

2. **Rollback database** (se necessário):
```bash
railway run php artisan migrate:rollback --step=1
```

3. **Rollback aplicação**:
```bash
railway rollback [deployment-id]
```

4. **Verificar saúde**:
```bash
curl https://api.itsm-platform.app/health
```

## 📊 Post-Deploy Verification

### Smoke Tests

```bash
#!/bin/bash
# scripts/smoke-tests.sh

API_URL="${1:-https://api.itsm-platform.app}"

echo "Running smoke tests for $API_URL"

# Health check
curl -f "$API_URL/health" || exit 1

# API availability
curl -f "$API_URL/api/v1/status" || exit 1

# Frontend loading
curl -f "${API_URL/api/}" || exit 1

# Critical endpoints
curl -f -H "Authorization: Bearer $TEST_TOKEN" \
  "$API_URL/api/v1/tickets" || exit 1

echo "All smoke tests passed!"
```

### Performance Verification

```javascript
// scripts/performance-test.js
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '2m', target: 100 },
    { duration: '5m', target: 100 },
    { duration: '2m', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],
    http_req_failed: ['rate<0.1'],
  },
};

export default function () {
  const res = http.get('https://api.itsm-platform.app/api/v1/tickets');
  
  check(res, {
    'status is 200': (r) => r.status === 200,
    'response time < 500ms': (r) => r.timings.duration < 500,
  });
  
  sleep(1);
}
```

## 🎯 Deployment Checklist

### Pre-deployment
- [ ] All tests passing
- [ ] Security scan completed
- [ ] Performance tested
- [ ] Database backup taken
- [ ] Rollback plan documented
- [ ] Team notified

### Deployment
- [ ] Deploy to staging first
- [ ] Run smoke tests
- [ ] Monitor metrics
- [ ] Deploy to production
- [ ] Run production smoke tests

### Post-deployment
- [ ] Monitor error rates
- [ ] Check performance metrics
- [ ] Verify all services healthy
- [ ] Update status page
- [ ] Document any issues
- [ ] Close deployment ticket

## 🆘 Troubleshooting

### Common Issues

#### "502 Bad Gateway"
```bash
# Check service health
railway logs --service backend --tail

# Restart service
railway restart --service backend
```

#### "Database connection refused"
```bash
# Check database status
railway status --service postgres

# Verify connection
railway run --service backend php artisan db:show
```

#### "Redis connection timeout"
```bash
# Clear Redis
railway run --service backend php artisan cache:clear

# Check Redis memory
railway run --service redis redis-cli INFO memory
```

### Emergency Procedures

1. **Ativar modo manutenção**:
```bash
railway run --service backend php artisan down --message="Maintenance in progress"
```

2. **Escalar recursos**:
```bash
railway scale --service backend --replicas=5
```

3. **Emergency rollback**:
```bash
./scripts/emergency-rollback.sh production
```

## 📚 Recursos Adicionais

- [Railway Documentation](https://docs.railway.app)
- [Docker Best Practices](https://docs.docker.com/develop/dev-best-practices/)
- [12 Factor App](https://12factor.net/)
- [Production Readiness Checklist](https://gruntwork.io/devops-checklist/)

---

**Deploy com confiança! 🚀**
