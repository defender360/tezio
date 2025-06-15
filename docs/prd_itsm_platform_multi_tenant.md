# 🚀 PRD Enterprise - ITSM Platform Multi-Tenant com ITIL
## Product Requirements Document - Nível ServiceNow/Jira Service Management

### 📋 Sumário Executivo

**Produto**: Enterprise ITSM Platform - Plataforma de Gestão de Serviços de TI baseada em ITIL v4  
**Versão**: 1.0 MVP Enterprise  
**Data**: Junho 2025  
**Stack**: Laravel 11 + FastAPI + Vue.js 3 + PostgreSQL + Redis  
**Framework**: ITIL v4 (Information Technology Infrastructure Library)  
**Deploy**: Railway (Multi-service Architecture)  
**Metodologia**: Agile com sprints de 2 semanas  

---

## 1. 🎯 Visão Estratégica e Problema de Negócio

### 1.1 O Problema Real do ITSM Enterprise
Departamentos de TI e MSPs enfrentam desafios críticos na **Gestão de Serviços**:
- **Fragmentação de Processos ITIL**: Incidentes, requisições e mudanças gerenciados em sistemas separados
- **Falta de Catálogo de Serviços**: Usuários não sabem o que podem solicitar
- **SLAs Não Rastreados**: Violações constantes sem visibilidade
- **Ausência de CMDB**: Sem visão dos ativos e suas dependências
- **Processos Reativos**: Apenas apagam incêndios, sem análise de causa raiz
- **Custo Proibitivo**: ServiceNow custa $100-300/agente/mês

### 1.2 Nossa Solução ITSM Enterprise
Plataforma ITSM completa com:
- **Framework ITIL v4 Nativo**: Processos de Incidentes, Requisições, Problemas e Mudanças
- **Catálogo de Serviços**: Portal self-service proativo
- **Gestão de SLA Avançada**: Monitoramento em tempo real com pausas inteligentes
- **CMDB Integrado**: Visão completa de ativos e dependências
- **IA para Causa Raiz**: Claude AI analisa padrões e sugere soluções
- **Preço Justo**: $25-75/agente/mês com mais valor

### 1.3 Diferenciação Estratégica
```yaml
ServiceNow:
  Força: Completude e enterprise-grade
  Fraqueza: Complexidade e custo altíssimo
  
Jira Service Management:
  Força: Flexibilidade e integração Atlassian
  Fraqueza: Não é ITIL-first, precisa customização
  
Nossa Plataforma:
  Força: ITIL nativo + IA + preço justo + deploy rápido
  Target: MSPs e TI médio porte que querem enterprise features sem complexity
```

---

## 2. 🏗️ Arquitetura Enterprise ITSM

