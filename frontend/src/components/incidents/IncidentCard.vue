<template>
  <div
    class="bg-white p-6 rounded-lg shadow-defender-sm hover:shadow-defender-md transition-shadow cursor-pointer border-l-4"
    :class="priorityBorderClass"
    @click="$emit('click', incident)"
  >
    <div class="flex justify-between items-start">
      <div class="flex-1">
        <!-- Header -->
        <div class="flex items-center space-x-3 mb-2">
          <span class="text-sm font-mono text-gray-500">{{ incident.number }}</span>
          <StatusBadge :status="incident.status" />
          <PriorityBadge :priority="incident.priority" />
          <SLAIndicator 
            v-if="incident.is_overdue" 
            type="overdue"
          />
        </div>

        <!-- Title -->
        <h3 class="text-lg font-semibold text-deep-sea-500 mb-2">
          {{ incident.title }}
        </h3>

        <!-- Description -->
        <p class="text-gray-600 line-clamp-2 mb-3">
          {{ incident.description }}
        </p>

        <!-- Footer -->
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4 text-sm text-gray-500">
            <div class="flex items-center">
              <ClockIcon class="h-4 w-4 mr-1" />
              <TimeAgo :date="incident.created_at" />
            </div>
            <div v-if="incident.assigned_user" class="flex items-center">
              <UserIcon class="h-4 w-4 mr-1" />
              <span>{{ incident.assigned_user.name }}</span>
            </div>
          </div>
          
          <!-- Actions -->
          <div class="flex items-center space-x-2" @click.stop>
            <button
              v-if="!incident.assigned_to && canAssign"
              @click="$emit('assign', incident)"
              class="btn-secondary-sm"
            >
              Assign to Me
            </button>
            <button
              v-if="canResolve"
              @click="$emit('resolve', incident)"
              class="btn-primary-sm"
            >
              Resolve
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Incident } from '@/types'
import { useAuthStore } from '@/stores/auth'
import StatusBadge from '@/components/common/StatusBadge.vue'
import PriorityBadge from '@/components/common/PriorityBadge.vue'
import SLAIndicator from '@/components/common/SLAIndicator.vue'
import TimeAgo from '@/components/common/TimeAgo.vue'
import { ClockIcon, UserIcon } from '@heroicons/vue/24/outline'

interface Props {
  incident: Incident
}

const props = defineProps<Props>()

const emit = defineEmits<{
  click: [incident: Incident]
  assign: [incident: Incident]
  resolve: [incident: Incident]
}>()

const authStore = useAuthStore()

const priorityBorderClass = computed(() => {
  const classes = {
    critical: 'border-priority-critical',
    high: 'border-priority-high',
    medium: 'border-priority-medium',
    low: 'border-priority-low'
  }
  return classes[props.incident.priority]
})

const canAssign = computed(() => {
  return authStore.isAgent && !props.incident.assigned_to
})

const canResolve = computed(() => {
  return authStore.isAgent && 
    props.incident.assigned_to === authStore.user?.id &&
    !['resolved', 'closed'].includes(props.incident.status)
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>