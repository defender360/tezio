<template>
  <Menu as="div" class="relative inline-block text-left">
    <div>
      <MenuButton
        class="p-1 rounded-full text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        <EllipsisVerticalIcon class="h-5 w-5" />
      </MenuButton>
    </div>

    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <MenuItems
        class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
      >
        <div class="py-1">
          <MenuItem v-if="notification.status === 'unread'" v-slot="{ active }">
            <button
              @click="$emit('mark-as-read', notification.id)"
              :class="[
                active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                'group flex items-center px-4 py-2 text-sm w-full',
              ]"
            >
              <CheckIcon class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" />
              Mark as read
            </button>
          </MenuItem>

          <MenuItem v-if="notification.status === 'read'" v-slot="{ active }">
            <button
              @click="$emit('mark-as-unread', notification.id)"
              :class="[
                active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                'group flex items-center px-4 py-2 text-sm w-full',
              ]"
            >
              <XMarkIcon class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" />
              Mark as unread
            </button>
          </MenuItem>

          <MenuItem v-slot="{ active }">
            <button
              @click="$emit('archive', notification.id)"
              :class="[
                active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                'group flex items-center px-4 py-2 text-sm w-full',
              ]"
            >
              <ArchiveBoxIcon class="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500" />
              Archive
            </button>
          </MenuItem>

          <div class="border-t border-gray-100"></div>

          <MenuItem v-slot="{ active }">
            <button
              @click="$emit('delete', notification.id)"
              :class="[
                active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                'group flex items-center px-4 py-2 text-sm w-full text-red-600',
              ]"
            >
              <TrashIcon class="mr-3 h-5 w-5 text-red-400 group-hover:text-red-500" />
              Delete
            </button>
          </MenuItem>
        </div>
      </MenuItems>
    </Transition>
  </Menu>
</template>

<script setup lang="ts">
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import {
  EllipsisVerticalIcon,
  CheckIcon,
  XMarkIcon,
  ArchiveBoxIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'

interface Props {
  notification: {
    id: string
    status: string
  }
}

defineProps<Props>()

defineEmits<{
  'mark-as-read': [id: string]
  'mark-as-unread': [id: string]
  archive: [id: string]
  delete: [id: string]
}>()
</script>