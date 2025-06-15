# 🚀 Fase 2 - ITSM Platform: Correções + Auth0 + Incidents + Dashboard

## Contexto
Excelente trabalho na Fase 1! A infraestrutura base está rodando. Agora vamos corrigir os issues identificados e implementar a Fase 2 completa seguindo o arquivo claude.md.

## 🔧 Parte 1: Corrigir Issues da Fase 1

### 1.1 Corrigir APP_KEY Issue
```bash
# No backend/.env, adicione uma APP_KEY válida:
APP_KEY=base64:gZQJ92wJb5Q7X7kFt8XU0cq2BhNjzvMc8jrDkcJB5Qk=

# Ou gere uma nova:
docker-compose exec backend php artisan key:generate

# Ajuste o Dockerfile do backend para copiar o .env correto:
# backend/Dockerfile
COPY .env.example .env
RUN php artisan key:generate
```

### 1.2 Corrigir Queue Worker
Ajuste o docker-compose.yml para o queue worker compartilhar o vendor:
```yaml
queue:
  build:
    context: ./backend
    dockerfile: Dockerfile
  container_name: itsm-queue
  restart: unless-stopped
  working_dir: /var/www/html
  volumes:
    - ./backend:/var/www/html
    - backend_vendor:/var/www/html/vendor  # Compartilha vendor
  depends_on:
    backend:
      condition: service_healthy
  command: php artisan queue:work --sleep=3 --tries=3
```

### 1.3 Adicionar comandos ao Makefile
```makefile
# Comandos para fixes
fix-permissions:
	docker-compose exec backend chown -R www-data:www-data storage bootstrap/cache
	docker-compose exec backend chmod -R 775 storage bootstrap/cache

key-generate:
	docker-compose exec backend php artisan key:generate

clear-all:
	docker-compose exec backend php artisan cache:clear
	docker-compose exec backend php artisan config:clear
	docker-compose exec backend php artisan route:clear
	docker-compose exec backend php artisan view:clear

verify-setup:
	@echo "🔍 Verificando setup..."
	@curl -s http://localhost:8000/health | jq '.' || echo "❌ Health check failed"
	@docker-compose ps
```

## 🔐 Parte 2: Implementar Auth0

### 2.1 Configuração Backend Auth0

#### Arquivo: `backend/config/auth0.php`
```php
<?php

return [
    'domain' => env('AUTH0_DOMAIN'),
    'client_id' => env('AUTH0_CLIENT_ID'),
    'client_secret' => env('AUTH0_CLIENT_SECRET'),
    'audience' => env('AUTH0_AUDIENCE', 'https://api.itsm-platform.com'),
    'scope' => env('AUTH0_SCOPE', 'openid profile email'),
    'cookie_secret' => env('AUTH0_COOKIE_SECRET', env('APP_KEY')),
    'redirect_uri' => env('AUTH0_REDIRECT_URI', env('APP_URL') . '/auth/callback'),
    
    // Custom claims for multi-tenancy
    'custom_claims' => [
        'tenant_id' => 'https://itsm-platform.com/tenant_id',
        'roles' => 'https://itsm-platform.com/roles',
    ],
];
```

