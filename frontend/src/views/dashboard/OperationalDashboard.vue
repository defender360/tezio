<template>
  <div class="operational-dashboard">
    <!-- Header -->
    <div class="dashboard-header">
      <h1 class="text-2xl font-bold text-gray-900">Operational Dashboard</h1>
      <div class="flex items-center space-x-4">
        <select v-model="refreshInterval" @change="updateRefreshInterval" class="form-select">
          <option value="0">Manual Refresh</option>
          <option value="30">30 seconds</option>
          <option value="60">1 minute</option>
          <option value="300">5 minutes</option>
        </select>
        <button 
          @click="refreshDashboard" 
          :disabled="loading"
          class="btn-primary flex items-center space-x-2"
        >
          <RefreshIcon class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          <span>Refresh</span>
        </button>
      </div>
    </div>

    <!-- Real-time Indicators -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <RealTimeIndicator
        title="Active Incidents"
        :value="realtimeMetrics.active_incidents"
        :threshold="50"
        icon="ExclamationIcon"
      />
      <RealTimeIndicator
        title="Queue Length"
        :value="realtimeMetrics.queue_length"
        :threshold="20"
        icon="CollectionIcon"
      />
      <RealTimeIndicator
        title="SLA at Risk"
        :value="realtimeMetrics.sla_at_risk"
        :threshold="5"
        icon="ClockIcon"
        severity="warning"
      />
      <RealTimeIndicator
        title="Online Users"
        :value="realtimeMetrics.online_users"
        icon="UsersIcon"
        showThreshold="false"
      />
    </div>

    <!-- Loading State -->
    <LoadingSpinner v-if="loading && !dashboardData.active_incidents" class="mt-8" />

    <!-- Dashboard Content -->
    <div v-else class="dashboard-content">
      <!-- Active Incidents Section -->
      <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6 border-b border-gray-200">
          <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">Active Incidents</h2>
            <div class="flex items-center space-x-2">
              <input
                v-model="incidentSearch"
                type="text"
                placeholder="Search incidents..."
                class="form-input"
              />
              <select v-model="priorityFilter" class="form-select">
                <option value="">All Priorities</option>
                <option value="critical">Critical</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
              </select>
            </div>
          </div>
        </div>
        <ActiveIncidentsTable 
          :incidents="filteredIncidents" 
          :loading="loading"
          @assign="handleAssignIncident"
          @view="handleViewIncident"
        />
      </div>

      <!-- Queue and Response Metrics -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Queue Metrics -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Queue Metrics</h3>
          <QueueMetricsDisplay :data="dashboardData.queue_metrics" />
        </div>

        <!-- Response Times -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Response Time Analysis</h3>
          <ResponseTimeChart :data="dashboardData.response_times" />
        </div>
      </div>

      <!-- Workload Distribution -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Workload Distribution</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <WorkloadHeatmap :data="dashboardData.workload_distribution" />
          <WorkloadBalanceChart :data="dashboardData.workload_distribution" />
        </div>
      </div>

      <!-- Service Health -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Service Health Status</h3>
        <ServiceHealthGrid :services="dashboardData.service_health" />
      </div>

      <!-- Real-time Activity -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Activity Feed -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Recent Activity</h3>
          <ActivityFeed :activities="dashboardData.real_time_activity.recent_updates" />
        </div>

        <!-- Activity Heatmap -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Activity Heatmap</h3>
          <HeatmapChart :data="dashboardData.real_time_activity.activity_heatmap" />
        </div>
      </div>

      <!-- Escalations Panel -->
      <div class="bg-white rounded-lg shadow p-6 mt-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Escalations</h3>
          <span class="text-sm text-gray-500">
            Total: {{ dashboardData.escalations?.total_escalations || 0 }}
          </span>
        </div>
        <EscalationList :escalations="dashboardData.escalations" />
      </div>

      <!-- Pending Changes Alert -->
      <div v-if="dashboardData.pending_changes?.length > 0" class="mt-6">
        <PendingChangesAlert :changes="dashboardData.pending_changes" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAnalyticsStore } from '@/stores/analytics'
import { useIncidentStore } from '@/stores/incident'
import { useToast } from '@/composables/useToast'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import RealTimeIndicator from '@/components/dashboard/RealTimeIndicator.vue'
import ActiveIncidentsTable from '@/components/dashboard/ActiveIncidentsTable.vue'
import QueueMetricsDisplay from '@/components/dashboard/QueueMetricsDisplay.vue'
import ResponseTimeChart from '@/components/charts/ResponseTimeChart.vue'
import WorkloadHeatmap from '@/components/charts/WorkloadHeatmap.vue'
import WorkloadBalanceChart from '@/components/charts/WorkloadBalanceChart.vue'
import ServiceHealthGrid from '@/components/dashboard/ServiceHealthGrid.vue'
import ActivityFeed from '@/components/dashboard/ActivityFeed.vue'
import HeatmapChart from '@/components/charts/HeatmapChart.vue'
import EscalationList from '@/components/dashboard/EscalationList.vue'
import PendingChangesAlert from '@/components/dashboard/PendingChangesAlert.vue'
import { RefreshIcon } from '@heroicons/vue/outline'

