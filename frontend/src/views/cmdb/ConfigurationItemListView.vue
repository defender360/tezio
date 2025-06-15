<template>
  <div class="py-6">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
            Configuration Items
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Manage your IT assets and configuration items
          </p>
        </div>
        <div class="mt-4 flex md:ml-4 md:mt-0">
          <button
            @click="showCreateModal = true"
            type="button"
            class="ml-3 inline-flex items-center rounded-md bg-deep-sea-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-deep-sea-500"
          >
            <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
            Add Configuration Item
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Search</label>
          <input
            v-model="filters.search"
            type="text"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
            placeholder="Search by name, serial, or tag..."
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Type</label>
          <select
            v-model="filters.type"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
          >
            <option value="">All Types</option>
            <option value="server">Server</option>
            <option value="workstation">Workstation</option>
            <option value="laptop">Laptop</option>
            <option value="network_device">Network Device</option>
            <option value="printer">Printer</option>
            <option value="software">Software</option>
            <option value="license">License</option>
            <option value="virtual_machine">Virtual Machine</option>
            <option value="mobile_device">Mobile Device</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select
            v-model="filters.status"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
          >
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="maintenance">Under Maintenance</option>
            <option value="retired">Retired</option>
            <option value="disposed">Disposed</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Location</label>
          <input
            v-model="filters.location"
            type="text"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
            placeholder="Filter by location"
          />
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
          <dt class="truncate text-sm font-medium text-gray-500">Total Assets</dt>
          <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
            {{ stats?.total || 0 }}
          </dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
          <dt class="truncate text-sm font-medium text-gray-500">Active</dt>
          <dd class="mt-1 text-3xl font-semibold tracking-tight text-green-600">
            {{ stats?.active || 0 }}
          </dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
          <dt class="truncate text-sm font-medium text-gray-500">Under Maintenance</dt>
          <dd class="mt-1 text-3xl font-semibold tracking-tight text-yellow-600">
            {{ stats?.maintenance || 0 }}
          </dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
          <dt class="truncate text-sm font-medium text-gray-500">Warranty Expiring</dt>
          <dd class="mt-1 text-3xl font-semibold tracking-tight text-red-600">
            {{ stats?.warranty_expiring || 0 }}
          </dd>
        </div>
      </div>

      <!-- Table -->
      <div class="mt-8 flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
              <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="relative px-7 sm:w-12 sm:px-6">
                      <input
                        type="checkbox"
                        :checked="isAllSelected"
                        :indeterminate="isIndeterminate"
                        @change="toggleSelectAll"
                        class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-deep-sea-600 focus:ring-deep-sea-600"
                      />
                    </th>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">
                      Name
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Type
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Status
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Owner
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Location
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Warranty
                    </th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                      <span class="sr-only">Actions</span>
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                  <tr v-if="isLoading">
                    <td colspan="8" class="text-center py-4">
                      <div class="inline-flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-deep-sea-600" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Loading configuration items...
                      </div>
                    </td>
                  </tr>
                  <tr v-else-if="!configItems?.data?.length">
                    <td colspan="8" class="text-center py-4 text-gray-500">
                      No configuration items found
                    </td>
                  </tr>
                  <tr v-else v-for="item in configItems.data" :key="item.id" :class="selectedItems.includes(item.id) ? 'bg-gray-50' : undefined">
                    <td class="relative px-7 sm:w-12 sm:px-6">
                      <div v-if="selectedItems.includes(item.id)" class="absolute inset-y-0 left-0 w-0.5 bg-deep-sea-600"></div>
                      <input
                        type="checkbox"
                        :value="item.id"
                        v-model="selectedItems"
                        class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-deep-sea-600 focus:ring-deep-sea-600"
                      />
                    </td>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-0">
                      <div>
                        <div class="font-medium text-gray-900">{{ item.name }}</div>
                        <div class="text-gray-500">{{ item.asset_tag || item.serial_number }}</div>
                      </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">
                        {{ formatType(item.type) }}
                      </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      <span :class="[getStatusColor(item.status), 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium']">
                        {{ formatStatus(item.status) }}
                      </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      <div v-if="item.owner" class="flex items-center">
                        <img
                          class="h-8 w-8 rounded-full"
                          :src="item.owner.avatar || 'https://ui-avatars.com/api/?name=' + item.owner.name"
                          :alt="item.owner.name"
                        />
                        <span class="ml-2">{{ item.owner.name }}</span>
                      </div>
                      <span v-else class="text-gray-400">Unassigned</span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      {{ item.location || '-' }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      <span v-if="item.warranty_status === 'active'" class="text-green-600">
                        <CheckCircleIcon class="inline h-4 w-4" /> Active
                      </span>
                      <span v-else-if="item.warranty_status === 'expiring_soon'" class="text-yellow-600">
                        <ExclamationTriangleIcon class="inline h-4 w-4" /> Expiring
                      </span>
                      <span v-else-if="item.warranty_status === 'expired'" class="text-red-600">
                        <XCircleIcon class="inline h-4 w-4" /> Expired
                      </span>
                      <span v-else class="text-gray-400">-</span>
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                      <router-link
                        :to="{ name: 'cmdb-detail', params: { id: item.id } }"
                        class="text-deep-sea-600 hover:text-deep-sea-900"
                      >
                        View<span class="sr-only">, {{ item.name }}</span>
                      </router-link>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <nav v-if="configItems?.meta" class="flex items-center justify-between border-t border-gray-200 px-4 sm:px-0 mt-6">
        <div class="-mt-px flex w-0 flex-1">
          <button
            @click="previousPage"
            :disabled="!configItems.links.prev"
            class="inline-flex items-center border-t-2 border-transparent pr-1 pt-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <ArrowLongLeftIcon class="mr-3 h-5 w-5 text-gray-400" aria-hidden="true" />
            Previous
          </button>
        </div>
        <div class="hidden md:-mt-px md:flex">
          <span class="inline-flex items-center border-t-2 border-transparent px-4 pt-4 text-sm font-medium text-gray-500">
            Page {{ configItems.meta.current_page }} of {{ configItems.meta.last_page }}
          </span>
        </div>
        <div class="-mt-px flex w-0 flex-1 justify-end">
          <button
            @click="nextPage"
            :disabled="!configItems.links.next"
            class="inline-flex items-center border-t-2 border-transparent pl-1 pt-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
            <ArrowLongRightIcon class="ml-3 h-5 w-5 text-gray-400" aria-hidden="true" />
          </button>
        </div>
      </nav>
    </div>

    <!-- Create Modal -->
    <CreateConfigurationItemModal
      v-if="showCreateModal"
      :open="showCreateModal"
      @close="showCreateModal = false"
      @created="handleItemCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { useRouter } from 'vue-router'
import {
  PlusIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
  ArrowLongLeftIcon,
  ArrowLongRightIcon
} from '@heroicons/vue/24/outline'
import api from '@/services/api'
import CreateConfigurationItemModal from './CreateConfigurationItemModal.vue'
import type { ConfigurationItem } from '@/types'

const router = useRouter()

const filters = ref({
  search: '',
  type: '',
  status: '',
  location: '',
  page: 1
})

const selectedItems = ref<number[]>([])
const showCreateModal = ref(false)

const queryParams = computed(() => {
  const params: any = {
    page: filters.value.page,
    per_page: 15
  }
  
  if (filters.value.search) params.search = filters.value.search
  if (filters.value.type) params.type = filters.value.type
  if (filters.value.status) params.status = filters.value.status
  if (filters.value.location) params.location = filters.value.location
  
  return params
})

// Fetch configuration items
const { data: configItems, isLoading, refetch } = useQuery({
  queryKey: ['configuration-items', queryParams],
  queryFn: async () => {
    const response = await api.get('/api/v1/configuration-items', { params: queryParams.value })
    return response.data
  }
})

// Fetch stats
const { data: stats } = useQuery({
  queryKey: ['configuration-items-stats'],
  queryFn: async () => {
    const response = await api.get('/api/v1/configuration-items/stats')
    return response.data.data
  }
})

// Reset page when filters change
watch([() => filters.value.search, () => filters.value.type, () => filters.value.status, () => filters.value.location], () => {
  filters.value.page = 1
})

const isAllSelected = computed(() => {
  return configItems.value?.data?.length > 0 && selectedItems.value.length === configItems.value.data.length
})

const isIndeterminate = computed(() => {
  return selectedItems.value.length > 0 && selectedItems.value.length < (configItems.value?.data?.length || 0)
})

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedItems.value = []
  } else {
    selectedItems.value = configItems.value?.data?.map((item: ConfigurationItem) => item.id) || []
  }
}

const previousPage = () => {
  if (filters.value.page > 1) {
    filters.value.page--
  }
}

const nextPage = () => {
  if (configItems.value?.meta && filters.value.page < configItems.value.meta.last_page) {
    filters.value.page++
  }
}

const handleItemCreated = () => {
  refetch()
}

const formatType = (type: string) => {
  return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatStatus = (status: string) => {
  return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
    maintenance: 'bg-yellow-100 text-yellow-800',
    retired: 'bg-orange-100 text-orange-800',
    disposed: 'bg-red-100 text-red-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}
</script>