#### Arquivo: `backend/app/Http/Middleware/Auth0Middleware.php`
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class Auth0Middleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['error' => 'No token provided'], 401);
        }
        
        try {
            // Decode and verify JWT
            $decoded = $this->verifyToken($token);
            
            // Extract tenant from token
            $tenantId = $decoded->{config('auth0.custom_claims.tenant_id')} ?? null;
            
            if (!$tenantId) {
                return response()->json(['error' => 'No tenant specified'], 403);
            }
            
            // Set current tenant
            $tenant = Tenant::findOrFail($tenantId);
            app()->instance('current_tenant', $tenant);
            
            // Set tenant in database session
            \DB::statement("SET SESSION app.current_tenant_id = ?", [$tenantId]);
            
            // Find or create user
            $user = User::firstOrCreate(
                ['auth0_id' => $decoded->sub],
                [
                    'email' => $decoded->email,
                    'name' => $decoded->name ?? $decoded->email,
                    'tenant_id' => $tenantId,
                ]
            );
            
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token: ' . $e->getMessage()], 401);
        }
        
        return $next($request);
    }
    
    private function verifyToken($token)
    {
        $jwks = Cache::remember('auth0_jwks', 3600, function () {
            $response = \Http::get('https://' . config('auth0.domain') . '/.well-known/jwks.json');
            return $response->json();
        });
        
        $keys = JWK::parseKeySet($jwks);
        
        return JWT::decode($token, $keys);
    }
}
```

#### Arquivo: `backend/routes/api/v1.php`
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\IncidentController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Middleware\Auth0Middleware;

Route::prefix('v1')->middleware(['api', Auth0Middleware::class])->group(function () {
    // User info
    Route::get('/me', function () {
        return response()->json([
            'user' => auth()->user(),
            'tenant' => app('current_tenant'),
        ]);
    });
    
    // Incidents
    Route::apiResource('incidents', IncidentController::class);
    
    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/metrics', [DashboardController::class, 'metrics']);
        Route::get('/incidents-by-status', [DashboardController::class, 'incidentsByStatus']);
        Route::get('/incidents-by-priority', [DashboardController::class, 'incidentsByPriority']);
        Route::get('/recent-activity', [DashboardController::class, 'recentActivity']);
    });
});
```

## 🎫 Parte 3: Módulo de Incidents (DDD)

### 3.1 Model com Multi-tenancy

#### Arquivo: `backend/app/Domains/Incident/Models/Incident.php`
```php
<?php

namespace App\Domains\Incident\Models;

use App\Core\Models\BaseModel;
use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Incident extends BaseModel
{
    use HasFactory, SoftDeletes, BelongsToTenant, HasAuditLog;
    
    protected $fillable = [
        'tenant_id',
        'number',
        'title',
        'description',
        'priority',
        'impact',
        'urgency',
        'status',
        'category_id',
        'assigned_to',
        'created_by',
        'resolved_at',
        'closed_at',
        'sla_response_target',
        'sla_resolution_target',
    ];
    
    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'sla_response_target' => 'datetime',
        'sla_resolution_target' => 'datetime',
    ];
    
    protected static function booted()
    {
        static::creating(function ($incident) {
            // Generate incident number
            $incident->number = static::generateNumber();
            
            // Set created_by
            $incident->created_by = auth()->id();
            
            // Calculate SLA targets
            $incident->calculateSlaTargets();
        });
    }
    
    public static function generateNumber(): string
    {
        $tenant = app('current_tenant');
        $year = date('Y');
        
        $lastIncident = static::where('tenant_id', $tenant->id)
            ->whereYear('created_at', $year)
            ->orderBy('number', 'desc')
            ->first();
        
        $sequence = 1;
        if ($lastIncident) {
            preg_match('/INC' . $year . '(\d+)/', $lastIncident->number, $matches);
            $sequence = isset($matches[1]) ? ((int) $matches[1]) + 1 : 1;
        }
        
        return sprintf('INC%s%06d', $year, $sequence);
    }
    
    public function calculateSlaTargets(): void
    {
        $priorityHours = [
            'critical' => ['response' => 1, 'resolution' => 4],
            'high' => ['response' => 2, 'resolution' => 8],
            'medium' => ['response' => 4, 'resolution' => 24],
            'low' => ['response' => 8, 'resolution' => 48],
        ];
        
        $hours = $priorityHours[$this->priority] ?? $priorityHours['medium'];
        
        $this->sla_response_target = now()->addHours($hours['response']);
        $this->sla_resolution_target = now()->addHours($hours['resolution']);
    }
    
    // Relationships
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    // Scopes
    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', ['resolved', 'closed']);
    }
    
    public function scopeOverdue($query)
    {
        return $query->where('sla_resolution_target', '<', now())
            ->whereNull('resolved_at');
    }
}
```

