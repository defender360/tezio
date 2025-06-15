import { defineStore } from 'pinia'
import { api } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import type { Incident, CreateIncidentData, IncidentComment } from '@/types'

interface IncidentState {
  incidents: Incident[]
  currentIncident: Incident | null
  isLoading: boolean
  error: string | null
  filters: {
    status?: string
    priority?: string
    assigned_to?: string
    search?: string
  }
  pagination: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export const useIncidentStore = defineStore('incident', {
  state: (): IncidentState => ({
    incidents: [],
    currentIncident: null,
    isLoading: false,
    error: null,
    filters: {},
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0
    }
  }),

  getters: {
    openIncidents: (state) => state.incidents.filter(i => ['new', 'assigned', 'in_progress'].includes(i.status)),
    overdueIncidents: (state) => state.incidents.filter(i => i.is_overdue),
    myIncidents: (state) => {
      const authStore = useAuthStore()
      const userId = authStore.user?.id
      return state.incidents.filter(i => i.assigned_to === userId)
    }
  },

  actions: {
    async fetchIncidents(page = 1) {
      this.isLoading = true
      try {
        const params = {
          page,
          per_page: this.pagination.per_page,
          ...this.filters
        }
        
        const response = await api.get('/api/v1/incidents', { params })
        this.incidents = response.data.data
        this.pagination = response.data.meta
      } catch (error) {
        this.error = 'Failed to fetch incidents'
        console.error('Error fetching incidents:', error)
      } finally {
        this.isLoading = false
      }
    },

    async fetchIncident(id: string) {
      this.isLoading = true
      try {
        const response = await api.get(`/api/v1/incidents/${id}`)
        this.currentIncident = response.data.data
        return response.data.data
      } catch (error) {
        this.error = 'Failed to fetch incident'
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async createIncident(data: CreateIncidentData) {
      this.isLoading = true
      try {
        const response = await api.post('/api/v1/incidents', data)
        const newIncident = response.data.data
        this.incidents.unshift(newIncident)
        return newIncident
      } catch (error) {
        this.error = 'Failed to create incident'
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async updateIncident(id: string, data: Partial<CreateIncidentData>) {
      try {
        const response = await api.put(`/api/v1/incidents/${id}`, data)
        const updated = response.data.data
        
        const index = this.incidents.findIndex(i => i.id === id)
        if (index !== -1) {
          this.incidents[index] = updated
        }
        
        if (this.currentIncident?.id === id) {
          this.currentIncident = updated
        }
        
        return updated
      } catch (error) {
        this.error = 'Failed to update incident'
        throw error
      }
    },

    async addComment(incidentId: string, body: string, isInternal = false) {
      try {
        const response = await api.post(`/api/v1/incidents/${incidentId}/comments`, {
          body,
          is_internal: isInternal
        })
        
        // Refresh current incident to get updated comments
        if (this.currentIncident?.id === incidentId) {
          await this.fetchIncident(incidentId)
        }
        
        return response.data.data
      } catch (error) {
        throw error
      }
    },

    async resolveIncident(id: string, resolutionNotes: string) {
      try {
        const response = await api.post(`/api/v1/incidents/${id}/resolve`, {
          resolution_notes: resolutionNotes
        })
        
        const resolved = response.data.data
        const index = this.incidents.findIndex(i => i.id === id)
        if (index !== -1) {
          this.incidents[index] = resolved
        }
        
        if (this.currentIncident?.id === id) {
          this.currentIncident = resolved
        }
        
        return resolved
      } catch (error) {
        throw error
      }
    },

    setFilters(filters: any) {
      this.filters = filters
      this.fetchIncidents(1)
    },

    clearFilters() {
      this.filters = {}
      this.fetchIncidents(1)
    }
  }
})