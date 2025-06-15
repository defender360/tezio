<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center gap-4 mb-2">
        <router-link
          to="/reports"
          class="text-gray-500 hover:text-gray-700 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </router-link>
        <h1 class="text-2xl font-bold text-gray-900">SLA Compliance Reports</h1>
      </div>
      <p class="text-gray-600">
        Monitor service level agreement performance and compliance metrics
      </p>
    </div>

    <!-- Date Range and Filters -->
    <div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Time Period</label>
          <select
            v-model="selectedPeriod"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="7d">Last 7 Days</option>
            <option value="30d">Last 30 Days</option>
            <option value="90d">Last 90 Days</option>
            <option value="1y">Last Year</option>
            <option value="custom">Custom Range</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Service Type</label>
          <select
            v-model="selectedService"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">All Services</option>
            <option value="incident">Incident Management</option>
            <option value="service_request">Service Requests</option>
            <option value="change">Change Management</option>
            <option value="problem">Problem Management</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
          <select
            v-model="selectedPriority"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">All Priorities</option>
            <option value="critical">Critical</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
          </select>
        </div>
        
        <div class="flex items-end">
          <button
            @click="refreshData"
            :disabled="isLoading"
            class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
          >
            <LoadingSpinner v-if="isLoading" class="w-4 h-4" />
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Refresh
          </button>
        </div>
      </div>
      
      <!-- Custom Date Range -->
      <div v-if="selectedPeriod === 'custom'" class="mt-4 flex items-center gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
          <input
            v-model="customDateRange.from"
            type="date"
            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
          <input
            v-model="customDateRange.to"
            type="date"
            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center py-12">
      <LoadingSpinner />
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
      <p class="text-red-800">Error loading SLA data: {{ error.message }}</p>
    </div>

    <!-- SLA Overview -->
    <div v-else-if="slaData" class="space-y-6">
      <!-- Overall SLA Metrics -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Overall Compliance</p>
              <p class="text-3xl font-bold text-gray-900">{{ slaData.overallCompliance }}%</p>
              <p class="text-sm text-gray-500">
                <span :class="[
                  slaData.complianceChange >= 0 ? 'text-green-600' : 'text-red-600'
                ]">
                  {{ slaData.complianceChange >= 0 ? '+' : '' }}{{ slaData.complianceChange }}%
                </span>
                from last period
              </p>
            </div>
            <div class="p-3 bg-green-100 rounded-full">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Met SLA</p>
              <p class="text-3xl font-bold text-gray-900">{{ slaData.metSLA }}</p>
              <p class="text-sm text-gray-500">
                out of {{ slaData.totalItems }} items
              </p>
            </div>
            <div class="p-3 bg-blue-100 rounded-full">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
          </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Breached SLA</p>
              <p class="text-3xl font-bold text-gray-900">{{ slaData.breachedSLA }}</p>
              <p class="text-sm text-gray-500">
                {{ slaData.breachPercentage }}% of total
              </p>
            </div>
            <div class="p-3 bg-red-100 rounded-full">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
            </div>
          </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">At Risk</p>
              <p class="text-3xl font-bold text-gray-900">{{ slaData.atRisk }}</p>
              <p class="text-sm text-gray-500">
                within 10% of SLA target
              </p>
            </div>
            <div class="p-3 bg-yellow-100 rounded-full">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- SLA Trend Chart -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">SLA Compliance Trend</h3>
        <div class="h-64 flex items-center justify-center text-gray-500">
          <!-- Placeholder for chart -->
          <div class="text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <p>SLA Trend Chart Placeholder</p>
            <p class="text-sm">Shows compliance trends over time</p>
          </div>
        </div>
      </div>

      <!-- Service-wise SLA Performance -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Service-wise Performance</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Service Type
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Total Items
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Met SLA
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Compliance %
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Avg Response Time
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Avg Resolution Time
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="service in slaData.servicePerformance" :key="service.type" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ service.type }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ service.total }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ service.metSLA }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="w-16 bg-gray-200 rounded-full h-2 mr-3">
                      <div
                        :class="[
                          'h-2 rounded-full',
                          service.compliance >= 95
                            ? 'bg-green-500'
                            : service.compliance >= 85
                            ? 'bg-yellow-500'
                            : 'bg-red-500'
                        ]"
                        :style="{ width: `${service.compliance}%` }"
                      ></div>
                    </div>
                    <span
                      :class="[
                        'text-sm font-medium',
                        service.compliance >= 95
                          ? 'text-green-600'
                          : service.compliance >= 85
                          ? 'text-yellow-600'
                          : 'text-red-600'
                      ]"
                    >
                      {{ service.compliance }}%
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ service.avgResponseTime }}h
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ service.avgResolutionTime }}h
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Priority-wise SLA Performance -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Priority-wise Performance</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div
            v-for="priority in slaData.priorityPerformance"
            :key="priority.name"
            class="border border-gray-200 rounded-lg p-4"
          >
            <div class="flex items-center justify-between mb-2">
              <h4 class="text-sm font-medium text-gray-700">{{ priority.name }}</h4>
              <span
                :class="[
                  'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                  getPriorityColor(priority.name)
                ]"
              >
                {{ priority.name }}
              </span>
            </div>
            
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">Compliance:</span>
                <span
                  :class="[
                    'font-medium',
                    priority.compliance >= 95
                      ? 'text-green-600'
                      : priority.compliance >= 85
                      ? 'text-yellow-600'
                      : 'text-red-600'
                  ]"
                >
                  {{ priority.compliance }}%
                </span>
              </div>
              
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">Response SLA:</span>
                <span class="text-gray-900">{{ priority.responseSLA }}h</span>
              </div>
              
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">Resolution SLA:</span>
                <span class="text-gray-900">{{ priority.resolutionSLA }}h</span>
              </div>
              
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">Avg Response:</span>
                <span
                  :class="[
                    priority.avgResponse <= priority.responseSLA
                      ? 'text-green-600'
                      : 'text-red-600'
                  ]"
                >
                  {{ priority.avgResponse }}h
                </span>
              </div>
              
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">Avg Resolution:</span>
                <span
                  :class="[
                    priority.avgResolution <= priority.resolutionSLA
                      ? 'text-green-600'
                      : 'text-red-600'
                  ]"
                >
                  {{ priority.avgResolution }}h
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SLA Breaches -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Recent SLA Breaches</h3>
          <router-link
            to="/incidents?sla=breached"
            class="text-sm text-blue-600 hover:text-blue-800"
          >
            View All Breaches
          </router-link>
        </div>
        
        <div v-if="slaData.recentBreaches?.length === 0" class="text-center py-8 text-gray-500">
          <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p>No recent SLA breaches</p>
        </div>
        
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Item
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Type
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Priority
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  SLA Target
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actual Time
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Breach Time
                </th>
                <th class="relative px-6 py-3">
                  <span class="sr-only">Actions</span>
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="breach in slaData.recentBreaches" :key="breach.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ breach.number }}</div>
                  <div class="text-sm text-gray-500">{{ breach.title }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ breach.type }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <PriorityBadge :priority="breach.priority" />
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ breach.slaTarget }}h
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                  {{ breach.actualTime }}h
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                  +{{ breach.breachTime }}h
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <router-link
                    :to="`/${breach.type}s/${breach.id}`"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    View
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Export Options -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Export SLA Report</h3>
        <div class="flex items-center gap-4">
          <button
            @click="exportReport('pdf')"
            class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export as PDF
          </button>
          
          <button
            @click="exportReport('excel')"
            class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export as Excel
          </button>
          
          <button
            @click="scheduleReport"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Schedule Report
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { useToast } from 'vue-toastification'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import PriorityBadge from '@/components/common/PriorityBadge.vue'
import { api } from '@/services/api'

