# Claude AI - Guia de Referência do Projeto Tezio ITSM Platform

## 🎯 Visão Geral do Projeto

**Nome:** Tezio ITSM Platform (Defender360)  
**Tipo:** Plataforma Enterprise Multi-tenant de IT Service Management  
**Objetivo:** Sistema completo de gestão de serviços de TI seguindo as melhores práticas ITIL v4

### Stack Tecnológica Principal

- **Backend:** Laravel 11 (PHP 8.2) com arquitetura DDD
- **Frontend:** Vue 3 + TypeScript + Vite + Tailwind CSS
- **AI Service:** FastAPI (Python 3.10) com integração Claude AI
- **Database:** PostgreSQL 16 com Row Level Security (RLS)
- **Cache/Queue:** Redis 7
- **Search:** Elasticsearch 8
- **File Storage:** MinIO (S3-compatible)
- **Containerização:** Docker + Docker Compose

## 🐳 Arquitetura Docker

### Versão Simplificada (6 Containers) - RECOMENDADA
A partir de Junho 2025, foi criada uma versão simplificada que reduz drasticamente a complexidade mantendo toda funcionalidade essencial.

**Arquivo:** `docker-compose.simple.yml`

#### Containers:
- **postgres** - PostgreSQL 16 (porta 5432)
- **redis** - Redis 7 (porta 6379)
- **backend** - PHP-FPM + Queue Worker + Scheduler via Supervisor
- **nginx** - Web server (porta 8000)
- **frontend** - Vue.js dev server (porta 80)

#### Benefícios:
- ✅ 75% menos memória (2GB vs 8GB)
- ✅ Startup 4x mais rápido (30s vs 2-3min)
- ✅ Mais fácil de debugar
- ✅ Mesma funcionalidade core
- ✅ Email via SendGrid (sem Mailpit)

#### Como usar:
```bash
# Iniciar versão simplificada
docker-compose -f docker-compose.simple.yml up -d

# Ou usando Makefile
make -f Makefile.simple setup
```

## 🔐 Sistema de Autenticação

**Tecnologia:** Laravel Sanctum (stateful authentication)

### Fluxo de Autenticação
1. Frontend solicita CSRF cookie: `GET /sanctum/csrf-cookie`
2. Frontend envia credenciais com XSRF token: `POST /api/v1/auth/login`
3. Backend cria sessão e retorna dados do usuário
4. Requisições subsequentes usam cookie de sessão

### Multi-tenancy
- Isolamento por Row Level Security (RLS) no PostgreSQL
- Cada registro tem `tenant_id`
- Políticas RLS garantem isolamento automático

## 📦 Módulos Implementados

### Gestão de Incidentes
- Ciclo completo: Open → Assigned → In Progress → Resolved → Closed
- SLA tracking com cálculo de business hours
- Escalação automática
- Merge de incidentes duplicados
- Templates de incidentes

### CMDB (Configuration Management)
- Tipos: Hardware, Software, Network, Service, Documentation
- Relacionamentos entre CIs
- Impact analysis
- Health monitoring
- Discovery integrations

## 🚀 Comandos Essenciais

### Docker & Containers - Versão Simplificada
```bash
# Gerenciamento usando Makefile
make -f Makefile.simple up        # Iniciar serviços
make -f Makefile.simple down      # Parar serviços
make -f Makefile.simple restart   # Reiniciar serviços
make -f Makefile.simple logs      # Ver logs
make -f Makefile.simple status    # Verificar status

# Acesso direto aos containers
docker exec -it itsm_backend bash
docker exec -it itsm_frontend sh
docker exec -it itsm_postgres psql -U itsm_user -d itsm_platform
```

## 🌐 Portas e Acessos

### Aplicação Principal
- **🎯 Frontend:** http://localhost (porta 80 - ATENÇÃO: NÃO use :3000!)
- **API Backend:** http://localhost:8000
- **AI Service:** http://localhost:8001

> ⚠️ **IMPORTANTE:** O frontend roda na porta 80, não 3000! Acesse sempre http://localhost sem especificar porta.

### Bancos de Dados
- **PostgreSQL:** localhost:5432
- **Redis:** localhost:6379

## 🔑 Credenciais de Desenvolvimento

### ⚠️ ATENÇÃO: Sistema sem usuários
A partir de Junho 2025, todos os usuários demo foram removidos do sistema.

### Como criar novo usuário:

#### Opção 1: Via Interface Web (Recomendado)
1. Acesse http://localhost/register
2. Preencha o formulário com:
   - Nome completo
   - Email
   - Nome da empresa (criará o tenant automaticamente)
   - Senha (mínimo 8 caracteres)
   - Aceite os termos
3. Clique em "Criar conta"
4. Verifique seu email (enviado via SendGrid)
5. Clique no link de verificação

### Banco de Dados
- **Host:** postgres (interno) / localhost:5432 (externo)
- **Database:** itsm_platform
- **Username:** itsm_user
- **Password:** secure_password_here

## 🛠️ Ferramentas MCP Disponíveis

### postgres
- Acesso completo ao banco PostgreSQL
- 150+ tabelas disponíveis
- Esquemas: incidents, changes, problems, knowledge, etc.

### github
- Criação/atualização de arquivos
- Gestão de issues e PRs
- Busca em repositórios

### brave-search
- Busca web para informações externas
- Documentação de tecnologias

## ⚠️ Problemas Conhecidos & Soluções

### 1. Módulo de Incidentes - Temporariamente com Mock Data
**Status:** ✅ Resolvido Temporariamente

**Problema Original:** 
- Erro 500 ao acessar página de incidentes
- Falha no carregamento de módulos lodash-es
- Erro de conexão WebSocket na porta 6001

**Solução Aplicada:**
1. ✅ Instalação de lodash-es no container: `docker exec itsm_frontend npm install lodash-es`
2. ✅ Desabilitação temporária do WebSocket
3. ✅ Implementação de dados mock no frontend para permitir teste da funcionalidade

**Estado Atual:**
- ✅ Frontend compila sem erros
- ✅ Página de incidentes carrega com dados mock
- ✅ Modal de criação funciona com dropdowns
- ✅ Sistema funcional para testes do usuário

## 🚨 Troubleshooting Comum

### Erro de importação de módulos (lodash-es, vue-i18n, etc)
**Problema:** "Failed to resolve import 'lodash-es'" ou similar

**Solução:**
1. Verificar se o módulo está instalado NO CONTAINER:
   ```bash
   docker exec itsm_frontend npm list lodash-es
   ```
2. Se não estiver instalado, instalar NO CONTAINER:
   ```bash
   docker exec itsm_frontend npm install lodash-es @types/lodash-es
   ```

### ⚠️ IMPORTANTE: Desenvolvimento Frontend no Docker
- **O frontend roda DENTRO do container Docker!**
- **🎯 PORTA DE ACESSO CORRETA:** http://localhost (porta 80, NÃO use :3000!)
- **Comandos NPM devem ser executados NO CONTAINER:**
  ```bash
  # ❌ Errado (no host):
  npm install lodash-es
  
  # ✅ Correto (no container):
  docker exec itsm_frontend npm install lodash-es
  ```

---

**Última atualização:** Dezembro 2024  
**Mantido por:** Equipe de Desenvolvimento Tezio