# ITSM Platform Developer Guide

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Development Setup](#development-setup)
3. [Backend Development](#backend-development)
4. [Frontend Development](#frontend-development)
5. [AI Service Development](#ai-service-development)
6. [Database](#database)
7. [API Development](#api-development)
8. [Testing](#testing)
9. [Security](#security)
10. [Performance](#performance)
11. [Deployment](#deployment)
12. [Contributing](#contributing)

## Architecture Overview

### System Architecture

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│                 │     │                 │     │                 │
│   Vue.js SPA    │────▶│  Laravel API    │────▶│   PostgreSQL    │
│                 │     │                 │     │                 │
└─────────────────┘     └─────────────────┘     └─────────────────┘
         │                       │                        │
         │                       │                        │
         ▼                       ▼                        ▼
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│                 │     │                 │     │                 │
│     Auth0       │     │   AI Service    │     │ Elasticsearch   │
│                 │     │   (FastAPI)     │     │                 │
└─────────────────┘     └─────────────────┘     └─────────────────┘
```

### Technology Stack

**Backend:**
- PHP 8.2 + Laravel 10
- PostgreSQL 16
- Redis 7
- Elasticsearch 8

**Frontend:**
- Vue 3 + TypeScript
- Vite
- Tailwind CSS
- Pinia

**Infrastructure:**
- Docker + Docker Compose
- Nginx
- Prometheus + Grafana

## Development Setup

### Prerequisites

- Docker Desktop 20.10+
- Node.js 18+
- Git
- VS Code (recommended)

### Quick Start

```bash
# Clone repository
git clone https://github.com/your-org/itsm-platform.git
cd itsm-platform

# Run setup script
./scripts/setup-environment.sh

# Access applications
# Frontend: http://localhost:3000
# Backend: http://localhost:8000
# Mailpit: http://localhost:8025
```

### IDE Setup

**VS Code Extensions:**
- PHP Intelephense
- Vue Language Features (Volar)
- ESLint
- Prettier
- GitLens
- Docker

**Configuration** (`.vscode/settings.json`):
```json
{
  "editor.formatOnSave": true,
  "editor.codeActionsOnSave": {
    "source.fixAll.eslint": true
  },
  "php.validate.executablePath": "/usr/bin/php",
  "intelephense.environment.phpVersion": "8.2.0"
}
```

## Backend Development

### Project Structure

```
backend/
├── app/
│   ├── Console/          # Artisan commands
│   ├── Core/             # Core functionality
│   ├── Domains/          # Domain-driven design
│   ├── Events/           # Event classes
│   ├── Exceptions/       # Exception handlers
│   ├── Http/             # Controllers, middleware
│   ├── Jobs/             # Queue jobs
│   ├── Mail/             # Email templates
│   ├── Models/           # Eloquent models
│   ├── Policies/         # Authorization policies
│   ├── Providers/        # Service providers
│   └── Services/         # Business logic
├── bootstrap/            # Application bootstrapping
├── config/               # Configuration files
├── database/             # Migrations, factories, seeds
├── routes/               # API routes
├── storage/              # Logs, cache, uploads
└── tests/                # Test suites
```

### Domain-Driven Design

Each domain is self-contained:

```php
// app/Domains/Incident/Models/Incident.php
namespace App\Domains\Incident\Models;

use App\Core\Models\BaseModel;
use App\Core\Traits\HasWorkflow;

class Incident extends BaseModel
{
    use HasWorkflow;
    
    protected $fillable = [
        'title', 'description', 'priority', 'status'
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'resolved_at' => 'datetime'
    ];
    
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
```

### Creating a New Feature

1. **Create Migration:**
```bash
php artisan make:migration create_features_table
```

2. **Create Model:**
```php
// app/Models/Feature.php
namespace App\Models;

use App\Core\Models\BaseModel;

class Feature extends BaseModel
{
    protected $fillable = ['name', 'description', 'enabled'];
}
```

3. **Create Controller:**
```php
// app/Http/Controllers/Api/V1/FeatureController.php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Http\Resources\FeatureResource;

class FeatureController extends Controller
{
    public function index()
    {
        $features = Feature::paginate();
        return FeatureResource::collection($features);
    }
    
    public function store(StoreFeatureRequest $request)
    {
        $feature = Feature::create($request->validated());
        return new FeatureResource($feature);
    }
}
```

4. **Create Request Validation:**
```php
// app/Http/Requests/StoreFeatureRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeatureRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'enabled' => 'boolean'
        ];
    }
}
```

5. **Add Routes:**
```php
// routes/api.php
Route::apiResource('features', FeatureController::class);
```

### Service Layer Pattern

```php
// app/Services/IncidentService.php
namespace App\Services;

use App\Models\Incident;
use App\Events\IncidentCreated;
use Illuminate\Support\Facades\DB;

class IncidentService
{
    public function createIncident(array $data): Incident
    {
        return DB::transaction(function () use ($data) {
            $incident = Incident::create($data);
            
            $this->calculateSLA($incident);
            $this->assignToTeam($incident);
            
            event(new IncidentCreated($incident));
            
            return $incident;
        });
    }
    
    private function calculateSLA(Incident $incident): void
    {
        // SLA calculation logic
    }
    
    private function assignToTeam(Incident $incident): void
    {
        // Auto-assignment logic
    }
}
```

### Multi-Tenancy

All models use tenant scoping:

```php
// app/Core/Traits/BelongsToTenant.php
namespace App\Core\Traits;

use App\Core\Scopes\TenantScope;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        static::addGlobalScope(new TenantScope);
        
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
```

## Frontend Development

### Project Structure

```
frontend/
├── src/
│   ├── assets/           # Static assets
│   ├── components/       # Vue components
│   ├── composables/      # Composition API utilities
│   ├── layouts/          # Page layouts
│   ├── router/           # Vue Router config
│   ├── services/         # API services
│   ├── stores/           # Pinia stores
│   ├── styles/           # Global styles
│   ├── types/            # TypeScript types
│   ├── utils/            # Utility functions
│   └── views/            # Page components
├── public/               # Public assets
└── tests/                # Test files
```

### Component Development

```vue
<!-- components/incidents/IncidentCard.vue -->
<template>
  <div 
    class="incident-card"
    :class="{ 'incident-card--urgent': isUrgent }"
    @click="handleClick"
  >
    <div class="incident-header">
      <h3>{{ incident.title }}</h3>
      <StatusBadge :status="incident.status" />
    </div>
    
    <div class="incident-body">
      <p>{{ incident.description }}</p>
    </div>
    
    <div class="incident-footer">
      <UserAvatar :user="incident.assignee" />
      <TimeAgo :date="incident.created_at" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import type { Incident } from '@/types'
import StatusBadge from '@/components/common/StatusBadge.vue'
import UserAvatar from '@/components/common/UserAvatar.vue'
import TimeAgo from '@/components/common/TimeAgo.vue'

interface Props {
  incident: Incident
}

const props = defineProps<Props>()
const router = useRouter()

const isUrgent = computed(() => 
  props.incident.priority === 'critical' || 
  props.incident.priority === 'high'
)

const handleClick = () => {
  router.push({
    name: 'incident-detail',
    params: { id: props.incident.id }
  })
}
</script>

<style scoped>
.incident-card {
  @apply bg-white rounded-lg shadow p-4 cursor-pointer
         hover:shadow-md transition-shadow;
}

.incident-card--urgent {
  @apply border-l-4 border-red-500;
}

.incident-header {
  @apply flex justify-between items-start mb-3;
}

.incident-body {
  @apply text-gray-600 text-sm mb-4;
}

.incident-footer {
  @apply flex justify-between items-center;
}
</style>
```

### State Management with Pinia

```typescript
// stores/incident.ts
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Incident } from '@/types'
import { api } from '@/services/api'

export const useIncidentStore = defineStore('incident', () => {
  // State
  const incidents = ref<Incident[]>([])
  const currentIncident = ref<Incident | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  
  // Getters
  const openIncidents = computed(() => 
    incidents.value.filter(i => i.status !== 'closed')
  )
  
  const myIncidents = computed(() => 
    incidents.value.filter(i => i.assigned_to === currentUser.value?.id)
  )
  
  // Actions
  async function fetchIncidents(params?: any) {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.get('/incidents', { params })
      incidents.value = response.data.data
    } catch (err) {
      error.value = 'Failed to fetch incidents'
      console.error(err)
    } finally {
      loading.value = false
    }
  }
  
  async function createIncident(data: CreateIncidentDto) {
    try {
      const response = await api.post('/incidents', data)
      const newIncident = response.data.data
      incidents.value.unshift(newIncident)
      return newIncident
    } catch (err) {
      error.value = 'Failed to create incident'
      throw err
    }
  }
  
  return {
    // State
    incidents,
    currentIncident,
    loading,
    error,
    
    // Getters
    openIncidents,
    myIncidents,
    
    // Actions
    fetchIncidents,
    createIncident
  }
})
```

### API Service Layer

```typescript
// services/api.ts
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL + '/api/v1',
  headers: {
    'Content-Type': 'application/json'
  }
})

// Request interceptor
api.interceptors.request.use(
  config => {
    const authStore = useAuthStore()
    if (authStore.token) {
      config.headers.Authorization = `Bearer ${authStore.token}`
    }
    return config
  },
  error => Promise.reject(error)
)

// Response interceptor
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      const authStore = useAuthStore()
      authStore.logout()
    }
    return Promise.reject(error)
  }
)

// Service modules
export const incidentService = {
  list: (params?: any) => api.get('/incidents', { params }),
  get: (id: string) => api.get(`/incidents/${id}`),
  create: (data: any) => api.post('/incidents', data),
  update: (id: string, data: any) => api.put(`/incidents/${id}`, data),
  delete: (id: string) => api.delete(`/incidents/${id}`),
  
  // Specific actions
  assign: (id: string, userId: string) => 
    api.post(`/incidents/${id}/assign`, { user_id: userId }),
    
  addComment: (id: string, content: string) =>
    api.post(`/incidents/${id}/comments`, { content }),
    
  uploadAttachment: (id: string, file: File) => {
    const formData = new FormData()
    formData.append('file', file)
    return api.post(`/incidents/${id}/attachments`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  }
}
```

## AI Service Development

### FastAPI Structure

```python
# ai-service/app/main.py
from fastapi import FastAPI
from app.api.v1.api import api_router
from app.core.config import settings

app = FastAPI(
    title=settings.PROJECT_NAME,
    version=settings.VERSION,
    openapi_url=f"{settings.API_V1_STR}/openapi.json"
)

app.include_router(api_router, prefix=settings.API_V1_STR)

@app.on_event("startup")
async def startup_event():
    # Initialize ML models
    from app.ml.model_loader import load_models
    await load_models()
```

### ML Pipeline

```python
# ai-service/app/ml/ticket_classifier.py
import numpy as np
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import MultinomialNB
import joblib

class TicketClassifier:
    def __init__(self):
        self.vectorizer = TfidfVectorizer(max_features=1000)
        self.classifier = MultinomialNB()
        self.load_model()
    
    def load_model(self):
        try:
            self.vectorizer = joblib.load('models/vectorizer.pkl')
            self.classifier = joblib.load('models/classifier.pkl')
        except FileNotFoundError:
            # Train new model if not exists
            self.train()
    
    def predict(self, text: str) -> dict:
        features = self.vectorizer.transform([text])
        category = self.classifier.predict(features)[0]
        probability = self.classifier.predict_proba(features).max()
        
        return {
            'category': category,
            'confidence': float(probability),
            'subcategories': self.get_subcategories(category)
        }
    
    def train(self, X=None, y=None):
        if X is None:
            # Load training data
            X, y = self.load_training_data()
        
        self.vectorizer.fit(X)
        features = self.vectorizer.transform(X)
        self.classifier.fit(features, y)
        
        # Save model
        joblib.dump(self.vectorizer, 'models/vectorizer.pkl')
        joblib.dump(self.classifier, 'models/classifier.pkl')
```

## Database

### Schema Design

```sql
-- Multi-tenant schema
CREATE TABLE tenants (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    settings JSONB DEFAULT '{}',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Incidents table with tenant isolation
CREATE TABLE incidents (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    number VARCHAR(20) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    priority VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL,
    impact VARCHAR(20),
    urgency VARCHAR(20),
    reporter_id UUID REFERENCES users(id),
    assigned_to UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP,
    closed_at TIMESTAMP,
    UNIQUE(tenant_id, number)
);

-- Row Level Security
ALTER TABLE incidents ENABLE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON incidents
    FOR ALL
    USING (tenant_id = current_setting('app.current_tenant')::uuid);
```

### Migrations Best Practices

```php
// database/migrations/2024_01_01_create_incidents_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained();
            $table->string('number', 20);
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical']);
            $table->enum('status', ['new', 'in_progress', 'resolved', 'closed']);
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'priority']);
            $table->index(['tenant_id', 'created_at']);
            $table->unique(['tenant_id', 'number']);
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('incidents');
    }
};
```

### Query Optimization

```php
// Efficient query with eager loading
$incidents = Incident::with(['assignee', 'reporter', 'comments' => function ($query) {
    $query->latest()->limit(5);
}])
->where('status', 'open')
->where('priority', 'high')
->orderBy('created_at', 'desc')
->paginate(20);

