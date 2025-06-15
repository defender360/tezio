<template>
  <div class="p-6 max-w-4xl mx-auto">
    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center py-12">
      <LoadingSpinner />
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
      <p class="text-red-800">Error loading article: {{ error.message }}</p>
    </div>

    <!-- Article Content -->
    <div v-else-if="article">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
          <router-link
            to="/knowledge"
            class="text-gray-500 hover:text-gray-700 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </router-link>
          <span
            :class="[
              'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
              getCategoryColor(article.category)
            ]"
          >
            {{ article.category }}
          </span>
          <span
            :class="[
              'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
              getStatusColor(article.status)
            ]"
          >
            {{ article.status }}
          </span>
        </div>
        
        <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ article.title }}</h1>
        
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-6 text-sm text-gray-600">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <span>{{ article.author.name }}</span>
            </div>
            
            <div class="flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>Last updated <TimeAgo :date="article.updatedAt" /></span>
            </div>
            
            <div class="flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <span>{{ article.views || 0 }} views</span>
            </div>
          </div>
          
          <div class="flex items-center gap-2">
            <button
              @click="bookmarkArticle"
              :class="[
                'p-2 rounded-md transition-colors',
                article.isBookmarked
                  ? 'bg-yellow-100 text-yellow-600'
                  : 'bg-gray-100 text-gray-400 hover:text-gray-600'
              ]"
              :title="article.isBookmarked ? 'Remove bookmark' : 'Bookmark article'"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
              </svg>
            </button>
            
            <button
              @click="shareArticle"
              class="p-2 rounded-md bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors"
              title="Share article"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
              </svg>
            </button>
            
            <router-link
              v-if="canEdit"
              :to="`/knowledge/articles/${article.id}/edit`"
              class="p-2 rounded-md bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors"
              title="Edit article"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </router-link>
          </div>
        </div>
        
        <!-- Summary -->
        <div v-if="article.summary" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
          <h2 class="text-sm font-medium text-blue-900 mb-2">Summary</h2>
          <p class="text-blue-800">{{ article.summary }}</p>
        </div>
      </div>

      <!-- Article Content -->
      <div class="prose prose-lg max-w-none mb-8">
        <div v-html="article.content"></div>
      </div>

      <!-- Tags -->
      <div v-if="article.tags?.length > 0" class="mb-8">
        <h3 class="text-sm font-medium text-gray-700 mb-2">Tags</h3>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="tag in article.tags"
            :key="tag"
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
          >
            {{ tag }}
          </span>
        </div>
      </div>

      <!-- Rating Section -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Rate this article</h3>
        
        <div class="flex items-center gap-4 mb-4">
          <div class="flex items-center gap-1">
            <button
              v-for="star in 5"
              :key="star"
              @click="rateArticle(star)"
              :class="[
                'w-8 h-8 transition-colors',
                star <= (userRating || 0)
                  ? 'text-yellow-400 hover:text-yellow-500'
                  : 'text-gray-300 hover:text-gray-400'
              ]"
            >
              <svg
                :class="[
                  'w-full h-full',
                  star <= (userRating || 0) ? 'fill-current' : ''
                ]"
                viewBox="0 0 20 20"
              >
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
            </button>
          </div>
          <span class="text-sm text-gray-600">
            {{ userRating ? `You rated this ${userRating} star${userRating > 1 ? 's' : ''}` : 'Click to rate' }}
          </span>
        </div>
        
        <div class="flex items-center gap-2 text-sm text-gray-600">
          <div class="flex items-center">
            <svg
              v-for="star in 5"
              :key="star"
              :class="[
                'w-4 h-4',
                star <= (article.averageRating || 0)
                  ? 'text-yellow-400 fill-current'
                  : 'text-gray-300'
              ]"
              viewBox="0 0 20 20"
            >
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
          </div>
          <span>{{ article.averageRating || 0 }}/5 ({{ article.ratingsCount || 0 }} ratings)</span>
        </div>
      </div>

      <!-- Comments Section -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Comments</h3>
        
        <!-- Add Comment Form -->
        <form @submit.prevent="addComment" class="mb-6">
          <textarea
            v-model="newComment"
            rows="3"
            placeholder="Add a comment..."
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 mb-3"
            required
          ></textarea>
          <button
            type="submit"
            :disabled="isAddingComment"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50"
          >
            Add Comment
          </button>
        </form>
        
        <!-- Comments List -->
        <div v-if="comments?.length > 0" class="space-y-4">
          <div v-for="comment in comments" :key="comment.id" class="flex gap-3">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
            </div>
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <span class="font-medium text-gray-900">{{ comment.author.name }}</span>
                <span class="text-sm text-gray-500">
                  <TimeAgo :date="comment.createdAt" />
                </span>
              </div>
              <p class="text-gray-700 whitespace-pre-wrap">{{ comment.content }}</p>
            </div>
          </div>
        </div>
        
        <div v-else class="text-center py-8 text-gray-500">
          <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <p>No comments yet. Be the first to comment!</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { useToast } from 'vue-toastification'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import TimeAgo from '@/components/common/TimeAgo.vue'
