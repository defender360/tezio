# 🛠️ Setup Local - ITSM Platform

Este guia fornece instruções detalhadas para configurar o ambiente de desenvolvimento local.

## 📋 Pré-requisitos

### Sistema Operacional
- macOS 12+ / Ubuntu 22.04+ / Windows 11 com WSL2
- Mínimo 8GB RAM (16GB recomendado)
- 20GB espaço livre em disco

### Software Necessário
- **Git** 2.40+
- **Docker Desktop** 24+ com Docker Compose v2
- **Node.js** 20 LTS
- **PHP** 8.3+ (opcional se usar Docker)
- **Python** 3.11+ (opcional se usar Docker)
- **Make** (opcional mas recomendado)

## 🚀 Setup com Docker (Recomendado)

### 1. Clone o Repositório

```bash
# Clone com SSH (recomendado)
git clone git@github.com:your-org/itsm-platform.git

# Ou com HTTPS
git clone https://github.com/your-org/itsm-platform.git

cd itsm-platform
```

### 2. Configure as Variáveis de Ambiente

```bash
# Copie os arquivos de exemplo
cp .env.example .env
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
cp ai-service/.env.example ai-service/.env

# Edite o arquivo principal .env
nano .env
```

Configurações importantes no `.env`:

```env
# Ambiente
APP_ENV=local
APP_DEBUG=true

# URLs dos serviços
BACKEND_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000
AI_SERVICE_URL=http://localhost:8001

# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=itsm_platform
DB_USERNAME=itsm_user
DB_PASSWORD=your_secure_password

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=your_redis_password

# Auth (escolha um)
AUTH_PROVIDER=supabase # ou auth0
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_ANON_KEY=your-anon-key
SUPABASE_SERVICE_KEY=your-service-key

# AI Service
CLAUDE_API_KEY=your-claude-api-key
OPENAI_API_KEY=your-openai-api-key # opcional

# Integrations (opcional para desenvolvimento)
DATTO_API_KEY=your-datto-key
BITDEFENDER_API_KEY=your-bitdefender-key
```

### 3. Build e Start dos Containers

```bash
# Build all services
docker-compose build

# Start all services in detached mode
docker-compose up -d

# Ou use o Makefile
make build
make up
```

### 4. Setup Inicial do Banco de Dados

```bash
# Aguarde os containers iniciarem
sleep 10

# Execute as migrations
docker-compose exec backend php artisan migrate

# Seed com dados de desenvolvimento
docker-compose exec backend php artisan db:seed

# Ou use o Makefile
make setup-db
```

### 5. Instale as Dependências do Frontend

```bash
# Install frontend dependencies
docker-compose exec frontend npm install

# Build frontend assets
docker-compose exec frontend npm run build

# Ou localmente
cd frontend && npm install && cd ..
```

### 6. Verifique os Serviços

```bash
# Check status
docker-compose ps

# Deve mostrar todos os containers rodando:
# - itsm-backend (Laravel)
# - itsm-frontend (Vue.js)
# - itsm-ai-service (FastAPI)
# - itsm-postgres
# - itsm-redis
# - itsm-mailhog (email testing)
```

### 7. Acesse a Aplicação

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8000
- **AI Service**: http://localhost:8001
- **API Docs**: http://localhost:8000/api/documentation
- **Mailhog**: http://localhost:8025
- **Redis Commander**: http://localhost:8081

### 8. Crie um Usuário Admin

```bash
# Via artisan command
docker-compose exec backend php artisan make:admin

# Ou via tinker
docker-compose exec backend php artisan tinker
>>> User::create([
>>>     'name' => 'Admin User',
>>>     'email' => 'admin@example.com',
>>>     'password' => bcrypt('password'),
>>>     'role' => 'admin'
>>> ]);
```

## 💻 Setup Manual (Sem Docker)

### 1. Configure o PostgreSQL

```bash
# macOS
brew install postgresql@16
brew services start postgresql@16

# Ubuntu
sudo apt update
sudo apt install postgresql-16 postgresql-contrib

# Crie o database
sudo -u postgres psql
CREATE DATABASE itsm_platform;
CREATE USER itsm_user WITH ENCRYPTED PASSWORD 'your_password';
GRANT ALL PRIVILEGES ON DATABASE itsm_platform TO itsm_user;
\q
```

### 2. Configure o Redis

