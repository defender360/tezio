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
1. **Multi-tenancy First**: Sempre considere `tenant_id` em todas as operações
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
    
    private function generateNumber(): string
    {
        return 'INC' . date('Y') . str_pad(
            Incident::whereYear('created_at', date('Y'))->count() + 1,
            6,
            '0',
            STR_PAD_LEFT
        );
    }
}
```

#### Controller Thin
```php
// app/Http/Controllers/Api/V1/IncidentController.php
class IncidentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $incidents = QueryBuilder::for(Incident::class)
            ->forTenant() // Sempre filtrar por tenant
            ->allowedFilters(['status', 'priority', 'assigned_to'])
            ->allowedSorts(['created_at', 'priority', 'updated_at'])
            ->paginate($request->get('per_page', 20));
            
        return IncidentResource::collection($incidents);
    }
    
    public function store(
        CreateIncidentRequest $request,
        CreateIncidentAction $action
    ): JsonResponse {
        $incident = $action->execute(
            CreateIncidentData::from($request->validated())
        );
        
        return new IncidentResource($incident);
    }
}
```

### Frontend (Vue.js) - Padrões Obrigatórios

#### Componente com TypeScript
```vue
<!-- src/modules/incident-management/components/IncidentCard.vue -->
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

const emit = defineEmits<{
  click: [incident: Incident]
  assign: [incident: Incident]
}>()

const incidentStore = useIncidentStore()

const priorityClass = computed(() => `priority-${props.incident.priority}`)
const isOverdue = computed(() => {
  if (!props.incident.sla_target) return false
  return new Date(props.incident.sla_target) < new Date()
})

async function handleAssign() {
  await incidentStore.assignToMe(props.incident.id)
  emit('assign', props.incident)
}
</script>

<template>
  <div 
    class="incident-card shadow-defender-md hover:shadow-defender-lg transition-shadow"
    :class="[priorityClass, { 'border-red-500': isOverdue }]"
    @click="emit('click', incident)"
  >
    <div class="flex justify-between items-start p-4">
      <div class="flex-1">
        <div class="flex items-center gap-2 mb-2">
          <span class="text-sm font-mono text-gray-500">{{ incident.number }}</span>
          <StatusBadge :status="incident.status" />
          <PriorityIndicator :priority="incident.priority" />
        </div>
        
        <h3 class="text-lg font-semibold text-deep-sea-500 mb-1">
          {{ incident.title }}
        </h3>
        
        <p class="text-gray-600 line-clamp-2">
          {{ incident.description }}
        </p>
        
        <div class="flex items-center gap-4 mt-3 text-sm text-gray-500">
          <TimeAgo :date="incident.created_at" />
          <UserAvatar 
            v-if="incident.assigned_to" 
            :user="incident.assigned_to"
            size="sm"
          />
        </div>
      </div>
      
      <div v-if="showActions" class="flex gap-2">
        <Button
          size="sm"
          variant="ghost"
          @click.stop="handleAssign"
        >
          Assign to Me
        </Button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.incident-card {
  @apply bg-white rounded-lg border-l-4 cursor-pointer;
}

.priority-critical { @apply border-red-600; }
.priority-high { @apply border-orange-600; }
.priority-medium { @apply border-yellow-600; }
.priority-low { @apply border-green-600; }
</style>
```

### AI Service (Python) - Padrões Obrigatórios

#### Service com Type Hints
```python
# ai-service/app/services/incident_analyzer.py
from typing import List, Optional, Dict, Any
from datetime import datetime
from pydantic import BaseModel

from app.ml.models import IncidentClassifier
from app.integrations.claude import ClaudeClient
from app.schemas.incident import IncidentAnalysis, SuggestionResponse

class IncidentAnalyzerService:
    """Service for AI-powered incident analysis."""
    
    def __init__(
        self,
        classifier: IncidentClassifier,
        claude_client: ClaudeClient
    ):
        self.classifier = classifier
        self.claude = claude_client
        
    async def analyze_incident(
        self,
        incident_id: str,
        tenant_id: str,
        include_suggestions: bool = True
    ) -> IncidentAnalysis:
        """
        Analyze an incident using ML and Claude AI.
        
        Args:
            incident_id: The incident identifier
            tenant_id: The tenant identifier (multi-tenancy)
            include_suggestions: Whether to include resolution suggestions
            
        Returns:
            Complete incident analysis with AI insights
        """
        # Get incident data
        incident = await self._get_incident(incident_id, tenant_id)
        
        # ML Classification
        category_prediction = await self.classifier.predict_category(
            text=f"{incident['title']} {incident['description']}"
        )
        
        # Priority prediction based on impact/urgency
        priority_prediction = await self.classifier.predict_priority(
            incident_data=incident
        )
        
        # Get similar incidents
        similar_incidents = await self._find_similar_incidents(
            incident, tenant_id
        )
        
        # Claude AI analysis
        claude_analysis = None
        if include_suggestions:
            claude_analysis = await self.claude.analyze_incident(
                incident=incident,
                similar_incidents=similar_incidents,
                context={
                    "tenant_settings": await self._get_tenant_settings(tenant_id),
                    "historical_data": await self._get_historical_context(tenant_id)
                }
            )
        
        return IncidentAnalysis(
            incident_id=incident_id,
            predicted_category=category_prediction,
            predicted_priority=priority_prediction,
            similar_incidents=similar_incidents,
            ai_suggestions=claude_analysis.suggestions if claude_analysis else [],
            confidence_score=claude_analysis.confidence if claude_analysis else 0.0,
            analysis_timestamp=datetime.utcnow()
        )
