<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Reports Dashboard</h1>
      <p class="mt-2 text-gray-600">
        Comprehensive analytics and reports for IT service management
      </p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">Open Incidents</p>
            <p class="text-3xl font-bold text-gray-900">{{ stats?.openIncidents || 0 }}</p>
            <p class="text-sm text-gray-500">
              <span :class="[
                stats?.incidentsChange >= 0 ? 'text-red-600' : 'text-green-600'
              ]">
                {{ stats?.incidentsChange >= 0 ? '+' : '' }}{{ stats?.incidentsChange || 0 }}%
              </span>
              from last week
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
            <p class="text-sm font-medium text-gray-600">SLA Compliance</p>
            <p class="text-3xl font-bold text-gray-900">{{ stats?.slaCompliance || 0 }}%</p>
            <p class="text-sm text-gray-500">
              <span :class="[
                stats?.slaChange >= 0 ? 'text-green-600' : 'text-red-600'
              ]">
                {{ stats?.slaChange >= 0 ? '+' : '' }}{{ stats?.slaChange || 0 }}%
              </span>
              from last month
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
            <p class="text-sm font-medium text-gray-600">Avg Resolution Time</p>
            <p class="text-3xl font-bold text-gray-900">{{ stats?.avgResolutionTime || 0 }}h</p>
            <p class="text-sm text-gray-500">
              <span :class="[
                stats?.resolutionTimeChange <= 0 ? 'text-green-600' : 'text-red-600'
              ]">
                {{ stats?.resolutionTimeChange >= 0 ? '+' : '' }}{{ stats?.resolutionTimeChange || 0 }}%
              </span>
              from last month
            </p>
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
            <p class="text-sm font-medium text-gray-600">Customer Satisfaction</p>
            <p class="text-3xl font-bold text-gray-900">{{ stats?.customerSatisfaction || 0 }}%</p>
            <p class="text-sm text-gray-500">
              <span :class="[
                stats?.satisfactionChange >= 0 ? 'text-green-600' : 'text-red-600'
              ]">
                {{ stats?.satisfactionChange >= 0 ? '+' : '' }}{{ stats?.satisfactionChange || 0 }}%
              </span>
              from last month
            </p>
          </div>
          <div class="p-3 bg-yellow-100 rounded-full">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Report Categories -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
      <!-- Incident Reports -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center mb-4">
          <div class="p-2 bg-red-100 rounded-lg mr-3">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900">Incident Reports</h3>
        </div>
        <p class="text-gray-600 mb-4">
          Comprehensive analytics on incident trends, resolution times, and performance metrics.
        </p>
        <router-link
          to="/reports/incidents"
          class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 transition-colors"
        >
          View Reports
          <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </router-link>
      </div>

      <!-- SLA Reports -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center mb-4">
          <div class="p-2 bg-green-100 rounded-lg mr-3">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900">SLA Compliance</h3>
        </div>
        <p class="text-gray-600 mb-4">
          Monitor service level agreement compliance and performance against targets.
        </p>
        <router-link
          to="/reports/sla"
          class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition-colors"
        >
          View Reports
          <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </router-link>
      </div>

      <!-- Change Management Reports -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center mb-4">
          <div class="p-2 bg-blue-100 rounded-lg mr-3">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900">Change Reports</h3>
        </div>
        <p class="text-gray-600 mb-4">
          Analyze change success rates, approval times, and implementation metrics.
        </p>
        <button
          @click="generateChangeReport"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors"
        >
          View Reports
          <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>

      <!-- Problem Management Reports -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center mb-4">
          <div class="p-2 bg-purple-100 rounded-lg mr-3">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900">Problem Reports</h3>
        </div>
        <p class="text-gray-600 mb-4">
          Track problem resolution effectiveness and root cause analysis metrics.
        </p>
        <button
          @click="generateProblemReport"
          class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700 transition-colors"
        >
          View Reports
          <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>

      <!-- Performance Reports -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center mb-4">
          <div class="p-2 bg-yellow-100 rounded-lg mr-3">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900">Performance Reports</h3>
        </div>
        <p class="text-gray-600 mb-4">
          Team performance metrics, workload analysis, and productivity insights.
        </p>
        <button
          @click="generatePerformanceReport"
          class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-md hover:bg-yellow-700 transition-colors"
        >
          View Reports
          <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>

      <!-- Custom Reports -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center mb-4">
          <div class="p-2 bg-gray-100 rounded-lg mr-3">
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900">Custom Reports</h3>
        </div>
        <p class="text-gray-600 mb-4">
          Create custom reports with specific metrics and time periods tailored to your needs.
        </p>
        <button
          @click="createCustomReport"
          class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors"
        >
          Create Report
          <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Recent Reports -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Recent Reports</h3>
        <button
          @click="refreshReports"
          class="text-sm text-blue-600 hover:text-blue-800"
        >
          Refresh
        </button>
      </div>
      
      <div v-if="isLoadingReports" class="flex justify-center py-8">
        <LoadingSpinner />
      </div>
      
      <div v-else-if="recentReports?.length === 0" class="text-center py-8 text-gray-500">
        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p>No recent reports generated</p>
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Report Name
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Type
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Generated
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="relative px-6 py-3">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="report in recentReports" :key="report.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ report.name }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-500">{{ report.type }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <TimeAgo :date="report.createdAt" />
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    report.status === 'completed'
                      ? 'bg-green-100 text-green-800'
                      : report.status === 'processing'
                      ? 'bg-yellow-100 text-yellow-800'
                      : 'bg-red-100 text-red-800'
                  ]"
                >
                  {{ report.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center gap-2">
                  <button
                    v-if="report.status === 'completed'"
                    @click="downloadReport(report.id)"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    Download
                  </button>
                  <button
                    @click="deleteReport(report.id)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import { useToast } from 'vue-toastification'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import TimeAgo from '@/components/common/TimeAgo.vue'
import { api } from '@/services/api'

const router = useRouter()
const toast = useToast()

// Queries
const { data: stats } = useQuery({
  queryKey: ['dashboard-stats'],
  queryFn: async () => {
    const response = await api.get('/reports/stats')
    return response.data
  }
})

const { data: recentReports, isLoading: isLoadingReports, refetch: refreshReports } = useQuery({
  queryKey: ['recent-reports'],
  queryFn: async () => {
    const response = await api.get('/reports/recent')
    return response.data
  }
})

// Methods
const generateChangeReport = () => {
  toast.info('Change reports coming soon!')
}

const generateProblemReport = () => {
  toast.info('Problem reports coming soon!')
}

const generatePerformanceReport = () => {
  toast.info('Performance reports coming soon!')
}

const createCustomReport = () => {
  toast.info('Custom report builder coming soon!')
}

const downloadReport = async (reportId: string) => {
  try {
    const response = await api.get(`/reports/${reportId}/download`, {
      responseType: 'blob'
    })
    
    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `report-${reportId}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    
    toast.success('Report downloaded successfully')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to download report')
  }
}

const deleteReport = async (reportId: string) => {
  if (!confirm('Are you sure you want to delete this report?')) return
  
  try {
    await api.delete(`/reports/${reportId}`)
    refreshReports()
    toast.success('Report deleted successfully')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to delete report')
  }
}
</script>