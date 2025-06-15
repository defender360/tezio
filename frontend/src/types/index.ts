// Base types
export interface User {
  id: string
  name: string
  email: string
  role: 'admin' | 'agent' | 'user'
  avatar?: string
  tenant_id: string
}

export interface Tenant {
  id: string
  name: string
  subdomain: string
  settings: Record<string, any>
}

// Incident types
export type IncidentPriority = 'critical' | 'high' | 'medium' | 'low'
export type IncidentStatus = 'new' | 'assigned' | 'in_progress' | 'pending' | 'resolved' | 'closed'
export type IncidentImpact = 'critical' | 'high' | 'medium' | 'low'
export type IncidentUrgency = 'critical' | 'high' | 'medium' | 'low'

export interface Incident {
  id: string
  number: string
  title: string
  description: string
  priority: IncidentPriority
  impact: IncidentImpact
  urgency: IncidentUrgency
  status: IncidentStatus
  category_id?: string
  assigned_to?: string
  assigned_user?: User
  created_by: string
  created_by_user?: User
  sla_response_target: string
  sla_resolution_target: string
  resolved_at?: string
  closed_at?: string
  created_at: string
  updated_at: string
  comments_count?: number
  attachments_count?: number
  is_overdue?: boolean
}

export interface IncidentComment {
  id: string
  incident_id: string
  user_id: string
  user?: User
  body: string
  is_internal: boolean
  created_at: string
}

export interface CreateIncidentData {
  title: string
  description: string
  priority: IncidentPriority
  impact: IncidentImpact
  urgency: IncidentUrgency
  category_id?: string
  assigned_to?: string
}

export interface DashboardMetrics {
  total_incidents: number
  open_incidents: number
  overdue_incidents: number
  avg_resolution_time: number
  sla_compliance: number
  incidents_today: number
}