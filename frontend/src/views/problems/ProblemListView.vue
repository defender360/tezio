<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Problem Management</h1>
        <router-link
          to="/problems/new"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          New Problem
        </router-link>
      </div>
      <p class="mt-2 text-gray-600">
        Identify and manage root causes of incidents
      </p>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select
            v-model="filters.status"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">All Statuses</option>
            <option value="new">New</option>
            <option value="investigating">Investigating</option>
            <option value="identified">Root Cause Identified</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
          <select
            v-model="filters.priority"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">All Priorities</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="critical">Critical</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Known Error</label>
          <select
            v-model="filters.isKnownError"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">All Problems</option>
            <option value="true">Known Errors Only</option>
            <option value="false">Not Known Errors</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
          <select
            v-model="filters.category"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">All Categories</option>
            <option value="hardware">Hardware</option>
            <option value="software">Software</option>
            <option value="network">Network</option>
            <option value="process">Process</option>
            <option value="human">Human Error</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search problems..."
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Total Problems</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats?.total || 0 }}</p>
          </div>
          <div class="p-3 bg-blue-100 rounded-full">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Known Errors</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats?.knownErrors || 0 }}</p>
          </div>
          <div class="p-3 bg-yellow-100 rounded-full">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Under Investigation</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats?.investigating || 0 }}</p>
          </div>
          <div class="p-3 bg-orange-100 rounded-full">
            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Resolved This Month</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats?.resolvedThisMonth || 0 }}</p>
          </div>
          <div class="p-3 bg-green-100 rounded-full">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Problems List -->
    <div class="space-y-4">
      <div v-if="isLoading" class="flex justify-center py-8">
        <LoadingSpinner />
      </div>
      
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-red-800">Error loading problems: {{ error.message }}</p>
      </div>
      
      <div v-else-if="problems?.length === 0" class="bg-gray-50 rounded-lg p-8 text-center">
        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-gray-600">No problems found</p>
      </div>
      
      <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Problem ID
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Title
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Priority
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Known Error
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Category
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Related Incidents
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Assignee
              </th>
              <th class="relative px-6 py-3">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="problem in problems" :key="problem.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <router-link
                  :to="`/problems/${problem.id}`"
                  class="text-blue-600 hover:text-blue-800 font-medium"
                >
                  {{ problem.number }}
                </router-link>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900">{{ problem.title }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <StatusBadge :status="problem.status" />
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <PriorityBadge :priority="problem.priority" />
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  v-if="problem.isKnownError"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                >
                  Known Error
                </span>
                <span v-else class="text-sm text-gray-500">-</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-900">{{ problem.category }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-900">{{ problem.relatedIncidentsCount || 0 }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ problem.assignee?.name || 'Unassigned' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <router-link
                  :to="`/problems/${problem.id}`"
                  class="text-blue-600 hover:text-blue-900"
                >
                  View
                </router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <Pagination
        v-if="problems && problems.length > 0"
        :current-page="currentPage"
        :total-pages="totalPages"
        @update:current-page="currentPage = $event"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import PriorityBadge from '@/components/common/PriorityBadge.vue'
import Pagination from '@/components/common/Pagination.vue'
import { api } from '@/services/api'

// Types
interface Problem {
  id: string
  number: string
  title: string
  description: string
  status: string
  priority: string
  category: string
  isKnownError: boolean
  rootCause?: string
  workaround?: string
  permanentFix?: string
  relatedIncidentsCount: number
  assignee?: {
    id: string
    name: string
  }
}

// State
const filters = ref({
  status: '',
  priority: '',
  isKnownError: '',
  category: '',
  search: ''
})
const currentPage = ref(1)

// Queries
const { data, isLoading, error } = useQuery({
  queryKey: ['problems', filters, currentPage],
  queryFn: async () => {
    const params = new URLSearchParams({
      page: currentPage.value.toString(),
      limit: '20',
      ...Object.fromEntries(
        Object.entries(filters.value).filter(([_, v]) => v !== '')
      )
    })
    const response = await api.get(`/problems?${params}`)
    return response.data
  }
})

const { data: stats } = useQuery({
  queryKey: ['problem-stats'],
  queryFn: async () => {
    const response = await api.get('/problems/stats')
    return response.data
  }
})

// Computed
const problems = computed(() => data.value?.items || [])
const totalPages = computed(() => data.value?.totalPages || 1)

// Watchers
watch(filters, () => {
  currentPage.value = 1
}, { deep: true })
</script>