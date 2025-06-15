<template>
  <div class="relative">
    <!-- Notification Bell Button -->
    <button
      @click="toggleDropdown"
      class="relative p-2 text-gray-600 hover:text-gray-900 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 rounded-lg"
      :class="{ 'text-indigo-600': isOpen }"
    >
      <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
        />
      </svg>
      
      <!-- Unread Count Badge -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 flex items-center justify-center h-5 w-5 text-xs font-bold text-white bg-red-500 rounded-full animate-pulse"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown Menu -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50"
        v-click-outside="closeDropdown"
      >
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
            <div class="flex items-center space-x-2">
              <button
                v-if="unreadCount > 0"
                @click="markAllAsRead"
                class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
              >
                Mark all as read
              </button>
              <router-link
                to="/notifications"
                class="text-sm text-gray-500 hover:text-gray-700"
                @click="closeDropdown"
              >
                View all
              </router-link>
            </div>
          </div>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
          <div v-if="loading" class="p-4 text-center">
            <LoadingSpinner size="small" />
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
            <p class="mt-2 text-sm text-gray-500">No new notifications</p>
          </div>

          <div v-else class="divide-y divide-gray-200">
            <NotificationItem
              v-for="notification in notifications"
              :key="notification.id"
              :notification="notification"
              @click="handleNotificationClick(notification)"
              @mark-as-read="markAsRead(notification.id)"
              class="cursor-pointer hover:bg-gray-50 transition-colors"
            />
          </div>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
          <router-link
            to="/notifications/preferences"
            class="flex items-center justify-center text-sm text-gray-600 hover:text-gray-900"
            @click="closeDropdown"
          >
            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
              />
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
              />
            </svg>
            Notification Settings
          </router-link>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notification'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import NotificationItem from './NotificationItem.vue'
import { vClickOutside } from '@/directives/clickOutside'

const router = useRouter()
const notificationStore = useNotificationStore()

const isOpen = ref(false)
const loading = ref(false)

const notifications = computed(() => notificationStore.recentNotifications)
const unreadCount = computed(() => notificationStore.unreadCount)

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    loadNotifications()
  }
}

const closeDropdown = () => {
  isOpen.value = false
}

const loadNotifications = async () => {
  loading.value = true
  try {
    await notificationStore.fetchRecentNotifications()
  } finally {
    loading.value = false
  }
}

const markAsRead = async (notificationId: string) => {
  await notificationStore.markAsRead([notificationId])
}

const markAllAsRead = async () => {
  await notificationStore.markAllAsRead()
  closeDropdown()
}

const handleNotificationClick = async (notification: any) => {
  // Mark as read if unread
  if (notification.status === 'unread') {
    await markAsRead(notification.id)
  }

  // Navigate based on notification type
  const routes: Record<string, (data: any) => string> = {
    incident_created: (data) => `/incidents/${data.incident_number}`,
    incident_updated: (data) => `/incidents/${data.incident_number}`,
    incident_resolved: (data) => `/incidents/${data.incident_number}`,
    change_requested: (data) => `/changes/${data.change_number}`,
    change_approved: (data) => `/changes/${data.change_number}`,
    problem_created: (data) => `/problems/${data.problem_number}`,
    service_request_created: (data) => `/service-requests/${data.request_number}`,
    knowledge_article_published: (data) => `/knowledge/${data.article_id}`,
  }

  const routeGenerator = routes[notification.type]
  if (routeGenerator && notification.data) {
    const route = routeGenerator(notification.data)
    router.push(route)
    closeDropdown()
  }
}

// WebSocket connection for real-time notifications
let ws: WebSocket | null = null

const connectWebSocket = () => {
  // Temporarily disabled WebSocket
  return
  
  const wsUrl = import.meta.env.VITE_WS_URL || 'ws://localhost:6001'
  const token = localStorage.getItem('auth_token')

  ws = new WebSocket(`${wsUrl}/notifications?token=${token}`)

  ws.onopen = () => {
    console.log('WebSocket connected')
  }

  ws.onmessage = (event) => {
    const data = JSON.parse(event.data)
    if (data.type === 'notification') {
      notificationStore.addNotification(data.notification)
      showNotificationToast(data.notification)
    }
  }

  ws.onerror = (error) => {
    console.error('WebSocket error:', error)
  }

  ws.onclose = () => {
    console.log('WebSocket disconnected')
    // Attempt to reconnect after 5 seconds
    setTimeout(connectWebSocket, 5000)
  }
}

const showNotificationToast = (notification: any) => {
  // This could integrate with a toast library
  // For now, we'll use the browser's Notification API
  if ('Notification' in window && Notification.permission === 'granted') {
    new Notification(notification.title, {
      body: notification.body,
      icon: '/favicon.ico',
      tag: notification.id,
    })
  }
}

onMounted(() => {
  // Load initial notification count
  notificationStore.fetchStatistics()

  // Connect to WebSocket for real-time updates
  connectWebSocket()

  // Request notification permission
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission()
  }
})

onUnmounted(() => {
  if (ws) {
    ws.close()
  }
})
</script>

<style scoped>
/* Custom animation for the notification badge */
@keyframes pulse {
  0% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.1);
    opacity: 0.8;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

.animate-pulse {
  animation: pulse 2s ease-in-out infinite;
}
</style>