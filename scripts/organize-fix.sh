#!/bin/bash

# Script para corrigir e organizar a estrutura do projeto ITSM Platform
# Corrige nomes de pastas e move arquivos para locais corretos

set -e

echo "🔧 Corrigindo e organizando projeto ITSM Platform..."

# 1. Corrigir nome da pasta 'assents' para 'assets'
if [ -d "assents" ] && [ ! -d "assets" ]; then
    echo "📁 Renomeando 'assents' para 'assets'..."
    mv assents assets
fi

# 2. Mover arquivos de documentação para locais corretos
echo "📚 Organizando documentação..."

# Mover arquivos da pasta docs para a raiz (alguns devem ficar na raiz)
if [ -f "docs/blueprint.md" ] && [ ! -f "blueprint.md" ]; then
    mv docs/blueprint.md .
fi

if [ -f "docs/prd_itsm_platform_multi_tenant.md" ] && [ ! -f "prd_itsm_platform_multi_tenant.md" ]; then
    mv docs/prd_itsm_platform_multi_tenant.md .
fi

if [ -f "docs/projeto_itsm_multicliente.md" ] && [ ! -f "projeto_itsm_multicliente.md" ]; then
    mv docs/projeto_itsm_multicliente.md .
fi

# Remover readme duplicado em docs
if [ -f "docs/readme.md" ]; then
    rm -f docs/readme.md
fi

# 3. Mover conteúdo de pastas antigas
# Mover claude/instructions.md para docs/claude/
if [ -f "claude/instructions.md" ] && [ -d "docs/claude" ]; then
    mv claude/instructions.md docs/claude/
    rmdir claude 2>/dev/null || true
fi

# Mover setup/local-setup.md para docs/setup/
if [ -f "setup/local-setup.md" ]; then
    mkdir -p docs/setup
    mv setup/local-setup.md docs/setup/
    rmdir setup 2>/dev/null || true
fi

# 4. Renomear makefile para Makefile
if [ -f "makefile" ]; then
    mv makefile Makefile
fi

# 5. Renomear readme.md para README.md
if [ -f "readme.md" ]; then
    mv readme.md README.md
fi

# 6. Criar estrutura completa do projeto
echo "🏗️ Criando estrutura completa..."

# Backend (Laravel)
mkdir -p backend/{app,bootstrap,config,database,public,resources,routes,storage,tests}
mkdir -p backend/app/{Console,Core,Domains,Exceptions,Http,Models,Providers}
mkdir -p backend/app/Domains/{Incident,ServiceRequest,Problem,Change,Asset,User,Tenant}
mkdir -p backend/app/Core/{Auth,Tenant,Workflow,Traits,Services,Contracts}
mkdir -p backend/database/{factories,migrations,seeders}
mkdir -p backend/storage/{app,framework,logs}
mkdir -p backend/storage/framework/{cache,sessions,testing,views}
mkdir -p backend/tests/{Feature,Unit,Integration}

# Frontend (Vue.js)
mkdir -p frontend/{public,src,tests}
mkdir -p frontend/src/{assets,components,composables,layouts,modules,plugins,router,services,stores,styles,types,utils,views}
mkdir -p frontend/src/modules/{incident-management,service-catalog,dashboard,knowledge-base,admin}
mkdir -p frontend/src/components/{common,forms,charts,tables}
mkdir -p frontend/tests/{unit,e2e,integration}

# AI Service (Python/FastAPI)
mkdir -p ai-service/{app,tests,scripts,models,migrations}
mkdir -p ai-service/app/{api,core,ml,services,integrations,models,schemas,utils}
mkdir -p ai-service/app/api/{endpoints,middleware,dependencies}
mkdir -p ai-service/app/ml/{models,pipelines,utils}
mkdir -p ai-service/app/integrations/{claude,openai,webhooks}
mkdir -p ai-service/tests/{unit,integration,e2e}

# Docker
mkdir -p docker/{nginx,postgres,redis,config}

# Tests
mkdir -p tests/{e2e,integration,performance}