### 3.2 Action Classes (DDD Pattern)

#### Arquivo: `backend/app/Domains/Incident/Actions/CreateIncidentAction.php`
```php
<?php

namespace App\Domains\Incident\Actions;

use App\Core\Actions\Action;
use App\Domains\Incident\DTOs\CreateIncidentData;
use App\Domains\Incident\Models\Incident;
use App\Domains\Incident\Events\IncidentCreated;
use Illuminate\Support\Facades\DB;

class CreateIncidentAction extends Action
{
    public function execute(CreateIncidentData $data): Incident
    {
        return DB::transaction(function () use ($data) {
            $incident = Incident::create([
                'tenant_id' => app('current_tenant')->id,
                'title' => $data->title,
                'description' => $data->description,
                'priority' => $data->priority,
                'impact' => $data->impact,
                'urgency' => $data->urgency,
                'status' => 'new',
                'category_id' => $data->category_id,
                'assigned_to' => $data->assigned_to,
            ]);
            
            // Log activity
            activity()
                ->performedOn($incident)
                ->causedBy(auth()->user())
                ->withProperties(['action' => 'created'])
                ->log('Incident created');
            
            // Dispatch event
            event(new IncidentCreated($incident));
            
            return $incident;
        });
    }
}
```

#### Arquivo: `backend/app/Domains/Incident/DTOs/CreateIncidentData.php`
```php
<?php

namespace App\Domains\Incident\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Max;

class CreateIncidentData extends Data
{
    public function __construct(
        #[Required, Max(255)]
        public string $title,
        
        #[Required]
        public string $description,
        
        #[Required, In(['critical', 'high', 'medium', 'low'])]
        public string $priority,
        
        #[Required, In(['critical', 'high', 'medium', 'low'])]
        public string $impact,
        
        #[Required, In(['critical', 'high', 'medium', 'low'])]
        public string $urgency,
        
        public ?string $category_id = null,
        public ?string $assigned_to = null,
    ) {}
}
```

### 3.3 Controller

