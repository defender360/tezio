<template>
  <span
    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
    :class="statusClass"
  >
    {{ displayStatus }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { IncidentStatus } from '@/types'

interface Props {
  status: IncidentStatus
}

const props = defineProps<Props>()

const statusClass = computed(() => {
  const classes = {
    new: 'bg-blue-100 text-blue-800',
    assigned: 'bg-yellow-100 text-yellow-800',
    in_progress: 'bg-purple-100 text-purple-800',
    pending: 'bg-orange-100 text-orange-800',
    resolved: 'bg-green-100 text-green-800',
    closed: 'bg-gray-100 text-gray-800'
  }
  return classes[props.status] || 'bg-gray-100 text-gray-800'
})

const displayStatus = computed(() => {
  const display = {
    new: 'New',
    assigned: 'Assigned',
    in_progress: 'In Progress',
    pending: 'Pending',
    resolved: 'Resolved',
    closed: 'Closed'
  }
  return display[props.status] || props.status
})
</script>