# Scripts
mkdir -p scripts/{setup,deploy,maintenance}

# GitHub
mkdir -p .github/{workflows,ISSUE_TEMPLATE,PULL_REQUEST_TEMPLATE}

# 7. Criar arquivos essenciais que estão faltando
echo "📄 Criando arquivos essenciais..."

# .gitignore
cat > .gitignore << 'EOF'
# OS
.DS_Store
Thumbs.db

# IDE
.idea/
.vscode/
*.swp
*.swo
*~

# Environment
.env
.env.local
.env.*.local
*.local

# Logs
logs/
*.log
npm-debug.log*
yarn-debug.log*
yarn-error.log*
lerna-debug.log*
.pnpm-debug.log*

# Dependencies
node_modules/
vendor/
__pycache__/
*.py[cod]
*$py.class
*.so
.Python
.pytest_cache/
venv/
.venv/
env/
ENV/

# Build
dist/
build/
*.egg-info/
.next/
.nuxt/
.cache/
.parcel-cache/
out/
.vuepress/dist/
.temp/

# Laravel
/public/hot
/public/storage
/storage/*.key
.phpunit.result.cache
Homestead.json
Homestead.yaml
auth.json

# Testing
coverage/
.nyc_output/
.coverage
.coverage.*
htmlcov/
*.cover
*.py,cover
.hypothesis/
.pytest_cache/
cover/

# Docker
postgres_data/
redis_data/
mysql_data/

# Misc
*.tmp
*.temp
.cache/
tmp/
temp/
*.bak
*.backup
EOF

# .env.example
cat > .env.example << 'EOF'
# Application
APP_NAME="ITSM Platform"
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

# Auth0
AUTH0_DOMAIN=your-tenant.auth0.com
AUTH0_CLIENT_ID=your-client-id
AUTH0_CLIENT_SECRET=your-client-secret
AUTH0_AUDIENCE=https://api.itsm-platform.com

# AI Services
CLAUDE_API_KEY=sk-ant-xxx
OPENAI_API_KEY=sk-xxx

# External Integrations
DATTO_API_KEY=
DATTO_API_SECRET=
BITDEFENDER_API_KEY=
BITDEFENDER_ACCESS_URL=

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=
MAIL_FROM_ADDRESS=noreply@itsm-platform.com
MAIL_FROM_NAME="${APP_NAME}"

# AWS (opcional)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=

# Monitoring
SENTRY_DSN=
LOG_CHANNEL=stack
LOG_LEVEL=debug
EOF

# CONTRIBUTING.md
cat > CONTRIBUTING.md << 'EOF'
# Contributing to ITSM Platform

## Code of Conduct
Be respectful and inclusive. We welcome contributions from everyone.

## How to Contribute
1. Fork the repository
2. Create a feature branch (`feature/ITSM-XXX-description`)
3. Make your changes following our coding standards
4. Write/update tests
5. Update documentation
6. Submit a pull request

## Coding Standards
- PHP: PSR-12
- JavaScript/TypeScript: ESLint + Prettier
- Python: PEP 8
- Commits: Conventional Commits

## Testing
All code must have tests. Run `make test` before submitting.
EOF

# 8. Criar claude.md melhorado na raiz
echo "🤖 Criando arquivo claude.md..."
touch claude.md
echo "✅ Arquivo claude.md criado. Copie o conteúdo do artifact para este arquivo."

# 9. Mostrar estrutura final
echo ""
echo "✅ Projeto organizado com sucesso!"
echo ""
echo "📁 Estrutura atual:"
find . -type d -not -path '*/\.*' -not -path './node_modules*' -not -path './vendor*' | sort | head -30

echo ""
echo "📄 Arquivos na raiz:"
ls -la | grep -E "^-" | awk '{print $9}'

echo ""
echo "📋 Próximos passos:"
echo "1. Revise o arquivo claude.md criado"
echo "2. Configure .env com suas credenciais"
echo "3. Execute: docker-compose up -d"
echo "4. Execute: make setup"
echo ""
echo "🚀 Projeto pronto para desenvolvimento!"
EOF
