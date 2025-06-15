<template>
  <div class="team-dashboard">
    <!-- Header -->
    <div class="dashboard-header">
      <h1 class="text-2xl font-bold text-gray-900">Team Dashboard</h1>
      <div class="flex items-center space-x-4">
        <select v-model="selectedTeam" @change="loadTeamData" class="form-select">
          <option value="">Select Team</option>
          <option v-for="team in teams" :key="team.id" :value="team.id">
            {{ team.name }}
          </option>
        </select>
        <DateRangePicker v-model="dateRange" @change="loadTeamData" />
        <button 
          @click="exportTeamReport" 
          class="btn-secondary flex items-center space-x-2"
        >
          <DownloadIcon class="w-4 h-4" />
          <span>Export Report</span>
        </button>
      </div>
    </div>

    <!-- Team Not Selected -->
    <div v-if="!selectedTeam" class="mt-8 text-center">
      <UsersIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
      <p class="text-gray-500">Please select a team to view dashboard</p>
    </div>

    <!-- Loading State -->
    <LoadingSpinner v-else-if="loading" class="mt-8" />

    <!-- Dashboard Content -->
    <div v-else class="dashboard-content mt-6">
      <!-- Team Overview Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <MetricCard
          title="Team Members"
          :value="teamMetrics.overview?.team_members || 0"
          icon="UsersIcon"
          color="blue"
        />
        <MetricCard
          title="Active Tickets"
          :value="teamMetrics.overview?.total_tickets || 0"
          icon="TicketIcon"
          color="purple"
        />
        <MetricCard
          title="Resolved This Period"
          :value="teamMetrics.overview?.resolved_tickets || 0"
          icon="CheckCircleIcon"
          color="green"
        />
        <MetricCard
          title="SLA Compliance"
          :value="teamMetrics.overview?.sla_compliance || 0"
          suffix="%"
          icon="ShieldCheckIcon"
          :color="getSLAColor(teamMetrics.overview?.sla_compliance)"
        />
      </div>

      <!-- Individual Performance -->
      <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6 border-b border-gray-200">
          <h2 class="text-lg font-semibold">Individual Performance</h2>
        </div>
        <IndividualPerformanceTable 
          :members="individualPerformance"
          @viewMember="handleViewMember"
        />
      </div>

      <!-- Performance Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Productivity Trend -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Productivity Trend</h3>
          <LineChart
            :data="productivityChartData"
            :options="lineChartOptions"
            height="300"
          />
        </div>

        <!-- Workload Balance -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Workload Balance</h3>
          <BarChart
            :data="workloadBalanceData"
            :options="barChartOptions"
            height="300"
          />
          <div class="mt-4 text-center">
            <p class="text-sm text-gray-600">
              Balance Index: 
              <span class="font-semibold" :class="getBalanceColor(workloadBalance.balance_index)">
                {{ workloadBalance.balance_index }}%
              </span>
            </p>
          </div>
        </div>
      </div>

      <!-- Efficiency Metrics -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Team Efficiency Metrics</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <EfficiencyMetric
            label="First Response Time"
            :value="teamMetrics.efficiency?.first_response_time"
            unit="minutes"
            :target="30"
            icon="ClockIcon"
          />
          <EfficiencyMetric
            label="Resolution Time"
            :value="teamMetrics.efficiency?.resolution_time"
            unit="hours"
            :target="8"
            icon="CheckIcon"
          />
          <EfficiencyMetric
            label="Rework Rate"
            :value="teamMetrics.efficiency?.rework_rate"
            unit="%"
            :target="5"
            :inverse="true"
            icon="RefreshIcon"
          />
          <EfficiencyMetric
            label="Automation Rate"
            :value="teamMetrics.efficiency?.automation_rate"
            unit="%"
            :target="40"
            icon="LightningBoltIcon"
          />
        </div>
      </div>

      <!-- Skill Matrix -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Skill Matrix</h3>
          <button @click="showSkillDetails = true" class="text-blue-600 hover:text-blue-800">
            View Details
          </button>
        </div>
        <SkillMatrixHeatmap :data="skillMatrix" />
      </div>

      <!-- Training Recommendations -->
      <div v-if="trainingNeeds.length > 0" class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Training Recommendations</h3>
        <TrainingRecommendations :recommendations="trainingNeeds" />
      </div>

      <!-- Workload Recommendations -->
      <div v-if="workloadBalance.recommendations?.length > 0" class="mt-6">
        <RecommendationsPanel
          title="Workload Balancing Recommendations"
          :recommendations="workloadBalance.recommendations"
          type="workload"
        />
      </div>
    </div>

    <!-- Skill Details Modal -->
    <SkillDetailsModal
      v-if="showSkillDetails"
      :teamId="selectedTeam"
      :skillMatrix="skillMatrix"
      @close="showSkillDetails = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAnalyticsStore } from '@/stores/analytics'
import { useTeamStore } from '@/stores/team'
import { useToast } from '@/composables/useToast'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import DateRangePicker from '@/components/common/DateRangePicker.vue'
import MetricCard from '@/components/dashboard/MetricCard.vue'
import IndividualPerformanceTable from '@/components/dashboard/IndividualPerformanceTable.vue'
import LineChart from '@/components/charts/LineChart.vue'
import BarChart from '@/components/charts/BarChart.vue'
import EfficiencyMetric from '@/components/dashboard/EfficiencyMetric.vue'
import SkillMatrixHeatmap from '@/components/dashboard/SkillMatrixHeatmap.vue'
import TrainingRecommendations from '@/components/dashboard/TrainingRecommendations.vue'
import RecommendationsPanel from '@/components/dashboard/RecommendationsPanel.vue'
import SkillDetailsModal from '@/components/modals/SkillDetailsModal.vue'
import { DownloadIcon, UsersIcon } from '@heroicons/vue/outline'

