<template>
  <div class="bg-white p-4 rounded-lg shadow-defender-sm">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <!-- Search -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Search
        </label>
        <div class="relative">
          <input
            v-model="localFilters.search"
            type="text"
            placeholder="Search incidents..."
            class="input-field pl-10"
            @input="debounceUpdate"
          >
          <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
        </div>
      </div>

      <!-- Status Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Status
        </label>
        <select
          v-model="localFilters.status"
          class="input-field"
          @change="updateFilters"
        >
          <option value="">All Statuses</option>
          <option value="new">New</option>
          <option value="assigned">Assigned</option>
          <option value="in_progress">In Progress</option>
          <option value="pending">Pending</option>
          <option value="resolved">Resolved</option>
          <option value="closed">Closed</option>
        </select>
      </div>

      <!-- Priority Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Priority
        </label>
        <select
          v-model="localFilters.priority"
          class="input-field"
          @change="updateFilters"
        >
          <option value="">All Priorities</option>
          <option value="critical">Critical</option>
          <option value="high">High</option>
          <option value="medium">Medium</option>
          <option value="low">Low</option>
        </select>
      </div>

      <!-- Assigned To Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Assigned To
        </label>
        <select
          v-model="localFilters.assigned_to"
          class="input-field"
          @change="updateFilters"
        >
          <option value="">All Users</option>
          <option value="unassigned">Unassigned</option>
          <option value="me">Assigned to Me</option>
        </select>
      </div>
    </div>

    <!-- Active Filters -->
    <div v-if="hasActiveFilters" class="mt-4 flex items-center space-x-2">
      <span class="text-sm text-gray-500">Active filters:</span>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="(value, key) in activeFilters"
          :key="key"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-tech-horizon-100 text-tech-horizon-800"
        >
          {{ formatFilterLabel(key, value) }}
          <button
            @click="clearFilter(key)"
            class="ml-1 hover:text-tech-horizon-900"
          >
            <XMarkIcon class="h-3 w-3" />
          </button>
        </span>
      </div>
      <button
        @click="clearAllFilters"
        class="text-sm text-tech-horizon-600 hover:text-tech-horizon-700"
      >
        Clear all
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useDebounce } from '@vueuse/core'

interface Filters {
  status: string
  priority: string
  assigned_to: string
  search: string
}

interface Props {
  filters: Filters
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:filters': [filters: Filters]
}>()

const localFilters = ref<Filters>({ ...props.filters })

const activeFilters = computed(() => {
  const active: Record<string, string> = {}
  Object.entries(localFilters.value).forEach(([key, value]) => {
    if (value) {
      active[key] = value
    }
  })
  return active
})

const hasActiveFilters = computed(() => Object.keys(activeFilters.value).length > 0)

const debouncedSearch = useDebounce(localFilters.value.search, 300)

watch(debouncedSearch, () => {
  updateFilters()
})

function updateFilters() {
  emit('update:filters', { ...localFilters.value })
}

function clearFilter(key: keyof Filters) {
  localFilters.value[key] = ''
  updateFilters()
}

function clearAllFilters() {
  localFilters.value = {
    status: '',
    priority: '',
    assigned_to: '',
    search: ''
  }
  updateFilters()
}

function formatFilterLabel(key: string, value: string): string {
  const labels: Record<string, string> = {
    status: 'Status',
    priority: 'Priority',
    assigned_to: 'Assigned',
    search: 'Search'
  }
  
  if (key === 'assigned_to' && value === 'me') {
    return `${labels[key]}: Me`
  }
  
  return `${labels[key]}: ${value.charAt(0).toUpperCase() + value.slice(1).replace('_', ' ')}`
}

function debounceUpdate() {
  // Search is debounced via the watcher
}
</script>