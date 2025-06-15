<template>
  <nav class="flex items-center justify-between">
    <div class="text-sm text-gray-700">
      Showing <span class="font-medium">{{ start }}</span> to
      <span class="font-medium">{{ end }}</span> of
      <span class="font-medium">{{ total }}</span> results
    </div>
    <div class="flex items-center space-x-2">
      <button
        @click="$emit('change', currentPage - 1)"
        :disabled="currentPage === 1"
        class="relative inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <ChevronLeftIcon class="h-4 w-4" />
      </button>
      
      <div class="flex space-x-1">
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="$emit('change', page)"
          :class="[
            page === currentPage
              ? 'bg-tech-horizon-500 text-white'
              : 'text-gray-700 bg-white hover:bg-gray-50',
            'relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md border border-gray-300'
          ]"
        >
          {{ page }}
        </button>
      </div>
      
      <button
        @click="$emit('change', currentPage + 1)"
        :disabled="currentPage === lastPage"
        class="relative inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <ChevronRightIcon class="h-4 w-4" />
      </button>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

interface Props {
  currentPage: number
  lastPage: number
  total: number
  perPage?: number
}

const props = withDefaults(defineProps<Props>(), {
  perPage: 20
})

const emit = defineEmits<{
  change: [page: number]
}>()

const start = computed(() => (props.currentPage - 1) * props.perPage + 1)
const end = computed(() => Math.min(props.currentPage * props.perPage, props.total))

const visiblePages = computed(() => {
  const delta = 2
  const range = []
  const rangeWithDots = []
  let l

  for (let i = 1; i <= props.lastPage; i++) {
    if (i === 1 || i === props.lastPage || (i >= props.currentPage - delta && i <= props.currentPage + delta)) {
      range.push(i)
    }
  }

  range.forEach((i) => {
    if (l) {
      if (i - l === 2) {
        rangeWithDots.push(l + 1)
      } else if (i - l !== 1) {
        rangeWithDots.push('...')
      }
    }
    rangeWithDots.push(i)
    l = i
  })

  return rangeWithDots
})
</script>