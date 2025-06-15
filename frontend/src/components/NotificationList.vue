<template>
  <div class="bg-white shadow rounded-lg">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">Notifications</h2>
        
        <!-- Actions -->
        <div class="flex items-center space-x-4">
          <!-- Filters -->
          <div class="flex items-center space-x-2">
            <select
              v-model="filters.status"
              @change="applyFilters"
              class="text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">All Status</option>
              <option value="unread">Unread</option>
              <option value="read">Read</option>
              <option value="archived">Archived</option>
            </select>

            <select
              v-model="filters.priority"
              @change="applyFilters"
              class="text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">All Priorities</option>
              <option value="critical">Critical</option>
              <option value="high">High</option>
              <option value="medium">Medium</option>
              <option value="low">Low</option>
            </select>

            <select
              v-model="filters.type"
              @change="applyFilters"
              class="text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="">All Types</option>
              <option value="incident">Incidents</option>
              <option value="change">Changes</option>
              <option value="problem">Problems</option>
              <option value="sla">SLA</option>
              <option value="service_request">Service Requests</option>
              <option value="knowledge">Knowledge</option>
            </select>
          </div>

          <!-- Bulk Actions -->
          <div v-if="selectedNotifications.length > 0" class="flex items-center space-x-2">
            <span class="text-sm text-gray-500">
              {{ selectedNotifications.length }} selected
            </span>
            <button
              @click="markSelectedAsRead"
              class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
            >
              Mark as read
            </button>
            <button
              @click="archiveSelected"
              class="text-sm font-medium text-gray-600 hover:text-gray-500"
            >
              Archive
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Notifications List -->
    <div v-if="loading" class="p-8 text-center">
      <LoadingSpinner />
    </div>

    <div v-else-if="notifications.length === 0" class="p-8 text-center">
      <svg
        class="mx-auto h-12 w-12 text-gray-400"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
        />
      </svg>
      <p class="mt-2 text-sm text-gray-500">No notifications found</p>
    </div>

    <div v-else>
      <ul class="divide-y divide-gray-200">
        <li
          v-for="notification in notifications"
          :key="notification.id"
          class="relative hover:bg-gray-50 transition-colors"
        >
          <div class="px-6 py-4">
            <div class="flex items-start">
              <!-- Checkbox -->
              <div class="flex-shrink-0 mr-3">
                <input
                  type="checkbox"
                  :value="notification.id"
                  v-model="selectedNotifications"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
              </div>

              <!-- Icon -->
              <div class="flex-shrink-0 mr-4">
                <div
                  class="h-10 w-10 rounded-full flex items-center justify-center"
                  :class="getIconClass(notification)"
                >
                  <component
                    :is="getIcon(notification.type)"
                    class="h-6 w-6"
                  />
                </div>
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <p
                      class="text-sm font-medium text-gray-900"
                      :class="{ 'font-semibold': notification.status === 'unread' }"
                    >
                      {{ notification.title }}
                    </p>
                    <p class="mt-1 text-sm text-gray-600">
                      {{ notification.body }}
                    </p>
                    
                    <!-- Meta Info -->
                    <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500">
                      <span class="flex items-center">
                        <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                          />
                        </svg>
                        <TimeAgo :date="notification.created_at" />
                      </span>
                      
                      <span
                        v-if="notification.priority"
                        class="flex items-center"
                      >
                        <PriorityBadge :priority="notification.priority" size="small" />
                      </span>
                    </div>
                  </div>

                  <!-- Actions -->
                  <div class="flex-shrink-0 ml-4">
                    <NotificationMenu
                      :notification="notification"
                      @mark-as-read="markAsRead"
                      @mark-as-unread="markAsUnread"
                      @archive="archiveNotification"
                      @delete="deleteNotification"
                    />
                  </div>
                </div>

                <!-- Unread Indicator -->
                <div
                  v-if="notification.status === 'unread'"
                  class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-600"
                />
              </div>
            </div>
          </div>
        </li>
      </ul>

      <!-- Pagination -->
      <div class="px-6 py-3 border-t border-gray-200">
        <Pagination
          :current-page="pagination.current_page"
          :total-pages="pagination.last_page"
          :total-items="pagination.total"
          :per-page="pagination.per_page"
          @change="handlePageChange"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useNotificationStore } from '@/stores/notification'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import TimeAgo from '@/components/common/TimeAgo.vue'
import PriorityBadge from '@/components/common/PriorityBadge.vue'
import Pagination from '@/components/common/Pagination.vue'
import NotificationMenu from './NotificationMenu.vue'
import {
  BellIcon,
  ExclamationCircleIcon,
  ClockIcon,
  ClipboardCheckIcon,
  LightBulbIcon,
  DocumentTextIcon,
  InboxIcon,
  CogIcon,
} from '@heroicons/vue/24/outline'

const notificationStore = useNotificationStore()

const loading = ref(false)
const selectedNotifications = ref<string[]>([])

const filters = ref({
  status: '',
  priority: '',
  type: '',
})

const notifications = computed(() => notificationStore.notifications)
const pagination = computed(() => notificationStore.pagination)

const getIcon = (type: string) => {
  const icons: Record<string, any> = {
    incident_created: ExclamationCircleIcon,
    incident_updated: ExclamationCircleIcon,
    incident_resolved: ClipboardCheckIcon,
    sla_breach: ClockIcon,
    sla_warning: ClockIcon,
    change_requested: CogIcon,
    change_approved: ClipboardCheckIcon,
    problem_created: LightBulbIcon,
    knowledge_article_published: DocumentTextIcon,
    service_request_created: InboxIcon,
  }
  return icons[type] || BellIcon
}

const getIconClass = (notification: any) => {
  const classes: Record<string, string> = {
    critical: 'bg-red-100 text-red-600',
    high: 'bg-orange-100 text-orange-600',
    medium: 'bg-yellow-100 text-yellow-600',
    low: 'bg-blue-100 text-blue-600',
  }
  return classes[notification.priority] || 'bg-gray-100 text-gray-600'
}

const applyFilters = () => {
  loadNotifications()
}

const loadNotifications = async (page = 1) => {
  loading.value = true
  try {
    await notificationStore.fetchNotifications({
      page,
      ...filters.value,
    })
  } finally {
    loading.value = false
  }
}

const handlePageChange = (page: number) => {
  loadNotifications(page)
}

const markAsRead = async (notificationId: string) => {
  await notificationStore.markAsRead([notificationId])
}

const markAsUnread = async (notificationId: string) => {
  await notificationStore.markAsUnread([notificationId])
}

const archiveNotification = async (notificationId: string) => {
  await notificationStore.archive([notificationId])
}

const deleteNotification = async (notificationId: string) => {
  if (confirm('Are you sure you want to delete this notification?')) {
    await notificationStore.deleteNotification(notificationId)
  }
}

const markSelectedAsRead = async () => {
  await notificationStore.markAsRead(selectedNotifications.value)
  selectedNotifications.value = []
}

const archiveSelected = async () => {
  await notificationStore.archive(selectedNotifications.value)
  selectedNotifications.value = []
}

onMounted(() => {
  loadNotifications()
})
</script>