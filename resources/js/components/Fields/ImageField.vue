<template>
  <BaseField
    :field="field"
    :model-value="modelValue"
    :errors="errors"
    :disabled="disabled"
    :readonly="readonly"
    :size="size"
    v-bind="$attrs"
  >
    <div class="image-field">
      <!-- Image Preview -->
      <div v-if="imagePreviewUrl" class="mb-4">
        <div
          class="image-preview-container"
          :class="{
            'image-preview-squared': field.squared,
            'image-preview-rectangle': !field.squared
          }"
        >
          <img
            :src="imagePreviewUrl"
            :alt="field.name"
            class="image-preview"
            :class="{
              'image-preview-squared': field.squared,
              'image-preview-rounded': field.rounded
            }"
            @error="handleImageError"
          />
          
          <!-- Remove button overlay -->
          <div v-if="!readonly" class="image-preview-overlay">
            <button
              type="button"
              class="image-remove-btn"
              @click="removeImage"
              title="Remove image"
            >
              <XMarkIcon class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- File Input -->
      <div
        class="image-input-wrapper"
        :class="{
          'image-input-dragover': isDragOver,
          'image-input-disabled': disabled || readonly,
          'image-input-has-image': imagePreviewUrl
        }"
        @dragover.prevent="handleDragOver"
        @dragleave.prevent="handleDragLeave"
        @drop.prevent="handleDrop"
      >
        <input
          :id="fieldId"
          ref="fileInputRef"
          type="file"
          accept="image/*"
          :disabled="disabled || readonly"
          class="image-input-hidden"
          @change="handleFileSelect"
        />

        <div class="image-input-content">
          <PhotoIcon class="h-8 w-8 text-gray-400 mb-2" />
          <p class="text-sm text-gray-600" :class="{ 'text-gray-400': isDarkTheme }">
            <button
              type="button"
              class="text-blue-600 hover:text-blue-500 font-medium"
              :class="{ 'text-blue-400': isDarkTheme }"
              @click="openFileDialog"
            >
              {{ imagePreviewUrl ? 'Change image' : 'Choose image' }}
            </button>
            {{ !imagePreviewUrl ? ' or drag and drop' : '' }}
          </p>
          <p v-if="field.acceptedTypes" class="text-xs text-gray-500 mt-1">
            Accepted types: {{ field.acceptedTypes }}
          </p>
          <p v-if="field.maxSize" class="text-xs text-gray-500">
            Max size: {{ formatFileSize(field.maxSize * 1024) }}
          </p>
        </div>
      </div>

      <!-- Upload Progress -->
      <div v-if="uploadProgress > 0 && uploadProgress < 100" class="mt-3">
        <div class="bg-gray-200 rounded-full h-2" :class="{ 'bg-gray-700': isDarkTheme }">
          <div
            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
            :style="{ width: uploadProgress + '%' }"
          ></div>
        </div>
        <p class="text-xs text-gray-500 mt-1">Uploading... {{ uploadProgress }}%</p>
      </div>

      <!-- Error Display -->
      <div v-if="uploadError" class="mt-3 p-2 bg-red-50 border border-red-200 rounded-md">
        <p class="text-sm text-red-600">{{ uploadError }}</p>
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * ImageField Component
 * 
 * An image upload field with preview, drag-and-drop support,
 * and image-specific validation.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed, ref, watch } from 'vue'
import { PhotoIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import BaseField from './BaseField.vue'
import { useAdminStore } from '@/stores/admin'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: [String, File],
    default: null
  },
  errors: {
    type: Object,
    default: () => ({})
  },
  disabled: {
    type: Boolean,
    default: false
  },
  readonly: {
    type: Boolean,
    default: false
  },
  size: {
    type: String,
    default: 'default',
    validator: (value) => ['small', 'default', 'large'].includes(value)
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'change'])

