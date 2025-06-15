# 🚀 Fase 3 - Frontend Vue.js + Dashboard + Integrações

## Contexto
Excelente trabalho na Fase 2! Agora temos:
- ✅ Backend Laravel com multi-tenancy funcionando
- ✅ Auth0 configurado
- ✅ API de Incidents completa com DDD
- ✅ Dashboard metrics no backend
- ✅ Testes completos

Agora vamos criar o frontend Vue.js 3 com TypeScript, dashboard visual e começar as integrações.

## 🎨 Parte 1: Setup Frontend Vue.js 3

### 1.1 Configuração Base do Frontend

#### Arquivo: `frontend/package.json`
```json
{
  "name": "defender360-itsm-frontend",
  "version": "0.1.0",
  "private": true,
  "scripts": {
    "dev": "vite",
    "build": "vue-tsc && vite build",
    "preview": "vite preview",
    "test:unit": "vitest",
    "test:e2e": "cypress run",
    "lint": "eslint . --ext .vue,.js,.jsx,.cjs,.mjs,.ts,.tsx,.cts,.mts --fix --ignore-path .gitignore",
    "type-check": "vue-tsc --noEmit",
    "format": "prettier --write src/"
  },
  "dependencies": {
    "vue": "^3.4.0",
    "vue-router": "^4.2.5",
    "pinia": "^2.1.7",
    "@vueuse/core": "^10.7.0",
    "axios": "^1.6.2",
    "@tanstack/vue-query": "^5.17.0",
    "@auth0/auth0-vue": "^2.3.3",
    "chart.js": "^4.4.1",
    "vue-chartjs": "^5.3.0",
    "date-fns": "^3.0.6",
    "@headlessui/vue": "^1.7.16",
    "@heroicons/vue": "^2.1.1",
    "vue-toastification": "^2.0.0-rc.5",
    "@vuelidate/core": "^2.0.3",
    "@vuelidate/validators": "^2.0.4"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.0.2",
    "@vue/test-utils": "^2.4.3",
    "vite": "^5.0.10",
    "typescript": "^5.3.3",
    "vue-tsc": "^1.8.27",
    "@types/node": "^20.10.5",
    "tailwindcss": "^3.4.0",
    "autoprefixer": "^10.4.16",
    "postcss": "^8.4.32",
    "@typescript-eslint/eslint-plugin": "^6.15.0",
    "@typescript-eslint/parser": "^6.15.0",
    "eslint": "^8.56.0",
    "eslint-plugin-vue": "^9.19.2",
    "prettier": "^3.1.1",
    "vitest": "^1.1.0",
    "@testing-library/vue": "^8.0.1",
    "cypress": "^13.6.2",
    "msw": "^2.0.11"
  }
}
```

#### Arquivo: `frontend/src/main.ts`
```typescript
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { VueQueryPlugin } from '@tanstack/vue-query'
import { createAuth0 } from '@auth0/auth0-vue'
import Toast from 'vue-toastification'
import App from './App.vue'
import router from './router'
import './styles/index.css'
import 'vue-toastification/dist/index.css'

// Auth0 configuration
const auth0 = createAuth0({
  domain: import.meta.env.VITE_AUTH0_DOMAIN,
  clientId: import.meta.env.VITE_AUTH0_CLIENT_ID,
  authorizationParams: {
    redirect_uri: window.location.origin,
    audience: import.meta.env.VITE_AUTH0_AUDIENCE,
    scope: 'openid profile email tenant_id'
  },
  cacheLocation: 'localstorage',
  useRefreshTokens: true
})

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(auth0)
app.use(VueQueryPlugin)
app.use(Toast, {
  position: 'top-right',
  timeout: 5000,
  closeOnClick: true,
  pauseOnFocusLoss: true,
  pauseOnHover: true
})

app.mount('#app')
```

### 1.2 Configuração Tailwind com Design System Defender360

