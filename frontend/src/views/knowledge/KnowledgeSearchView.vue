<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Knowledge Base</h1>
        <router-link
          to="/knowledge/new"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          New Article
        </router-link>
      </div>
      <p class="mt-2 text-gray-600">
        Search and browse knowledge articles for solutions and documentation
      </p>
    </div>

    <!-- Search Bar -->
    <div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div class="flex gap-4">
        <div class="flex-1">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search articles, solutions, FAQs..."
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-lg p-4"
            @keyup.enter="performSearch"
          >
        </div>
        <button
          @click="performSearch"
          class="px-6 py-4 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </button>
      </div>
      
      <!-- Quick Search Tags -->
      <div class="mt-4 flex flex-wrap gap-2">
        <span class="text-sm text-gray-600 mr-2">Quick search:</span>
        <button
          v-for="tag in quickSearchTags"
          :key="tag"
          @click="quickSearch(tag)"
          class="px-3 py-1 bg-gray-100 text-gray-700 text-sm rounded-full hover:bg-gray-200 transition-colors"
        >
          {{ tag }}
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
          <select
            v-model="filters.category"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">All Categories</option>
            <option value="troubleshooting">Troubleshooting</option>
            <option value="procedures">Procedures</option>
            <option value="faq">FAQ</option>
            <option value="documentation">Documentation</option>
            <option value="solutions">Solutions</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select
            v-model="filters.status"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">All Statuses</option>
            <option value="published">Published</option>
            <option value="draft">Draft</option>
            <option value="under_review">Under Review</option>
            <option value="archived">Archived</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Rating</label>
          <select
            v-model="filters.rating"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="">Any Rating</option>
            <option value="5">5 Stars</option>
            <option value="4">4+ Stars</option>
            <option value="3">3+ Stars</option>
            <option value="2">2+ Stars</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
          <select
            v-model="sortBy"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option value="relevance">Relevance</option>
            <option value="date">Date Created</option>
            <option value="updated">Last Updated</option>
            <option value="rating">Rating</option>
            <option value="views">Most Viewed</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Search Results -->
    <div class="space-y-6">
      <!-- Loading State -->
      <div v-if="isLoading" class="flex justify-center py-8">
        <LoadingSpinner />
      </div>
      
      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-red-800">Error searching articles: {{ error.message }}</p>
      </div>
      
      <!-- No Results -->
      <div v-else-if="articles?.length === 0" class="bg-gray-50 rounded-lg p-8 text-center">
        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="text-gray-600 mb-2">No articles found</p>
        <p class="text-sm text-gray-500">Try adjusting your search terms or filters</p>
      </div>
      
      <!-- Results -->
      <div v-else>
        <!-- Results Count -->
        <div class="flex items-center justify-between mb-4">
          <p class="text-sm text-gray-600">
            {{ totalResults }} articles found
            <span v-if="searchQuery">(for "{{ searchQuery }}")</span>
          </p>
          <div class="flex items-center gap-2">
            <button
              @click="viewMode = 'list'"
              :class="[
                'p-2 rounded-md transition-colors',
                viewMode === 'list'
                  ? 'bg-blue-100 text-blue-700'
                  : 'text-gray-400 hover:text-gray-600'
              ]"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
            <button
              @click="viewMode = 'grid'"
              :class="[
                'p-2 rounded-md transition-colors',
                viewMode === 'grid'
                  ? 'bg-blue-100 text-blue-700'
                  : 'text-gray-400 hover:text-gray-600'
              ]"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
              </svg>
            </button>
          </div>
        </div>

        <!-- List View -->
        <div v-if="viewMode === 'list'" class="space-y-4">
          <div
            v-for="article in articles"
            :key="article.id"
            class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow"
          >
            <div class="flex items-start gap-4">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <router-link
                    :to="`/knowledge/articles/${article.id}`"
                    class="text-lg font-semibold text-gray-900 hover:text-blue-600 transition-colors"
                  >
                    {{ article.title }}
                  </router-link>
                  <span
                    :class="[
                      'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                      getCategoryColor(article.category)
                    ]"
                  >
                    {{ article.category }}
                  </span>
                </div>
                
                <p class="text-gray-600 mb-3 line-clamp-2">{{ article.summary }}</p>
                
                <div class="flex items-center gap-4 text-sm text-gray-500">
                  <div class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ article.author.name }}
                  </div>
                  <div class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <TimeAgo :date="article.updatedAt" />
                  </div>
                  <div class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    {{ article.views || 0 }} views
                  </div>
                </div>
              </div>
              
              <div class="flex-shrink-0 text-right">
                <div class="flex items-center gap-1 mb-2">
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
                  <span class="text-sm text-gray-500 ml-1">
                    ({{ article.ratingsCount || 0 }})
                  </span>
                </div>
                
                <div class="flex gap-2">
                  <button
                    @click="bookmarkArticle(article.id)"
                    :class="[
                      'p-2 rounded-md transition-colors',
                      article.isBookmarked
                        ? 'bg-yellow-100 text-yellow-600'
                        : 'bg-gray-100 text-gray-400 hover:text-gray-600'
                    ]"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                  </button>
                  <button
                    @click="shareArticle(article)"
                    class="p-2 rounded-md bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Grid View -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="article in articles"
            :key="article.id"
            class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow"
          >
            <div class="mb-3">
              <span
                :class="[
                  'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                  getCategoryColor(article.category)
                ]"
              >
                {{ article.category }}
              </span>
            </div>
            
            <router-link
              :to="`/knowledge/articles/${article.id}`"
              class="text-lg font-semibold text-gray-900 hover:text-blue-600 transition-colors line-clamp-2"
            >
              {{ article.title }}
            </router-link>
            
            <p class="text-gray-600 mt-2 mb-4 line-clamp-3">{{ article.summary }}</p>
            
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-1">
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
                <span class="text-sm text-gray-500 ml-1">
                  ({{ article.ratingsCount || 0 }})
                </span>
              </div>
              
              <div class="text-sm text-gray-500">
                {{ article.views || 0 }} views
              </div>
            </div>
          </div>
        </div>
        
        <!-- Pagination -->
        <Pagination
          v-if="articles && articles.length > 0"
          :current-page="currentPage"
          :total-pages="totalPages"
          @update:current-page="currentPage = $event"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { useToast } from 'vue-toastification'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import TimeAgo from '@/components/common/TimeAgo.vue'
