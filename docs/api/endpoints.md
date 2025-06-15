# 📡 API Endpoints - ITSM Platform

## Visão Geral

A API do ITSM Platform segue os princípios RESTful e está organizada por domínios de negócio. Todos os endpoints requerem autenticação via Bearer Token (JWT) obtido através do Auth0.

**Base URL**: `https://api.itsm-platform.com/v1`  
**Format**: JSON  
**Authentication**: Bearer Token (JWT)

## Autenticação

Todos os requests devem incluir o header:
```
Authorization: Bearer YOUR_JWT_TOKEN
```

### Obter Token
```javascript
// Frontend example
const token = await auth0Client.getAccessTokenSilently({
  audience: 'https://api.itsm-platform.com',
  scope: 'read:tickets write:tickets'
});
```

## Convenções

### HTTP Status Codes
- `200 OK` - Sucesso
- `201 Created` - Recurso criado
- `204 No Content` - Sucesso sem retorno
- `400 Bad Request` - Erro de validação
- `401 Unauthorized` - Token inválido/expirado
- `403 Forbidden` - Sem permissão
- `404 Not Found` - Recurso não encontrado
- `422 Unprocessable Entity` - Dados inválidos
- `429 Too Many Requests` - Rate limit excedido
- `500 Internal Server Error` - Erro do servidor

### Paginação
```
GET /api/tickets?page=2&per_page=50
```

Response headers:
```
X-Total-Count: 245
X-Page: 2
X-Per-Page: 50
Link: <...?page=3>; rel="next", <...?page=1>; rel="prev"
```

### Filtros e Ordenação
```
GET /api/tickets?status=open&priority=high&sort=-created_at
```

### Rate Limiting
```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1640995200
```

## 🎫 Tickets (Incidents & Service Requests)

### Listar Tickets
```http
GET /api/tickets
```

**Query Parameters:**
- `type` - `incident` | `service_request`
- `status` - `new` | `open` | `pending` | `resolved` | `closed`
- `priority` - `low` | `medium` | `high` | `critical`
- `assigned_to` - User ID
- `category_id` - Category ID
- `search` - Full text search
- `created_after` - ISO 8601 date
- `created_before` - ISO 8601 date
- `sort` - Campo para ordenação (prefix `-` para DESC)
- `page` - Página atual (default: 1)
- `per_page` - Items por página (default: 20, max: 100)

**Response:**
```json
{
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "type": "incident",
      "number": "INC0001234",
      "title": "Email service down",
      "description": "Users cannot access email...",
      "status": "open",
      "priority": "high",
      "category": {
        "id": "cat-001",
        "name": "Email Services"
      },
      "requester": {
        "id": "user-123",
        "name": "John Doe",
        "email": "john@company.com"
      },
      "assigned_to": {
        "id": "user-456",
        "name": "Jane Smith",
        "email": "jane@support.com"
      },
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-15T11:45:00Z",
      "sla": {
        "response_target": "2024-01-15T11:30:00Z",
        "response_met": true,
        "resolution_target": "2024-01-15T14:30:00Z",
        "resolution_met": null
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 13,
    "per_page": 20,
    "to": 20,
    "total": 245
  }
}
```

### Criar Ticket
```http
POST /api/tickets
```

**Request Body:**
```json
{
  "type": "incident",
  "title": "Cannot access shared drive",
  "description": "Getting error when trying to access \\\\fileserver\\shared",
  "priority": "medium",
  "category_id": "cat-002",
  "requester_id": "user-789",
  "affected_ci_ids": ["ci-001", "ci-002"],
  "attachments": [
    {
      "filename": "error-screenshot.png",
      "content": "base64-encoded-content",
      "mime_type": "image/png"
    }
  ]
}
```

**Response:** `201 Created`
```json
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440001",
    "number": "INC0001235",
    "status": "new",
    // ... resto do objeto ticket
  }
}
```

### Obter Ticket
```http
GET /api/tickets/{id}
```

