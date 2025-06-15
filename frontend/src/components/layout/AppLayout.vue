<template>
  <div class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar -->
    <aside
      :class="[
        'bg-deep-sea-500 text-white w-64 min-h-screen flex flex-col transition-all duration-300',
        sidebarCollapsed ? 'w-16' : 'w-64'
      ]"
    >
      <!-- Logo -->
      <div class="p-4 border-b border-deep-sea-600">
        <div class="flex items-center justify-between">
          <h1 :class="['font-brain font-bold transition-all', sidebarCollapsed ? 'text-lg' : 'text-xl']">
            {{ sidebarCollapsed ? 'D360' : 'Defender360' }}
          </h1>
          <button
            @click="toggleSidebar"
            class="text-arctic-breeze-300 hover:text-white transition-colors"
          >
            <ChevronLeftIcon v-if="!sidebarCollapsed" class="h-5 w-5" />
            <ChevronRightIcon v-else class="h-5 w-5" />
          </button>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-2 py-4 space-y-1">
        <router-link
          v-for="item in navigation"
          :key="item.name"
          :to="item.path"
          :class="[
            'flex items-center px-3 py-2 rounded-lg transition-colors',
            isActive(item.path)
              ? 'bg-deep-sea-600 text-white'
              : 'text-arctic-breeze-300 hover:bg-deep-sea-600 hover:text-white'
          ]"
        >
          <component :is="item.icon" class="h-5 w-5" />
          <span v-if="!sidebarCollapsed" class="ml-3">{{ item.name }}</span>
        </router-link>
      </nav>

      <!-- User Menu -->
      <div class="p-4 border-t border-deep-sea-600">
        <div class="flex items-center">
          <div class="w-8 h-8 bg-arctic-breeze-500 rounded-full flex items-center justify-center">
            <span class="text-deep-sea-500 text-sm font-medium">
              {{ userInitials }}
            </span>
          </div>
          <div v-if="!sidebarCollapsed" class="ml-3 flex-1">
            <p class="text-sm font-medium">{{ currentUser?.name }}</p>
            <p class="text-xs text-arctic-breeze-300">{{ currentUser?.email }}</p>
          </div>
          <button
            @click="logout"
            class="ml-2 text-arctic-breeze-300 hover:text-white transition-colors"
          >
            <ArrowRightOnRectangleIcon class="h-5 w-5" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col">
      <!-- Header -->
      <header class="bg-white shadow-sm">
        <div class="px-6 py-4">
          <div class="flex items-center justify-between">
            <h2 class="text-2xl font-brain font-semibold text-deep-sea-500">
              {{ pageTitle }}
            </h2>
            
            <!-- Quick Actions -->
            <div class="flex items-center space-x-4">
              <!-- Notifications -->
              <NotificationBell />
              
              <!-- Create New -->
              <button
                @click="showCreateModal = true"
                class="btn-primary"
              >
                <PlusIcon class="h-5 w-5 mr-2" />
                New Incident
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 p-6">
        <slot />
      </main>
    </div>

    <!-- Create Incident Modal -->
    <CreateIncidentModal
      v-if="showCreateModal"
      @close="showCreateModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  HomeIcon,
  TicketIcon,
  ClipboardDocumentListIcon,
  UsersIcon,
  Cog6ToothIcon,
  ChartBarIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ArrowRightOnRectangleIcon,
  BellIcon,
  PlusIcon,
  ServerIcon,
  ArrowPathIcon,
  ExclamationTriangleIcon,
  BookOpenIcon
} from '@heroicons/vue/24/outline'
import CreateIncidentModal from '@/components/incidents/CreateIncidentModal.vue'
import NotificationBell from '@/components/NotificationBell.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const sidebarCollapsed = ref(false)
const showCreateModal = ref(false)

const currentUser = computed(() => authStore.user)
const userInitials = computed(() => {
  if (!currentUser.value) return ''
  return currentUser.value.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
})

const navigation = [
  { name: 'Dashboard', path: '/dashboard', icon: HomeIcon },
  { name: 'Incidents', path: '/incidents', icon: TicketIcon },
  { name: 'Changes', path: '/changes', icon: ArrowPathIcon },
  { name: 'Problems', path: '/problems', icon: ExclamationTriangleIcon },
  { name: 'Service Requests', path: '/service-requests', icon: ClipboardDocumentListIcon },
  { name: 'CMDB', path: '/cmdb', icon: ServerIcon },
  { name: 'Knowledge Base', path: '/knowledge', icon: BookOpenIcon },
  { name: 'Reports', path: '/reports', icon: ChartBarIcon },
  { name: 'Users', path: '/users', icon: UsersIcon },
  { name: 'Settings', path: '/settings', icon: Cog6ToothIcon }
]

const pageTitle = computed(() => {
  const currentRoute = navigation.find(item => route.path.startsWith(item.path))
  return currentRoute?.name || 'Defender360'
})

const toggleSidebar = () => {
  sidebarCollapsed.value = !sidebarCollapsed.value
}

const isActive = (path: string) => {
  return route.path.startsWith(path)
}

const logout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>