# 👨‍💻 Guia do Desenvolvedor - ITSM Platform

## Bem-vindo ao Time!

Este guia fornece todas as informações necessárias para começar a desenvolver no ITSM Platform. Nosso objetivo é que você esteja produtivo o mais rápido possível.

## 🏁 Getting Started

### 1. Pré-requisitos

Certifique-se de ter instalado:
- **Git** 2.40+
- **Docker Desktop** 24+ com Docker Compose
- **Node.js** 20 LTS (para desenvolvimento local)
- **PHP** 8.3+ (opcional se usar Docker)
- **Python** 3.11+ (opcional se usar Docker)
- **IDE** recomendado: VS Code ou PHPStorm

### 2. Setup Inicial

```bash
# Clone o repositório
git clone git@github.com:your-org/itsm-platform.git
cd itsm-platform

# Setup com Make (recomendado)
make setup

# Ou manualmente
docker-compose up -d
docker-compose exec backend php artisan migrate --seed
```

### 3. Configurar seu IDE

#### VS Code Extensions Recomendadas
```json
{
  "recommendations": [
    // PHP
    "bmewburn.vscode-intelephense-client",
    "junstyle.php-cs-fixer",
    
    // Vue.js
    "Vue.volar",
    "Vue.vscode-typescript-vue-plugin",
    
    // Python
    "ms-python.python",
    "ms-python.vscode-pylance",
    
    // General
    "dbaeumer.vscode-eslint",
    "esbenp.prettier-vscode",
    "EditorConfig.EditorConfig",
    "eamodio.gitlens",
    "GitHub.copilot"
  ]
}
```

#### PHPStorm Plugins
- Laravel Idea
- Vue.js
- .env files support
- Docker integration

### 4. Acesso aos Serviços

Após o setup, você terá acesso a:
- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8000
- **AI Service Docs**: http://localhost:8001/docs
- **Mailhog**: http://localhost:8025
- **pgAdmin**: http://localhost:5050
- **Redis Commander**: http://localhost:8081

## 🏗️ Arquitetura e Estrutura

### Domain-Driven Design (DDD)

Organizamos o código em domínios de negócio:

```
backend/app/Domains/
├── Incident/          # Gestão de Incidentes
│   ├── Models/       # Eloquent Models
│   ├── Actions/      # Business Logic
│   ├── Services/     # Domain Services
│   ├── DTOs/         # Data Transfer Objects
│   ├── Events/       # Domain Events
│   └── Repositories/ # Data Access
├── ServiceRequest/   # Requisições de Serviço
├── User/            # Gestão de Usuários
└── Tenant/          # Multi-tenancy
```

### Princípios de Desenvolvimento

1. **Single Responsibility**: Cada classe tem uma única responsabilidade
2. **Dependency Injection**: Use o container IoC do Laravel
3. **Type Safety**: Use type hints e DTOs
4. **Testing First**: Escreva testes antes do código
5. **Clean Code**: Código legível > código inteligente

## 💻 Desenvolvimento Backend (Laravel)

### Criando um Novo Domínio

```bash
# Criar estrutura do domínio
php artisan make:domain Problem

# Isso cria:
# app/Domains/Problem/
# ├── Models/Problem.php
# ├── Actions/CreateProblemAction.php
# ├── Services/ProblemService.php
# └── Repositories/ProblemRepository.php
```

### Padrão Action Classes

Actions encapsulam uma única operação de negócio:

```php
namespace App\Domains\Incident\Actions;

use App\Core\Actions\Action;
use App\Domains\Incident\DTOs\CreateIncidentData;
use App\Domains\Incident\Models\Incident;

class CreateIncidentAction extends Action
{
    public function __construct(
        private IncidentRepository $repository,
        private NotificationService $notifications,
        private AuditService $audit
    ) {}

    public function execute(CreateIncidentData $data): Incident
    {
        // Validação de negócio
        $this->validate($data);
        
        // Criar incidente
        $incident = $this->repository->create([
            'title' => $data->title,
            'description' => $data->description,
            'priority' => $data->priority,
            'tenant_id' => tenant()->id,
        ]);
        
        // Side effects
        $this->notifications->notifyNewIncident($incident);
        $this->audit->log('incident.created', $incident);
        
        return $incident;
    }
    
    private function validate(CreateIncidentData $data): void
    {
        if ($data->priority === 'critical' && !auth()->user()->can('create-critical')) {
            throw new UnauthorizedException('Cannot create critical incidents');
        }
    }
}
```