import { api } from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const toast = useToast()
const queryClient = useQueryClient()
const authStore = useAuthStore()

const articleId = route.params.id as string

// State
const newComment = ref('')
const userRating = ref(0)
const isAddingComment = ref(false)

// Queries
const { data: article, isLoading, error } = useQuery({
  queryKey: ['article', articleId],
  queryFn: async () => {
    const response = await api.get(`/knowledge/articles/${articleId}`)
    return response.data
  }
})

const { data: comments } = useQuery({
  queryKey: ['article-comments', articleId],
  queryFn: async () => {
    const response = await api.get(`/knowledge/articles/${articleId}/comments`)
    return response.data
  }
})

// Computed
const canEdit = computed(() => {
  if (!article.value || !authStore.user) return false
  return article.value.author.id === authStore.user.id || authStore.user.role === 'admin'
})

// Mutations
const ratingMutation = useMutation({
  mutationFn: async (rating: number) => {
    const response = await api.post(`/knowledge/articles/${articleId}/rate`, { rating })
    return response.data
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['article', articleId] })
    toast.success('Rating submitted')
  },
  onError: (error: any) => {
    toast.error(error.response?.data?.message || 'Failed to submit rating')
  }
})

const commentMutation = useMutation({
  mutationFn: async (content: string) => {
    const response = await api.post(`/knowledge/articles/${articleId}/comments`, { content })
    return response.data
  },
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ['article-comments', articleId] })
    newComment.value = ''
    toast.success('Comment added')
  },
  onError: (error: any) => {
    toast.error(error.response?.data?.message || 'Failed to add comment')
  }
})

// Methods
const getCategoryColor = (category: string) => {
  const colors: Record<string, string> = {
    troubleshooting: 'bg-red-100 text-red-800',
    procedures: 'bg-blue-100 text-blue-800',
    faq: 'bg-green-100 text-green-800',
    documentation: 'bg-purple-100 text-purple-800',
    solutions: 'bg-yellow-100 text-yellow-800'
  }
  return colors[category] || 'bg-gray-100 text-gray-800'
}

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    published: 'bg-green-100 text-green-800',
    draft: 'bg-gray-100 text-gray-800',
    under_review: 'bg-yellow-100 text-yellow-800',
    archived: 'bg-red-100 text-red-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const rateArticle = (rating: number) => {
  userRating.value = rating
  ratingMutation.mutate(rating)
}

const addComment = () => {
  if (!newComment.value.trim()) return
  commentMutation.mutate(newComment.value.trim())
}

const bookmarkArticle = async () => {
  try {
    await api.post(`/knowledge/articles/${articleId}/bookmark`)
    // Update the article's bookmarked status locally
    if (article.value) {
      article.value.isBookmarked = !article.value.isBookmarked
    }
    toast.success(article.value?.isBookmarked ? 'Article bookmarked' : 'Bookmark removed')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to bookmark article')
  }
}

const shareArticle = () => {
  const url = window.location.href
  navigator.clipboard.writeText(url).then(() => {
    toast.success('Article link copied to clipboard')
  }).catch(() => {
    toast.error('Failed to copy link')
  })
}
</script>

<style scoped>
.prose {
  @apply text-gray-900;
}

.prose h1 {
  @apply text-2xl font-bold text-gray-900 mt-8 mb-4;
}

.prose h2 {
  @apply text-xl font-semibold text-gray-900 mt-6 mb-3;
}

.prose h3 {
  @apply text-lg font-medium text-gray-900 mt-4 mb-2;
}

.prose p {
  @apply mb-4 leading-relaxed;
}

.prose ul {
  @apply list-disc pl-6 mb-4;
}

.prose ol {
  @apply list-decimal pl-6 mb-4;
}

.prose li {
  @apply mb-2;
}

.prose code {
  @apply bg-gray-100 text-gray-800 px-2 py-1 rounded text-sm font-mono;
}

.prose pre {
  @apply bg-gray-100 p-4 rounded-lg overflow-x-auto mb-4;
}

.prose blockquote {
  @apply border-l-4 border-gray-300 pl-4 italic text-gray-600 mb-4;
}

.prose table {
  @apply w-full border-collapse border border-gray-300 mb-4;
}

.prose th {
  @apply border border-gray-300 bg-gray-50 px-4 py-2 text-left font-medium;
}

.prose td {
  @apply border border-gray-300 px-4 py-2;
}
</style>