#### Arquivo: `frontend/tailwind.config.js`
```javascript
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Defender360 colors
        'deep-sea': {
          DEFAULT: '#043659',
          50: '#E6EEF4',
          100: '#CCDDE9',
          200: '#99BBD3',
          300: '#6699BD',
          400: '#3377A7',
          500: '#043659',
          600: '#032B47',
          700: '#022035',
          800: '#011523',
          900: '#000A12'
        },
        'tech-horizon': {
          DEFAULT: '#0070AF',
          50: '#E6F2FA',
          100: '#CCE5F5',
          200: '#99CBEB',
          300: '#66B1E1',
          400: '#3397D7',
          500: '#0070AF',
          600: '#005A8C',
          700: '#004369',
          800: '#002D46',
          900: '#001623'
        },
        'arctic-breeze': {
          DEFAULT: '#60B4CD',
          500: '#60B4CD',
          400: '#7FC0D6',
          300: '#9ECDDF'
        },
        'vital-energy': {
          DEFAULT: '#009F8D',
          500: '#009F8D',
          600: '#007F71'
        },
        // Priority colors
        'priority': {
          critical: '#D32F2F',
          high: '#F57C00',
          medium: '#FBC02D',
          low: '#388E3C'
        }
      },
      fontFamily: {
        'brain': ['Brain Wants', 'Helvetica', 'Arial', 'sans-serif'],
        'tipografix': ['Tipografix', 'Arial', 'sans-serif'],
        'playfair': ['PlayFair Display', 'Georgia', 'serif']
      },
      boxShadow: {
        'defender-sm': '0 2px 4px rgba(4, 54, 89, 0.1)',
        'defender-md': '0 4px 8px rgba(4, 54, 89, 0.15)',
        'defender-lg': '0 8px 16px rgba(4, 54, 89, 0.2)'
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography')
  ],
}
```

### 1.3 Auth Store com Pinia

#### Arquivo: `frontend/src/stores/auth.ts`
```typescript
import { defineStore } from 'pinia'
import { useAuth0 } from '@auth0/auth0-vue'
import type { User, Tenant } from '@/types'
import { api } from '@/services/api'

interface AuthState {
  user: User | null
  tenant: Tenant | null
  isLoading: boolean
  error: string | null
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    tenant: null,
    isLoading: false,
    error: null
  }),

  getters: {
    isAuthenticated: (state) => !!state.user,
    userRole: (state) => state.user?.role || 'user',
    isAdmin: (state) => state.user?.role === 'admin',
    isAgent: (state) => ['admin', 'agent'].includes(state.user?.role || ''),
    tenantId: (state) => state.tenant?.id
  },

  actions: {
    async initializeAuth() {
      const auth0 = useAuth0()
      
      if (auth0.isAuthenticated.value) {
        this.isLoading = true
        try {
          const token = await auth0.getAccessTokenSilently()
          api.defaults.headers.common['Authorization'] = `Bearer ${token}`
          
          // Get user data from API
          const response = await api.get('/api/v1/me')
          this.user = response.data.user
          this.tenant = response.data.tenant
        } catch (error) {
          this.error = 'Failed to initialize authentication'
          console.error('Auth initialization error:', error)
        } finally {
          this.isLoading = false
        }
      }
    },

    async logout() {
      const auth0 = useAuth0()
      this.user = null
      this.tenant = null
      delete api.defaults.headers.common['Authorization']
      await auth0.logout({ logoutParams: { returnTo: window.location.origin } })
    },

    setUser(user: User) {
      this.user = user
    },

    setTenant(tenant: Tenant) {
      this.tenant = tenant
    }
  }
})
```

## 🎫 Parte 2: Módulo de Incidents no Frontend

### 2.1 Types e Interfaces

