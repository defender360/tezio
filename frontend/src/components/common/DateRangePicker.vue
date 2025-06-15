<template>
  <div class="relative">
    <button
      @click="toggleDropdown"
      class="flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
    >
      <CalendarIcon class="w-5 h-5 mr-2 text-gray-400" />
      <span class="text-sm font-medium text-gray-700">
        {{ displayText }}
      </span>
      <ChevronDownIcon class="w-4 h-4 ml-2 text-gray-400" />
    </button>

    <div
      v-if="isOpen"
      v-click-outside="closeDropdown"
      class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
    >
      <div class="p-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-medium text-gray-900">Select Date Range</h3>
          <button
            @click="closeDropdown"
            class="text-gray-400 hover:text-gray-500"
          >
            <XIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Quick Presets -->
        <div class="mb-4">
          <div class="grid grid-cols-2 gap-2">
            <button
              v-for="preset in presets"
              :key="preset.label"
              @click="selectPreset(preset)"
              class="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              {{ preset.label }}
            </button>
          </div>
        </div>

        <!-- Custom Date Selection -->
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Start Date
            </label>
            <input
              v-model="localStartDate"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              End Date
            </label>
            <input
              v-model="localEndDate"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-2">
          <button
            @click="closeDropdown"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            Cancel
          </button>
          <button
            @click="applyDateRange"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            Apply
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { CalendarIcon, ChevronDownIcon, XIcon } from '@heroicons/vue/outline'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      startDate: new Date(new Date().setMonth(new Date().getMonth() - 1)),
      endDate: new Date()
    })
  }
})

const emit = defineEmits(['update:modelValue', 'change'])

// State
const isOpen = ref(false)
const localStartDate = ref('')
const localEndDate = ref('')

// Presets
const presets = [
  {
    label: 'Last 7 days',
    getValue: () => ({
      startDate: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000),
      endDate: new Date()
    })
  },
  {
    label: 'Last 30 days',
    getValue: () => ({
      startDate: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000),
      endDate: new Date()
    })
  },
  {
    label: 'Last 3 months',
    getValue: () => ({
      startDate: new Date(new Date().setMonth(new Date().getMonth() - 3)),
      endDate: new Date()
    })
  },
  {
    label: 'Last 6 months',
    getValue: () => ({
      startDate: new Date(new Date().setMonth(new Date().getMonth() - 6)),
      endDate: new Date()
    })
  },
  {
    label: 'This month',
    getValue: () => ({
      startDate: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
      endDate: new Date()
    })
  },
  {
    label: 'Last month',
    getValue: () => {
      const lastMonth = new Date()
      lastMonth.setMonth(lastMonth.getMonth() - 1)
      return {
        startDate: new Date(lastMonth.getFullYear(), lastMonth.getMonth(), 1),
        endDate: new Date(lastMonth.getFullYear(), lastMonth.getMonth() + 1, 0)
      }
    }
  }
]

// Computed
const displayText = computed(() => {
  if (!props.modelValue.startDate || !props.modelValue.endDate) {
    return 'Select date range'
  }
  
  const start = new Date(props.modelValue.startDate)
  const end = new Date(props.modelValue.endDate)
  
  const formatOptions = { month: 'short', day: 'numeric', year: 'numeric' }
  return `${start.toLocaleDateString('en-US', formatOptions)} - ${end.toLocaleDateString('en-US', formatOptions)}`
})

// Methods
const toggleDropdown = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    updateLocalDates()
  }
}

const closeDropdown = () => {
  isOpen.value = false
}

const updateLocalDates = () => {
  if (props.modelValue.startDate) {
    localStartDate.value = formatDateForInput(props.modelValue.startDate)
  }
  if (props.modelValue.endDate) {
    localEndDate.value = formatDateForInput(props.modelValue.endDate)
  }
}

const formatDateForInput = (date) => {
  const d = new Date(date)
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const selectPreset = (preset) => {
  const range = preset.getValue()
  emit('update:modelValue', range)
  emit('change', range)
  closeDropdown()
}

const applyDateRange = () => {
  const range = {
    startDate: new Date(localStartDate.value),
    endDate: new Date(localEndDate.value)
  }
  
  // Validate dates
  if (range.startDate > range.endDate) {
    alert('Start date must be before end date')
    return
  }
  
  emit('update:modelValue', range)
  emit('change', range)
  closeDropdown()
}

// Watch for external changes
watch(() => props.modelValue, () => {
  updateLocalDates()
}, { deep: true })
</script>