// Using query scopes
$incidents = Incident::query()
    ->open()
    ->highPriority()
    ->assignedTo($userId)
    ->recentlyUpdated()
    ->paginate();
```

## API Development

### RESTful Principles

```php
// Follow REST conventions
Route::apiResource('incidents', IncidentController::class);
// GET    /incidents          - List
// POST   /incidents          - Create
// GET    /incidents/{id}     - Show
// PUT    /incidents/{id}     - Update
// DELETE /incidents/{id}     - Delete

// Custom actions
Route::post('incidents/{incident}/assign', [IncidentController::class, 'assign']);
Route::post('incidents/{incident}/comments', [IncidentController::class, 'addComment']);
```

### API Versioning

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::apiResource('incidents', V1\IncidentController::class);
});

Route::prefix('v2')->group(function () {
    Route::apiResource('incidents', V2\IncidentController::class);
});
```

### Request/Response Format

```php
// app/Http/Resources/IncidentResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'reporter' => new UserResource($this->whenLoaded('reporter')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'links' => [
                'self' => route('incidents.show', $this->id),
                'comments' => route('incidents.comments', $this->id)
            ]
        ];
    }
}
```

## Testing

### Backend Testing

```php
// tests/Feature/IncidentApiTest.php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Incident;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IncidentApiTest extends TestCase
{
    use RefreshDatabase;
    
    private User $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }
    
    public function test_can_list_incidents()
    {
        Incident::factory()->count(5)->create([
            'tenant_id' => $this->user->tenant_id
        ]);
        
        $response = $this->getJson('/api/v1/incidents');
        
        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'status', 'priority']
                ],
                'meta' => ['current_page', 'total']
            ]);
    }
    
    public function test_can_create_incident()
    {
        $data = [
            'title' => 'Test Incident',
            'description' => 'Test description',
            'impact' => 'high',
            'urgency' => 'high'
        ];
        
        $response = $this->postJson('/api/v1/incidents', $data);
        
        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Test Incident')
            ->assertJsonPath('data.priority', 'critical');
        
        $this->assertDatabaseHas('incidents', [
            'title' => 'Test Incident',
            'tenant_id' => $this->user->tenant_id
        ]);
    }
}
```