// Refs
const fileInputRef = ref(null)
const isDragOver = ref(false)
const uploadProgress = ref(0)
const uploadError = ref(null)
const imagePreviewUrl = ref(null)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `image-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

// Methods
const updateImagePreview = (value) => {
  if (!value) {
    imagePreviewUrl.value = null
    return
  }

  if (value instanceof File) {
    // Create object URL for file preview
    imagePreviewUrl.value = URL.createObjectURL(value)
  } else if (typeof value === 'string') {
    // Use the string as URL (could be a path or full URL)
    imagePreviewUrl.value = value.startsWith('http') ? value : `/storage/${value}`
  }
}

const openFileDialog = () => {
  if (!props.disabled && !props.readonly) {
    fileInputRef.value?.click()
  }
}

const handleFileSelect = (event) => {
  const files = event.target.files
  if (files && files.length > 0) {
    handleFiles(files)
  }
}

const handleDragOver = (event) => {
  if (!props.disabled && !props.readonly) {
    isDragOver.value = true
  }
}

const handleDragLeave = (event) => {
  isDragOver.value = false
}

const handleDrop = (event) => {
  isDragOver.value = false
  
  if (props.disabled || props.readonly) return
  
  const files = event.dataTransfer.files
  if (files && files.length > 0) {
    handleFiles(files)
  }
}

const handleFiles = (files) => {
  uploadError.value = null
  
  const file = files[0]
  
  // Validate file
  if (!validateImage(file)) {
    return
  }
  
  // Emit the file
  emit('update:modelValue', file)
  emit('change', file)
  
  // Simulate upload progress
  simulateUpload()
}

const validateImage = (file) => {
  // Check if it's an image
  if (!file.type.startsWith('image/')) {
    uploadError.value = 'Please select an image file'
    return false
  }
  
  // Check file size
  if (props.field.maxSize && file.size > props.field.maxSize * 1024) {
    uploadError.value = `Image size exceeds maximum allowed size of ${formatFileSize(props.field.maxSize * 1024)}`
    return false
  }
  
  return true
}

const simulateUpload = () => {
  uploadProgress.value = 0
  const interval = setInterval(() => {
    uploadProgress.value += 10
    if (uploadProgress.value >= 100) {
      clearInterval(interval)
      setTimeout(() => {
        uploadProgress.value = 0
      }, 1000)
    }
  }, 100)
}

const removeImage = () => {
  // Clean up object URL if it exists
  if (imagePreviewUrl.value && imagePreviewUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(imagePreviewUrl.value)
  }
  
  imagePreviewUrl.value = null
  
  emit('update:modelValue', null)
  emit('change', null)
  
  // Clear the file input
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

const handleImageError = () => {
  uploadError.value = 'Failed to load image preview'
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// Focus method for external use
const focus = () => {
  fileInputRef.value?.focus()
}

// Watch for model value changes
watch(() => props.modelValue, (newValue) => {
  updateImagePreview(newValue)
}, { immediate: true })

defineExpose({
  focus
})
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.image-preview-container {
  @apply relative inline-block;
}

.image-preview-squared {
  @apply w-32 h-32 object-cover rounded-lg;
}

.image-preview-rectangle {
  @apply max-w-xs max-h-48 object-contain rounded-lg;
}

.image-preview-rounded {
  @apply rounded-full;
}

.image-preview {
  @apply border border-gray-200 shadow-sm;
}

.image-preview-overlay {
  @apply absolute top-2 right-2 opacity-0 transition-opacity;
}

.image-preview-container:hover .image-preview-overlay {
  @apply opacity-100;
}

.image-remove-btn {
  @apply bg-red-600 text-white p-1 rounded-full hover:bg-red-700 transition-colors;
}

.image-input-wrapper {
  @apply border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer transition-colors;
}

.image-input-wrapper:hover {
  @apply border-gray-400 bg-gray-50;
}

.image-input-dragover {
  @apply border-blue-400 bg-blue-50;
}

.image-input-disabled {
  @apply cursor-not-allowed opacity-50;
}

.image-input-has-image {
  @apply p-4;
}

.image-input-hidden {
  @apply sr-only;
}

.dark .image-input-wrapper:hover {
  @apply bg-gray-800;
}

.dark .image-input-dragover {
  @apply bg-gray-800;
}
</style>