#### Arquivo: `frontend/src/types/index.ts`
```typescript
// Base types
export interface User {
  id: string
  name: string
  email: string
  role: 'admin' | 'agent' | 'user'
  avatar?: string
  tenant_id: string
}

export interface Tenant {
  id: string
  name: string
  subdomain: string
  settings: Record<string, any>
}

// Incident types
export type IncidentPriority = 'critical' | 'high' | 'medium' | 'low'
export type IncidentStatus = 'new' | 'assigned' | 'in_progress' | 'pending' | 'resolved' | 'closed'
export type IncidentImpact = 'critical' | 'high' | 'medium' | 'low'
export type IncidentUrgency = 'critical' | 'high' | 'medium' | 'low'

export interface Incident {
  id: string
  number: string
  title: string
  description: string
  priority: IncidentPriority
  impact: IncidentImpact
  urgency: IncidentUrgency
  status: IncidentStatus
  category_id?: string
  assigned_to?: string
  assigned_user?: User
  created_by: string
  created_by_user?: User
  sla_response_target: string
  sla_resolution_target: string
  resolved_at?: string
  closed_at?: string
  created_at: string
  updated_at: string
  comments_count?: number
  attachments_count?: number
  is_overdue?: boolean
}

export interface IncidentComment {
  id: string
  incident_id: string
  user_id: string
  user?: User
  body: string
  is_internal: boolean
  created_at: string
}

export interface CreateIncidentData {
  title: string
  description: string
  priority: IncidentPriority
  impact: IncidentImpact
  urgency: IncidentUrgency
  category_id?: string
  assigned_to?: string
}

export interface DashboardMetrics {
  total_incidents: number
  open_incidents: number
  overdue_incidents: number
  avg_resolution_time: number
  sla_compliance: number
  incidents_today: number
}
```

### 2.2 API Service

#### Arquivo: `frontend/src/services/api.ts`
```typescript
import axios from 'axios'
import { useAuth0 } from '@auth0/auth0-vue'
import { useToast } from 'vue-toastification'

const toast = useToast()

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Request interceptor
api.interceptors.request.use(
  async (config) => {
    const { getAccessTokenSilently, isAuthenticated } = useAuth0()
    
    if (isAuthenticated.value) {
      try {
        const token = await getAccessTokenSilently()
        config.headers.Authorization = `Bearer ${token}`
      } catch (error) {
        console.error('Error getting access token:', error)
      }
    }
    
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response) {
      switch (error.response.status) {
        case 401:
          toast.error('Authentication required. Please login again.')
          // Redirect to login
          break
        case 403:
          toast.error('You do not have permission to perform this action.')
          break
        case 422:
          const errors = error.response.data.errors
          if (errors) {
            Object.values(errors).flat().forEach((msg: any) => {
              toast.error(msg)
            })
          }
          break
        case 500:
          toast.error('Server error. Please try again later.')
          break
      }
    } else if (error.request) {
      toast.error('Network error. Please check your connection.')
    }
    
    return Promise.reject(error)
  }
)
```

### 2.3 Incident Store