### Data Transfer Objects (DTOs)

Use DTOs para type safety:

```php
namespace App\Domains\Incident\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\In;

class CreateIncidentData extends Data
{
    public function __construct(
        #[Required]
        public string $title,
        
        #[Required]
        public string $description,
        
        #[Required, In(['low', 'medium', 'high', 'critical'])]
        public string $priority,
        
        public ?string $category_id = null,
        public array $tags = [],
    ) {}
}
```

### Controllers Simples

Controllers devem ser thin:

```php
namespace App\Http\Controllers\Api;

use App\Domains\Incident\Actions\CreateIncidentAction;
use App\Domains\Incident\DTOs\CreateIncidentData;
use App\Http\Resources\IncidentResource;

class IncidentController extends Controller
{
    public function store(
        CreateIncidentRequest $request,
        CreateIncidentAction $action
    ): IncidentResource {
        $data = CreateIncidentData::from($request->validated());
        $incident = $action->execute($data);
        
        return new IncidentResource($incident);
    }
}
```

### Testing Backend

```php
namespace Tests\Feature\Domains\Incident;

use Tests\TestCase;
use App\Domains\Incident\Models\Incident;

class CreateIncidentTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_creates_an_incident(): void
    {
        // Arrange
        $user = User::factory()->withTenant()->create();
        $data = [
            'title' => 'Server is down',
            'description' => 'Production server not responding',
            'priority' => 'high',
        ];
        
        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/tickets', $data);
        
        // Assert
        $response->assertCreated();
        $this->assertDatabaseHas('incidents', [
            'title' => 'Server is down',
            'tenant_id' => $user->tenant_id,
        ]);
    }
}
```

### Database e Migrations

```php
// Multi-tenant migration example
Schema::create('incidents', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('tenant_id')->index();
    $table->string('number', 20)->unique();
    $table->string('title');
    $table->text('description');
    $table->enum('priority', ['low', 'medium', 'high', 'critical']);
    $table->enum('status', ['new', 'open', 'pending', 'resolved', 'closed']);
    $table->uuid('created_by_id');
    $table->uuid('assigned_to_id')->nullable();
    $table->timestamps();
    
    // Indexes for performance
    $table->index(['tenant_id', 'status']);
    $table->index(['tenant_id', 'created_at']);
    
    // Foreign keys
    $table->foreign('tenant_id')->references('id')->on('tenants');
});
```

## 🎨 Desenvolvimento Frontend (Vue.js)

### Estrutura de Módulos

```
frontend/src/modules/incident-management/
├── components/        # Componentes específicos do módulo
├── views/            # Páginas/rotas
├── stores/           # Pinia stores
├── services/         # API calls
├── composables/      # Composition functions
└── types/            # TypeScript types
```

### Componente Vue 3 Exemplo

```vue
<template>
  <div class="incident-card">
    <div class="flex justify-between items-start">
      <div>
        <h3 class="text-lg font-semibold">{{ incident.title }}</h3>
        <p class="text-gray-600">{{ incident.description }}</p>
      </div>
      <PriorityBadge :priority="incident.priority" />
    </div>
    
    <div class="mt-4 flex gap-2">
      <Button @click="assignToMe" :loading="isAssigning">
        Assign to Me
      </Button>
      <Button variant="outline" @click="showDetails">
        View Details
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useIncidentStore } from '@/stores/incident'
import { useToast } from '@/composables/useToast'
import type { Incident } from '@/types'

interface Props {
  incident: Incident
}

const props = defineProps<Props>()
const emit = defineEmits<{
  assigned: [incident: Incident]
  click: [incident: Incident]
}>()

const incidentStore = useIncidentStore()
const { showSuccess, showError } = useToast()
const isAssigning = ref(false)

async function assignToMe() {
  isAssigning.value = true
  try {
    const updated = await incidentStore.assignToCurrentUser(props.incident.id)
    showSuccess('Incident assigned successfully')
    emit('assigned', updated)
  } catch (error) {
    showError('Failed to assign incident')
  } finally {
    isAssigning.value = false
  }
}

function showDetails() {
  emit('click', props.incident)
}
</script>
```