### 2.1 Estrutura Orientada a ITIL
```
itsm-enterprise-platform/
├── frontend/                    # Vue.js 3 + TypeScript
│   ├── src/
│   │   ├── modules/
│   │   │   ├── incident-management/     # Gestão de Incidentes
│   │   │   ├── service-request/         # Requisições de Serviço
│   │   │   ├── problem-management/      # Gestão de Problemas
│   │   │   ├── change-management/       # Gestão de Mudanças
│   │   │   ├── service-catalog/         # Catálogo de Serviços
│   │   │   ├── cmdb/                   # Configuration Management
│   │   │   ├── sla-management/         # Gestão de SLAs
│   │   │   └── knowledge-base/         # Base de Conhecimento
│   │   ├── shared/
│   │   │   ├── components/             # Componentes compartilhados
│   │   │   ├── composables/            # Business logic hooks
│   │   │   └── services/               # API clients
│   │   └── core/
│   │       ├── auth/                   # Autenticação
│   │       ├── tenant/                 # Multi-tenancy
│   │       └── workflow/               # Engine de workflow
│   └── tests/
│
├── backend/                     # Laravel 11
│   ├── app/
│   │   ├── Domains/            # Domain-Driven Design
│   │   │   ├── Incident/
│   │   │   │   ├── Models/
│   │   │   │   ├── Services/
│   │   │   │   ├── Repositories/
│   │   │   │   └── Actions/
│   │   │   ├── ServiceRequest/
│   │   │   ├── Problem/
│   │   │   ├── Change/
│   │   │   ├── ServiceCatalog/
│   │   │   ├── CMDB/
│   │   │   └── SLA/
│   │   ├── Core/
│   │   │   ├── Tenant/
│   │   │   ├── Workflow/
│   │   │   └── Notification/
│   │   └── Shared/
│   ├── database/
│   │   ├── migrations/
│   │   │   ├── core/          # Tabelas base
│   │   │   ├── itil/          # Processos ITIL
│   │   │   └── cmdb/          # Configuration items
│   └── tests/
│
├── ai-service/                 # FastAPI Python
│   ├── app/
│   │   ├── itil/
│   │   │   ├── incident_analyzer/      # Análise de incidentes
│   │   │   ├── problem_rca/            # Root Cause Analysis
│   │   │   ├── change_risk/            # Análise de risco
│   │   │   └── knowledge_extractor/    # Extração de conhecimento
│   │   ├── ml/
│   │   │   ├── classification/         # Classificação ITIL
│   │   │   ├── prediction/             # Predição de SLA
│   │   │   └── pattern_detection/      # Detecção de padrões
│   │   └── integrations/
│   │       └── claude_ai/              # Claude AI integration
│   └── tests/
│
└── infrastructure/
    ├── itil-processes/         # Documentação de processos
    ├── sla-templates/          # Templates de SLA
    └── cmdb-schema/           # Schema do CMDB
```

### 2.2 Modelo de Dados ITIL-Compliant

```sql
-- Core ITIL Tables
CREATE TABLE incidents (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    number VARCHAR(20) UNIQUE NOT NULL, -- INC0001234
    title VARCHAR(255) NOT NULL,
    description TEXT,
    impact ENUM('1-Critical', '2-High', '3-Medium', '4-Low'),
    urgency ENUM('1-Critical', '2-High', '3-Medium', '4-Low'),
    priority INT GENERATED ALWAYS AS (impact_value * urgency_value),
    category_id UUID REFERENCES categories(id),
    affected_ci_id UUID REFERENCES configuration_items(id),
    assigned_team_id UUID REFERENCES teams(id),
    assigned_user_id UUID REFERENCES users(id),
    status ENUM('New', 'Assigned', 'In Progress', 'Pending Customer', 'Resolved', 'Closed'),
    resolution TEXT,
    created_at TIMESTAMP,
    resolved_at TIMESTAMP,
    closed_at TIMESTAMP,
    sla_response_target TIMESTAMP,
    sla_resolution_target TIMESTAMP,
    sla_paused_at TIMESTAMP,
    sla_pause_reason TEXT
);

CREATE TABLE service_requests (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    number VARCHAR(20) UNIQUE NOT NULL, -- REQ0001234
    catalog_item_id UUID REFERENCES service_catalog_items(id),
    requested_for_user_id UUID REFERENCES users(id),
    fulfillment_team_id UUID REFERENCES teams(id),
    status ENUM('Submitted', 'Approved', 'In Fulfillment', 'Completed', 'Cancelled'),
    approval_status ENUM('Not Required', 'Pending', 'Approved', 'Rejected'),
    variables JSONB, -- Dynamic form data
    sla_target TIMESTAMP
);

CREATE TABLE problems (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    number VARCHAR(20) UNIQUE NOT NULL, -- PRB0001234
    title VARCHAR(255) NOT NULL,
    root_cause TEXT,
    workaround TEXT,
    permanent_fix TEXT,
    related_incidents JSONB, -- Array of incident IDs
    affected_cis JSONB, -- Array of CI IDs
    status ENUM('New', 'Investigating', 'Root Cause Identified', 'Fix In Progress', 'Resolved', 'Closed')
);

CREATE TABLE changes (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    number VARCHAR(20) UNIQUE NOT NULL, -- CHG0001234
    type ENUM('Standard', 'Normal', 'Emergency'),
    risk_level ENUM('Low', 'Medium', 'High', 'Critical'),
    implementation_plan TEXT,
    rollback_plan TEXT,
    affected_cis JSONB,
    cab_approval_status ENUM('Not Required', 'Pending', 'Approved', 'Rejected'),
    scheduled_start TIMESTAMP,
    scheduled_end TIMESTAMP
);

-- CMDB Tables
CREATE TABLE configuration_items (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    ci_type ENUM('Hardware', 'Software', 'Service', 'Document'),
    status ENUM('Active', 'Inactive', 'Retired'),
    owner_id UUID REFERENCES users(id),
    location VARCHAR(255),
    attributes JSONB, -- Flexible attributes based on CI type
    relationships JSONB -- Related CIs and relationship types
);

-- Service Catalog
CREATE TABLE service_catalog_items (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id UUID REFERENCES categories(id),
    fulfillment_team_id UUID REFERENCES teams(id),
    sla_hours INT DEFAULT 24,
    approval_required BOOLEAN DEFAULT false,
    form_schema JSONB, -- Dynamic form definition
    automation_script TEXT, -- Optional automation
    is_active BOOLEAN DEFAULT true
);

-- SLA Management
CREATE TABLE sla_policies (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('Incident', 'Service Request', 'Change'),
    conditions JSONB, -- Conditions for SLA application
    response_time_hours INT,
    resolution_time_hours INT,
    business_hours_only BOOLEAN DEFAULT true,
    pause_conditions JSONB -- When to pause SLA
);
```

