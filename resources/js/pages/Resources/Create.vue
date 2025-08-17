<template>
  <AdminLayout :title="`Create ${resource.singularLabel} - Admin Panel`">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900" :class="{ 'text-white': isDarkTheme }">
            Create {{ resource.singularLabel }}
          </h1>
          <p class="mt-1 text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
            Add a new {{ resource.singularLabel.toLowerCase() }} to your {{ resource.label.toLowerCase() }}
          </p>
        </div>

        <!-- Back button -->
        <Link
          :href="route('admin-panel.resources.index', resource.uriKey)"
          class="admin-btn-outline"
        >
          <ArrowLeftIcon class="h-4 w-4 mr-2" />
          Back to {{ resource.label }}
        </Link>
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
                  :disabled="processing"
                >
                  <span v-if="processing" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Creating...
                  </span>
                  <span v-else class="flex items-center">
                    <CheckIcon class="h-4 w-4 mr-2" />
                    Create {{ resource.singularLabel }}
                  </span>
                </button>

                <button
                  type="button"
                  class="admin-btn-secondary"
                  :disabled="processing"
                  @click="createAndAddAnother"
                >
                  Create & Add Another
                </button>
              </div>

              <Link
                :href="route('admin-panel.resources.index', resource.uriKey)"
                class="admin-btn-outline"
                :disabled="processing"
              >
                Cancel
              </Link>
            </div>
          </div>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
/**
 * Resources Create Page
 *
 * Form for creating new resources with field validation,
 * error handling, and user-friendly interface.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, reactive, computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { useAdminStore } from '@/stores/admin'
import { ArrowLeftIcon, CheckIcon } from '@heroicons/vue/24/outline'
import AdminLayout from '@/components/Layout/AdminLayout.vue'

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
import ColorField from '@/components/Fields/ColorField.vue'

// Props from Inertia
const props = defineProps({
  resource: Object,
  fields: Array,
})

// Store
const adminStore = useAdminStore()

// Reactive data
const processing = ref(false)
const createAnother = ref(false)

// Initialize form data
const initialFormData = {}
props.fields.forEach(field => {
  // Set default values based on field type
  switch (field.component) {
    case 'BooleanField':
      initialFormData[field.attribute] = field.falseValue || false
      break
    case 'NumberField':
      initialFormData[field.attribute] = null
      break
    case 'SelectField':
      initialFormData[field.attribute] = null
      break
    default:
      initialFormData[field.attribute] = ''
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
  ColorField,
}

// Methods
const getFieldComponent = (componentName) => {
  return fieldComponents[componentName] || TextField
}

const submit = () => {
  processing.value = true

  form.post(route('admin-panel.resources.store', props.resource.uriKey), {
    onSuccess: (page) => {
      adminStore.notifySuccess(`${props.resource.singularLabel} created successfully`)

      if (createAnother.value) {
        // Reset form and stay on create page
        form.reset()
        createAnother.value = false
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
    }
  })
}

const createAndAddAnother = () => {
  createAnother.value = true
  submit()
}

// Use global route() function provided by Ziggy via @routes directive
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

/* Form validation styles */
.field-group:has(.field-error) {
}

/* Disabled state for form */
form:has(button[disabled]) {
}

form:has(button[disabled]) button:not([disabled]) {
}
</style>
