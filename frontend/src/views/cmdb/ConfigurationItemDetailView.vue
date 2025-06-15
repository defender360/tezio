<template>
  <div class="py-6">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="min-w-0 flex-1">
          <div class="flex items-center">
            <button
              @click="router.back()"
              class="mr-4 text-gray-400 hover:text-gray-500"
            >
              <ArrowLeftIcon class="h-5 w-5" />
            </button>
            <div>
              <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                {{ configItem?.name }}
              </h2>
              <p class="mt-1 text-sm text-gray-500">
                {{ configItem?.asset_tag || configItem?.serial_number }}
              </p>
            </div>
          </div>
        </div>
        <div class="mt-4 flex md:ml-4 md:mt-0 space-x-3">
          <button
            @click="showEditModal = true"
            type="button"
            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
          >
            <PencilIcon class="-ml-0.5 mr-1.5 h-4 w-4" />
            Edit
          </button>
          <button
            @click="showRelationshipsModal = true"
            type="button"
            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
          >
            <LinkIcon class="-ml-0.5 mr-1.5 h-4 w-4" />
            Relationships
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-deep-sea-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="rounded-md bg-red-50 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <XCircleIcon class="h-5 w-5 text-red-400" aria-hidden="true" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Error loading configuration item</h3>
            <div class="mt-2 text-sm text-red-700">
              <p>{{ error.message }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Configuration Item Content -->
      <div v-else-if="configItem" class="space-y-6">
        <!-- Main Info Card -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
          <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              Configuration Item Information
            </h3>
          </div>
          <div class="border-t border-gray-200">
            <dl>
              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Type</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ formatType(configItem.type) }}
                </dd>
              </div>
              <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Status</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  <span :class="[getStatusColor(configItem.status), 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium']">
                    {{ formatStatus(configItem.status) }}
                  </span>
                </dd>
              </div>
              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Serial Number</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ configItem.serial_number || '-' }}
                </dd>
              </div>
              <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Asset Tag</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ configItem.asset_tag || '-' }}
                </dd>
              </div>
              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Manufacturer</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ configItem.manufacturer || '-' }}
                </dd>
              </div>
              <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Model</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ configItem.model || '-' }}
                </dd>
              </div>
              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Location</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ configItem.location || '-' }}
                </dd>
              </div>
              <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Owner</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  <div v-if="configItem.owner" class="flex items-center">
                    <img
                      class="h-8 w-8 rounded-full"
                      :src="configItem.owner.avatar || 'https://ui-avatars.com/api/?name=' + configItem.owner.name"
                      :alt="configItem.owner.name"
                    />
                    <span class="ml-2">{{ configItem.owner.name }} ({{ configItem.owner.email }})</span>
                  </div>
                  <span v-else>Unassigned</span>
                </dd>
              </div>
              <div v-if="configItem.description" class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Description</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ configItem.description }}
                </dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Purchase & Warranty Info -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
          <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              Purchase & Warranty Information
            </h3>
          </div>
          <div class="border-t border-gray-200">
            <dl>
              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Purchase Date</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ configItem.purchase_date ? formatDate(configItem.purchase_date) : '-' }}
                </dd>
              </div>
              <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Purchase Cost</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ configItem.purchase_cost ? formatCurrency(configItem.purchase_cost) : '-' }}
                </dd>
              </div>
              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Warranty Expiry</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  <div v-if="configItem.warranty_expiry" class="flex items-center">
                    <span>{{ formatDate(configItem.warranty_expiry) }}</span>
                    <span v-if="configItem.warranty_status === 'active'" class="ml-2 text-green-600">
                      <CheckCircleIcon class="inline h-4 w-4" /> Active
                    </span>
                    <span v-else-if="configItem.warranty_status === 'expiring_soon'" class="ml-2 text-yellow-600">
                      <ExclamationTriangleIcon class="inline h-4 w-4" /> Expiring Soon
                    </span>
                    <span v-else-if="configItem.warranty_status === 'expired'" class="ml-2 text-red-600">
                      <XCircleIcon class="inline h-4 w-4" /> Expired
                    </span>
                  </div>
                  <span v-else>-</span>
                </dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Technical Specifications -->
        <div v-if="hasTechnicalSpecs" class="bg-white shadow overflow-hidden sm:rounded-lg">
          <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              Technical Specifications
            </h3>
          </div>
          <div class="border-t border-gray-200">
            <dl>
              <div v-if="configItem.ip_address" class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">IP Address</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ configItem.ip_address }}
                </dd>
              </div>
              <div v-if="configItem.mac_address" class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">MAC Address</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ configItem.mac_address }}
                </dd>
              </div>
              <div v-if="configItem.operating_system" class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Operating System</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ configItem.operating_system }}
                </dd>
              </div>
              <div v-if="configItem.cpu_info" class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">CPU</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ configItem.cpu_info }}
                </dd>
              </div>
              <div v-if="configItem.ram_size" class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">RAM</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ configItem.ram_size }} GB
                </dd>
              </div>
              <div v-if="configItem.storage_size" class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Storage</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ configItem.storage_size }} GB
                </dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Related Items -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <!-- Related Incidents -->
          <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
              <h3 class="text-lg leading-6 font-medium text-gray-900">
                Related Incidents ({{ configItem.incidents_count || 0 }})
              </h3>
            </div>
            <ul v-if="recentIncidents?.length" class="divide-y divide-gray-200">
              <li v-for="incident in recentIncidents" :key="incident.id" class="px-4 py-4 sm:px-6">
                <router-link
                  :to="{ name: 'incident-detail', params: { id: incident.id } }"
                  class="block hover:bg-gray-50"
                >
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm font-medium text-deep-sea-600 truncate">
                        #{{ incident.id }} - {{ incident.title }}
                      </p>
                      <p class="text-sm text-gray-500">
                        {{ incident.status }} • {{ formatDate(incident.created_at) }}
                      </p>
                    </div>
                    <ChevronRightIcon class="h-5 w-5 text-gray-400" />
                  </div>
                </router-link>
              </li>
            </ul>
            <div v-else class="px-4 py-4 sm:px-6 text-center text-gray-500">
              No related incidents
            </div>
          </div>

          <!-- Related Changes -->
          <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
              <h3 class="text-lg leading-6 font-medium text-gray-900">
                Related Changes ({{ configItem.changes_count || 0 }})
              </h3>
            </div>
            <ul v-if="recentChanges?.length" class="divide-y divide-gray-200">
              <li v-for="change in recentChanges" :key="change.id" class="px-4 py-4 sm:px-6">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-deep-sea-600 truncate">
                      #{{ change.id }} - {{ change.title }}
                    </p>
                    <p class="text-sm text-gray-500">
                      {{ change.status }} • {{ formatDate(change.scheduled_at) }}
                    </p>
                  </div>
                  <ChevronRightIcon class="h-5 w-5 text-gray-400" />
                </div>
              </li>
            </ul>
            <div v-else class="px-4 py-4 sm:px-6 text-center text-gray-500">
              No related changes
            </div>
          </div>
        </div>

        <!-- Impact Analysis -->
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              Impact Analysis
            </h3>
            <button
              @click="runImpactAnalysis"
              class="mt-2 inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-deep-sea-600 hover:bg-deep-sea-700"
            >
              <ArrowPathIcon class="-ml-0.5 mr-1.5 h-4 w-4" />
              Run Analysis
            </button>
          </div>
          <div v-if="impactAnalysis" class="border-t border-gray-200 px-4 py-5 sm:px-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
              <div>
                <dt class="text-sm font-medium text-gray-500">Direct Dependencies</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ impactAnalysis.direct_dependencies }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Total Affected Items</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ impactAnalysis.total_affected_items }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Risk Level</dt>
                <dd class="mt-1">
                  <span :class="[
                    getRiskLevelColor(impactAnalysis.risk_level),
                    'inline-flex items-center rounded-full px-3 py-0.5 text-sm font-medium'
                  ]">
                    {{ impactAnalysis.risk_level }}
                  </span>
                </dd>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery, useMutation } from '@tanstack/vue-query'