```

## 🚀 Comandos Essenciais

### Setup Inicial (Execute na ordem)
```bash
# 1. Clone o projeto
git clone [repository-url]
cd itsm-platform

# 2. Execute o script de organização
chmod +x scripts/organize-fix.sh
./scripts/organize-fix.sh

# 3. Configure ambiente
cp .env.example .env
# Edite .env com suas credenciais

# 4. Inicie com Docker
docker-compose up -d

# 5. Setup do banco de dados
make setup-db
# Ou: docker-compose exec backend php artisan migrate --seed

# 6. Crie usuário admin
make create-admin
```

### Comandos de Desenvolvimento
```bash
# Ver logs
make logs                    # Todos os serviços
make logs-backend           # Apenas backend
make logs-frontend          # Apenas frontend
make logs-ai               # Apenas AI service

# Acessar shells
make shell-backend         # Shell Laravel
make shell-frontend        # Shell Vue.js
make shell-ai             # Shell Python

# Executar comandos
make artisan cmd="make:model Domain/Problem/Models/Problem"
make npm cmd="install vue-chartjs"
make pip cmd="install pandas"

# Testes
make test                  # Todos os testes
make test-backend         # PHPUnit/Pest
make test-frontend        # Vitest
make test-ai             # Pytest

# Utilidades
make fresh               # Reset completo
make cache-clear         # Limpar caches
make format              # Formatar código
```

## 📋 Fluxo de Implementação

### Fase 1: Foundation ✅ (Em Progresso)
```
□ Setup Docker e ambientes
□ Configurar Auth0
□ Implementar multi-tenancy
□ CRUD de Incidents básico
□ Dashboard inicial
□ Integração Claude AI básica
```

### Fase 2: Core ITSM (Próxima)
```
□ Service Request Management
□ SLA Engine completa
□ Workflow automation
□ Email integration
□ Knowledge Base
□ Notifications system
```

### Fase 3: Advanced Features
```
□ Problem Management
□ Change Management
□ CMDB implementation
□ Datto RMM integration
□ Bitdefender integration
□ Advanced analytics
```

### Fase 4: Enterprise
```
□ Advanced AI/ML
□ Predictive analytics
□ Custom workflows
□ API marketplace
□ White-label support
□ Advanced reporting
```

## 🎨 Design System Defender360

### Cores Principais
```scss
// Tailwind config
$deep-sea: #043659;        // Headers, primary text
$tech-horizon: #0070AF;    // CTAs, links, primary actions
$arctic-breeze: #60B4CD;   // Secondary actions, info
$shadow-forest: #00434F;   // Dark accents
$vital-energy: #009F8D;    // Success states

// Uso
class="bg-deep-sea-500 text-white"
class="border-tech-horizon-500 hover:bg-tech-horizon-50"
class="text-vital-energy-600"
```

### Componentes Padrão
```vue
<!-- Sempre use componentes do design system -->
<DefenderCard>
  <template #header>
    <h3 class="font-brain text-xl">Title</h3>
  </template>
  <template #default>
    Content
  </template>
</DefenderCard>

<DefenderButton 
  variant="primary"
  size="medium"
  :loading="isLoading"
  @click="handleClick"
>
  Save Changes
</DefenderButton>
```

## 🔒 Checklist de Segurança

### Sempre Verifique
- [ ] Tenant isolation em todas as queries
- [ ] Validação de inputs
- [ ] Autorização de ações
- [ ] Sanitização de outputs
- [ ] Rate limiting implementado
- [ ] Logs de auditoria
- [ ] Dados sensíveis criptografados

### Exemplo de Query Segura
```php
// SEMPRE inclua tenant_id
$incidents = Incident::query()
    ->where('tenant_id', tenant()->id)  // Multi-tenancy
    ->where('user_id', auth()->id())    // Autorização
    ->whereIn('status', $request->validated('statuses', [])) // Validação
    ->select(['id', 'number', 'title', 'status']) // Apenas campos necessários
    ->with(['assignee:id,name,avatar'])  // Eager loading otimizado
    ->paginate(20);  // Sempre paginar
