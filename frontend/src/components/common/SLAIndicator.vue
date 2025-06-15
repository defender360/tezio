<template>
  <span
    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
    :class="indicatorClass"
  >
    <ExclamationTriangleIcon v-if="type === 'overdue'" class="h-3 w-3 mr-1" />
    <ClockIcon v-else class="h-3 w-3 mr-1" />
    {{ displayText }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { ExclamationTriangleIcon, ClockIcon } from '@heroicons/vue/24/solid'

interface Props {
  type: 'overdue' | 'at-risk' | 'on-track'
  hours?: number
}

const props = defineProps<Props>()

const indicatorClass = computed(() => {
  const classes = {
    overdue: 'bg-red-100 text-red-800',
    'at-risk': 'bg-yellow-100 text-yellow-800',
    'on-track': 'bg-green-100 text-green-800'
  }
  return classes[props.type]
})

const displayText = computed(() => {
  switch (props.type) {
    case 'overdue':
      return 'SLA Breached'
    case 'at-risk':
      return props.hours ? `${props.hours}h remaining` : 'At Risk'
    case 'on-track':
      return 'On Track'
    default:
      return ''
  }
})
</script>