import {
  ArrowLeftIcon,
  PencilIcon,
  LinkIcon,
  XCircleIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ChevronRightIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline'
import { useToast } from '@/composables/useToast'
import api from '@/services/api'
import { formatDate, formatCurrency } from '@/utils/format'
import type { ConfigurationItem } from '@/types'

const route = useRoute()
const router = useRouter()
const { showToast } = useToast()

const configItemId = computed(() => route.params.id as string)
const showEditModal = ref(false)
const showRelationshipsModal = ref(false)

// Fetch configuration item details
const { data: configItem, isLoading, error } = useQuery({
  queryKey: ['configuration-item', configItemId.value],
  queryFn: async () => {
    const response = await api.get(`/api/v1/configuration-items/${configItemId.value}`)
    return response.data.data
  }
})

// Fetch recent incidents
const { data: recentIncidents } = useQuery({
  queryKey: ['config-item-incidents', configItemId.value],
  queryFn: async () => {
    const response = await api.get(`/api/v1/configuration-items/${configItemId.value}/incidents`, {
      params: { limit: 5 }
    })
    return response.data.data
  },
  enabled: () => !!configItem.value
})

// Fetch recent changes
const { data: recentChanges } = useQuery({
  queryKey: ['config-item-changes', configItemId.value],
  queryFn: async () => {
    const response = await api.get(`/api/v1/configuration-items/${configItemId.value}/changes`, {
      params: { limit: 5 }
    })
    return response.data.data
  },
  enabled: () => !!configItem.value
})

// Impact analysis
const impactAnalysis = ref(null)
const runImpactAnalysisMutation = useMutation({
  mutationFn: async () => {
    const response = await api.get(`/api/v1/configuration-items/${configItemId.value}/impact`)
    return response.data.data
  },
  onSuccess: (data) => {
    impactAnalysis.value = data
    showToast('Impact analysis completed', 'success')
  },
  onError: () => {
    showToast('Failed to run impact analysis', 'error')
  }
})

const runImpactAnalysis = () => {
  runImpactAnalysisMutation.mutate()
}

const hasTechnicalSpecs = computed(() => {
  return configItem.value && (
    configItem.value.ip_address ||
    configItem.value.mac_address ||
    configItem.value.operating_system ||
    configItem.value.cpu_info ||
    configItem.value.ram_size ||
    configItem.value.storage_size
  )
})

const formatType = (type: string) => {
  return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatStatus = (status: string) => {
  return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
    maintenance: 'bg-yellow-100 text-yellow-800',
    retired: 'bg-orange-100 text-orange-800',
    disposed: 'bg-red-100 text-red-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const getRiskLevelColor = (level: string) => {
  const colors: Record<string, string> = {
    low: 'bg-green-100 text-green-800',
    medium: 'bg-yellow-100 text-yellow-800',
    high: 'bg-orange-100 text-orange-800',
    critical: 'bg-red-100 text-red-800'
  }
  return colors[level] || 'bg-gray-100 text-gray-800'
}
</script>