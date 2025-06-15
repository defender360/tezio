<template>
  <div class="p-6 max-w-6xl mx-auto">
    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center py-12">
      <LoadingSpinner />
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
      <p class="text-red-800">Error loading problem: {{ error.message }}</p>
    </div>

    <!-- Problem Details -->
    <div v-else-if="problem">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-center gap-4 mb-2">
          <router-link
            to="/problems"
            class="text-gray-500 hover:text-gray-700 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </router-link>
          <h1 class="text-2xl font-bold text-gray-900">{{ problem.number }}</h1>
          <StatusBadge :status="problem.status" />
          <PriorityBadge :priority="problem.priority" />
          <span
            v-if="problem.isKnownError"
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
          >
            Known Error
          </span>
        </div>
        <h2 class="text-xl text-gray-700">{{ problem.title }}</h2>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Basic Information -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Problem Details</h3>
            
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">Category</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ problem.category }}</dd>
              </div>
              
              <div>
                <dt class="text-sm font-medium text-gray-500">Impact</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ problem.impact }}</dd>
              </div>
              
              <div>
                <dt class="text-sm font-medium text-gray-500">Assignee</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ problem.assignee?.name || 'Unassigned' }}</dd>
              </div>
              
              <div>
                <dt class="text-sm font-medium text-gray-500">Team</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ problem.team || '-' }}</dd>
              </div>
              
              <div>
                <dt class="text-sm font-medium text-gray-500">Created</dt>
                <dd class="mt-1 text-sm text-gray-900">
                  <TimeAgo :date="problem.createdAt" />
                </dd>
              </div>
              
              <div>
                <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                <dd class="mt-1 text-sm text-gray-900">
                  <TimeAgo :date="problem.updatedAt" />
                </dd>
              </div>
            </dl>
            
            <div class="mt-4">
              <h4 class="text-sm font-medium text-gray-500 mb-2">Description</h4>
              <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ problem.description }}</p>
            </div>
            
            <div v-if="problem.symptoms" class="mt-4">
              <h4 class="text-sm font-medium text-gray-500 mb-2">Symptoms</h4>
              <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ problem.symptoms }}</p>
            </div>
          </div>

          <!-- Investigation -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Investigation</h3>
            
            <div class="space-y-4">
              <div v-if="problem.investigationNotes">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Investigation Notes</h4>
                <div class="bg-gray-50 rounded-md p-4">
                  <pre class="text-sm text-gray-900 whitespace-pre-wrap">{{ problem.investigationNotes }}</pre>
                </div>
              </div>
              
              <div v-if="problem.possibleCauses">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Possible Root Causes</h4>
                <div class="bg-gray-50 rounded-md p-4">
                  <pre class="text-sm text-gray-900 whitespace-pre-wrap">{{ problem.possibleCauses }}</pre>
                </div>
              </div>
              
              <div v-if="problem.rootCause">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Identified Root Cause</h4>
                <div class="bg-green-50 border border-green-200 rounded-md p-4">
                  <pre class="text-sm text-green-900 whitespace-pre-wrap">{{ problem.rootCause }}</pre>
                </div>
              </div>
              
              <div v-if="problem.workaround">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Workaround</h4>
                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                  <pre class="text-sm text-yellow-900 whitespace-pre-wrap">{{ problem.workaround }}</pre>
                </div>
              </div>
              
              <div v-if="problem.permanentFix">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Permanent Fix</h4>
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                  <pre class="text-sm text-blue-900 whitespace-pre-wrap">{{ problem.permanentFix }}</pre>
                </div>
              </div>
            </div>
          </div>

          <!-- Related Incidents -->
          <div v-if="problem.relatedIncidents?.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Related Incidents</h3>
            
            <div class="space-y-3">
              <div
                v-for="incident in problem.relatedIncidents"
                :key="incident.id"
                class="flex items-center justify-between p-3 bg-gray-50 rounded-md"
              >
                <div>
                  <p class="font-medium text-gray-900">{{ incident.number }}</p>
                  <p class="text-sm text-gray-600">{{ incident.title }}</p>
                  <p class="text-xs text-gray-500">Status: {{ incident.status }}</p>
                </div>
                <router-link
                  :to="`/incidents/${incident.id}`"
                  class="text-blue-600 hover:text-blue-800 text-sm"
                >
                  View
                </router-link>
              </div>
            </div>
          </div>

          <!-- Investigation Log -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900">Investigation Log</h3>
              <button
                @click="showAddEntryModal = true"
                class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 transition-colors"
              >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Entry
              </button>
            </div>
            
            <div class="space-y-4">
              <div v-for="entry in investigationLog" :key="entry.id" class="flex gap-4">
                <div class="flex-shrink-0">
                  <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                </div>
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="font-medium text-gray-900">{{ entry.user.name }}</span>
                    <span class="text-xs text-gray-500">
                      <TimeAgo :date="entry.createdAt" />
                    </span>
                  </div>
                  <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ entry.content }}</p>
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
              <template v-if="problem.status === 'new'">
                <button
                  @click="startInvestigation"
                  class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                >
                  Start Investigation
                </button>
              </template>
              
              <template v-else-if="problem.status === 'investigating'">
                <button
                  @click="markAsKnownError"
                  class="w-full px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition-colors"
                >
                  Mark as Known Error
                </button>
                <button
                  @click="identifyRootCause"
                  class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors"
                >
                  Identify Root Cause
                </button>
              </template>
              
              <template v-else-if="problem.status === 'identified'">
                <button
                  @click="createChangeRequest"
                  class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                >
                  Create Change Request
                </button>
                <button
                  @click="resolveProblem"
                  class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors"
                >
                  Mark as Resolved
                </button>
              </template>
              
              <template v-else-if="problem.status === 'resolved'">
                <button
                  @click="closeProblem"
                  class="w-full px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors"
                >
                  Close Problem
                </button>
              </template>
              
              <button
                @click="reassignProblem"
                class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors"
              >
                Reassign
              </button>
            </div>
          </div>

          <!-- Problem Statistics -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistics</h3>
            
            <div class="space-y-3">
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Related Incidents</span>
                <span class="text-sm font-medium text-gray-900">{{ problem.relatedIncidentsCount || 0 }}</span>
              </div>
              
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Days Open</span>
                <span class="text-sm font-medium text-gray-900">{{ daysOpen }}</span>
              </div>
              
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Investigation Entries</span>
                <span class="text-sm font-medium text-gray-900">{{ investigationLog?.length || 0 }}</span>
              </div>
            </div>
          </div>

          <!-- Related Change Requests -->
          <div v-if="problem.relatedChanges?.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Related Changes</h3>
            
            <div class="space-y-2">
              <router-link
                v-for="change in problem.relatedChanges"
                :key="change.id"
                :to="`/changes/${change.id}`"
                class="block text-sm text-blue-600 hover:text-blue-800"
              >
                {{ change.number }}: {{ change.title }}
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Investigation Entry Modal -->
    <div v-if="showAddEntryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-lg mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Add Investigation Entry</h3>
        
        <form @submit.prevent="addInvestigationEntry">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Entry Content
            </label>
            <textarea
              v-model="newEntry.content"
              required
              rows="4"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="Document your investigation findings..."
            ></textarea>
          </div>
          
          <div class="flex justify-end gap-3">
            <button
              type="button"
              @click="showAddEntryModal = false"
              class="px-4 py-2 text-gray-700 hover:text-gray-900 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="isAddingEntry"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50"
            >
              Add Entry
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { differenceInDays, parseISO } from 'date-fns'
import { useToast } from 'vue-toastification'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import PriorityBadge from '@/components/common/PriorityBadge.vue'
import TimeAgo from '@/components/common/TimeAgo.vue'
import { api } from '@/services/api'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const queryClient = useQueryClient()

