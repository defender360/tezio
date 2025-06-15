# 🤖 Claude AI - Instruções Completas para o Projeto ITSM Platform

## 🎯 Contexto e Visão do Projeto

Você está desenvolvendo o **ITSM Platform (Defender360)**, uma plataforma enterprise de gestão de serviços de TI que compete diretamente com ServiceNow e Jira Service Management.

### Informações Chave
- **Nome Comercial**: Defender360 ITSM Platform
- **Tipo**: SaaS B2B Multi-tenant
- **Público-alvo**: MSPs e departamentos de TI (10-1000 funcionários)
- **Diferencial**: IA nativa com Claude AI + Preço justo + ITIL v4 completo
- **Stack Principal**: Laravel 11 + Vue.js 3 + FastAPI (Python) + PostgreSQL
- **Deploy**: Railway (arquitetura multi-serviço)

## 📁 Estrutura do Projeto (Mantida e Organizada)

```
itsm-platform/
├── assets/                    # Recursos visuais
│   ├── brandbook/            # Brand guidelines Defender360 (28 PNGs)
│   ├── logo1.png            # Logo principal
│   └── logo2.png            # Logo alternativo
├── backend/                  # Laravel 11 API (PHP 8.3)
│   ├── app/
│   │   ├── Core/            # Núcleo compartilhado
│   │   ├── Domains/         # DDD - Domínios de negócio
│   │   └── Http/            # Controllers e Middleware
│   ├── database/            # Migrations e Seeders
│   └── tests/               # Testes PHPUnit/Pest
├── frontend/                 # Vue.js 3 + TypeScript
│   ├── src/
│   │   ├── modules/         # Módulos por funcionalidade
│   │   ├── components/      # Componentes reutilizáveis
│   │   └── stores/          # Pinia state management
│   └── tests/               # Vitest + Cypress
├── ai-service/              # FastAPI + ML (Python 3.11)
│   ├── app/
│   │   ├── api/            # Endpoints FastAPI
│   │   ├── ml/             # Modelos e pipelines ML
│   │   └── integrations/   # Claude AI, OpenAI
│   └── tests/              # Pytest
├── docs/                    # Documentação completa
│   ├── api/                # endpoints.md
│   ├── architecture/       # system-design.md
│   ├── claude/             # instructions.md
│   ├── guides/             # developer-guide.md, deployment-guide.md
│   └── setup/              # local-setup.md
├── docker/                  # Configurações Docker
├── scripts/                 # Scripts de automação
├── tests/                   # Testes E2E
├── .github/                 # GitHub Actions CI/CD
├── blueprint.md            # Blueprint técnico completo
├── prd_itsm_platform_multi_tenant.md  # PRD detalhado
├── projeto_itsm_multicliente.md       # Plano de melhorias
├── docker-compose.yml      # Orquestração local
├── Makefile               # Comandos automatizados
├── claude.md              # Este arquivo
└── README.md              # Documentação principal
```

## 🏗️ Arquitetura e Princípios

### Arquitetura de Alto Nível
```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Vue.js SPA    │────▶│  Laravel API    │────▶│  PostgreSQL     │
│   (Frontend)    │     │   (Backend)     │     │   + Redis       │
└─────────────────┘     └─────────────────┘     └─────────────────┘
                               │
                               ▼
                        ┌─────────────────┐
                        │  Python AI/ML   │
                        │   (FastAPI)     │
                        └─────────────────┘
                               │
                    ┌──────────┴──────────┐
                    ▼                     ▼
              ┌──────────┐         ┌──────────┐
              │Claude AI │         │ ML Models│
              └──────────┘         └──────────┘
```

### Princípios Fundamentais
1. **Multi-tenancy First**: Sempre considere tenant_id em todas as operações
2. **ITIL v4 Native**: Processos padronizados (Incident, Problem, Change, Service Request)
3. **Security by Design**: Zero-trust, Auth0, criptografia em todos os níveis
4. **AI-Powered**: IA integrada em todos os processos, não como add-on
5. **Performance**: < 200ms response time, caching estratégico
6. **Clean Architecture**: DDD no backend, modular no frontend