---

## 3. 📦 MVP Enterprise - Fase 1 (3 Meses)

### 3.1 Sprint Planning com Foco ITIL

#### **Sprint 0: Foundation & ITIL Setup (1 semana)**
```yaml
Objetivo: Estabelecer base ITIL-compliant

Tasks:
  - Setup estrutura de projetos orientada a domínios
  - Configurar Railway com serviços ITIL
  - Criar schema base ITIL no PostgreSQL
  - Setup processos ITIL documentados
  - Configurar numeração automática (INC/REQ/PRB/CHG)
  
Claude Code Commands:
  - claude-code scaffold itil-platform --domains
  - claude-code generate itil-schema --all-processes
  - claude-code setup numbering --pattern="INC{YYYY}{MM}{0000}"
```

#### **Sprints 1-2: Core ITIL + Multi-tenancy**
```yaml
Objetivo: Base multi-tenant com processos ITIL

User Stories:
  - Como admin, posso configurar processos ITIL para meu tenant
  - Como agente, vejo apenas incidentes e requisições do meu tenant
  - Como gestor, defino SLAs por tipo de ticket

Technical Tasks:
  ITIL Core:
    - Implementar modelos Incident e ServiceRequest
    - Criar diferenciação clara entre tipos
    - Setup cálculo de prioridade (Impact x Urgency)
    - Implementar pausas de SLA
    
  Multi-tenancy:
    - Tenant isolation em todos os processos
    - Configurações ITIL por tenant
    - Templates de SLA por tenant

Claude Code:
  - claude-code generate itil-models --incident --service-request
  - claude-code implement sla-engine --with-pauses
  - claude-code generate workflow incident-lifecycle --itil-compliant
```

#### **Sprints 3-4: Gestão de Incidentes & Requisições**
```yaml
Objetivo: Processos ITIL funcionais com SLA

User Stories:
  - Como usuário, reporto incidentes com impacto/urgência
  - Como agente, gerencio incidentes seguindo workflow ITIL
  - Como gestor, monitoro SLAs em tempo real
  - Como admin, configuro catálogo de serviços básico

Features - Incident Management:
  - Criação com categorização ITIL
  - Cálculo automático de prioridade
  - Workflow com "Pending Customer" (pausa SLA)
  - Atribuição para times, não apenas indivíduos
  - Relacionamento com CIs (básico)
  - Templates de resolução

Features - Service Request:
  - Catálogo de serviços (5 itens iniciais)
  - Formulários dinâmicos por serviço
  - Aprovação simples (se necessário)
  - Fulfillment tracking
  - SLA específico por serviço

Claude Code:
  - claude-code generate incident-management --full-itil
  - claude-code generate service-catalog --items=5
  - claude-code implement sla-tracking --real-time
  - claude-code generate team-assignment --with-queue
```

