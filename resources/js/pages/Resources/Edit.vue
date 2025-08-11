<template>
  <AdminLayout :title="`Edit ${resource.singularLabel} - Admin Panel`">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900" :class="{ 'text-white': isDarkTheme }">
            Edit {{ resource.singularLabel }}
          </h1>
          <p class="mt-1 text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
            Update this {{ resource.singularLabel.toLowerCase() }}
          </p>
        </div>

        <!-- Navigation buttons -->
        <div class="flex space-x-3">
          <Link
            :href="route('admin-panel.resources.show', [resource.uriKey, resourceData.id])"
            class="admin-btn-outline"
          >
            <ArrowLeftIcon class="h-4 w-4 mr-2" />
            Back to Details
          </Link>

          <Link
            :href="route('admin-panel.resources.index', resource.uriKey)"
            class="admin-btn-secondary"
          >
            <ListBulletIcon class="h-4 w-4 mr-2" />
            View All
          </Link>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit">
        <div class="space-y-6">
          <!-- Form fields -->
          <div class="admin-card">
            <div class="space-y-6">
              <div
                v-for="field in fields"
                :key="field.attribute"
                class="field-group"
              >
                <component
                  :is="getFieldComponent(field.component)"
                  :field="field"
                  v-model="form[field.attribute]"
                  :errors="errors"
                  :disabled="processing"
                />
              </div>
            </div>
          </div>

          <!-- Form actions -->
          <div class="admin-card">
            <div class="flex items-center justify-between">
              <div class="flex space-x-3">
                <button
                  type="submit"
                  class="admin-btn-primary"
                  :disabled="processing || !form.isDirty"
                >
                  <span v-if="processing" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Updating...
                  </span>
                  <span v-else class="flex items-center">
                    <CheckIcon class="h-4 w-4 mr-2" />
                    Update {{ resource.singularLabel }}
                  </span>
                </button>

                <button
                  type="button"
                  class="admin-btn-secondary"
                  :disabled="processing || !form.isDirty"
                  @click="updateAndContinue"
                >
                  Update & Continue Editing
                </button>
              </div>

              <div class="flex space-x-3">
                <button
                  v-if="form.isDirty"
                  type="button"
                  class="admin-btn-outline"
                  :disabled="processing"
                  @click="resetForm"
                >
                  Reset Changes
                </button>

                <Link
                  :href="route('admin-panel.resources.show', [resource.uriKey, resourceData.id])"
                  class="admin-btn-outline"
                  :disabled="processing"
                >
                  Cancel
                </Link>
              </div>
            </div>

            <!-- Unsaved changes warning -->
            <div
              v-if="form.isDirty"
              class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-md"
              :class="{ 'bg-amber-900 border-amber-700': isDarkTheme }"
            >
              <div class="flex">
                <ExclamationTriangleIcon class="h-5 w-5 text-amber-400" />
                <div class="ml-3">
                  <p class="text-sm text-amber-700" :class="{ 'text-amber-200': isDarkTheme }">
                    You have unsaved changes. Make sure to save your work before leaving this page.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>

      <!-- Last updated info -->
      <div class="admin-card">
        <div class="px-4 py-3 bg-gray-50 text-sm text-gray-500" :class="{ 'bg-gray-700 text-gray-400': isDarkTheme }">
          <div class="flex items-center justify-between">
            <span>
              Resource ID: {{ resourceData.id }}
            </span>
            <span v-if="resourceData.updated_at">
              Last updated: {{ formatDate(resourceData.updated_at) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Unsaved changes confirmation modal -->
    <ConfirmationModal
      v-if="showUnsavedModal"
      title="Unsaved Changes"
      message="You have unsaved changes. Are you sure you want to leave without saving?"
      confirm-text="Leave Without Saving"
      confirm-color="red"
      cancel-text="Stay and Save"
      @confirm="proceedWithNavigation"
      @cancel="saveAndStay"
    />
  </AdminLayout>
</template>

<script setup>
/**
 * Resources Edit Page
 *
 * Form for editing existing resources with change tracking,
 * validation, and unsaved changes protection.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { useAdminStore } from '@/stores/admin'
import {
  ArrowLeftIcon,
  ListBulletIcon,
  CheckIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'
import AdminLayout from '@/components/Layout/AdminLayout.vue'
import ConfirmationModal from '@/components/Common/ConfirmationModal.vue'

// Field components
import TextField from '@/components/Fields/TextField.vue'
import TextareaField from '@/components/Fields/TextareaField.vue'
import EmailField from '@/components/Fields/EmailField.vue'
import PasswordField from '@/components/Fields/PasswordField.vue'
import NumberField from '@/components/Fields/NumberField.vue'
import SelectField from '@/components/Fields/SelectField.vue'
import BooleanField from '@/components/Fields/BooleanField.vue'
import DateField from '@/components/Fields/DateField.vue'
import MarkdownField from '@/components/Fields/MarkdownField.vue'

// Props from Inertia
const props = defineProps({
  resource: Object,
  resourceData: Object,
  fields: Array,
})

// Store
const adminStore = useAdminStore()

// Reactive data
const processing = ref(false)
const continueEditing = ref(false)
const showUnsavedModal = ref(false)
const pendingNavigation = ref(null)
const isSubmitting = ref(false) // Track if we're actively submitting

// Initialize form data with existing values
const initialFormData = {}
props.fields.forEach(field => {
  const fieldData = props.resourceData[field.attribute]

  // Handle different field value formats
  if (fieldData && typeof fieldData === 'object' && fieldData.value !== undefined) {
    initialFormData[field.attribute] = fieldData.value
  } else {
    initialFormData[field.attribute] = fieldData
  }
})

// Form handling
const form = useForm(initialFormData)

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)
const errors = computed(() => form.errors)

// Field component mapping
const fieldComponents = {
  TextField,
  TextareaField,
  EmailField,
  PasswordField,
  NumberField,
  SelectField,
  BooleanField,
  DateField,
  MarkdownField,
}

// Methods
const getFieldComponent = (componentName) => {
  return fieldComponents[componentName] || TextField
}

const submit = () => {
  processing.value = true
  isSubmitting.value = true // Disable navigation guard during submission

  form.put(route('admin-panel.resources.update', [props.resource.uriKey, props.resourceData.id]), {
    onSuccess: () => {
      adminStore.notifySuccess(`${props.resource.singularLabel} updated successfully`)

      if (continueEditing.value) {
        // Stay on edit page
        continueEditing.value = false
      } else {
        // Redirect to show page (handled by controller)
      }
    },
    onError: (errors) => {
      adminStore.notifyError('Please check the form for errors')

      // Focus on first field with error
      const firstErrorField = Object.keys(errors)[0]
      if (firstErrorField) {
        const fieldElement = document.querySelector(`[name="${firstErrorField}"]`)
        if (fieldElement) {
          fieldElement.focus()
        }
      }
    },
    onFinish: () => {
      processing.value = false
      isSubmitting.value = false // Re-enable navigation guard
    }
  })
}

const updateAndContinue = () => {
  continueEditing.value = true
  submit()
}

const saveAndStay = () => {
  // Save the form and close the unsaved changes modal
  processing.value = true
  isSubmitting.value = true // Disable navigation guard during submission

  form.put(route('admin-panel.resources.update', [props.resource.uriKey, props.resourceData.id]), {
    onSuccess: () => {
      adminStore.notifySuccess(`${props.resource.singularLabel} updated successfully`)
      showUnsavedModal.value = false // Close the modal
      pendingNavigation.value = null // Clear pending navigation
    },
    onError: (errors) => {
      adminStore.notifyError('Please check the form for errors')
      showUnsavedModal.value = false // Close modal even on error so user can see form errors
    },
    onFinish: () => {
      processing.value = false
      isSubmitting.value = false // Re-enable navigation guard
    }
  })
}

const resetForm = () => {
  form.reset()
  adminStore.notifyInfo('Form reset to original values')
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'

  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (error) {
    return dateString
  }
}

const handleBeforeUnload = (event) => {
  if (form.isDirty) {
    event.preventDefault()
    event.returnValue = ''
  }
}

const handleInertiaNavigate = (event) => {
  // Don't intercept navigation during active form submission
  if (isSubmitting.value) {
    return
  }

  if (form.isDirty) {
    // Don't intercept form submissions (PUT/POST requests)
    const method = event.detail.method || 'GET'

    if (method.toUpperCase() !== 'GET') {
      return // Allow form submissions to proceed
    }

    event.preventDefault()
    pendingNavigation.value = event.detail.url
    showUnsavedModal.value = true
  }
}

const proceedWithNavigation = () => {
  showUnsavedModal.value = false
  if (pendingNavigation.value) {
    router.visit(pendingNavigation.value)
  }
}

// Use global route() function provided by Ziggy via @routes directive

// Lifecycle
onMounted(() => {
  // Warn about unsaved changes when leaving page
  window.addEventListener('beforeunload', handleBeforeUnload)
  document.addEventListener('inertia:before', handleInertiaNavigate)
})

onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload)
  document.removeEventListener('inertia:before', handleInertiaNavigate)
})
</script>

<style scoped>
.field-group {
}

/* Loading spinner animation */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Disabled button styles */
button:disabled {
}

/* Unsaved changes indicator */
.bg-amber-50 {
  background-color: rgba(251, 191, 36, 0.1);
}

.dark .bg-amber-900 {
  background-color: rgba(146, 64, 14, 0.3);
}

/* Form validation styles */
.field-group:has(.field-error) {
}
</style>
