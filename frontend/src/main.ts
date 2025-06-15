import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { VueQueryPlugin } from '@tanstack/vue-query'
import { createAuth0 } from '@auth0/auth0-vue'
import Toast from 'vue-toastification'
import App from './App.vue'
import router from './router'
import './styles/index.css'
import 'vue-toastification/dist/index.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)

// Only initialize Auth0 if not in dev mode
if (import.meta.env.VITE_DEV_MODE === 'false') {
  const auth0 = createAuth0({
    domain: import.meta.env.VITE_AUTH0_DOMAIN,
    clientId: import.meta.env.VITE_AUTH0_CLIENT_ID,
    authorizationParams: {
      redirect_uri: window.location.origin + '/callback',
      audience: import.meta.env.VITE_AUTH0_AUDIENCE,
      scope: 'openid profile email tenant_id'
    },
    cacheLocation: 'localstorage',
    useRefreshTokens: true
  })
  app.use(auth0)
}

app.use(VueQueryPlugin)
app.use(Toast, {
  position: 'top-right',
  timeout: 5000,
  closeOnClick: true,
  pauseOnFocusLoss: true,
  pauseOnHover: true
})

app.mount('#app')