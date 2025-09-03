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
    <div class="media-library-file-field">
      <!-- Existing Files Display -->
      <div v-if="existingFiles.length > 0" class="mb-4">
        <div class="space-y-2">
          <div
            v-for="(file, index) in existingFiles"
            :key="file.id || index"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border"
            :class="{ 'bg-gray-800 border-gray-700': isDarkTheme }"
          >
            <div class="flex items-center space-x-3">
              <DocumentIcon class="h-8 w-8 text-gray-400" />
              <div>
                <p class="text-sm font-medium text-gray-900" :class="{ 'text-white': isDarkTheme }">
                  {{ file.name || file.file_name || 'Unknown File' }}
                </p>
                <p class="text-xs text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                  {{ file.human_readable_size || formatFileSize(file.size || 0) }} • 
                  {{ file.mime_type || 'Unknown type' }}
                </p>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <!-- Download button -->
              <button
                v-if="file.url"
                type="button"
                class="p-1 text-gray-400 hover:text-gray-600 transition-colors"
                :class="{ 'hover:text-gray-300': isDarkTheme }"
                @click="downloadFile(file)"
                title="Download file"
              >
                <ArrowDownTrayIcon class="h-4 w-4" />
              </button>
              <!-- Remove button -->
              <button
                v-if="!readonly"
                type="button"
                class="p-1 text-red-400 hover:text-red-600 transition-colors"
                @click="removeFile(index)"
                title="Remove file"
              >
                <XMarkIcon class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Upload Area -->
      <div
        v-if="!readonly && (!field.singleFile || existingFiles.length === 0)"
        class="upload-area"
        :class="{
          'upload-area-dragover': isDragOver,
          'upload-area-disabled': disabled,
          'upload-area-dark': isDarkTheme
        }"
        @dragover.prevent="handleDragOver"
        @dragleave.prevent="handleDragLeave"
        @drop.prevent="handleDrop"
        @click="openFileDialog"
      >
        <input
          :id="fieldId"
          ref="fileInputRef"
          type="file"
          :accept="acceptedTypes"
          :multiple="field.multiple"
          :disabled="disabled"
          class="hidden"
          @change="handleFileSelect"
        />

        <div class="upload-content">
          <CloudArrowUpIcon class="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <p class="text-sm text-gray-600 mb-2" :class="{ 'text-gray-300': isDarkTheme }">
            <span class="font-medium">Click to upload</span> or drag and drop
          </p>
          <p class="text-xs text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
            {{ acceptedTypesText }}
            <span v-if="field.maxFileSize"> • Max {{ formatFileSize(field.maxFileSize * 1024) }}</span>
          </p>
        </div>
      </div>

      <!-- Upload Progress -->
      <div v-if="uploadProgress > 0 && uploadProgress < 100" class="mt-4">
        <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
          <span>Uploading...</span>
          <span>{{ uploadProgress }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div
            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
            :style="{ width: uploadProgress + '%' }"
          ></div>
        </div>
      </div>

      <!-- Upload Error -->
      <div v-if="uploadError" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
        <div class="flex">
          <ExclamationCircleIcon class="h-5 w-5 text-red-400" />
          <div class="ml-3">
            <p class="text-sm text-red-800">{{ uploadError }}</p>
          </div>
        </div>
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * MediaLibraryFileField Component
 * 
 * A file upload field with Media Library integration, drag-and-drop support,
 * progress indicators, and file management capabilities.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed, ref, watch } from 'vue'
import {
  DocumentIcon,
  CloudArrowUpIcon,
  ArrowDownTrayIcon,
  XMarkIcon,
  ExclamationCircleIcon
} from '@heroicons/vue/24/outline'
import BaseField from './BaseField.vue'
import { useAdminStore } from '@/stores/admin'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: [String, Array, Object],
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
  return `media-library-file-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const existingFiles = computed(() => {
  if (!props.modelValue) return []
  
  if (Array.isArray(props.modelValue)) {
    return props.modelValue
  }
  
  if (typeof props.modelValue === 'object') {
    return [props.modelValue]
  }
  
  return []
})

const acceptedTypes = computed(() => {
  if (props.field.acceptedMimeTypes && props.field.acceptedMimeTypes.length > 0) {
    return props.field.acceptedMimeTypes.join(',')
  }
  return '*/*'
})

const acceptedTypesText = computed(() => {
  if (props.field.acceptedMimeTypes && props.field.acceptedMimeTypes.length > 0) {
    const types = props.field.acceptedMimeTypes.map(type => {
      if (type.startsWith('application/pdf')) return 'PDF'
      if (type.startsWith('application/msword')) return 'DOC'
      if (type.startsWith('application/vnd.openxmlformats')) return 'DOCX'
      if (type.startsWith('text/')) return 'TXT'
      if (type.startsWith('application/zip')) return 'ZIP'
      return type.split('/')[1]?.toUpperCase() || type
    })
    return types.join(', ') + ' files'
  }
  return 'All file types'
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
  
  // Validate files
  for (let file of files) {
    if (!validateFile(file)) {
      return
    }
  }
  
  // Process files
  const fileArray = Array.from(files)
  
  if (props.field.singleFile) {
    emit('update:modelValue', fileArray[0])
  } else {
    const currentFiles = existingFiles.value
    emit('update:modelValue', [...currentFiles, ...fileArray])
  }
  
  emit('change', fileArray)
}

const validateFile = (file) => {
  // Check file size
  if (props.field.maxFileSize && file.size > props.field.maxFileSize * 1024) {
    uploadError.value = `File size exceeds maximum allowed size of ${formatFileSize(props.field.maxFileSize * 1024)}`
    return false
  }
  
  // Check MIME type
  if (props.field.acceptedMimeTypes && props.field.acceptedMimeTypes.length > 0) {
    if (!props.field.acceptedMimeTypes.includes(file.type)) {
      uploadError.value = `File type not allowed. Accepted types: ${acceptedTypesText.value}`
      return false
    }
  }
  
  return true
}

const removeFile = (index) => {
  const currentFiles = [...existingFiles.value]
  currentFiles.splice(index, 1)
  
  if (props.field.singleFile) {
    emit('update:modelValue', currentFiles[0] || null)
  } else {
    emit('update:modelValue', currentFiles)
  }
  
  emit('change', currentFiles)
}

const downloadFile = (file) => {
  if (file.url) {
    window.open(file.url, '_blank')
  }
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 B'
  
  const units = ['B', 'KB', 'MB', 'GB', 'TB']
  const power = Math.floor(Math.log(bytes) / Math.log(1024))
  const size = bytes / Math.pow(1024, power)
  
  return `${size.toFixed(power > 0 ? 1 : 0)} ${units[power]}`
}

const handleFocus = (event) => {
  emit('focus', event)
}

const handleBlur = (event) => {
  emit('blur', event)
}
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.upload-area {
  @apply border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer transition-colors;
}

.upload-area:hover {
  @apply border-gray-400 bg-gray-50;
}

.upload-area-dragover {
  @apply border-blue-400 bg-blue-50;
}

.upload-area-disabled {
  @apply cursor-not-allowed opacity-50;
}

.upload-area-dark {
  @apply border-gray-600 bg-gray-800;
}

.upload-area-dark:hover {
  @apply border-gray-500 bg-gray-700;
}

.upload-content {
  @apply pointer-events-none;
}
</style>