const analyticsStore = useAnalyticsStore()
const teamStore = useTeamStore()
const { showToast } = useToast()

// State
const loading = ref(false)
const selectedTeam = ref('')
const teams = ref([])
const showSkillDetails = ref(false)
const dateRange = ref({
  startDate: new Date(new Date().setMonth(new Date().getMonth() - 1)),
  endDate: new Date()
})

const teamData = ref({
  team_metrics: {},
  individual_performance: [],
  workload_balance: {},
  skill_matrix: {},
  training_needs: [],
  team_sla_performance: []
})

// Computed
const teamMetrics = computed(() => teamData.value.team_metrics || {})
const individualPerformance = computed(() => teamData.value.individual_performance || [])
const workloadBalance = computed(() => teamData.value.workload_balance || {})
const skillMatrix = computed(() => teamData.value.skill_matrix || {})
const trainingNeeds = computed(() => teamData.value.training_needs || [])

// Chart Data
const productivityChartData = computed(() => {
  const productivity = teamMetrics.value.productivity || []
  return {
    labels: productivity.map(p => p.period),
    datasets: [
      {
        label: 'Tickets Resolved',
        data: productivity.map(p => p.tickets_resolved),
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        tension: 0.4
      },
      {
        label: 'Productivity Score',
        data: productivity.map(p => p.productivity_score),
        borderColor: 'rgb(16, 185, 129)',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        tension: 0.4,
        yAxisID: 'y1'
      }
    ]
  }
})

const workloadBalanceData = computed(() => {
  const members = workloadBalance.value.members || []
  return {
    labels: members.map(m => m.user.name),
    datasets: [
      {
        label: 'Current Load',
        data: members.map(m => m.current_load.total || 0),
        backgroundColor: 'rgba(59, 130, 246, 0.8)'
      },
      {
        label: 'Capacity',
        data: members.map(m => m.capacity || 0),
        backgroundColor: 'rgba(229, 231, 235, 0.8)'
      }
    ]
  }
})

// Chart Options
const lineChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index',
    intersect: false
  },
  plugins: {
    legend: {
      position: 'top'
    }
  },
  scales: {
    y: {
      type: 'linear',
      display: true,
      position: 'left',
      beginAtZero: true
    },
    y1: {
      type: 'linear',
      display: true,
      position: 'right',
      beginAtZero: true,
      grid: {
        drawOnChartArea: false
      }
    }
  }
}

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top'
    },
    tooltip: {
      callbacks: {
        afterLabel: (context) => {
          if (context.datasetIndex === 0) {
            const member = workloadBalance.value.members[context.dataIndex]
            const utilization = member?.utilization_rate || 0
            return `Utilization: ${utilization}%`
          }
          return ''
        }
      }
    }
  },
  scales: {
    x: {
      stacked: false
    },
    y: {
      stacked: false,
      beginAtZero: true
    }
  }
}

// Methods
const loadTeams = async () => {
  try {
    teams.value = await teamStore.getTeams()
  } catch (error) {
    showToast('Failed to load teams', 'error')
    console.error('Load teams error:', error)
  }
}

const loadTeamData = async () => {
  if (!selectedTeam.value) return
  
  loading.value = true
  try {
    const response = await analyticsStore.getTeamDashboard({
      team_id: selectedTeam.value,
      start_date: dateRange.value.startDate.toISOString().split('T')[0],
      end_date: dateRange.value.endDate.toISOString().split('T')[0]
    })
    teamData.value = response.data
  } catch (error) {
    showToast('Failed to load team data', 'error')
    console.error('Team data load error:', error)
  } finally {
    loading.value = false
  }
}

const exportTeamReport = async () => {
  if (!selectedTeam.value) {
    showToast('Please select a team first', 'warning')
    return
  }
  
  try {
    await analyticsStore.exportAnalytics({
      type: 'team',
      format: 'pdf',
      team_id: selectedTeam.value,
      start_date: dateRange.value.startDate.toISOString().split('T')[0],
      end_date: dateRange.value.endDate.toISOString().split('T')[0]
    })
    showToast('Team report exported successfully', 'success')
  } catch (error) {
    showToast('Export failed', 'error')
    console.error('Export error:', error)
  }
}

const handleViewMember = (member) => {
  // Navigate to member details or open modal
  console.log('View member:', member)
}

const getSLAColor = (value) => {
  if (value >= 95) return 'green'
  if (value >= 85) return 'yellow'
  return 'red'
}

const getBalanceColor = (value) => {
  if (value >= 80) return 'text-green-600'
  if (value >= 60) return 'text-yellow-600'
  return 'text-red-600'
}

// Lifecycle
onMounted(() => {
  loadTeams()
})

// Watchers
watch(selectedTeam, () => {
  if (selectedTeam.value) {
    loadTeamData()
  }
})

watch(dateRange, () => {
  if (selectedTeam.value) {
    loadTeamData()
  }
}, { deep: true })
</script>

<style scoped>
.team-dashboard {
  @apply p-6;
}

.dashboard-header {
  @apply flex justify-between items-center mb-6;
}

.dashboard-content {
  @apply space-y-6;
}

.form-select {
  @apply px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white;
}

.btn-secondary {
  @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500;
}
</style>