const toast = useToast()

// State
const selectedPeriod = ref('30d')
const selectedService = ref('')
const selectedPriority = ref('')
const customDateRange = ref({
  from: '',
  to: ''
})

// Computed date range
const dateRange = computed(() => {
  if (selectedPeriod.value === 'custom') {
    return {
      from: customDateRange.value.from,
      to: customDateRange.value.to
    }
  }
  
  const now = new Date()
  const from = new Date()
  
  switch (selectedPeriod.value) {
    case '7d':
      from.setDate(now.getDate() - 7)
      break
    case '30d':
      from.setDate(now.getDate() - 30)
      break
    case '90d':
      from.setDate(now.getDate() - 90)
      break
    case '1y':
      from.setFullYear(now.getFullYear() - 1)
      break
  }
  
  return {
    from: from.toISOString().split('T')[0],
    to: now.toISOString().split('T')[0]
  }
})

// Query
const { data: slaData, isLoading, error, refetch } = useQuery({
  queryKey: ['sla-reports', dateRange, selectedService, selectedPriority],
  queryFn: async () => {
    const params = new URLSearchParams({
      from: dateRange.value.from,
      to: dateRange.value.to
    })
    
    if (selectedService.value) {
      params.append('service', selectedService.value)
    }
    
    if (selectedPriority.value) {
      params.append('priority', selectedPriority.value)
    }
    
    const response = await api.get(`/reports/sla?${params}`)
    return response.data
  },
  enabled: computed(() => !!dateRange.value.from && !!dateRange.value.to)
})

// Methods
const refreshData = () => {
  refetch()
}

const getPriorityColor = (priority: string) => {
  const colors: Record<string, string> = {
    critical: 'bg-red-100 text-red-800',
    high: 'bg-orange-100 text-orange-800',
    medium: 'bg-yellow-100 text-yellow-800',
    low: 'bg-green-100 text-green-800'
  }
  return colors[priority.toLowerCase()] || 'bg-gray-100 text-gray-800'
}

const exportReport = async (format: 'pdf' | 'excel') => {
  try {
    const params = new URLSearchParams({
      from: dateRange.value.from,
      to: dateRange.value.to,
      format
    })
    
    if (selectedService.value) {
      params.append('service', selectedService.value)
    }
    
    if (selectedPriority.value) {
      params.append('priority', selectedPriority.value)
    }
    
    const response = await api.get(`/reports/sla/export?${params}`, {
      responseType: 'blob'
    })
    
    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `sla-report.${format === 'pdf' ? 'pdf' : 'xlsx'}`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    
    toast.success(`SLA report exported as ${format.toUpperCase()}`)
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to export report')
  }
}

const scheduleReport = () => {
  toast.info('Report scheduling coming soon!')
}
</script>