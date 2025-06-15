<template>
  <div class="pie-chart-container">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend
} from 'chart.js'

// Register Chart.js components
ChartJS.register(ArcElement, Tooltip, Legend)

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
  },
  type: {
    type: String,
    default: 'pie',
    validator: (value) => ['pie', 'doughnut'].includes(value)
  }
})

const chartCanvas = ref(null)
let chart = null

const defaultOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'right',
      labels: {
        usePointStyle: true,
        padding: 15,
        font: {
          size: 12
        },
        generateLabels: function(chart) {
          const data = chart.data
          if (data.labels.length && data.datasets.length) {
            const dataset = data.datasets[0]
            const total = dataset.data.reduce((a, b) => a + b, 0)
            
            return data.labels.map((label, i) => {
              const value = dataset.data[i]
              const percentage = ((value / total) * 100).toFixed(1)
              
              return {
                text: `${label} (${percentage}%)`,
                fillStyle: dataset.backgroundColor[i],
                strokeStyle: dataset.borderColor ? dataset.borderColor[i] : dataset.backgroundColor[i],
                lineWidth: dataset.borderWidth || 1,
                hidden: isNaN(value) || chart.getDatasetMeta(0).data[i].hidden,
                index: i
              }
            })
          }
          return []
        }
      }
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      titleColor: '#fff',
      bodyColor: '#fff',
      borderColor: '#ddd',
      borderWidth: 1,
      padding: 10,
      bodySpacing: 5,
      titleMarginBottom: 10,
      callbacks: {
        label: function(context) {
          const label = context.label || ''
          const value = context.parsed
          const dataset = context.dataset
          const total = dataset.data.reduce((a, b) => a + b, 0)
          const percentage = ((value / total) * 100).toFixed(1)
          
          return `${label}: ${new Intl.NumberFormat().format(value)} (${percentage}%)`
        }
      }
    }
  },
  animation: {
    animateScale: true,
    animateRotate: true
  }
}

// Default color palette
const defaultColors = [
  '#3b82f6', // blue
  '#10b981', // emerald
  '#f59e0b', // amber
  '#ef4444', // red
  '#8b5cf6', // violet
  '#ec4899', // pink
  '#14b8a6', // teal
  '#f97316', // orange
  '#6366f1', // indigo
  '#84cc16'  // lime
]

const prepareData = (data) => {
  // Ensure colors are set
  if (data.datasets && data.datasets[0]) {
    const dataset = data.datasets[0]
    if (!dataset.backgroundColor || dataset.backgroundColor.length === 0) {
      dataset.backgroundColor = defaultColors.slice(0, data.labels.length)
    }
    if (!dataset.borderColor) {
      dataset.borderColor = '#fff'
    }
    if (!dataset.borderWidth) {
      dataset.borderWidth = 2
    }
  }
  return data
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
    }
  }

  chart = new ChartJS(ctx, {
    type: props.type,
    data: prepareData(props.data),
    options: mergedOptions
  })
}

const updateChart = () => {
  if (!chart) return

  chart.data = prepareData(props.data)
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

watch(() => [props.options, props.type], () => {
  destroyChart()
  createChart()
}, { deep: true })
</script>

<style scoped>
.pie-chart-container {
  position: relative;
  width: 100%;
}

.pie-chart-container canvas {
  max-width: 100%;
  height: v-bind(height + 'px');
}
</style>