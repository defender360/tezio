import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { api } from '@/services/api'

export interface KPI {
  current: number
  previous: number
  change_percent: number
}

export interface DashboardKPIs {
  total_tickets: KPI
  resolution_rate: KPI
  avg_resolution_time: KPI
  customer_satisfaction: KPI
  sla_compliance: KPI
  cost_per_ticket: KPI
}

export interface ExecutiveDashboard {
  kpis: DashboardKPIs
  trends: any
  distributions: any
  top_issues: any[]
  team_performance: any[]
  sla_overview: any
  change_success_rate: any
  cost_analysis: any
  risk_assessment: any
}

export interface OperationalDashboard {
  active_incidents: any[]
  pending_changes: any[]
  workload_distribution: any
  queue_metrics: any
  response_times: any
  escalations: any
  service_health: any
  real_time_activity: any
}

export interface TeamDashboard {
  team_metrics: any
  individual_performance: any[]
  workload_balance: any
  skill_matrix: any
  training_needs: any[]
  team_sla_performance: any[]
}

export interface AnalyticsFilters {
  start_date?: string
  end_date?: string
  team_id?: string
  category?: string
  priority?: string
  service_id?: string
  group_by?: string
}

export const useAnalyticsStore = defineStore('analytics', () => {
  // State
  const executiveDashboard = ref<ExecutiveDashboard | null>(null)
  const operationalDashboard = ref<OperationalDashboard | null>(null)
  const teamDashboard = ref<TeamDashboard | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  
  // Real-time metrics
  const realtimeMetrics = ref({
    active_incidents: 0,
    queue_length: 0,
    sla_at_risk: 0,
    online_users: 0,
    response_times: {}
  })

  // Actions
  const getExecutiveDashboard = async (filters: AnalyticsFilters = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/dashboard/executive', { params: filters })
      executiveDashboard.value = response.data.data
      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to load executive dashboard'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getOperationalDashboard = async (filters: AnalyticsFilters = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/dashboard/operational', { params: filters })
      operationalDashboard.value = response.data.data
      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to load operational dashboard'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getTeamDashboard = async (filters: AnalyticsFilters & { team_id: string }) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/dashboard/team', { params: filters })
      teamDashboard.value = response.data.data
      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to load team dashboard'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getIncidentAnalytics = async (filters: AnalyticsFilters) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/analytics/incidents', { params: filters })
      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to load incident analytics'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getChangeAnalytics = async (filters: AnalyticsFilters) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/analytics/changes', { params: filters })
      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to load change analytics'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getSLAAnalytics = async (filters: AnalyticsFilters) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/analytics/sla', { params: filters })
      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to load SLA analytics'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getWorkloadAnalytics = async (filters: AnalyticsFilters) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/analytics/workload', { params: filters })
      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to load workload analytics'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getPredictiveAnalytics = async (type: string, days: number = 30) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/analytics/predictive', { 
        params: { type, days } 
      })
      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to load predictive analytics'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getRealTimeMetrics = async (metrics?: string[]) => {
    try {
      const response = await api.get('/analytics/realtime', { 
        params: { metrics } 
      })
      realtimeMetrics.value = response.data.data
      return response.data
    } catch (err: any) {
      console.error('Failed to load real-time metrics:', err)
      throw err
    }
  }

  const exportAnalytics = async (params: {
    type: string
    format: string
    start_date: string
    end_date: string
    [key: string]: any
  }) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/analytics/export', params, {
        responseType: 'blob'
      })
      
      // Create download link
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `analytics_${params.type}_${Date.now()}.${params.format}`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
      
      return true
    } catch (err: any) {
      error.value = err.message || 'Failed to export analytics'
      throw err
    } finally {
      loading.value = false
    }
  }

  const scheduleReport = async (config: {
    name: string
    type: string
    frequency: string
    recipients: string[]
    format: string
    filters?: any
  }) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/analytics/schedule-report', config)
      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to schedule report'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getDrilldownData = async (params: {
    metric: string
    dimension: string
    value: string
    start_date?: string
    end_date?: string
  }) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/analytics/drilldown', { params })
      return response.data
    } catch (err: any) {
      error.value = err.message || 'Failed to load drilldown data'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Computed
  const hasExecutiveData = computed(() => executiveDashboard.value !== null)
  const hasOperationalData = computed(() => operationalDashboard.value !== null)
  const hasTeamData = computed(() => teamDashboard.value !== null)

  // WebSocket connection for real-time updates
  const connectRealTimeUpdates = () => {
    // This would establish WebSocket connection
    // For now, we'll simulate with polling
    setInterval(() => {
      getRealTimeMetrics()
    }, 30000) // Update every 30 seconds
  }

  return {
    // State
    executiveDashboard,
    operationalDashboard,
    teamDashboard,
    realtimeMetrics,
    loading,
    error,
    
    // Computed
    hasExecutiveData,
    hasOperationalData,
    hasTeamData,
    
    // Actions
    getExecutiveDashboard,
    getOperationalDashboard,
    getTeamDashboard,
    getIncidentAnalytics,
    getChangeAnalytics,
    getSLAAnalytics,
    getWorkloadAnalytics,
    getPredictiveAnalytics,
    getRealTimeMetrics,
    exportAnalytics,
    scheduleReport,
    getDrilldownData,
    connectRealTimeUpdates
  }
})