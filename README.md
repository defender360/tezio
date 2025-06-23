# Tezio ITSM Platform (Defender360)

> Enterprise Multi-tenant IT Service Management System

[![License](https://img.shields.io/badge/license-Proprietary-red.svg)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-green.svg)](https://vuejs.org)
[![Docker](https://img.shields.io/badge/Docker-Compose-blue.svg)](https://docker.com)

## 🎯 Overview

Tezio is a comprehensive IT Service Management (ITSM) platform built following ITIL v4 best practices. It provides enterprise-grade multi-tenant capabilities for managing IT services, incidents, changes, problems, and more.

### Key Features

- 🏢 **Multi-tenant Architecture** - Complete tenant isolation with Row Level Security
- 🎫 **Incident Management** - Full ITIL-compliant incident lifecycle
- 🔄 **Change Management** - Structured change approval workflows
- 🔍 **Problem Management** - Root cause analysis and known error database
- 📚 **Knowledge Management** - Collaborative knowledge base with AI assistance
- 🏗️ **CMDB** - Configuration Management Database with relationships
- 📋 **Service Catalog** - Self-service portal with automated provisioning
- 📊 **Analytics & Reporting** - Real-time dashboards and metrics
- 🤖 **AI Integration** - Claude AI for intelligent suggestions and automation
- 🔐 **Enterprise Security** - Multi-factor authentication, SSO, and audit trails

## 🏗️ Architecture

### Technology Stack

- **Backend**: Laravel 11 (PHP 8.3) with Domain-Driven Design
- **Frontend**: Vue 3 + TypeScript + Tailwind CSS
- **Database**: PostgreSQL 16 with Row Level Security
- **Cache/Queue**: Redis 7
- **Search**: Elasticsearch 8
- **File Storage**: MinIO (S3-compatible)
- **Containerization**: Docker + Docker Compose

### Deployment Options

#### 🚀 Simplified Setup (Recommended for Development)
- **Containers**: 6 (PostgreSQL, Redis, Backend, Nginx, Frontend)
- **Memory**: ~2GB RAM
- **Startup**: ~30 seconds
- **Features**: Core ITSM functionality with SendGrid email

#### 🏢 Enterprise Setup
- **Containers**: 20+ (Full monitoring, logging, search stack)
- **Memory**: ~8GB RAM
- **Features**: Complete observability, Elasticsearch, Grafana dashboards

## 🚀 Quick Start

### Prerequisites

- Docker & Docker Compose
- Git
- Make (optional, for easier commands)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/defender360/tezio.git
   cd tezio
   ```

2. **Setup environment**
   ```bash
   cp backend/.env.example backend/.env
   # Edit backend/.env with your configuration
   ```

3. **Start the application**
   ```bash
   # Using Makefile (recommended)
   make -f Makefile.simple setup
   
   # Or using Docker Compose directly
   docker-compose -f docker-compose.simple.yml up -d
   ```

4. **Access the application**
   - **Frontend**: http://localhost
   - **API**: http://localhost:8000
   - **Health Check**: http://localhost:8000/api/health

### First-time Setup

1. **Create your first user**
   - Navigate to http://localhost/register
   - Fill in company details (creates tenant automatically)
   - Verify email (sent via SendGrid)
   - Login and start using the platform

2. **Optional: Seed demo data**
   ```bash
   make -f Makefile.simple seed
   ```

## 📖 Documentation

- **[Complete Project Guide](claude.md)** - Comprehensive reference
- **[MCP Setup Guide](.claude/MCP_SETUP_GUIDE.md)** - Development workflow setup
- **[Architecture Documentation](docs/ARCHITECTURE_EXPLAINED.md)** - System design details
- **[API Documentation](docs/api/)** - REST API reference

## 🛠️ Development

### Useful Commands

```bash
# Start services
make -f Makefile.simple up

# View logs
make -f Makefile.simple logs

# Access containers
make -f Makefile.simple backend-shell
make -f Makefile.simple frontend-shell

# Database operations
make -f Makefile.simple migrate
make -f Makefile.simple db

# Health checks
make -f Makefile.simple status
```

### Development Workflow

1. **Backend Development** (Laravel)
   ```bash
   docker exec itsm_backend php artisan make:controller YourController
   docker exec itsm_backend php artisan migrate
   ```

2. **Frontend Development** (Vue.js)
   ```bash
   docker exec itsm_frontend npm install package-name
   # Files are auto-reloaded via volume mount
   ```

3. **Database Management**
   ```bash
   docker exec itsm_backend php artisan tinker
   docker exec itsm_postgres psql -U itsm_user -d itsm_platform
   ```

### MCP Integration

The project includes Model Context Protocol (MCP) integration for enhanced development workflow:

```bash
# Setup MCP configuration
cp .claude/mcp.json.example .claude/mcp.json
# Edit with your credentials

# Start Claude Code with MCP
claude-code --mcp-config .claude/mcp.json
```

See [MCP Setup Guide](.claude/MCP_SETUP_GUIDE.md) for detailed configuration.

## 🏢 Enterprise Features

### Multi-tenancy
- Complete data isolation per tenant
- Subdomain-based tenant identification
- Centralized user management
- Custom branding per tenant

### Security
- Laravel Sanctum authentication
- Multi-factor authentication (2FA)
- Magic link login
- Role-based access control (RBAC)
- Audit logging
- CSRF protection

### Integrations
- **Datto RMM** - Remote monitoring and management
- **SendGrid** - Email delivery
- **Claude AI** - Intelligent assistance
- **Elasticsearch** - Advanced search
- **Prometheus/Grafana** - Monitoring

## 📊 Modules

### Core ITSM Modules
- **Incident Management** - Ticket lifecycle management
- **Change Management** - Change advisory board (CAB) workflows
- **Problem Management** - Root cause analysis
- **Service Request Management** - Self-service catalog
- **Knowledge Management** - Collaborative knowledge base

### Extended Modules
- **Asset Management** - IT asset tracking
- **Configuration Management** - CMDB with relationships
- **SLA Management** - Service level agreements
- **Financial Management** - Cost tracking and chargeback
- **GRC** - Governance, Risk, and Compliance
- **Communications** - Multi-channel notifications
- **Automation** - Workflow engine
- **Infrastructure Monitoring** - Real-time monitoring
- **AIOps** - AI-powered operations

## 🤝 Contributing

This is a proprietary project. For authorized contributors:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is proprietary software. All rights reserved by Defender360.

## 🆘 Support

- **Documentation**: See [claude.md](claude.md) for complete reference
- **Issues**: Report bugs via GitHub Issues
- **Enterprise Support**: Contact daniel@defender360.com.br

---

**Built with ❤️ by the Defender360 Team**