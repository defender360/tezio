<template>
  <TransitionRoot as="template" :show="open">
    <Dialog as="div" class="relative z-10" @close="$emit('close')">
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

      <div class="fixed inset-0 z-10 overflow-y-auto">
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
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
              <div>
                <div class="mt-3 text-center sm:mt-5">
                  <DialogTitle as="h3" class="text-lg font-semibold leading-6 text-gray-900">
                    {{ skill?.name || 'Skill Details' }}
                  </DialogTitle>
                  <div class="mt-2">
                    <p class="text-sm text-gray-500">
                      {{ skill?.description || 'No description available' }}
                    </p>
                  </div>
                </div>
              </div>
              
              <div v-if="skill" class="mt-5">
                <h4 class="text-sm font-medium text-gray-900">Team Members with this skill:</h4>
                <ul class="mt-2 divide-y divide-gray-100">
                  <li v-for="member in skill.members" :key="member.id" class="py-2">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center">
                        <img
                          :src="member.avatar || `https://ui-avatars.com/api/?name=${member.name}`"
                          :alt="member.name"
                          class="h-8 w-8 rounded-full"
                        >
                        <span class="ml-3 text-sm font-medium text-gray-900">{{ member.name }}</span>
                      </div>
                      <span class="text-sm text-gray-500">Level {{ member.level }}</span>
                    </div>
                  </li>
                </ul>
              </div>

              <div class="mt-5 sm:mt-6">
                <button
                  type="button"
                  class="inline-flex w-full justify-center rounded-md bg-deep-sea-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-deep-sea-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-deep-sea-600"
                  @click="$emit('close')"
                >
                  Close
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'

interface Skill {
  id: string
  name: string
  description?: string
  members: Array<{
    id: string
    name: string
    avatar?: string
    level: number
  }>
}

interface Props {
  open: boolean
  skill?: Skill
}

defineProps<Props>()

defineEmits<{
  close: []
}>()
</script>