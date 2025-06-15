import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'
import IncidentCard from '@/components/incidents/IncidentCard.vue'

const mockIncident = {
  id: '1',
  number: 'INC-2024-000001',
  title: 'Email server is down',
  description: 'Users cannot send or receive emails',
  priority: 'high',
  status: 'in_progress',
  impact: 'high',
  urgency: 'high',
  reporter: {
    id: '1',
    name: 'John Doe',
    email: 'john@example.com'
  },
  assignee: {
    id: '2',
    name: 'Jane Smith',
    email: 'jane@example.com'
  },
  created_at: new Date('2024-01-14T10:00:00Z').toISOString(),
  updated_at: new Date('2024-01-14T11:00:00Z').toISOString(),
  sla_response_target: new Date('2024-01-14T12:00:00Z').toISOString(),
  sla_resolution_target: new Date('2024-01-14T16:00:00Z').toISOString()
}

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/incidents/:id',
      name: 'incident-detail',
      component: { template: '<div>Incident Detail</div>' }
    }
  ]
})

describe('IncidentCard', () => {
  it('renders incident information correctly', () => {
    const wrapper = mount(IncidentCard, {
      props: {
        incident: mockIncident
      },
      global: {
        plugins: [router]
      }
    })

    expect(wrapper.text()).toContain('INC-2024-000001')
    expect(wrapper.text()).toContain('Email server is down')
    expect(wrapper.text()).toContain('John Doe')
    expect(wrapper.text()).toContain('Jane Smith')
  })

  it('displays correct priority badge', () => {
    const wrapper = mount(IncidentCard, {
      props: {
        incident: mockIncident
      },
      global: {
        plugins: [router]
      }
    })

    const priorityBadge = wrapper.find('[data-testid="priority-badge"]')
    expect(priorityBadge.exists()).toBe(true)
    expect(priorityBadge.text()).toBe('High')
    expect(priorityBadge.classes()).toContain('bg-orange-100')
  })

  it('displays correct status badge', () => {
    const wrapper = mount(IncidentCard, {
      props: {
        incident: mockIncident
      },
      global: {
        plugins: [router]
      }
    })

    const statusBadge = wrapper.find('[data-testid="status-badge"]')
    expect(statusBadge.exists()).toBe(true)
    expect(statusBadge.text()).toBe('In Progress')
    expect(statusBadge.classes()).toContain('bg-blue-100')
  })

  it('shows SLA indicator when approaching deadline', () => {
    const incidentWithSLA = {
      ...mockIncident,
      sla_response_target: new Date(Date.now() + 30 * 60 * 1000).toISOString() // 30 minutes from now
    }

    const wrapper = mount(IncidentCard, {
      props: {
        incident: incidentWithSLA
      },
      global: {
        plugins: [router]
      }
    })

    const slaIndicator = wrapper.find('[data-testid="sla-indicator"]')
    expect(slaIndicator.exists()).toBe(true)
    expect(slaIndicator.classes()).toContain('text-orange-600')
  })

  it('shows SLA breach indicator', () => {
    const breachedIncident = {
      ...mockIncident,
      sla_response_target: new Date(Date.now() - 60 * 60 * 1000).toISOString(), // 1 hour ago
      sla_breached: true
    }

    const wrapper = mount(IncidentCard, {
      props: {
        incident: breachedIncident
      },
      global: {
        plugins: [router]
      }
    })

    const slaIndicator = wrapper.find('[data-testid="sla-indicator"]')
    expect(slaIndicator.exists()).toBe(true)
    expect(slaIndicator.classes()).toContain('text-red-600')
    expect(wrapper.text()).toContain('SLA Breached')
  })

  it('navigates to incident detail on click', async () => {
    const push = vi.spyOn(router, 'push')
    
    const wrapper = mount(IncidentCard, {
      props: {
        incident: mockIncident
      },
      global: {
        plugins: [router]
      }
    })

    await wrapper.trigger('click')

    expect(push).toHaveBeenCalledWith({
      name: 'incident-detail',
      params: { id: '1' }
    })
  })

  it('emits select event when checkbox is clicked', async () => {
    const wrapper = mount(IncidentCard, {
      props: {
        incident: mockIncident,
        selectable: true
      },
      global: {
        plugins: [router]
      }
    })

    const checkbox = wrapper.find('input[type="checkbox"]')
    expect(checkbox.exists()).toBe(true)

    await checkbox.setValue(true)

    expect(wrapper.emitted('select')).toBeTruthy()
    expect(wrapper.emitted('select')?.[0]).toEqual([true])
  })

  it('displays unassigned state correctly', () => {
    const unassignedIncident = {
      ...mockIncident,
      assignee: null
    }

    const wrapper = mount(IncidentCard, {
      props: {
        incident: unassignedIncident
      },
      global: {
        plugins: [router]
      }
    })

    expect(wrapper.text()).toContain('Unassigned')
    expect(wrapper.find('[data-testid="unassigned-indicator"]').exists()).toBe(true)
  })

  it('formats dates correctly', () => {
    const wrapper = mount(IncidentCard, {
      props: {
        incident: mockIncident
      },
      global: {
        plugins: [router]
      }
    })

    // Should show relative time
    expect(wrapper.text()).toMatch(/\d+ (hours?|minutes?) ago/)
  })

  it('shows comment count if available', () => {
    const incidentWithComments = {
      ...mockIncident,
      comments_count: 5
    }

    const wrapper = mount(IncidentCard, {
      props: {
        incident: incidentWithComments
      },
      global: {
        plugins: [router]
      }
    })

    const commentIndicator = wrapper.find('[data-testid="comment-count"]')
    expect(commentIndicator.exists()).toBe(true)
    expect(commentIndicator.text()).toBe('5')
  })

  it('shows attachment indicator if attachments exist', () => {
    const incidentWithAttachments = {
      ...mockIncident,
      attachments_count: 3
    }

    const wrapper = mount(IncidentCard, {
      props: {
        incident: incidentWithAttachments
      },
      global: {
        plugins: [router]
      }
    })

    const attachmentIndicator = wrapper.find('[data-testid="attachment-indicator"]')
    expect(attachmentIndicator.exists()).toBe(true)
  })

  it('applies highlight class when highlighted prop is true', () => {
    const wrapper = mount(IncidentCard, {
      props: {
        incident: mockIncident,
        highlighted: true
      },
      global: {
        plugins: [router]
      }
    })

    expect(wrapper.classes()).toContain('ring-2')
    expect(wrapper.classes()).toContain('ring-primary-500')
  })
})