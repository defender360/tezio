<template>
  <div class="py-6">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
            Service Requests
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Browse and request services from the catalog
          </p>
        </div>
        <div class="mt-4 flex md:ml-4 md:mt-0">
          <button
            @click="showCatalogModal = true"
            type="button"
            class="ml-3 inline-flex items-center rounded-md bg-deep-sea-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-deep-sea-500"
          >
            <ShoppingCartIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
            Service Catalog
          </button>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
          <dt class="truncate text-sm font-medium text-gray-500">My Requests</dt>
          <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
            {{ stats?.total_requests || 0 }}
          </dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
          <dt class="truncate text-sm font-medium text-gray-500">In Progress</dt>
          <dd class="mt-1 text-3xl font-semibold tracking-tight text-yellow-600">
            {{ stats?.in_progress || 0 }}
          </dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
          <dt class="truncate text-sm font-medium text-gray-500">Pending Approval</dt>
          <dd class="mt-1 text-3xl font-semibold tracking-tight text-orange-600">
            {{ stats?.pending_approval || 0 }}
          </dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
          <dt class="truncate text-sm font-medium text-gray-500">Completed</dt>
          <dd class="mt-1 text-3xl font-semibold tracking-tight text-green-600">
            {{ stats?.completed || 0 }}
          </dd>
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
            placeholder="Search requests..."
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select
            v-model="filters.status"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
          >
            <option value="">All Statuses</option>
            <option value="submitted">Submitted</option>
            <option value="in_progress">In Progress</option>
            <option value="pending_approval">Pending Approval</option>
            <option value="approved">Approved</option>
            <option value="on_hold">On Hold</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Priority</label>
          <select
            v-model="filters.priority"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
          >
            <option value="">All Priorities</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="critical">Critical</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Category</label>
          <select
            v-model="filters.category"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
          >
            <option value="">All Categories</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>
      </div>

      <!-- Service Requests List -->
      <div class="mt-8 flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
              <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                      Request Number
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Service
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Requester
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Status
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Priority
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Due Date
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Progress
                    </th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
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
                        Loading service requests...
                      </div>
                    </td>
                  </tr>
                  <tr v-else-if="!serviceRequests?.data?.length">
                    <td colspan="8" class="text-center py-4 text-gray-500">
                      No service requests found
                    </td>
                  </tr>
                  <tr v-else v-for="request in serviceRequests.data" :key="request.id">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                      <div class="font-medium text-gray-900">
                        {{ request.request_number }}
                      </div>
                      <div class="text-gray-500">{{ formatDate(request.created_at) }}</div>
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-500">
                      <div class="font-medium text-gray-900">{{ request.service_item.name }}</div>
                      <div class="text-gray-500">{{ request.service_item.category.name }}</div>
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-500">
                      <div class="flex items-center">
                        <img
                          class="h-8 w-8 rounded-full"
                          :src="request.requester.avatar || 'https://ui-avatars.com/api/?name=' + request.requester.name"
                          :alt="request.requester.name"
                        />
                        <div class="ml-2">
                          <div class="font-medium text-gray-900">{{ request.requester.name }}</div>
                          <div v-if="request.requested_for_id !== request.requester_id" class="text-xs text-gray-500">
                            For: {{ request.requested_for.name }}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      <span :class="[getStatusColor(request.status), 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium']">
                        {{ formatStatus(request.status) }}
                      </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      <span :class="[getPriorityColor(request.priority), 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium']">
                        {{ request.priority }}
                      </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      <div v-if="request.due_date">
                        <p>{{ formatDate(request.due_date) }}</p>
                        <p v-if="request.is_overdue" class="text-xs text-red-600">Overdue</p>
                      </div>
                      <span v-else class="text-gray-400">-</span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      <div class="flex items-center">
                        <div class="flex-1">
                          <div class="bg-gray-200 rounded-full overflow-hidden">
                            <div
                              class="bg-deep-sea-600 h-2 rounded-full"
                              :style="{ width: `${request.completion_percentage}%` }"
                            />
                          </div>
                        </div>
                        <span class="ml-2 text-xs">{{ request.completion_percentage }}%</span>
                      </div>
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                      <router-link
                        :to="{ name: 'service-request-detail', params: { id: request.id } }"
                        class="text-deep-sea-600 hover:text-deep-sea-900"
                      >
                        View<span class="sr-only">, {{ request.request_number }}</span>
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
      <nav v-if="serviceRequests?.meta" class="flex items-center justify-between border-t border-gray-200 px-4 sm:px-0 mt-6">
        <div class="-mt-px flex w-0 flex-1">
          <button
            @click="previousPage"
            :disabled="!serviceRequests.links.prev"
            class="inline-flex items-center border-t-2 border-transparent pr-1 pt-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <ArrowLongLeftIcon class="mr-3 h-5 w-5 text-gray-400" aria-hidden="true" />
            Previous
          </button>
        </div>
        <div class="hidden md:-mt-px md:flex">
          <span class="inline-flex items-center border-t-2 border-transparent px-4 pt-4 text-sm font-medium text-gray-500">
            Page {{ serviceRequests.meta.current_page }} of {{ serviceRequests.meta.last_page }}
          </span>
        </div>
        <div class="-mt-px flex w-0 flex-1 justify-end">
          <button
            @click="nextPage"
            :disabled="!serviceRequests.links.next"
            class="inline-flex items-center border-t-2 border-transparent pl-1 pt-4 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
            <ArrowLongRightIcon class="ml-3 h-5 w-5 text-gray-400" aria-hidden="true" />
          </button>
        </div>
      </nav>
    </div>

    <!-- Service Catalog Modal -->
    <ServiceCatalogModal
      v-if="showCatalogModal"
      :open="showCatalogModal"
      @close="showCatalogModal = false"
      @request-created="handleRequestCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import {
  ShoppingCartIcon,
  ArrowLongLeftIcon,
  ArrowLongRightIcon
} from '@heroicons/vue/24/outline'
import api from '@/services/api'
import { formatDate, formatStatus } from '@/utils/format'
import ServiceCatalogModal from './ServiceCatalogModal.vue'
import type { ServiceRequest } from '@/types'

