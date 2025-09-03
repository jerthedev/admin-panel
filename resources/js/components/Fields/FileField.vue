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
    <div class="file-field">
      <!-- File Input -->
      <div
        class="file-input-wrapper"
        :class="{
          'file-input-dragover': isDragOver,
          'file-input-disabled': disabled || readonly
        }"
        @dragover.prevent="handleDragOver"
        @dragleave.prevent="handleDragLeave"
        @drop.prevent="handleDrop"
      >
        <input
          :id="fieldId"
          ref="fileInputRef"
          type="file"
          :accept="field.acceptedTypes"
          :multiple="field.multiple"
          :disabled="disabled || readonly"
          class="file-input-hidden"
          @change="handleFileSelect"
        />

        <div class="file-input-content">
          <DocumentIcon class="h-8 w-8 text-gray-400 mb-2" />
          <p class="text-sm text-gray-600" :class="{ 'text-gray-400': isDarkTheme }">
            <button
              type="button"
              class="text-blue-600 hover:text-blue-500 font-medium"
              :class="{ 'text-blue-400': isDarkTheme }"
              @click="openFileDialog"
            >
              Choose file{{ field.multiple ? 's' : '' }}
            </button>
            or drag and drop
          </p>
          <p v-if="field.acceptedTypes" class="text-xs text-gray-500 mt-1">
            Accepted types: {{ field.acceptedTypes }}
          </p>
          <p v-if="field.maxSize" class="text-xs text-gray-500">
            Max size: {{ formatFileSize(field.maxSize * 1024) }}
          </p>
        </div>
      </div>

      <!-- Current File Display -->
      <div v-if="currentFile" class="mt-3 p-3 bg-gray-50 rounded-md" :class="{ 'bg-gray-800': isDarkTheme }">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <DocumentIcon class="h-5 w-5 text-gray-400 mr-2" />
            <span class="text-sm text-gray-700" :class="{ 'text-gray-300': isDarkTheme }">
              {{ getFileName(currentFile) }}
            </span>
          </div>
          <div class="flex items-center space-x-2">
            <button
              v-if="!field.downloadsDisabled && currentFile"
              type="button"
              class="text-blue-600 hover:text-blue-500 text-sm"
              :class="{ 'text-blue-400': isDarkTheme }"
              @click="downloadFile"
            >
              Download
            </button>
            <button
              v-if="!readonly && field.deletable !== false"
              type="button"
              class="text-red-600 hover:text-red-500 text-sm"
              :class="{ 'text-red-400': isDarkTheme }"
              @click="removeFile"
            >
              Remove
            </button>
          </div>
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
 * FileField Component
 * 
 * A file upload field with drag-and-drop support, type restrictions,
 * and progress indication.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed, ref, watch } from 'vue'
import { DocumentIcon } from '@heroicons/vue/24/outline'
import BaseField from './BaseField.vue'
import { useAdminStore } from '@/stores/admin'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: [String, File, Array],
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

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `file-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const currentFile = computed(() => {
  return props.modelValue
})

// Methods
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
  
  const file = files[0] // For now, handle single file
  
  // Validate file
  if (!validateFile(file)) {
    return
  }
  
  // Emit the file
  emit('update:modelValue', file)
  emit('change', file)
  
  // Simulate upload progress (in real implementation, this would be actual upload)
  simulateUpload()
}

const validateFile = (file) => {
  // Check file size
  if (props.field.maxSize && file.size > props.field.maxSize * 1024) {
    uploadError.value = `File size exceeds maximum allowed size of ${formatFileSize(props.field.maxSize * 1024)}`
    return false
  }
  
  // Check file type
  if (props.field.acceptedTypes) {
    const acceptedTypes = props.field.acceptedTypes.split(',').map(type => type.trim())
    const fileExtension = '.' + file.name.split('.').pop().toLowerCase()
    
    if (!acceptedTypes.includes(fileExtension)) {
      uploadError.value = `File type not allowed. Accepted types: ${props.field.acceptedTypes}`
      return false
    }
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

const removeFile = () => {
  if (props.field.deletable === false) {
    return
  }

  emit('update:modelValue', null)
  emit('change', null)

  // Clear the file input
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

const downloadFile = () => {
  if (props.field.downloadsDisabled || !currentFile.value) {
    return
  }

  // For now, just trigger a download using the file URL
  // In a real implementation, this would make an API call to the download endpoint
  if (typeof currentFile.value === 'string') {
    // If it's a file path, construct the download URL
    const downloadUrl = `/admin/files/download?path=${encodeURIComponent(currentFile.value)}&field=${props.field.attribute}`
    window.open(downloadUrl, '_blank')
  } else if (currentFile.value instanceof File) {
    // If it's a File object, create a blob URL
    const url = URL.createObjectURL(currentFile.value)
    const a = document.createElement('a')
    a.href = url
    a.download = currentFile.value.name
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  }
}

const getFileName = (file) => {
  if (typeof file === 'string') {
    return file.split('/').pop()
  }
  if (file && file.name) {
    return file.name
  }
  return 'Unknown file'
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

defineExpose({
  focus
})
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.file-input-wrapper {
  @apply border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer transition-colors;
}

.file-input-wrapper:hover {
  @apply border-gray-400 bg-gray-50;
}

.file-input-dragover {
  @apply border-blue-400 bg-blue-50;
}

.file-input-disabled {
  @apply cursor-not-allowed opacity-50;
}

.file-input-hidden {
  @apply sr-only;
}

.dark .file-input-wrapper:hover {
  @apply bg-gray-800;
}

.dark .file-input-dragover {
  @apply bg-gray-800;
}
</style>