### Pinia Store Pattern

```typescript
// stores/incident.ts
import { defineStore } from 'pinia'
import { incidentApi } from '@/services/api'
import type { Incident, CreateIncidentData } from '@/types'

export const useIncidentStore = defineStore('incident', {
  state: () => ({
    incidents: [] as Incident[],
    currentIncident: null as Incident | null,
    isLoading: false,
    filters: {
      status: 'open',
      priority: null,
    }
  }),

  getters: {
    openIncidents: (state) => 
      state.incidents.filter(i => i.status === 'open'),
    
    highPriorityIncidents: (state) =>
      state.incidents.filter(i => ['high', 'critical'].includes(i.priority)),
  },

  actions: {
    async fetchIncidents() {
      this.isLoading = true
      try {
        const { data } = await incidentApi.list(this.filters)
        this.incidents = data
      } finally {
        this.isLoading = false
      }
    },

    async createIncident(data: CreateIncidentData) {
      const { data: incident } = await incidentApi.create(data)
      this.incidents.unshift(incident)
      return incident
    },

    async assignToCurrentUser(incidentId: string) {
      const { data: updated } = await incidentApi.update(incidentId, {
        assigned_to_id: useAuthStore().user?.id
      })
      
      const index = this.incidents.findIndex(i => i.id === incidentId)
      if (index !== -1) {
        this.incidents[index] = updated
      }
      
      return updated
    }
  }
})
```

### Composables para Lógica Reutilizável

```typescript
// composables/useIncidentFilters.ts
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

export function useIncidentFilters() {
  const route = useRoute()
  const router = useRouter()
  
  const filters = ref({
    status: route.query.status || 'all',
    priority: route.query.priority || null,
    assignedTo: route.query.assigned_to || null,
    search: route.query.search || '',
  })
  
  const activeFilterCount = computed(() => {
    return Object.values(filters.value)
      .filter(v => v && v !== 'all')
      .length
  })
  
  watch(filters, (newFilters) => {
    router.push({
      query: {
        ...route.query,
        ...newFilters
      }
    })
  }, { deep: true })
  
  function clearFilters() {
    filters.value = {
      status: 'all',
      priority: null,
      assignedTo: null,
      search: '',
    }
  }
  
  return {
    filters,
    activeFilterCount,
    clearFilters
  }
}
```

### Testing Frontend

```typescript
// tests/unit/components/IncidentCard.spec.ts
import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import IncidentCard from '@/components/IncidentCard.vue'
import { createTestingPinia } from '@pinia/testing'

describe('IncidentCard', () => {
  const mockIncident = {
    id: '123',
    title: 'Test Incident',
    description: 'Test description',
    priority: 'high',
    status: 'open'
  }
  
  it('renders incident information', () => {
    const wrapper = mount(IncidentCard, {
      props: { incident: mockIncident },
      global: {
        plugins: [createTestingPinia()]
      }
    })
    
    expect(wrapper.text()).toContain('Test Incident')
    expect(wrapper.text()).toContain('Test description')
  })
  
  it('emits assigned event when assign button clicked', async () => {
    const wrapper = mount(IncidentCard, {
      props: { incident: mockIncident },
      global: {
        plugins: [createTestingPinia({
          createSpy: vi.fn
        })]
      }
    })
    
    await wrapper.find('button').trigger('click')
    
    expect(wrapper.emitted('assigned')).toBeTruthy()
  })
})
```

## 🤖 Desenvolvimento AI Service (Python)

### Estrutura FastAPI

```python
# app/main.py
from fastapi import FastAPI
from app.api import incidents, analytics, suggestions
from app.core.config import settings
from app.middleware.auth import AuthMiddleware

app = FastAPI(
    title="ITSM AI Service",
    version="1.0.0",
    docs_url="/docs" if settings.DEBUG else None
)

# Middleware
app.add_middleware(AuthMiddleware)

# Routers
app.include_router(incidents.router, prefix="/api/incidents")
app.include_router(analytics.router, prefix="/api/analytics")
app.include_router(suggestions.router, prefix="/api/suggestions")
```

### Endpoint com ML

