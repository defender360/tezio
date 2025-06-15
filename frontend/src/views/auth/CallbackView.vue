<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center">
      <LoadingSpinner />
      <p class="mt-4 text-sm text-gray-600">Completing sign in...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
</script>