const router = useRouter()
const analyticsStore = useAnalyticsStore()
const incidentStore = useIncidentStore()
const { showToast } = useToast()

// State
const loading = ref(false)
const refreshInterval = ref(60) // seconds
const refreshTimer = ref(null)
const incidentSearch = ref('')
const priorityFilter = ref('')

const dashboardData = ref({
  active_incidents: [],
  pending_changes: [],
  workload_distribution: {},
  queue_metrics: {},
  response_times: {},
  escalations: {},
  service_health: {},
  real_time_activity: {
    recent_updates: [],
    activity_heatmap: {}
  }
})

const realtimeMetrics = ref({
  active_incidents: 0,
  queue_length: 0,
  sla_at_risk: 0,
  online_users: 0
})

// Computed
const filteredIncidents = computed(() => {
  let incidents = dashboardData.value.active_incidents || []
  
  if (incidentSearch.value) {
    const search = incidentSearch.value.toLowerCase()
    incidents = incidents.filter(incident => 
      incident.title.toLowerCase().includes(search) ||
      incident.number.toLowerCase().includes(search) ||
      incident.description?.toLowerCase().includes(search)
    )
  }
  
  if (priorityFilter.value) {
    incidents = incidents.filter(incident => 
      incident.priority === priorityFilter.value
    )
  }
  
  return incidents
})

// Methods
const loadDashboard = async () => {
  loading.value = true
  try {
    const [operationalData, realtimeData] = await Promise.all([
      analyticsStore.getOperationalDashboard(),
      analyticsStore.getRealTimeMetrics()
    ])
    
    dashboardData.value = operationalData.data
    realtimeMetrics.value = realtimeData.data
  } catch (error) {
    showToast('Failed to load dashboard data', 'error')
    console.error('Dashboard load error:', error)
  } finally {
    loading.value = false
  }
}

const refreshDashboard = async () => {
  await loadDashboard()
  showToast('Dashboard refreshed', 'success')
}

const updateRefreshInterval = () => {
  // Clear existing timer
  if (refreshTimer.value) {
    clearInterval(refreshTimer.value)
    refreshTimer.value = null
  }
  
  // Set new timer if interval > 0
  if (refreshInterval.value > 0) {
    refreshTimer.value = setInterval(() => {
      loadDashboard()
    }, refreshInterval.value * 1000)
  }
}

const handleAssignIncident = async (incident) => {
  // Open assignment modal or handle assignment
  try {
    await incidentStore.assignIncident(incident.id, { /* assignment data */ })
    showToast('Incident assigned successfully', 'success')
    loadDashboard()
  } catch (error) {
    showToast('Failed to assign incident', 'error')
  }
}

const handleViewIncident = (incident) => {
  router.push(`/incidents/${incident.id}`)
}

// WebSocket for real-time updates
const connectWebSocket = () => {
  // This would connect to your WebSocket server for real-time updates
  // For now, we'll simulate with periodic updates
  const ws = new WebSocket(import.meta.env.VITE_WS_URL || 'ws://localhost:3000/ws')
  
  ws.onmessage = (event) => {
    const data = JSON.parse(event.data)
    if (data.type === 'metrics_update') {
      realtimeMetrics.value = data.metrics
    } else if (data.type === 'incident_update') {
      // Update specific incident in the list
      const index = dashboardData.value.active_incidents.findIndex(
        i => i.id === data.incident.id
      )
      if (index !== -1) {
        dashboardData.value.active_incidents[index] = data.incident
      }
    }
  }
  
  ws.onerror = (error) => {
    console.error('WebSocket error:', error)
  }
  
  return ws
}

// Lifecycle
onMounted(() => {
  loadDashboard()
  updateRefreshInterval()
  
  // Connect to WebSocket for real-time updates
  // const ws = connectWebSocket()
  
  // Cleanup on unmount
  onUnmounted(() => {
    if (refreshTimer.value) {
      clearInterval(refreshTimer.value)
    }
    // ws.close()
  })
})

// Watchers
watch(refreshInterval, updateRefreshInterval)
</script>

<style scoped>
.operational-dashboard {
  @apply p-6;
}

.dashboard-header {
  @apply flex justify-between items-center mb-6;
}

.dashboard-content {
  @apply space-y-6;
}

.form-input {
  @apply px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.form-select {
  @apply px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white;
}

.btn-primary {
  @apply px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed;
}
</style>