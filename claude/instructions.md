# 🤖 Instruções para Claude AI - ITSM Platform

## Visão Geral do Projeto

Você está trabalhando no **ITSM Platform**, uma plataforma enterprise de gestão de serviços de TI com as seguintes características principais:

- **Multi-tenant**: Isolamento completo de dados por empresa
- **ITIL v4**: Processos padronizados de gestão de serviços
- **Arquitetura Híbrida**: Laravel (PHP) + FastAPI (Python) + Vue.js
- **IA Integrada**: Claude AI para automação e análise preditiva
- **Segurança Zero-Trust**: Backend isolado sem acesso direto à internet

## Estrutura do Projeto

```
itsm-platform/
├── backend/          # Laravel 11 API (PHP 8.3)
├── frontend/         # Vue.js 3 + TypeScript
├── ai-service/       # FastAPI + ML (Python 3.11)
├── docs/            # Documentação
├── assets/          # Recursos visuais
└── docker/          # Configurações Docker
```

## Padrões de Código

### Backend (Laravel)

1. **Arquitetura**: Domain-Driven Design (DDD)
   ```php
   app/
   ├── Domains/
   │   ├── Incident/
   │   │   ├── Models/
   │   │   ├── Services/
   │   │   ├── Actions/
   │   │   └── Repositories/
   │   └── ServiceRequest/
   └── Core/
       ├── Tenant/
       └── Auth/
   ```

2. **Convenções**:
   - PSR-12 coding standards
   - Type hints obrigatórios
   - Actions para lógica de negócio
   - Repository pattern para queries
   - Form Requests para validação

3. **Exemplo de Action**:
   ```php
   namespace App\Domains\Incident\Actions;
   
   class CreateIncidentAction
   {
       public function execute(CreateIncidentData $data): Incident
       {
           // Lógica aqui
       }
   }
   ```

### Frontend (Vue.js)

1. **Estrutura**:
   ```
   src/
   ├── modules/          # Módulos por domínio
   ├── components/       # Componentes reutilizáveis
   ├── composables/      # Composition API hooks
   ├── stores/          # Pinia stores
   └── services/        # API clients
   ```

2. **Convenções**:
   - Composition API + TypeScript
   - Props com tipos definidos
   - Emits documentados
   - Componentes com `<script setup>`

3. **Exemplo de Componente**:
   ```vue
   <script setup lang="ts">
   interface Props {
     incident: Incident
     readonly?: boolean
   }
   
   const props = defineProps<Props>()
   const emit = defineEmits<{
     update: [incident: Incident]
   }>()
   </script>
   ```

### AI Service (Python)

1. **Estrutura**:
   ```
   app/
   ├── api/           # FastAPI routes
   ├── ml/            # Machine Learning models
   ├── services/      # Business logic
   └── integrations/  # External APIs
   ```

2. **Convenções**:
   - Type hints com Pydantic
   - Async/await para I/O
   - Dependency injection
   - Docstrings completas

## Princípios de Design

### 1. Multi-tenancy First
- Sempre incluir `tenant_id` em queries
- Usar middleware de tenant isolation
- Validar acesso cross-tenant

### 2. ITIL Compliance
- Seguir nomenclatura ITIL (Incident, Problem, Change)
- Implementar workflows padrão
- Manter auditoria completa

### 3. Security by Design
- Nunca expor IDs internos
- Validar e sanitizar todas as entradas
- Usar UUIDs ao invés de IDs incrementais
- Implementar rate limiting

### 4. Performance
- Eager loading para evitar N+1
- Cache estratégico com Redis
- Paginação obrigatória
- Índices otimizados

## Tarefas Comuns

### Criar um Novo Módulo ITIL

1. **Backend**:
   ```bash
   # Criar estrutura do domínio
   php artisan make:domain Problem
   
   # Criar migration
   php artisan make:migration create_problems_table
   
   # Criar modelo com relationships
   php artisan make:model Domain/Problem/Models/Problem
   ```

2. **Frontend**:
   ```bash
   # Criar módulo Vue
   mkdir -p src/modules/problem-management
   
   # Estrutura padrão
   ├── components/
   ├── views/
   ├── stores/
   └── services/
   ```

### Implementar Feature com IA

1. **Definir o serviço Python**:
   ```python
   # ai-service/app/services/problem_analyzer.py
   class ProblemAnalyzer:
       async def analyze_incidents(self, incidents: List[Incident]) -> ProblemSuggestion:
           # ML logic aqui
   ```

2. **Integrar no Laravel**:
   ```php
   // backend/app/Services/AiIntegrationService.php
   public function analyzeProblem(array $incidentIds): array
   {
       return Http::post('http://ai-service:8000/analyze-problem', [
           'incident_ids' => $incidentIds
       ])->json();
   }
   ```

### Adicionar Integração Externa

1. **Criar Integration Hub**:
   ```php
   // backend/app/Integrations/Datto/DattoClient.php
   class DattoClient extends BaseIntegration
   {
       public function getDevices(): Collection
       {
           // Implementação segura
       }
   }
   ```

2. **Configurar no .env**:
   ```env
   DATTO_API_KEY=xxx
   DATTO_API_URL=https://api.datto.com
   ```

## Comandos Úteis

### Development
```bash
# Backend
composer install
php artisan serve
php artisan migrate:fresh --seed
php artisan test

# Frontend
npm install
npm run dev
npm run build
npm run test

# Python
pip install -r requirements.txt
uvicorn main:app --reload
pytest

# Docker
docker-compose up -d
docker-compose logs -f
docker-compose exec laravel bash
```

### Debugging
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Tinker
php artisan tinker

# Vue DevTools
# Instalar extensão do browser

# Python debugging
python -m pdb main.py
```

## Processo de Deploy

1. **Local → Staging**:
   - Testes passando
   - Code review aprovado
   - Migrations testadas

2. **Staging → Production**:
   - QA aprovado
   - Performance validada
   - Rollback plan definido

## Recursos Importantes

### Documentação
- [Blueprint Completo](../../../blueprint.md)
- [PRD Enterprise](../../../prd_itsm_platform_multi_tenant.md)
- [Setup Local](../setup/local-setup.md)
- [API Docs](../api/README.md)

### Design System
- Figma: [Link do Figma]
- Tailwind Config: `frontend/tailwind.config.js`
- Componentes: `frontend/src/components/design-system/`

### Segurança
- OWASP Top 10 compliance
- Pen test reports em `docs/security/`
- Incident response plan

## Dicas para Claude

1. **Sempre considere multi-tenancy** em qualquer código
2. **Use type hints** em PHP e Python
3. **Implemente testes** junto com o código
4. **Documente decisões** importantes
5. **Siga os padrões** estabelecidos no projeto
6. **Priorize segurança** sobre conveniência
7. **Otimize queries** para grandes volumes
8. **Mantenha logs** estruturados

## Contatos

- **Tech Lead**: [email]
- **DevOps**: [email]
- **Security**: [email]
- **Slack**: #itsm-platform-dev

---

*Última atualização: [Data]*
