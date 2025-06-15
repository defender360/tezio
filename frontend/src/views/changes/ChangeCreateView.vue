<template>
  <div class="p-6 max-w-4xl mx-auto">
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
        <h1 class="text-2xl font-bold text-gray-900">Create Change Request</h1>
      </div>
      <p class="text-gray-600">Submit a new change request for review and approval</p>
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
              placeholder="Brief description of the change"
            >
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Type <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.type"
              required
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">Select type</option>
              <option value="standard">Standard (Pre-approved)</option>
              <option value="normal">Normal</option>
              <option value="emergency">Emergency</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Risk Level <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.risk"
              required
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
              <option value="">Select risk level</option>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="critical">Critical</option>
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
              <option value="low">Low - Minimal impact</option>
              <option value="medium">Medium - Some users affected</option>
              <option value="high">High - Many users affected</option>
              <option value="critical">Critical - All users affected</option>
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
            placeholder="Detailed description of the change"
          ></textarea>
        </div>
        
        <div class="mt-6">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Business Justification <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="form.justification"
            required
            rows="3"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="Why is this change necessary?"
          ></textarea>
        </div>
      </div>

      <!-- Schedule -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Schedule</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Scheduled Start <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.scheduledStart"
              type="datetime-local"
              required
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Scheduled End <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.scheduledEnd"
              type="datetime-local"
              required
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
          </div>
        </div>
        
        <div class="mt-6">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Downtime Required
          </label>
          <div class="flex items-center gap-6">
            <label class="flex items-center">
              <input
                v-model="form.downtimeRequired"
                type="radio"
                :value="true"
                class="mr-2 text-blue-600 focus:ring-blue-500"
              >
              <span class="text-sm text-gray-700">Yes</span>
            </label>
            <label class="flex items-center">
              <input
                v-model="form.downtimeRequired"
                type="radio"
                :value="false"
                class="mr-2 text-blue-600 focus:ring-blue-500"
              >
              <span class="text-sm text-gray-700">No</span>
            </label>
          </div>
        </div>
        
        <div v-if="form.downtimeRequired" class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Expected Downtime (minutes)
          </label>
          <input
            v-model.number="form.expectedDowntime"
            type="number"
            min="0"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="Enter expected downtime in minutes"
          >
        </div>
      </div>

      <!-- Implementation Plan -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Implementation Plan</h2>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Implementation Steps <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="form.implementationPlan"
            required
            rows="6"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="List the steps to implement this change"
          ></textarea>
        </div>
        
        <div class="mt-6">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Rollback Plan <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="form.rollbackPlan"
            required
            rows="4"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="How to rollback if the change fails?"
          ></textarea>
        </div>
        
        <div class="mt-6">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Test Plan
          </label>
          <textarea
            v-model="form.testPlan"
            rows="4"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="How will the change be tested?"
          ></textarea>
        </div>
      </div>

      <!-- Affected Configuration Items -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Affected Configuration Items</h2>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Search Configuration Items
          </label>
          <div class="flex gap-2">
            <input
              v-model="ciSearch"
              type="text"
              class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="Search by name or type..."
            >
            <button
              type="button"
              @click="searchCIs"
              class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors"
            >
              Search
            </button>
          </div>
        </div>
        
        <div v-if="selectedCIs.length > 0" class="mb-4">
          <h3 class="text-sm font-medium text-gray-700 mb-2">Selected Items</h3>
          <div class="space-y-2">
            <div
              v-for="ci in selectedCIs"
              :key="ci.id"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-md"
            >
              <div>
                <p class="font-medium text-gray-900">{{ ci.name }}</p>
                <p class="text-sm text-gray-500">{{ ci.type }} - {{ ci.status }}</p>
              </div>
              <button
                type="button"
                @click="removeCi(ci.id)"
                class="text-red-600 hover:text-red-800"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Approvers -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Approvers</h2>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Change Advisory Board (CAB) Members
          </label>
          <UserSelect
            v-model="form.approvers"
            multiple
            placeholder="Select CAB members to approve this change"
          />
        </div>
        
        <div class="p-4 bg-blue-50 rounded-md">
          <p class="text-sm text-blue-800">
            <strong>Note:</strong> Based on the change type and risk level, additional approvers may be automatically added.
          </p>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-4">
        <router-link
          to="/changes"
          class="px-4 py-2 text-gray-700 hover:text-gray-900 transition-colors"
        >
          Cancel
        </router-link>
        <button
          type="button"
          @click="saveDraft"
          :disabled="isSaving"
          class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors disabled:opacity-50"
        >
          Save as Draft
        </button>
        <button
          type="submit"
          :disabled="isSubmitting"
          class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center gap-2"
        >
          <LoadingSpinner v-if="isSubmitting" class="w-4 h-4" />
          Submit for Approval
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useMutation, useQuery } from '@tanstack/vue-query'
import { useToast } from 'vue-toastification'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import UserSelect from '@/components/common/UserSelect.vue'
import { api } from '@/services/api'

const router = useRouter()
const toast = useToast()

// Form state
const form = reactive({
  title: '',
  type: '',
  risk: '',
  impact: '',
  description: '',
  justification: '',
  scheduledStart: '',
  scheduledEnd: '',
  downtimeRequired: false,
  expectedDowntime: 0,
  implementationPlan: '',
  rollbackPlan: '',
  testPlan: '',
  affectedCIs: [] as string[],
  approvers: [] as string[]
})

const ciSearch = ref('')
const selectedCIs = ref<any[]>([])
const isSaving = ref(false)

// Mutations
const { mutate: createChange, isPending: isSubmitting } = useMutation({
  mutationFn: async (data: any) => {
    const response = await api.post('/changes', data)
    return response.data
  },
  onSuccess: (data) => {
    toast.success('Change request created successfully')
    router.push(`/changes/${data.id}`)
  },
  onError: (error: any) => {
    toast.error(error.response?.data?.message || 'Failed to create change request')
  }
})

// Methods
const handleSubmit = () => {
  const data = {
    ...form,
    affectedCIs: selectedCIs.value.map(ci => ci.id),
    status: 'awaiting_approval'
  }
  createChange(data)
}

const saveDraft = async () => {
  isSaving.value = true
  try {
    const data = {
      ...form,
      affectedCIs: selectedCIs.value.map(ci => ci.id),
      status: 'draft'
    }
    const response = await api.post('/changes', data)
    toast.success('Draft saved successfully')
    router.push(`/changes/${response.data.id}`)
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to save draft')
  } finally {
    isSaving.value = false
  }
}

const searchCIs = async () => {
  // Implementation for searching configuration items
  // This would typically call an API endpoint
}

const removeCi = (id: string) => {
  selectedCIs.value = selectedCIs.value.filter(ci => ci.id !== id)
}
</script>