### Frontend Testing

```typescript
// tests/unit/components/IncidentCard.test.ts
import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import IncidentCard from '@/components/incidents/IncidentCard.vue'
import { createRouter, createWebHistory } from 'vue-router'

const mockIncident = {
  id: '1',
  title: 'Test Incident',
  description: 'Test description',
  priority: 'high',
  status: 'open',
  created_at: new Date().toISOString()
}

const router = createRouter({
  history: createWebHistory(),
  routes: []
})

describe('IncidentCard', () => {
  it('renders incident information', () => {
    const wrapper = mount(IncidentCard, {
      props: { incident: mockIncident },
      global: { plugins: [router] }
    })
    
    expect(wrapper.text()).toContain('Test Incident')
    expect(wrapper.text()).toContain('Test description')
  })
  
  it('shows urgent indicator for high priority', () => {
    const wrapper = mount(IncidentCard, {
      props: { incident: mockIncident },
      global: { plugins: [router] }
    })
    
    expect(wrapper.classes()).toContain('incident-card--urgent')
  })
  
  it('navigates to detail on click', async () => {
    const wrapper = mount(IncidentCard, {
      props: { incident: mockIncident },
      global: { plugins: [router] }
    })
    
    await wrapper.trigger('click')
    
    expect(router.currentRoute.value.name).toBe('incident-detail')
  })
})
```

