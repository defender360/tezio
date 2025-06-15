<template>
  <div class="p-6 max-w-6xl mx-auto">
    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center py-12">
      <LoadingSpinner />
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
      <p class="text-red-800">Error loading change: {{ error.message }}</p>
    </div>

    <!-- Change Details -->
    <div v-else-if="change">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-center gap-4 mb-2">
          <router-link
            to="/changes"
            class="text-gray-500 hover:text-gray-700 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </router-link>
          <h1 class="text-2xl font-bold text-gray-900">{{ change.number }}</h1>
          <StatusBadge :status="change.status" />
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
        </div>
        <h2 class="text-xl text-gray-700">{{ change.title }}</h2>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Basic Information -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
            
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">Risk Level</dt>
                <dd class="mt-1">
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
                </dd>
              </div>
              
              <div>
                <dt class="text-sm font-medium text-gray-500">Impact</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ change.impact }}</dd>
              </div>
              
              <div>
                <dt class="text-sm font-medium text-gray-500">Requester</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ change.requester?.name }}</dd>
              </div>
              
              <div>
                <dt class="text-sm font-medium text-gray-500">Assignee</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ change.assignee?.name || 'Unassigned' }}</dd>
              </div>
              
              <div>
                <dt class="text-sm font-medium text-gray-500">Created</dt>
                <dd class="mt-1 text-sm text-gray-900">
                  <TimeAgo :date="change.createdAt" />
                </dd>
              </div>
              
              <div>
                <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                <dd class="mt-1 text-sm text-gray-900">
                  <TimeAgo :date="change.updatedAt" />
                </dd>
              </div>
            </dl>
            
            <div class="mt-4">
              <h4 class="text-sm font-medium text-gray-500 mb-2">Description</h4>
              <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ change.description }}</p>
            </div>
            
            <div class="mt-4">
              <h4 class="text-sm font-medium text-gray-500 mb-2">Business Justification</h4>
              <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ change.justification }}</p>
            </div>
          </div>

          <!-- Schedule -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Schedule</h3>
            
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">Scheduled Start</dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDate(change.scheduledStart) }}
                </dd>
              </div>
              
              <div>
                <dt class="text-sm font-medium text-gray-500">Scheduled End</dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ formatDate(change.scheduledEnd) }}
                </dd>
              </div>
              
              <div>
                <dt class="text-sm font-medium text-gray-500">Downtime Required</dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ change.downtimeRequired ? 'Yes' : 'No' }}
                </dd>
              </div>
              
              <div v-if="change.downtimeRequired">
                <dt class="text-sm font-medium text-gray-500">Expected Downtime</dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ change.expectedDowntime }} minutes
                </dd>
              </div>
            </dl>
          </div>

          <!-- Implementation Details -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Implementation Details</h3>
            
            <div class="space-y-4">
              <div>
                <h4 class="text-sm font-medium text-gray-700 mb-2">Implementation Plan</h4>
                <div class="bg-gray-50 rounded-md p-4">
                  <pre class="text-sm text-gray-900 whitespace-pre-wrap">{{ change.implementationPlan }}</pre>
                </div>
              </div>
              
              <div>
                <h4 class="text-sm font-medium text-gray-700 mb-2">Rollback Plan</h4>
                <div class="bg-gray-50 rounded-md p-4">
                  <pre class="text-sm text-gray-900 whitespace-pre-wrap">{{ change.rollbackPlan }}</pre>
                </div>
              </div>
              
              <div v-if="change.testPlan">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Test Plan</h4>
                <div class="bg-gray-50 rounded-md p-4">
                  <pre class="text-sm text-gray-900 whitespace-pre-wrap">{{ change.testPlan }}</pre>
                </div>
              </div>
            </div>
          </div>

          <!-- Affected Configuration Items -->
          <div v-if="change.affectedCIs?.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Affected Configuration Items</h3>
            
            <div class="space-y-2">
              <div
                v-for="ci in change.affectedCIs"
                :key="ci.id"
                class="flex items-center justify-between p-3 bg-gray-50 rounded-md"
              >
                <div>
                  <p class="font-medium text-gray-900">{{ ci.name }}</p>
                  <p class="text-sm text-gray-500">{{ ci.type }} - {{ ci.status }}</p>
                </div>
                <router-link
                  :to="`/cmdb/items/${ci.id}`"
                  class="text-blue-600 hover:text-blue-800 text-sm"
                >
                  View
                </router-link>
              </div>
            </div>
          </div>

          <!-- Activity Log -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Activity Log</h3>
            
            <div class="space-y-4">
              <div v-for="activity in activities" :key="activity.id" class="flex gap-4">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                </div>
                <div class="flex-1">
                  <p class="text-sm text-gray-900">
                    <span class="font-medium">{{ activity.user.name }}</span>
                    {{ activity.action }}
                  </p>
                  <p class="text-xs text-gray-500">
                    <TimeAgo :date="activity.createdAt" />
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Actions -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
            
            <div class="space-y-3">
              <!-- Status-specific actions -->
              <template v-if="change.status === 'draft'">
                <button
                  @click="submitForApproval"
                  class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                >
                  Submit for Approval
                </button>
                <button
                  @click="editChange"
                  class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors"
                >
                  Edit Change
                </button>
              </template>
              
              <template v-else-if="change.status === 'awaiting_approval' && canApprove">
                <button
                  @click="approveChange"
                  class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors"
                >
                  Approve
                </button>
                <button
                  @click="rejectChange"
                  class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors"
                >
                  Reject
                </button>
                <button
                  @click="requestMoreInfo"
                  class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors"
                >
                  Request More Info
                </button>
              </template>
              
              <template v-else-if="change.status === 'approved'">
                <button
                  @click="scheduleChange"
                  class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                >
                  Schedule Implementation
                </button>
              </template>
              
              <template v-else-if="change.status === 'scheduled'">
                <button
                  @click="startImplementation"
                  class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                >
                  Start Implementation
                </button>
                <button
                  @click="postponeChange"
                  class="w-full px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition-colors"
                >
                  Postpone
                </button>
              </template>
              
              <template v-else-if="change.status === 'implementing'">
                <button
                  @click="completeChange"
                  class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors"
                >
                  Mark as Completed
                </button>
                <button
                  @click="rollbackChange"
                  class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors"
                >
                  Rollback
                </button>
              </template>
              
              <button
                @click="cancelChange"
                class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors"
              >
                Cancel Change
              </button>
            </div>
          </div>

          <!-- Approval Status -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Approval Status</h3>
            
            <div class="space-y-3">
              <div v-for="approval in change.approvals" :key="approval.id" class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div
                    :class="[
                      'w-8 h-8 rounded-full flex items-center justify-center',
                      approval.status === 'approved'
                        ? 'bg-green-100'
                        : approval.status === 'rejected'
                        ? 'bg-red-100'
                        : 'bg-gray-100'
                    ]"
                  >
                    <svg
                      v-if="approval.status === 'approved'"
                      class="w-5 h-5 text-green-600"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg
                      v-else-if="approval.status === 'rejected'"
                      class="w-5 h-5 text-red-600"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <svg
                      v-else
                      class="w-5 h-5 text-gray-400"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ approval.approver.name }}</p>
                    <p class="text-xs text-gray-500">
                      {{ approval.status === 'pending' ? 'Pending' : formatDate(approval.decidedAt) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Related Items -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Related Items</h3>
            
            <div class="space-y-4">
              <div v-if="change.relatedIncidents?.length > 0">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Related Incidents</h4>
                <div class="space-y-2">
                  <router-link
                    v-for="incident in change.relatedIncidents"
                    :key="incident.id"
                    :to="`/incidents/${incident.id}`"
                    class="block text-sm text-blue-600 hover:text-blue-800"
                  >
                    {{ incident.number }}: {{ incident.title }}
                  </router-link>
                </div>
              </div>
              
              <div v-if="change.relatedProblems?.length > 0">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Related Problems</h4>
                <div class="space-y-2">
                  <router-link
                    v-for="problem in change.relatedProblems"
                    :key="problem.id"
                    :to="`/problems/${problem.id}`"
                    class="block text-sm text-blue-600 hover:text-blue-800"
                  >
                    {{ problem.number }}: {{ problem.title }}
                  </router-link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { format, parseISO } from 'date-fns'
import { useToast } from 'vue-toastification'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import TimeAgo from '@/components/common/TimeAgo.vue'
import { api } from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const queryClient = useQueryClient()
const authStore = useAuthStore()

const changeId = route.params.id as string

// Queries
const { data: change, isLoading, error } = useQuery({
  queryKey: ['change', changeId],
  queryFn: async () => {
    const response = await api.get(`/changes/${changeId}`)
    return response.data
  }
})

const { data: activities } = useQuery({
  queryKey: ['change-activities', changeId],
  queryFn: async () => {
    const response = await api.get(`/changes/${changeId}/activities`)
    return response.data
  }
})

// Computed
const canApprove = computed(() => {
  if (!change.value || !authStore.user) return false
  return change.value.approvals.some(
    (approval: any) => 
      approval.approver.id === authStore.user?.id && 
      approval.status === 'pending'
  )
})

// Mutations
const updateStatus = useMutation({
  mutationFn: async (status: string) => {
    const response = await api.patch(`/changes/${changeId}/status`, { status })
    return response.data
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['change', changeId] })
    queryClient.invalidateQueries({ queryKey: ['change-activities', changeId] })
  }
})

