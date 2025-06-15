<template>
  <div class="line-chart-container">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

const props = defineProps({
  data: {
    type: Object,
    required: true
  },
  options: {
    type: Object,
    default: () => ({})
  },
  height: {
    type: [String, Number],
    default: '400'
  }
})

const chartCanvas = ref(null)
let chart = null

const defaultOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
      labels: {
        usePointStyle: true,
        padding: 15
      }
    },
    tooltip: {
      mode: 'index',
      intersect: false,
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      titleColor: '#fff',
      bodyColor: '#fff',
      borderColor: '#ddd',
      borderWidth: 1,
      padding: 10,
      bodySpacing: 5,
      titleMarginBottom: 10
    }
  },
  scales: {
    x: {
      grid: {
        display: false
      },
      ticks: {
        maxRotation: 45,
        minRotation: 0
      }
    },
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(0, 0, 0, 0.05)'
      }
    }
  },
  interaction: {
    mode: 'nearest',
    axis: 'x',
    intersect: false
  }
}

const createChart = () => {
  if (!chartCanvas.value) return

  const ctx = chartCanvas.value.getContext('2d')
  
  // Merge options with defaults
  const mergedOptions = {
    ...defaultOptions,
    ...props.options,
    plugins: {
      ...defaultOptions.plugins,
      ...props.options.plugins
    },
    scales: {
      ...defaultOptions.scales,
      ...props.options.scales
    }
  }

  chart = new ChartJS(ctx, {
    type: 'line',
    data: props.data,
    options: mergedOptions
  })
}

const updateChart = () => {
  if (!chart) return

  chart.data = props.data
  chart.update('active')
}

const destroyChart = () => {
  if (chart) {
    chart.destroy()
    chart = null
  }
}

onMounted(() => {
  createChart()
})

onUnmounted(() => {
  destroyChart()
})

watch(() => props.data, () => {
  updateChart()
}, { deep: true })

watch(() => props.options, () => {
  destroyChart()
  createChart()
}, { deep: true })
</script>

<style scoped>
.line-chart-container {
  position: relative;
  width: 100%;
}

.line-chart-container canvas {
  max-width: 100%;
  height: v-bind(height + 'px');
}
</style>