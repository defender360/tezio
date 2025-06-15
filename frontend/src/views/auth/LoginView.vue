<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-brain font-bold text-deep-sea-500">
          Welcome to Defender360
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Enterprise IT Service Management Platform
        </p>
      </div>
      
      <div class="mt-8 space-y-6">
        <button
          @click="login"
          class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-tech-horizon-500 hover:bg-tech-horizon-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-tech-horizon-500"
        >
          Sign in with Auth0
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuth0 } from '@auth0/auth0-vue'

const { loginWithRedirect } = useAuth0()

function login() {
  loginWithRedirect()
}
</script>