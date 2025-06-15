# Tezio - Defender360 ITSM Platform

## 🚀 Multi-tenant IT Service Management System

Tezio é uma plataforma completa de ITSM (IT Service Management) multi-tenant, desenvolvida para a Defender360, oferecendo gestão integrada de incidentes, problemas, mudanças, base de conhecimento e muito mais.

### 🎯 Principais Características

- **Multi-tenancy**: Isolamento completo entre clientes
- **Gestão de Incidentes**: Fluxo completo com SLA e priorização
- **Gestão de Mudanças**: CAB, aprovações e controle de riscos
- **Base de Conhecimento**: Artigos, categorias e busca inteligente
- **CMDB**: Configuration Management Database integrado
- **Portal do Cliente**: Interface self-service
- **Automação**: Workflows e integrações via API
- **AI/ML**: Análise preditiva e sugestões inteligentes

### 🛠️ Stack Tecnológica

#### Frontend
- Vue 3 + TypeScript
- Vite + TailwindCSS
- Pinia (State Management)
- Vue Router + TanStack Query

#### Backend
- Laravel 11
- PostgreSQL + Redis
- Auth0 (Authentication)
- Elasticsearch (Search)

#### AI Service
- FastAPI (Python)
- Machine Learning Models
- Natural Language Processing

#### Infrastructure
- Docker + Docker Compose
- Nginx
- Prometheus + Grafana
- ELK Stack (Logging)

### 📦 Estrutura do Projeto

```
tezio/
├── frontend/          # Vue 3 SPA Application
├── backend/           # Laravel API
├── ai-service/        # Python AI/ML Service
├── docker/            # Docker configurations
├── monitoring/        # Prometheus, Grafana configs
├── docs/              # Documentation
└── scripts/           # Utility scripts
```

### 🚀 Quick Start

```bash
# Clone o repositório
git clone https://github.com/defender360/tezio.git
cd tezio

# Configure as variáveis de ambiente
cp .env.example .env

# Inicie com Docker
docker-compose up -d

# Acesse
# Frontend: http://localhost:3000
# Backend API: http://localhost:8000
# AI Service: http://localhost:8001
```

### 📚 Documentação

- [Guia de Instalação](docs/deployment-guide.md)
- [Guia do Desenvolvedor](docs/developer-guide.md)
- [Documentação da API](docs/api/README.md)
- [Arquitetura do Sistema](docs/architecture/system-design.md)

### 🤝 Contribuindo

Veja nosso [Guia de Contribuição](CONTRIBUTING.md) para detalhes sobre nosso processo de desenvolvimento.

### 📄 Licença

Este projeto é proprietário da Defender360. Todos os direitos reservados.

---

**Defender360** - Transformando a gestão de TI