import Pagination from '@/components/common/Pagination.vue'
import { api } from '@/services/api'

const toast = useToast()

// State
const searchQuery = ref('')
const viewMode = ref<'list' | 'grid'>('list')
const sortBy = ref('relevance')
const currentPage = ref(1)

const filters = ref({
  category: '',
  status: 'published',
  rating: ''
})

const quickSearchTags = [
  'password reset',
  'network issues',
  'email problems',
  'software installation',
  'troubleshooting',
  'how to'
]

// Query
const { data, isLoading, error } = useQuery({
  queryKey: ['knowledge-articles', searchQuery, filters, sortBy, currentPage],
  queryFn: async () => {
    const params = new URLSearchParams({
      page: currentPage.value.toString(),
      limit: '20',
      sort: sortBy.value,
      ...Object.fromEntries(
        Object.entries(filters.value).filter(([_, v]) => v !== '')
      )
    })
    
    if (searchQuery.value) {
      params.append('search', searchQuery.value)
    }
    
    const response = await api.get(`/knowledge/articles?${params}`)
    return response.data
  }
})

// Computed
const articles = computed(() => data.value?.items || [])
const totalPages = computed(() => data.value?.totalPages || 1)
const totalResults = computed(() => data.value?.total || 0)

// Methods
const performSearch = () => {
  currentPage.value = 1
}

const quickSearch = (tag: string) => {
  searchQuery.value = tag
  performSearch()
}

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

const bookmarkArticle = async (articleId: string) => {
  try {
    await api.post(`/knowledge/articles/${articleId}/bookmark`)
    toast.success('Article bookmarked')
    // Update the article's bookmarked status locally if needed
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to bookmark article')
  }
}

const shareArticle = (article: any) => {
  const url = `${window.location.origin}/knowledge/articles/${article.id}`
  navigator.clipboard.writeText(url).then(() => {
    toast.success('Article link copied to clipboard')
  }).catch(() => {
    toast.error('Failed to copy link')
  })
}

// Watchers
watch([filters, sortBy], () => {
  currentPage.value = 1
}, { deep: true })
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>