```python
# app/api/suggestions.py
from fastapi import APIRouter, Depends
from app.schemas import SuggestionRequest, SuggestionResponse
from app.services.ml import SuggestionService
from app.dependencies import get_current_tenant

router = APIRouter()

@router.post("/", response_model=SuggestionResponse)
async def get_suggestions(
    request: SuggestionRequest,
    tenant_id: str = Depends(get_current_tenant),
    service: SuggestionService = Depends()
):
    """
    Get AI-powered resolution suggestions for a ticket.
    """
    suggestions = await service.get_suggestions(
        ticket_id=request.ticket_id,
        tenant_id=tenant_id,
        include_similar=request.include_similar
    )
    
    return SuggestionResponse(
        suggestions=suggestions,
        confidence=suggestions[0].confidence if suggestions else 0
    )
```

### ML Service Implementation

```python
# app/services/ml/suggestion_service.py
import numpy as np
from typing import List
from app.models import Ticket, Suggestion
from app.ml.models import load_similarity_model
from app.integrations.claude import ClaudeClient

class SuggestionService:
    def __init__(self):
        self.similarity_model = load_similarity_model()
        self.claude = ClaudeClient()
        
    async def get_suggestions(
        self, 
        ticket_id: str, 
        tenant_id: str,
        include_similar: bool = True
    ) -> List[Suggestion]:
        # Get ticket data
        ticket = await Ticket.get(ticket_id, tenant_id)
        
        # Find similar resolved tickets
        if include_similar:
            similar_tickets = await self._find_similar_tickets(ticket)
        else:
            similar_tickets = []
        
        # Generate suggestions using Claude AI
        claude_suggestions = await self.claude.analyze_ticket(
            title=ticket.title,
            description=ticket.description,
            similar_tickets=similar_tickets
        )
        
        # Combine and rank suggestions
        all_suggestions = self._combine_suggestions(
            similar_tickets,
            claude_suggestions
        )
        
        return all_suggestions[:5]  # Top 5 suggestions
    
    async def _find_similar_tickets(self, ticket: Ticket) -> List[Ticket]:
        # Vectorize current ticket
        ticket_vector = self.similarity_model.encode(
            f"{ticket.title} {ticket.description}"
        )
        
        # Get all resolved tickets for tenant
        resolved_tickets = await Ticket.get_resolved(ticket.tenant_id)
        
        # Calculate similarities
        similarities = []
        for resolved in resolved_tickets:
            resolved_vector = self.similarity_model.encode(
                f"{resolved.title} {resolved.description}"
            )
            similarity = np.dot(ticket_vector, resolved_vector) / (
                np.linalg.norm(ticket_vector) * np.linalg.norm(resolved_vector)
            )
            similarities.append((resolved, similarity))
        
        # Sort by similarity and return top 10
        similarities.sort(key=lambda x: x[1], reverse=True)
        return [ticket for ticket, _ in similarities[:10]]
```

## 🔒 Segurança e Best Practices

### Segurança no Backend

1. **Sempre use validação**:
```php
public function rules(): array
{
    return [
        'title' => ['required', 'string', 'max:255'],
        'description' => ['required', 'string'],
        'priority' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
        'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
    ];
}
```

2. **Authorize ações**:
```php
public function authorize(): bool
{
    return $this->user()->can('create', Incident::class);
}
```

3. **Sanitize output**:
```php
$description = e($incident->description); // Escape HTML
$markdown = Str::markdown($content, ['html_input' => 'strip']);
```

### Segurança no Frontend

1. **Nunca confie no cliente**:
```javascript
// BAD - validação apenas no frontend
if (priority === 'critical') {
  createCriticalIncident(data)
}

// GOOD - sempre valide no backend também
try {
  await api.createIncident(data) // Backend valida permissões
} catch (error) {
  if (error.code === 'FORBIDDEN') {
    showError('You cannot create critical incidents')
  }
}
```

2. **Sanitize user input**:
```vue
<template>
  <!-- Use v-text para prevenir XSS -->
  <p v-text="userContent"></p>
  
  <!-- Ou use computed com sanitização -->
  <div v-html="sanitizedHtml"></div>
</template>

<script setup>
import DOMPurify from 'dompurify'

const sanitizedHtml = computed(() => 
  DOMPurify.sanitize(props.htmlContent)
)
</script>
```

