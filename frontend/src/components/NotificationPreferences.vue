<template>
  <div class="max-w-4xl mx-auto">
    <div class="bg-white shadow rounded-lg">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">Notification Preferences</h2>
        <p class="mt-1 text-sm text-gray-600">
          Manage how and when you receive notifications
        </p>
      </div>

      <div v-if="loading" class="p-8 text-center">
        <LoadingSpinner />
      </div>

      <div v-else class="p-6 space-y-6">
        <!-- Global Settings -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h3 class="text-sm font-medium text-gray-900 mb-3">Global Settings</h3>
          
          <div class="space-y-3">
            <!-- Quiet Hours -->
            <div class="flex items-start">
              <div class="flex items-center h-5">
                <input
                  v-model="globalSettings.enableQuietHours"
                  type="checkbox"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                />
              </div>
              <div class="ml-3">
                <label class="text-sm font-medium text-gray-700">
                  Enable Quiet Hours
                </label>
                <p class="text-xs text-gray-500">
                  Pause non-critical notifications during specified hours
                </p>
                
                <div v-if="globalSettings.enableQuietHours" class="mt-2 grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-medium text-gray-700">Start Time</label>
                    <input
                      v-model="globalSettings.quietHoursStart"
                      type="time"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-700">End Time</label>
                    <input
                      v-model="globalSettings.quietHoursEnd"
                      type="time"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Timezone -->
            <div>
              <label class="block text-sm font-medium text-gray-700">Timezone</label>
              <select
                v-model="globalSettings.timezone"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              >
                <option value="UTC">UTC</option>
                <option value="America/New_York">Eastern Time</option>
                <option value="America/Chicago">Central Time</option>
                <option value="America/Denver">Mountain Time</option>
                <option value="America/Los_Angeles">Pacific Time</option>
                <option value="Europe/London">London</option>
                <option value="Europe/Paris">Paris</option>
                <option value="Asia/Tokyo">Tokyo</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Notification Types -->
        <div class="space-y-4">
          <h3 class="text-sm font-medium text-gray-900">Notification Types</h3>
          
          <div class="space-y-3">
            <div
              v-for="preference in preferences"
              :key="preference.notification_type"
              class="bg-white border border-gray-200 rounded-lg p-4"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center">
                    <h4 class="text-sm font-medium text-gray-900">
                      {{ getNotificationTypeName(preference.notification_type) }}
                    </h4>
                    <span
                      v-if="!preference.enabled"
                      class="ml-2 text-xs text-gray-500"
                    >
                      (Disabled)
                    </span>
                  </div>
                  <p class="mt-1 text-xs text-gray-500">
                    {{ getNotificationTypeDescription(preference.notification_type) }}
                  </p>
                </div>
                
                <div class="flex items-center">
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input
                      v-model="preference.enabled"
                      type="checkbox"
                      class="sr-only peer"
                    />
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                  </label>
                </div>
              </div>

              <div v-if="preference.enabled" class="mt-4 space-y-3">
                <!-- Delivery Channels -->
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-2">
                    Delivery Channels
                  </label>
                  <div class="flex flex-wrap gap-2">
                    <label
                      v-for="channel in availableChannels"
                      :key="channel"
                      class="inline-flex items-center"
                    >
                      <input
                        v-model="preference.channels"
                        :value="channel"
                        type="checkbox"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                      />
                      <span class="ml-2 text-sm text-gray-700">
                        {{ getChannelName(channel) }}
                      </span>
                    </label>
                  </div>
                </div>

                <!-- Frequency -->
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">
                    Frequency
                  </label>
                  <select
                    v-model="preference.frequency"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                  >
                    <option value="immediate">Immediate</option>
                    <option value="hourly">Hourly Digest</option>
                    <option value="daily">Daily Digest</option>
                    <option value="weekly">Weekly Digest</option>
                  </select>
                </div>

                <!-- Priority Filter -->
                <div v-if="hasPriorityFilter(preference.notification_type)">
                  <label class="block text-xs font-medium text-gray-700 mb-2">
                    Only notify for these priorities
                  </label>
                  <div class="flex flex-wrap gap-2">
                    <label
                      v-for="priority in ['critical', 'high', 'medium', 'low']"
                      :key="priority"
                      class="inline-flex items-center"
                    >
                      <input
                        v-model="preference.filters.priority"
                        :value="priority"
                        type="checkbox"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                      />
                      <span class="ml-2 text-sm text-gray-700 capitalize">
                        {{ priority }}
                      </span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end pt-4">
          <button
            @click="savePreferences"
            :disabled="saving"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg
              v-if="saving"
              class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              />
            </svg>
            {{ saving ? 'Saving...' : 'Save Preferences' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Test Notification -->
    <div class="mt-6 bg-white shadow rounded-lg p-6">
      <h3 class="text-sm font-medium text-gray-900 mb-4">Test Notifications</h3>
      <p class="text-sm text-gray-600 mb-4">
        Send a test notification to verify your settings are working correctly.
      </p>
      
      <div class="flex items-center space-x-4">
        <select
          v-model="testNotificationType"
          class="block w-64 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
        >
          <option value="">Select notification type</option>
          <option
            v-for="type in notificationTypes"
            :key="type"
            :value="type"
          >
            {{ getNotificationTypeName(type) }}
          </option>
        </select>
        
        <button
          @click="sendTestNotification"
          :disabled="!testNotificationType || sendingTest"
          class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ sendingTest ? 'Sending...' : 'Send Test' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useNotificationStore } from '@/stores/notification'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import { useToast } from '@/composables/useToast'

const notificationStore = useNotificationStore()
const { showSuccess, showError } = useToast()

const loading = ref(false)
const saving = ref(false)
const sendingTest = ref(false)

const preferences = ref<any[]>([])
const availableChannels = ref(['email', 'sms', 'in_app', 'slack'])
const notificationTypes = ref<string[]>([])
const testNotificationType = ref('')

const globalSettings = ref({
  enableQuietHours: false,
  quietHoursStart: '22:00',
  quietHoursEnd: '08:00',
  timezone: 'UTC',
})

const getNotificationTypeName = (type: string) => {
  const names: Record<string, string> = {
    incident_created: 'Incident Created',
    incident_updated: 'Incident Updated',
    incident_resolved: 'Incident Resolved',
    incident_assigned: 'Incident Assigned',
    sla_breach: 'SLA Breach',
    sla_warning: 'SLA Warning',
    change_requested: 'Change Requested',
    change_approved: 'Change Approved',
    change_rejected: 'Change Rejected',
    problem_created: 'Problem Created',
    problem_resolved: 'Problem Resolved',
    knowledge_article_published: 'Knowledge Article Published',
    service_request_created: 'Service Request Created',
    service_request_completed: 'Service Request Completed',
  }
  return names[type] || type.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase())
}