#### Arquivo: `backend/app/Http/Controllers/Api/V1/IncidentController.php`
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Domains\Incident\Models\Incident;
use App\Domains\Incident\Actions\CreateIncidentAction;
use App\Domains\Incident\Actions\UpdateIncidentAction;
use App\Domains\Incident\DTOs\CreateIncidentData;
use App\Domains\Incident\DTOs\UpdateIncidentData;
use App\Http\Resources\IncidentResource;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        $incidents = QueryBuilder::for(Incident::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('priority'),
                AllowedFilter::exact('assigned_to'),
                AllowedFilter::scope('open'),
                AllowedFilter::scope('overdue'),
            ])
            ->allowedSorts(['created_at', 'priority', 'sla_resolution_target'])
            ->allowedIncludes(['assignedUser', 'category'])
            ->paginate($request->get('per_page', 20));
        
        return IncidentResource::collection($incidents);
    }
    
    public function store(Request $request, CreateIncidentAction $action)
    {
        $data = CreateIncidentData::from($request->all());
        $incident = $action->execute($data);
        
        return new IncidentResource($incident->load(['assignedUser', 'category']));
    }
    
    public function show(Incident $incident)
    {
        $this->authorize('view', $incident);
        
        return new IncidentResource(
            $incident->load(['assignedUser', 'createdByUser', 'category'])
        );
    }
    
    public function update(Request $request, Incident $incident, UpdateIncidentAction $action)
    {
        $this->authorize('update', $incident);
        
        $data = UpdateIncidentData::from($request->all());
        $incident = $action->execute($incident, $data);
        
        return new IncidentResource($incident);
    }
    
    public function destroy(Incident $incident)
    {
        $this->authorize('delete', $incident);
        
        $incident->delete();
        
        activity()
            ->performedOn($incident)
            ->causedBy(auth()->user())
            ->withProperties(['action' => 'deleted'])
            ->log('Incident deleted');
        
        return response()->noContent();
    }
}
```

### 3.4 Migration

#### Arquivo: `backend/database/migrations/2024_01_01_000003_create_incidents_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('number', 20)->unique();
            $table->string('title');
            $table->text('description');
            $table->enum('priority', ['critical', 'high', 'medium', 'low']);
            $table->enum('impact', ['critical', 'high', 'medium', 'low']);
            $table->enum('urgency', ['critical', 'high', 'medium', 'low']);
            $table->enum('status', ['new', 'assigned', 'in_progress', 'pending', 'resolved', 'closed'])
                  ->default('new');
            $table->uuid('category_id')->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->uuid('created_by');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('sla_response_target')->nullable();
            $table->timestamp('sla_resolution_target')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'priority']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('sla_resolution_target');
            
            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('assigned_to')->references('id')->on('users');
        });
        
        // Enable RLS
        DB::statement('ALTER TABLE incidents ENABLE ROW LEVEL SECURITY');
        
        // Create RLS policy
        DB::statement("
            CREATE POLICY tenant_isolation_policy ON incidents
            FOR ALL TO application_role
            USING (tenant_id = current_setting('app.current_tenant_id')::uuid)
        ");
    }
    
    public function down()
    {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation_policy ON incidents');
        Schema::dropIfExists('incidents');
    }
};
```

## 📊 Parte 4: Dashboard e Métricas

### 4.1 Dashboard Controller

#### Arquivo: `backend/app/Http/Controllers/Api/V1/DashboardController.php`
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Domains\Incident\Models\Incident;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function metrics()
    {
        $tenantId = app('current_tenant')->id;
        $cacheKey = "dashboard_metrics_{$tenantId}";
        
        return Cache::remember($cacheKey, 300, function () {
            return [
                'total_incidents' => Incident::count(),
                'open_incidents' => Incident::open()->count(),
                'overdue_incidents' => Incident::overdue()->count(),
                'avg_resolution_time' => $this->getAverageResolutionTime(),
                'sla_compliance' => $this->getSlaCompliance(),
                'incidents_today' => Incident::whereDate('created_at', today())->count(),
            ];
        });
    }
    
    public function incidentsByStatus()
    {
        return Incident::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn($item) => [$item->status => $item->count]);
    }
    
    public function incidentsByPriority()
    {
        return Incident::select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->get();
    }
    
    public function recentActivity()
    {
        return Incident::with(['assignedUser', 'createdByUser'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($incident) {
                return [
                    'id' => $incident->id,
                    'number' => $incident->number,
                    'title' => $incident->title,
                    'status' => $incident->status,
                    'priority' => $incident->priority,
                    'created_at' => $incident->created_at,
                    'created_by' => $incident->createdByUser?->name,
                ];
            });
    }
    
    private function getAverageResolutionTime()
    {
        $avg = Incident::whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
            ->value('avg_hours');
        
        return round($avg ?? 0, 1);
    }
    
    private function getSlaCompliance()
    {
        $total = Incident::whereNotNull('resolved_at')->count();
        
        if ($total === 0) return 100;
        
        $onTime = Incident::whereNotNull('resolved_at')
            ->whereColumn('resolved_at', '<=', 'sla_resolution_target')
            ->count();
        
        return round(($onTime / $total) * 100, 1);
    }
}
```

## 🧪 Parte 5: Testes

### 5.1 Teste de Incident com Multi-tenancy

