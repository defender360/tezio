<template>
  <div class="executive-dashboard">
    <!-- Header with Date Range Selector -->
    <div class="dashboard-header">
      <h1 class="text-2xl font-bold text-gray-900">Executive Dashboard</h1>
      <div class="flex items-center space-x-4">
        <DateRangePicker v-model="dateRange" @change="refreshDashboard" />
        <button 
          @click="exportDashboard" 
          class="btn-secondary flex items-center space-x-2"
        >
          <DownloadIcon class="w-4 h-4" />
          <span>Export</span>
        </button>
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

    <!-- Loading State -->
    <LoadingSpinner v-if="loading" class="mt-8" />

    <!-- Dashboard Content -->
    <div v-else class="dashboard-content mt-6">
      <!-- KPI Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
        <KPICard
          v-for="kpi in kpis"
          :key="kpi.id"
          :title="kpi.title"
          :value="kpi.value"
          :previousValue="kpi.previousValue"
          :format="kpi.format"
          :icon="kpi.icon"
          :trend="kpi.trend"
          :loading="loading"
        />
      </div>

      <!-- Main Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Ticket Volume Trends -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Ticket Volume Trends</h3>
          <LineChart
            :data="trends.ticket_volume"
            :options="volumeChartOptions"
            height="300"
          />
        </div>

        <!-- SLA Compliance Trend -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">SLA Compliance Trend</h3>
          <LineChart
            :data="trends.sla_compliance"
            :options="slaChartOptions"
            height="300"
          />
        </div>
      </div>

      <!-- Distribution Charts -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Category Distribution -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Tickets by Category</h3>
          <PieChart
            :data="distributions.by_category"
            :options="pieChartOptions"
            height="250"
          />
        </div>

        <!-- Priority Distribution -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Tickets by Priority</h3>
          <PieChart
            :data="distributions.by_priority"
            :options="priorityChartOptions"
            height="250"
          />
        </div>

        <!-- Service Distribution -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Tickets by Service</h3>
          <BarChart
            :data="distributions.by_service"
            :options="barChartOptions"
            height="250"
          />
        </div>
      </div>

      <!-- Performance Metrics -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Team Performance -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Team Performance</h3>
          <TeamPerformanceTable :data="teamPerformance" />
        </div>

        <!-- Top Issues -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Top Issues</h3>
          <TopIssuesTable :data="topIssues" />
        </div>
      </div>

      <!-- Financial Analysis -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Cost Analysis -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Cost Analysis</h3>
          <CostAnalysisChart :data="costAnalysis" />
        </div>

        <!-- ROI Metrics -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">ROI Metrics</h3>
          <ROIMetricsDisplay :data="costAnalysis.roi_metrics" />
        </div>
      </div>

      <!-- Risk Assessment -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Risk Assessment</h3>
        <RiskAssessmentMatrix :data="riskAssessment" />
      </div>
    </div>

    <!-- Export Modal -->
    <ExportModal
      v-if="showExportModal"
      @close="showExportModal = false"
      @export="handleExport"
      :exportTypes="['excel', 'pdf']"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAnalyticsStore } from '@/stores/analytics'
import { useToast } from '@/composables/useToast'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import DateRangePicker from '@/components/common/DateRangePicker.vue'
import KPICard from '@/components/dashboard/KPICard.vue'
import LineChart from '@/components/charts/LineChart.vue'
import PieChart from '@/components/charts/PieChart.vue'
import BarChart from '@/components/charts/BarChart.vue'
import TeamPerformanceTable from '@/components/dashboard/TeamPerformanceTable.vue'
import TopIssuesTable from '@/components/dashboard/TopIssuesTable.vue'
import CostAnalysisChart from '@/components/dashboard/CostAnalysisChart.vue'
import ROIMetricsDisplay from '@/components/dashboard/ROIMetricsDisplay.vue'
import RiskAssessmentMatrix from '@/components/dashboard/RiskAssessmentMatrix.vue'
import ExportModal from '@/components/common/ExportModal.vue'
import { DownloadIcon, RefreshIcon } from '@heroicons/vue/outline'

const analyticsStore = useAnalyticsStore()
const { showToast } = useToast()

const loading = ref(false)
const showExportModal = ref(false)
const dateRange = ref({
  startDate: new Date(new Date().setMonth(new Date().getMonth() - 1)),
  endDate: new Date()
})

const dashboardData = ref({
  kpis: {},
  trends: {},
  distributions: {},
  top_issues: [],
  team_performance: [],
  sla_overview: {},
  change_success_rate: {},
  cost_analysis: {},
  risk_assessment: {}
})

