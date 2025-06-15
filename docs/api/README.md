# ITSM Platform API Documentation

## Overview

The ITSM Platform API is a RESTful API that provides comprehensive access to all IT Service Management functionality. The API follows REST principles and uses JSON for request and response payloads.

## Base URL

```
https://api.itsm-platform.com/api/v1
```

For local development:
```
http://localhost:8000/api/v1
```

## Authentication

The API uses Auth0 for authentication. All requests must include a valid JWT token in the Authorization header:

```
Authorization: Bearer <your-jwt-token>
```

### Obtaining a Token

1. Register your application with Auth0
2. Use the Auth0 authentication flow to obtain an access token
3. Include the token in all API requests

## Common Headers

| Header | Value | Description |
|--------|-------|-------------|
| Content-Type | application/json | Required for POST/PUT requests |
| Accept | application/json | Recommended for all requests |
| X-Tenant-ID | {tenant-id} | Optional tenant override (admin only) |

## Response Format

All API responses follow a consistent format:

### Success Response

```json
{
  "data": {
    // Response data
  },
  "meta": {
    // Pagination or additional metadata
  },
  "links": {
    // HATEOAS links
  }
}
```

### Error Response

```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable error message",
    "details": {
      // Additional error details
    }
  }
}
```

## Status Codes

| Code | Description |
|------|-------------|
| 200 | OK - Request succeeded |
| 201 | Created - Resource created successfully |
| 204 | No Content - Request succeeded with no response body |
| 400 | Bad Request - Invalid request data |
| 401 | Unauthorized - Authentication required |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource not found |
| 422 | Unprocessable Entity - Validation failed |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error - Server error |

## Pagination

List endpoints support pagination using the following query parameters:

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| page | integer | 1 | Page number |
| per_page | integer | 20 | Items per page (max: 100) |
| sort_by | string | created_at | Field to sort by |
| sort_order | string | desc | Sort order (asc/desc) |

### Pagination Response

```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 20,
    "to": 20,
    "total": 95
  },
  "links": {
    "first": "https://api.itsm-platform.com/api/v1/incidents?page=1",
    "last": "https://api.itsm-platform.com/api/v1/incidents?page=5",
    "prev": null,
    "next": "https://api.itsm-platform.com/api/v1/incidents?page=2"
  }
}
```

## Filtering

Most list endpoints support filtering:

```
GET /api/v1/incidents?status=new,in_progress&priority=high,critical&created_after=2024-01-01
```

## Rate Limiting

API requests are rate limited to ensure fair usage:

- **Authenticated requests**: 1000 requests per hour
- **Unauthenticated requests**: 60 requests per hour

Rate limit information is included in response headers:

```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1640995200
```

## Endpoints

### Incidents

#### List Incidents
```http
GET /api/v1/incidents
```

Query parameters:
- `status` - Filter by status (comma-separated)
- `priority` - Filter by priority (comma-separated)
- `assigned_to` - Filter by assignee ID
- `search` - Search in title and description
- `created_after` - Filter by creation date
- `created_before` - Filter by creation date

#### Get Incident
```http
GET /api/v1/incidents/{id}
```

#### Create Incident
```http
POST /api/v1/incidents
```

Request body:
```json
{
  "title": "Server is down",
  "description": "Production server is not responding",
  "impact": "high",
  "urgency": "high",
  "category": "infrastructure",
  "subcategory": "server"
}
```

#### Update Incident
```http
PUT /api/v1/incidents/{id}
```

#### Assign Incident
```http
POST /api/v1/incidents/{id}/assign
```

Request body:
```json
{
  "user_id": "123",
  "group": "Level 2 Support"
}
```

#### Add Comment
```http
POST /api/v1/incidents/{id}/comments
```

Request body:
```json
{
  "content": "Investigation in progress",
  "is_private": false
}
```

### Changes

#### List Changes
```http
GET /api/v1/changes
```

#### Create Change Request
```http
POST /api/v1/changes
```

Request body:
```json
{
  "title": "Upgrade database server",
  "description": "Upgrade PostgreSQL from 14 to 16",
  "type": "standard",
  "risk": "medium",
  "impact": "medium",
  "implementation_plan": "...",
  "backout_plan": "...",
  "scheduled_start": "2024-02-01T02:00:00Z",
  "scheduled_end": "2024-02-01T06:00:00Z"
}
```

### Knowledge Base

#### Search Articles
```http
GET /api/v1/knowledge/search?q=password+reset
```

#### Get Article
```http
GET /api/v1/knowledge/articles/{id}
```

#### Create Article
```http
POST /api/v1/knowledge/articles
```

### Analytics

#### Incident Analytics
```http
GET /api/v1/analytics/incidents?period=30days
```

#### SLA Performance
```http
GET /api/v1/analytics/sla?period=30days
```

## Webhooks

Configure webhooks to receive real-time notifications:

```http
POST /api/v1/webhooks
```

Request body:
```json
{
  "url": "https://your-app.com/webhook",
  "events": ["incident.created", "incident.resolved"],
  "secret": "your-webhook-secret"
}
```

## API Clients

### JavaScript/TypeScript

```typescript
import { ITSMClient } from '@itsm-platform/client';

const client = new ITSMClient({
  apiKey: 'your-api-key',
  baseURL: 'https://api.itsm-platform.com'
});

// List incidents
const incidents = await client.incidents.list({
  status: ['new', 'in_progress'],
  priority: 'high'
});

// Create incident
const incident = await client.incidents.create({
  title: 'Server down',
  description: 'Cannot access server',
  impact: 'high',
  urgency: 'high'
});
```

### Python

```python
from itsm_platform import Client

client = Client(
    api_key='your-api-key',
    base_url='https://api.itsm-platform.com'
)

# List incidents
incidents = client.incidents.list(
    status=['new', 'in_progress'],
    priority='high'
)

# Create incident
incident = client.incidents.create(
    title='Server down',
    description='Cannot access server',
    impact='high',
    urgency='high'
)
```

### cURL

```bash
# List incidents
curl -X GET "https://api.itsm-platform.com/api/v1/incidents" \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"

# Create incident
curl -X POST "https://api.itsm-platform.com/api/v1/incidents" \
  -H "Authorization: Bearer your-token" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Server down",
    "description": "Cannot access server",
    "impact": "high",
    "urgency": "high"
  }'
```

## Testing

Use our API sandbox for testing:

- Base URL: `https://sandbox.itsm-platform.com/api/v1`
- Test credentials available in the developer portal

## Support

- Email: api-support@itsm-platform.com
- Developer Portal: https://developers.itsm-platform.com
- Status Page: https://status.itsm-platform.com