**Response:**
```json
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    // ... todos os campos do ticket
    "comments": [
      {
        "id": "comment-001",
        "body": "Initial investigation shows...",
        "author": {
          "id": "user-456",
          "name": "Jane Smith"
        },
        "created_at": "2024-01-15T11:00:00Z",
        "is_internal": false
      }
    ],
    "attachments": [
      {
        "id": "att-001",
        "filename": "error-log.txt",
        "size": 2048,
        "mime_type": "text/plain",
        "url": "/api/tickets/550e8400/attachments/att-001"
      }
    ],
    "history": [
      {
        "id": "hist-001",
        "field": "status",
        "old_value": "new",
        "new_value": "open",
        "changed_by": {
          "id": "user-456",
          "name": "Jane Smith"
        },
        "changed_at": "2024-01-15T10:45:00Z"
      }
    ]
  }
}
```

### Atualizar Ticket
```http
PATCH /api/tickets/{id}
```

**Request Body:**
```json
{
  "status": "resolved",
  "resolution": "Restarted email service",
  "priority": "low"
}
```

**Response:** `200 OK`

### Adicionar Comentário
```http
POST /api/tickets/{id}/comments
```

**Request Body:**
```json
{
  "body": "Issue has been resolved by restarting the service.",
  "is_internal": false
}
```

### Upload de Anexo
```http
POST /api/tickets/{id}/attachments
```

**Request:** `multipart/form-data`
- `file` - Arquivo para upload
- `description` - Descrição opcional

**Response:**
```json
{
  "data": {
    "id": "att-002",
    "filename": "solution-steps.pdf",
    "size": 102400,
    "mime_type": "application/pdf",
    "url": "/api/tickets/550e8400/attachments/att-002"
  }
}
```

## 🤖 AI/ML Endpoints

### Obter Sugestões de Resolução
```http
POST /api/ai/suggestions
```

**Request Body:**
```json
{
  "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
  "include_similar": true,
  "max_suggestions": 5
}
```

**Response:**
```json
{
  "data": {
    "suggestions": [
      {
        "confidence": 0.92,
        "resolution": "Restart the email service using...",
        "steps": [
          "1. Connect to email server",
          "2. Run command: service email restart",
          "3. Verify service is running"
        ],
        "similar_tickets": [
          {
            "id": "ticket-123",
            "title": "Email down - resolved",
            "resolution": "Service restart",
            "resolved_at": "2024-01-10T15:30:00Z"
          }
        ]
      }
    ],
    "category_suggestion": {
      "category_id": "cat-001",
      "confidence": 0.88
    },
    "priority_suggestion": {
      "priority": "high",
      "reason": "Multiple users affected, business critical service"
    }
  }
}
```

### Classificar Ticket
```http
POST /api/ai/classify
```

**Request Body:**
```json
{
  "title": "Cannot print to network printer",
  "description": "Printer shows offline but is powered on..."
}
```

**Response:**
```json
{
  "data": {
    "category": {
      "id": "cat-005",
      "name": "Printing Services",
      "confidence": 0.85
    },
    "priority": {
      "level": "medium",
      "confidence": 0.78
    },
    "tags": ["printer", "network", "connectivity"],
    "sentiment": "neutral"
  }
}
```

### Análise de Tendências
```http
GET /api/ai/analytics/trends
```

**Query Parameters:**
- `period` - `day` | `week` | `month` | `quarter`
- `metric` - `volume` | `resolution_time` | `categories`

**Response:**
```json
{
  "data": {
    "period": "week",
    "trends": [
      {
        "date": "2024-01-15",
        "ticket_count": 45,
        "avg_resolution_time": 3.2,
        "top_categories": [
          {"name": "Email", "count": 15},
          {"name": "Network", "count": 12}
        ]
      }
    ],
    "predictions": {
      "next_period_volume": 52,
      "confidence": 0.82
    }
  }
}
```

## 👥 Usuários e Equipes

### Listar Usuários
```http
GET /api/users
```

**Query Parameters:**
- `role` - `admin` | `agent` | `user`
- `team_id` - ID da equipe
- `active` - `true` | `false`
- `search` - Busca por nome/email

### Obter Usuário
```http
GET /api/users/{id}
```

### Obter Perfil Atual
```http
GET /api/users/me
```

