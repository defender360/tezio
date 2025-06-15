<template>
  <div class="response-time-chart">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Response Time Trends</h3>
    <div v-if="!data || data.length === 0" class="text-gray-500 text-center py-8">
      No data available
    </div>
    <LineChart v-else :data="chartData" :options="chartOptions" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import LineChart from './LineChart.vue'

interface Props {
  data?: Array<{
    date: string
    avgResponseTime: number
    p95ResponseTime: number
  }>
}

const props = withDefaults(defineProps<Props>(), {
  data: () => []
})

const chartData = computed(() => ({
  labels: props.data.map(d => d.date),
  datasets: [
    {
      label: 'Average Response Time',
      data: props.data.map(d => d.avgResponseTime),
      borderColor: 'rgb(59, 130, 246)',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      tension: 0.4
    },
    {
      label: '95th Percentile',
      data: props.data.map(d => d.p95ResponseTime),
      borderColor: 'rgb(239, 68, 68)',
      backgroundColor: 'rgba(239, 68, 68, 0.1)',
      tension: 0.4
    }
  ]
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top' as const
    },
    tooltip: {
      mode: 'index' as const,
      intersect: false
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      title: {
        display: true,
        text: 'Response Time (ms)'
      }
    }
  }
}
</script>