### Performance Best Practices

1. **Laravel - Evite N+1 queries**:
```php
// BAD
$incidents = Incident::all();
foreach ($incidents as $incident) {
    echo $incident->assignee->name; // N+1 query
}

// GOOD
$incidents = Incident::with('assignee')->get();
```

2. **Vue - Use lazy loading**:
```javascript
// router/index.ts
const routes = [
  {
    path: '/incidents',
    component: () => import('@/views/incidents/IncidentList.vue'),
    children: [
      {
        path: ':id',
        component: () => import('@/views/incidents/IncidentDetail.vue')
      }
    ]
  }
]
```

3. **API - Implemente caching**:
```php
public function index(Request $request)
{
    $cacheKey = 'incidents:' . $request->fullUrl();
    
    return Cache::remember($cacheKey, 300, function () use ($request) {
        return IncidentResource::collection(
            Incident::filter($request->all())
                ->paginate()
        );
    });
}
```

## 🚀 Deploy e CI/CD

### Branch Strategy

```
main
├── develop
│   ├── feature/ITSM-123-add-sla-management
│   ├── feature/ITSM-124-ai-suggestions
│   └── feature/ITSM-125-dashboard-widgets
├── release/v1.2.0
└── hotfix/ITSM-126-fix-critical-bug
```

### Commit Messages

Seguimos Conventional Commits:
```
feat(incidents): add bulk assignment feature
fix(api): correct SLA calculation for business hours
docs(api): update endpoint documentation
test(frontend): add tests for incident filters
refactor(ai): improve suggestion algorithm
chore(deps): update Laravel to 11.x
```

### Pull Request Checklist

- [ ] Código segue os padrões do projeto
- [ ] Testes escritos e passando
- [ ] Documentação atualizada
- [ ] Sem problemas de segurança
- [ ] Performance considerada
- [ ] Acessibilidade verificada (frontend)
- [ ] Migrations testadas (rollback também)

### CI/CD Pipeline

```yaml
# .github/workflows/ci.yml
name: CI

on:
  pull_request:
    branches: [main, develop]

jobs:
  backend-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: php artisan test --parallel
      - name: Run static analysis
        run: ./vendor/bin/phpstan analyse
        
  frontend-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup Node
        uses: actions/setup-node@v3
        with:
          node-version: '20'
      - name: Install dependencies
        run: npm ci
      - name: Run tests
        run: npm run test:unit
      - name: Build
        run: npm run build
```

## 📚 Recursos e Links