// Computed properties for KPI cards
const kpis = computed(() => {
  const kpiData = dashboardData.value.kpis
  return [
    {
      id: 'total_tickets',
      title: 'Total Tickets',
      value: kpiData.total_tickets?.current || 0,
      previousValue: kpiData.total_tickets?.previous || 0,
      format: 'number',
      icon: 'TicketIcon',
      trend: kpiData.total_tickets?.change_percent || 0
    },
    {
      id: 'resolution_rate',
      title: 'Resolution Rate',
      value: kpiData.resolution_rate?.current || 0,
      previousValue: kpiData.resolution_rate?.previous || 0,
      format: 'percentage',
      icon: 'CheckCircleIcon',
      trend: kpiData.resolution_rate?.change_percent || 0
    },
    {
      id: 'avg_resolution_time',
      title: 'Avg Resolution Time',
      value: kpiData.avg_resolution_time?.current || 0,
      previousValue: kpiData.avg_resolution_time?.previous || 0,
      format: 'hours',
      icon: 'ClockIcon',
      trend: kpiData.avg_resolution_time?.change_percent || 0
    },
    {
      id: 'customer_satisfaction',
      title: 'Customer Satisfaction',
      value: kpiData.customer_satisfaction?.current || 0,
      previousValue: kpiData.customer_satisfaction?.previous || 0,
      format: 'percentage',
      icon: 'EmojiHappyIcon',
      trend: kpiData.customer_satisfaction?.change_percent || 0
    },
    {
      id: 'sla_compliance',
      title: 'SLA Compliance',
      value: kpiData.sla_compliance?.current || 0,
      previousValue: kpiData.sla_compliance?.previous || 0,
      format: 'percentage',
      icon: 'ShieldCheckIcon',
      trend: kpiData.sla_compliance?.change_percent || 0
    },
    {
      id: 'cost_per_ticket',
      title: 'Cost per Ticket',
      value: kpiData.cost_per_ticket?.current || 0,
      previousValue: kpiData.cost_per_ticket?.previous || 0,
      format: 'currency',
      icon: 'CurrencyDollarIcon',
      trend: kpiData.cost_per_ticket?.change_percent || 0
    }
  ]
})

// Chart options
const volumeChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      position: 'top'
    },
    tooltip: {
      mode: 'index',
      intersect: false
    }
  },
  scales: {
    x: {
      grid: {
        display: false
      }
    },
    y: {
      beginAtZero: true
    }
  }
}

const slaChartOptions = {
  ...volumeChartOptions,
  scales: {
    ...volumeChartOptions.scales,
    y: {
      beginAtZero: true,
      max: 100,
      ticks: {
        callback: (value) => value + '%'
      }
    }
  }
}

const pieChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'right',
      labels: {
        padding: 10,
        boxWidth: 12
      }
    }
  }
}

const priorityChartOptions = {
  ...pieChartOptions,
  plugins: {
    ...pieChartOptions.plugins,
    legend: {
      ...pieChartOptions.plugins.legend,
      labels: {
        ...pieChartOptions.plugins.legend.labels,
        generateLabels: (chart) => {
          const data = chart.data
          if (data.labels.length && data.datasets.length) {
            return data.labels.map((label, i) => {
              const meta = chart.getDatasetMeta(0)
              const style = meta.controller.getStyle(i)
              return {
                text: label,
                fillStyle: style.backgroundColor,
                strokeStyle: style.borderColor,
                lineWidth: style.borderWidth,
                hidden: isNaN(data.datasets[0].data[i]) || meta.data[i].hidden,
                index: i,
                // Add priority colors
                ...(label === 'Critical' && { fillStyle: '#dc2626' }),
                ...(label === 'High' && { fillStyle: '#f59e0b' }),
                ...(label === 'Medium' && { fillStyle: '#3b82f6' }),
                ...(label === 'Low' && { fillStyle: '#10b981' })
              }
            })
          }
          return []
        }
      }
    }
  }
}

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    x: {
      grid: {
        display: false
      }
    },
    y: {
      beginAtZero: true
    }
  }
}

// Getters
const trends = computed(() => dashboardData.value.trends || {})
const distributions = computed(() => dashboardData.value.distributions || {})
const teamPerformance = computed(() => dashboardData.value.team_performance || [])
const topIssues = computed(() => dashboardData.value.top_issues || [])
const costAnalysis = computed(() => dashboardData.value.cost_analysis || {})
const riskAssessment = computed(() => dashboardData.value.risk_assessment || {})

// Methods
const loadDashboard = async () => {
  loading.value = true
  try {
    const response = await analyticsStore.getExecutiveDashboard({
      start_date: dateRange.value.startDate.toISOString().split('T')[0],
      end_date: dateRange.value.endDate.toISOString().split('T')[0]
    })
    dashboardData.value = response.data
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

const exportDashboard = () => {
  showExportModal.value = true
}

const handleExport = async (format) => {
  try {
    await analyticsStore.exportAnalytics({
      type: 'executive',
      format,
      start_date: dateRange.value.startDate.toISOString().split('T')[0],
      end_date: dateRange.value.endDate.toISOString().split('T')[0]
    })
    showToast(`Dashboard exported as ${format.toUpperCase()}`, 'success')
    showExportModal.value = false
  } catch (error) {
    showToast('Export failed', 'error')
    console.error('Export error:', error)
  }
}

// Lifecycle
onMounted(() => {
  loadDashboard()
})

// Watch for date range changes
watch(dateRange, () => {
  loadDashboard()
}, { deep: true })
</script>

<style scoped>
.executive-dashboard {
  @apply p-6;
}

.dashboard-header {
  @apply flex justify-between items-center mb-6;
}

.dashboard-content {
  @apply space-y-6;
}

.btn-primary {
  @apply px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500;
}
</style>