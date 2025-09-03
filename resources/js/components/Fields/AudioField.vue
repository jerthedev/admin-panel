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
    <div class="audio-field">
      <!-- Audio Preview -->
      <div v-if="audioPreviewUrl" class="mb-4">
        <div class="audio-preview-container">
          <div class="audio-preview-header">
            <div class="flex items-center space-x-3">
              <div class="audio-icon">
                <svg class="w-8 h-8 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v6.114A4.369 4.369 0 005 11c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.369 4.369 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/>
                </svg>
              </div>
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900" :class="{ 'text-gray-100': isDarkTheme }">
                  Current Audio
                </p>
                <p class="text-xs text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                  {{ currentAudioName || 'Uploaded audio file' }}
                </p>
              </div>
              <button
                v-if="!readonly && !field.meta?.downloadsDisabled"
                type="button"
                class="download-btn"
                @click="downloadAudio"
                title="Download audio"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-4-4m4 4l4-4m5-8H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V6a2 2 0 00-2-2z"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- HTML5 Audio Player -->
          <div class="audio-player mt-3">
            <audio
              ref="audioPlayer"
              :src="audioPreviewUrl"
              :preload="field.meta?.preload || 'metadata'"
              controls
              class="w-full"
              @error="handleAudioError"
            >
              Your browser does not support the audio element.
            </audio>
          </div>

          <!-- Remove button -->
          <div v-if="!readonly" class="audio-actions mt-3">
            <button
              type="button"
              class="remove-audio-btn"
              @click="removeAudio"
              title="Remove audio"
            >
              <XMarkIcon class="h-4 w-4 mr-1" />
              Remove Audio
            </button>
          </div>
        </div>
      </div>

      <!-- File Upload -->
      <div v-if="!readonly && !disabled" class="file-upload-container">
        <div
          class="file-upload-dropzone"
          :class="{
            'file-upload-dropzone-dragover': isDragOver,
            'file-upload-dropzone-error': hasError,
            'file-upload-dropzone-dark': isDarkTheme
          }"
          @drop="handleDrop"
          @dragover="handleDragOver"
          @dragenter="handleDragEnter"
          @dragleave="handleDragLeave"
          @click="triggerFileInput"
        >
          <input
            ref="fileInput"
            type="file"
            :accept="field.acceptedTypes || 'audio/*'"
            :disabled="disabled || readonly"
            class="hidden"
            @change="handleFileSelect"
          />

          <div class="file-upload-content">
            <CloudArrowUpIcon class="mx-auto h-12 w-12 text-gray-400" />
            <div class="mt-4">
              <label class="file-upload-label">
                <span class="file-upload-text">Upload an audio file</span>
                <span class="file-upload-subtext">or drag and drop</span>
              </label>
            </div>
            <p v-if="field.acceptedTypes" class="file-upload-types">
              {{ formatAcceptedTypes(field.acceptedTypes) }}
            </p>
            <p v-if="field.maxSize" class="file-upload-size">
              Maximum file size: {{ formatFileSize(field.maxSize) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Upload Progress -->
      <div v-if="uploadProgress > 0 && uploadProgress < 100" class="upload-progress">
        <div class="upload-progress-bar">
          <div
            class="upload-progress-fill"
            :style="{ width: uploadProgress + '%' }"
          ></div>
        </div>
        <p class="upload-progress-text">Uploading... {{ uploadProgress }}%</p>
      </div>

      <!-- Error Messages -->
      <div v-if="uploadError" class="upload-error">
        <p class="upload-error-text">{{ uploadError }}</p>
      </div>
    </div>
  </BaseField>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { CloudArrowUpIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import BaseField from './BaseField.vue'
import { useAdminStore } from '@/stores/admin'

const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: [String, File, null],
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
    default: 'default'
  }
})

const emit = defineEmits(['update:modelValue'])

// Store
const adminStore = useAdminStore()
const { isDarkTheme } = adminStore

// Refs
const fileInput = ref(null)
const audioPlayer = ref(null)
const isDragOver = ref(false)
const uploadProgress = ref(0)
const uploadError = ref('')

// Computed
const audioPreviewUrl = computed(() => {
  if (props.modelValue instanceof File) {
    return URL.createObjectURL(props.modelValue)
  }
  return props.modelValue
})

const currentAudioName = computed(() => {
  if (props.modelValue instanceof File) {
    return props.modelValue.name
  }
  if (typeof props.modelValue === 'string') {
    return props.modelValue.split('/').pop()
  }
  return null
})

const hasError = computed(() => {
  return props.errors[props.field.attribute] || uploadError.value
})

// Methods
const triggerFileInput = () => {
  if (!props.disabled && !props.readonly) {
    fileInput.value?.click()
  }
}

const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    processFile(file)
  }
}

const handleDrop = (event) => {
  event.preventDefault()
  isDragOver.value = false

  if (props.disabled || props.readonly) return

  const files = event.dataTransfer.files
  if (files.length > 0) {
    processFile(files[0])
  }
}