#### **Sprints 5-6: Dashboard ITIL & CMDB Básico**
```yaml
Objetivo: Visibilidade operacional e gestão de ativos

User Stories:
  - Como gestor, vejo métricas ITIL em dashboards
  - Como admin, cadastro CIs básicos
  - Como agente, associo incidentes a CIs
  - Como todos, vejo relatórios de performance ITIL

Features - Dashboards:
  - Widget de SLA Status (violações, em risco)
  - Métricas ITIL (MTTR, volume por categoria)
  - Performance de times
  - Distribuição Impact x Urgency
  - Tendências de incidentes

Features - CMDB Básico:
  - CRUD de Configuration Items
  - Tipos: Hardware, Software, Service
  - Relacionamento CI x Incident
  - Importação básica (CSV)

Claude Code:
  - claude-code generate dashboard itil-operations
  - claude-code generate cmdb --basic
  - claude-code implement ci-incident-relationship
  - claude-code generate reports --itil-standard
```

### 3.2 Definição de "Done" - Padrão Enterprise

#### **Critérios ITIL de Aceitação**
- [ ] Processos seguem ITIL v4 best practices
- [ ] Numeração automática funcionando (INC/REQ)
- [ ] SLAs calculados corretamente com pausas
- [ ] Segregação total por tenant
- [ ] Auditoria completa de mudanças
- [ ] Relatórios ITIL standard disponíveis

#### **Features Completas MVP Enterprise**
1. **Incident Management**: 
   - Lifecycle completo com SLA
   - Categorização e priorização ITIL
   - Atribuição para times
   - Estados incluindo "Pending Customer"

2. **Service Request Management**:
   - Catálogo com 5 serviços configuráveis
   - Formulários dinâmicos
   - Tracking de fulfillment
   - SLA por serviço

3. **SLA Management**:
   - Políticas configuráveis por tenant
   - Cálculo em tempo real
   - Pausas automáticas
   - Alertas de violação

4. **CMDB Básico**:
   - Gestão de CIs principais
   - Relacionamento com incidentes
   - Visão de impacto básica

5. **Dashboards & Reports**:
   - Métricas ITIL standard
   - Performance de SLA
   - Análise de tendências
   - Exportação de relatórios

---

## 4. 🤖 Implementação ITIL com Claude Code

### 4.1 Comandos Específicos ITIL

#### **Setup Inicial ITIL**
```bash
# Criar estrutura ITIL completa
claude-code create itil-platform --framework=itilv4

# Gerar domínios ITIL
claude-code generate domain Incident --itil-process
claude-code generate domain ServiceRequest --itil-process
claude-code generate domain Problem --itil-process
claude-code generate domain Change --itil-process

# Configurar numeração ITIL
claude-code configure numbering --patterns="INC,REQ,PRB,CHG"
```

#### **Incident Management Implementation**
```bash
# Models e estrutura
claude-code generate model Incident --itil-fields="impact,urgency,priority"
claude-code generate model IncidentCategory --hierarchical
claude-code generate model Team --with-queue

# Business logic
claude-code generate service IncidentService --itil-workflow
claude-code implement priority-matrix --impact-urgency
claude-code generate workflow incident --states="New,Assigned,InProgress,PendingCustomer,Resolved,Closed"

# SLA específico
claude-code generate sla-policy IncidentSLA --pausable
claude-code implement sla-calculation --business-hours

# Componentes frontend
claude-code generate view IncidentManagement --itil-layout
claude-code generate component IncidentForm --impact-urgency-matrix
claude-code generate component SLAIndicator --real-time
```