const filters = ref({
  search: '',
  status: '',
  priority: '',
  category: '',
  page: 1
})

const showCatalogModal = ref(false)

const queryParams = computed(() => {
  const params: any = {
    page: filters.value.page,
    per_page: 15
  }
  
  if (filters.value.search) params.search = filters.value.search
  if (filters.value.status) params.status = filters.value.status
  if (filters.value.priority) params.priority = filters.value.priority
  if (filters.value.category) params.category = filters.value.category
  
  return params
})

// Fetch service requests
const { data: serviceRequests, isLoading, refetch } = useQuery({
  queryKey: ['service-requests', queryParams],
  queryFn: async () => {
    const response = await api.get('/api/v1/service-requests', { params: queryParams.value })
    return response.data
  }
})

// Fetch stats
const { data: stats } = useQuery({
  queryKey: ['service-request-stats'],
  queryFn: async () => {
    const response = await api.get('/api/v1/service-requests/stats')
    return response.data.data
  }
})

// Fetch categories
const { data: categories } = useQuery({
  queryKey: ['service-categories'],
  queryFn: async () => {
    const response = await api.get('/api/v1/service-catalog/categories')
    return response.data.data
  }
})

// Reset page when filters change
watch([() => filters.value.search, () => filters.value.status, () => filters.value.priority, () => filters.value.category], () => {
  filters.value.page = 1
})

const previousPage = () => {
  if (filters.value.page > 1) {
    filters.value.page--
  }
}

const nextPage = () => {
  if (serviceRequests.value?.meta && filters.value.page < serviceRequests.value.meta.last_page) {
    filters.value.page++
  }
}

const handleRequestCreated = () => {
  showCatalogModal.value = false
  refetch()
}

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    submitted: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    pending_approval: 'bg-orange-100 text-orange-800',
    approved: 'bg-green-100 text-green-800',
    on_hold: 'bg-gray-100 text-gray-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    closed: 'bg-gray-100 text-gray-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const getPriorityColor = (priority: string) => {
  const colors: Record<string, string> = {
    low: 'bg-green-100 text-green-800',
    medium: 'bg-yellow-100 text-yellow-800',
    high: 'bg-orange-100 text-orange-800',
    critical: 'bg-red-100 text-red-800'
  }
  return colors[priority] || 'bg-gray-100 text-gray-800'
}
</script>