**Response:**
```json
{
  "data": {
    "id": "user-123",
    "name": "John Doe",
    "email": "john@company.com",
    "role": "agent",
    "teams": [
      {
        "id": "team-001",
        "name": "Level 1 Support"
      }
    ],
    "permissions": [
      "tickets.view",
      "tickets.create",
      "tickets.update",
      "tickets.assign"
    ],
    "preferences": {
      "language": "en",
      "timezone": "America/New_York",
      "notifications": {
        "email": true,
        "desktop": true
      }
    }
  }
}
```

### Atualizar Preferências
```http
PATCH /api/users/me/preferences
```

**Request Body:**
```json
{
  "language": "pt-BR",
  "notifications": {
    "email": false
  }
}
```

## 📊 Dashboards e Relatórios

### Obter Métricas do Dashboard
```http
GET /api/dashboard/metrics
```

**Query Parameters:**
- `period` - `today` | `week` | `month` | `custom`
- `start_date` - ISO 8601 (se period=custom)
- `end_date` - ISO 8601 (se period=custom)
- `team_id` - Filtrar por equipe

**Response:**
```json
{
  "data": {
    "tickets": {
      "total": 245,
      "open": 67,
      "resolved_today": 23,
      "avg_resolution_time": 4.5
    },
    "sla": {
      "compliance_rate": 0.94,
      "breached_tickets": 4,
      "at_risk": 12
    },
    "categories": [
      {
        "name": "Email Services",
        "count": 45,
        "percentage": 0.18
      }
    ],
    "team_performance": [
      {
        "team": "Level 1 Support",
        "tickets_resolved": 120,
        "avg_time": 2.3,
        "satisfaction": 4.6
      }
    ]
  }
}
```

### Gerar Relatório
```http
POST /api/reports/generate
```

**Request Body:**
```json
{
  "type": "sla_compliance",
  "format": "pdf",
  "period": "month",
  "filters": {
    "team_id": "team-001",
    "category_ids": ["cat-001", "cat-002"]
  },
  "email_to": "manager@company.com"
}
```

**Response:**
```json
{
  "data": {
    "report_id": "report-123",
    "status": "processing",
    "estimated_time": 30,
    "url": null
  }
}
```

### Verificar Status do Relatório
```http
GET /api/reports/{report_id}/status
```

**Response:**
```json
{
  "data": {
    "report_id": "report-123",
    "status": "completed",
    "url": "/api/reports/report-123/download",
    "expires_at": "2024-01-16T10:00:00Z"
  }
}
```

## 🔧 Administração

### Categorias

#### Listar Categorias
```http
GET /api/admin/categories
```

#### Criar Categoria
```http
POST /api/admin/categories
```

**Request Body:**
```json
{
  "name": "Database Issues",
  "parent_id": "cat-001",
  "description": "All database related problems",
  "sla_response_hours": 2,
  "sla_resolution_hours": 8
}
```

### SLA Policies

#### Listar Políticas SLA
```http
GET /api/admin/sla-policies
```

#### Criar Política SLA
```http
POST /api/admin/sla-policies
```

**Request Body:**
```json
{
  "name": "Critical Priority SLA",
  "conditions": {
    "priority": ["critical"],
    "categories": ["cat-001", "cat-002"]
  },
  "targets": {
    "response_time": 1,
    "resolution_time": 4
  },
  "business_hours_only": false
}
```

### Workflows

#### Listar Workflows
```http
GET /api/admin/workflows
```

#### Obter Workflow
```http
GET /api/admin/workflows/{id}
```

### Integrações

#### Listar Integrações
```http
GET /api/admin/integrations
```

**Response:**
```json
{
  "data": [
    {
      "id": "int-001",
      "name": "Datto RMM",
      "type": "rmm",
      "status": "active",
      "last_sync": "2024-01-15T10:00:00Z",
      "config": {
        "sync_interval": 300,
        "auto_create_tickets": true
      }
    }
  ]
}
```

#### Testar Integração
```http
POST /api/admin/integrations/{id}/test
```

## 🔍 Knowledge Base

### Buscar Artigos
```http
GET /api/knowledge/articles/search
```

**Query Parameters:**
- `q` - Query de busca
- `category_id` - Filtrar por categoria
- `tags` - Filtrar por tags (comma-separated)

### Obter Artigo
```http
GET /api/knowledge/articles/{id}
```

### Avaliar Artigo
```http
POST /api/knowledge/articles/{id}/feedback
```

