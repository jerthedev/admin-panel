<template>
  <AdminLayout title="Profile">
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-2xl font-semibold text-gray-900" :class="{ 'text-white': isDarkTheme }">
          Profile Settings
        </h1>
        <p class="mt-1 text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
          Manage your account settings and preferences
        </p>
      </div>

      <!-- Profile Information -->
      <div class="admin-card">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" :class="{ 'text-white': isDarkTheme }">
            Profile Information
          </h3>
          
          <form @submit.prevent="updateProfile">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
              <!-- Name -->
              <div>
                <label for="name" class="block text-sm font-medium text-gray-700" :class="{ 'text-gray-300': isDarkTheme }">
                  Name
                </label>
                <input
                  id="name"
                  v-model="profileForm.name"
                  type="text"
                  class="admin-input mt-1 block w-full"
                  :class="{ 'admin-input-dark': isDarkTheme }"
                  required
                />
                <div v-if="profileForm.errors.name" class="mt-1 text-sm text-red-600">
                  {{ profileForm.errors.name }}
                </div>
              </div>

              <!-- Email -->
              <div>
                <label for="email" class="block text-sm font-medium text-gray-700" :class="{ 'text-gray-300': isDarkTheme }">
                  Email
                </label>
                <input
                  id="email"
                  v-model="profileForm.email"
                  type="email"
                  class="admin-input mt-1 block w-full"
                  :class="{ 'admin-input-dark': isDarkTheme }"
                  required
                />
                <div v-if="profileForm.errors.email" class="mt-1 text-sm text-red-600">
                  {{ profileForm.errors.email }}
                </div>
              </div>
            </div>

            <div class="mt-6">
              <button
                type="submit"
                class="admin-btn-primary"
                :disabled="profileForm.processing"
              >
                {{ profileForm.processing ? 'Updating...' : 'Update Profile' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Change Password -->
      <div class="admin-card">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" :class="{ 'text-white': isDarkTheme }">
            Change Password
          </h3>
          
          <form @submit.prevent="updatePassword">
            <div class="space-y-6">
              <!-- Current Password -->
              <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700" :class="{ 'text-gray-300': isDarkTheme }">
                  Current Password
                </label>
                <input
                  id="current_password"
                  v-model="passwordForm.current_password"
                  type="password"
                  class="admin-input mt-1 block w-full"
                  :class="{ 'admin-input-dark': isDarkTheme }"
                  required
                />
                <div v-if="passwordForm.errors.current_password" class="mt-1 text-sm text-red-600">
                  {{ passwordForm.errors.current_password }}
                </div>
              </div>

              <!-- New Password -->
              <div>
                <label for="password" class="block text-sm font-medium text-gray-700" :class="{ 'text-gray-300': isDarkTheme }">
                  New Password
                </label>
                <input
                  id="password"
                  v-model="passwordForm.password"
                  type="password"
                  class="admin-input mt-1 block w-full"
                  :class="{ 'admin-input-dark': isDarkTheme }"
                  required
                />
                <div v-if="passwordForm.errors.password" class="mt-1 text-sm text-red-600">
                  {{ passwordForm.errors.password }}
                </div>
              </div>

              <!-- Confirm Password -->
              <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700" :class="{ 'text-gray-300': isDarkTheme }">
                  Confirm New Password
                </label>
                <input
                  id="password_confirmation"
                  v-model="passwordForm.password_confirmation"
                  type="password"
                  class="admin-input mt-1 block w-full"
                  :class="{ 'admin-input-dark': isDarkTheme }"
                  required
                />
              </div>
            </div>

            <div class="mt-6">
              <button
                type="submit"
                class="admin-btn-primary"
                :disabled="passwordForm.processing"
              >
                {{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Active Sessions -->
      <div class="admin-card">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" :class="{ 'text-white': isDarkTheme }">
            Active Sessions
          </h3>
          
          <div class="space-y-4">
            <div
              v-for="session in sessions"
              :key="session.id"
              class="flex items-center justify-between p-4 border border-gray-200 rounded-lg"
              :class="{ 'border-gray-600': isDarkTheme }"
            >
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <ComputerDesktopIcon class="h-6 w-6 text-gray-400" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900" :class="{ 'text-white': isDarkTheme }">
                    {{ session.user_agent }}
                    <span v-if="session.is_current" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                      Current Session
                    </span>
                  </p>
                  <p class="text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                    {{ session.ip_address }} • Last active {{ formatDate(session.last_active) }}
                  </p>
                </div>
              </div>
              
              <button
                v-if="!session.is_current"
                class="text-red-600 hover:text-red-500 text-sm font-medium"
                :class="{ 'text-red-400 hover:text-red-300': isDarkTheme }"
                @click="revokeSession(session.id)"
              >
                Revoke
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
/**
 * Profile Page
 * 
 * User profile management page with profile information,
 * password change, and session management.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { ComputerDesktopIcon } from '@heroicons/vue/24/outline'
import AdminLayout from '@/components/Layout/AdminLayout.vue'

// Props
const props = defineProps({
  user: Object,
  sessions: Array,
})

// Store
const adminStore = useAdminStore()

// Forms
const profileForm = useForm({
  name: props.user.name,
  email: props.user.email,
})

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

// Methods
const updateProfile = () => {
  profileForm.put(route('admin-panel.profile.update'), {
    onSuccess: () => {
      adminStore.notifySuccess('Profile updated successfully')
    },
    onError: () => {
      adminStore.notifyError('Failed to update profile')
    }
  })
}

const updatePassword = () => {
  passwordForm.put(route('admin-panel.password.update'), {
    onSuccess: () => {
      passwordForm.reset()
      adminStore.notifySuccess('Password updated successfully')
    },
    onError: () => {
      adminStore.notifyError('Failed to update password')
    }
  })
}

const revokeSession = (sessionId) => {
  // Implementation for revoking sessions
  adminStore.notifyInfo('Session revocation not implemented yet')
}

const formatDate = (dateString) => {
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (error) {
    return dateString
  }
}

const route = (name, params = {}) => {
  return window.adminPanel?.route(name, params) || '#'
}
</script>

<style scoped>
@import '../../../css/admin.css' reference;

/* Form styling */
.admin-input {
}

.admin-input:focus {
}

.admin-input-dark {
}

.admin-input-dark:focus {
}

/* Button styling */
.admin-btn-primary {
}
</style>
