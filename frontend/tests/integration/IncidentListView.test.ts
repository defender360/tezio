import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'
import { createPinia } from 'pinia'
import IncidentListView from '@/views/incidents/IncidentListView.vue'
import { useIncidentStore } from '@/stores/incident'
import { useAuthStore } from '@/stores/auth'
import { api } from '@/services/api'

// Mock API
vi.mock('@/services/api', () => ({
  api: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn()
  }
}))

const mockIncidents = [
  {
    id: '1',
    number: 'INC-2024-000001',
    title: 'Email server is down',
    priority: 'critical',
    status: 'new',
    created_at: new Date().toISOString()
  },
  {
    id: '2',
    number: 'INC-2024-000002',
    title: 'Printer not working',
    priority: 'low',
    status: 'in_progress',
    created_at: new Date().toISOString()
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/incidents',
      name: 'incidents',
      component: IncidentListView
    },
    {
      path: '/incidents/:id',
      name: 'incident-detail',
      component: { template: '<div>Detail</div>' }
    }
  ]
})

describe('IncidentListView Integration', () => {
  let pinia: any

  beforeEach(async () => {
    pinia = createPinia()
    vi.clearAllMocks()
    
    // Setup auth store
    const authStore = useAuthStore(pinia)
    authStore.user = {
      id: '1',
      name: 'Test User',
      email: 'test@example.com',
      role: 'admin'
    }
    authStore.isAuthenticated = true

    // Mock initial API response
    vi.mocked(api.get).mockResolvedValue({
      data: {
        data: mockIncidents,
        meta: {
          current_page: 1,
          last_page: 1,
          per_page: 20,
          total: 2
        }
      }
    })

    await router.push('/incidents')
    await router.isReady()
  })

  it('loads and displays incidents on mount', async () => {
    const wrapper = mount(IncidentListView, {
      global: {
        plugins: [pinia, router],
        stubs: {
          teleport: true
        }
      }
    })

    // Wait for async operations
    await flushPromises()

    // Check loading state was shown
    expect(vi.mocked(api.get)).toHaveBeenCalledWith('/incidents', expect.any(Object))

    // Check incidents are displayed
    const incidentCards = wrapper.findAll('[data-testid="incident-card"]')
    expect(incidentCards).toHaveLength(2)
    
    expect(wrapper.text()).toContain('INC-2024-000001')
    expect(wrapper.text()).toContain('Email server is down')
    expect(wrapper.text()).toContain('INC-2024-000002')
    expect(wrapper.text()).toContain('Printer not working')
  })

  it('filters incidents by status', async () => {
    const wrapper = mount(IncidentListView, {
      global: {
        plugins: [pinia, router],
        stubs: {
          teleport: true
        }
      }
    })

    await flushPromises()

    // Find and click status filter
    const statusFilter = wrapper.find('[data-testid="status-filter"]')
    await statusFilter.find('button').trigger('click')
    
    // Select "New" status
    const newStatusOption = wrapper.find('[data-testid="status-new"]')
    await newStatusOption.trigger('click')

    // Apply filter
    const applyButton = wrapper.find('[data-testid="apply-filters"]')
    await applyButton.trigger('click')

    await flushPromises()

    // Verify API was called with status filter
    expect(vi.mocked(api.get)).toHaveBeenLastCalledWith('/incidents', {
      params: expect.objectContaining({
        status: 'new'
      })
    })
  })

  it('searches incidents', async () => {
    const wrapper = mount(IncidentListView, {
      global: {
        plugins: [pinia, router],
        stubs: {
          teleport: true
        }
      }
    })

    await flushPromises()

    // Find search input
    const searchInput = wrapper.find('[data-testid="search-input"]')
    await searchInput.setValue('email')

    // Wait for debounce
    await new Promise(resolve => setTimeout(resolve, 300))
    await flushPromises()

    // Verify API was called with search term
    expect(vi.mocked(api.get)).toHaveBeenLastCalledWith('/incidents', {
      params: expect.objectContaining({
        search: 'email'
      })
    })
  })

  it('creates a new incident', async () => {
    vi.mocked(api.post).mockResolvedValueOnce({
      data: {
        data: {
          id: '3',
          number: 'INC-2024-000003',
          title: 'New test incident',
          priority: 'medium',
          status: 'new'
        }
      }
    })

    const wrapper = mount(IncidentListView, {
      global: {
        plugins: [pinia, router],
        stubs: {
          teleport: true
        }
      }
    })

    await flushPromises()

    // Click create button
    const createButton = wrapper.find('[data-testid="create-incident-button"]')
    await createButton.trigger('click')

    // Fill in the form in the modal
    const modal = wrapper.findComponent({ name: 'CreateIncidentModal' })
    expect(modal.exists()).toBe(true)

    await modal.find('[data-testid="title-input"]').setValue('New test incident')
    await modal.find('[data-testid="description-input"]').setValue('Test description')
    await modal.find('[data-testid="impact-select"]').setValue('medium')
    await modal.find('[data-testid="urgency-select"]').setValue('medium')

    // Submit form
    await modal.find('[data-testid="submit-button"]').trigger('click')
    await flushPromises()

    // Verify incident was created
    expect(vi.mocked(api.post)).toHaveBeenCalledWith('/incidents', {
      title: 'New test incident',
      description: 'Test description',
      impact: 'medium',
      urgency: 'medium'
    })

    // Verify list was refreshed
    expect(vi.mocked(api.get)).toHaveBeenCalledTimes(2)
  })

  it('performs bulk actions on selected incidents', async () => {
    vi.mocked(api.post).mockResolvedValueOnce({
      data: {
        updated: 2
      }
    })

    const wrapper = mount(IncidentListView, {
      global: {
        plugins: [pinia, router],
        stubs: {
          teleport: true
        }
      }
    })

    await flushPromises()

    // Select incidents
    const checkboxes = wrapper.findAll('[data-testid="incident-checkbox"]')
    await checkboxes[0].setValue(true)
    await checkboxes[1].setValue(true)

    // Bulk actions should now be visible
    const bulkActions = wrapper.find('[data-testid="bulk-actions"]')
    expect(bulkActions.exists()).toBe(true)

    // Select bulk update action
    await bulkActions.find('[data-testid="bulk-update-button"]').trigger('click')

    // Select new status in modal
    const modal = wrapper.findComponent({ name: 'BulkUpdateModal' })
    await modal.find('[data-testid="status-select"]').setValue('resolved')
    await modal.find('[data-testid="confirm-button"]').trigger('click')

    await flushPromises()

    // Verify bulk update was called
    expect(vi.mocked(api.post)).toHaveBeenCalledWith('/incidents/bulk-update', {
      incident_ids: ['1', '2'],
      updates: {
        status: 'resolved'
      }
    })
  })

  it('exports incidents', async () => {
    // Mock blob response
    const mockBlob = new Blob(['csv data'], { type: 'text/csv' })
    vi.mocked(api.post).mockResolvedValueOnce({
      data: mockBlob,
      headers: {
        'content-disposition': 'attachment; filename=incidents.csv'
      }
    })

    const wrapper = mount(IncidentListView, {
      global: {
        plugins: [pinia, router],
        stubs: {
          teleport: true
        }
      }
    })

    await flushPromises()

    // Click export button
    const exportButton = wrapper.find('[data-testid="export-button"]')
    await exportButton.trigger('click')

    // Select CSV format
    const csvOption = wrapper.find('[data-testid="export-csv"]')
    await csvOption.trigger('click')

    await flushPromises()

    // Verify export was called
    expect(vi.mocked(api.post)).toHaveBeenCalledWith('/incidents/export', {
      format: 'csv',
      filters: expect.any(Object)
    })
  })

  it('handles pagination', async () => {
    // Update mock to return more pages
    vi.mocked(api.get).mockResolvedValueOnce({
      data: {
        data: mockIncidents,
        meta: {
          current_page: 1,
          last_page: 3,
          per_page: 20,
          total: 60
        }
      }
    })

    const wrapper = mount(IncidentListView, {
      global: {
        plugins: [pinia, router],
        stubs: {
          teleport: true
        }
      }
    })

    await flushPromises()

    // Find pagination component
    const pagination = wrapper.findComponent({ name: 'Pagination' })
    expect(pagination.exists()).toBe(true)

    // Click next page
    await pagination.find('[data-testid="next-page"]').trigger('click')
    await flushPromises()

    // Verify API was called with page 2
    expect(vi.mocked(api.get)).toHaveBeenLastCalledWith('/incidents', {
      params: expect.objectContaining({
        page: 2
      })
    })
  })

  it('handles errors gracefully', async () => {
    // Mock API error
    vi.mocked(api.get).mockRejectedValueOnce(new Error('Network error'))

    const wrapper = mount(IncidentListView, {
      global: {
        plugins: [pinia, router],
        stubs: {
          teleport: true
        }
      }
    })

    await flushPromises()

    // Should show error message
    expect(wrapper.text()).toContain('Failed to load incidents')
    
    // Should show retry button
    const retryButton = wrapper.find('[data-testid="retry-button"]')
    expect(retryButton.exists()).toBe(true)

    // Mock successful response for retry
    vi.mocked(api.get).mockResolvedValueOnce({
      data: {
        data: mockIncidents,
        meta: {
          current_page: 1,
          last_page: 1,
          per_page: 20,
          total: 2
        }
      }
    })

    // Click retry
    await retryButton.trigger('click')
    await flushPromises()

    // Should now show incidents
    expect(wrapper.text()).toContain('INC-2024-000001')
    expect(wrapper.text()).not.toContain('Failed to load incidents')
  })

  it('updates incident list in real-time', async () => {
    const wrapper = mount(IncidentListView, {
      global: {
        plugins: [pinia, router],
        stubs: {
          teleport: true
        }
      }
    })

    await flushPromises()

    // Simulate WebSocket event for new incident
    const incidentStore = useIncidentStore(pinia)
    incidentStore.incidents.push({
      id: '3',
      number: 'INC-2024-000003',
      title: 'New real-time incident',
      priority: 'high',
      status: 'new',
      created_at: new Date().toISOString()
    })

    await wrapper.vm.$nextTick()

    // Should show the new incident
    expect(wrapper.text()).toContain('INC-2024-000003')
    expect(wrapper.text()).toContain('New real-time incident')
    
    // Should show 3 incidents total
    const incidentCards = wrapper.findAll('[data-testid="incident-card"]')
    expect(incidentCards).toHaveLength(3)
  })
})