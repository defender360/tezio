<template>
  <div class="p-6 max-w-4xl mx-auto">
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
        <h1 class="text-2xl font-bold text-gray-900">Create Problem</h1>
      </div>
      <p class="text-gray-600">Document a new problem for investigation</p>
    </div>

    <!-- Form -->
    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Basic Information -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Title <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.title"
              type="text"
              required
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="Brief description of the problem"
            >
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Priority <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.priority"
              required
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">Select priority</option>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="critical">Critical</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Category <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.category"
              required
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">Select category</option>
              <option value="hardware">Hardware</option>
              <option value="software">Software</option>
              <option value="network">Network</option>
              <option value="process">Process</option>
              <option value="human">Human Error</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Impact <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.impact"
              required
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">Select impact</option>
              <option value="low">Low - Few users affected</option>
              <option value="medium">Medium - Department affected</option>
              <option value="high">High - Multiple departments affected</option>
              <option value="critical">Critical - Entire organization affected</option>
            </select>
          </div>
        </div>
        
        <div class="mt-6">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Description <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="form.description"
            required
            rows="4"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="Detailed description of the problem"
          ></textarea>
        </div>
        
        <div class="mt-6">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Symptoms
          </label>
          <textarea
            v-model="form.symptoms"
            rows="3"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="What are the observable symptoms of this problem?"
          ></textarea>
        </div>
      </div>

      <!-- Related Incidents -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Related Incidents</h2>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Search Incidents
          </label>
          <div class="flex gap-2">
            <input
              v-model="incidentSearch"
              type="text"
              class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="Search by incident number or title..."
              @keyup.enter="searchIncidents"
            >
            <button
              type="button"
              @click="searchIncidents"
              class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors"
            >
              Search
            </button>
          </div>
        </div>
        
        <!-- Search Results -->
        <div v-if="searchResults.length > 0" class="mb-4 max-h-48 overflow-y-auto border border-gray-200 rounded-md">
          <div
            v-for="incident in searchResults"
            :key="incident.id"
            class="p-3 hover:bg-gray-50 cursor-pointer border-b last:border-b-0"
            @click="addRelatedIncident(incident)"
          >
            <div class="flex items-center justify-between">
              <div>
                <p class="font-medium text-gray-900">{{ incident.number }}</p>
                <p class="text-sm text-gray-600">{{ incident.title }}</p>
              </div>
              <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
            </div>
          </div>
        </div>
        
        <!-- Selected Incidents -->
        <div v-if="selectedIncidents.length > 0" class="space-y-2">
          <h3 class="text-sm font-medium text-gray-700">Selected Incidents</h3>
          <div
            v-for="incident in selectedIncidents"
            :key="incident.id"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-md"
          >
            <div>
              <p class="font-medium text-gray-900">{{ incident.number }}: {{ incident.title }}</p>
              <p class="text-sm text-gray-500">Status: {{ incident.status }}</p>
            </div>
            <button
              type="button"
              @click="removeIncident(incident.id)"
              class="text-red-600 hover:text-red-800"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Investigation -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Initial Investigation</h2>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Investigation Notes
          </label>
          <textarea
            v-model="form.investigationNotes"
            rows="4"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="Document any initial investigation findings..."
          ></textarea>
        </div>
        
        <div class="mt-6">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Possible Root Causes
          </label>
          <textarea
            v-model="form.possibleCauses"
            rows="3"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="List any suspected root causes..."
          ></textarea>
        </div>
        
        <div class="mt-6">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Temporary Workaround
          </label>
          <textarea
            v-model="form.workaround"
            rows="3"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="Is there a temporary workaround available?"
          ></textarea>
        </div>
      </div>

      <!-- Assignment -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Assignment</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Assign To
            </label>
            <UserSelect
              v-model="form.assigneeId"
              placeholder="Select assignee"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Problem Team
            </label>
            <select
              v-model="form.team"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">Select team</option>
              <option value="infrastructure">Infrastructure</option>
              <option value="application">Application</option>
              <option value="network">Network</option>
              <option value="security">Security</option>
              <option value="database">Database</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-4">
        <router-link
          to="/problems"
          class="px-4 py-2 text-gray-700 hover:text-gray-900 transition-colors"
        >
          Cancel
        </router-link>
        <button
          type="submit"
          :disabled="isSubmitting"
          class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center gap-2"
        >
          <LoadingSpinner v-if="isSubmitting" class="w-4 h-4" />
          Create Problem
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useMutation } from '@tanstack/vue-query'
import { useToast } from 'vue-toastification'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import UserSelect from '@/components/common/UserSelect.vue'
import { api } from '@/services/api'

const router = useRouter()
const toast = useToast()

// Form state
const form = reactive({
  title: '',
  priority: '',
  category: '',
  impact: '',
  description: '',
  symptoms: '',
  investigationNotes: '',
  possibleCauses: '',
  workaround: '',
  assigneeId: '',
  team: '',
  relatedIncidents: [] as string[]
})

const incidentSearch = ref('')
const searchResults = ref<any[]>([])
const selectedIncidents = ref<any[]>([])

// Mutations
const { mutate: createProblem, isPending: isSubmitting } = useMutation({
  mutationFn: async (data: any) => {
    const response = await api.post('/problems', data)
    return response.data
  },
  onSuccess: (data) => {
    toast.success('Problem created successfully')
    router.push(`/problems/${data.id}`)
  },
  onError: (error: any) => {
    toast.error(error.response?.data?.message || 'Failed to create problem')
  }
})

// Methods
const handleSubmit = () => {
  const data = {
    ...form,
    relatedIncidents: selectedIncidents.value.map(i => i.id)
  }
  createProblem(data)
}

const searchIncidents = async () => {
  if (!incidentSearch.value.trim()) return
  
  try {
    const response = await api.get('/incidents', {
      params: {
        search: incidentSearch.value,
        limit: 10
      }
    })
    searchResults.value = response.data.items || []
  } catch (error) {
    toast.error('Failed to search incidents')
  }
}

const addRelatedIncident = (incident: any) => {
  if (!selectedIncidents.value.find(i => i.id === incident.id)) {
    selectedIncidents.value.push(incident)
  }
  searchResults.value = []
  incidentSearch.value = ''
}

const removeIncident = (id: string) => {
  selectedIncidents.value = selectedIncidents.value.filter(i => i.id !== id)
}
</script>