const getNotificationTypeDescription = (type: string) => {
  const descriptions: Record<string, string> = {
    incident_created: 'Receive notifications when new incidents are created',
    incident_updated: 'Receive notifications when incidents are updated',
    incident_resolved: 'Receive notifications when incidents are resolved',
    incident_assigned: 'Receive notifications when incidents are assigned to you',
    sla_breach: 'Receive urgent notifications when SLAs are breached',
    sla_warning: 'Receive warnings when SLAs are about to breach',
    change_requested: 'Receive notifications for new change requests',
    change_approved: 'Receive notifications when changes are approved',
    change_rejected: 'Receive notifications when changes are rejected',
    problem_created: 'Receive notifications for new problem records',
    problem_resolved: 'Receive notifications when problems are resolved',
    knowledge_article_published: 'Receive notifications for new knowledge articles',
    service_request_created: 'Receive notifications for new service requests',
    service_request_completed: 'Receive notifications when service requests are completed',
  }
  return descriptions[type] || 'Manage notification settings for this type'
}

const getChannelName = (channel: string) => {
  const names: Record<string, string> = {
    email: 'Email',
    sms: 'SMS',
    in_app: 'In-App',
    slack: 'Slack',
    teams: 'Teams',
    webhook: 'Webhook',
  }
  return names[channel] || channel
}

const hasPriorityFilter = (type: string) => {
  return ['incident_created', 'incident_updated', 'problem_created'].includes(type)
}

const loadPreferences = async () => {
  loading.value = true
  try {
    const response = await notificationStore.fetchPreferences()
    preferences.value = response.preferences
    availableChannels.value = response.available_channels
    
    // Extract notification types
    notificationTypes.value = preferences.value.map(p => p.notification_type)
    
    // Apply global settings from first preference
    if (preferences.value.length > 0) {
      const firstPref = preferences.value[0]
      globalSettings.value.enableQuietHours = firstPref.enable_quiet_hours || false
      globalSettings.value.quietHoursStart = firstPref.quiet_hours_start || '22:00'
      globalSettings.value.quietHoursEnd = firstPref.quiet_hours_end || '08:00'
      globalSettings.value.timezone = firstPref.timezone || 'UTC'
    }
  } catch (error) {
    showError('Failed to load notification preferences')
  } finally {
    loading.value = false
  }
}

const savePreferences = async () => {
  saving.value = true
  try {
    // Apply global settings to all preferences
    const updatedPreferences = preferences.value.map(pref => ({
      ...pref,
      enable_quiet_hours: globalSettings.value.enableQuietHours,
      quiet_hours_start: globalSettings.value.quietHoursStart,
      quiet_hours_end: globalSettings.value.quietHoursEnd,
      timezone: globalSettings.value.timezone,
    }))
    
    await notificationStore.updatePreferences(updatedPreferences)
    showSuccess('Notification preferences saved successfully')
  } catch (error) {
    showError('Failed to save notification preferences')
  } finally {
    saving.value = false
  }
}

const sendTestNotification = async () => {
  if (!testNotificationType.value) return
  
  sendingTest.value = true
  try {
    await notificationStore.sendTestNotification(testNotificationType.value)
    showSuccess('Test notification sent successfully')
    testNotificationType.value = ''
  } catch (error) {
    showError('Failed to send test notification')
  } finally {
    sendingTest.value = false
  }
}

onMounted(() => {
  loadPreferences()
})
</script>