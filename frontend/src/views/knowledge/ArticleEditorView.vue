<template>
  <div class="p-6 max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center gap-4 mb-2">
        <router-link
          to="/knowledge"
          class="text-gray-500 hover:text-gray-700 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </router-link>
        <h1 class="text-2xl font-bold text-gray-900">
          {{ isEditing ? 'Edit Article' : 'Create Article' }}
        </h1>
      </div>
      <p class="text-gray-600">
        {{ isEditing ? 'Update the knowledge article' : 'Create a new knowledge base article' }}
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center py-12">
      <LoadingSpinner />
    </div>

    <!-- Error State -->
    <div v-else-if="error && isEditing" class="bg-red-50 border border-red-200 rounded-lg p-4">
      <p class="text-red-800">Error loading article: {{ error.message }}</p>
    </div>

    <!-- Editor Form -->
    <div v-else>
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Basic Information -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Title <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.title"
                type="text"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Enter article title"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Category <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.category"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="">Select category</option>
                <option value="troubleshooting">Troubleshooting</option>
                <option value="procedures">Procedures</option>
                <option value="faq">FAQ</option>
                <option value="documentation">Documentation</option>
                <option value="solutions">Solutions</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Status
              </label>
              <select
                v-model="form.status"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
                <option value="draft">Draft</option>
                <option value="under_review">Under Review</option>
                <option value="published">Published</option>
              </select>
            </div>
            
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Summary
              </label>
              <textarea
                v-model="form.summary"
                rows="2"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Brief summary of the article"
              ></textarea>
            </div>
            
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Tags
              </label>
              <div class="flex flex-wrap gap-2 mb-2">
                <span
                  v-for="(tag, index) in tags"
                  :key="index"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                >
                  {{ tag }}
                  <button
                    type="button"
                    @click="removeTag(index)"
                    class="ml-1 text-blue-600 hover:text-blue-800"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </span>
              </div>
              <div class="flex gap-2">
                <input
                  v-model="newTag"
                  type="text"
                  class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  placeholder="Add tags (press Enter to add)"
                  @keyup.enter="addTag"
                >
                <button
                  type="button"
                  @click="addTag"
                  class="px-3 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors"
                >
                  Add
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Content Editor -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Content</h2>
          
          <!-- Editor Toolbar -->
          <div class="border border-gray-300 rounded-t-md p-3 bg-gray-50 flex flex-wrap gap-2" v-if="showRichEditor">
            <button
              type="button"
              @click="formatText('bold')"
              class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded"
              title="Bold"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z" />
              </svg>
            </button>
            
            <button
              type="button"
              @click="formatText('italic')"
              class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded"
              title="Italic"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4l4 16" />
              </svg>
            </button>
            
            <button
              type="button"
              @click="formatText('underline')"
              class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded"
              title="Underline"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 20h12M8 4v12a4 4 0 008 0V4" />
              </svg>
            </button>
            
            <div class="w-px h-6 bg-gray-300"></div>
            
            <button
              type="button"
              @click="formatText('insertUnorderedList')"
              class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded"
              title="Bullet List"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
            
            <button
              type="button"
              @click="formatText('insertOrderedList')"
              class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded"
              title="Numbered List"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
            
            <div class="w-px h-6 bg-gray-300"></div>
            
            <button
              type="button"
              @click="insertLink"
              class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded"
              title="Insert Link"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
              </svg>
            </button>
            
            <button
              type="button"
              @click="insertImage"
              class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded"
              title="Insert Image"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </button>
            
            <div class="w-px h-6 bg-gray-300"></div>
            
            <button
              type="button"
              @click="showRichEditor = !showRichEditor"
              class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded"
              title="Toggle HTML View"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
              </svg>
            </button>
          </div>
          
          <!-- Rich Text Editor -->
          <div
            v-if="showRichEditor"
            ref="editorRef"
            contenteditable="true"
            @input="updateContent"
            class="min-h-96 p-4 border border-gray-300 rounded-b-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 prose max-w-none"
            style="border-top: none;"
          ></div>
          
          <!-- HTML Source Editor -->
          <textarea
            v-else
            v-model="form.content"
            rows="20"
            class="w-full rounded-b-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm"
            style="border-top: none;"
            placeholder="Enter article content (HTML supported)"
          ></textarea>
          
          <!-- Editor Toggle -->
          <div class="mt-2 flex justify-between items-center">
            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="showRichEditor = !showRichEditor"
                class="text-sm text-blue-600 hover:text-blue-800"
              >
                {{ showRichEditor ? 'Switch to HTML' : 'Switch to Rich Editor' }}
              </button>
            </div>
            <div class="text-xs text-gray-500">
              {{ form.content.length }} characters
            </div>
          </div>
        </div>

        <!-- Preview -->
        <div v-if="form.content" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Preview</h2>
            <button
              type="button"
              @click="showPreview = !showPreview"
              class="text-sm text-blue-600 hover:text-blue-800"
            >
              {{ showPreview ? 'Hide Preview' : 'Show Preview' }}
            </button>
          </div>
          
          <div v-if="showPreview" class="prose max-w-none">
            <h1>{{ form.title }}</h1>
            <p v-if="form.summary" class="text-lg text-gray-600">{{ form.summary }}</p>
            <div v-html="form.content"></div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4">
          <router-link
            to="/knowledge"
            class="px-4 py-2 text-gray-700 hover:text-gray-900 transition-colors"
          >
            Cancel
          </router-link>
          <button
            type="button"
            @click="saveDraft"
            :disabled="isSaving"
            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors disabled:opacity-50"
          >
            Save as Draft
          </button>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center gap-2"
          >
            <LoadingSpinner v-if="isSubmitting" class="w-4 h-4" />
            {{ isEditing ? 'Update Article' : 'Create Article' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuery, useMutation } from '@tanstack/vue-query'
import { useToast } from 'vue-toastification'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import { api } from '@/services/api'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const articleId = route.params.id as string
const isEditing = computed(() => !!articleId)

// State
const showRichEditor = ref(true)
const showPreview = ref(false)
const newTag = ref('')
const tags = ref<string[]>([])
const isSaving = ref(false)
const editorRef = ref<HTMLElement>()

const form = reactive({
  title: '',
  category: '',
  status: 'draft',
  summary: '',
  content: '',
  tags: [] as string[]
})

// Query for editing
const { data: article, isLoading, error } = useQuery({
  queryKey: ['article', articleId],
  queryFn: async () => {
    if (!isEditing.value) return null
    const response = await api.get(`/knowledge/articles/${articleId}`)
    return response.data
  },
  enabled: isEditing.value
})

// Mutations
const { mutate: saveArticle, isPending: isSubmitting } = useMutation({
  mutationFn: async (data: any) => {
    if (isEditing.value) {
      const response = await api.put(`/knowledge/articles/${articleId}`, data)
      return response.data
    } else {
      const response = await api.post('/knowledge/articles', data)
      return response.data
    }
  },
  onSuccess: (data) => {
    toast.success(isEditing.value ? 'Article updated successfully' : 'Article created successfully')
    router.push(`/knowledge/articles/${data.id}`)
  },
  onError: (error: any) => {
    toast.error(error.response?.data?.message || 'Failed to save article')
  }
})

// Initialize form data when editing
onMounted(() => {
  if (article.value) {
    Object.assign(form, {
      title: article.value.title,
      category: article.value.category,
      status: article.value.status,
      summary: article.value.summary || '',
      content: article.value.content || '',
      tags: article.value.tags || []
    })
    tags.value = [...(article.value.tags || [])]
    
    // Set editor content
    nextTick(() => {
      if (editorRef.value) {
        editorRef.value.innerHTML = form.content
      }
    })
  }
})

// Methods
const handleSubmit = () => {
  const data = {
    ...form,
    tags: tags.value
  }
  saveArticle(data)
}

const saveDraft = async () => {
  isSaving.value = true
  try {
    const data = {
      ...form,
      status: 'draft',
      tags: tags.value
    }
    
    if (isEditing.value) {
      await api.put(`/knowledge/articles/${articleId}`, data)
    } else {
      const response = await api.post('/knowledge/articles', data)
      router.push(`/knowledge/articles/${response.data.id}/edit`)
    }
    
    toast.success('Draft saved successfully')
  } catch (error: any) {
    toast.error(error.response?.data?.message || 'Failed to save draft')
  } finally {
    isSaving.value = false
  }
}

const addTag = () => {
  const tag = newTag.value.trim()
  if (tag && !tags.value.includes(tag)) {
    tags.value.push(tag)
    newTag.value = ''
  }
}

const removeTag = (index: number) => {
  tags.value.splice(index, 1)
}

const formatText = (command: string) => {
  document.execCommand(command, false)
  updateContent()
}

const insertLink = () => {
  const url = prompt('Enter URL:')
  if (url) {
    document.execCommand('createLink', false, url)
    updateContent()
  }
}

const insertImage = () => {
  const url = prompt('Enter image URL:')
  if (url) {
    document.execCommand('insertImage', false, url)
    updateContent()
  }
}

const updateContent = () => {
  if (editorRef.value) {
    form.content = editorRef.value.innerHTML
  }
}
</script>

<style scoped>
.prose {
  @apply text-gray-900;
}

.prose h1 {
  @apply text-2xl font-bold text-gray-900 mt-6 mb-4;
}

.prose h2 {
  @apply text-xl font-semibold text-gray-900 mt-5 mb-3;
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
  @apply mb-1;
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

/* Contenteditable styling */
[contenteditable="true"]:focus {
  outline: none;
}
</style>