```

## 🧪 Padrão de Testes

### Backend Test
```php
public function test_incident_creation_respects_tenant_isolation(): void
{
    // Arrange
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $user1 = User::factory()->for($tenant1)->create();
    $user2 = User::factory()->for($tenant2)->create();
    
    // Act
    $this->actingAs($user1)->post('/api/incidents', [
        'title' => 'Test Incident',
        'description' => 'Description',
        'priority' => 'high'
    ]);
    
    // Assert - User 2 não deve ver o incident
    $this->actingAs($user2)
        ->get('/api/incidents')
        ->assertJsonCount(0, 'data');
}
```

### Frontend Test
```typescript
describe('IncidentCard', () => {
  it('displays priority correctly', () => {
    const incident = createMockIncident({ priority: 'critical' })
    
    const wrapper = mount(IncidentCard, {
      props: { incident }
    })
    
    expect(wrapper.classes()).toContain('priority-critical')
    expect(wrapper.find('.priority-indicator').text()).toBe('Critical')
  })
})
```

## 📝 Git Workflow

### Branch Naming
```
feature/ITSM-{numero}-{descricao-curta}
bugfix/ITSM-{numero}-{descricao-curta}
hotfix/ITSM-{numero}-{descricao-curta}
```

### Commit Messages (Conventional Commits)
```
feat(incidents): add bulk assignment feature
fix(sla): correct business hours calculation
docs(api): update incident endpoints documentation
test(frontend): add incident form validation tests
refactor(ai): improve suggestion algorithm performance
chore(deps): update Laravel to 11.x
```

### PR Description Template
```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Unit tests pass
- [ ] Integration tests pass
- [ ] Manual testing completed

## Checklist
- [ ] Multi-tenancy considered
- [ ] Security verified
- [ ] Performance impact assessed
- [ ] Documentation updated
```

## 🎯 Métricas de Sucesso

### Performance Targets
- API Response: < 200ms (P95)
- Page Load: < 1.5s
- Time to Interactive: < 3s
- Database queries: < 50ms

### Quality Metrics
- Test Coverage: > 80%
- Code Complexity: < 10
- Technical Debt Ratio: < 5%
- 0 Critical Security Issues

### Business Metrics
- User Satisfaction: > 4.5/5
- Ticket Resolution Time: -30%
- Automation Rate: > 60%
- SLA Compliance: > 95%

## 🆘 Troubleshooting Comum

### "Cannot connect to database"
```bash
# Verifique containers
docker-compose ps

# Verifique logs do PostgreSQL
docker-compose logs postgres

# Teste conexão
docker-compose exec backend php artisan db:show
```

### "Frontend não conecta com backend"
```bash
# Verifique CORS
grep -r "FRONTEND_URL" backend/.env

# Verifique URLs
grep -r "VITE_API_URL" frontend/.env
```

### "Erro de permissão"
```bash
# Fix permissões Laravel
docker-compose exec backend chmod -R 777 storage bootstrap/cache

# Fix permissões gerais
sudo chown -R $USER:$USER .
```

## 📚 Recursos e Documentação

### Documentação do Projeto
- 📘 [Blueprint Técnico](blueprint.md) - Arquitetura detalhada e implementação
- 📋 [PRD Completo](prd_itsm_platform_multi_tenant.md) - Requisitos e funcionalidades
- 🔧 [Guia do Desenvolvedor](docs/guides/developer-guide.md) - Como desenvolver
- 🚀 [Guia de Deploy](docs/guides/deployment-guide.md) - Deploy em produção
- 🏗️ [Arquitetura](docs/architecture/system-design.md) - Design do sistema
- 📡 [API Docs](docs/api/endpoints.md) - Endpoints disponíveis

### Links Externos Úteis
- [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)
- [Vue.js 3 Docs](https://vuejs.org/)
- [FastAPI Docs](https://fastapi.tiangolo.com/)
- [ITIL v4 Foundation](https://www.axelos.com/certifications/itil-service-management/itil-4-foundation)

## 🚦 Status do Projeto

### ✅ Concluído
- Estrutura do projeto
- Documentação base
- Docker setup
- Design system definido

### 🚧 Em Progresso
- Implementação Auth0
- Multi-tenancy
- CRUD Incidents

### 📅 Próximos Passos
1. Finalizar autenticação
2. Implementar tenant isolation
3. Criar módulo de incidents
4. Integrar Claude AI
5. Desenvolver dashboard

---

**IMPORTANTE**: Este projeto é profissional e segue padrões enterprise. Sempre priorize:
1. **Segurança** (multi-tenancy, validação, autorização)
2. **Performance** (queries otimizadas, cache)
3. **Manutenibilidade** (código limpo, testes)
4. **Documentação** (sempre atualizada)

**Você está desenvolvendo um produto que competirá com ServiceNow. Mantenha a qualidade em nível enterprise!**
