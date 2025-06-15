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
                      <ServerIcon class="h-6 w-6 text-deep-sea-600" aria-hidden="true" />
                    </div>
                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                      <DialogTitle as="h3" class="text-base font-semibold leading-6 text-gray-900">
                        Add Configuration Item
                      </DialogTitle>
                      
                      <div class="mt-4 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                          <!-- Name -->
                          <div>
                            <label class="block text-sm font-medium text-gray-700">Name *</label>
                            <input
                              v-model="form.name"
                              type="text"
                              required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                              placeholder="e.g., Web Server 01"
                            />
                          </div>

                          <!-- Type -->
                          <div>
                            <label class="block text-sm font-medium text-gray-700">Type *</label>
                            <select
                              v-model="form.type"
                              required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                            >
                              <option value="server">Server</option>
                              <option value="workstation">Workstation</option>
                              <option value="laptop">Laptop</option>
                              <option value="network_device">Network Device</option>
                              <option value="printer">Printer</option>
                              <option value="software">Software</option>
                              <option value="license">License</option>
                              <option value="virtual_machine">Virtual Machine</option>
                              <option value="mobile_device">Mobile Device</option>
                              <option value="other">Other</option>
                            </select>
                          </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                          <!-- Status -->
                          <div>
                            <label class="block text-sm font-medium text-gray-700">Status *</label>
                            <select
                              v-model="form.status"
                              required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                            >
                              <option value="active">Active</option>
                              <option value="inactive">Inactive</option>
                              <option value="maintenance">Under Maintenance</option>
                              <option value="retired">Retired</option>
                              <option value="disposed">Disposed</option>
                            </select>
                          </div>

                          <!-- Location -->
                          <div>
                            <label class="block text-sm font-medium text-gray-700">Location</label>
                            <input
                              v-model="form.location"
                              type="text"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                              placeholder="e.g., Data Center A, Rack 12"
                            />
                          </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                          <!-- Serial Number -->
                          <div>
                            <label class="block text-sm font-medium text-gray-700">Serial Number</label>
                            <input
                              v-model="form.serial_number"
                              type="text"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                              placeholder="e.g., SN123456789"
                            />
                          </div>

                          <!-- Asset Tag -->
                          <div>
                            <label class="block text-sm font-medium text-gray-700">Asset Tag</label>
                            <input
                              v-model="form.asset_tag"
                              type="text"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                              placeholder="e.g., IT-2024-001"
                            />
                          </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                          <!-- Manufacturer -->
                          <div>
                            <label class="block text-sm font-medium text-gray-700">Manufacturer</label>
                            <input
                              v-model="form.manufacturer"
                              type="text"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                              placeholder="e.g., Dell, HP, Cisco"
                            />
                          </div>

                          <!-- Model -->
                          <div>
                            <label class="block text-sm font-medium text-gray-700">Model</label>
                            <input
                              v-model="form.model"
                              type="text"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                              placeholder="e.g., PowerEdge R750"
                            />
                          </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                          <!-- Owner -->
                          <div>
                            <label class="block text-sm font-medium text-gray-700">Owner</label>
                            <select
                              v-model="form.owner_id"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                            >
                              <option value="">Select owner</option>
                              <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                                {{ user.name }}
                              </option>
                            </select>
                          </div>

                          <!-- Department -->
                          <div>
                            <label class="block text-sm font-medium text-gray-700">Department</label>
                            <select
                              v-model="form.department_id"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                            >
                              <option value="">Select department</option>
                              <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                {{ dept.name }}
                              </option>
                            </select>
                          </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                          <!-- Purchase Date -->
                          <div>
                            <label class="block text-sm font-medium text-gray-700">Purchase Date</label>
                            <input
                              v-model="form.purchase_date"
                              type="date"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                            />
                          </div>

                          <!-- Warranty Expiry -->
                          <div>
                            <label class="block text-sm font-medium text-gray-700">Warranty Expiry</label>
                            <input
                              v-model="form.warranty_expiry"
                              type="date"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                            />
                          </div>
                        </div>

                        <!-- Description -->
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Description</label>
                          <textarea
                            v-model="form.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                            placeholder="Additional details about this configuration item"
                          />
                        </div>

                        <!-- Technical Specs (conditional) -->
                        <div v-if="showTechnicalSpecs" class="space-y-4 border-t pt-4">
                          <h4 class="text-sm font-medium text-gray-900">Technical Specifications</h4>
                          
                          <div class="grid grid-cols-2 gap-4">
                            <div>
                              <label class="block text-sm font-medium text-gray-700">IP Address</label>
                              <input
                                v-model="form.ip_address"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                                placeholder="192.168.1.100"
                              />
                            </div>
                            <div>
                              <label class="block text-sm font-medium text-gray-700">MAC Address</label>
                              <input
                                v-model="form.mac_address"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                                placeholder="00:1B:44:11:3A:B7"
                              />
                            </div>
                          </div>

                          <div class="grid grid-cols-3 gap-4">
                            <div>
                              <label class="block text-sm font-medium text-gray-700">Operating System</label>
                              <input
                                v-model="form.operating_system"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                                placeholder="Ubuntu 22.04"
                              />
                            </div>
                            <div>
                              <label class="block text-sm font-medium text-gray-700">RAM (GB)</label>
                              <input
                                v-model.number="form.ram_size"
                                type="number"
                                min="0"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                                placeholder="16"
                              />
                            </div>
                            <div>
                              <label class="block text-sm font-medium text-gray-700">Storage (GB)</label>
                              <input
                                v-model.number="form.storage_size"
                                type="number"
                                min="0"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                                placeholder="500"
                              />
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
                    <span v-if="!isSubmitting">Create Item</span>
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
import { ref, reactive, computed } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { ServerIcon } from '@heroicons/vue/24/outline'
import { useMutation, useQuery } from '@tanstack/vue-query'
import { useToast } from '@/composables/useToast'
import api from '@/services/api'
import type { ConfigurationItem } from '@/types'

