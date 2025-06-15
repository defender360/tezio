<template>
  <div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
      Training Recommendations
    </h3>
    
    <div v-if="recommendations.length === 0" class="text-center py-8">
      <AcademicCapIcon class="w-12 h-12 text-gray-400 mx-auto mb-3" />
      <p class="text-gray-500">No training recommendations at this time</p>
    </div>
    
    <div v-else class="space-y-4">
      <div
        v-for="recommendation in recommendations"
        :key="recommendation.id"
        class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <h4 class="font-medium text-gray-900">{{ recommendation.title }}</h4>
            <p class="text-sm text-gray-600 mt-1">{{ recommendation.description }}</p>
            
            <div class="flex items-center mt-2 space-x-4">
              <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                {{ recommendation.category }}
              </span>
              <span class="text-xs text-gray-500">
                {{ recommendation.estimatedDuration }}
              </span>
              <span class="text-xs text-gray-500">
                Priority: {{ recommendation.priority }}
              </span>
            </div>
          </div>
          
          <button
            @click="enrollInTraining(recommendation)"
            class="ml-4 btn-primary-sm"
          >
            Enroll
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { AcademicCapIcon } from '@heroicons/vue/24/outline'

interface TrainingRecommendation {
  id: string
  title: string
  description: string
  category: string
  estimatedDuration: string
  priority: 'High' | 'Medium' | 'Low'
}

interface Props {
  recommendations: TrainingRecommendation[]
}

defineProps<Props>()

const enrollInTraining = (recommendation: TrainingRecommendation) => {
  // Handle training enrollment
  console.log('Enrolling in training:', recommendation.title)
}
</script>