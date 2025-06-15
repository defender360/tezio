import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useIncidentStore } from '@/stores/incident'
import { api } from '@/services/api'

// Mock the API service
vi.mock('@/services/api', () => ({
  api: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
    patch: vi.fn()
  }
}))

const mockIncident = {
  id: '1',
  number: 'INC-2024-000001',
  title: 'Test Incident',
  description: 'Test description',
  priority: 'high',
  status: 'new',
  impact: 'high',
  urgency: 'high',
  created_at: new Date().toISOString(),
  updated_at: new Date().toISOString()
}

describe('Incident Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('fetchIncidents', () => {
    it('fetches and stores incidents', async () => {
      const mockResponse = {
        data: {
          data: [mockIncident],
          meta: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 1
          }
        }
      }

      vi.mocked(api.get).mockResolvedValueOnce(mockResponse)

      const store = useIncidentStore()
      await store.fetchIncidents()

      expect(api.get).toHaveBeenCalledWith('/incidents', {
        params: {
          page: 1,
          per_page: 20,
          sort_by: 'created_at',
          sort_order: 'desc'
        }
      })

      expect(store.incidents).toEqual([mockIncident])
      expect(store.pagination).toEqual(mockResponse.data.meta)
      expect(store.loading).toBe(false)
    })

    it('handles fetch error', async () => {
      const error = new Error('Network error')
      vi.mocked(api.get).mockRejectedValueOnce(error)

      const store = useIncidentStore()
      await store.fetchIncidents()

      expect(store.error).toBe('Failed to fetch incidents')
      expect(store.loading).toBe(false)
      expect(store.incidents).toEqual([])
    })

    it('applies filters when fetching', async () => {
      const mockResponse = {
        data: {
          data: [],
          meta: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0
          }
        }
      }

      vi.mocked(api.get).mockResolvedValueOnce(mockResponse)

      const store = useIncidentStore()
      store.filters = {
        status: ['new', 'in_progress'],
        priority: ['high', 'critical'],
        search: 'server'
      }

      await store.fetchIncidents()

      expect(api.get).toHaveBeenCalledWith('/incidents', {
        params: {
          page: 1,
          per_page: 20,
          sort_by: 'created_at',
          sort_order: 'desc',
          status: 'new,in_progress',
          priority: 'high,critical',
          search: 'server'
        }
      })
    })
  })

  describe('fetchIncident', () => {
    it('fetches a single incident', async () => {
      const mockResponse = {
        data: {
          data: mockIncident
        }
      }

      vi.mocked(api.get).mockResolvedValueOnce(mockResponse)

      const store = useIncidentStore()
      const result = await store.fetchIncident('1')

      expect(api.get).toHaveBeenCalledWith('/incidents/1')
      expect(result).toEqual(mockIncident)
      expect(store.currentIncident).toEqual(mockIncident)
    })

    it('returns existing incident if already current', async () => {
      const store = useIncidentStore()
      store.currentIncident = mockIncident

      const result = await store.fetchIncident('1')

      expect(api.get).not.toHaveBeenCalled()
      expect(result).toEqual(mockIncident)
    })
  })

  describe('createIncident', () => {
    it('creates a new incident', async () => {
      const newIncidentData = {
        title: 'New Incident',
        description: 'Description',
        impact: 'medium',
        urgency: 'medium'
      }

      const createdIncident = {
        ...mockIncident,
        ...newIncidentData,
        id: '2',
        number: 'INC-2024-000002'
      }

      vi.mocked(api.post).mockResolvedValueOnce({
        data: { data: createdIncident }
      })

      const store = useIncidentStore()
      const result = await store.createIncident(newIncidentData)

      expect(api.post).toHaveBeenCalledWith('/incidents', newIncidentData)
      expect(result).toEqual(createdIncident)
      expect(store.incidents).toContain(createdIncident)
    })

    it('handles creation error', async () => {
      const error = new Error('Validation error')
      vi.mocked(api.post).mockRejectedValueOnce(error)

      const store = useIncidentStore()
      const result = await store.createIncident({ title: 'Test' })

      expect(result).toBeNull()
      expect(store.error).toBe('Failed to create incident')
    })
  })

  describe('updateIncident', () => {
    it('updates an existing incident', async () => {
      const updates = { status: 'in_progress' }
      const updatedIncident = { ...mockIncident, ...updates }

      vi.mocked(api.put).mockResolvedValueOnce({
        data: { data: updatedIncident }
      })

      const store = useIncidentStore()
      store.incidents = [mockIncident]

      const result = await store.updateIncident('1', updates)

      expect(api.put).toHaveBeenCalledWith('/incidents/1', updates)
      expect(result).toEqual(updatedIncident)
      expect(store.incidents[0]).toEqual(updatedIncident)
    })

    it('updates current incident if it matches', async () => {
      const updates = { status: 'resolved' }
      const updatedIncident = { ...mockIncident, ...updates }

      vi.mocked(api.put).mockResolvedValueOnce({
        data: { data: updatedIncident }
      })

      const store = useIncidentStore()
      store.currentIncident = mockIncident

      await store.updateIncident('1', updates)

      expect(store.currentIncident).toEqual(updatedIncident)
    })
  })

  describe('addComment', () => {
    it('adds a comment to an incident', async () => {
      const comment = {
        id: '1',
        content: 'Test comment',
        user: { id: '1', name: 'John Doe' },
        created_at: new Date().toISOString()
      }

      vi.mocked(api.post).mockResolvedValueOnce({
        data: { data: comment }
      })

      const store = useIncidentStore()
      const result = await store.addComment('1', 'Test comment')

      expect(api.post).toHaveBeenCalledWith('/incidents/1/comments', {
        content: 'Test comment'
      })
      expect(result).toEqual(comment)
    })
  })

  describe('bulkUpdate', () => {
    it('updates multiple incidents', async () => {
      const mockResponse = {
        data: {
          updated: 3,
          incidents: [
            { ...mockIncident, status: 'in_progress' },
            { ...mockIncident, id: '2', status: 'in_progress' },
            { ...mockIncident, id: '3', status: 'in_progress' }
          ]
        }
      }

      vi.mocked(api.post).mockResolvedValueOnce(mockResponse)

      const store = useIncidentStore()
      store.incidents = [
        mockIncident,
        { ...mockIncident, id: '2' },
        { ...mockIncident, id: '3' }
      ]

      const result = await store.bulkUpdate(
        ['1', '2', '3'],
        { status: 'in_progress' }
      )

      expect(api.post).toHaveBeenCalledWith('/incidents/bulk-update', {
        incident_ids: ['1', '2', '3'],
        updates: { status: 'in_progress' }
      })

      expect(result).toBe(3)
      expect(store.incidents.every(i => i.status === 'in_progress')).toBe(true)
    })
  })

  describe('computed getters', () => {
    it('filters incidents by status', () => {
      const store = useIncidentStore()
      store.incidents = [
        { ...mockIncident, id: '1', status: 'new' },
        { ...mockIncident, id: '2', status: 'in_progress' },
        { ...mockIncident, id: '3', status: 'resolved' },
        { ...mockIncident, id: '4', status: 'new' }
      ]

      expect(store.incidentsByStatus('new')).toHaveLength(2)
      expect(store.incidentsByStatus('in_progress')).toHaveLength(1)
      expect(store.incidentsByStatus('resolved')).toHaveLength(1)
      expect(store.incidentsByStatus('closed')).toHaveLength(0)
    })

    it('filters incidents by priority', () => {
      const store = useIncidentStore()
      store.incidents = [
        { ...mockIncident, id: '1', priority: 'critical' },
        { ...mockIncident, id: '2', priority: 'high' },
        { ...mockIncident, id: '3', priority: 'high' },
        { ...mockIncident, id: '4', priority: 'medium' }
      ]

      expect(store.incidentsByPriority('critical')).toHaveLength(1)
      expect(store.incidentsByPriority('high')).toHaveLength(2)
      expect(store.incidentsByPriority('medium')).toHaveLength(1)
      expect(store.incidentsByPriority('low')).toHaveLength(0)
    })

    it('counts unresolved incidents', () => {
      const store = useIncidentStore()
      store.incidents = [
        { ...mockIncident, id: '1', status: 'new' },
        { ...mockIncident, id: '2', status: 'in_progress' },
        { ...mockIncident, id: '3', status: 'resolved' },
        { ...mockIncident, id: '4', status: 'closed' }
      ]

      expect(store.unresolvedCount).toBe(2)
    })

    it('filters overdue incidents', () => {
      const store = useIncidentStore()
      const now = new Date()
      
      store.incidents = [
        {
          ...mockIncident,
          id: '1',
          sla_resolution_target: new Date(now.getTime() - 3600000).toISOString(), // 1 hour ago
          status: 'in_progress'
        },
        {
          ...mockIncident,
          id: '2',
          sla_resolution_target: new Date(now.getTime() + 3600000).toISOString(), // 1 hour from now
          status: 'in_progress'
        },
        {
          ...mockIncident,
          id: '3',
          sla_resolution_target: new Date(now.getTime() - 3600000).toISOString(),
          status: 'resolved' // Not overdue because resolved
        }
      ]

      expect(store.overdueIncidents).toHaveLength(1)
      expect(store.overdueIncidents[0].id).toBe('1')
    })
  })

  describe('filters and sorting', () => {
    it('sets filters', () => {
      const store = useIncidentStore()
      const filters = {
        status: ['new', 'in_progress'],
        priority: ['high'],
        assignee: '123'
      }

      store.setFilters(filters)

      expect(store.filters).toEqual(filters)
    })

    it('clears filters', () => {
      const store = useIncidentStore()
      store.filters = {
        status: ['new'],
        priority: ['high']
      }

      store.clearFilters()

      expect(store.filters).toEqual({})
    })

    it('sets sorting', () => {
      const store = useIncidentStore()
      
      store.setSorting('priority', 'asc')

      expect(store.sortBy).toBe('priority')
      expect(store.sortOrder).toBe('asc')
    })
  })
})