#### Arquivo: `frontend/src/stores/incident.ts`
```typescript
import { defineStore } from 'pinia'
import { api } from '@/services/api'
import type { Incident, CreateIncidentData, IncidentComment } from '@/types'

interface IncidentState {
  incidents: Incident[]
  currentIncident: Incident | null
  isLoading: boolean
  error: string | null
  filters: {
    status?: string
    priority?: string
    assigned_to?: string
    search?: string
  }
  pagination: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export const useIncidentStore = defineStore('incident', {
  state: (): IncidentState => ({
    incidents: [],
    currentIncident: null,
    isLoading: false,
    error: null,
    filters: {},
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0
    }
  }),

  getters: {
    openIncidents: (state) => state.incidents.filter(i => ['new', 'assigned', 'in_progress'].includes(i.status)),
    overdueIncidents: (state) => state.incidents.filter(i => i.is_overdue),
    myIncidents: (state) => {
      const userId = useAuthStore().user?.id
      return state.incidents.filter(i => i.assigned_to === userId)
    }
  },

  actions: {
    async fetchIncidents(page = 1) {
      this.isLoading = true
      try {
        const params = {
          page,
          per_page: this.pagination.per_page,
          ...this.filters
        }
        
        const response = await api.get('/api/v1/incidents', { params })
        this.incidents = response.data.data
        this.pagination = response.data.meta
      } catch (error) {
        this.error = 'Failed to fetch incidents'
        console.error('Error fetching incidents:', error)
      } finally {
        this.isLoading = false
      }
    },

    async fetchIncident(id: string) {
      this.isLoading = true
      try {
        const response = await api.get(`/api/v1/incidents/${id}`)
        this.currentIncident = response.data.data
        return response.data.data
      } catch (error) {
        this.error = 'Failed to fetch incident'
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async createIncident(data: CreateIncidentData) {
      this.isLoading = true
      try {
        const response = await api.post('/api/v1/incidents', data)
        const newIncident = response.data.data
        this.incidents.unshift(newIncident)
        return newIncident
      } catch (error) {
        this.error = 'Failed to create incident'
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async updateIncident(id: string, data: Partial<CreateIncidentData>) {
      try {
        const response = await api.put(`/api/v1/incidents/${id}`, data)
        const updated = response.data.data
        
        const index = this.incidents.findIndex(i => i.id === id)
        if (index !== -1) {
          this.incidents[index] = updated
        }
        
        if (this.currentIncident?.id === id) {
          this.currentIncident = updated
        }
        
        return updated
      } catch (error) {
        this.error = 'Failed to update incident'
        throw error
      }
    },

    async addComment(incidentId: string, body: string, isInternal = false) {
      try {
        const response = await api.post(`/api/v1/incidents/${incidentId}/comments`, {
          body,
          is_internal: isInternal
        })
        
        // Refresh current incident to get updated comments
        if (this.currentIncident?.id === incidentId) {
          await this.fetchIncident(incidentId)
        }
        
        return response.data.data
      } catch (error) {
        throw error
      }
    },

    async resolveIncident(id: string, resolutionNotes: string) {
      try {
        const response = await api.post(`/api/v1/incidents/${id}/resolve`, {
          resolution_notes: resolutionNotes
        })
        
        const resolved = response.data.data
        const index = this.incidents.findIndex(i => i.id === id)
        if (index !== -1) {
          this.incidents[index] = resolved
        }
        
        if (this.currentIncident?.id === id) {
          this.currentIncident = resolved
        }
        
        return resolved
      } catch (error) {
        throw error
      }
    },

    setFilters(filters: any) {
      this.filters = filters
      this.fetchIncidents(1)
    },

    clearFilters() {
      this.filters = {}
      this.fetchIncidents(1)
    }
  }
})
```

## 📊 Parte 3: Dashboard Visual

### 3.1 Dashboard View

