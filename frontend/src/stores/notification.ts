import { defineStore } from 'pinia'
import { api } from '@/services/api'

interface Notification {
  id: string
  type: string
  title: string
  body: string
  data: any
  priority: string
  status: 'unread' | 'read' | 'archived'
  created_at: string
  read_at?: string
}

interface NotificationStatistics {
  unread: number
  read: number
  archived: number
  total: number
}

interface NotificationPreference {
  notification_type: string
  enabled: boolean
  channels: string[]
  frequency: string
  filters?: any
  enable_quiet_hours?: boolean
  quiet_hours_start?: string
  quiet_hours_end?: string
  timezone?: string
}

export const useNotificationStore = defineStore('notification', {
  state: () => ({
    notifications: [] as Notification[],
    recentNotifications: [] as Notification[],
    statistics: {
      unread: 0,
      read: 0,
      archived: 0,
      total: 0,
    } as NotificationStatistics,
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
    },
    preferences: [] as NotificationPreference[],
  }),

  getters: {
    unreadCount: (state) => state.statistics.unread,
    hasUnread: (state) => state.statistics.unread > 0,
  },

  actions: {
    async fetchNotifications(params: any = {}) {
      try {
        const response = await api.get('/api/v1/notifications', { params })
        this.notifications = response.data.notifications
        this.pagination = response.data.pagination
        this.statistics = response.data.statistics
      } catch (error) {
        console.error('Failed to fetch notifications:', error)
        throw error
      }
    },

    async fetchRecentNotifications() {
      try {
        const response = await api.get('/api/v1/notifications', {
          params: {
            status: 'unread',
            per_page: 5,
            sort_by: 'created_at',
            sort_order: 'desc',
          },
        })
        this.recentNotifications = response.data.notifications
        this.statistics = response.data.statistics
      } catch (error) {
        console.error('Failed to fetch recent notifications:', error)
        throw error
      }
    },

    async fetchStatistics() {
      try {
        const response = await api.get('/api/v1/notifications/statistics')
        this.statistics = response.data
      } catch (error) {
        console.error('Failed to fetch notification statistics:', error)
        throw error
      }
    },

    async markAsRead(notificationIds: string[]) {
      try {
        await api.post('/api/v1/notifications/mark-as-read', {
          notification_ids: notificationIds,
        })
        
        // Update local state
        notificationIds.forEach(id => {
          const notification = this.notifications.find(n => n.id === id)
          if (notification) {
            notification.status = 'read'
          }
          const recentNotification = this.recentNotifications.find(n => n.id === id)
          if (recentNotification) {
            recentNotification.status = 'read'
          }
        })
        
        // Update statistics
        this.statistics.unread = Math.max(0, this.statistics.unread - notificationIds.length)
        this.statistics.read += notificationIds.length
      } catch (error) {
        console.error('Failed to mark notifications as read:', error)
        throw error
      }
    },

    async markAsUnread(notificationIds: string[]) {
      try {
        await api.post('/api/v1/notifications/mark-as-unread', {
          notification_ids: notificationIds,
        })
        
        // Update local state
        notificationIds.forEach(id => {
          const notification = this.notifications.find(n => n.id === id)
          if (notification) {
            notification.status = 'unread'
          }
        })
        
        // Update statistics
        this.statistics.read = Math.max(0, this.statistics.read - notificationIds.length)
        this.statistics.unread += notificationIds.length
      } catch (error) {
        console.error('Failed to mark notifications as unread:', error)
        throw error
      }
    },

    async markAllAsRead() {
      try {
        const response = await api.post('/api/v1/notifications/mark-all-as-read')
        
        // Update local state
        this.notifications.forEach(notification => {
          if (notification.status === 'unread') {
            notification.status = 'read'
          }
        })
        this.recentNotifications = []
        
        // Update statistics
        this.statistics.read += this.statistics.unread
        this.statistics.unread = 0
        
        return response.data.count
      } catch (error) {
        console.error('Failed to mark all notifications as read:', error)
        throw error
      }
    },

    async archive(notificationIds: string[]) {
      try {
        await api.post('/api/v1/notifications/archive', {
          notification_ids: notificationIds,
        })
        
        // Remove from local state
        this.notifications = this.notifications.filter(n => !notificationIds.includes(n.id))
        this.recentNotifications = this.recentNotifications.filter(n => !notificationIds.includes(n.id))
        
        // Update statistics
        this.statistics.archived += notificationIds.length
        this.statistics.total = Math.max(0, this.statistics.total - notificationIds.length)
      } catch (error) {
        console.error('Failed to archive notifications:', error)
        throw error
      }
    },

    async deleteNotification(notificationId: string) {
      try {
        await api.delete(`/api/v1/notifications/${notificationId}`)
        
        // Remove from local state
        this.notifications = this.notifications.filter(n => n.id !== notificationId)
        this.recentNotifications = this.recentNotifications.filter(n => n.id !== notificationId)
        
        // Update statistics
        this.statistics.total = Math.max(0, this.statistics.total - 1)
      } catch (error) {
        console.error('Failed to delete notification:', error)
        throw error
      }
    },

    async fetchPreferences() {
      try {
        const response = await api.get('/api/v1/notifications/preferences')
        this.preferences = response.data.preferences
        return response.data
      } catch (error) {
        console.error('Failed to fetch notification preferences:', error)
        throw error
      }
    },

    async updatePreferences(preferences: NotificationPreference[]) {
      try {
        const response = await api.put('/api/v1/notifications/preferences', {
          preferences,
        })
        this.preferences = response.data.preferences
      } catch (error) {
        console.error('Failed to update notification preferences:', error)
        throw error
      }
    },

    async sendTestNotification(type: string) {
      try {
        await api.post('/api/v1/notifications/test', {
          type,
          channels: ['email', 'in_app'],
        })
      } catch (error) {
        console.error('Failed to send test notification:', error)
        throw error
      }
    },

    // Add notification from WebSocket
    addNotification(notification: Notification) {
      // Add to recent notifications
      this.recentNotifications.unshift(notification)
      if (this.recentNotifications.length > 5) {
        this.recentNotifications.pop()
      }
      
      // Update statistics
      if (notification.status === 'unread') {
        this.statistics.unread++
        this.statistics.total++
      }
    },
  },
})