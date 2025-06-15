<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <p class="text-sm font-medium text-gray-600">{{ title }}</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">
          <span v-if="loading" class="animate-pulse bg-gray-200 rounded w-24 h-8 inline-block"></span>
          <span v-else>{{ formattedValue }}</span>
        </p>
        <div v-if="previousValue !== null && !loading" class="mt-2 flex items-center text-sm">
          <span 
            :class="[
              'flex items-center font-medium',
              trend > 0 ? 'text-green-600' : trend < 0 ? 'text-red-600' : 'text-gray-500'
            ]"
          >
            <TrendingUpIcon v-if="trend > 0" class="w-4 h-4 mr-1" />
            <TrendingDownIcon v-else-if="trend < 0" class="w-4 h-4 mr-1" />
            <MinusIcon v-else class="w-4 h-4 mr-1" />
            {{ Math.abs(trend) }}%
          </span>
          <span class="ml-2 text-gray-500">vs previous period</span>
        </div>
      </div>
      <div v-if="icon" class="ml-4">
        <component 
          :is="iconComponent" 
          class="w-8 h-8 text-gray-400"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { 
  TrendingUpIcon, 
  TrendingDownIcon, 
  MinusIcon,
  TicketIcon,
  CheckCircleIcon,
  ClockIcon,
  EmojiHappyIcon,
  ShieldCheckIcon,
  CurrencyDollarIcon
} from '@heroicons/vue/outline'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: Number,
    required: true
  },
  previousValue: {
    type: Number,
    default: null
  },
  format: {
    type: String,
    default: 'number', // number, percentage, currency, hours
    validator: (value) => ['number', 'percentage', 'currency', 'hours'].includes(value)
  },
  icon: {
    type: String,
    default: null
  },
  trend: {
    type: Number,
    default: 0
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const formattedValue = computed(() => {
  switch (props.format) {
    case 'percentage':
      return `${props.value}%`
    case 'currency':
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(props.value)
    case 'hours':
      if (props.value < 24) {
        return `${props.value.toFixed(1)}h`
      } else {
        return `${(props.value / 24).toFixed(1)}d`
      }
    default:
      return new Intl.NumberFormat().format(props.value)
  }
})

const iconComponent = computed(() => {
  const iconMap = {
    'TicketIcon': TicketIcon,
    'CheckCircleIcon': CheckCircleIcon,
    'ClockIcon': ClockIcon,
    'EmojiHappyIcon': EmojiHappyIcon,
    'ShieldCheckIcon': ShieldCheckIcon,
    'CurrencyDollarIcon': CurrencyDollarIcon
  }
  return iconMap[props.icon] || null
})
</script>