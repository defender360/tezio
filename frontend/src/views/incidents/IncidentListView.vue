<template>
  <div class="space-y-6">
    <!-- Header with Actions -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-brain font-semibold text-deep-sea-500">Incidents</h1>
        <p class="text-sm text-gray-600 mt-1">Manage and track all incidents</p>
      </div>
      
      <div class="flex space-x-3">
        <button
          @click="exportIncidents"
          class="btn-secondary"
        >
          <ArrowDownTrayIcon class="h-5 w-5 mr-2" />
          Export
        </button>
        <button
          @click="showCreateModal = true"
          class="btn-primary"
        >
          <PlusIcon class="h-5 w-5 mr-2" />
          New Incident
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-defender-sm p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search by title or number..."
            class="input-field"
            @input="debouncedSearch"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select v-model="filters.status" class="input-field">
            <option value="">All Status</option>
            <option value="new">New</option>
            <option value="assigned">Assigned</option>
            <option value="in_progress">In Progress</option>
            <option value="pending">Pending</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
          <select v-model="filters.priority" class="input-field">
            <option value="">All Priorities</option>
            <option value="critical">Critical</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
          <UserSelect
            v-model="filters.assigned_to"
            placeholder="All Users"
            :allow-clear="true"
          />
        </div>
      </div>
      
      <div class="flex justify-between items-center mt-4">
        <button
          @click="resetFilters"
          class="text-sm text-tech-horizon-600 hover:text-tech-horizon-700"
        >
          Clear Filters
        </button>
        
        <div class="flex items-center space-x-4">
          <span class="text-sm text-gray-600">
            {{ incidents?.meta?.total || 0 }} incidents found
          </span>
        </div>
      </div>
    </div>

    <!-- Incidents Table -->
    <div class="bg-white rounded-lg shadow-defender-sm overflow-hidden">
      <div v-if="isLoading" class="p-8">
        <LoadingSpinner />
      </div>
      
      <div v-else-if="incidents?.data?.length === 0" class="p-8 text-center">
        <TicketIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">No incidents found</h3>
        <p class="mt-1 text-sm text-gray-500">
          Get started by creating a new incident.
        </p>
        <div class="mt-6">
          <button
            @click="showCreateModal = true"
            class="btn-primary"
          >
            <PlusIcon class="h-5 w-5 mr-2" />
            New Incident
          </button>
        </div>
      </div>
      
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              <input
                type="checkbox"
                v-model="selectAll"
                @change="toggleSelectAll"
                class="h-4 w-4 text-tech-horizon-600 focus:ring-tech-horizon-500 border-gray-300 rounded"
              />
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Incident
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Priority
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Status
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Assigned To
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              SLA
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Created
            </th>
            <th class="relative px-6 py-3">
              <span class="sr-only">Actions</span>
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="incident in incidents.data"
            :key="incident.id"
            class="hover:bg-gray-50 cursor-pointer"
            @click="goToIncident(incident.id)"
          >
            <td class="px-6 py-4 whitespace-nowrap" @click.stop>
              <input
                type="checkbox"
                v-model="selectedIncidents"
                :value="incident.id"
                class="h-4 w-4 text-tech-horizon-600 focus:ring-tech-horizon-500 border-gray-300 rounded"
              />
            </td>
            <td class="px-6 py-4">
              <div>
                <div class="text-sm font-medium text-gray-900">
                  {{ incident.title }}
                </div>
                <div class="text-sm text-gray-500">
                  #{{ incident.number }}
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <PriorityBadge :priority="incident.priority" />
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <StatusBadge :status="incident.status" />
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div v-if="incident.assigned_user" class="flex items-center">
                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                  <span class="text-xs font-medium text-gray-600">
                    {{ incident.assigned_user.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <div class="ml-2 text-sm text-gray-900">
                  {{ incident.assigned_user.name }}
                </div>
              </div>
              <span v-else class="text-sm text-gray-500">Unassigned</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <SLAIndicator
                :target-time="incident.sla_resolution_target"
                :resolved-time="incident.resolved_at"
              />
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
              <TimeAgo :datetime="incident.created_at" />
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" @click.stop>
              <button
                @click="showActions(incident)"
                class="text-gray-400 hover:text-gray-600"
              >
                <EllipsisVerticalIcon class="h-5 w-5" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      
      <!-- Pagination -->
      <div v-if="incidents?.meta?.last_page > 1" class="bg-gray-50 px-6 py-3">
        <Pagination
          :current-page="incidents.meta.current_page"
          :total-pages="incidents.meta.last_page"
          @update:current-page="changePage"
        />
      </div>
    </div>

    <!-- Bulk Actions -->
    <div
      v-if="selectedIncidents.length > 0"
      class="fixed bottom-6 right-6 bg-white rounded-lg shadow-defender-lg p-4"
    >
      <div class="flex items-center space-x-4">
        <span class="text-sm text-gray-600">
          {{ selectedIncidents.length }} selected
        </span>
        <button
          @click="bulkAssign"
          class="btn-secondary-sm"
        >
          Assign
        </button>
        <button
          @click="bulkUpdateStatus"
          class="btn-secondary-sm"
        >
          Update Status
        </button>
        <button
          @click="selectedIncidents = []"
          class="text-sm text-gray-600 hover:text-gray-800"
        >
          Cancel
        </button>
      </div>
    </div>

    <!-- Create Incident Modal -->
    <CreateIncidentModal
      v-if="showCreateModal"
      @close="showCreateModal = false"
      @created="onIncidentCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useQuery, useMutation } from '@tanstack/vue-query'
import { debounce } from 'lodash-es'
import { api } from '@/services/api'
import { useToast } from 'vue-toastification'
import {
  PlusIcon,
  ArrowDownTrayIcon,
  TicketIcon,
  EllipsisVerticalIcon
} from '@heroicons/vue/24/outline'
import StatusBadge from '@/components/common/StatusBadge.vue'
import PriorityBadge from '@/components/common/PriorityBadge.vue'
import TimeAgo from '@/components/common/TimeAgo.vue'
import SLAIndicator from '@/components/common/SLAIndicator.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import Pagination from '@/components/common/Pagination.vue'
import UserSelect from '@/components/common/UserSelect.vue'
import CreateIncidentModal from '@/components/incidents/CreateIncidentModal.vue'

const router = useRouter()
const toast = useToast()

// State
const showCreateModal = ref(false)
const selectedIncidents = ref<string[]>([])
const selectAll = ref(false)
const currentPage = ref(1)

// Filters
const filters = ref({
  search: '',
  status: '',
  priority: '',
  assigned_to: ''
})

// Build query parameters
const queryParams = computed(() => {
  const params: any = {
    page: currentPage.value,
    per_page: 20
  }
  
  if (filters.value.search) {
    params['filter[title]'] = filters.value.search
  }
  if (filters.value.status) {
    params['filter[status]'] = filters.value.status
  }
  if (filters.value.priority) {
    params['filter[priority]'] = filters.value.priority
  }
  if (filters.value.assigned_to) {
    params['filter[assigned_to]'] = filters.value.assigned_to
  }
  
  return params
})

// Fetch incidents
const { data: incidents, isLoading, refetch } = useQuery({
  queryKey: ['incidents', queryParams],
  queryFn: async () => {
    const response = await api.get('/api/v1/incidents', { params: queryParams.value })
    return response.data
  }
})

// Debounced search
const debouncedSearch = debounce(() => {
  currentPage.value = 1
  refetch()
}, 300)

// Export mutation
const exportMutation = useMutation({
  mutationFn: async (format: 'csv' | 'pdf') => {
    return api.post('/api/v1/incidents/export', {
      format,
      filters: filters.value
    })
  },
  onSuccess: () => {
    toast.success('Export started. You will receive an email when ready.')
  },
  onError: () => {
    toast.error('Failed to start export')
  }
})

// Methods
const goToIncident = (id: string) => {
  router.push(`/incidents/${id}`)
}

const resetFilters = () => {
  filters.value = {
    search: '',
    status: '',
    priority: '',
    assigned_to: ''
  }
  currentPage.value = 1
}

const changePage = (page: number) => {
  currentPage.value = page
}

const toggleSelectAll = () => {
  if (selectAll.value) {
    selectedIncidents.value = incidents.value?.data.map((i: any) => i.id) || []
  } else {
    selectedIncidents.value = []
  }
}

const exportIncidents = () => {
  exportMutation.mutate('csv')
}

const bulkAssign = () => {
  // TODO: Implement bulk assign modal
  toast.info('Bulk assign feature coming soon')
}

const bulkUpdateStatus = () => {
  // TODO: Implement bulk status update modal
  toast.info('Bulk status update feature coming soon')
}

const showActions = (incident: any) => {
  // TODO: Implement actions dropdown
  toast.info('Actions menu coming soon')
}

const onIncidentCreated = () => {
  showCreateModal.value = false
  refetch()
  toast.success('Incident created successfully')
}

// Watch filters
watch(filters, () => {
  currentPage.value = 1
}, { deep: true })
</script>