### Documentação Oficial
- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Documentation](https://vuejs.org/guide/)
- [FastAPI Documentation](https://fastapi.tiangolo.com/)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)

### Ferramentas Internas
- **API Documentation**: http://localhost:8000/api/documentation
- **Storybook** (componentes): http://localhost:6006
- **Coverage Reports**: http://localhost:8080/coverage
- **Performance Metrics**: http://localhost:3000/admin/metrics

### Guias Internos
- [Arquitetura do Sistema](../architecture/system-design.md)
- [API Endpoints](../api/endpoints.md)
- [Guia de Deploy](deployment-guide.md)
- [Troubleshooting](../setup/troubleshooting.md)

### Ferramentas Recomendadas
- **API Testing**: [Insomnia](https://insomnia.rest/) ou [Postman](https://www.postman.com/)
- **Database GUI**: [TablePlus](https://tableplus.com/) ou pgAdmin
- **Git GUI**: [GitKraken](https://www.gitkraken.com/) ou [Sourcetree](https://www.sourcetreeapp.com/)
- **Monitoring**: [Ray](https://myray.app/) para debugging

## 🎯 Workflow de Desenvolvimento

### 1. Pegando uma Task

```bash
# Atualize seu código
git checkout develop
git pull origin develop

# Crie uma branch para sua feature
git checkout -b feature/ITSM-123-description

# Ou para bugfix
git checkout -b bugfix/ITSM-124-description
```

### 2. Desenvolvimento

```bash
# Backend development
make shell-backend
php artisan tinker  # Para testar código

# Frontend development
make shell-frontend
npm run dev  # Hot reload ativo

# AI Service development
make shell-ai
python -m ipdb  # Debugging interativo
```

### 3. Testando

```bash
# Rode os testes antes de commitar
make test

# Testes específicos
make test-backend TEST="--filter=IncidentTest"
make test-frontend TEST="IncidentCard.spec.ts"
make test-ai TEST="test_suggestions.py"

# Verifique a cobertura
make coverage
```

### 4. Commit e Push

```bash
# Stage suas mudanças
git add .

# Commit com mensagem descritiva
git commit -m "feat(incidents): implement bulk assignment

- Add bulk selection to incident list
- Create bulk assignment modal
- Add API endpoint for bulk operations
- Update tests and documentation

Resolves: ITSM-123"

# Push para o remoto
git push origin feature/ITSM-123-description
```

### 5. Pull Request

Crie um PR no GitHub com:
- Título claro: "ITSM-123: Add bulk assignment feature"
- Descrição detalhada do que foi feito
- Screenshots (se houver mudanças visuais)
- Checklist preenchida
- Reviewers apropriados

## 🐛 Debugging e Troubleshooting

### Laravel Debugging

```php
// Use dd() para debug rápido
dd($variable);

// Use Log facade para debugging em produção
Log::debug('Processing incident', [
    'incident_id' => $incident->id,
    'user_id' => auth()->id(),
]);

// Laravel Telescope para debugging avançado
// Acesse: http://localhost:8000/telescope
```

### Vue.js Debugging

```javascript
// Vue DevTools no browser
// Instale a extensão para Chrome/Firefox

// Console logging com contexto
console.group('Incident Processing');
console.log('Incident:', incident);
console.log('User:', currentUser.value);
console.groupEnd();

// Breakpoints condicionais
if (incident.priority === 'critical') {
  debugger;
}
```

### Python Debugging

```python
# Use ipdb para debugging interativo
import ipdb; ipdb.set_trace()

# Logging estruturado
import structlog
logger = structlog.get_logger()

logger.info("processing_ticket", 
    ticket_id=ticket.id,
    tenant_id=tenant_id,
    ml_model_version="1.2.3"
)

# FastAPI automatic docs
# http://localhost:8001/docs - Swagger UI
# http://localhost:8001/redoc - ReDoc
```

### Problemas Comuns

#### "Cannot connect to database"
```bash
# Verifique se o PostgreSQL está rodando
docker-compose ps

# Verifique as credenciais
docker-compose exec backend php artisan tinker
>>> DB::connection()->getPdo();
```

#### "CORS error in frontend"
```php
// Verifique config/cors.php
'allowed_origins' => [
    'http://localhost:3000',
    env('FRONTEND_URL'),
],
```

#### "Redis connection refused"
```bash
# Restart Redis
docker-compose restart redis

# Clear cache
docker-compose exec backend php artisan cache:clear
```

## 🎨 Style Guides

### PHP/Laravel Style

```php
<?php

namespace App\Domains\Incident\Services;

use App\Core\Contracts\ServiceInterface;
use App\Domains\Incident\Models\Incident;
use Illuminate\Support\Collection;

/**
 * Service responsible for incident business logic.
 */
class IncidentService implements ServiceInterface
{
    /**
     * Create a new incident with the given data.
     *
     * @param array $data The incident data
     * @return Incident The created incident
     * @throws ValidationException
     */
    public function createIncident(array $data): Incident
    {
        // Implementation
    }
    
    /**
     * Get incidents for the current tenant.
     *
     * @return Collection<int, Incident>
     */
    public function getTenantIncidents(): Collection
    {
        return Incident::forCurrentTenant()
            ->with(['assignee', 'category'])
            ->latest()
            ->get();
    }
}
```

### Vue.js/TypeScript Style

```typescript
// Use TypeScript interfaces
interface IncidentFilters {
  status?: IncidentStatus;
  priority?: Priority;
  assignedTo?: string;
  dateRange?: DateRange;
}

// Componente com props tipadas
interface Props {
  incident: Incident;
  readonly?: boolean;
  onUpdate?: (incident: Incident) => void;
}

// Enums para valores constantes
enum IncidentStatus {
  New = 'new',
  Open = 'open',
  Pending = 'pending',
  Resolved = 'resolved',
  Closed = 'closed'
}

// Composables com return types
export function useIncidentManagement(): {
  incidents: Ref<Incident[]>;
  loading: Ref<boolean>;
  createIncident: (data: CreateIncidentData) => Promise<Incident>;
  updateIncident: (id: string, data: UpdateIncidentData) => Promise<Incident>;
} {
  // Implementation
}
```

### Python Style

```python
from typing import List, Optional, Dict, Any
from datetime import datetime
from pydantic import BaseModel, Field

class IncidentSuggestion(BaseModel):
    """Model for incident resolution suggestions."""
    
    confidence: float = Field(..., ge=0, le=1, description="Confidence score")
    resolution: str = Field(..., min_length=10)
    similar_incidents: List[str] = Field(default_factory=list)
    estimated_time: Optional[int] = Field(None, description="Minutes to resolve")
    
    class Config:
        schema_extra = {
            "example": {
                "confidence": 0.85,
                "resolution": "Restart the email service",
                "similar_incidents": ["INC001", "INC002"],
                "estimated_time": 30
            }
        }

async def analyze_incident(
    incident_id: str,
    *,  # Force keyword arguments
    include_history: bool = True,
    max_suggestions: int = 5
) -> List[IncidentSuggestion]:
    """
    Analyze an incident and provide resolution suggestions.
    
    Args:
        incident_id: The incident identifier
        include_history: Whether to include historical analysis
        max_suggestions: Maximum number of suggestions to return
        
    Returns:
        List of incident suggestions ordered by confidence
        
    Raises:
        IncidentNotFoundError: If incident doesn't exist
        AnalysisError: If analysis fails
    """
    # Implementation
```

## 🚦 Code Review Guidelines

### O que procurar em Code Reviews

1. **Segurança**
   - SQL injection vulnerabilities
   - XSS vulnerabilities
   - Autenticação e autorização adequadas
   - Dados sensíveis não expostos

2. **Performance**
   - N+1 queries
   - Caching apropriado
   - Índices de banco de dados
   - Bundle size (frontend)

3. **Manutenibilidade**
   - Código claro e legível
   - Testes adequados
   - Documentação atualizada
   - Sem código duplicado

4. **Padrões**
   - Segue style guide
   - Naming conventions
   - Estrutura de arquivos correta
   - Types/interfaces definidos

### Exemplo de Review Comment

```markdown
## 🔍 Code Review

### ✅ Pontos Positivos
- Boa cobertura de testes
- Documentação clara
- Uso apropriado de types

### 🔧 Sugestões

**Performance**: Este loop pode causar N+1 queries
```php
// Atual
foreach ($incidents as $incident) {
    $incident->assignee->notify(...);
}

// Sugerido
$incidents->load('assignee');
foreach ($incidents as $incident) {
    $incident->assignee->notify(...);
}
```

**Security**: Sanitize user input
```javascript
// Adicione sanitização
const sanitizedInput = DOMPurify.sanitize(userInput);
```

### ❓ Perguntas
- Qual foi a razão para não usar o padrão Action aqui?
- Considerou usar cache para esta query pesada?
```

## 🎉 Dicas Finais

### Produtividade

1. **Use snippets**: Configure snippets no seu editor para código comum
2. **Aliases**: Configure aliases para comandos frequentes
3. **Hot reload**: Mantenha o hot reload ativo durante desenvolvimento
4. **Dual monitors**: Use um monitor para código, outro para browser/docs

### Aprendizado

1. **Pair programming**: Programe em par com colegas experientes
2. **Code reading**: Reserve tempo para ler código de outros
3. **Experimente**: Use branches locais para experimentar ideias
4. **Pergunte**: Não hesite em pedir ajuda no Slack

### Qualidade

1. **Boy Scout Rule**: Deixe o código melhor do que encontrou
2. **YAGNI**: You Aren't Gonna Need It - não over-engineer
3. **DRY**: Don't Repeat Yourself - mas sem exageros
4. **KISS**: Keep It Simple, Stupid - simplicidade > complexidade

## 🆘 Obtendo Ajuda

- **Slack**: #itsm-platform-dev
- **Wiki**: [Internal Wiki](https://wiki.company.com/itsm)
- **Office Hours**: Terças e Quintas, 15h-16h
- **Mentoria**: Solicite um mentor para onboarding

---

**Bem-vindo ao time! Estamos animados para ver suas contribuições!** 🚀