**Request Body:**
```json
{
  "helpful": true,
  "comment": "This solved my problem"
}
```

## 🪝 Webhooks

### Listar Webhooks
```http
GET /api/webhooks
```

### Criar Webhook
```http
POST /api/webhooks
```

**Request Body:**
```json
{
  "name": "Slack Notification",
  "url": "https://hooks.slack.com/services/...",
  "events": ["ticket.created", "ticket.resolved"],
  "active": true,
  "secret": "webhook-secret-key"
}
```

### Eventos Disponíveis
- `ticket.created`
- `ticket.updated`
- `ticket.resolved`
- `ticket.closed`
- `ticket.assigned`
- `comment.added`
- `sla.breached`
- `user.created`
- `user.updated`

## 🔒 Rate Limiting

A API implementa rate limiting por tenant:

- **Padrão**: 1000 requests/hora
- **Endpoints AI**: 100 requests/hora
- **Uploads**: 50 requests/hora
- **Reports**: 10 requests/hora

Limites customizados podem ser configurados por tenant.

## 📝 Exemplos de Código

### JavaScript/TypeScript
```typescript
// Cliente API com Axios
import axios from 'axios';
import { auth0 } from './auth';

const api = axios.create({
  baseURL: 'https://api.itsm-platform.com/v1',
  headers: {
    'Content-Type': 'application/json'
  }
});

// Interceptor para adicionar token
api.interceptors.request.use(async (config) => {
  const token = await auth0.getAccessTokenSilently();
  config.headers.Authorization = `Bearer ${token}`;
  return config;
});

// Criar ticket
async function createTicket(data: CreateTicketDTO) {
  const response = await api.post('/tickets', data);
  return response.data;
}

// Listar tickets com filtros
async function listTickets(filters: TicketFilters) {
  const response = await api.get('/tickets', { params: filters });
  return response.data;
}
```

### Python
```python
import httpx
from typing import Dict, Any

class ITSMClient:
    def __init__(self, base_url: str, get_token_func):
        self.base_url = base_url
        self.get_token = get_token_func
        self.client = httpx.AsyncClient()
    
    async def _request(self, method: str, path: str, **kwargs) -> Dict[str, Any]:
        token = await self.get_token()
        headers = {
            "Authorization": f"Bearer {token}",
            "Content-Type": "application/json"
        }
        
        response = await self.client.request(
            method,
            f"{self.base_url}{path}",
            headers=headers,
            **kwargs
        )
        response.raise_for_status()
        return response.json()
    
    async def create_ticket(self, data: Dict[str, Any]) -> Dict[str, Any]:
        return await self._request("POST", "/tickets", json=data)
    
    async def get_ai_suggestions(self, ticket_id: str) -> Dict[str, Any]:
        return await self._request(
            "POST", 
            "/ai/suggestions",
            json={"ticket_id": ticket_id}
        )
```

### cURL
```bash
# Criar ticket
curl -X POST https://api.itsm-platform.com/v1/tickets \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "incident",
    "title": "Server down",
    "description": "Production server not responding",
    "priority": "critical"
  }'

# Buscar tickets
curl -X GET "https://api.itsm-platform.com/v1/tickets?status=open&priority=high" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🚨 Tratamento de Erros

Todos os erros seguem o formato:

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "title": ["The title field is required."],
      "priority": ["Invalid priority value."]
    }
  },
  "request_id": "req_550e8400-e29b-41d4",
  "timestamp": "2024-01-15T10:30:00Z"
}
```

### Códigos de Erro Comuns
- `AUTHENTICATION_ERROR` - Problema com autenticação
- `AUTHORIZATION_ERROR` - Sem permissão
- `VALIDATION_ERROR` - Dados inválidos
- `NOT_FOUND` - Recurso não encontrado
- `RATE_LIMIT_EXCEEDED` - Limite de requests excedido
- `INTERNAL_ERROR` - Erro interno do servidor

## 📚 Recursos Adicionais

- [Postman Collection](https://www.postman.com/itsm-platform/workspace/api)
- [OpenAPI Specification](/api/openapi.json)
- [GraphQL Playground](/api/graphql)
- [WebSocket Events](/docs/websockets.md)
- [SDK Documentation](/docs/sdk/)
