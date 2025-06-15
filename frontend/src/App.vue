<template>
  <div id="app">
    <template v-if="!isLoading">
      <AppLayout v-if="isAuthenticated && !isAuthRoute">
        <router-view />
      </AppLayout>
      <router-view v-else />
    </template>
    <div v-else class="min-h-screen flex items-center justify-center">
      <LoadingSpinner />
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth0 } from '@auth0/auth0-vue'
import { useAuthStore } from '@/stores/auth'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'
import AppLayout from '@/components/layout/AppLayout.vue'

const isLoading = ref(true)
const route = useRoute()
const authStore = useAuthStore()

const isAuthenticated = computed(() => authStore.isAuthenticated)
const isAuthRoute = computed(() => {
  const authRoutes = ['/login', '/callback', '/register']
  return authRoutes.some(path => route.path.startsWith(path))
})

onMounted(async () => {
  // In dev mode, skip Auth0 initialization
  if (import.meta.env.VITE_DEV_MODE === 'true') {
    // Set mock user data for development
    authStore.setUser({
      id: 'dev-user-1',
      name: 'Dev User',
      email: 'dev@defender360.com',
      role: 'admin',
      tenant_id: 'dev-tenant-1'
    })
    authStore.setTenant({
      id: 'dev-tenant-1',
      name: 'Development Tenant',
      subdomain: 'dev',
      settings: {}
    })
    isLoading.value = false
    return
  }

  // Production Auth0 flow (only if Auth0 is initialized)
  if (import.meta.env.VITE_DEV_MODE === 'false') {
    const { isAuthenticated, isLoading: auth0Loading } = useAuth0()
    
    // Wait for Auth0 to initialize
    while (auth0Loading.value) {
      await new Promise(resolve => setTimeout(resolve, 100))
    }

    // Initialize auth if authenticated
    if (isAuthenticated.value) {
      await authStore.initializeAuth()
    }
  }

  isLoading.value = false
})
</script>