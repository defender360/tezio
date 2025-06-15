<template>
  <div class="py-6">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="min-w-0 flex-1">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
            Knowledge Base
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Find answers and solutions to common issues
          </p>
        </div>
        <div v-if="authStore.user?.role === 'admin' || authStore.user?.role === 'agent'" class="mt-4 flex md:ml-4 md:mt-0">
          <button
            @click="showCreateModal = true"
            type="button"
            class="ml-3 inline-flex items-center rounded-md bg-deep-sea-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-deep-sea-500"
          >
            <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
            New Article
          </button>
        </div>
      </div>

      <!-- Search Bar -->
      <div class="mb-8">
        <div class="relative">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
          </div>
          <input
            v-model="searchQuery"
            @keyup.enter="performSearch"
            type="text"
            class="block w-full rounded-md border-0 py-3 pl-10 pr-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-deep-sea-600 sm:text-sm sm:leading-6"
            placeholder="Search for articles, guides, and solutions..."
          />
          <button
            @click="performSearch"
            class="absolute inset-y-0 right-0 flex items-center pr-3"
          >
            <span class="bg-deep-sea-600 text-white px-4 py-1.5 rounded-md text-sm font-medium hover:bg-deep-sea-700">
              Search
            </span>
          </button>
        </div>
        
        <!-- Popular searches -->
        <div v-if="!searchResults && popularSearches?.length" class="mt-3 flex flex-wrap gap-2">
          <span class="text-sm text-gray-500">Popular searches:</span>
          <button
            v-for="search in popularSearches"
            :key="search"
            @click="searchQuery = search; performSearch()"
            class="text-sm text-deep-sea-600 hover:text-deep-sea-900"
          >
            {{ search }}
          </button>
        </div>
      </div>

      <!-- Categories Grid -->
      <div v-if="!searchResults" class="mb-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Browse by Category</h3>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="category in categories"
            :key="category.id"
            @click="selectedCategory = category"
            class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400 focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-deep-sea-500 cursor-pointer"
          >
            <div class="flex-shrink-0">
              <component :is="getCategoryIcon(category.icon)" class="h-10 w-10 text-deep-sea-600" />
            </div>
            <div class="flex-1 min-w-0">
              <span class="absolute inset-0" aria-hidden="true" />
              <p class="text-sm font-medium text-gray-900">{{ category.name }}</p>
              <p class="text-sm text-gray-500 truncate">{{ category.article_count }} articles</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Search Results -->
      <div v-if="searchResults" class="mb-8">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">
            {{ searchResults.total }} results for "{{ lastSearchQuery }}"
          </h3>
          <button
            @click="clearSearch"
            class="text-sm text-gray-500 hover:text-gray-700"
          >
            Clear search
          </button>
        </div>
        
        <div v-if="searchResults.results.length" class="space-y-4">
          <article
            v-for="article in searchResults.results"
            :key="article.article_id"
            class="bg-white shadow rounded-lg overflow-hidden hover:shadow-md transition-shadow"
          >
            <router-link
              :to="{ name: 'knowledge-article', params: { id: article.article_id } }"
              class="block p-6"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <h4 class="text-lg font-medium text-gray-900 mb-2">
                    {{ article.title }}
                  </h4>
                  <p class="text-gray-600 text-sm mb-3">
                    {{ article.excerpt }}
                  </p>
                  <div class="flex items-center gap-4 text-sm text-gray-500">
                    <span>{{ article.category }}</span>
                    <span>•</span>
                    <span>{{ article.view_count }} views</span>
                    <span v-if="article.helpful_percentage > 0">•</span>
                    <span v-if="article.helpful_percentage > 0" class="text-green-600">
                      {{ Math.round(article.helpful_percentage) }}% helpful
                    </span>
                  </div>
                </div>
                <div class="ml-4">
                  <span class="inline-flex items-center rounded-full bg-deep-sea-100 px-2.5 py-0.5 text-xs font-medium text-deep-sea-800">
                    {{ Math.round(article.relevance_score * 100) }}% match
                  </span>
                </div>
              </div>
            </router-link>
          </article>
        </div>
        
        <div v-else class="text-center py-12">
          <DocumentMagnifyingGlassIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-semibold text-gray-900">No articles found</h3>
          <p class="mt-1 text-sm text-gray-500">Try adjusting your search terms</p>
        </div>
      </div>

      <!-- Category Articles -->
      <div v-else-if="selectedCategory" class="mb-8">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center">
            <button
              @click="selectedCategory = null"
              class="mr-4 text-gray-400 hover:text-gray-500"
            >
              <ArrowLeftIcon class="h-5 w-5" />
            </button>
            <h3 class="text-lg font-medium text-gray-900">
              {{ selectedCategory.name }}
            </h3>
          </div>
        </div>
        
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <article
            v-for="article in categoryArticles"
            :key="article.id"
            class="bg-white shadow rounded-lg overflow-hidden hover:shadow-md transition-shadow"
          >
            <router-link
              :to="{ name: 'knowledge-article', params: { id: article.id } }"
              class="block p-6"
            >
              <h4 class="text-base font-medium text-gray-900 mb-2">
                {{ article.title }}
              </h4>
              <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                {{ article.excerpt }}
              </p>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500">{{ formatDate(article.updated_at) }}</span>
                <span v-if="article.featured" class="text-deep-sea-600">
                  <StarIcon class="h-4 w-4" />
                </span>
              </div>
            </router-link>
          </article>
        </div>
      </div>

      <!-- Featured Articles -->
      <div v-if="!searchResults && !selectedCategory" class="mb-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Featured Articles</h3>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <article
            v-for="article in featuredArticles"
            :key="article.id"
            class="bg-white shadow rounded-lg overflow-hidden hover:shadow-md transition-shadow"
          >
            <router-link
              :to="{ name: 'knowledge-article', params: { id: article.id } }"
              class="flex p-6"
            >
              <StarIcon class="h-5 w-5 text-yellow-400 flex-shrink-0 mt-0.5" />
              <div class="ml-4 flex-1">
                <h4 class="text-lg font-medium text-gray-900 mb-2">
                  {{ article.title }}
                </h4>
                <p class="text-gray-600 text-sm mb-3">
                  {{ article.excerpt }}
                </p>
                <div class="flex items-center gap-4 text-sm text-gray-500">
                  <span>{{ article.category.name }}</span>
                  <span>•</span>
                  <span>{{ article.view_count }} views</span>
                  <span v-if="article.average_rating">•</span>
                  <span v-if="article.average_rating" class="flex items-center">
                    <StarIcon class="h-4 w-4 text-yellow-400 mr-1" />
                    {{ article.average_rating.toFixed(1) }}
                  </span>
                </div>
              </div>
            </router-link>
          </article>
        </div>
      </div>

      <!-- Recent Articles -->
      <div v-if="!searchResults && !selectedCategory" class="mb-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Articles</h3>
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
          <ul class="divide-y divide-gray-200">
            <li v-for="article in recentArticles" :key="article.id">
              <router-link
                :to="{ name: 'knowledge-article', params: { id: article.id } }"
                class="block hover:bg-gray-50 px-4 py-4 sm:px-6"
              >
                <div class="flex items-center justify-between">
                  <div class="flex-1">
                    <p class="text-sm font-medium text-deep-sea-600 truncate">
                      {{ article.title }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                      {{ article.category.name }} • Updated {{ formatDate(article.updated_at) }}
                    </p>
                  </div>
                  <ChevronRightIcon class="h-5 w-5 text-gray-400" />
                </div>
              </router-link>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Create Article Modal -->
    <CreateKnowledgeArticleModal
      v-if="showCreateModal"
      :open="showCreateModal"
      @close="showCreateModal = false"
      @created="handleArticleCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuery } from '@tanstack/vue-query'
import {
  PlusIcon,
  MagnifyingGlassIcon,
  StarIcon,
  ChevronRightIcon,
  ArrowLeftIcon,
  DocumentMagnifyingGlassIcon,
  ComputerDesktopIcon,
  ServerIcon,
  ShieldCheckIcon,
  UserGroupIcon,
  CogIcon,
  QuestionMarkCircleIcon
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import { formatDate } from '@/utils/date'
import CreateKnowledgeArticleModal from './CreateKnowledgeArticleModal.vue'
import type { KnowledgeArticle, KnowledgeCategory } from '@/types'

const router = useRouter()
const authStore = useAuthStore()

const searchQuery = ref('')
const lastSearchQuery = ref('')
const searchResults = ref(null)
const selectedCategory = ref<KnowledgeCategory | null>(null)
const showCreateModal = ref(false)

// Fetch categories
const { data: categories } = useQuery({
  queryKey: ['knowledge-categories'],
  queryFn: async () => {
    const response = await api.get('/api/v1/knowledge/categories')
    return response.data.data
  }
})

// Fetch featured articles
const { data: featuredArticles } = useQuery({
  queryKey: ['knowledge-featured'],
  queryFn: async () => {
    const response = await api.get('/api/v1/knowledge/articles', {
      params: { featured: true, limit: 4 }
    })
    return response.data.data
  }
})

// Fetch recent articles
const { data: recentArticles } = useQuery({
  queryKey: ['knowledge-recent'],
  queryFn: async () => {
    const response = await api.get('/api/v1/knowledge/articles', {
      params: { sort: 'updated_at', direction: 'desc', limit: 5 }
    })
    return response.data.data
  }
})

// Fetch category articles
const { data: categoryArticles, refetch: refetchCategoryArticles } = useQuery({
  queryKey: ['knowledge-category-articles', selectedCategory],
  queryFn: async () => {
    if (!selectedCategory.value) return []
    const response = await api.get('/api/v1/knowledge/articles', {
      params: { category_id: selectedCategory.value.id }
    })
    return response.data.data
  },
  enabled: computed(() => !!selectedCategory.value)
})

// Popular searches
const popularSearches = ref([
  'password reset',
  'vpn setup',
  'email configuration',
  'printer issues',
  'software installation'
])

const performSearch = async () => {
  if (!searchQuery.value.trim()) return
  
  lastSearchQuery.value = searchQuery.value
  const response = await api.get('/api/v1/knowledge/search', {
    params: { query: searchQuery.value }
  })
  searchResults.value = response.data
}

const clearSearch = () => {
  searchQuery.value = ''
  lastSearchQuery.value = ''
  searchResults.value = null
}

const getCategoryIcon = (icon: string) => {
  const icons: Record<string, any> = {
    'computer': ComputerDesktopIcon,
    'server': ServerIcon,
    'shield': ShieldCheckIcon,
    'users': UserGroupIcon,
    'cog': CogIcon,
    'question': QuestionMarkCircleIcon
  }
  return icons[icon] || QuestionMarkCircleIcon
}

const handleArticleCreated = () => {
  // Refetch data
  if (selectedCategory.value) {
    refetchCategoryArticles()
  }
}

// Check for search query in route
onMounted(() => {
  const query = router.currentRoute.value.query.search as string
  if (query) {
    searchQuery.value = query
    performSearch()
  }
})
</script>