### E2E Testing

```typescript
// tests/e2e/incident-workflow.spec.ts
import { test, expect } from '@playwright/test'

test('complete incident workflow', async ({ page }) => {
  // Login
  await page.goto('/login')
  await page.fill('[data-testid="email"]', 'test@example.com')
  await page.fill('[data-testid="password"]', 'password')
  await page.click('[data-testid="login-button"]')
  
  // Create incident
  await page.goto('/incidents')
  await page.click('[data-testid="create-incident"]')
  await page.fill('[data-testid="title"]', 'E2E Test Incident')
  await page.fill('[data-testid="description"]', 'Test description')
  await page.selectOption('[data-testid="impact"]', 'high')
  await page.selectOption('[data-testid="urgency"]', 'high')
  await page.click('[data-testid="submit"]')
  
  // Verify creation
  await expect(page.locator('text=E2E Test Incident')).toBeVisible()
  
  // Resolve incident
  await page.click('text=E2E Test Incident')
  await page.click('[data-testid="resolve-button"]')
  await page.fill('[data-testid="resolution"]', 'Test resolution')
  await page.click('[data-testid="confirm-resolve"]')
  
  // Verify resolution
  await expect(page.locator('[data-testid="status"]')).toContainText('Resolved')
})
```

## Security

### Authentication & Authorization

