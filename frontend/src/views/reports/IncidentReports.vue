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
        <h1 class="text-2xl font-bold text-gray-900">Incident Reports</h1>
      </div>
      <p class="text-gray-600">
        Comprehensive analytics and insights on incident management performance
      </p>
    </div>

    <!-- Date Range Selector -->
    <div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
      <div class="flex items-center gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Time Period</label>
          <select
            v-model="selectedPeriod"
            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="7d">Last 7 Days</option>
            <option value="30d">Last 30 Days</option>
            <option value="90d">Last 90 Days</option>
            <option value="1y">Last Year</option>
            <option value="custom">Custom Range</option>
          </select>
        </div>
        
        <div v-if="selectedPeriod === 'custom'" class="flex items-center gap-2">
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
        
        <div class="flex items-end">
          <button
            @click="generateReport"
            :disabled="isGenerating"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center gap-2"
          >
            <LoadingSpinner v-if="isGenerating" class="w-4 h-4" />
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Generate Report
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center py-12">
      <LoadingSpinner />
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
      <p class="text-red-800">Error loading incident data: {{ error.message }}</p>
    </div>

    <!-- Report Content -->
    <div v-else-if="reportData" class="space-y-6">
      <!-- Key Metrics -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total Incidents</p>
              <p class="text-2xl font-bold text-gray-900">{{ reportData.totalIncidents }}</p>
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
              <p class="text-sm font-medium text-gray-600">Avg Resolution Time</p>
              <p class="text-2xl font-bold text-gray-900">{{ reportData.avgResolutionTime }}h</p>
            </div>
            <div class="p-3 bg-blue-100 rounded-full">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">SLA Compliance</p>
              <p class="text-2xl font-bold text-gray-900">{{ reportData.slaCompliance }}%</p>
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
              <p class="text-sm font-medium text-gray-600">First Call Resolution</p>
              <p class="text-2xl font-bold text-gray-900">{{ reportData.firstCallResolution }}%</p>
            </div>
            <div class="p-3 bg-yellow-100 rounded-full">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Incident Volume Trend -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Incident Volume Trend</h3>
          <div class="h-64 flex items-center justify-center text-gray-500">
            <!-- Placeholder for chart -->
            <div class="text-center">
              <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              <p>Chart Component Placeholder</p>
              <p class="text-sm">Shows daily incident volumes over time</p>
            </div>
          </div>
        </div>

        <!-- Priority Distribution -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Priority Distribution</h3>
          <div class="space-y-3">
            <div v-for="priority in reportData.priorityDistribution" :key="priority.name" class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div
                  :class="[
                    'w-4 h-4 rounded-full',
                    getPriorityColor(priority.name)
                  ]"
                ></div>
                <span class="text-sm font-medium text-gray-700">{{ priority.name }}</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-24 bg-gray-200 rounded-full h-2">
                  <div
                    :class="[
                      'h-2 rounded-full',
                      getPriorityColor(priority.name)
                    ]"
                    :style="{ width: `${priority.percentage}%` }"
                  ></div>
                </div>
                <span class="text-sm text-gray-600 w-12">{{ priority.count }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Category Analysis -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Category Analysis</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Category
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Total Incidents
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Avg Resolution Time
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  SLA Compliance
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  % of Total
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="category in reportData.categoryAnalysis" :key="category.name" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ category.name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ category.totalIncidents }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ category.avgResolutionTime }}h
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      category.slaCompliance >= 95
                        ? 'bg-green-100 text-green-800'
                        : category.slaCompliance >= 85
                        ? 'bg-yellow-100 text-yellow-800'
                        : 'bg-red-100 text-red-800'
                    ]"
                  >
                    {{ category.slaCompliance }}%
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ category.percentage }}%
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Team Performance -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Team Performance</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Assignee
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Assigned
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Resolved
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Avg Resolution Time
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Customer Rating
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="member in reportData.teamPerformance" :key="member.name" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ member.name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ member.assigned }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ member.resolved }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ member.avgResolutionTime }}h
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="flex items-center">
                      <svg
                        v-for="star in 5"
                        :key="star"
                        :class="[
                          'w-4 h-4',
                          star <= (member.rating || 0)
                            ? 'text-yellow-400 fill-current'
                            : 'text-gray-300'
                        ]"
                        viewBox="0 0 20 20"
                      >
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                    </div>
                    <span class="ml-2 text-sm text-gray-500">
                      ({{ member.ratingCount || 0 }})
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Export Options -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Export Report</h3>
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
import { ref, computed, watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { useToast } from 'vue-toastification'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import { api } from '@/services/api'

const toast = useToast()

// State
const selectedPeriod = ref('30d')
const customDateRange = ref({
  from: '',
  to: ''
})
const isGenerating = ref(false)

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
const { data: reportData, isLoading, error, refetch } = useQuery({
  queryKey: ['incident-reports', dateRange],
  queryFn: async () => {
    const params = new URLSearchParams({
      from: dateRange.value.from,
      to: dateRange.value.to
    })
    const response = await api.get(`/reports/incidents?${params}`)
    return response.data
  },
  enabled: computed(() => !!dateRange.value.from && !!dateRange.value.to)
})

// Methods
const generateReport = async () => {
  isGenerating.value = true
  try {
    await refetch()
    toast.success('Report generated successfully')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to generate report')
  } finally {
    isGenerating.value = false
  }
}

const getPriorityColor = (priority: string) => {
  const colors: Record<string, string> = {
    critical: 'bg-red-500',
    high: 'bg-orange-500',
    medium: 'bg-yellow-500',
    low: 'bg-green-500'
  }
  return colors[priority.toLowerCase()] || 'bg-gray-500'
}

const exportReport = async (format: 'pdf' | 'excel') => {
  try {
    const params = new URLSearchParams({
      from: dateRange.value.from,
      to: dateRange.value.to,
      format
    })
    
    const response = await api.get(`/reports/incidents/export?${params}`, {
      responseType: 'blob'
    })
    
    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `incident-report.${format === 'pdf' ? 'pdf' : 'xlsx'}`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    
    toast.success(`Report exported as ${format.toUpperCase()}`)
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to export report')
  }
}

const scheduleReport = () => {
  toast.info('Report scheduling coming soon!')
}

// Watch for period changes
watch(selectedPeriod, () => {
  if (selectedPeriod.value !== 'custom') {
    generateReport()
  }
})
</script>