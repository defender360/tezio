<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Change Management</h1>
        <div class="flex items-center gap-4">
          <!-- View Toggle -->
          <div class="flex items-center bg-gray-100 rounded-lg p-1">
            <button
              @click="viewMode = 'list'"
              :class="[
                'px-3 py-1.5 rounded text-sm font-medium transition-colors',
                viewMode === 'list'
                  ? 'bg-white text-gray-900 shadow-sm'
                  : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                List
              </span>
            </button>
            <button
              @click="viewMode = 'calendar'"
              :class="[
                'px-3 py-1.5 rounded text-sm font-medium transition-colors',
                viewMode === 'calendar'
                  ? 'bg-white text-gray-900 shadow-sm'
                  : 'text-gray-600 hover:text-gray-900'
              ]"
            >
              <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Calendar
              </span>
            </button>
          </div>
          <router-link
            to="/changes/new"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            New Change Request
          </router-link>
        </div>
      </div>
      
      <p class="mt-2 text-gray-600">
        Manage and track changes to IT infrastructure and services
      </p>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select
            v-model="filters.status"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="planning">Planning</option>
            <option value="awaiting_approval">Awaiting Approval</option>
            <option value="approved">Approved</option>
            <option value="scheduled">Scheduled</option>
            <option value="implementing">Implementing</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
          <select
            v-model="filters.type"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">All Types</option>
            <option value="standard">Standard</option>
            <option value="normal">Normal</option>
            <option value="emergency">Emergency</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Risk Level</label>
          <select
            v-model="filters.risk"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">All Risk Levels</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="critical">Critical</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search changes..."
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
        </div>
      </div>
    </div>

    <!-- List View -->
    <div v-if="viewMode === 'list'" class="space-y-4">
      <div v-if="isLoading" class="flex justify-center py-8">
        <LoadingSpinner />
      </div>
      
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-red-800">Error loading changes: {{ error.message }}</p>
      </div>
      
      <div v-else-if="changes?.length === 0" class="bg-gray-50 rounded-lg p-8 text-center">
        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <p class="text-gray-600">No changes found</p>
      </div>
      
      <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Change ID
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Title
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Type
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Risk
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Scheduled
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Requester
              </th>
              <th class="relative px-6 py-3">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="change in changes" :key="change.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <router-link
                  :to="`/changes/${change.id}`"
                  class="text-blue-600 hover:text-blue-800 font-medium"
                >
                  {{ change.number }}
                </router-link>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900">{{ change.title }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    change.type === 'emergency'
                      ? 'bg-red-100 text-red-800'
                      : change.type === 'normal'
                      ? 'bg-blue-100 text-blue-800'
                      : 'bg-gray-100 text-gray-800'
                  ]"
                >
                  {{ change.type }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <StatusBadge :status="change.status" />
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    change.risk === 'critical'
                      ? 'bg-red-100 text-red-800'
                      : change.risk === 'high'
                      ? 'bg-orange-100 text-orange-800'
                      : change.risk === 'medium'
                      ? 'bg-yellow-100 text-yellow-800'
                      : 'bg-green-100 text-green-800'
                  ]"
                >
                  {{ change.risk }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(change.scheduledStart) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ change.requester?.name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <router-link
                  :to="`/changes/${change.id}`"
                  class="text-blue-600 hover:text-blue-900"
                >
                  View
                </router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <Pagination
        v-if="changes && changes.length > 0"
        :current-page="currentPage"
        :total-pages="totalPages"
        @update:current-page="currentPage = $event"
      />
    </div>

    <!-- Calendar View -->
    <div v-else-if="viewMode === 'calendar'" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">
          {{ currentMonth }}
        </h2>
        <div class="flex items-center gap-2">
          <button
            @click="previousMonth"
            class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <button
            @click="today"
            class="px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md transition-colors"
          >
            Today
          </button>
          <button
            @click="nextMonth"
            class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>
      
      <!-- Calendar Grid -->
      <div class="grid grid-cols-7 gap-px bg-gray-200">
        <!-- Weekday Headers -->
        <div
          v-for="day in weekDays"
          :key="day"
          class="bg-gray-50 p-2 text-center text-xs font-medium text-gray-700 uppercase"
        >
          {{ day }}
        </div>
        
        <!-- Calendar Days -->
        <div
          v-for="(day, index) in calendarDays"
          :key="index"
          :class="[
            'bg-white p-2 min-h-[100px]',
            day.isCurrentMonth ? '' : 'bg-gray-50',
            day.isToday ? 'ring-2 ring-inset ring-blue-500' : ''
          ]"
        >
          <div class="text-sm font-medium text-gray-900 mb-1">
            {{ day.date }}
          </div>
          <div class="space-y-1">
            <div
              v-for="change in day.changes"
              :key="change.id"
              class="text-xs p-1 rounded cursor-pointer hover:opacity-80 transition-opacity"
              :class="[
                change.type === 'emergency'
                  ? 'bg-red-100 text-red-800'
                  : change.type === 'normal'
                  ? 'bg-blue-100 text-blue-800'
                  : 'bg-gray-100 text-gray-800'
              ]"
              @click="$router.push(`/changes/${change.id}`)"
            >
              {{ change.number }}: {{ change.title }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { useRouter } from 'vue-router'
import { format, startOfMonth, endOfMonth, eachDayOfInterval, isSameMonth, isToday, parseISO } from 'date-fns'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import Pagination from '@/components/common/Pagination.vue'
import { api } from '@/services/api'

// Types
interface Change {
  id: string
  number: string
  title: string
  description: string
  type: 'standard' | 'normal' | 'emergency'
  status: string
  risk: 'low' | 'medium' | 'high' | 'critical'
  scheduledStart: string
  scheduledEnd: string
  requester: {
    id: string
    name: string
  }
  assignee?: {
    id: string
    name: string
  }
}

// State
const viewMode = ref<'list' | 'calendar'>('list')
const filters = ref({
  status: '',
  type: '',
  risk: '',
  search: ''
})
const currentPage = ref(1)
const currentDate = ref(new Date())

// Query
const { data, isLoading, error } = useQuery({
  queryKey: ['changes', filters, currentPage],
  queryFn: async () => {
    const params = new URLSearchParams({
      page: currentPage.value.toString(),
      limit: '20',
      ...Object.fromEntries(
        Object.entries(filters.value).filter(([_, v]) => v !== '')
      )
    })
    const response = await api.get(`/changes?${params}`)
    return response.data
  }
})

// Computed
const changes = computed(() => data.value?.items || [])
const totalPages = computed(() => data.value?.totalPages || 1)

const currentMonth = computed(() => format(currentDate.value, 'MMMM yyyy'))

const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const calendarDays = computed(() => {
  const start = startOfMonth(currentDate.value)
  const end = endOfMonth(currentDate.value)
  const days = eachDayOfInterval({ start, end })
  
  // Add padding days from previous month
  const startDay = start.getDay()
  const paddingDays = Array(startDay).fill(null).map((_, i) => {
    const date = new Date(start)
    date.setDate(date.getDate() - (startDay - i))
    return date
  })
  
  const allDays = [...paddingDays, ...days]
  
  // Add padding days from next month to complete the grid
  const remainingDays = 42 - allDays.length // 6 weeks * 7 days
  const nextMonthDays = Array(remainingDays).fill(null).map((_, i) => {
    const date = new Date(end)
    date.setDate(date.getDate() + i + 1)
    return date
  })
  
  return [...allDays, ...nextMonthDays].map(date => ({
    date: date.getDate(),
    isCurrentMonth: isSameMonth(date, currentDate.value),
    isToday: isToday(date),
    changes: getChangesForDate(date)
  }))
})

// Methods
const formatDate = (dateString: string) => {
  if (!dateString) return '-'
  return format(parseISO(dateString), 'MMM d, yyyy HH:mm')
}

const getChangesForDate = (date: Date) => {
  return changes.value.filter((change: Change) => {
    const changeDate = parseISO(change.scheduledStart)
    return (
      changeDate.getFullYear() === date.getFullYear() &&
      changeDate.getMonth() === date.getMonth() &&
      changeDate.getDate() === date.getDate()
    )
  })
}

const previousMonth = () => {
  const newDate = new Date(currentDate.value)
  newDate.setMonth(newDate.getMonth() - 1)
  currentDate.value = newDate
}

const nextMonth = () => {
  const newDate = new Date(currentDate.value)
  newDate.setMonth(newDate.getMonth() + 1)
  currentDate.value = newDate
}

const today = () => {
  currentDate.value = new Date()
}

// Watchers
watch(filters, () => {
  currentPage.value = 1
}, { deep: true })
</script>