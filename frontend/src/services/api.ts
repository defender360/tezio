import axios from 'axios'
import { useAuth0 } from '@auth0/auth0-vue'
import { useToast } from 'vue-toastification'

const toast = useToast()

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Request interceptor
api.interceptors.request.use(
  async (config) => {
    // Skip auth completely in dev mode
    if (import.meta.env.VITE_DEV_MODE === 'true') {
      return config
    }
    
    // Only use Auth0 in production mode
    try {
      const { getAccessTokenSilently, isAuthenticated } = useAuth0()
      
      if (isAuthenticated?.value) {
        const token = await getAccessTokenSilently()
        config.headers.Authorization = `Bearer ${token}`
      }
    } catch (authError) {
      // Auth0 not available, continue without auth header
    }
    
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response) {
      switch (error.response.status) {
        case 401:
          toast.error('Authentication required. Please login again.')
          // Redirect to login
          break
        case 403:
          toast.error('You do not have permission to perform this action.')
          break
        case 422:
          const errors = error.response.data.errors
          if (errors) {
            Object.values(errors).flat().forEach((msg: any) => {
              toast.error(msg)
            })
          }
          break
        case 500:
          toast.error('Server error. Please try again later.')
          break
      }
    } else if (error.request) {
      toast.error('Network error. Please check your connection.')
    }
    
    return Promise.reject(error)
  }
)