const props = defineProps<{
  open: boolean
}>()

const emit = defineEmits<{
  close: () => void
  created: (item: ConfigurationItem) => void
}>()

const { showToast } = useToast()

const form = reactive({
  name: '',
  type: 'server',
  status: 'active',
  serial_number: '',
  asset_tag: '',
  manufacturer: '',
  model: '',
  location: '',
  description: '',
  purchase_date: '',
  warranty_expiry: '',
  owner_id: '',
  department_id: '',
  ip_address: '',
  mac_address: '',
  operating_system: '',
  cpu_info: '',
  ram_size: null as number | null,
  storage_size: null as number | null
})

const isSubmitting = ref(false)

// Show technical specs for certain types
const showTechnicalSpecs = computed(() => {
  return ['server', 'workstation', 'laptop', 'network_device', 'virtual_machine'].includes(form.type)
})

// Fetch available users
const { data: availableUsers } = useQuery({
  queryKey: ['users'],
  queryFn: async () => {
    const response = await api.get('/api/v1/users')
    return response.data.data
  }
})

// Fetch departments
const { data: departments } = useQuery({
  queryKey: ['departments'],
  queryFn: async () => {
    const response = await api.get('/api/v1/departments')
    return response.data.data
  }
})

const createItemMutation = useMutation({
  mutationFn: async (data: typeof form) => {
    const response = await api.post('/api/v1/configuration-items', data)
    return response.data
  },
  onSuccess: (data) => {
    showToast('Configuration item created successfully', 'success')
    emit('created', data.data)
    emit('close')
    resetForm()
  },
  onError: (error: any) => {
    showToast(error.response?.data?.message || 'Failed to create configuration item', 'error')
  }
})

const handleSubmit = async () => {
  isSubmitting.value = true
  await createItemMutation.mutateAsync(form)
  isSubmitting.value = false
}

const resetForm = () => {
  Object.assign(form, {
    name: '',
    type: 'server',
    status: 'active',
    serial_number: '',
    asset_tag: '',
    manufacturer: '',
    model: '',
    location: '',
    description: '',
    purchase_date: '',
    warranty_expiry: '',
    owner_id: '',
    department_id: '',
    ip_address: '',
    mac_address: '',
    operating_system: '',
    cpu_info: '',
    ram_size: null,
    storage_size: null
  })
}
</script>