#### Arquivo: `backend/tests/Feature/Incidents/CreateIncidentTest.php`
```php
<?php

namespace Tests\Feature\Incidents;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Domains\Incident\Models\Incident;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateIncidentTest extends TestCase
{
    use RefreshDatabase;
    
    private $tenant1;
    private $tenant2;
    private $user1;
    private $user2;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create tenants
        $this->tenant1 = Tenant::factory()->create();
        $this->tenant2 = Tenant::factory()->create();
        
        // Create users
        $this->user1 = User::factory()->create(['tenant_id' => $this->tenant1->id]);
        $this->user2 = User::factory()->create(['tenant_id' => $this->tenant2->id]);
    }
    
    /** @test */
    public function user_can_create_incident_in_their_tenant()
    {
        $this->actingAs($this->user1);
        app()->instance('current_tenant', $this->tenant1);
        
        $response = $this->postJson('/api/v1/incidents', [
            'title' => 'Test Incident',
            'description' => 'Test Description',
            'priority' => 'high',
            'impact' => 'medium',
            'urgency' => 'high',
        ]);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'number',
                    'title',
                    'status',
                    'sla_response_target',
                    'sla_resolution_target',
                ]
            ]);
        
        $this->assertDatabaseHas('incidents', [
            'title' => 'Test Incident',
            'tenant_id' => $this->tenant1->id,
        ]);
        
        // Verify number format
        $this->assertMatchesRegularExpression(
            '/^INC\d{4}\d{6}$/',
            $response->json('data.number')
        );
    }
    
    /** @test */
    public function user_cannot_see_incidents_from_other_tenants()
    {
        // Create incident for tenant1
        Incident::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'title' => 'Tenant 1 Incident',
        ]);
        
        // Create incident for tenant2
        Incident::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'title' => 'Tenant 2 Incident',
        ]);
        
        // Act as user from tenant1
        $this->actingAs($this->user1);
        app()->instance('current_tenant', $this->tenant1);
        DB::statement("SET SESSION app.current_tenant_id = ?", [$this->tenant1->id]);
        
        $response = $this->getJson('/api/v1/incidents');
        
        $response->assertStatus(200);
        
        // Should only see tenant1's incident
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Tenant 1 Incident', $response->json('data.0.title'));
    }
    
    /** @test */
    public function sla_targets_are_calculated_based_on_priority()
    {
        $this->actingAs($this->user1);
        app()->instance('current_tenant', $this->tenant1);
        
        $response = $this->postJson('/api/v1/incidents', [
            'title' => 'Critical Incident',
            'description' => 'Test',
            'priority' => 'critical',
            'impact' => 'critical',
            'urgency' => 'critical',
        ]);
        
        $response->assertStatus(201);
        
        $incident = Incident::find($response->json('data.id'));
        
        // Critical = 1 hour response, 4 hour resolution
        $this->assertEquals(
            now()->addHours(1)->format('Y-m-d H'),
            $incident->sla_response_target->format('Y-m-d H')
        );
        
        $this->assertEquals(
            now()->addHours(4)->format('Y-m-d H'),
            $incident->sla_resolution_target->format('Y-m-d H')
        );
    }
}
```

## ✅ Verificação e Próximos Passos

### Comandos de Verificação:
```bash
# 1. Fix permissions e clear cache
make fix-permissions
make clear-all

# 2. Run migrations
docker-compose exec backend php artisan migrate

# 3. Run tests
docker-compose exec backend php artisan test

# 4. Verify endpoints
# Get health
curl http://localhost:8000/health

# Create test incident (need real Auth0 token)
curl -X POST http://localhost:8000/api/v1/incidents \
  -H "Authorization: Bearer YOUR_AUTH0_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Incident",
    "description": "Testing the system",
    "priority": "high",
    "impact": "medium",
    "urgency": "high"
  }'

# Get dashboard metrics
curl http://localhost:8000/api/v1/dashboard/metrics \
  -H "Authorization: Bearer YOUR_AUTH0_TOKEN"
```

### Checklist Final:
- [ ] APP_KEY configurada e funcionando
- [ ] Queue worker rodando sem erros
- [ ] Auth0 middleware funcionando
- [ ] Multi-tenancy com RLS ativo
- [ ] Incidents CRUD completo
- [ ] Dashboard com métricas
- [ ] Testes passando
- [ ] Tenant isolation verificado

### Próxima Fase (3):
1. Frontend Vue.js com Auth0
2. UI para Incidents
3. Dashboard visual
4. Integração com Claude AI
5. WebSockets para real-time

Lembre-se: Este é um projeto ENTERPRISE competindo com ServiceNow. Mantenha a qualidade em todos os aspectos!