#### **Service Catalog Implementation**
```bash
# Catálogo de serviços
claude-code generate model ServiceCatalogItem --dynamic-forms
claude-code generate model ServiceCategory --tree-structure

# Serviços iniciais
claude-code seed service-catalog --items="PasswordReset,SoftwareRequest,HardwareRequest,AccessRequest,NewUserOnboarding"

# Portal de serviços
claude-code generate view ServicePortal --catalog-layout
claude-code generate component ServiceRequestForm --dynamic
claude-code generate workflow service-request --with-approval

# Automação
claude-code implement automation PasswordReset --self-service
```

#### **CMDB Implementation**
```bash
# Configuration Items
claude-code generate model ConfigurationItem --flexible-attributes
claude-code generate model CIRelationship --types="DependsOn,UsedBy,Contains"

# Importação e discovery
claude-code implement import-ci --from-csv
claude-code generate job DiscoverCIs --scheduled

# Visualização
claude-code generate component CIExplorer --tree-view
claude-code generate component ImpactAnalysis --visual
```

### 4.2 Padrões de Código ITIL

#### **Laravel - Domain-Driven Design para ITIL**
```php
// Domain/Incident/Models/Incident.php
namespace App\Domains\Incident\Models;

class Incident extends Model
{
    use HasTenant, HasSLA, HasWorkflow;
    
    protected $fillable = [
        'title', 'description', 'impact', 'urgency',
        'category_id', 'affected_ci_id', 'assigned_team_id'
    ];
    
    protected $casts = [
        'impact' => ImpactEnum::class,
        'urgency' => UrgencyEnum::class,
        'status' => IncidentStatusEnum::class,
    ];
    
    protected static function booted()
    {
        static::creating(function ($incident) {
            $incident->number = IncidentNumberGenerator::next();
            $incident->calculatePriority();
            $incident->setSLATargets();
        });
    }
    
    public function calculatePriority(): void
    {
        $matrix = [
            '1-Critical' => ['1-Critical' => 1, '2-High' => 2, '3-Medium' => 3, '4-Low' => 4],
            '2-High'     => ['1-Critical' => 2, '2-High' => 3, '3-Medium' => 4, '4-Low' => 5],
            '3-Medium'   => ['1-Critical' => 3, '2-High' => 4, '3-Medium' => 5, '4-Low' => 5],
            '4-Low'      => ['1-Critical' => 4, '2-High' => 5, '3-Medium' => 5, '4-Low' => 5],
        ];
        
        $this->priority = $matrix[$this->impact->value][$this->urgency->value];
    }
}

// Domain/Incident/Services/IncidentService.php
namespace App\Domains\Incident\Services;

class IncidentService
{
    public function __construct(
        private IncidentRepository $repository,
        private SLAService $slaService,
        private NotificationService $notifications,
        private WorkflowEngine $workflow
    ) {}
    
    public function createIncident(array $data): Incident
    {
        return DB::transaction(function () use ($data) {
            // Create incident
            $incident = $this->repository->create($data);
            
            // Apply SLA policy
            $this->slaService->applyPolicy($incident);
            
            // Start workflow
            $this->workflow->start($incident, IncidentWorkflow::class);
            
            // Notify stakeholders
            $this->notifications->incidentCreated($incident);
            
            // Check for major incident
            if ($incident->priority <= 2) {
                $this->escalateToMajorIncident($incident);
            }
            
            return $incident;
        });
    }
    
    public function transitionStatus(Incident $incident, string $newStatus): void
    {
        $oldStatus = $incident->status;
        
        // Validate transition
        if (!$this->workflow->canTransition($incident, $newStatus)) {
            throw new InvalidTransitionException();
        }
        
        // Handle SLA pauses
        if ($newStatus === IncidentStatus::PendingCustomer) {
            $this->slaService->pause($incident, 'Awaiting customer response');
        } elseif ($oldStatus === IncidentStatus::PendingCustomer) {
            $this->slaService->resume($incident);
        }
        
        // Execute transition
        $this->workflow->transition($incident, $newStatus);
    }
}
```