#### Arquivo: `frontend/src/views/DashboardView.vue`
```vue
<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-deep-sea-500 shadow-defender-lg">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <h1 class="text-3xl font-brain font-bold text-white">
            Defender360 Dashboard
          </h1>
          <div class="flex items-center space-x-4">
            <span class="text-white text-sm">{{ currentUser?.name }}</span>
            <button
              @click="logout"
              class="text-arctic-breeze-300 hover:text-white transition-colors"
            >
              <LogoutIcon class="h-5 w-5" />
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Metrics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <MetricCard
          title="Total Incidents"
          :value="metrics.total_incidents"
          :trend="{ value: 12, direction: 'up' }"
          icon="ticket"
          color="tech-horizon"
        />
        <MetricCard
          title="Open Incidents"
          :value="metrics.open_incidents"
          :trend="{ value: 5, direction: 'down' }"
          icon="clock"
          color="vital-energy"
        />
        <MetricCard
          title="Overdue"
          :value="metrics.overdue_incidents"
          :trend="{ value: 2, direction: 'up' }"
          icon="exclamation"
          color="priority-high"
        />
        <MetricCard
          title="SLA Compliance"
          :value="`${metrics.sla_compliance}%`"
          :trend="{ value: 3, direction: 'up' }"
          icon="chart"
          color="arctic-breeze"
        />
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-defender-md p-6">
          <h2 class="text-lg font-brain font-semibold text-deep-sea-500 mb-4">
            Incidents by Priority
          </h2>
          <PriorityChart :data="priorityData" />
        </div>
        
        <div class="bg-white rounded-lg shadow-defender-md p-6">
          <h2 class="text-lg font-brain font-semibold text-deep-sea-500 mb-4">
            SLA Performance Trend
          </h2>
          <SLATrendChart :data="slaTrendData" />
        </div>
      </div>

      <!-- Recent Incidents Table -->
      <div class="bg-white rounded-lg shadow-defender-md">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex justify-between items-center">
            <h2 class="text-lg font-brain font-semibold text-deep-sea-500">
              Recent Incidents
            </h2>
            <router-link
              to="/incidents"
              class="text-tech-horizon-500 hover:text-tech-horizon-600 text-sm font-medium"
            >
              View All →
            </router-link>
          </div>
        </div>
        <RecentIncidentsTable :incidents="recentIncidents" />
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth0 } from '@auth0/auth0-vue'
import { useAuthStore } from '@/stores/auth'
import { useQuery } from '@tanstack/vue-query'
import { api } from '@/services/api'
import MetricCard from '@/components/dashboard/MetricCard.vue'
import PriorityChart from '@/components/dashboard/PriorityChart.vue'
import SLATrendChart from '@/components/dashboard/SLATrendChart.vue'
import RecentIncidentsTable from '@/components/dashboard/RecentIncidentsTable.vue'
import { ArrowRightOnRectangleIcon as LogoutIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const { logout: auth0Logout } = useAuth0()
const authStore = useAuthStore()

const currentUser = computed(() => authStore.user)

// Fetch dashboard metrics
const { data: metrics = ref({
  total_incidents: 0,
  open_incidents: 0,
  overdue_incidents: 0,
  sla_compliance: 0,
  incidents_today: 0,
  avg_resolution_time: 0
})} = useQuery({
  queryKey: ['dashboard-metrics'],
  queryFn: async () => {
    const response = await api.get('/api/v1/dashboard/metrics')
    return response.data
  },
  refetchInterval: 30000 // Refresh every 30 seconds
})

// Fetch recent incidents
const { data: recentIncidents = ref([]) } = useQuery({
  queryKey: ['recent-incidents'],
  queryFn: async () => {
    const response = await api.get('/api/v1/dashboard/recent-incidents')
    return response.data
  }
})

// Mock data for charts (replace with real API calls)
const priorityData = ref({
  labels: ['Critical', 'High', 'Medium', 'Low'],
  datasets: [{
    data: [5, 15, 30, 25],
    backgroundColor: ['#D32F2F', '#F57C00', '#FBC02D', '#388E3C']
  }]
})

const slaTrendData = ref({
  labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
  datasets: [{
    label: 'SLA Compliance %',
    data: [95, 92, 94, 96, 93, 97, 95],
    borderColor: '#0070AF',
    backgroundColor: 'rgba(0, 112, 175, 0.1)'
  }]
})

async function logout() {
  await authStore.logout()
}
</script>
```

### 3.2 Incident List View

