<template>
  <div class="gauge-chart-container">
    <canvas ref="chartCanvas"></canvas>
    <div class="gauge-value">
      <span class="value">{{ displayValue }}</span>
      <span class="label">{{ label }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip
} from 'chart.js'

// Register Chart.js components
ChartJS.register(ArcElement, Tooltip)

const props = defineProps({
  value: {
    type: Number,
    required: true
  },
  min: {
    type: Number,
    default: 0
  },
  max: {
    type: Number,
    default: 100
  },
  label: {
    type: String,
    default: ''
  },
  thresholds: {
    type: Array,
    default: () => [
      { value: 60, color: '#ef4444' }, // red
      { value: 80, color: '#f59e0b' }, // amber
      { value: 100, color: '#10b981' } // green
    ]
  },
  suffix: {
    type: String,
    default: '%'
  },
  height: {
    type: [String, Number],
    default: '200'
  }
})

const chartCanvas = ref(null)
let chart = null

const displayValue = computed(() => {
  return `${props.value}${props.suffix}`
})

const getColor = (value) => {
  const normalizedValue = ((value - props.min) / (props.max - props.min)) * 100
  
  for (let i = 0; i < props.thresholds.length; i++) {
    if (normalizedValue <= props.thresholds[i].value) {
      return props.thresholds[i].color
    }
  }
  return props.thresholds[props.thresholds.length - 1].color
}

const createGaugeData = () => {
  const normalizedValue = ((props.value - props.min) / (props.max - props.min)) * 100
  const remainingValue = 100 - normalizedValue
  const color = getColor(props.value)
  
  return {
    datasets: [{
      data: [normalizedValue, remainingValue],
      backgroundColor: [color, '#e5e7eb'],
      borderWidth: 0,
      cutout: '75%',
      rotation: -90,
      circumference: 180
    }]
  }
}

const options = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      enabled: false
    }
  }
}

const createChart = () => {
  if (!chartCanvas.value) return

  const ctx = chartCanvas.value.getContext('2d')
  
  chart = new ChartJS(ctx, {
    type: 'doughnut',
    data: createGaugeData(),
    options: options
  })
}

const updateChart = () => {
  if (!chart) return

  chart.data = createGaugeData()
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

watch(() => props.value, () => {
  updateChart()
})

watch(() => [props.min, props.max, props.thresholds], () => {
  destroyChart()
  createChart()
}, { deep: true })
</script>

<style scoped>
.gauge-chart-container {
  position: relative;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.gauge-chart-container canvas {
  max-width: 100%;
  height: v-bind(height + 'px');
}

.gauge-value {
  position: absolute;
  bottom: 20%;
  left: 50%;
  transform: translateX(-50%);
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.gauge-value .value {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  line-height: 1;
}

.gauge-value .label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-top: 0.25rem;
}
</style>