const updateApproval = useMutation({
  mutationFn: async ({ status, comment }: { status: string; comment?: string }) => {
    const response = await api.post(`/changes/${changeId}/approvals`, { status, comment })
    return response.data
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['change', changeId] })
    queryClient.invalidateQueries({ queryKey: ['change-activities', changeId] })
  }
})

// Methods
const formatDate = (dateString: string) => {
  if (!dateString) return '-'
  return format(parseISO(dateString), 'MMM d, yyyy HH:mm')
}

const editChange = () => {
  router.push(`/changes/${changeId}/edit`)
}

const submitForApproval = async () => {
  try {
    await updateStatus.mutateAsync('awaiting_approval')
    toast.success('Change submitted for approval')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to submit change')
  }
}

const approveChange = async () => {
  try {
    await updateApproval.mutateAsync({ status: 'approved' })
    toast.success('Change approved')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to approve change')
  }
}

const rejectChange = async () => {
  const comment = prompt('Please provide a reason for rejection:')
  if (!comment) return
  
  try {
    await updateApproval.mutateAsync({ status: 'rejected', comment })
    toast.success('Change rejected')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to reject change')
  }
}

const requestMoreInfo = async () => {
  const comment = prompt('What additional information is needed?')
  if (!comment) return
  
  try {
    await updateApproval.mutateAsync({ status: 'more_info_needed', comment })
    toast.success('More information requested')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to request more info')
  }
}

const scheduleChange = () => {
  // Implementation for scheduling change
}

const startImplementation = async () => {
  try {
    await updateStatus.mutateAsync('implementing')
    toast.success('Implementation started')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to start implementation')
  }
}

const completeChange = async () => {
  try {
    await updateStatus.mutateAsync('completed')
    toast.success('Change completed successfully')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to complete change')
  }
}

const rollbackChange = async () => {
  const reason = prompt('Please provide a reason for rollback:')
  if (!reason) return
  
  try {
    await updateStatus.mutateAsync('rolled_back')
    toast.success('Change rolled back')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to rollback change')
  }
}

const postponeChange = async () => {
  const reason = prompt('Please provide a reason for postponement:')
  if (!reason) return
  
  try {
    await updateStatus.mutateAsync('postponed')
    toast.success('Change postponed')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to postpone change')
  }
}

const cancelChange = async () => {
  const reason = prompt('Please provide a reason for cancellation:')
  if (!reason) return
  
  try {
    await updateStatus.mutateAsync('cancelled')
    toast.success('Change cancelled')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to cancel change')
  }
}
</script>