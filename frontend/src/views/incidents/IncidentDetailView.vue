<template>
  <div class="py-6">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="min-w-0 flex-1">
          <div class="flex items-center">
            <button
              @click="router.back()"
              class="mr-4 text-gray-400 hover:text-gray-500"
            >
              <ArrowLeftIcon class="h-5 w-5" />
            </button>
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
              Incident #{{ incidentId }}
            </h2>
          </div>
        </div>
        <div class="mt-4 flex md:ml-4 md:mt-0 space-x-3">
          <button
            @click="showEditModal = true"
            type="button"
            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
          >
            <PencilIcon class="-ml-0.5 mr-1.5 h-4 w-4" />
            Edit
          </button>
          <button
            v-if="incident?.status !== 'resolved' && incident?.status !== 'closed'"
            @click="resolveIncident"
            type="button"
            class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500"
          >
            <CheckCircleIcon class="-ml-0.5 mr-1.5 h-4 w-4" />
            Resolve
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-deep-sea-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="rounded-md bg-red-50 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <XCircleIcon class="h-5 w-5 text-red-400" aria-hidden="true" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Error loading incident</h3>
            <div class="mt-2 text-sm text-red-700">
              <p>{{ error.message }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Incident Content -->
      <div v-else-if="incident" class="space-y-6">
        <!-- Main Info Card -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
          <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              {{ incident.title }}
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
              Created {{ formatDate(incident.created_at) }}
            </p>
          </div>
          <div class="border-t border-gray-200">
            <dl>
              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Status</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  <span :class="[getStatusColor(incident.status), 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium']">
                    {{ incident.status }}
                  </span>
                </dd>
              </div>
              <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Priority</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  <span :class="[getPriorityColor(incident.priority), 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium']">
                    {{ incident.priority }}
                  </span>
                </dd>
              </div>
              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Impact</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ incident.impact }}
                </dd>
              </div>
              <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Category</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ incident.category?.name || 'Uncategorized' }}
                </dd>
              </div>
              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Assigned to</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  <div class="flex items-center">
                    <img
                      v-if="incident.assignee"
                      class="h-8 w-8 rounded-full"
                      :src="incident.assignee.avatar || 'https://ui-avatars.com/api/?name=' + incident.assignee.name"
                      :alt="incident.assignee.name"
                    />
                    <span class="ml-2">{{ incident.assignee?.name || 'Unassigned' }}</span>
                  </div>
                </dd>
              </div>
              <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Description</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  <p class="whitespace-pre-wrap">{{ incident.description }}</p>
                </dd>
              </div>
              <div v-if="incident.affected_systems" class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Affected Systems</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {{ incident.affected_systems }}
                </dd>
              </div>
              <div v-if="incident.tags?.length" class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Tags</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  <div class="flex flex-wrap gap-2">
                    <span
                      v-for="tag in incident.tags"
                      :key="tag"
                      class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600"
                    >
                      {{ tag }}
                    </span>
                  </div>
                </dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Timeline / Comments -->
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              Activity Timeline
            </h3>
          </div>
          <div class="border-t border-gray-200">
            <div class="px-4 py-5 sm:px-6">
              <!-- Comments List -->
              <div v-if="comments?.length" class="space-y-4">
                <div v-for="comment in comments" :key="comment.id" class="flex space-x-3">
                  <img
                    class="h-8 w-8 rounded-full"
                    :src="comment.user.avatar || 'https://ui-avatars.com/api/?name=' + comment.user.name"
                    :alt="comment.user.name"
                  />
                  <div class="flex-1">
                    <div class="flex items-center justify-between">
                      <h4 class="text-sm font-medium text-gray-900">{{ comment.user.name }}</h4>
                      <p class="text-sm text-gray-500">{{ formatDate(comment.created_at) }}</p>
                    </div>
                    <p class="mt-1 text-sm text-gray-700">{{ comment.content }}</p>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-4 text-gray-500">
                No comments yet
              </div>

              <!-- Add Comment Form -->
              <div class="mt-6">
                <form @submit.prevent="addComment" class="flex space-x-3">
                  <img
                    class="h-8 w-8 rounded-full"
                    :src="currentUser?.avatar || 'https://ui-avatars.com/api/?name=' + currentUser?.name"
                    :alt="currentUser?.name"
                  />
                  <div class="flex-1">
                    <textarea
                      v-model="newComment"
                      rows="3"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-deep-sea-500 focus:ring-deep-sea-500 sm:text-sm"
                      placeholder="Add a comment..."
                    />
                    <div class="mt-3 flex justify-end">
                      <button
                        type="submit"
                        :disabled="!newComment.trim() || isAddingComment"
                        class="inline-flex items-center rounded-md bg-deep-sea-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-deep-sea-500 disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        <span v-if="!isAddingComment">Add Comment</span>
                        <span v-else>Adding...</span>
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <!-- Related Incidents -->
        <div v-if="relatedIncidents?.length" class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              Related Incidents
            </h3>
          </div>
          <div class="border-t border-gray-200">
            <ul class="divide-y divide-gray-200">
              <li v-for="related in relatedIncidents" :key="related.id" class="px-4 py-4 sm:px-6">
                <router-link
                  :to="{ name: 'incident-detail', params: { id: related.id } }"
                  class="flex items-center justify-between hover:bg-gray-50"
                >
                  <div>
                    <p class="text-sm font-medium text-deep-sea-600 truncate">
                      #{{ related.id }} - {{ related.title }}
                    </p>
                    <p class="text-sm text-gray-500">
                      {{ related.status }} • {{ formatDate(related.created_at) }}
                    </p>
                  </div>
                  <ChevronRightIcon class="h-5 w-5 text-gray-400" />
                </router-link>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import {
  ArrowLeftIcon,
  PencilIcon,
  CheckCircleIcon,
  XCircleIcon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import { formatDate } from '@/utils/date'
import type { Incident, IncidentComment } from '@/types'

const route = useRoute()
const router = useRouter()
const queryClient = useQueryClient()
const { showToast } = useToast()
const authStore = useAuthStore()

const incidentId = computed(() => route.params.id as string)
const currentUser = computed(() => authStore.user)
const showEditModal = ref(false)
const newComment = ref('')
const isAddingComment = ref(false)

// Fetch incident details
const { data: incident, isLoading, error } = useQuery({
  queryKey: ['incident', incidentId.value],
  queryFn: async () => {
    const response = await api.get(`/api/v1/incidents/${incidentId.value}`)
    return response.data.data
  }
})

// Fetch comments
const { data: comments } = useQuery({
  queryKey: ['incident-comments', incidentId.value],
  queryFn: async () => {
    const response = await api.get(`/api/v1/incidents/${incidentId.value}/comments`)
    return response.data.data
  },
  enabled: () => !!incident.value
})

// Fetch related incidents
const { data: relatedIncidents } = useQuery({
  queryKey: ['related-incidents', incidentId.value],
  queryFn: async () => {
    const response = await api.get(`/api/v1/incidents/${incidentId.value}/related`)
    return response.data.data
  },
  enabled: () => !!incident.value
})

// Add comment mutation
const addCommentMutation = useMutation({
  mutationFn: async (content: string) => {
    const response = await api.post(`/api/v1/incidents/${incidentId.value}/comments`, {
      content
    })
    return response.data
  },
  onSuccess: () => {
    newComment.value = ''
    queryClient.invalidateQueries({ queryKey: ['incident-comments', incidentId.value] })
    showToast('Comment added successfully', 'success')
  },
  onError: (error: any) => {
    showToast(error.response?.data?.message || 'Failed to add comment', 'error')
  }
})

// Resolve incident mutation
const resolveIncidentMutation = useMutation({
  mutationFn: async () => {
    const response = await api.post(`/api/v1/incidents/${incidentId.value}/resolve`)
    return response.data
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['incident', incidentId.value] })
    showToast('Incident resolved successfully', 'success')
  },
  onError: (error: any) => {
    showToast(error.response?.data?.message || 'Failed to resolve incident', 'error')
  }
})

const addComment = async () => {
  if (!newComment.value.trim()) return
  
  isAddingComment.value = true
  await addCommentMutation.mutateAsync(newComment.value)
  isAddingComment.value = false
}

const resolveIncident = async () => {
  if (confirm('Are you sure you want to resolve this incident?')) {
    await resolveIncidentMutation.mutateAsync()
  }
}

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    open: 'bg-red-100 text-red-800',
    'in-progress': 'bg-yellow-100 text-yellow-800',
    resolved: 'bg-green-100 text-green-800',
    closed: 'bg-gray-100 text-gray-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const getPriorityColor = (priority: string) => {
  const colors: Record<string, string> = {
    critical: 'bg-red-100 text-red-800',
    high: 'bg-orange-100 text-orange-800',
    medium: 'bg-yellow-100 text-yellow-800',
    low: 'bg-green-100 text-green-800'
  }
  return colors[priority] || 'bg-gray-100 text-gray-800'
}
</script>