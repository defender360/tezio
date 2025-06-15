<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-4">
          <div class="flex items-center">
            <h1 class="text-2xl font-brain font-bold text-deep-sea-500">Defender360 Portal</h1>
          </div>
          <div class="flex items-center space-x-4">
            <button
              @click="showNewTicketModal = true"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-deep-sea-600 hover:bg-deep-sea-700"
            >
              <PlusIcon class="-ml-1 mr-2 h-5 w-5" />
              New Ticket
            </button>
            <div class="relative">
              <button
                @click="showUserMenu = !showUserMenu"
                class="flex items-center text-gray-700 hover:text-gray-900"
              >
                <img
                  class="h-8 w-8 rounded-full"
                  :src="currentUser?.avatar || 'https://ui-avatars.com/api/?name=' + currentUser?.name"
                  :alt="currentUser?.name"
                />
                <span class="ml-2">{{ currentUser?.name }}</span>
                <ChevronDownIcon class="ml-1 h-4 w-4" />
              </button>
              
              <div
                v-if="showUserMenu"
                class="absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 z-10"
              >
                <button
                  @click="logout"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left"
                >
                  Sign out
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Welcome Section -->
      <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-900">Welcome back, {{ currentUser?.name }}</h2>
        <p class="mt-1 text-sm text-gray-600">Track your tickets and access our knowledge base</p>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <TicketIcon class="h-6 w-6 text-gray-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Open Tickets</dt>
                  <dd class="text-lg font-medium text-gray-900">{{ stats?.open_tickets || 0 }}</dd>
                </dl>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-5 py-3">
            <router-link to="/portal/tickets" class="text-sm font-medium text-deep-sea-600 hover:text-deep-sea-900">
              View all
            </router-link>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <CheckCircleIcon class="h-6 w-6 text-green-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Resolved This Month</dt>
                  <dd class="text-lg font-medium text-gray-900">{{ stats?.resolved_this_month || 0 }}</dd>
                </dl>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-5 py-3">
            <span class="text-sm text-gray-500">{{ stats?.resolution_rate || 0 }}% resolution rate</span>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <BookOpenIcon class="h-6 w-6 text-blue-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Knowledge Articles</dt>
                  <dd class="text-lg font-medium text-gray-900">{{ stats?.knowledge_articles || 0 }}</dd>
                </dl>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-5 py-3">
            <router-link to="/portal/knowledge" class="text-sm font-medium text-deep-sea-600 hover:text-deep-sea-900">
              Browse KB
            </router-link>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <ClockIcon class="h-6 w-6 text-yellow-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Avg. Resolution Time</dt>
                  <dd class="text-lg font-medium text-gray-900">{{ stats?.avg_resolution_time || '0h' }}</dd>
                </dl>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-5 py-3">
            <span class="text-sm text-gray-500">Last 30 days</span>
          </div>
        </div>
      </div>

      <!-- Recent Tickets -->
      <div class="bg-white shadow overflow-hidden sm:rounded-md mb-8">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
          <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Tickets</h3>
        </div>
        <ul class="divide-y divide-gray-200">
          <li v-if="isLoadingTickets" class="px-4 py-4 sm:px-6">
            <div class="animate-pulse flex space-x-4">
              <div class="flex-1 space-y-2 py-1">
                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
              </div>
            </div>
          </li>
          <li v-else-if="!recentTickets?.length" class="px-4 py-4 sm:px-6 text-center text-gray-500">
            No tickets found
          </li>
          <li v-else v-for="ticket in recentTickets" :key="ticket.id" class="px-4 py-4 sm:px-6">
            <router-link :to="`/portal/tickets/${ticket.id}`" class="block hover:bg-gray-50">
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-deep-sea-600 truncate">
                      #{{ ticket.id }} - {{ ticket.title }}
                    </p>
                    <div class="ml-2 flex-shrink-0 flex">
                      <p :class="[
                        'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                        getStatusColor(ticket.status)
                      ]">
                        {{ ticket.status }}
                      </p>
                    </div>
                  </div>
                  <div class="mt-2 sm:flex sm:justify-between">
                    <div class="sm:flex">
                      <p class="flex items-center text-sm text-gray-500">
                        {{ formatDate(ticket.created_at) }}
                      </p>
                    </div>
                    <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                      <p :class="[
                        'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                        getPriorityColor(ticket.priority)
                      ]">
                        {{ ticket.priority }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </router-link>
          </li>
        </ul>
      </div>

      <!-- Knowledge Base Search -->
      <div class="bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
            Search Knowledge Base
          </h3>
          <form @submit.prevent="searchKnowledge" class="mt-5 sm:flex sm:items-center">
            <div class="w-full sm:max-w-xs">
              <input
                v-model="searchQuery"
                type="text"
                class="shadow-sm focus:ring-deep-sea-500 focus:border-deep-sea-500 block w-full sm:text-sm border-gray-300 rounded-md"
                placeholder="Search for solutions..."
              />
            </div>
            <button
              type="submit"
              class="mt-3 w-full inline-flex items-center justify-center px-4 py-2 border border-transparent shadow-sm font-medium rounded-md text-white bg-deep-sea-600 hover:bg-deep-sea-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-deep-sea-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Search
            </button>
          </form>
          
          <!-- Popular Articles -->
          <div v-if="popularArticles?.length" class="mt-6">
            <h4 class="text-sm font-medium text-gray-900 mb-3">Popular Articles</h4>
            <ul class="space-y-2">
              <li v-for="article in popularArticles" :key="article.id">
                <a
                  :href="`/portal/knowledge/${article.id}`"
                  class="text-sm text-deep-sea-600 hover:text-deep-sea-900"
                >
                  {{ article.title }}
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </main>

    <!-- New Ticket Modal -->
    <CreateTicketModal
      v-if="showNewTicketModal"
      :open="showNewTicketModal"
      @close="showNewTicketModal = false"
      @created="handleTicketCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import {
  PlusIcon,
  ChevronDownIcon,
  TicketIcon,
  CheckCircleIcon,
  BookOpenIcon,
  ClockIcon
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import { formatDate } from '@/utils/date'
import CreateTicketModal from './CreateTicketModal.vue'

const router = useRouter()
const authStore = useAuthStore()

const showUserMenu = ref(false)
const showNewTicketModal = ref(false)
const searchQuery = ref('')

const currentUser = computed(() => authStore.user)

// Fetch portal stats
const { data: stats } = useQuery({
  queryKey: ['portal-stats'],
  queryFn: async () => {
    const response = await api.get('/api/v1/portal/stats')
    return response.data.data
  }
})

// Fetch recent tickets
const { data: recentTickets, isLoading: isLoadingTickets, refetch: refetchTickets } = useQuery({
  queryKey: ['portal-recent-tickets'],
  queryFn: async () => {
    const response = await api.get('/api/v1/portal/tickets', {
      params: { limit: 5, sort: 'created_at', direction: 'desc' }
    })
    return response.data.data
  }
})

// Fetch popular knowledge articles
const { data: popularArticles } = useQuery({
  queryKey: ['popular-articles'],
  queryFn: async () => {
    const response = await api.get('/api/v1/portal/knowledge/popular', {
      params: { limit: 5 }
    })
    return response.data.data
  }
})

const searchKnowledge = () => {
  if (searchQuery.value.trim()) {
    router.push(`/portal/knowledge?search=${encodeURIComponent(searchQuery.value)}`)
  }
}

const handleTicketCreated = () => {
  refetchTickets()
  showNewTicketModal.value = false
}

const logout = async () => {
  await authStore.logout()
  router.push('/portal/login')
}

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    open: 'bg-red-100 text-red-800',
    'in-progress': 'bg-yellow-100 text-yellow-800',
    resolved: 'bg-green-100 text-green-800',
    closed: 'bg-gray-100 text-gray-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const getPriorityColor = (priority: string) => {
  const colors: Record<string, string> = {
    critical: 'bg-red-100 text-red-800',
    high: 'bg-orange-100 text-orange-800',
    medium: 'bg-yellow-100 text-yellow-800',
    low: 'bg-green-100 text-green-800'
  }
  return colors[priority] || 'bg-gray-100 text-gray-800'
}
</script>