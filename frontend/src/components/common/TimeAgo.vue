<template>
  <span :title="formattedDate">{{ timeAgo }}</span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { formatDistanceToNow, format } from 'date-fns'

interface Props {
  date: string | Date
}

const props = defineProps<Props>()

const timeAgo = computed(() => {
  const dateObj = typeof props.date === 'string' ? new Date(props.date) : props.date
  return formatDistanceToNow(dateObj, { addSuffix: true })
})

const formattedDate = computed(() => {
  const dateObj = typeof props.date === 'string' ? new Date(props.date) : props.date
  return format(dateObj, 'PPpp')
})
</script>