#### Arquivo: `frontend/src/views/incidents/IncidentListView.vue`
```vue
<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <div class="bg-white shadow-defender-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="py-6">
          <div class="flex justify-between items-center">
            <h1 class="text-2xl font-brain font-bold text-deep-sea-500">
              Incidents
            </h1>
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
    </div>

    <!-- Filters -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
      <IncidentFilters 
        v-model:filters="filters"
        @update:filters="handleFilterChange"
      />
    </div>

    <!-- Incidents List -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white shadow-defender-md rounded-lg">
        <!-- Loading State -->
        <div v-if="incidentStore.isLoading" class="p-8 text-center">
          <LoadingSpinner />
        </div>

        <!-- Empty State -->
        <div v-else-if="incidents.length === 0" class="p-8 text-center">
          <ExclamationCircleIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">No incidents found</h3>
          <p class="mt-1 text-sm text-gray-500">
            Get started by creating a new incident.
          </p>
        </div>

        <!-- Incidents Grid -->
        <div v-else class="grid gap-4 p-6">
          <IncidentCard
            v-for="incident in incidents"
            :key="incident.id"
            :incident="incident"
            @click="goToIncident(incident.id)"
            @assign="handleAssign"
            @resolve="handleResolve"
          />
        </div>

        <!-- Pagination -->
        <div v-if="incidents.length > 0" class="px-6 py-4 border-t">
          <Pagination
            :current-page="incidentStore.pagination.current_page"
            :last-page="incidentStore.pagination.last_page"
            :total="incidentStore.pagination.total"
            @change="handlePageChange"
          />
        </div>
      </div>
    </div>

    <!-- Create Incident Modal -->
    <CreateIncidentModal
      v-model:open="showCreateModal"
      @created="handleIncidentCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useIncidentStore } from '@/stores/incident'
import IncidentCard from '@/components/incidents/IncidentCard.vue'
import IncidentFilters from '@/components/incidents/IncidentFilters.vue'
import CreateIncidentModal from '@/components/incidents/CreateIncidentModal.vue'
import Pagination from '@/components/common/Pagination.vue'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import { PlusIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'

const router = useRouter()
const incidentStore = useIncidentStore()
const toast = useToast()

const showCreateModal = ref(false)
const filters = ref({
  status: '',
  priority: '',
  assigned_to: '',
  search: ''
})

const incidents = computed(() => incidentStore.incidents)

onMounted(() => {
  incidentStore.fetchIncidents()
})

function handleFilterChange() {
  incidentStore.setFilters(filters.value)
}

function handlePageChange(page: number) {
  incidentStore.fetchIncidents(page)
}

function goToIncident(id: string) {
  router.push(`/incidents/${id}`)
}

async function handleAssign(incident: any) {
  try {
    await incidentStore.updateIncident(incident.id, {
      assigned_to: useAuthStore().user?.id
    })
    toast.success('Incident assigned to you')
  } catch (error) {
    toast.error('Failed to assign incident')
  }
}

async function handleResolve(incident: any) {
  // Open resolve modal or handle resolution
  router.push(`/incidents/${incident.id}?action=resolve`)
}

function handleIncidentCreated(incident: any) {
  showCreateModal.value = false
  toast.success('Incident created successfully')
  router.push(`/incidents/${incident.id}`)
}
</script>
```

## 🎨 Parte 4: Componentes Reutilizáveis

### 4.1 Incident Card Component

#### Arquivo: `frontend/src/components/incidents/IncidentCard.vue`
```vue
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
.btn-primary-sm {
  @apply px-3 py-1 bg-tech-horizon-500 text-white text-sm font-medium rounded-md hover:bg-tech-horizon-600 transition-colors;
}

.btn-secondary-sm {
  @apply px-3 py-1 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 transition-colors;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
```

### 4.2 Create Incident Modal