## 💻 Padrões de Código por Stack

### Backend (Laravel) - Padrões Obrigatórios

#### Estrutura de Domínio (DDD)
```php
// app/Domains/Incident/Actions/CreateIncidentAction.php
namespace App\Domains\Incident\Actions;

use App\Core\Actions\Action;
use App\Core\Attributes\Transaction;
use App\Domains\Incident\DTOs\CreateIncidentData;
use App\Domains\Incident\Models\Incident;

class CreateIncidentAction extends Action
{
    public function __construct(
        private IncidentRepository $repository,
        private SLAService $slaService,
        private NotificationService $notifications,
        private AuditService $audit
    ) {}

    #[Transaction]
    public function execute(CreateIncidentData $data): Incident
    {
        // 1. Validação de negócio
        $this->validateBusinessRules($data);
        
        // 2. Criar incidente com tenant
        $incident = $this->repository->create([
            'tenant_id' => tenant()->id,  // SEMPRE incluir tenant_id
            'number' => $this->generateNumber(),
            'title' => $data->title,
            'description' => $data->description,
            'priority' => $data->priority,
            'impact' => $data->impact,
            'urgency' => $data->urgency,
            'category_id' => $data->category_id,
            'created_by_id' => auth()->id(),
        ]);
        
        // 3. Aplicar SLA
        $this->slaService->applyToIncident($incident);
        
        // 4. Side effects
        $this->notifications->notifyNewIncident($incident);
        $this->audit->log('incident.created', $incident);
        
        return $incident;
    }
}
```

### Frontend (Vue.js) - Padrões Obrigatórios

#### Componente com TypeScript
```vue
<script setup lang="ts">
import { computed } from 'vue'
import { useIncidentStore } from '@/stores/incident'
import type { Incident } from '@/types/incident'

interface Props {
  incident: Incident
  showActions?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showActions: true
})

const priorityClass = computed(() => `priority-${props.incident.priority}`)
</script>

<template>
  <div class="incident-card" :class="priorityClass">
    <!-- Template aqui -->
  </div>
</template>
```

### AI Service (Python) - Padrões Obrigatórios

```python
# ai-service/app/services/incident_analyzer.py
from typing import List, Optional, Dict, Any
from pydantic import BaseModel

class IncidentAnalyzerService:
    """Service for AI-powered incident analysis."""
    
    async def analyze_incident(
        self,
        incident_id: str,
        tenant_id: str
    ) -> IncidentAnalysis:
        """Analyze incident with ML and Claude AI."""
        # Implementation
```

## 🚀 Comandos Essenciais

### Setup Inicial
```bash
# 1. Configure ambiente
cp .env.example .env

# 2. Inicie com Docker
docker-compose up -d

# 3. Setup do banco
make setup-db

# 4. Crie admin
make create-admin
```

### Desenvolvimento Diário
```bash
# Logs
make logs
make logs-backend

# Shells
make shell-backend
make shell-frontend
make shell-ai

# Testes
make test
make test-backend
```

## 📋 Implementação por Fases

### Fase 1: Foundation (Atual)
- [ ] Docker setup
- [ ] Auth0 config
- [ ] Multi-tenancy
- [ ] CRUD Incidents
- [ ] Dashboard básico

### Fase 2: Core ITSM
- [ ] Service Requests
- [ ] SLA Engine
- [ ] Workflows
- [ ] Knowledge Base

### Fase 3: AI & Integrations
- [ ] Claude AI
- [ ] ML predictions
- [ ] Datto RMM
- [ ] Bitdefender

## 🔒 Segurança Obrigatória

1. **Sempre inclua tenant_id**
2. **Valide todos inputs**
3. **Authorize todas ações**
4. **Sanitize outputs**
5. **Log ações sensíveis**

## 📚 Documentação

- [Blueprint](blueprint.md)
- [PRD](prd_itsm_platform_multi_tenant.md)
- [API Docs](docs/api/endpoints.md)
- [Deploy Guide](docs/guides/deployment-guide.md)

---

**LEMBRE-SE**: Este é um projeto ENTERPRISE. Mantenha qualidade de código em nível ServiceNow!
