<template>
  <div class="heatmap-container">
    <div v-if="loading" class="loading-state">
      <LoadingSpinner />
    </div>
    <div v-else class="heatmap-content">
      <div class="heatmap-grid" :style="gridStyle">
        <div
          v-for="(cell, index) in cells"
          :key="index"
          class="heatmap-cell"
          :style="getCellStyle(cell)"
          @click="handleCellClick(cell)"
          @mouseenter="handleCellHover(cell)"
          @mouseleave="handleCellLeave"
        >
          <span class="cell-value">{{ cell.value }}</span>
        </div>
      </div>
      
      <!-- Axes Labels -->
      <div class="x-axis-labels">
        <div 
          v-for="label in xLabels" 
          :key="label" 
          class="x-label"
        >
          {{ label }}
        </div>
      </div>
      
      <div class="y-axis-labels">
        <div 
          v-for="label in yLabels" 
          :key="label" 
          class="y-label"
        >
          {{ label }}
        </div>
      </div>
      
      <!-- Legend -->
      <div class="heatmap-legend">
        <span class="legend-label">{{ minValue }}</span>
        <div class="legend-gradient" :style="legendGradientStyle"></div>
        <span class="legend-label">{{ maxValue }}</span>
      </div>
      
      <!-- Tooltip -->
      <div 
        v-if="tooltip.show" 
        class="heatmap-tooltip"
        :style="tooltipStyle"
      >
        <div class="tooltip-content">
          <strong>{{ tooltip.title }}</strong>
          <div>{{ tooltip.value }}</div>
          <div v-if="tooltip.detail" class="tooltip-detail">
            {{ tooltip.detail }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const props = defineProps({
  data: {
    type: Object,
    required: true
  },
  colorScale: {
    type: Array,
    default: () => [
      { threshold: 0, color: '#f3f4f6' },
      { threshold: 25, color: '#dbeafe' },
      { threshold: 50, color: '#93c5fd' },
      { threshold: 75, color: '#3b82f6' },
      { threshold: 100, color: '#1d4ed8' }
    ]
  },
  loading: {
    type: Boolean,
    default: false
  },
  cellSize: {
    type: Number,
    default: 60
  },
  showValues: {
    type: Boolean,
    default: true
  },
  interactive: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['cellClick', 'cellHover'])

// State
const tooltip = ref({
  show: false,
  x: 0,
  y: 0,
  title: '',
  value: '',
  detail: ''
})

// Computed
const cells = computed(() => {
  if (!props.data.matrix) return []
  
  const flatCells = []
  props.data.matrix.forEach((row, y) => {
    row.forEach((value, x) => {
      flatCells.push({
        x,
        y,
        value,
        label: `${props.data.yLabels[y]} - ${props.data.xLabels[x]}`
      })
    })
  })
  
  return flatCells
})

const xLabels = computed(() => props.data.xLabels || [])
const yLabels = computed(() => props.data.yLabels || [])

const minValue = computed(() => {
  if (!cells.value.length) return 0
  return Math.min(...cells.value.map(c => c.value))
})

const maxValue = computed(() => {
  if (!cells.value.length) return 100
  return Math.max(...cells.value.map(c => c.value))
})

const gridStyle = computed(() => ({
  gridTemplateColumns: `repeat(${xLabels.value.length}, ${props.cellSize}px)`,
  gridTemplateRows: `repeat(${yLabels.value.length}, ${props.cellSize}px)`
}))

const legendGradientStyle = computed(() => {
  const stops = props.colorScale.map(scale => 
    `${scale.color} ${scale.threshold}%`
  ).join(', ')
  
  return {
    background: `linear-gradient(to right, ${stops})`
  }
})

const tooltipStyle = computed(() => ({
  left: `${tooltip.value.x}px`,
  top: `${tooltip.value.y}px`
}))

// Methods
const getColor = (value) => {
  const normalizedValue = ((value - minValue.value) / (maxValue.value - minValue.value)) * 100
  
  for (let i = props.colorScale.length - 1; i >= 0; i--) {
    if (normalizedValue >= props.colorScale[i].threshold) {
      return props.colorScale[i].color
    }
  }
  
  return props.colorScale[0].color
}

const getCellStyle = (cell) => {
  return {
    backgroundColor: getColor(cell.value),
    cursor: props.interactive ? 'pointer' : 'default'
  }
}

const handleCellClick = (cell) => {
  if (!props.interactive) return
  emit('cellClick', cell)
}

const handleCellHover = (cell) => {
  if (!props.interactive) return
  
  const event = window.event
  const rect = event.target.getBoundingClientRect()
  
  tooltip.value = {
    show: true,
    x: rect.left + rect.width / 2,
    y: rect.top - 10,
    title: cell.label,
    value: `Value: ${cell.value}`,
    detail: props.data.details?.[cell.y]?.[cell.x] || ''
  }
  
  emit('cellHover', cell)
}

const handleCellLeave = () => {
  tooltip.value.show = false
}

// Watch for data changes
watch(() => props.data, () => {
  tooltip.value.show = false
}, { deep: true })
</script>

<style scoped>
.heatmap-container {
  position: relative;
  width: 100%;
}

.loading-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 200px;
}

.heatmap-content {
  position: relative;
  padding: 40px 40px 60px 60px;
}

.heatmap-grid {
  display: grid;
  gap: 2px;
  position: relative;
  z-index: 1;
}

.heatmap-cell {
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  border-radius: 4px;
  position: relative;
}

.heatmap-cell:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  z-index: 10;
}

.cell-value {
  font-size: 0.875rem;
  font-weight: 500;
  color: #1f2937;
}

.x-axis-labels {
  display: flex;
  position: absolute;
  bottom: 30px;
  left: 60px;
  gap: 2px;
}

.x-label {
  width: v-bind(cellSize + 'px');
  text-align: center;
  font-size: 0.75rem;
  color: #6b7280;
  transform: rotate(-45deg);
  transform-origin: center;
}

.y-axis-labels {
  position: absolute;
  left: 0;
  top: 40px;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.y-label {
  height: v-bind(cellSize + 'px');
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding-right: 10px;
  font-size: 0.75rem;
  color: #6b7280;
}

.heatmap-legend {
  position: absolute;
  bottom: 0;
  left: 60px;
  right: 40px;
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 20px;
}

.legend-gradient {
  flex: 1;
  height: 10px;
  border-radius: 5px;
}

.legend-label {
  font-size: 0.75rem;
  color: #6b7280;
}

.heatmap-tooltip {
  position: fixed;
  z-index: 1000;
  pointer-events: none;
  transform: translate(-50%, -100%);
  margin-top: -10px;
}

.tooltip-content {
  background-color: rgba(0, 0, 0, 0.9);
  color: white;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 0.875rem;
  white-space: nowrap;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.tooltip-detail {
  font-size: 0.75rem;
  color: #e5e7eb;
  margin-top: 4px;
}
</style>