#### Arquivo: `frontend/src/components/incidents/CreateIncidentModal.vue`
```vue
<template>
  <TransitionRoot appear :show="open" as="template">
    <Dialog as="div" @close="close" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black bg-opacity-25" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
              <DialogTitle
                as="h3"
                class="text-lg font-brain font-bold leading-6 text-deep-sea-500"
              >
                Create New Incident
              </DialogTitle>

              <form @submit.prevent="handleSubmit" class="mt-6">
                <div class="space-y-4">
                  <!-- Title -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700">
                      Title <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="form.title"
                      type="text"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-tech-horizon-500 focus:ring-tech-horizon-500"
                      placeholder="Brief description of the issue"
                    />
                  </div>

                  <!-- Description -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700">
                      Description <span class="text-red-500">*</span>
                    </label>
                    <textarea
                      v-model="form.description"
                      rows="4"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-tech-horizon-500 focus:ring-tech-horizon-500"
                      placeholder="Detailed description of the issue"
                    />
                  </div>

                  <!-- Priority Matrix -->
                  <div class="grid grid-cols-3 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700">
                        Impact <span class="text-red-500">*</span>
                      </label>
                      <select
                        v-model="form.impact"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-tech-horizon-500 focus:ring-tech-horizon-500"
                      >
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                      </select>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700">
                        Urgency <span class="text-red-500">*</span>
                      </label>
                      <select
                        v-model="form.urgency"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-tech-horizon-500 focus:ring-tech-horizon-500"
                      >
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                      </select>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-gray-700">
                        Priority (Auto)
                      </label>
                      <div class="mt-1 px-3 py-2 bg-gray-100 rounded-md">
                        <PriorityBadge :priority="calculatedPriority" />
                      </div>
                    </div>
                  </div>

                  <!-- Category (optional) -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700">
                      Category
                    </label>
                    <select
                      v-model="form.category_id"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-tech-horizon-500 focus:ring-tech-horizon-500"
                    >
                      <option value="">Select category...</option>
                      <option value="hardware">Hardware</option>
                      <option value="software">Software</option>
                      <option value="network">Network</option>
                      <option value="security">Security</option>
                    </select>
                  </div>

                  <!-- Assign To (optional) -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700">
                      Assign To
                    </label>
                    <UserSelect
                      v-model="form.assigned_to"
                      :users="availableUsers"
                      placeholder="Select user..."
                    />
                  </div>
                </div>

                <!-- Actions -->
                <div class="mt-6 flex justify-end space-x-3">
                  <button
                    type="button"
                    @click="close"
                    class="btn-secondary"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="isSubmitting"
                    class="btn-primary"
                  >
                    <span v-if="isSubmitting">Creating...</span>
                    <span v-else>Create Incident</span>
                  </button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { useIncidentStore } from '@/stores/incident'
import { useToast } from 'vue-toastification'
import PriorityBadge from '@/components/common/PriorityBadge.vue'
import UserSelect from '@/components/common/UserSelect.vue'
import type { CreateIncidentData, IncidentPriority } from '@/types'

interface Props {
  open: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  created: [incident: any]
}>()

const incidentStore = useIncidentStore()
const toast = useToast()

const isSubmitting = ref(false)
const form = ref<CreateIncidentData>({
  title: '',
  description: '',
  priority: 'medium',
  impact: 'medium',
  urgency: 'medium',
  category_id: '',
  assigned_to: ''
})

// Mock available users - replace with real API call
const availableUsers = ref([
  { id: '1', name: 'John Doe' },
  { id: '2', name: 'Jane Smith' }
])

const calculatedPriority = computed((): IncidentPriority => {
  // Priority matrix calculation
  const matrix: Record<string, Record<string, IncidentPriority>> = {
    critical: { critical: 'critical', high: 'critical', medium: 'high', low: 'medium' },
    high: { critical: 'critical', high: 'high', medium: 'medium', low: 'low' },
    medium: { critical: 'high', high: 'medium', medium: 'medium', low: 'low' },
    low: { critical: 'medium', high: 'low', medium: 'low', low: 'low' }
  }
  
  const priority = matrix[form.value.impact]?.[form.value.urgency] || 'medium'
  form.value.priority = priority
  return priority
})

function close() {
  emit('update:open', false)
  resetForm()
}

function resetForm() {
  form.value = {
    title: '',
    description: '',
    priority: 'medium',
    impact: 'medium',
    urgency: 'medium',
    category_id: '',
    assigned_to: ''
  }
}

async function handleSubmit() {
  isSubmitting.value = true
  
  try {
    const incident = await incidentStore.createIncident(form.value)
    emit('created', incident)
    close()
  } catch (error) {
    console.error('Failed to create incident:', error)
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped>
.btn-primary {
  @apply px-4 py-2 bg-tech-horizon-500 text-white font-medium rounded-md hover:bg-tech-horizon-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply px-4 py-2 bg-white border border-gray-300 text-gray-700 font-medium rounded-md hover:bg-gray-50 transition-colors;
}
</style>
```

