import { createRouter, createWebHistory } from 'vue-router'
import { useAuth0 } from '@auth0/auth0-vue'
import type { RouteLocationNormalized, NavigationGuardNext } from 'vue-router'

// Route guard for authentication
const requireAuth = async (to: RouteLocationNormalized, from: RouteLocationNormalized, next: NavigationGuardNext) => {
  // Skip auth in dev mode
  if (import.meta.env.VITE_DEV_MODE === 'true') {
    next()
    return
  }
  
  // Only use Auth0 if it's initialized (production mode)
  if (import.meta.env.VITE_DEV_MODE === 'false') {
    try {
      const { isAuthenticated, loginWithRedirect } = useAuth0()
      
      if (!isAuthenticated.value) {
        await loginWithRedirect({
          appState: { targetUrl: to.fullPath }
        })
      } else {
        next()
      }
    } catch (error) {
      console.error('Auth error:', error)
      next('/dashboard') // Go to dashboard anyway
    }
  } else {
    next()
  }
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/dashboard'
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('@/views/DashboardView.vue'),
      beforeEnter: requireAuth
    },
    {
      path: '/dashboard/executive',
      name: 'executive-dashboard',
      component: () => import('@/views/dashboard/ExecutiveDashboard.vue'),
      beforeEnter: requireAuth
    },
    {
      path: '/dashboard/operational',
      name: 'operational-dashboard',
      component: () => import('@/views/dashboard/OperationalDashboard.vue'),
      beforeEnter: requireAuth
    },
    {
      path: '/dashboard/team',
      name: 'team-dashboard',
      component: () => import('@/views/dashboard/TeamDashboard.vue'),
      beforeEnter: requireAuth
    },
    {
      path: '/incidents',
      name: 'incidents',
      component: () => import('@/views/incidents/IncidentListView.vue'),
      beforeEnter: requireAuth
    },
    {
      path: '/incidents/:id',
      name: 'incident-detail',
      component: () => import('@/views/incidents/IncidentDetailView.vue'),
      beforeEnter: requireAuth
    },
    {
      path: '/cmdb',
      name: 'cmdb',
      component: () => import('@/views/cmdb/ConfigurationItemListView.vue'),
      beforeEnter: requireAuth
    },
    {
      path: '/cmdb/:id',
      name: 'cmdb-detail',
      component: () => import('@/views/cmdb/ConfigurationItemDetailView.vue'),
      beforeEnter: requireAuth
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/auth/LoginView.vue')
    },
    {
      path: '/callback',
      name: 'callback',
      component: () => import('@/views/auth/CallbackView.vue')
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue')
    }
  ]
})

// Handle Auth0 redirect
router.beforeEach(async (to, from, next) => {
  // In dev mode, completely skip Auth0 processing
  if (import.meta.env.VITE_DEV_MODE === 'true') {
    if (to.path === '/callback') {
      next('/dashboard')
      return
    }
    next()
    return
  }
  
  // Only process Auth0 callbacks in production mode
  if (import.meta.env.VITE_DEV_MODE === 'false' && to.path === '/callback') {
    try {
      const { handleRedirectCallback } = useAuth0()
      await handleRedirectCallback()
      next('/dashboard')
    } catch (error) {
      console.error('Callback error:', error)
      next('/dashboard') // Go to dashboard anyway
    }
  } else {
    next()
  }
})

export default router