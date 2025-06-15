<template>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Incident
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Priority
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Status
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Assigned To
          </th>
          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            Created
          </th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <tr
          v-for="incident in incidents"
          :key="incident.id"
          class="hover:bg-gray-50 cursor-pointer transition-colors"
          @click="$router.push(`/incidents/${incident.id}`)"
        >
          <td class="px-6 py-4 whitespace-nowrap">
            <div>
              <div class="text-sm font-medium text-gray-900">
                {{ incident.title }}
              </div>
              <div class="text-sm text-gray-500">
                #{{ incident.number }}
              </div>
            </div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <PriorityBadge :priority="incident.priority" />
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <StatusBadge :status="incident.status" />
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            {{ incident.assigned_user?.name || 'Unassigned' }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            <TimeAgo :date="incident.created_at" />
          </td>
        </tr>
      </tbody>
    </table>
    
    <div v-if="incidents.length === 0" class="text-center py-8 text-gray-500">
      No recent incidents
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Incident } from '@/types'
import PriorityBadge from '@/components/common/PriorityBadge.vue'
import StatusBadge from '@/components/common/StatusBadge.vue'
import TimeAgo from '@/components/common/TimeAgo.vue'

interface Props {
  incidents: Incident[]
}

defineProps<Props>()
</script>