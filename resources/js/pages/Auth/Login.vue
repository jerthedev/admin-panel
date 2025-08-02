<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8" :class="{ 'bg-gray-900': isDarkTheme }">
    <div class="max-w-md w-full space-y-8">
      <!-- Header -->
      <div>
        <div class="mx-auto h-12 w-auto flex items-center justify-center">
          <div class="h-8 w-8 bg-blue-600 rounded-lg flex items-center justify-center">
            <span class="text-white font-bold text-lg">A</span>
          </div>
          <span class="ml-2 text-2xl font-bold text-gray-900" :class="{ 'text-white': isDarkTheme }">
            Admin Panel
          </span>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900" :class="{ 'text-white': isDarkTheme }">
          Sign in to your account
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600" :class="{ 'text-gray-400': isDarkTheme }">
          Access the admin dashboard
        </p>
      </div>

      <!-- Login Form -->
      <form class="mt-8 space-y-6" @submit.prevent="submit">
        <div class="rounded-md shadow-sm -space-y-px">
          <!-- Email -->
          <div>
            <label for="email" class="sr-only">Email address</label>
            <input
              id="email"
              v-model="form.email"
              name="email"
              type="email"
              autocomplete="email"
              required
              class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
              :class="{ 
                'bg-gray-700 border-gray-600 text-white placeholder-gray-400 focus:ring-blue-400 focus:border-blue-400': isDarkTheme,
                'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.email
              }"
              placeholder="Email address"
            />
          </div>

          <!-- Password -->
          <div>
            <label for="password" class="sr-only">Password</label>
            <input
              id="password"
              v-model="form.password"
              name="password"
              type="password"
              autocomplete="current-password"
              required
              class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
              :class="{ 
                'bg-gray-700 border-gray-600 text-white placeholder-gray-400 focus:ring-blue-400 focus:border-blue-400': isDarkTheme,
                'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.password
              }"
              placeholder="Password"
            />
          </div>
        </div>

        <!-- Error Messages -->
        <div v-if="form.errors.email || form.errors.password" class="rounded-md bg-red-50 p-4" :class="{ 'bg-red-900': isDarkTheme }">
          <div class="flex">
            <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800" :class="{ 'text-red-200': isDarkTheme }">
                Authentication Error
              </h3>
              <div class="mt-2 text-sm text-red-700" :class="{ 'text-red-300': isDarkTheme }">
                <ul class="list-disc pl-5 space-y-1">
                  <li v-if="form.errors.email">{{ form.errors.email }}</li>
                  <li v-if="form.errors.password">{{ form.errors.password }}</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Status Message -->
        <div v-if="status" class="rounded-md bg-green-50 p-4" :class="{ 'bg-green-900': isDarkTheme }">
          <div class="flex">
            <CheckCircleIcon class="h-5 w-5 text-green-400" />
            <div class="ml-3">
              <p class="text-sm font-medium text-green-800" :class="{ 'text-green-200': isDarkTheme }">
                {{ status }}
              </p>
            </div>
          </div>
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input
              id="remember"
              v-model="form.remember"
              name="remember"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              :class="{ 'border-gray-600 bg-gray-700': isDarkTheme }"
            />
            <label for="remember" class="ml-2 block text-sm text-gray-900" :class="{ 'text-gray-300': isDarkTheme }">
              Remember me
            </label>
          </div>

          <div v-if="canResetPassword" class="text-sm">
            <a href="#" class="font-medium text-blue-600 hover:text-blue-500" :class="{ 'text-blue-400 hover:text-blue-300': isDarkTheme }">
              Forgot your password?
            </a>
          </div>
        </div>

        <!-- Submit Button -->
        <div>
          <button
            type="submit"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            :class="{ 'focus:ring-offset-gray-900': isDarkTheme }"
            :disabled="form.processing"
          >
            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
              <LockClosedIcon v-if="!form.processing" class="h-5 w-5 text-blue-500 group-hover:text-blue-400" />
              <svg v-else class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </span>
            {{ form.processing ? 'Signing in...' : 'Sign in' }}
          </button>
        </div>
      </form>

      <!-- Footer -->
      <div class="text-center">
        <p class="text-xs text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
          © {{ currentYear }} Admin Panel. All rights reserved.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * Login Page
 * 
 * Admin panel login page with form validation, error handling,
 * and professional styling.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed } from 'vue'
import { useForm, Head } from '@inertiajs/vue3'
import { useAdminStore } from '@/stores/admin'
import {
  LockClosedIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  canResetPassword: Boolean,
  status: String,
})

// Store
const adminStore = useAdminStore()

// Form
const form = useForm({
  email: '',
  password: '',
  remember: false,
})

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)
const currentYear = computed(() => new Date().getFullYear())

// Methods
const submit = () => {
  form.post(route('admin-panel.login'), {
    onFinish: () => form.reset('password'),
  })
}

const route = (name, params = {}) => {
  return window.adminPanel?.route(name, params) || '#'
}
</script>

<style scoped>
/* Loading spinner animation */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Focus styles */
input:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Dark theme input styles */
.dark input {
  background-color: rgb(55 65 81);
  border-color: rgb(75 85 99);
  color: white;
}

.dark input::placeholder {
  color: rgb(156 163 175);
}

.dark input:focus {
  border-color: rgb(96 165 250);
  box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.1);
}
</style>
