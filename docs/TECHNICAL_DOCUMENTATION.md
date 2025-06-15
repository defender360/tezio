# Documentação Técnica Completa - ITSM Tezio Defender360

## Índice

1. [Visão Geral do Sistema](#1-visão-geral-do-sistema)
2. [Estrutura de Diretórios](#2-estrutura-de-diretórios)
3. [Backend Laravel - Detalhamento Completo](#3-backend-laravel---detalhamento-completo)
4. [Frontend Vue.js - Componentes e Views](#4-frontend-vuejs---componentes-e-views)
5. [AI Service (FastAPI)](#5-ai-service-fastapi)
6. [Banco de Dados](#6-banco-de-dados)
7. [Infraestrutura e DevOps](#7-infraestrutura-e-devops)
8. [Segurança](#8-segurança)
9. [Testes](#9-testes)
10. [Funcionalidades de Negócio](#10-funcionalidades-de-negócio)
11. [Integrações Externas](#11-integrações-externas)
12. [Performance e Otimizações](#12-performance-e-otimizações)
13. [Comandos e Scripts](#13-comandos-e-scripts)
14. [Configurações e Variáveis de Ambiente](#14-configurações-e-variáveis-de-ambiente)
15. [Código Exemplo](#15-código-exemplo)

---

## 1. Visão Geral do Sistema

### Propósito e Objetivos do ITSM

O **Tezio Defender360** é uma plataforma ITSM (IT Service Management) completa, desenvolvida para gerenciar todos os aspectos de serviços de TI seguindo as melhores práticas do ITIL v4. O sistema oferece:

- **Gestão de Incidentes**: Registro, acompanhamento e resolução de incidentes
- **Gestão de Mudanças**: Controle de mudanças com workflow de aprovação
- **Gestão de Problemas**: Análise de causa raiz e conhecimento
- **Base de Conhecimento**: Documentação com busca inteligente
- **CMDB**: Gestão de ativos e configurações
- **Portal de Serviços**: Catálogo de serviços para usuários
- **Analytics e IA**: Insights preditivos e automação

### Stack Tecnológica Completa

#### Backend
- **Laravel 11** (PHP 8.2+)
- **PostgreSQL 15** com Row Level Security (RLS)
- **Redis** para cache e filas
- **Elasticsearch 8.x** para busca avançada
- **Laravel Scout** para indexação

#### Frontend
- **Vue.js 3** com Composition API
- **TypeScript** para type safety
- **Tailwind CSS** para estilização
- **Tanstack Query** para gerenciamento de estado
- **Pinia** para store management
- **Vite** como build tool

#### AI Service
- **FastAPI** (Python 3.10+)
- **scikit-learn** para ML clássico
- **transformers** para NLP
- **spaCy** para processamento de texto
- **NumPy/Pandas** para análise de dados

#### Infraestrutura
- **Docker** & **Docker Compose** para containerização
- **Nginx** como reverse proxy
- **Prometheus** + **Grafana** para monitoramento
- **ELK Stack** (Elasticsearch, Logstash, Kibana) para logs
- **MinIO** para object storage

### Arquitetura Geral Implementada

```mermaid
graph TB
    subgraph "Frontend Layer"
        VUE[Vue.js SPA]
        PWA[PWA Mobile]
    end
    
    subgraph "API Gateway"
        NGINX[Nginx Reverse Proxy]
    end
    
    subgraph "Application Layer"
        LARAVEL[Laravel API]
        FASTAPI[FastAPI AI Service]
    end
    
    subgraph "Data Layer"
        POSTGRES[(PostgreSQL)]
        REDIS[(Redis)]
        ELASTIC[(Elasticsearch)]
        MINIO[(MinIO Storage)]
    end
    
    subgraph "Monitoring"
        PROM[Prometheus]
        GRAFANA[Grafana]
        ELK[ELK Stack]
    end
    
    VUE --> NGINX
    PWA --> NGINX
    NGINX --> LARAVEL
    NGINX --> FASTAPI
    LARAVEL --> POSTGRES
    LARAVEL --> REDIS
    LARAVEL --> ELASTIC
    LARAVEL --> MINIO
    FASTAPI --> POSTGRES
    FASTAPI --> REDIS
    LARAVEL --> PROM
    FASTAPI --> PROM
    PROM --> GRAFANA
    LARAVEL --> ELK
```

### Decisões Arquiteturais

1. **Domain-Driven Design (DDD)**: Separação clara entre domínios de negócio
2. **Multi-tenancy com RLS**: Isolamento de dados a nível de banco
3. **CQRS Pattern**: Separação de comandos e queries para performance
4. **Event-Driven**: Comunicação assíncrona entre serviços
5. **API-First**: Todas as funcionalidades expostas via API REST
6. **Microservices**: AI Service separado para escalabilidade
7. **Cache-First**: Uso extensivo de cache em múltiplas camadas

---

## 2. Estrutura de Diretórios

### Estrutura Completa do Projeto

```
tezio/
├── backend/                      # Laravel Application
│   ├── app/
│   │   ├── Console/             # Comandos Artisan
│   │   │   └── Commands/
│   │   │       ├── ElasticsearchSetup.php
│   │   │       └── NotificationInitialize.php
│   │   ├── Events/              # Eventos do sistema
│   │   ├── Exceptions/          # Exception handlers
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Api/
│   │   │   │   │   └── V1/
│   │   │   │   │       ├── AnalyticsController.php
│   │   │   │   │       ├── ChangeController.php
│   │   │   │   │       ├── ConfigurationItemController.php
│   │   │   │   │       ├── DashboardController.php
│   │   │   │   │       ├── IncidentController.php
│   │   │   │   │       ├── KnowledgeController.php
│   │   │   │   │       ├── NotificationController.php
│   │   │   │   │       ├── ProblemController.php
│   │   │   │   │       └── ServiceRequestController.php
│   │   │   ├── Middleware/      # Middleware customizado
│   │   │   ├── Requests/        # Form Requests
│   │   │   │   └── V1/
│   │   │   │       ├── Change/
│   │   │   │       ├── Incident/
│   │   │   │       ├── Knowledge/
│   │   │   │       ├── Problem/
│   │   │   │       └── ServiceRequest/
│   │   │   └── Resources/       # API Resources
│   │   │       └── V1/
│   │   │           ├── ChangeResource.php
│   │   │           ├── IncidentResource.php
│   │   │           ├── KnowledgeArticleResource.php
│   │   │           ├── ProblemResource.php
│   │   │           └── ServiceRequestResource.php
│   │   ├── Jobs/                # Background jobs
│   │   ├── Listeners/           # Event listeners
│   │   ├── Mail/                # Email classes
│   │   │   ├── ChangeNotification.php
│   │   │   ├── IncidentNotification.php
│   │   │   └── SlaBreachNotification.php
│   │   ├── Models/              # Eloquent Models
│   │   │   ├── Activity.php
│   │   │   ├── Approval.php
│   │   │   ├── Attachment.php
│   │   │   ├── Change.php
│   │   │   ├── Comment.php
│   │   │   ├── ConfigurationItem.php
│   │   │   ├── Group.php
│   │   │   ├── Incident.php
│   │   │   ├── KnowledgeArticle.php
│   │   │   ├── Notification.php
│   │   │   ├── Problem.php
│   │   │   ├── ServiceRequest.php
│   │   │   ├── SLA.php
│   │   │   ├── User.php
│   │   │   └── Workflow.php
│   │   ├── Policies/            # Authorization policies
│   │   ├── Providers/           # Service providers
│   │   ├── Search/              # Elasticsearch integration
│   │   │   └── ElasticsearchEngine.php
│   │   ├── Services/            # Business logic services
│   │   │   ├── Analytics/
│   │   │   │   ├── ChangeAnalytics.php
│   │   │   │   ├── DashboardService.php
│   │   │   │   ├── IncidentAnalytics.php
│   │   │   │   ├── SlaAnalytics.php
│   │   │   │   └── WorkloadAnalytics.php
│   │   │   ├── ChannelService.php
│   │   │   ├── NotificationService.php
│   │   │   ├── SearchService.php
│   │   │   └── TemplateService.php
│   │   └── Traits/              # Reusable traits
│   │       ├── HasActivities.php
│   │       ├── HasApprovals.php
│   │       └── HasWorkflow.php
│   ├── bootstrap/               # Laravel bootstrap
│   ├── config/                  # Configuration files
│   │   ├── app.php
│   │   ├── database.php
│   │   ├── scout.php
│   │   └── ...
│   ├── database/
│   │   ├── elasticsearch/
│   │   │   └── knowledge_articles_mapping.json
│   │   ├── factories/           # Model factories
│   │   ├── migrations/          # Database migrations
│   │   │   ├── 2024_01_01_create_users_table.php
│   │   │   ├── 2024_01_02_create_incidents_table.php
│   │   │   ├── 2024_01_03_create_configuration_items_table.php
│   │   │   ├── 2024_01_04_create_changes_table.php
│   │   │   ├── 2024_01_05_create_service_requests_table.php
│   │   │   ├── 2024_01_06_create_knowledge_articles_table.php
│   │   │   ├── 2024_01_14_create_problems_table.php
│   │   │   ├── 2024_01_14_create_slas_table.php
│   │   │   └── ...
│   │   └── seeders/             # Database seeders
│   ├── public/                  # Public assets
│   ├── resources/               # Resources
│   ├── routes/                  # Route definitions
│   │   ├── api.php
│   │   └── web.php
│   ├── storage/                 # File storage
│   ├── tests/                   # Test suites
│   │   ├── Feature/
│   │   ├── Integration/
│   │   ├── Performance/
│   │   └── Unit/
│   └── vendor/                  # Composer dependencies
│
├── frontend/                    # Vue.js Application
│   ├── public/
│   │   ├── favicon.svg
│   │   └── index.html
│   ├── src/
│   │   ├── assets/              # Static assets
│   │   ├── components/          # Reusable components
│   │   │   ├── charts/
│   │   │   │   ├── BarChart.vue
│   │   │   │   ├── GaugeChart.vue
│   │   │   │   ├── HeatmapChart.vue
│   │   │   │   ├── LineChart.vue
│   │   │   │   └── PieChart.vue
│   │   │   ├── common/
│   │   │   │   ├── DateRangePicker.vue
│   │   │   │   ├── KPICard.vue
│   │   │   │   ├── PriorityBadge.vue
│   │   │   │   └── StatusBadge.vue
│   │   │   ├── incidents/
│   │   │   │   ├── IncidentCard.vue
│   │   │   │   └── IncidentForm.vue
│   │   │   ├── layout/
│   │   │   │   └── AppLayout.vue
│   │   │   └── notifications/
│   │   │       ├── NotificationBell.vue
│   │   │       ├── NotificationList.vue
│   │   │       └── NotificationPreferences.vue
│   │   ├── composables/         # Vue composables
│   │   │   ├── useAuth.ts
│   │   │   ├── useIncidents.ts
│   │   │   └── useNotifications.ts
│   │   ├── router/              # Vue Router
│   │   │   └── index.ts
│   │   ├── services/            # API services
│   │   │   └── api.ts
│   │   ├── stores/              # Pinia stores
│   │   │   ├── analytics.ts
│   │   │   ├── auth.ts
│   │   │   ├── incident.ts
│   │   │   └── team.ts
│   │   ├── types/               # TypeScript types
│   │   │   └── index.ts
│   │   ├── utils/               # Utility functions
│   │   │   └── format.ts
│   │   ├── views/               # Page components
│   │   │   ├── auth/
│   │   │   │   └── LoginView.vue
│   │   │   ├── changes/
│   │   │   │   ├── ChangeCreateView.vue
│   │   │   │   ├── ChangeDetailView.vue
│   │   │   │   └── ChangeListView.vue
│   │   │   ├── cmdb/
│   │   │   │   ├── ConfigurationItemDetailView.vue
│   │   │   │   └── ConfigurationItemListView.vue
│   │   │   ├── dashboard/
│   │   │   │   ├── DashboardView.vue
│   │   │   │   ├── ExecutiveDashboard.vue
│   │   │   │   ├── OperationalDashboard.vue
│   │   │   │   └── TeamDashboard.vue
│   │   │   ├── incidents/
│   │   │   │   ├── IncidentCreateView.vue
│   │   │   │   ├── IncidentDetailView.vue
│   │   │   │   └── IncidentListView.vue
│   │   │   ├── knowledge/
│   │   │   │   ├── ArticleDetailView.vue
│   │   │   │   ├── ArticleEditorView.vue
│   │   │   │   └── KnowledgeSearchView.vue
│   │   │   ├── problems/
│   │   │   │   ├── ProblemCreateView.vue
│   │   │   │   ├── ProblemDetailView.vue
│   │   │   │   └── ProblemListView.vue
│   │   │   ├── reports/
│   │   │   │   ├── IncidentReports.vue
│   │   │   │   ├── ReportsDashboard.vue
│   │   │   │   └── SLAReports.vue
│   │   │   └── service-requests/
│   │   │       ├── ServiceCatalogModal.vue
│   │   │       └── ServiceRequestListView.vue
│   │   ├── App.vue
│   │   └── main.ts
│   ├── tests/                   # Frontend tests
│   │   ├── e2e/
│   │   ├── integration/
│   │   └── unit/
│   ├── .env.development
│   ├── .env.production
│   ├── package.json
│   ├── tsconfig.json
│   └── vite.config.ts
│
├── ai-service/                  # FastAPI AI Service
│   ├── app/
│   │   ├── api/
│   │   │   └── v1/
│   │   │       ├── analytics.py
│   │   │       ├── endpoints.py
│   │   │       ├── incidents.py
│   │   │       ├── knowledge.py
│   │   │       ├── monitoring.py
│   │   │       └── predictions.py
│   │   ├── core/
│   │   │   ├── config.py
│   │   │   ├── database.py
│   │   │   └── models.py
│   │   ├── ml/
│   │   │   ├── similarity_engine.py
│   │   │   ├── ticket_classifier.py
│   │   │   └── trend_analyzer.py
│   │   ├── schemas/
│   │   │   ├── anomaly.py
│   │   │   ├── incident.py
│   │   │   ├── knowledge.py
│   │   │   ├── resolution.py
│   │   │   └── ticket_analysis.py
│   │   ├── services/
│   │   │   ├── anomaly_detector.py
│   │   │   ├── knowledge_enhancer.py
│   │   │   ├── resolution_suggester.py
│   │   │   └── ticket_analyzer.py
│   │   └── main.py
│   ├── requirements.txt
│   └── Dockerfile
│
├── elasticsearch/               # Elasticsearch config
│   ├── elasticsearch.yml
│   └── README.md
│
├── monitoring/                  # Monitoring configs
│   ├── prometheus/
│   │   ├── prometheus.yml
│   │   └── alerts.yml
│   ├── grafana/
│   │   ├── datasources.yml
│   │   └── dashboards/
│   └── alertmanager/
│       └── alertmanager.yml
│
├── scripts/                     # Utility scripts
│   ├── migrate-legacy-data.php
│   ├── seed-test-data.php
│   └── setup-environment.sh
│
├── docs/                        # Documentation
│   ├── API.md
│   ├── DEPLOYMENT.md
│   ├── DEVELOPER.md
│   └── USER_MANUAL.md
│
├── docker-compose.yml           # Docker orchestration
├── .env.example                # Environment template
├── .env.elasticsearch          # Elasticsearch config
├── Makefile                    # Build commands
└── README.md                   # Project overview
```

---

## 3. Backend Laravel - Detalhamento Completo

### 3.1 Domain Layer (DDD)

#### Domains Implementados

O backend segue Domain-Driven Design com os seguintes domínios:

##### 1. **Incident Management Domain**

**Models:**
- `Incident.php` - Modelo principal de incidentes
  - Relacionamentos: User (reporter, assignee), Group, ConfigurationItem, Attachment, Comment
  - Traits: HasActivities, HasWorkflow
  - Métodos especiais: `generateIncidentNumber()`, `calculatePriority()`, `isOverdue()`

**Controller:**
- `IncidentController.php`
  - Rotas: index, store, show, update, destroy, bulkUpdate, assign, resolve, close, reopen, metrics, export, timeline, relatedIncidents, mergeIncidents, escalate

**Form Requests:**
- `StoreIncidentRequest.php` - Validação para criação
- `UpdateIncidentRequest.php` - Validação para atualização
- `BulkUpdateRequest.php` - Validação para updates em massa
- `AssignIncidentRequest.php` - Validação para atribuição
- `ResolveIncidentRequest.php` - Validação para resolução

**Resources:**
- `IncidentResource.php` - Formatação principal
- `IncidentCollection.php` - Coleção com metadados

**Services:**
- `IncidentAnalytics.php` - Análise e métricas de incidentes

##### 2. **Change Management Domain**

**Models:**
- `Change.php` - Modelo de mudanças
  - Relacionamentos: User (requester, implementer), Approvals, ConfigurationItems, Tasks
  - Estados: draft, submitted, approved, scheduled, implementing, completed, failed, cancelled
  - Métodos: `generateChangeNumber()`, `canBeImplemented()`, `calculateRiskScore()`

**Controller:**
- `ChangeController.php`
  - Rotas: index, store, show, update, destroy, submit, approve, reject, implement, complete, scheduleCabMeeting, impactAnalysis, riskAssessment, calendar

**Form Requests:**
- `StoreChangeRequest.php` - Criação de mudança
- `UpdateChangeRequest.php` - Atualização de mudança
- `ApproveChangeRequest.php` - Aprovação com condições
- `ScheduleCabMeetingRequest.php` - Agendamento CAB
- `RiskAssessmentRequest.php` - Avaliação de risco

**Resources:**
- `ChangeResource.php` - Recurso principal
- `ChangeCollection.php` - Coleção com estatísticas
- `ChangeApprovalResource.php` - Detalhes de aprovação
- `CabMeetingResource.php` - Reuniões CAB

**Services:**
- `ChangeAnalytics.php` - Análise de mudanças e sucesso

##### 3. **Service Request Domain**

**Models:**
- `ServiceRequest.php` - Modelo de solicitações
  - Relacionamentos: User (requester, requested_for), ServiceItem, Tasks, Approvals
  - Estados: submitted, in_progress, pending_approval, approved, completed, cancelled
  - Métodos: `generateRequestNumber()`, `calculateCompletionPercentage()`

- `ServiceCatalogItem.php` - Itens do catálogo
  - Campos: name, description, category, sla_hours, requires_approval, form_fields

**Controller:**
- `ServiceRequestController.php`
  - Rotas: index, store, show, update, destroy, catalog, approve, reject, assign, start, complete, cancel, tasks, createTask, updateTaskStatus, slaTracking

**Form Requests:**
- `StoreServiceRequestRequest.php` - Nova solicitação
- `UpdateServiceRequestRequest.php` - Atualização
- `CreateServiceTaskRequest.php` - Criação de tarefa
- `UpdateTaskStatusRequest.php` - Status de tarefa

**Resources:**
- `ServiceRequestResource.php` - Recurso principal
- `ServiceRequestCollection.php` - Coleção
- `ServiceTaskResource.php` - Tarefas
- `ServiceCatalogItemResource.php` - Itens do catálogo

##### 4. **Problem Management Domain**

**Models:**
- `Problem.php` - Modelo de problemas
  - Relacionamentos: Incidents, Changes, ConfigurationItems, Investigations
  - Estados: open, investigating, identified, resolved, closed
  - Métodos: `markAsKnownError()`, `createKnownErrorArticle()`

- `ProblemInvestigation.php` - Investigações
  - Tipos: initial, technical, business_impact, root_cause, solution

**Controller:**
- `ProblemController.php`
  - Rotas: index, store, show, update, destroy, addInvestigation, setRootCause, convertToKnownError, linkIncidents, unlinkIncident, addWorkaround, approveWorkaround, createChange, resolve, knownErrors

**Form Requests:**
- `StoreProblemRequest.php` - Novo problema
- `UpdateProblemRequest.php` - Atualização
- `AddInvestigationRequest.php` - Nova investigação
- `LinkIncidentsRequest.php` - Vincular incidentes
- `AddWorkaroundRequest.php` - Adicionar workaround

**Resources:**
- `ProblemResource.php` - Recurso principal
- `ProblemCollection.php` - Coleção
- `ProblemInvestigationResource.php` - Investigações
- `KnownErrorResource.php` - Erros conhecidos

##### 5. **Knowledge Management Domain**

**Models:**
- `KnowledgeArticle.php` - Artigos da base
  - Relacionamentos: Author, Category, Tags, Versions, Comments
  - Scout Searchable com Elasticsearch
  - Métodos: `incrementViews()`, `calculateRating()`

- `KnowledgeCategory.php` - Categorias
  - Estrutura hierárquica com parent/children

**Controller:**
- `KnowledgeController.php`
  - Rotas: index, store, show, update, destroy, publish, unpublish, rate, markHelpful, search, suggestions, versions, restoreVersion, categories, createCategory, updateCategory

**Form Requests:**
- `StoreKnowledgeArticleRequest.php` - Novo artigo
- `UpdateKnowledgeArticleRequest.php` - Atualização
- `RateArticleRequest.php` - Avaliação
- `CreateCategoryRequest.php` - Nova categoria

**Resources:**
- `KnowledgeArticleResource.php` - Artigo completo
- `KnowledgeArticleCollection.php` - Coleção
- `KnowledgeCategoryResource.php` - Categorias
- `ArticleVersionResource.php` - Versões

**Services:**
- `SearchService.php` - Busca avançada com Elasticsearch

##### 6. **CMDB Domain**

**Models:**
- `ConfigurationItem.php` - Itens de configuração
  - Tipos: hardware, software, network, service, documentation
  - Relacionamentos: RelatedCIs, Incidents, Changes, Owner
  - Métodos: `calculateHealth()`, `getRelationshipMap()`

**Controller:**
- `ConfigurationItemController.php`
  - Rotas: index, store, show, update, destroy, relationships, impact, health

**Resources:**
- `ConfigurationItemResource.php` - CI completo
- `ConfigurationItemCollection.php` - Coleção

##### 7. **SLA Management Domain**

**Models:**
- `SLA.php` - Acordos de nível de serviço
  - Campos: priority_matrix, business_hours_only, response_time, resolution_time
  - Métodos: `calculateDeadline()`, `addBusinessMinutes()`, `calculateCompliance()`

- `SLAMetric.php` - Métricas de SLA
  - Tracking de response, resolution, breach

**Services:**
- `SlaAnalytics.php` - Análise de compliance e breaches

##### 8. **Workflow Engine Domain**

**Models:**
- `Workflow.php` - Definições de workflow
- `WorkflowState.php` - Estados possíveis
- `WorkflowTransition.php` - Transições entre estados

**Traits:**
- `HasWorkflow.php` - Adiciona workflow a qualquer modelo
  - Métodos: `transitionTo()`, `canTransition()`, `getAvailableTransitions()`

##### 9. **Notification Domain**

**Models:**
- `Notification.php` - Notificações do usuário
- `NotificationTemplate.php` - Templates de email/SMS
- `NotificationChannel.php` - Canais de entrega
- `NotificationPreference.php` - Preferências do usuário

**Controller:**
- `NotificationController.php`
  - Rotas: index, show, markAsRead, markAllAsRead, preferences, updatePreferences, testNotification

**Services:**
- `NotificationService.php` - Orquestrador de notificações
- `TemplateService.php` - Processamento de templates
- `ChannelService.php` - Entrega multi-canal

### 3.2 Infrastructure Layer

#### Migrations (30+ arquivos)

```sql
-- Principais migrations em ordem de criação:
2024_01_01_create_users_table.php
2024_01_01_create_groups_table.php
2024_01_01_create_roles_permissions_tables.php
2024_01_02_create_incidents_table.php
2024_01_03_create_configuration_items_table.php
2024_01_04_create_changes_table.php
2024_01_05_create_service_catalog_items_table.php
2024_01_05_create_service_requests_table.php
2024_01_06_create_knowledge_categories_table.php
2024_01_06_create_knowledge_articles_table.php
2024_01_07_create_workflows_table.php
2024_01_08_create_approvals_table.php
2024_01_09_create_activities_table.php
2024_01_10_create_attachments_table.php
2024_01_11_create_comments_table.php
2024_01_14_create_problems_table.php
2024_01_14_create_slas_table.php
2024_01_15_create_notifications_table.php
2024_01_15_create_notification_templates_table.php
2024_01_15_create_notification_channels_table.php
2024_01_15_create_notification_preferences_table.php
```

#### Seeders

- `DatabaseSeeder.php` - Orquestrador principal
- `UserSeeder.php` - Usuários de teste
- `IncidentSeeder.php` - Incidentes de exemplo
- `ServiceCatalogSeeder.php` - Catálogo de serviços
- `KnowledgeSeeder.php` - Artigos iniciais

#### External Integrations

- **Elasticsearch** via Scout Engine customizado
- **Redis** para cache e filas
- **SMTP/Twilio** para notificações
- **MinIO** para storage de arquivos

### 3.3 API Endpoints

#### Incident Management
```
GET    /api/v1/incidents                 - Listar incidentes
POST   /api/v1/incidents                 - Criar incidente
GET    /api/v1/incidents/{id}            - Detalhes do incidente
PUT    /api/v1/incidents/{id}            - Atualizar incidente
DELETE /api/v1/incidents/{id}            - Deletar incidente
POST   /api/v1/incidents/bulk-update     - Atualização em massa
POST   /api/v1/incidents/{id}/assign     - Atribuir incidente
POST   /api/v1/incidents/{id}/resolve    - Resolver incidente
POST   /api/v1/incidents/{id}/close      - Fechar incidente
POST   /api/v1/incidents/{id}/reopen     - Reabrir incidente
GET    /api/v1/incidents/metrics         - Métricas de incidentes
GET    /api/v1/incidents/export          - Exportar incidentes
GET    /api/v1/incidents/{id}/timeline   - Timeline do incidente
GET    /api/v1/incidents/{id}/related    - Incidentes relacionados
POST   /api/v1/incidents/merge           - Mesclar incidentes
POST   /api/v1/incidents/{id}/escalate   - Escalar incidente
```

#### Change Management
```
GET    /api/v1/changes                   - Listar mudanças
POST   /api/v1/changes                   - Criar mudança
GET    /api/v1/changes/{id}              - Detalhes da mudança
PUT    /api/v1/changes/{id}              - Atualizar mudança
DELETE /api/v1/changes/{id}              - Deletar mudança
POST   /api/v1/changes/{id}/submit       - Submeter para aprovação
POST   /api/v1/changes/{id}/approve      - Aprovar mudança
POST   /api/v1/changes/{id}/reject       - Rejeitar mudança
POST   /api/v1/changes/{id}/implement    - Implementar mudança
POST   /api/v1/changes/{id}/complete     - Completar mudança
POST   /api/v1/changes/{id}/schedule-cab - Agendar CAB
GET    /api/v1/changes/{id}/impact       - Análise de impacto
POST   /api/v1/changes/{id}/risk         - Avaliação de risco
GET    /api/v1/changes/calendar          - Calendário de mudanças
```

#### Service Request Management
```
GET    /api/v1/service-requests                      - Listar solicitações
POST   /api/v1/service-requests                      - Criar solicitação
GET    /api/v1/service-requests/{id}                 - Detalhes
PUT    /api/v1/service-requests/{id}                 - Atualizar
DELETE /api/v1/service-requests/{id}                 - Deletar
GET    /api/v1/service-catalog                       - Catálogo
POST   /api/v1/service-requests/{id}/approve         - Aprovar
POST   /api/v1/service-requests/{id}/reject          - Rejeitar
POST   /api/v1/service-requests/{id}/assign          - Atribuir
POST   /api/v1/service-requests/{id}/start           - Iniciar
POST   /api/v1/service-requests/{id}/complete        - Completar
POST   /api/v1/service-requests/{id}/cancel          - Cancelar
GET    /api/v1/service-requests/{id}/tasks           - Listar tarefas
POST   /api/v1/service-requests/{id}/tasks           - Criar tarefa
PUT    /api/v1/service-requests/tasks/{id}           - Atualizar tarefa
GET    /api/v1/service-requests/{id}/sla             - Tracking SLA
GET    /api/v1/service-requests/stats                - Estatísticas
```

#### Problem Management
```
GET    /api/v1/problems                              - Listar problemas
POST   /api/v1/problems                              - Criar problema
GET    /api/v1/problems/{id}                         - Detalhes
PUT    /api/v1/problems/{id}                         - Atualizar
DELETE /api/v1/problems/{id}                         - Deletar
POST   /api/v1/problems/{id}/investigations          - Add investigação
POST   /api/v1/problems/{id}/root-cause              - Definir causa raiz
POST   /api/v1/problems/{id}/known-error             - Marcar erro conhecido
POST   /api/v1/problems/{id}/incidents               - Vincular incidentes
DELETE /api/v1/problems/{id}/incidents/{incident}    - Desvincular
POST   /api/v1/problems/{id}/workaround              - Add workaround
POST   /api/v1/problems/{id}/workaround/approve      - Aprovar workaround
POST   /api/v1/problems/{id}/create-change           - Criar mudança
POST   /api/v1/problems/{id}/resolve                 - Resolver
GET    /api/v1/problems/known-errors                 - Listar erros conhecidos
```

#### Knowledge Management
```
GET    /api/v1/knowledge/articles                    - Listar artigos
POST   /api/v1/knowledge/articles                    - Criar artigo
GET    /api/v1/knowledge/articles/{id}              - Detalhes
PUT    /api/v1/knowledge/articles/{id}              - Atualizar
DELETE /api/v1/knowledge/articles/{id}              - Deletar
POST   /api/v1/knowledge/articles/{id}/publish      - Publicar
POST   /api/v1/knowledge/articles/{id}/unpublish    - Despublicar
POST   /api/v1/knowledge/articles/{id}/rate         - Avaliar
POST   /api/v1/knowledge/articles/{id}/helpful      - Marcar útil
GET    /api/v1/knowledge/search                     - Buscar
GET    /api/v1/knowledge/search/autocomplete        - Autocomplete
GET    /api/v1/knowledge/search/trending            - Trending
GET    /api/v1/knowledge/articles/{id}/similar      - Similares
POST   /api/v1/knowledge/search/reindex             - Reindexar
GET    /api/v1/knowledge/search/health              - Health check
GET    /api/v1/knowledge/suggestions                - Sugestões AI
GET    /api/v1/knowledge/articles/{id}/versions     - Versões
POST   /api/v1/knowledge/articles/{id}/restore      - Restaurar versão
GET    /api/v1/knowledge/categories                 - Categorias
POST   /api/v1/knowledge/categories                 - Criar categoria
PUT    /api/v1/knowledge/categories/{id}            - Atualizar categoria
```

#### Configuration Management (CMDB)
```
GET    /api/v1/configuration-items                   - Listar CIs
POST   /api/v1/configuration-items                   - Criar CI
GET    /api/v1/configuration-items/{id}             - Detalhes
PUT    /api/v1/configuration-items/{id}             - Atualizar
DELETE /api/v1/configuration-items/{id}             - Deletar
GET    /api/v1/configuration-items/{id}/relationships - Relacionamentos
GET    /api/v1/configuration-items/{id}/impact      - Análise impacto
GET    /api/v1/configuration-items/{id}/health      - Health status
```

#### Analytics & Dashboards
```
GET    /api/v1/dashboard/executive                   - Dashboard executivo
GET    /api/v1/dashboard/operational                 - Dashboard operacional
GET    /api/v1/dashboard/team                        - Dashboard equipe
GET    /api/v1/analytics/overview                    - Overview geral
GET    /api/v1/analytics/incidents                   - Analytics incidentes
GET    /api/v1/analytics/changes                     - Analytics mudanças
GET    /api/v1/analytics/sla                         - Analytics SLA
GET    /api/v1/analytics/workload                    - Analytics carga
GET    /api/v1/analytics/export                      - Exportar dados
```

#### Notifications
```
GET    /api/v1/notifications                         - Listar notificações
GET    /api/v1/notifications/{id}                    - Detalhes
POST   /api/v1/notifications/{id}/read               - Marcar lida
POST   /api/v1/notifications/read-all                - Marcar todas lidas
GET    /api/v1/notifications/preferences             - Preferências
PUT    /api/v1/notifications/preferences             - Atualizar prefs
POST   /api/v1/notifications/test                    - Testar notificação
```

---

## 4. Frontend Vue.js - Componentes e Views

### 4.1 Views/Pages

#### Authentication
- **Route:** `/login`
- **Component:** `LoginView.vue`
- **Functionality:** Login com Auth0 ou dev mode
- **Components Used:** Formulário de login, loading spinner

#### Dashboard Views
- **Route:** `/dashboard`
- **Component:** `DashboardView.vue`
- **Functionality:** Dashboard principal com seleção de tipo
- **Subviews:**
  - `ExecutiveDashboard.vue` - KPIs executivos, tendências
  - `OperationalDashboard.vue` - Métricas operacionais em tempo real
  - `TeamDashboard.vue` - Performance da equipe

#### Incident Management
- **Route:** `/incidents`
- **Component:** `IncidentListView.vue`
- **Functionality:** Lista de incidentes com filtros, bulk actions
- **Components Used:** StatusBadge, PriorityBadge, DataTable

- **Route:** `/incidents/new`
- **Component:** `IncidentCreateView.vue`
- **Functionality:** Formulário de criação de incidente
- **Components Used:** IncidentForm, UserSelect, CISelect

- **Route:** `/incidents/:id`
- **Component:** `IncidentDetailView.vue`
- **Functionality:** Detalhes completos, timeline, ações
- **Components Used:** ActivityLog, Comments, Attachments

#### Change Management
- **Route:** `/changes`
- **Component:** `ChangeListView.vue`
- **Functionality:** Lista e calendário de mudanças
- **Components Used:** Calendar, ChangeCard, FilterBar

- **Route:** `/changes/new`
- **Component:** `ChangeCreateView.vue`
- **Functionality:** Criação de mudança com wizard
- **Components Used:** StepWizard, RiskMatrix, ApproverSelect

- **Route:** `/changes/:id`
- **Component:** `ChangeDetailView.vue`
- **Functionality:** Detalhes, aprovações, implementação
- **Components Used:** ApprovalFlow, TaskList, ImpactAnalysis

#### Service Requests
- **Route:** `/service-requests`
- **Component:** `ServiceRequestListView.vue`
- **Functionality:** Lista de solicitações, catálogo
- **Components Used:** ServiceCatalogModal, ProgressBar

#### Problem Management
- **Route:** `/problems`
- **Component:** `ProblemListView.vue`
- **Functionality:** Lista de problemas, known errors
- **Components Used:** ProblemCard, KnownErrorBadge

- **Route:** `/problems/new`
- **Component:** `ProblemCreateView.vue`
- **Functionality:** Criação com link de incidentes
- **Components Used:** IncidentSelector, InvestigationForm

- **Route:** `/problems/:id`
- **Component:** `ProblemDetailView.vue`
- **Functionality:** Detalhes, investigações, RCA
- **Components Used:** InvestigationLog, RootCauseForm

#### Knowledge Base
- **Route:** `/knowledge`
- **Component:** `KnowledgeSearchView.vue`
- **Functionality:** Busca avançada, categorias
- **Components Used:** SearchBar, CategoryTree, ArticleCard

- **Route:** `/knowledge/articles/:id`
- **Component:** `ArticleDetailView.vue`
- **Functionality:** Visualização de artigo, rating
- **Components Used:** ArticleViewer, RatingStars, Comments

- **Route:** `/knowledge/editor`
- **Component:** `ArticleEditorView.vue`
- **Functionality:** Editor WYSIWYG
- **Components Used:** RichTextEditor, TagInput, Preview

#### CMDB
- **Route:** `/cmdb`
- **Component:** `ConfigurationItemListView.vue`
- **Functionality:** Lista de CIs, relacionamentos
- **Components Used:** CICard, RelationshipMap

- **Route:** `/cmdb/:id`
- **Component:** `ConfigurationItemDetailView.vue`
- **Functionality:** Detalhes do CI, impacto
- **Components Used:** ImpactDiagram, HealthIndicator

#### Reports
- **Route:** `/reports`
- **Component:** `ReportsDashboard.vue`
- **Functionality:** Hub de relatórios
- **Components Used:** ReportCard, ExportButton

- **Route:** `/reports/incidents`
- **Component:** `IncidentReports.vue`
- **Functionality:** Analytics de incidentes
- **Components Used:** LineChart, BarChart, DateRangePicker

- **Route:** `/reports/sla`
- **Component:** `SLAReports.vue`
- **Functionality:** Compliance SLA
- **Components Used:** GaugeChart, ComplianceTable

### 4.2 Componentes Reutilizáveis

#### Layout Components
- **`AppLayout.vue`**
  - Props: none
  - Events: logout
  - Usage: Wrapper principal com sidebar e header
  - Features: Menu colapsável, notificações, user menu

#### Common Components
- **`StatusBadge.vue`**
  - Props: `status: string`, `type: string`
  - Usage: Badges coloridos para status
  
- **`PriorityBadge.vue`**
  - Props: `priority: string`
  - Usage: Indicador de prioridade

- **`DateRangePicker.vue`**
  - Props: `modelValue: DateRange`, `presets: boolean`
  - Events: `update:modelValue`
  - Usage: Seleção de período

- **`KPICard.vue`**
  - Props: `title: string`, `value: number`, `trend: object`
  - Usage: Cards de métricas

#### Chart Components
- **`LineChart.vue`**
  - Props: `data: ChartData`, `options: ChartOptions`
  - Usage: Gráficos de linha/tempo

- **`BarChart.vue`**
  - Props: `data: ChartData`, `horizontal: boolean`
  - Usage: Gráficos de barras

- **`PieChart.vue`**
  - Props: `data: ChartData`, `showLegend: boolean`
  - Usage: Gráficos de pizza

- **`GaugeChart.vue`**
  - Props: `value: number`, `min: number`, `max: number`, `thresholds: array`
  - Usage: Gauges de SLA

- **`HeatmapChart.vue`**
  - Props: `data: HeatmapData`, `colorScale: string`
  - Usage: Heatmaps de atividade

#### Notification Components
- **`NotificationBell.vue`**
  - Props: none
  - Events: click
  - Usage: Sino de notificações no header
  - Features: Badge contador, dropdown

- **`NotificationList.vue`**
  - Props: `notifications: array`
  - Events: markAsRead, markAllAsRead
  - Usage: Lista de notificações

- **`NotificationPreferences.vue`**
  - Props: `preferences: object`
  - Events: save
  - Usage: Configurações de notificação

#### Incident Components
- **`IncidentCard.vue`**
  - Props: `incident: Incident`
  - Events: click, assign
  - Usage: Card resumido de incidente

- **`IncidentForm.vue`**
  - Props: `incident?: Incident`
  - Events: submit, cancel
  - Usage: Formulário de incidente

### 4.3 State Management

#### Pinia Stores

**`auth.ts`**
```typescript
interface AuthState {
  user: User | null
  isAuthenticated: boolean
  permissions: string[]
}

Actions:
- login(credentials)
- logout()
- checkPermission(permission)
```

**`incident.ts`**
```typescript
interface IncidentState {
  incidents: Incident[]
  currentIncident: Incident | null
  filters: IncidentFilters
  stats: IncidentStats
}

Actions:
- fetchIncidents(params)
- createIncident(data)
- updateIncident(id, data)
- assignIncident(id, userId)
```

**`analytics.ts`**
```typescript
interface AnalyticsState {
  dashboardData: DashboardData
  dateRange: DateRange
  realTimeMetrics: Metrics
}

Actions:
- fetchDashboardData(type)
- updateDateRange(range)
- subscribeToRealTime()
```

**`team.ts`**
```typescript
interface TeamState {
  members: TeamMember[]
  workload: WorkloadData
  skills: SkillMatrix
}

Actions:
- fetchTeamMembers()
- updateWorkload()
- getAvailableMembers(skills)
```

#### Composables

**`useAuth.ts`**
- Provides: user, isAuthenticated, hasPermission
- Methods: login, logout, refreshToken

**`useIncidents.ts`**
- Provides: incidents, loading, error
- Methods: create, update, resolve, close

**`useNotifications.ts`**
- Provides: notifications, unreadCount
- Methods: markAsRead, subscribe, unsubscribe

---

## 5. AI Service (FastAPI)

### 5.1 Endpoints ML/AI

#### Incident Analysis
```
POST   /api/v1/ai/incidents/analyze          - Analisar incidente
POST   /api/v1/ai/incidents/categorize       - Categorizar automaticamente
POST   /api/v1/ai/incidents/priority         - Sugerir prioridade
POST   /api/v1/ai/incidents/similar          - Encontrar similares
```

#### Knowledge Enhancement
```
POST   /api/v1/ai/knowledge/enhance          - Melhorar artigo
POST   /api/v1/ai/knowledge/suggest          - Sugerir artigos
POST   /api/v1/ai/knowledge/generate         - Gerar de incidentes
POST   /api/v1/ai/knowledge/quality          - Avaliar qualidade
```

#### Predictive Analytics
```
GET    /api/v1/ai/predictions/volume         - Prever volume
GET    /api/v1/ai/predictions/resources      - Prever recursos
GET    /api/v1/ai/predictions/sla            - Prever breaches
POST   /api/v1/ai/predictions/failure        - Prever falhas
```

#### Anomaly Detection
```
POST   /api/v1/ai/anomalies/detect           - Detectar anomalias
GET    /api/v1/ai/anomalies/trends           - Tendências anômalas
POST   /api/v1/ai/anomalies/configure        - Configurar detecção
```

### 5.2 Modelos de ML Utilizados

#### Ticket Classifier
- **Algoritmo:** Ensemble (Random Forest + XGBoost + Neural Network)
- **Features:** TF-IDF, word embeddings, metadata
- **Output:** Categoria, prioridade, time sugerido

#### Similarity Engine
- **Algoritmo:** Sentence-BERT embeddings + FAISS
- **Features:** Semantic embeddings de texto
- **Output:** Scores de similaridade, rankings

#### Trend Analyzer
- **Algoritmo:** SARIMA + Prophet + LSTM
- **Features:** Time series históricas
- **Output:** Forecasts com intervalos de confiança

#### Anomaly Detector
- **Algoritmo:** Isolation Forest + Autoencoders
- **Features:** Métricas multivariadas
- **Output:** Anomaly scores, alertas

### 5.3 Integrações

#### Conexão com Laravel
- Autenticação via API keys
- Async requests com retry logic
- Circuit breaker pattern
- Response caching

#### Processamento de Dados
- Batch processing para eficiência
- Streaming para real-time
- Data validation e sanitization
- Feature engineering pipeline

#### Cache e Otimizações
- Redis para cache de predictions
- Model caching em memória
- Lazy loading de modelos
- Connection pooling

---

## 6. Banco de Dados

### 6.1 Schema Principal

#### Tabela: users
```sql
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255),
    avatar VARCHAR(255),
    department VARCHAR(100),
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT true,
    last_login_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

#### Tabela: incidents
```sql
CREATE TABLE incidents (
    id BIGSERIAL PRIMARY KEY,
    incident_number VARCHAR(20) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'open',
    priority VARCHAR(20) DEFAULT 'medium',
    impact VARCHAR(20) DEFAULT 'medium',
    urgency VARCHAR(20) DEFAULT 'medium',
    category VARCHAR(100),
    subcategory VARCHAR(100),
    reported_by BIGINT REFERENCES users(id),
    assigned_to BIGINT REFERENCES users(id),
    assigned_group_id BIGINT REFERENCES groups(id),
    configuration_item_id BIGINT REFERENCES configuration_items(id),
    resolution TEXT,
    resolved_at TIMESTAMP,
    closed_at TIMESTAMP,
    reopened_count INTEGER DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    INDEX idx_status_priority (status, priority),
    INDEX idx_assigned (assigned_to, status),
    INDEX idx_created (created_at DESC)
);
```

#### Tabela: changes
```sql
CREATE TABLE changes (
    id BIGSERIAL PRIMARY KEY,
    change_number VARCHAR(20) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'standard',
    status VARCHAR(50) DEFAULT 'draft',
    priority VARCHAR(20) DEFAULT 'medium',
    risk_level VARCHAR(20) DEFAULT 'medium',
    impact_level VARCHAR(20) DEFAULT 'medium',
    category VARCHAR(100),
    reason_for_change TEXT,
    implementation_plan TEXT,
    rollback_plan TEXT,
    test_plan TEXT,
    scheduled_start TIMESTAMP,
    scheduled_end TIMESTAMP,
    actual_start TIMESTAMP,
    actual_end TIMESTAMP,
    downtime_required BOOLEAN DEFAULT false,
    downtime_duration INTEGER,
    requested_by BIGINT REFERENCES users(id),
    implementer_id BIGINT REFERENCES users(id),
    approval_status VARCHAR(50),
    cab_required BOOLEAN DEFAULT false,
    cab_date TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

#### Tabela: service_requests
```sql
CREATE TABLE service_requests (
    id BIGSERIAL PRIMARY KEY,
    request_number VARCHAR(20) UNIQUE NOT NULL,
    service_item_id BIGINT REFERENCES service_catalog_items(id),
    status VARCHAR(50) DEFAULT 'submitted',
    priority VARCHAR(20) DEFAULT 'medium',
    requester_id BIGINT REFERENCES users(id),
    requested_for_id BIGINT REFERENCES users(id),
    assigned_to BIGINT REFERENCES users(id),
    assigned_group_id BIGINT REFERENCES groups(id),
    description TEXT,
    business_justification TEXT,
    due_date TIMESTAMP,
    completion_percentage INTEGER DEFAULT 0,
    approval_status VARCHAR(50),
    additional_info JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

#### Tabela: problems
```sql
CREATE TABLE problems (
    id BIGSERIAL PRIMARY KEY,
    problem_number VARCHAR(20) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'open',
    priority VARCHAR(20) DEFAULT 'medium',
    category VARCHAR(100),
    root_cause TEXT,
    symptoms JSONB,
    impact_description TEXT,
    workaround TEXT,
    permanent_solution TEXT,
    assigned_to BIGINT REFERENCES users(id),
    assigned_group_id BIGINT REFERENCES groups(id),
    reported_by BIGINT REFERENCES users(id),
    detected_date TIMESTAMP,
    resolved_date TIMESTAMP,
    closed_date TIMESTAMP,
    known_error BOOLEAN DEFAULT false,
    known_error_date TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

#### Tabela: knowledge_articles
```sql
CREATE TABLE knowledge_articles (
    id BIGSERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    status VARCHAR(50) DEFAULT 'draft',
    category_id BIGINT REFERENCES knowledge_categories(id),
    author_id BIGINT REFERENCES users(id),
    tags JSONB,
    views_count INTEGER DEFAULT 0,
    helpful_count INTEGER DEFAULT 0,
    rating_sum INTEGER DEFAULT 0,
    rating_count INTEGER DEFAULT 0,
    version INTEGER DEFAULT 1,
    published_at TIMESTAMP,
    reviewed_at TIMESTAMP,
    expires_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    -- Full text search
    search_vector TSVECTOR,
    INDEX idx_search_vector (search_vector) USING gin
);
```

#### Tabela: configuration_items
```sql
CREATE TABLE configuration_items (
    id BIGSERIAL PRIMARY KEY,
    ci_number VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    category VARCHAR(100),
    subcategory VARCHAR(100),
    manufacturer VARCHAR(100),
    model VARCHAR(100),
    serial_number VARCHAR(100),
    asset_tag VARCHAR(100),
    location VARCHAR(255),
    owner_id BIGINT REFERENCES users(id),
    purchase_date DATE,
    warranty_expires DATE,
    attributes JSONB,
    relationships JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

#### Tabela: slas
```sql
CREATE TABLE slas (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type VARCHAR(50) DEFAULT 'incident',
    target_type VARCHAR(50) DEFAULT 'both',
    business_hours_only BOOLEAN DEFAULT true,
    response_time INTEGER,
    resolution_time INTEGER,
    escalation_time INTEGER,
    priority_matrix JSONB,
    excluded_statuses JSONB,
    conditions JSONB,
    penalties JSONB,
    is_active BOOLEAN DEFAULT true,
    valid_from TIMESTAMP,
    valid_until TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

### 6.2 Multi-tenancy

#### Row Level Security (RLS)

```sql
-- Enable RLS on all tables
ALTER TABLE incidents ENABLE ROW LEVEL SECURITY;
ALTER TABLE changes ENABLE ROW LEVEL SECURITY;
ALTER TABLE service_requests ENABLE ROW LEVEL SECURITY;

-- Create tenant isolation policy
CREATE POLICY tenant_isolation ON incidents
    FOR ALL
    USING (tenant_id = current_setting('app.current_tenant')::INTEGER);

-- Function to set current tenant
CREATE OR REPLACE FUNCTION set_current_tenant(tenant_id INTEGER)
RETURNS VOID AS $$
BEGIN
    PERFORM set_config('app.current_tenant', tenant_id::TEXT, false);
END;
$$ LANGUAGE plpgsql;
```

#### Tenant Management

- Cada registro tem `tenant_id`
- Middleware Laravel seta tenant atual
- Políticas RLS garantem isolamento
- Índices incluem tenant_id para performance

---

## 7. Infraestrutura e DevOps

### 7.1 Docker

#### Serviços no docker-compose.yml

```yaml
version: '3.8'

services:
  # Application Services
  nginx:
    image: nginx:alpine
    ports:
      - "443:443"
    volumes:
      - ./nginx/conf.d:/etc/nginx/conf.d
    depends_on:
      - backend
      - frontend

  backend:
    build: ./backend
    environment:
      - DB_CONNECTION=pgsql
      - REDIS_HOST=redis
      - ELASTICSEARCH_HOST=elasticsearch
    depends_on:
      - postgres
      - redis
      - elasticsearch

  frontend:
    build: ./frontend
    ports:
      - "80:3000"
    environment:
      - VITE_API_URL=http://backend:8000

  ai-service:
    build: ./ai-service
    ports:
      - "8001:8001"
    environment:
      - DATABASE_URL=postgresql://user:pass@postgres/itsm
    volumes:
      - ai_models:/app/models

  # Data Services
  postgres:
    image: postgres:15
    environment:
      - POSTGRES_DB=itsm
      - POSTGRES_USER=itsm_user
      - POSTGRES_PASSWORD=secure_password
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    image: redis:7-alpine
    volumes:
      - redis_data:/data

  elasticsearch:
    image: elasticsearch:8.11.0
    environment:
      - discovery.type=single-node
      - xpack.security.enabled=false
    volumes:
      - elastic_data:/usr/share/elasticsearch/data

  kibana:
    image: kibana:8.11.0
    ports:
      - "5601:5601"
    environment:
      - ELASTICSEARCH_HOSTS=http://elasticsearch:9200

  # Monitoring Services
  prometheus:
    image: prom/prometheus
    ports:
      - "9090:9090"
    volumes:
      - ./monitoring/prometheus:/etc/prometheus
      - prometheus_data:/prometheus

  grafana:
    image: grafana/grafana
    ports:
      - "3000:3000"
    volumes:
      - ./monitoring/grafana:/etc/grafana/provisioning
      - grafana_data:/var/lib/grafana

  # Logging Services
  logstash:
    image: logstash:8.11.0
    volumes:
      - ./monitoring/logstash/pipeline:/usr/share/logstash/pipeline

  filebeat:
    image: elastic/filebeat:8.11.0
    volumes:
      - ./monitoring/filebeat/filebeat.yml:/usr/share/filebeat/filebeat.yml
      - /var/lib/docker/containers:/var/lib/docker/containers:ro

volumes:
  postgres_data:
  redis_data:
  elastic_data:
  prometheus_data:
  grafana_data:
  ai_models:
```

### 7.2 Monitoring

#### Métricas Coletadas

**Application Metrics:**
- Request rate, latency, errors
- Database query performance
- Cache hit rates
- Queue lengths e processing time
- AI model inference time

**Business Metrics:**
- Incidents por status/prioridade
- SLA compliance rates
- MTTR (Mean Time To Resolve)
- Change success rate
- Knowledge article usage

**Infrastructure Metrics:**
- CPU, memory, disk usage
- Network I/O
- Container health
- Database connections
- Elasticsearch cluster health

#### Dashboards do Grafana

1. **System Overview Dashboard**
   - Health status de todos serviços
   - Request rates e latency
   - Error rates por serviço
   - Resource utilization

2. **ITSM Operations Dashboard**
   - Incident volume e trends
   - SLA performance
   - Team workload
   - Response times

3. **AI Service Dashboard**
   - Model performance metrics
   - Prediction accuracy
   - Processing times
   - Cache effectiveness

#### Alertas Configurados

```yaml
# prometheus/alerts.yml
groups:
  - name: itsm_alerts
    rules:
      - alert: HighErrorRate
        expr: rate(http_requests_total{status=~"5.."}[5m]) > 0.05
        annotations:
          summary: "High error rate detected"
          
      - alert: SLABreach
        expr: sla_breach_total > 0
        annotations:
          summary: "SLA breach detected"
          
      - alert: DatabaseDown
        expr: up{job="postgres"} == 0
        annotations:
          summary: "PostgreSQL is down"
```

---

## 8. Segurança

### 8.1 Autenticação e Autorização

#### Sistema de Auth

**Implementação:**
- Auth0 integration para produção
- Dev mode para desenvolvimento
- JWT tokens com refresh
- Session management

**Middleware:**
```php
// app/Http/Middleware/CheckPermission.php
public function handle($request, Closure $next, $permission)
{
    if (!$request->user()->hasPermission($permission)) {
        abort(403, 'Unauthorized action.');
    }
    return $next($request);
}
```

#### Roles e Permissions

**Roles Principais:**
- `admin` - Acesso total
- `service_desk` - Gestão de incidentes
- `change_manager` - Gestão de mudanças
- `problem_manager` - Gestão de problemas
- `knowledge_manager` - Base de conhecimento
- `user` - Usuário final

**Permissions Granulares:**
```php
// Incidents
'incidents.view', 'incidents.create', 'incidents.update', 
'incidents.delete', 'incidents.assign', 'incidents.resolve'

// Changes
'changes.view', 'changes.create', 'changes.approve',
'changes.implement', 'changes.schedule_cab'

// Knowledge
'knowledge.view', 'knowledge.create', 'knowledge.publish',
'knowledge.delete', 'knowledge.manage_categories'
```

### 8.2 Validações e Sanitização

#### Form Requests

Todas as entradas são validadas via Form Requests:

```php
// Example: StoreIncidentRequest
public function rules(): array
{
    return [
        'title' => 'required|string|max:255',
        'description' => 'required|string|min:10',
        'priority' => 'required|in:low,medium,high,critical',
        'category' => 'required|string|exists:categories,slug',
        'assigned_to' => 'nullable|exists:users,id',
        'attachments.*' => 'file|mimes:pdf,doc,docx,jpg,png|max:10240'
    ];
}
```

#### XSS/CSRF Protection

- CSRF tokens em todos os forms
- Content Security Policy headers
- XSS sanitization via HTMLPurifier
- SQL injection prevention via Eloquent

---

## 9. Testes

### 9.1 Testes Implementados

#### Backend Unit Tests

**Model Tests:**
```php
// tests/Unit/Models/IncidentTest.php
- testIncidentNumberGeneration()
- testPriorityCalculation()
- testStatusTransitions()
- testRelationships()
```

**Service Tests:**
```php
// tests/Unit/Services/NotificationServiceTest.php
- testSendNotification()
- testChannelSelection()
- testTemplateProcessing()
- testRateLimiting()
```

#### Integration Tests

```php
// tests/Integration/IncidentWorkflowTest.php
- testCompleteIncidentLifecycle()
- testEscalationProcess()
- testSLATracking()
- testNotificationFlow()
```

#### API Feature Tests

```php
// tests/Feature/Api/IncidentApiTest.php
- testListIncidentsWithFilters()
- testCreateIncidentValidation()
- testUpdateIncidentPermissions()
- testBulkOperations()
```

#### Performance Tests

```php
// tests/Performance/IncidentPerformanceTest.php
- testBulkIncidentCreation()
- testSearchPerformance()
- testConcurrentUpdates()
- testDashboardLoadTime()
```

### Frontend Tests

#### Component Unit Tests

```typescript
// tests/unit/components/IncidentCard.test.ts
describe('IncidentCard', () => {
  it('displays incident information correctly')
  it('shows correct status badge')
  it('emits click event')
  it('handles missing data gracefully')
})
```

#### Store Tests

```typescript
// tests/unit/stores/incident.test.ts
describe('Incident Store', () => {
  it('fetches incidents with filters')
  it('updates incident status')
  it('handles API errors')
  it('maintains cache correctly')
})
```

#### E2E Tests

```typescript
// tests/e2e/incident-management.spec.ts
test('Complete incident workflow', async ({ page }) => {
  await page.goto('/incidents')
  await page.click('text=New Incident')
  // ... complete workflow test
})
```

---

## 10. Funcionalidades de Negócio

### 10.1 Incident Management

#### Fluxo Completo do Ticket

```mermaid
stateDiagram-v2
    [*] --> Open: Ticket Created
    Open --> Assigned: Assign
    Assigned --> InProgress: Start Work
    InProgress --> Resolved: Resolve
    Resolved --> Closed: Close
    Resolved --> Open: Reopen
    Closed --> [*]
```

**Estados:**
- `open` - Aguardando atribuição
- `assigned` - Atribuído para técnico
- `in_progress` - Em atendimento
- `on_hold` - Aguardando informação
- `resolved` - Resolvido
- `closed` - Fechado

**SLA Implementation:**
- Response time tracking
- Resolution time tracking
- Automatic escalation
- Breach notifications
- Business hours calculation

**Automações:**
- Auto-assignment baseado em categoria
- Escalação automática por SLA
- Notificações de status
- Merge de tickets duplicados

### 10.2 Change Management

#### Workflow Engine

```php
// Workflow definition
$changeWorkflow = [
    'states' => ['draft', 'submitted', 'approved', 'scheduled', 'implementing', 'completed'],
    'transitions' => [
        'submit' => ['from' => 'draft', 'to' => 'submitted'],
        'approve' => ['from' => 'submitted', 'to' => 'approved'],
        'schedule' => ['from' => 'approved', 'to' => 'scheduled'],
        'implement' => ['from' => 'scheduled', 'to' => 'implementing'],
        'complete' => ['from' => 'implementing', 'to' => 'completed']
    ]
];
```

**Approval Process:**
- Multi-level approvals
- CAB scheduling
- Risk assessment
- Impact analysis
- Automated notifications

**Change Types:**
- Standard - Pre-approved
- Normal - Full approval
- Emergency - Fast-track
- Major - CAB required

### 10.3 Service Request

#### Catálogo de Serviços

**Estrutura:**
- Categorias hierárquicas
- Formulários dinâmicos
- SLA por serviço
- Aprovações configuráveis
- Preços e orçamento

**Request Fulfillment:**
- Task automation
- Progress tracking
- Multi-step workflows
- Integration points
- User portal

### 10.4 Problem Management

#### Root Cause Analysis

**Processo:**
1. Identificação de padrões
2. Investigação técnica
3. Análise de impacto
4. Documentação de findings
5. Solução permanente

**Known Errors:**
- Conversão automática
- Artigos de conhecimento
- Workarounds documentados
- Métricas de efetividade

### 10.5 Knowledge Base

#### Implementação

**Features:**
- Full-text search com Elasticsearch
- AI-powered suggestions
- Version control
- Rating system
- Auto-categorization
- Related articles

**AI Enhancement:**
- Content quality analysis
- Auto-tagging
- Summary generation
- Translation suggestions
- Gap identification

### 10.6 CMDB

#### Asset Management

**Types:**
- Hardware (servers, workstations)
- Software (licenses, applications)
- Network (routers, switches)
- Services (business services)
- Documentation

**Relationships:**
- Parent-child
- Dependency
- Connection
- Usage
- Support

**Discovery:**
- Manual entry
- Import tools
- API integration
- Auto-discovery agents

---

## 11. Integrações Externas

### APIs Consumidas

1. **Auth0**
   - Authentication
   - User management
   - SSO integration

2. **Twilio/SendGrid**
   - SMS notifications
   - Email delivery
   - Delivery tracking

3. **Slack/Teams**
   - Channel notifications
   - Bot integration
   - Interactive messages

### Webhooks Implementados

**Inbound:**
- Monitoring alerts
- Email-to-ticket
- Third-party updates

**Outbound:**
- Status changes
- SLA breaches
- Approval requests

### Formatos de Dados

**API Responses:**
```json
{
  "data": {
    "id": 1,
    "type": "incident",
    "attributes": {...}
  },
  "meta": {
    "total": 100,
    "per_page": 15
  },
  "links": {
    "self": "/api/v1/incidents/1",
    "next": "/api/v1/incidents?page=2"
  }
}
```

---

## 12. Performance e Otimizações

### Cache Strategies

**Multi-layer Caching:**
1. **Browser Cache** - Static assets
2. **CDN Cache** - Images, files
3. **Application Cache** - Redis
4. **Database Cache** - Query cache
5. **Search Cache** - Elasticsearch

**Cache Keys:**
```php
// Example cache usage
$incidents = Cache::remember("incidents:{$userId}:{$status}", 3600, function () {
    return Incident::where('user_id', $userId)
                   ->where('status', $status)
                   ->get();
});
```

### Query Optimizations

**Eager Loading:**
```php
$incidents = Incident::with(['reporter', 'assignee', 'comments', 'attachments'])
                    ->whereDate('created_at', '>=', now()->subDays(7))
                    ->get();
```

**Database Indexes:**
- Composite indexes on frequently filtered columns
- Partial indexes for soft deletes
- GIN indexes for JSONB columns
- Full-text indexes for search

### Background Jobs

**Queue Configuration:**
```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
]
```

**Job Types:**
- Email notifications
- Report generation
- Data imports
- AI processing
- Cleanup tasks

### Rate Limiting

```php
// routes/api.php
Route::middleware(['throttle:api'])->group(function () {
    Route::apiResource('incidents', IncidentController::class);
});

// Custom rate limits
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

---

## 13. Comandos e Scripts

### Artisan Commands

```bash
# Elasticsearch
php artisan elasticsearch:setup          # Create indexes
php artisan elasticsearch:reindex        # Reindex all data

# Notifications
php artisan notifications:initialize     # Setup templates
php artisan notifications:send-test      # Test delivery

# Maintenance
php artisan cleanup:old-logs            # Remove old logs
php artisan sla:check-breaches          # Check SLA breaches
php artisan reports:generate-monthly    # Generate reports

# Development
php artisan db:seed --class=DemoSeeder  # Load demo data
php artisan cache:warm                  # Warm up caches
```

### Scripts de Manutenção

**`scripts/backup.sh`:**
```bash
#!/bin/bash
# Database backup
pg_dump -h postgres -U itsm_user itsm > backup_$(date +%Y%m%d).sql

# File backup
tar -czf files_$(date +%Y%m%d).tar.gz storage/app/public

# Upload to S3
aws s3 cp backup_$(date +%Y%m%d).sql s3://itsm-backups/
```

**`scripts/health-check.sh`:**
```bash
#!/bin/bash
# Check all services
curl -f http://localhost/api/health || exit 1
curl -f http://localhost:9200/_cluster/health || exit 1
curl -f http://localhost:8001/health || exit 1
```

### Automações

**Cron Jobs:**
```cron
# Hourly
0 * * * * php /app/artisan schedule:run

# Daily
0 2 * * * /scripts/backup.sh
0 3 * * * php /app/artisan cleanup:old-logs

# Weekly
0 0 * * 0 php /app/artisan reports:generate-weekly
```

---

## 14. Configurações e Variáveis de Ambiente

### Variáveis .env Necessárias

```env
# Application
APP_NAME="Tezio Defender360"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://itsm.company.com

# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=itsm
DB_USERNAME=itsm_user
DB_PASSWORD=secure_password

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Elasticsearch
ELASTICSEARCH_HOST=elasticsearch:9200
ELASTICSEARCH_INDEX_PREFIX=itsm_

# AI Service
AI_SERVICE_URL=http://ai-service:8001
AI_SERVICE_API_KEY=...
OPENAI_API_KEY=...
CLAUDE_API_KEY=...

# Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=itsm-files

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=...

# Auth0
AUTH0_DOMAIN=your-tenant.auth0.com
AUTH0_CLIENT_ID=...
AUTH0_CLIENT_SECRET=...

# Monitoring
SENTRY_DSN=...
LOG_CHANNEL=stack
LOG_LEVEL=error

# Feature Flags
FEATURE_AI_SUGGESTIONS=true
FEATURE_AUTO_ASSIGNMENT=true
FEATURE_REALTIME_UPDATES=true
```

### Configurações Customizadas

**`config/itsm.php`:**
```php
return [
    'incident' => [
        'auto_close_days' => 7,
        'reopen_limit' => 3,
        'priority_matrix' => [
            'critical' => ['high', 'high'],
            'high' => ['high', 'medium'],
            'medium' => ['medium', 'medium'],
            'low' => ['low', 'low']
        ]
    ],
    
    'sla' => [
        'business_hours' => [
            'start' => '09:00',
            'end' => '18:00',
            'timezone' => 'America/Sao_Paulo'
        ]
    ],
    
    'notifications' => [
        'channels' => ['mail', 'database', 'slack'],
        'rate_limit' => 100
    ]
];
```

---

## 15. Código Exemplo

### Exemplo de Controller Completo

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreIncidentRequest;
use App\Http\Resources\V1\IncidentResource;
use App\Models\Incident;
use App\Services\IncidentService;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    protected $incidentService;
    
    public function __construct(IncidentService $incidentService)
    {
        $this->incidentService = $incidentService;
        $this->authorizeResource(Incident::class, 'incident');
    }
    
    public function index(Request $request)
    {
        $incidents = Incident::with(['reporter', 'assignee'])
            ->filter($request->all())
            ->paginate($request->per_page ?? 15);
            
        return IncidentResource::collection($incidents);
    }
    
    public function store(StoreIncidentRequest $request)
    {
        $incident = $this->incidentService->create($request->validated());
        
        return new IncidentResource($incident);
    }
}
```

### Exemplo de Component Vue

```vue
<template>
  <div class="incident-card" @click="$emit('click', incident)">
    <div class="incident-header">
      <h3>{{ incident.title }}</h3>
      <StatusBadge :status="incident.status" />
    </div>
    <div class="incident-meta">
      <PriorityBadge :priority="incident.priority" />
      <span class="incident-number">{{ incident.incident_number }}</span>
    </div>
    <div class="incident-assignee" v-if="incident.assignee">
      <img :src="incident.assignee.avatar" :alt="incident.assignee.name">
      <span>{{ incident.assignee.name }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { defineProps } from 'vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import PriorityBadge from '@/components/common/PriorityBadge.vue'
import type { Incident } from '@/types'

defineProps<{
  incident: Incident
}>()
</script>
```

### Exemplo de Service

```python
# ai-service/app/services/ticket_analyzer.py
from typing import Dict, List, Any
import spacy
from transformers import pipeline

class TicketAnalyzer:
    def __init__(self):
        self.nlp = spacy.load("en_core_web_sm")
        self.sentiment_analyzer = pipeline("sentiment-analysis")
    
    async def analyze(self, ticket_data: Dict[str, Any]) -> Dict[str, Any]:
        text = f"{ticket_data['title']} {ticket_data['description']}"
        
        # Extract entities
        doc = self.nlp(text)
        entities = self._extract_entities(doc)
        
        # Analyze sentiment
        sentiment = self.sentiment_analyzer(text)[0]
        
        # Calculate urgency
        urgency_score = self._calculate_urgency(
            sentiment=sentiment,
            keywords=self._extract_keywords(doc),
            priority=ticket_data.get('priority', 'medium')
        )
        
        return {
            'entities': entities,
            'sentiment': sentiment,
            'urgency_score': urgency_score,
            'suggested_category': self._suggest_category(doc),
            'suggested_priority': self._suggest_priority(urgency_score)
        }
```

### Exemplo de Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('incident_number', 20)->unique();
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['open', 'assigned', 'in_progress', 'resolved', 'closed'])
                  ->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])
                  ->default('medium');
            $table->foreignId('reported_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'priority']);
            $table->index(['assigned_to', 'status']);
        });
    }
};
```

---

## Conclusão

O **Tezio Defender360 ITSM** é uma plataforma completa e moderna para gestão de serviços de TI, implementada com as melhores práticas e tecnologias atuais. O sistema oferece:

- ✅ **Arquitetura escalável** com microserviços e containerização
- ✅ **Stack moderna** com Laravel 11, Vue.js 3, e FastAPI
- ✅ **IA integrada** para automação e insights
- ✅ **Multi-tenancy** com isolamento completo
- ✅ **Monitoramento completo** com Prometheus e Grafana
- ✅ **Segurança robusta** com autenticação, autorização e validação
- ✅ **Performance otimizada** com cache multi-camada e indexação
- ✅ **Testes abrangentes** com alta cobertura
- ✅ **Documentação completa** para usuários e desenvolvedores

O projeto está pronto para implantação em produção e pode escalar conforme a necessidade da organização.