#### **Vue.js - Componentes ITIL**
```vue
<!-- IncidentDashboard.vue -->
<template>
  <div class="incident-dashboard">
    <div class="grid grid-cols-4 gap-4 mb-6">
      <SLAStatusWidget 
        :violations="slaViolations"
        :at-risk="slaAtRisk"
        @click="showSLADetails"
      />
      <IncidentVolumeWidget 
        :data="incidentVolume"
        :period="selectedPeriod"
      />
      <MTTRWidget 
        :current="mttr.current"
        :target="mttr.target"
        :trend="mttr.trend"
      />
      <TeamPerformanceWidget 
        :teams="teamPerformance"
      />
    </div>
    
    <div class="grid grid-cols-2 gap-6">
      <PriorityMatrixChart 
        :data="priorityDistribution"
        @cell-click="filterByPriority"
      />
      <IncidentTrendChart 
        :data="incidentTrend"
        :categories="categories"
      />
    </div>
    
    <IncidentQueue 
      v-model:filters="queueFilters"
      :incidents="filteredIncidents"
      :show-sla="true"
      @incident-select="openIncident"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useIncidentStore } from '@/stores/incident'
import { useSLATracking } from '@/composables/useSLATracking'
import { useITILMetrics } from '@/composables/useITILMetrics'

const incidentStore = useIncidentStore()
const { slaViolations, slaAtRisk, trackSLA } = useSLATracking()
const { mttr, teamPerformance, calculateMetrics } = useITILMetrics()

const selectedPeriod = ref<'day' | 'week' | 'month'>('week')
const queueFilters = ref<QueueFilters>({
  status: ['New', 'Assigned', 'InProgress'],
  assignedTeam: null,
  priority: null
})

const priorityDistribution = computed(() => {
  return incidentStore.incidents.reduce((acc, incident) => {
    const key = `${incident.impact}-${incident.urgency}`
    acc[key] = (acc[key] || 0) + 1
    return acc
  }, {})
})

onMounted(async () => {
  await incidentStore.loadIncidents()
  trackSLA(incidentStore.incidents)
  calculateMetrics(incidentStore.incidents)
})
</script>
```

#### **FastAPI - AI para ITIL**
```python
# app/itil/incident_analyzer.py
from typing import List, Dict
from app.ml.models import IncidentClassifier, RCAAnalyzer
from app.integrations.claude_ai import ClaudeAIClient

class ITILIncidentAnalyzer:
    def __init__(
        self,
        classifier: IncidentClassifier,
        rca_analyzer: RCAAnalyzer,
        claude_client: ClaudeAIClient
    ):
        self.classifier = classifier
        self.rca_analyzer = rca_analyzer
        self.claude_client = claude_client
    
    async def analyze_incident(self, incident_data: dict) -> IncidentAnalysis:
        # Classificação automática
        category = await self.classifier.predict_category(
            incident_data['description']
        )
        
        # Sugestão de prioridade baseada em ML
        suggested_priority = await self.classifier.predict_priority(
            text=incident_data['description'],
            historical_context=await self._get_historical_context(category)
        )
        
        # Busca de incidentes similares
        similar_incidents = await self._find_similar_incidents(
            incident_data['description']
        )
        
        # Análise de possível problema (para incidentes recorrentes)
        problem_likelihood = await self._analyze_problem_likelihood(
            category, similar_incidents
        )
        
        # Sugestões de resolução via Claude AI
        resolution_suggestions = await self.claude_client.suggest_resolutions(
            incident_description=incident_data['description'],
            category=category,
            similar_incidents=similar_incidents
        )
        
        return IncidentAnalysis(
            suggested_category=category,
            suggested_priority=suggested_priority,
            similar_incidents=similar_incidents,
            problem_likelihood=problem_likelihood,
            resolution_suggestions=resolution_suggestions,
            knowledge_articles=await self._find_relevant_kb_articles(category)
        )
    
    async def analyze_major_incident_pattern(
        self, 
        incidents: List[Dict]
    ) -> MajorIncidentAnalysis:
        """Detecta padrões que podem indicar um Major Incident"""
        
        # Análise de correlação temporal
        time_correlation = await self._analyze_time_correlation(incidents)
        
        # Análise de CIs afetados
        ci_impact = await self._analyze_ci_impact(incidents)
        
        # Detecção de padrão via ML
        pattern = await self.rca_analyzer.detect_pattern(incidents)
        
        # Análise profunda com Claude AI
        claude_analysis = await self.claude_client.analyze_incident_pattern(
            incidents=incidents,
            time_correlation=time_correlation,
            ci_impact=ci_impact
        )
        
        return MajorIncidentAnalysis(
            is_major_incident=claude_analysis.confidence > 0.8,
            affected_services=ci_impact.affected_services,
            root_cause_hypothesis=claude_analysis.root_cause,
            recommended_actions=claude_analysis.actions,
            estimated_impact=claude_analysis.business_impact
        )
```