```php
// app/Http/Middleware/CheckPermission.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!$request->user()->can($permission)) {
            abort(403, 'Unauthorized action.');
        }
        
        return $next($request);
    }
}

// Usage in routes
Route::middleware(['auth', 'permission:manage-incidents'])->group(function () {
    Route::delete('/incidents/{incident}', [IncidentController::class, 'destroy']);
});
```

### Input Validation

```php
// app/Http/Requests/UpdateIncidentRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:5000',
            'status' => [
                'sometimes',
                Rule::in(['new', 'in_progress', 'resolved', 'closed'])
            ],
            'priority' => [
                'sometimes',
                Rule::in(['low', 'medium', 'high', 'critical'])
            ],
            'assigned_to' => 'sometimes|exists:users,id'
        ];
    }
    
    public function messages()
    {
        return [
            'title.max' => 'Title cannot exceed 255 characters',
            'status.in' => 'Invalid status value',
            'assigned_to.exists' => 'Selected user does not exist'
        ];
    }
}
```

### XSS Prevention

```vue
<!-- Always use v-text or {{ }} for user content -->
<template>
  <!-- Safe -->
  <div>{{ userContent }}</div>
  <div v-text="userContent"></div>
  
  <!-- Dangerous - only use with trusted content -->
  <div v-html="sanitizedContent"></div>
</template>

<script setup>
import DOMPurify from 'dompurify'

const sanitizedContent = computed(() => 
  DOMPurify.sanitize(props.htmlContent)
)
</script>
```

## Performance

### Database Optimization

```php
// Use eager loading to prevent N+1 queries
$incidents = Incident::with(['assignee', 'reporter', 'comments'])->get();

// Use chunking for large datasets
Incident::chunk(100, function ($incidents) {
    foreach ($incidents as $incident) {
        // Process incident
    }
});

// Use database indexes
Schema::table('incidents', function (Blueprint $table) {
    $table->index(['tenant_id', 'status', 'created_at']);
    $table->index(['tenant_id', 'assigned_to']);
});
```

### Caching Strategy

```php
// app/Services/IncidentService.php
use Illuminate\Support\Facades\Cache;

class IncidentService
{
    public function getMetrics($tenantId)
    {
        return Cache::remember("metrics.tenant.{$tenantId}", 300, function () use ($tenantId) {
            return [
                'total' => Incident::where('tenant_id', $tenantId)->count(),
                'open' => Incident::where('tenant_id', $tenantId)->open()->count(),
                'overdue' => Incident::where('tenant_id', $tenantId)->overdue()->count()
            ];
        });
    }
    
    public function clearCache($tenantId)
    {
        Cache::forget("metrics.tenant.{$tenantId}");
    }
}
```

### Frontend Performance

