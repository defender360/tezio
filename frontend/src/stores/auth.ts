import { defineStore } from 'pinia'
import { useAuth0 } from '@auth0/auth0-vue'
import type { User, Tenant } from '@/types'
import { api } from '@/services/api'

interface AuthState {
  user: User | null
  tenant: Tenant | null
  isLoading: boolean
  error: string | null
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    tenant: null,
    isLoading: false,
    error: null
  }),

  getters: {
    isAuthenticated: (state) => !!state.user,
    userRole: (state) => state.user?.role || 'user',
    isAdmin: (state) => state.user?.role === 'admin',
    isAgent: (state) => ['admin', 'agent'].includes(state.user?.role || ''),
    tenantId: (state) => state.tenant?.id
  },

  actions: {
    async initializeAuth() {
      // Skip Auth0 initialization in dev mode
      if (import.meta.env.VITE_DEV_MODE === 'true') {
        return
      }
      
      try {
        const auth0 = useAuth0()
        
        if (auth0?.isAuthenticated?.value) {
          this.isLoading = true
          const token = await auth0.getAccessTokenSilently()
          api.defaults.headers.common['Authorization'] = `Bearer ${token}`
          
          // Get user data from API
          const response = await api.get('/api/v1/me')
          this.user = response.data.user
          this.tenant = response.data.tenant
        }
      } catch (error) {
        this.error = 'Failed to initialize authentication'
        console.error('Auth initialization error:', error)
      } finally {
        this.isLoading = false
      }
    },

    async logout() {
      // In dev mode, just clear local state
      if (import.meta.env.VITE_DEV_MODE === 'true') {
        this.user = null
        this.tenant = null
        return
      }
      
      const auth0 = useAuth0()
      this.user = null
      this.tenant = null
      delete api.defaults.headers.common['Authorization']
      await auth0.logout({ logoutParams: { returnTo: window.location.origin } })
    },

    setUser(user: User) {
      this.user = user
    },

    setTenant(tenant: Tenant) {
      this.tenant = tenant
    }
  }
})