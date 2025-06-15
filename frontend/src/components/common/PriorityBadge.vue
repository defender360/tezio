<template>
  <span
    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
    :class="priorityClass"
  >
    {{ displayPriority }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { IncidentPriority } from '@/types'

interface Props {
  priority: IncidentPriority
}

const props = defineProps<Props>()

const priorityClass = computed(() => {
  const classes = {
    critical: 'bg-red-100 text-red-800',
    high: 'bg-orange-100 text-orange-800',
    medium: 'bg-yellow-100 text-yellow-800',
    low: 'bg-green-100 text-green-800'
  }
  return classes[props.priority] || 'bg-gray-100 text-gray-800'
})

const displayPriority = computed(() => {
  return props.priority.charAt(0).toUpperCase() + props.priority.slice(1)
})
</script>