---

## 5. 🧪 Estratégia de Testes ITIL

### 5.1 Testes de Processos ITIL

```bash
# Testes de Conformidade ITIL
claude-code test itil-compliance --all-processes

# Testes de SLA
claude-code test sla --scenarios="response,resolution,pause,resume"
claude-code test sla-calculation --business-hours --holidays

# Testes de Workflow
claude-code test workflow incident --all-transitions
claude-code test workflow service-request --with-approval

# Testes de Prioridade
claude-code test priority-matrix --all-combinations

# Testes de CMDB
claude-code test cmdb --relationships --impact-analysis
```

### 5.2 Cenários de Teste E2E ITIL

```bash
# Cenário: Incident Management Completo
claude-code test e2e incident-lifecycle \
  --steps="create,assign,investigate,pause-sla,resume,resolve,close" \
  --verify="sla-tracking,notifications,audit-trail"

# Cenário: Service Request com Aprovação
claude-code test e2e service-request-approval \
  --catalog-item="SoftwareRequest" \
  --approval-required=true \
  --verify="approval-workflow,fulfillment,sla"

# Cenário: Major Incident
claude-code test e2e major-incident \
  --trigger="multiple-related-incidents" \
  --verify="escalation,communication,war-room"
```

---

## 6. 📊 KPIs e Métricas ITIL

### 6.1 Métricas Operacionais ITIL

#### **Incident Management KPIs**
- **MTTR (Mean Time To Resolve)**: < 4 horas para P1, < 8 horas para P2
- **First Call Resolution Rate**: > 70%
- **SLA Compliance**: > 95%
- **Incident Recurrence Rate**: < 5%
- **Backlog Age**: Nenhum incidente > 30 dias

#### **Service Request KPIs**
- **Request Fulfillment Time**: Dentro do SLA em 98%
- **Self-Service Adoption**: > 60% das requisições
- **Approval Cycle Time**: < 2 horas
- **Customer Satisfaction**: > 4.5/5

#### **Problem Management KPIs**
- **Problems Identified**: > 10% dos incidentes recorrentes
- **Root Cause Found**: > 80% dos problemas
- **Preventive Actions**: 2 por problema resolvido

### 6.2 Dashboards ITIL Específicos

```yaml
Dashboard Operacional:
  - Widget: SLA Real-time Monitor
  - Widget: Incident Heat Map (por categoria/time)
  - Widget: Queue Health (aging, distribuição)
  - Widget: Team Workload Balance
  
Dashboard Gerencial:
  - Widget: MTTR Trend Analysis
  - Widget: SLA Compliance by Service
  - Widget: Cost per Incident/Request
  - Widget: Problem to Incident Ratio
  
Dashboard Executivo:
  - Widget: Service Availability
  - Widget: Business Impact Analysis
  - Widget: IT Service Performance
  - Widget: Continuous Improvement Metrics
```

