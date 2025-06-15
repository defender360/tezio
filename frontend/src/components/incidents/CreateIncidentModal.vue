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
                      class="mt-1 input-field"
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
                      class="mt-1 input-field"
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
                        class="mt-1 input-field"
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
                        class="mt-1 input-field"
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
                      class="mt-1 input-field"
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