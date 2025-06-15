<template>
  <div
    class="px-4 py-3 transition-colors"
    :class="{
      'bg-blue-50': notification.status === 'unread',
      'hover:bg-gray-50': notification.status !== 'unread',
    }"
    @click="$emit('click', notification)"
  >
    <div class="flex items-start">
      <!-- Icon -->
      <div class="flex-shrink-0">
        <div
          class="h-8 w-8 rounded-full flex items-center justify-center"
          :class="getIconClass()"
        >
          <component :is="getIcon()" class="h-5 w-5" />
        </div>
      </div>

      <!-- Content -->
      <div class="ml-3 flex-1">
        <p
          class="text-sm text-gray-900"
          :class="{ 'font-semibold': notification.status === 'unread' }"
        >
          {{ notification.title }}
        </p>
        <p class="mt-1 text-sm text-gray-600">
          {{ notification.body }}
        </p>
        <div class="mt-1 flex items-center space-x-3 text-xs text-gray-500">
          <TimeAgo :date="notification.created_at" />
          <span v-if="notification.priority" class="flex items-center">
            <span
              class="inline-block w-2 h-2 rounded-full mr-1"
              :class="getPriorityColor()"
            ></span>
            {{ notification.priority }}
          </span>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex-shrink-0 ml-2">
        <button
          v-if="notification.status === 'unread'"
          @click.stop="$emit('mark-as-read', notification.id)"
          class="text-xs text-indigo-600 hover:text-indigo-800"
        >
          Mark as read
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import TimeAgo from '@/components/common/TimeAgo.vue'
import {
  BellIcon,
  ExclamationCircleIcon,
  ClockIcon,
  CheckCircleIcon,
  InformationCircleIcon,
  CogIcon,
  DocumentTextIcon,
} from '@heroicons/vue/24/outline'

interface Props {
  notification: {
    id: string
    type: string
    title: string
    body: string
    priority?: string
    status: string
    created_at: string
  }
}

const props = defineProps<Props>()

defineEmits<{
  click: [notification: any]
  'mark-as-read': [id: string]
}>()

const getIcon = () => {
  const icons: Record<string, any> = {
    incident_created: ExclamationCircleIcon,
    incident_updated: ExclamationCircleIcon,
    incident_resolved: CheckCircleIcon,
    sla_breach: ClockIcon,
    sla_warning: ClockIcon,
    change_requested: CogIcon,
    change_approved: CheckCircleIcon,
    problem_created: InformationCircleIcon,
    knowledge_article_published: DocumentTextIcon,
  }
  return icons[props.notification.type] || BellIcon
}

const getIconClass = () => {
  if (props.notification.type.includes('sla_breach')) {
    return 'bg-red-100 text-red-600'
  }
  if (props.notification.type.includes('sla_warning')) {
    return 'bg-yellow-100 text-yellow-600'
  }
  if (props.notification.type.includes('resolved') || props.notification.type.includes('approved')) {
    return 'bg-green-100 text-green-600'
  }
  return 'bg-gray-100 text-gray-600'
}

const getPriorityColor = () => {
  const colors: Record<string, string> = {
    critical: 'bg-red-500',
    high: 'bg-orange-500',
    medium: 'bg-yellow-500',
    low: 'bg-blue-500',
  }
  return colors[props.notification.priority || 'medium'] || 'bg-gray-500'
}
</script>