---

## 7. 🚀 Roadmap ITIL Completo

### Phase 1: ITIL Foundation (Meses 1-3) ✅
- Incident Management completo
- Service Request com catálogo
- SLA Management avançado
- CMDB básico
- Dashboards operacionais

### Phase 2: ITIL Expansion (Meses 4-6)
```yaml
Problem Management:
  - Detecção automática de problemas
  - Root Cause Analysis com AI
  - Known Error Database
  - Workaround management

Change Management:
  - Change Advisory Board (CAB)
  - Risk assessment automatizado
  - Change calendar
  - Rollback procedures

Advanced CMDB:
  - Dependency mapping
  - Impact analysis visual
  - Auto-discovery
  - Integration with monitoring
```

### Phase 3: ITIL Maturity (Meses 7-9)
```yaml
Knowledge Management:
  - AI-powered article creation
  - Automatic knowledge extraction
  - Relevance scoring
  - Multi-language support

Continual Service Improvement:
  - CSI register
  - Improvement workflows
  - Metric baselines
  - Trend analysis AI

Event Management:
  - Event correlation
  - Automated incident creation
  - Predictive event analysis
  - Integration hub expansion
```

### Phase 4: ITIL Excellence (Meses 10-12)
```yaml
Service Portfolio Management:
  - Service lifecycle
  - Demand management
  - Financial management
  - Service retirement

Advanced Automation:
  - Self-healing systems
  - Predictive maintenance
  - Automated remediation
  - AI-driven optimization

Enterprise Features:
  - Multi-organization support
  - Advanced RBAC
  - Compliance reporting
  - Audit certification ready
```

---

## 8. 🎯 Diferenciação Competitiva ITIL

### 8.1 Versus ServiceNow
```yaml
ServiceNow:
  Complexidade: Meses de implementação
  Custo: $150-300/agente/mês
  Curva de Aprendizado: Muito alta
  
Nossa Solução:
  Simplicidade: Days to value
  Custo: $50-75/agente/mês  
  User Experience: Intuitiva e moderna
  AI Nativa: Não é add-on caro
```

### 8.2 Versus Jira Service Management
```yaml
Jira:
  ITIL: Precisa customização pesada
  Workflows: Complexos de configurar
  Integrações: Ecosystem Atlassian locked
  
Nossa Solução:
  ITIL: Nativo desde o core
  Workflows: Pre-configurados ITIL
  Integrações: Aberta e extensível
  IA: Integrada em todos os processos
```

---

## 9. 📝 Checklist de Implementação ITIL

### Sprint 0-1: ITIL Foundation
- [ ] Estrutura de domínios ITIL
- [ ] Schema database ITIL-compliant
- [ ] Numeração automática (INC/REQ)
- [ ] Processo de Incident Management
- [ ] Cálculo de prioridade (Impact x Urgency)

### Sprint 2-3: Service Management
- [ ] Catálogo de serviços
- [ ] Portal de requisições
- [ ] Workflows com aprovação
- [ ] SLA por tipo de serviço
- [ ] Formulários dinâmicos

### Sprint 4-5: SLA & CMDB
- [ ] Engine de SLA com pausas
- [ ] Alertas de violação
- [ ] CMDB com CIs básicos
- [ ] Relacionamento CI x Incident
- [ ] Import/export de CIs

### Sprint 6: Dashboards & Go-Live
- [ ] Dashboard operacional ITIL
- [ ] Relatórios de compliance
- [ ] Métricas de performance
- [ ] Training para usuários
- [ ] Go-live com pilot team

---

*Este PRD Enterprise transforma a plataforma em um verdadeiro competidor de ServiceNow e Jira Service Management, com processos ITIL maduros, diferenciação clara e foco em valor de negócio.*