## 🔧 Parte 5: Configuração e Build

### 5.1 Vite Configuration

#### Arquivo: `frontend/vite.config.ts`
```typescript
import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    }
  },
  server: {
    host: true,
    port: 3000,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false
      }
    }
  },
  build: {
    outDir: 'dist',
    sourcemap: true,
    rollupOptions: {
      output: {
        manualChunks: {
          'vue-vendor': ['vue', 'vue-router', 'pinia'],
          'ui-vendor': ['@headlessui/vue', '@heroicons/vue'],
          'chart-vendor': ['chart.js', 'vue-chartjs'],
          'utils': ['axios', 'date-fns', '@vueuse/core']
        }
      }
    }
  }
})
```

### 5.2 Docker Setup for Frontend

#### Arquivo: `frontend/Dockerfile`
```dockerfile
# Build stage
FROM node:20-alpine as builder

WORKDIR /app

# Copy package files
COPY package*.json ./
RUN npm ci

# Copy source code
COPY . .

# Build application
RUN npm run build

# Production stage
FROM nginx:alpine

# Copy built application
COPY --from=builder /app/dist /usr/share/nginx/html

# Copy nginx configuration
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Expose port
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
```

#### Arquivo: `frontend/nginx.conf`
```nginx
server {
    listen 80;
    server_name localhost;
    root /usr/share/nginx/html;
    index index.html;

    # Vue Router support
    location / {
        try_files $uri $uri/ /index.html;
    }

    # API proxy
    location /api {
        proxy_pass http://backend:8000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';" always;

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 5.3 Update Docker Compose

Adicione o serviço frontend ao `docker-compose.yml`:

```yaml
  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile.dev
    container_name: itsm-frontend
    restart: unless-stopped
    volumes:
      - ./frontend:/app
      - /app/node_modules
    ports:
      - "3000:3000"
    environment:
      - VITE_API_URL=http://localhost:8000
      - VITE_AUTH0_DOMAIN=${AUTH0_DOMAIN}
      - VITE_AUTH0_CLIENT_ID=${AUTH0_CLIENT_ID}
      - VITE_AUTH0_AUDIENCE=${AUTH0_AUDIENCE}
    depends_on:
      - backend
    command: npm run dev
```

## ✅ Verificação e Comandos

### Comandos de Setup:
```bash
# 1. Install frontend dependencies
cd frontend
npm install

# 2. Configure environment
cp .env.example .env
# Edit .env with your Auth0 credentials

# 3. Start frontend development server
npm run dev

# Or with Docker
docker-compose up frontend
```

### Testes do Frontend:
```bash
# Unit tests
npm run test:unit

# E2E tests
npm run test:e2e

# Type checking
npm run type-check

# Linting
npm run lint
```

### Build para Produção:
```bash
# Build frontend
npm run build

# Preview production build
npm run preview

# Build Docker image
docker build -t itsm-frontend:latest .
```

## 🎯 Checklist da Fase 3

- [ ] Setup Vue.js 3 com TypeScript
- [ ] Configuração Auth0 no frontend
- [ ] Store management com Pinia
- [ ] API service com interceptors
- [ ] Dashboard com métricas visuais
- [ ] CRUD de Incidents completo
- [ ] Componentes reutilizáveis
- [ ] Design System Defender360
- [ ] Docker setup para frontend
- [ ] Testes unitários básicos

## 📈 Próximos Passos (Fase 4)

1. **WebSockets para Real-time**
   - Laravel Echo setup
   - Live notifications
   - Real-time dashboard updates

2. **Integração Claude AI**
   - Sugestões inteligentes
   - Auto-categorização
   - Análise de sentimento

3. **Módulos Adicionais**
   - Knowledge Base
   - Service Catalog
   - Reports & Analytics

4. **Mobile PWA**
   - Offline support
   - Push notifications
   - Mobile-optimized views

O frontend está agora pronto e integrado com o backend! Continue mantendo a qualidade enterprise em todos os aspectos do desenvolvimento.