const problemId = route.params.id as string

// State
const showAddEntryModal = ref(false)
const newEntry = ref({ content: '' })
const isAddingEntry = ref(false)

// Queries
const { data: problem, isLoading, error } = useQuery({
  queryKey: ['problem', problemId],
  queryFn: async () => {
    const response = await api.get(`/problems/${problemId}`)
    return response.data
  }
})

const { data: investigationLog } = useQuery({
  queryKey: ['problem-investigation', problemId],
  queryFn: async () => {
    const response = await api.get(`/problems/${problemId}/investigation`)
    return response.data
  }
})

// Computed
const daysOpen = computed(() => {
  if (!problem.value?.createdAt) return 0
  return differenceInDays(new Date(), parseISO(problem.value.createdAt))
})

// Mutations
const updateStatus = useMutation({
  mutationFn: async (data: { status: string; [key: string]: any }) => {
    const response = await api.patch(`/problems/${problemId}/status`, data)
    return response.data
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['problem', problemId] })
  }
})

// Methods
const startInvestigation = () => {
  updateStatus.mutate({ status: 'investigating' })
  toast.success('Investigation started')
}

const markAsKnownError = () => {
  updateStatus.mutate({ status: 'investigating', isKnownError: true })
  toast.success('Marked as known error')
}

const identifyRootCause = () => {
  const rootCause = prompt('Please describe the identified root cause:')
  if (rootCause) {
    updateStatus.mutate({ status: 'identified', rootCause })
    toast.success('Root cause identified')
  }
}

const resolveProblem = () => {
  const permanentFix = prompt('Please describe the permanent fix:')
  if (permanentFix) {
    updateStatus.mutate({ status: 'resolved', permanentFix })
    toast.success('Problem resolved')
  }
}

const closeProblem = () => {
  updateStatus.mutate({ status: 'closed' })
  toast.success('Problem closed')
}

const createChangeRequest = () => {
  router.push(`/changes/new?problemId=${problemId}`)
}

const reassignProblem = () => {
  // Implementation for reassigning problem
}

const addInvestigationEntry = async () => {
  if (!newEntry.value.content.trim()) return
  
  isAddingEntry.value = true
  try {
    await api.post(`/problems/${problemId}/investigation`, {
      content: newEntry.value.content
    })
    queryClient.invalidateQueries({ queryKey: ['problem-investigation', problemId] })
    newEntry.value.content = ''
    showAddEntryModal.value = false
    toast.success('Investigation entry added')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to add entry')
  } finally {
    isAddingEntry.value = false
  }
}
</script>