const handleDragOver = (event) => {
  event.preventDefault()
  if (!props.disabled && !props.readonly) {
    isDragOver.value = true
  }
}

const handleDragEnter = (event) => {
  event.preventDefault()
  if (!props.disabled && !props.readonly) {
    isDragOver.value = true
  }
}

const handleDragLeave = (event) => {
  event.preventDefault()
  // Only set to false if we're leaving the dropzone entirely
  if (!event.currentTarget.contains(event.relatedTarget)) {
    isDragOver.value = false
  }
}

const processFile = (file) => {
  uploadError.value = ''

  // Validate file type
  if (props.field.acceptedTypes && !isValidFileType(file)) {
    uploadError.value = 'Invalid file type. Please select a valid audio file.'
    return
  }

  // Validate file size
  if (props.field.maxSize && file.size > props.field.maxSize * 1024) {
    uploadError.value = `File size exceeds maximum limit of ${formatFileSize(props.field.maxSize)}.`
    return
  }

  emit('update:modelValue', file)
}

const isValidFileType = (file) => {
  const acceptedTypes = props.field.acceptedTypes.toLowerCase()
  const fileType = file.type.toLowerCase()
  const fileName = file.name.toLowerCase()

  // Check MIME type
  if (acceptedTypes.includes(fileType)) {
    return true
  }

  // Check file extension
  const extension = '.' + fileName.split('.').pop()
  return acceptedTypes.includes(extension)
}

const removeAudio = () => {
  emit('update:modelValue', null)
  uploadError.value = ''
  uploadProgress.value = 0

  // Reset file input
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const downloadAudio = () => {
  if (audioPreviewUrl.value) {
    const link = document.createElement('a')
    link.href = audioPreviewUrl.value
    link.download = currentAudioName.value || 'audio-file'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }
}

const handleAudioError = () => {
  uploadError.value = 'Unable to load audio file. The file may be corrupted or in an unsupported format.'
}

const formatAcceptedTypes = (types) => {
  return types.split(',').map(type => type.trim().toUpperCase()).join(', ')
}

const formatFileSize = (sizeInKB) => {
  if (sizeInKB < 1024) {
    return `${sizeInKB} KB`
  }
  return `${(sizeInKB / 1024).toFixed(1)} MB`
}

// Cleanup
watch(() => props.modelValue, (newValue, oldValue) => {
  // Revoke old object URL to prevent memory leaks
  if (oldValue instanceof File && oldValue !== newValue) {
    URL.revokeObjectURL(URL.createObjectURL(oldValue))
  }
})

// Cleanup on unmount
import { onUnmounted } from 'vue'
onUnmounted(() => {
  if (props.modelValue instanceof File) {
    URL.revokeObjectURL(URL.createObjectURL(props.modelValue))
  }
})
</script>

<style scoped>
@import '../../../css/admin.css' reference;

.audio-field {
  @apply space-y-4;
}

.audio-preview-container {
  @apply border border-gray-300 rounded-lg p-4 bg-gray-50;
}

.audio-preview-container.dark {
  @apply border-gray-600 bg-gray-800;
}

.audio-icon {
  @apply flex-shrink-0;
}

.download-btn {
  @apply p-2 text-gray-400 hover:text-gray-600 transition-colors duration-200;
}

.audio-player audio {
  @apply rounded;
}

.audio-actions {
  @apply flex justify-end;
}

.remove-audio-btn {
  @apply inline-flex items-center px-3 py-1 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200;
}

.file-upload-container {
  @apply mt-4;
}

.file-upload-dropzone {
  @apply relative border-2 border-gray-300 border-dashed rounded-lg p-6 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200 cursor-pointer;
}

.file-upload-dropzone-dragover {
  @apply border-blue-400 bg-blue-50;
}

.file-upload-dropzone-error {
  @apply border-red-300 bg-red-50;
}

.file-upload-dropzone-dark {
  @apply border-gray-600 bg-gray-800 hover:border-gray-500;
}

.file-upload-content {
  @apply text-center;
}

.file-upload-label {
  @apply cursor-pointer;
}

.file-upload-text {
  @apply mt-2 block text-sm font-medium text-gray-900;
}

.file-upload-subtext {
  @apply block text-sm text-gray-500;
}

.file-upload-types {
  @apply mt-2 text-xs text-gray-500;
}

.file-upload-size {
  @apply mt-1 text-xs text-gray-500;
}

.upload-progress {
  @apply mt-4;
}

.upload-progress-bar {
  @apply w-full bg-gray-200 rounded-full h-2;
}

.upload-progress-fill {
  @apply bg-blue-600 h-2 rounded-full transition-all duration-300;
}

.upload-progress-text {
  @apply mt-2 text-sm text-gray-600 text-center;
}

.upload-error {
  @apply mt-4;
}

.upload-error-text {
  @apply text-sm text-red-600;
}
</style>
