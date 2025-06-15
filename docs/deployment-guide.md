# ITSM Platform Deployment Guide

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Deployment Options](#deployment-options)
4. [Production Deployment](#production-deployment)
5. [Configuration](#configuration)
6. [Database Setup](#database-setup)
7. [Security](#security)
8. [Monitoring](#monitoring)
9. [Backup and Recovery](#backup-and-recovery)
10. [Troubleshooting](#troubleshooting)

## Overview

This guide provides comprehensive instructions for deploying the ITSM Platform in production environments. The platform is designed to be deployed using Docker containers and can be hosted on various cloud providers or on-premises infrastructure.

## Prerequisites

### System Requirements

**Minimum Requirements:**
- CPU: 4 cores
- RAM: 8 GB
- Storage: 50 GB SSD
- OS: Ubuntu 20.04+ / CentOS 8+ / Debian 10+

**Recommended Requirements:**
- CPU: 8+ cores
- RAM: 16+ GB
- Storage: 100+ GB SSD (RAID 10)
- OS: Ubuntu 22.04 LTS

### Software Requirements

- Docker 20.10+
- Docker Compose 2.0+
- Git
- SSL certificates
- Domain name

## Deployment Options

### 1. Single Server Deployment

Suitable for small to medium organizations (< 1000 users).

```yaml
# docker-compose.prod.yml
version: '3.8'
services:
  # All services on one host
  nginx:
    image: itsm-platform/nginx:latest
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./ssl:/etc/nginx/ssl
    depends_on:
      - backend
      - frontend
```

### 2. Multi-Server Deployment

Recommended for large organizations.

**Architecture:**
- Load Balancer (2x for HA)
- Application Servers (3+)
- Database Cluster (Primary + Replica)
- Redis Cluster (3+ nodes)
- Elasticsearch Cluster (3+ nodes)

### 3. Cloud Deployment

#### AWS

```bash
# Deploy using AWS ECS
aws ecs create-cluster --cluster-name itsm-platform

# Create task definitions
aws ecs register-task-definition --cli-input-json file://ecs-task-definition.json

# Create services
aws ecs create-service \
  --cluster itsm-platform \
  --service-name itsm-backend \
  --task-definition itsm-backend:1
```

#### Azure

```bash
# Deploy using Azure Container Instances
az container create \
  --resource-group itsm-rg \
  --name itsm-platform \
  --image itsm-platform/backend:latest \
  --dns-name-label itsm-platform \
  --ports 80 443
```

#### Google Cloud

```bash
# Deploy using Google Kubernetes Engine
gcloud container clusters create itsm-platform \
  --num-nodes=3 \
  --zone=us-central1-a

kubectl apply -f kubernetes/
```

## Production Deployment

### Step 1: Server Preparation

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Configure firewall
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### Step 2: Clone Repository

```bash
# Create application directory
sudo mkdir -p /opt/itsm-platform
cd /opt/itsm-platform

# Clone repository
git clone https://github.com/your-org/itsm-platform.git .
```

### Step 3: Environment Configuration

```bash
# Copy production environment template
cp .env.production .env

# Generate secure keys
openssl rand -base64 32  # For APP_KEY
openssl rand -base64 16  # For database passwords

# Edit configuration
nano .env
```

**Critical Environment Variables:**

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-secure-key-here
APP_URL=https://itsm.yourdomain.com

# Database
DB_HOST=postgres
DB_PASSWORD=your-secure-password

# Redis
REDIS_PASSWORD=your-redis-password

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key

# Auth0
AUTH0_DOMAIN=your-domain.auth0.com
AUTH0_CLIENT_ID=your-client-id
AUTH0_CLIENT_SECRET=your-client-secret
```

### Step 4: SSL Configuration

```bash
# Using Let's Encrypt
sudo apt install certbot
sudo certbot certonly --standalone -d itsm.yourdomain.com

# Copy certificates
sudo cp /etc/letsencrypt/live/itsm.yourdomain.com/fullchain.pem ./ssl/
sudo cp /etc/letsencrypt/live/itsm.yourdomain.com/privkey.pem ./ssl/

# Update nginx configuration
nano docker/nginx/nginx.prod.conf
```

### Step 5: Build and Deploy

```bash
# Build production images
docker-compose -f docker-compose.prod.yml build

# Start services
docker-compose -f docker-compose.prod.yml up -d

# Run migrations
docker-compose -f docker-compose.prod.yml exec backend php artisan migrate --force

# Create admin user
docker-compose -f docker-compose.prod.yml exec backend php artisan user:create-admin
```

### Step 6: Configure Reverse Proxy

```nginx
# /etc/nginx/sites-available/itsm-platform
server {
    listen 80;
    server_name itsm.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name itsm.yourdomain.com;

    ssl_certificate /etc/letsencrypt/live/itsm.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/itsm.yourdomain.com/privkey.pem;

    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

## Configuration

### Performance Tuning

**PostgreSQL** (`postgresql.conf`):
```ini
max_connections = 200
shared_buffers = 4GB
effective_cache_size = 12GB
work_mem = 20MB
maintenance_work_mem = 1GB
wal_buffers = 16MB
checkpoint_completion_target = 0.9
```

**Redis** (`redis.conf`):
```ini
maxmemory 4gb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

**PHP-FPM** (`www.conf`):
```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
```

### Queue Workers

```bash
# Create systemd service
sudo nano /etc/systemd/system/itsm-queue.service

[Unit]
Description=ITSM Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/opt/itsm-platform
ExecStart=/usr/bin/docker-compose exec -T backend php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=on-failure

[Install]
WantedBy=multi-user.target

# Enable and start
sudo systemctl enable itsm-queue
sudo systemctl start itsm-queue
```

## Database Setup

### High Availability Setup

```bash
# Primary server
docker run -d \
  --name postgres-primary \
  -e POSTGRES_PASSWORD=secure_password \
  -e POSTGRES_REPLICATION_MODE=master \
  -e POSTGRES_REPLICATION_USER=replicator \
  -e POSTGRES_REPLICATION_PASSWORD=replication_password \
  -v postgres_data:/var/lib/postgresql/data \
  postgres:16-alpine

# Replica server
docker run -d \
  --name postgres-replica \
  -e POSTGRES_REPLICATION_MODE=slave \
  -e POSTGRES_MASTER_HOST=primary-server-ip \
  -e POSTGRES_REPLICATION_USER=replicator \
  -e POSTGRES_REPLICATION_PASSWORD=replication_password \
  postgres:16-alpine
```

### Backup Configuration

```bash
# Create backup script
nano /opt/itsm-platform/scripts/backup.sh

#!/bin/bash
BACKUP_DIR="/backups/postgres"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DB_NAME="itsm_platform"

# Create backup
docker-compose exec -T postgres pg_dump -U itsm_user $DB_NAME | gzip > $BACKUP_DIR/backup_$TIMESTAMP.sql.gz

# Keep only last 7 days
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +7 -delete

# Add to crontab
0 2 * * * /opt/itsm-platform/scripts/backup.sh
```

## Security

### Security Checklist

- [ ] Change all default passwords
- [ ] Enable SSL/TLS for all connections
- [ ] Configure firewall rules
- [ ] Enable audit logging
- [ ] Set up intrusion detection
- [ ] Regular security updates
- [ ] Implement backup encryption
- [ ] Configure rate limiting
- [ ] Enable 2FA for admin accounts
- [ ] Regular security audits

### Hardening

```bash
# Disable root SSH
sudo sed -i 's/PermitRootLogin yes/PermitRootLogin no/' /etc/ssh/sshd_config

# Install fail2ban
sudo apt install fail2ban
sudo systemctl enable fail2ban

# Configure AppArmor/SELinux
sudo aa-enforce /etc/apparmor.d/docker
```

### Network Security

```yaml
# docker-compose.prod.yml
networks:
  frontend:
    driver: bridge
  backend:
    driver: bridge
    internal: true
  database:
    driver: bridge
    internal: true
```

## Monitoring

### Prometheus Configuration

```yaml
# prometheus.yml
global:
  scrape_interval: 15s
  evaluation_interval: 15s

scrape_configs:
  - job_name: 'itsm-platform'
    static_configs:
      - targets: ['backend:9090', 'frontend:9090']
    
  - job_name: 'node'
    static_configs:
      - targets: ['node-exporter:9100']
```

### Grafana Dashboards

Import pre-configured dashboards:
1. System Overview (ID: 1860)
2. PostgreSQL Dashboard (ID: 9628)
3. Redis Dashboard (ID: 763)
4. Docker Dashboard (ID: 893)

### Alerting Rules

```yaml
# alerts.yml
groups:
  - name: itsm_alerts
    rules:
      - alert: HighMemoryUsage
        expr: (node_memory_MemTotal - node_memory_MemAvailable) / node_memory_MemTotal > 0.9
        for: 5m
        annotations:
          summary: "High memory usage detected"
          
      - alert: DatabaseDown
        expr: up{job="postgresql"} == 0
        for: 1m
        annotations:
          summary: "PostgreSQL is down"
```

## Backup and Recovery

### Automated Backups

```bash
# Install backup solution
sudo apt install borgbackup

# Initialize repository
borg init --encryption=repokey /backup/borg-repo

# Create backup script
cat > /opt/backup-itsm.sh << 'EOF'
#!/bin/bash
export BORG_REPO=/backup/borg-repo
export BORG_PASSPHRASE='your-secure-passphrase'

# Backup database
docker-compose exec -T postgres pg_dumpall -U postgres > /tmp/postgres_dump.sql

# Create backup
borg create --stats --progress \
  ::{hostname}-{now} \
  /opt/itsm-platform \
  /tmp/postgres_dump.sql \
  --exclude '/opt/itsm-platform/backend/storage/logs'

# Prune old backups
borg prune -v --list \
  --keep-daily=7 \
  --keep-weekly=4 \
  --keep-monthly=6

# Cleanup
rm /tmp/postgres_dump.sql
EOF

# Schedule backup
echo "0 3 * * * /opt/backup-itsm.sh" | crontab -
```

### Disaster Recovery

```bash
# Restore from backup
borg extract /backup/borg-repo::backup-name

# Restore database
docker-compose exec -T postgres psql -U postgres < postgres_dump.sql

# Restore files
rsync -av /backup/restore/ /opt/itsm-platform/
```

## Troubleshooting

### Common Issues

#### 1. Container won't start
```bash
# Check logs
docker-compose logs backend

# Check disk space
df -h

# Check memory
free -m

# Restart Docker
sudo systemctl restart docker
```

#### 2. Database connection errors
```bash
# Test connection
docker-compose exec backend php artisan db:ping

# Check PostgreSQL logs
docker-compose logs postgres

# Verify credentials
docker-compose exec postgres psql -U itsm_user -d itsm_platform
```

#### 3. Performance issues
```bash
# Check resource usage
docker stats

# Analyze slow queries
docker-compose exec postgres pg_stat_statements

# Check queue status
docker-compose exec backend php artisan queue:status
```

### Health Checks

```bash
# Application health
curl https://itsm.yourdomain.com/api/health

# Database health
docker-compose exec postgres pg_isready

# Redis health
docker-compose exec redis redis-cli ping

# Elasticsearch health
curl http://localhost:9200/_cluster/health
```

### Log Analysis

```bash
# Aggregate logs
docker-compose logs -f --tail=100

# Search for errors
docker-compose logs | grep ERROR

# Export logs
docker-compose logs > itsm-logs-$(date +%Y%m%d).log
```

## Maintenance

### Regular Tasks

**Daily:**
- Monitor system health
- Check backup completion
- Review error logs

**Weekly:**
- Update Docker images
- Review security alerts
- Performance analysis

**Monthly:**
- Security patches
- Capacity planning
- Backup restoration test

**Quarterly:**
- Full system audit
- Disaster recovery drill
- Performance tuning

### Update Procedure

```bash
# 1. Backup current state
./scripts/backup.sh

# 2. Pull latest changes
git pull origin main

# 3. Build new images
docker-compose -f docker-compose.prod.yml build

# 4. Run migrations
docker-compose -f docker-compose.prod.yml exec backend php artisan migrate --force

# 5. Restart services (rolling update)
docker-compose -f docker-compose.prod.yml up -d --no-deps backend
docker-compose -f docker-compose.prod.yml up -d --no-deps frontend

# 6. Clear caches
docker-compose -f docker-compose.prod.yml exec backend php artisan cache:clear
```

## Support

For production support:
- Email: support@itsm-platform.com
- Documentation: https://docs.itsm-platform.com
- Emergency: +1-xxx-xxx-xxxx (24/7)