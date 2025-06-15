<template>
  <Listbox v-model="selected">
    <div class="relative">
      <ListboxButton class="relative w-full cursor-default rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-left shadow-sm focus:border-tech-horizon-500 focus:outline-none focus:ring-1 focus:ring-tech-horizon-500 sm:text-sm">
        <span class="block truncate">{{ selected?.name || placeholder }}</span>
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
          <ChevronUpDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
        </span>
      </ListboxButton>

      <transition
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
          <ListboxOption
            v-for="user in users"
            :key="user.id"
            :value="user"
            v-slot="{ active, selected }"
            class="relative cursor-default select-none py-2 pl-10 pr-4"
            :class="[active ? 'bg-tech-horizon-100 text-tech-horizon-900' : 'text-gray-900']"
          >
            <span :class="[selected ? 'font-medium' : 'font-normal', 'block truncate']">
              {{ user.name }}
            </span>
            <span v-if="selected" class="absolute inset-y-0 left-0 flex items-center pl-3 text-tech-horizon-600">
              <CheckIcon class="h-5 w-5" aria-hidden="true" />
            </span>
          </ListboxOption>
        </ListboxOptions>
      </transition>
    </div>
  </Listbox>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { Listbox, ListboxButton, ListboxOptions, ListboxOption } from '@headlessui/vue'
import { CheckIcon, ChevronUpDownIcon } from '@heroicons/vue/20/solid'

interface User {
  id: string
  name: string
}

interface Props {
  modelValue?: string
  users: User[]
  placeholder?: string
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select user...'
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const selected = ref<User | undefined>(
  props.users.find(u => u.id === props.modelValue)
)

watch(selected, (newUser) => {
  emit('update:modelValue', newUser?.id || '')
})

watch(() => props.modelValue, (newValue) => {
  selected.value = props.users.find(u => u.id === newValue)
})
</script>