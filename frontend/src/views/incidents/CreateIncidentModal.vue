<template>
  <TransitionRoot as="template" :show="open">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
              <form @submit.prevent="handleSubmit">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                  <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-deep-sea-100 sm:mx-0 sm:h-10 sm:w-10">
                      <ExclamationTriangleIcon class="h-6 w-6 text-deep-sea-600" aria-hidden="true" />
                    </div>
                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                      <DialogTitle as="h3" class="text-base font-semibold leading-6 text-gray-900">
                        Create New Incident
                      </DialogTitle>
                      
                      <div class="mt-4 space-y-4">
                        <!-- Title -->
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Title</label>
                          <input
                            v-model="form.title"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                            placeholder="Brief description of the incident"
                          />
                        </div>

                        <!-- Description -->
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Description</label>
                          <textarea
                            v-model="form.description"
                            rows="4"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                            placeholder="Detailed description of the incident"
                          />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                          <!-- Priority -->
                          <div>
                            <label class="block text-sm font-medium text-gray-700">Priority</label>
                            <select
                              v-model="form.priority"
                              required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                            >
                              <option value="critical">Critical</option>
                              <option value="high">High</option>
                              <option value="medium">Medium</option>
                              <option value="low">Low</option>
                            </select>
                          </div>

                          <!-- Impact -->
                          <div>
                            <label class="block text-sm font-medium text-gray-700">Impact</label>
                            <select
                              v-model="form.impact"
                              required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                            >
                              <option value="high">High - Multiple users/critical systems</option>
                              <option value="medium">Medium - Department/group affected</option>
                              <option value="low">Low - Individual user affected</option>
                            </select>
                          </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                          <!-- Category -->
                          <div>
                            <label class="block text-sm font-medium text-gray-700">Category</label>
                            <select
                              v-model="form.category_id"
                              required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                            >
                              <option value="1">Hardware</option>
                              <option value="2">Software</option>
                              <option value="3">Network</option>
                              <option value="4">Security</option>
                              <option value="5">Access</option>
                              <option value="6">Other</option>
                            </select>
                          </div>

                          <!-- Assignee -->
                          <div>
                            <label class="block text-sm font-medium text-gray-700">Assign To</label>
                            <select
                              v-model="form.assigned_to"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                            >
                              <option value="">Auto-assign</option>
                              <option v-for="agent in availableAgents" :key="agent.id" :value="agent.id">
                                {{ agent.name }}
                              </option>
                            </select>
                          </div>
                        </div>

                        <!-- Affected Systems -->
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Affected Systems</label>
                          <input
                            v-model="form.affected_systems"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                            placeholder="e.g., CRM, Email Server, Website"
                          />
                        </div>

                        <!-- Tags -->
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Tags</label>
                          <input
                            v-model="tagsInput"
                            @keydown.enter.prevent="addTag"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                            placeholder="Press Enter to add tags"
                          />
                          <div v-if="form.tags.length > 0" class="mt-2 flex flex-wrap gap-2">
                            <span
                              v-for="(tag, index) in form.tags"
                              :key="index"
                              class="inline-flex items-center gap-x-1 rounded-md bg-deep-sea-100 px-2 py-1 text-xs font-medium text-deep-sea-700"
                            >
                              {{ tag }}
                              <button
                                @click="removeTag(index)"
                                type="button"
                                class="group relative -mr-1 h-3.5 w-3.5 rounded-sm hover:bg-deep-sea-200"
                              >
                                <XMarkIcon class="h-3.5 w-3.5" />
                              </button>
                            </span>
                          </div>
                        </div>

                        <!-- AI Suggestions -->
                        <div v-if="aiSuggestions" class="rounded-md bg-blue-50 p-4">
                          <div class="flex">
                            <div class="flex-shrink-0">
                              <LightBulbIcon class="h-5 w-5 text-blue-400" aria-hidden="true" />
                            </div>
                            <div class="ml-3">
                              <h3 class="text-sm font-medium text-blue-800">AI Suggestions</h3>
                              <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-disc space-y-1 pl-5">
                                  <li v-for="suggestion in aiSuggestions" :key="suggestion">
                                    {{ suggestion }}
                                  </li>
                                </ul>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                  <button
                    type="submit"
                    :disabled="isSubmitting"
                    class="inline-flex w-full justify-center rounded-md bg-deep-sea-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-deep-sea-500 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <span v-if="!isSubmitting">Create Incident</span>
                    <span v-else class="flex items-center">
                      <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Creating...
                    </span>
                  </button>
                  <button
                    type="button"
                    @click="$emit('close')"
                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                  >
                    Cancel
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
import { ref, reactive, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { ExclamationTriangleIcon, XMarkIcon, LightBulbIcon } from '@heroicons/vue/24/outline'
import { useMutation, useQuery } from '@tanstack/vue-query'
import { useToast } from '@/composables/useToast'
import api from '@/services/api'
import type { Incident } from '@/types'

const props = defineProps<{
  open: boolean
}>()

const emit = defineEmits<{
  close: () => void
  created: (incident: Incident) => void
}>()

const { showToast } = useToast()

const form = reactive({
  title: '',
  description: '',
  priority: 'medium',
  impact: 'medium',
  category_id: '2',
  assigned_to: '',
  affected_systems: '',
  tags: [] as string[]
})

const tagsInput = ref('')
const isSubmitting = ref(false)
const aiSuggestions = ref<string[]>()

// Fetch available agents
const { data: availableAgents } = useQuery({
  queryKey: ['agents'],
  queryFn: async () => {
    const response = await api.get('/api/v1/users/agents')
    return response.data.data
  }
})

// Watch description for AI suggestions
watch(() => form.description, async (newDescription) => {
  if (newDescription && newDescription.length > 50) {
    try {
      const response = await api.post('/api/v1/ai/incident-suggestions', {
        description: newDescription
      })
      aiSuggestions.value = response.data.suggestions
    } catch (error) {
      // Silently fail for AI suggestions
    }
  }
})

const addTag = () => {
  if (tagsInput.value.trim() && !form.tags.includes(tagsInput.value.trim())) {
    form.tags.push(tagsInput.value.trim())
    tagsInput.value = ''
  }
}

const removeTag = (index: number) => {
  form.tags.splice(index, 1)
}

const createIncidentMutation = useMutation({
  mutationFn: async (data: typeof form) => {
    const response = await api.post('/api/v1/incidents', data)
    return response.data
  },
  onSuccess: (data) => {
    showToast('Incident created successfully', 'success')
    emit('created', data.data)
    emit('close')
    resetForm()
  },
  onError: (error: any) => {
    showToast(error.response?.data?.message || 'Failed to create incident', 'error')
  }
})

const handleSubmit = async () => {
  isSubmitting.value = true
  await createIncidentMutation.mutateAsync(form)
  isSubmitting.value = false
}

const resetForm = () => {
  form.title = ''
  form.description = ''
  form.priority = 'medium'
  form.impact = 'medium'
  form.category_id = '2'
  form.assigned_to = ''
  form.affected_systems = ''
  form.tags = []
  tagsInput.value = ''
  aiSuggestions.value = undefined
}
</script>