```bash
# macOS
brew install redis
brew services start redis

# Ubuntu
sudo apt install redis-server
sudo systemctl start redis-server
```

### 3. Setup do Backend (Laravel)

```bash
cd backend

# Instale as dependências PHP
composer install

# Configure o .env
cp .env.example .env
php artisan key:generate

# Execute as migrations
php artisan migrate --seed

# Instale o Laravel Octane (opcional)
php artisan octane:install --server=swoole

# Start o servidor
php artisan serve
# Ou com Octane: php artisan octane:start
```

### 4. Setup do Frontend (Vue.js)

```bash
cd frontend

# Instale as dependências
npm install

# Configure o .env
cp .env.example .env.local

# Start o dev server
npm run dev
```

### 5. Setup do AI Service (Python)

```bash
cd ai-service

# Crie um virtual environment
python -m venv venv
source venv/bin/activate  # No Windows: venv\Scripts\activate

# Instale as dependências
pip install -r requirements.txt

# Configure o .env
cp .env.example .env

# Execute as migrations do SQLAlchemy
alembic upgrade head

# Start o servidor
uvicorn app.main:app --reload --port 8001
```

## 🧪 Verificação da Instalação

### 1. Health Checks

```bash
# Backend health check
curl http://localhost:8000/api/health

# AI Service health check
curl http://localhost:8001/health

# Frontend (deve retornar HTML)
curl http://localhost:3000
```

### 2. Execute os Testes

```bash
# Backend tests
docker-compose exec backend php artisan test

# Frontend tests
docker-compose exec frontend npm run test:unit

# AI Service tests
docker-compose exec ai-service pytest

# Ou use o Makefile
make test
```

### 3. Verificar Logs

```bash
# All logs
docker-compose logs -f

# Specific service
docker-compose logs -f backend
docker-compose logs -f ai-service

# Laravel logs
docker-compose exec backend tail -f storage/logs/laravel.log
```

## 🔧 Troubleshooting

### Problema: Containers não iniciam

```bash
# Limpe tudo e recomece
docker-compose down -v
docker system prune -f
docker-compose up -d --build
```

### Problema: Erro de permissão no Laravel

```bash
# Fix storage permissions
docker-compose exec backend chmod -R 777 storage bootstrap/cache
```

### Problema: Frontend não conecta ao backend

```bash
# Verifique as URLs no .env do frontend
# VITE_API_URL deve apontar para http://localhost:8000
```

### Problema: Migrations falhando

```bash
# Reset database
docker-compose exec backend php artisan migrate:fresh --seed
```

## 🛠️ Comandos Úteis

### Makefile Commands

```bash
make help          # Show all commands
make up            # Start all services
make down          # Stop all services
make build         # Build all containers
make logs          # Show logs
make shell-backend # Access backend shell
make test          # Run all tests
make fresh         # Fresh install with seeds
```

### Docker Commands

```bash
# Ver logs em tempo real
docker-compose logs -f [service]

# Executar comandos
docker-compose exec backend php artisan [command]
docker-compose exec frontend npm run [command]
docker-compose exec ai-service python [command]

# Reiniciar um serviço
docker-compose restart [service]

# Rebuild específico
docker-compose build --no-cache [service]
```

### Artisan Commands Úteis

```bash
# Clear all caches
php artisan optimize:clear

# Reindex search
php artisan scout:import "App\Models\Ticket"

# Process queues
php artisan queue:work

# Generate IDE helper
php artisan ide-helper:generate
```

## 📝 Próximos Passos

1. **Configure seu IDE**:
   - Instale extensões para Laravel, Vue.js e Python
   - Configure debugging
   - Setup linting e formatting

2. **Explore a documentação**:
   - [Arquitetura](../architecture/system-design.md)
   - [Guia do Desenvolvedor](../guides/developer-guide.md)
   - [API Documentation](../api/README.md)

3. **Comece a desenvolver**:
   - Crie uma branch para sua feature
   - Siga os padrões de código
   - Escreva testes
   - Abra um PR

## 🆘 Suporte

Se encontrar problemas:

1. Verifique o [Troubleshooting Guide](troubleshooting.md)
2. Procure nas [Issues](https://github.com/your-org/itsm-platform/issues)
3. Pergunte no Slack: #itsm-platform-dev
4. Abra uma issue no GitHub

---

*Happy coding! 🚀*