```typescript
// Lazy loading routes
const routes = [
  {
    path: '/incidents',
    component: () => import('@/views/incidents/IncidentListView.vue')
  }
]

// Component lazy loading
const HeavyComponent = defineAsyncComponent(() =>
  import('@/components/HeavyComponent.vue')
)

// Virtual scrolling for large lists
<template>
  <RecycleScroller
    :items="incidents"
    :item-size="80"
    key-field="id"
    v-slot="{ item }"
  >
    <IncidentCard :incident="item" />
  </RecycleScroller>
</template>
```

## Deployment

### Docker Build

```dockerfile
# backend/Dockerfile.prod
FROM php:8.2-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    postgresql-dev \
    redis \
    && docker-php-ext-install pdo_pgsql opcache

# Copy application
COPY . /var/www/html
WORKDIR /var/www/html

# Install composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
```

### CI/CD Pipeline

```yaml
# .github/workflows/deploy.yml
name: Deploy

on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Run tests
        run: |
          docker-compose -f docker-compose.test.yml up --abort-on-container-exit
          
  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Build and push Docker images
        run: |
          docker build -t itsm/backend:${{ github.sha }} ./backend
          docker build -t itsm/frontend:${{ github.sha }} ./frontend
          docker push itsm/backend:${{ github.sha }}
          docker push itsm/frontend:${{ github.sha }}
      
      - name: Deploy to production
        run: |
          ssh deploy@production "cd /opt/itsm && ./deploy.sh ${{ github.sha }}"
```

## Contributing

### Code Style

**PHP (PSR-12):**
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Incident;
use Illuminate\Support\Collection;

final class IncidentAnalyzer
{
    public function __construct(
        private readonly IncidentRepository $repository
    ) {
    }
    
    public function analyzePattern(Collection $incidents): array
    {
        return $incidents
            ->groupBy('category')
            ->map(fn (Collection $items) => $items->count())
            ->toArray();
    }
}
```

**TypeScript:**
```typescript
// Use consistent naming and typing
interface IncidentFilters {
  status?: IncidentStatus[]
  priority?: Priority[]
  assignedTo?: string
  dateRange?: DateRange
}

// Prefer composition over inheritance
const useIncidentFilters = () => {
  const filters = ref<IncidentFilters>({})
  
  const applyFilters = (newFilters: Partial<IncidentFilters>) => {
    filters.value = { ...filters.value, ...newFilters }
  }
  
  return { filters: readonly(filters), applyFilters }
}
```

### Git Workflow

```bash
# Feature branch workflow
git checkout -b feature/incident-templates
git add .
git commit -m "feat: add incident templates functionality"
git push origin feature/incident-templates

# Commit message format
# type(scope): subject
# 
# Types: feat, fix, docs, style, refactor, test, chore
# Example: feat(incidents): add bulk update functionality
```

### Code Review Checklist

- [ ] Tests are included and passing
- [ ] Documentation is updated
- [ ] Security implications considered
- [ ] Performance impact assessed
- [ ] Database migrations are reversible
- [ ] API changes are backward compatible
- [ ] UI is responsive and accessible
- [ ] Error handling is comprehensive

### Documentation

Always update documentation when:
- Adding new API endpoints
- Changing database schema
- Adding new features
- Modifying deployment process

Use JSDoc/PHPDoc for code documentation:

```php
/**
 * Calculate SLA targets for an incident based on priority
 * 
 * @param Incident $incident The incident to calculate SLA for
 * @param array $rules Optional custom SLA rules
 * @return array{response_target: Carbon, resolution_target: Carbon}
 * @throws InvalidArgumentException If priority is not recognized
 */
public function calculateSLA(Incident $incident, array $rules = []): array
{
    // Implementation
}
```

## Resources

### Documentation
- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Documentation](https://vuejs.org/guide/)
- [TypeScript Handbook](https://www.typescriptlang.org/docs/)
- [Docker Documentation](https://docs.docker.com/)

### Tools
- [Laravel Debugbar](https://github.com/barryvdh/laravel-debugbar)
- [Vue DevTools](https://devtools.vuejs.org/)
- [Postman](https://www.postman.com/)
- [TablePlus](https://tableplus.com/)

### Community
- Project Slack: itsm-platform.slack.com
- GitHub Discussions: github.com/your-org/itsm-platform/discussions
- Stack Overflow: [itsm-platform] tag