<template>
  <div class="card p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-600">{{ title }}</p>
        <p class="mt-2 text-3xl font-brain font-bold" :class="valueColor">
          {{ value }}
        </p>
        <div v-if="trend" class="mt-2 flex items-center text-sm">
          <ArrowUpIcon
            v-if="trend.direction === 'up'"
            class="h-4 w-4"
            :class="trend.direction === 'up' ? 'text-green-500' : 'text-red-500'"
          />
          <ArrowDownIcon
            v-else
            class="h-4 w-4"
            :class="trend.direction === 'down' ? 'text-green-500' : 'text-red-500'"
          />
          <span :class="trendColor" class="ml-1">
            {{ trend.value }}%
          </span>
        </div>
      </div>
      <div class="rounded-full p-3" :class="iconBackground">
        <component :is="iconComponent" class="h-6 w-6" :class="iconColor" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  TicketIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  ChartBarIcon,
  ArrowUpIcon,
  ArrowDownIcon
} from '@heroicons/vue/24/outline'

interface Props {
  title: string
  value: string | number
  trend?: {
    value: number
    direction: 'up' | 'down'
  }
  icon: 'ticket' | 'clock' | 'exclamation' | 'chart'
  color: 'tech-horizon' | 'vital-energy' | 'priority-high' | 'arctic-breeze'
}

const props = defineProps<Props>()

const iconComponent = computed(() => {
  const icons = {
    ticket: TicketIcon,
    clock: ClockIcon,
    exclamation: ExclamationTriangleIcon,
    chart: ChartBarIcon
  }
  return icons[props.icon]
})

const valueColor = computed(() => {
  const colors = {
    'tech-horizon': 'text-tech-horizon-500',
    'vital-energy': 'text-vital-energy-500',
    'priority-high': 'text-priority-high',
    'arctic-breeze': 'text-arctic-breeze-500'
  }
  return colors[props.color]
})

const iconBackground = computed(() => {
  const backgrounds = {
    'tech-horizon': 'bg-tech-horizon-50',
    'vital-energy': 'bg-green-50',
    'priority-high': 'bg-orange-50',
    'arctic-breeze': 'bg-blue-50'
  }
  return backgrounds[props.color]
})

const iconColor = computed(() => {
  const colors = {
    'tech-horizon': 'text-tech-horizon-500',
    'vital-energy': 'text-vital-energy-500',
    'priority-high': 'text-priority-high',
    'arctic-breeze': 'text-arctic-breeze-500'
  }
  return colors[props.color]
})

const trendColor = computed(() => {
  if (!props.trend) return ''
  
  // For some metrics, down is good (like open incidents)
  const downIsGood = ['open_incidents', 'overdue'].includes(props.title.toLowerCase())
  
  if (downIsGood) {
    return props.trend.direction === 'down' ? 'text-green-600' : 'text-red-600'
  }
  
  return props.trend.direction === 'up' ? 'text-green-600' : 'text-red-600'
})
</script>