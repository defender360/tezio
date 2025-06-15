<template>
  <div>
      <!-- Dashboard Navigation -->
      <div class="mb-6 flex flex-wrap gap-4">
        <router-link
          to="/dashboard"
          class="px-4 py-2 rounded-lg font-medium transition-colors"
          :class="$route.path === '/dashboard' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
        >
          Overview
        </router-link>
        <router-link
          to="/dashboard/executive"
          class="px-4 py-2 rounded-lg font-medium transition-colors"
          :class="$route.path === '/dashboard/executive' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
        >
          Executive Dashboard
        </router-link>
        <router-link
          to="/dashboard/operational"
          class="px-4 py-2 rounded-lg font-medium transition-colors"
          :class="$route.path === '/dashboard/operational' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
        >
          Operational Dashboard
        </router-link>
        <router-link
          to="/dashboard/team"
          class="px-4 py-2 rounded-lg font-medium transition-colors"
          :class="$route.path === '/dashboard/team' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
        >
          Team Dashboard
        </router-link>
      </div>

      <!-- Metrics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <MetricCard
          title="Total Incidents"
          :value="metrics.total_incidents"
          :trend="{ value: 12, direction: 'up' }"
          icon="ticket"
          color="tech-horizon"
        />
        <MetricCard
          title="Open Incidents"
          :value="metrics.open_incidents"
          :trend="{ value: 5, direction: 'down' }"
          icon="clock"
          color="vital-energy"
        />
        <MetricCard
          title="Overdue"
          :value="metrics.overdue_incidents"
          :trend="{ value: 2, direction: 'up' }"
          icon="exclamation"
          color="priority-high"
        />
        <MetricCard
          title="SLA Compliance"
          :value="`${metrics.sla_compliance}%`"
          :trend="{ value: 3, direction: 'up' }"
          icon="chart"
          color="arctic-breeze"
        />
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-defender-md p-6">
          <h2 class="text-lg font-brain font-semibold text-deep-sea-500 mb-4">
            Incidents by Priority
          </h2>
          <div class="h-64">
            <PriorityChart :data="priorityData" />
          </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-defender-md p-6">
          <h2 class="text-lg font-brain font-semibold text-deep-sea-500 mb-4">
            SLA Performance Trend
          </h2>
          <div class="h-64">
            <SLATrendChart :data="slaTrendData" />
          </div>
        </div>
      </div>

      <!-- Recent Incidents Table -->
      <div class="bg-white rounded-lg shadow-defender-md">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex justify-between items-center">
            <h2 class="text-lg font-brain font-semibold text-deep-sea-500">
              Recent Incidents
            </h2>
            <router-link
              to="/incidents"
              class="text-tech-horizon-500 hover:text-tech-horizon-600 text-sm font-medium"
            >
              View All →
            </router-link>
          </div>
        </div>
        <RecentIncidentsTable :incidents="recentIncidents" />
      </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { api } from '@/services/api'
import MetricCard from '@/components/dashboard/MetricCard.vue'
import PriorityChart from '@/components/dashboard/PriorityChart.vue'
import SLATrendChart from '@/components/dashboard/SLATrendChart.vue'
import RecentIncidentsTable from '@/components/dashboard/RecentIncidentsTable.vue'

// Default metrics data
const defaultMetrics = {
  total_incidents: 0,
  open_incidents: 0,
  overdue_incidents: 0,
  sla_compliance: 0,
  incidents_today: 0,
  avg_resolution_time: 0
}

// Fetch dashboard metrics
const { data: metricsData } = useQuery({
  queryKey: ['dashboard-metrics'],
  queryFn: async () => {
    const response = await api.get('/api/v1/dashboard/metrics')
    return response.data
  },
  refetchInterval: 30000 // Refresh every 30 seconds
})

// Computed metrics with default fallback
const metrics = computed(() => metricsData.value || defaultMetrics)

// Fetch recent incidents
const { data: recentIncidentsData } = useQuery({
  queryKey: ['recent-incidents'],
  queryFn: async () => {
    const response = await api.get('/api/v1/dashboard/recent-incidents')
    return response.data
  }
})

// Computed recent incidents with default fallback
const recentIncidents = computed(() => recentIncidentsData.value || [])

// Mock data for charts (replace with real API calls)
const priorityData = ref({
  labels: ['Critical', 'High', 'Medium', 'Low'],
  datasets: [{
    data: [5, 15, 30, 25],
    backgroundColor: ['#D32F2F', '#F57C00', '#FBC02D', '#388E3C']
  }]
})

const slaTrendData = ref({
  labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
  datasets: [{
    label: 'SLA Compliance %',
    data: [95, 92, 94, 96, 93, 97, 95],
    borderColor: '#0070AF',
    backgroundColor: 'rgba(0, 112, 175, 0.1)',
    fill: true,
    tension: 0.4
  }]
})
</script>