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
    <div class="media-library-audio-field">
      <!-- Existing Audio Display -->
      <div v-if="existingAudio" class="mb-4">
        <div
          class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border"
          :class="{ 'bg-gray-800 border-gray-700': isDarkTheme }"
        >
          <div class="flex items-center space-x-4">
            <!-- Audio Icon -->
            <div class="flex-shrink-0">
              <MusicalNoteIcon class="h-10 w-10 text-blue-500" />
            </div>
            
            <!-- Audio Info -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900" :class="{ 'text-white': isDarkTheme }">
                {{ audioMetadata.name || 'Audio File' }}
              </p>
              <div class="flex items-center space-x-4 text-xs text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                <span v-if="audioMetadata.human_readable_size">{{ audioMetadata.human_readable_size }}</span>
                <span v-if="audioMetadata.formatted_duration">{{ audioMetadata.formatted_duration }}</span>
                <span v-if="audioMetadata.bitrate">{{ audioMetadata.bitrate }}kbps</span>
              </div>
            </div>
          </div>

          <!-- Audio Actions -->
          <div class="flex items-center space-x-2">
            <!-- Download button -->
            <button
              v-if="!field.downloadsDisabled && audioUrl"
              type="button"
              class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
              :class="{ 'hover:text-gray-300': isDarkTheme }"
              @click="downloadAudio"
              title="Download audio"
            >
              <ArrowDownTrayIcon class="h-5 w-5" />
            </button>
            
            <!-- Remove button -->
            <button
              v-if="!readonly"
              type="button"
              class="p-2 text-red-400 hover:text-red-600 transition-colors"
              @click="removeAudio"
              title="Remove audio"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>
        </div>

        <!-- HTML5 Audio Player -->
        <div v-if="audioUrl" class="audio-player mt-3">
          <audio
            ref="audioPlayer"
            :src="audioUrl"
            :preload="field.preload || 'metadata'"
            controls
            class="w-full"
            @error="handleAudioError"
          >
            Your browser does not support the audio element.
          </audio>
        </div>
      </div>

      <!-- Upload Area -->
      <div
        v-if="!readonly && (!field.singleFile || !existingAudio)"
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
          <MusicalNoteIcon class="mx-auto h-12 w-12 text-gray-400" />
          <div class="mt-4">
            <p class="text-sm font-medium text-gray-900" :class="{ 'text-white': isDarkTheme }">
              {{ existingAudio ? 'Replace audio file' : 'Upload audio file' }}
            </p>
            <p class="text-xs text-gray-500 mt-1" :class="{ 'text-gray-400': isDarkTheme }">
              Drag and drop or click to browse
            </p>
            <p v-if="acceptedTypesDisplay" class="text-xs text-gray-400 mt-1">
              {{ acceptedTypesDisplay }}
            </p>
            <p v-if="field.maxFileSize" class="text-xs text-gray-400 mt-1">
              Max size: {{ formatFileSize(field.maxFileSize * 1024) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Upload Progress -->
      <div v-if="uploadProgress > 0 && uploadProgress < 100" class="mt-3">
        <div class="bg-gray-200 rounded-full h-2" :class="{ 'bg-gray-700': isDarkTheme }">
          <div
            class="bg-blue-500 h-2 rounded-full transition-all duration-300"
            :style="{ width: `${uploadProgress}%` }"
          ></div>
        </div>
        <p class="text-xs text-gray-500 mt-1" :class="{ 'text-gray-400': isDarkTheme }">
          Uploading... {{ uploadProgress }}%
        </p>
      </div>

      <!-- Upload Error -->
      <div v-if="uploadError" class="mt-3 p-3 bg-red-50 border border-red-200 rounded-md" :class="{ 'bg-red-900 border-red-700': isDarkTheme }">
        <div class="flex">
          <ExclamationCircleIcon class="h-5 w-5 text-red-400" />
          <div class="ml-3">
            <p class="text-sm text-red-800" :class="{ 'text-red-200': isDarkTheme }">
              {{ uploadError }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * MediaLibraryAudioField Component
 * 
 * An audio upload field with Media Library integration, audio preview/playback,
 * drag-and-drop support, progress indicators, and audio management capabilities.
 * 100% compatible with Nova Audio Field API with additional Media Library features.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed, ref, watch } from 'vue'
import {
  MusicalNoteIcon,
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
    type: [String, Array, Object, File],
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
const audioPlayer = ref(null)
const isDragOver = ref(false)
const uploadProgress = ref(0)
const uploadError = ref(null)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `media-library-audio-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const existingAudio = computed(() => {
  if (!props.modelValue) return null
  
  if (props.modelValue instanceof File) {
    return props.modelValue
  }
  
  if (typeof props.modelValue === 'object' && props.modelValue.url) {
    return props.modelValue
  }
  
  return null
})

const audioUrl = computed(() => {
  if (!existingAudio.value) return null
  
  if (existingAudio.value instanceof File) {
    return URL.createObjectURL(existingAudio.value)
  }
  
  if (existingAudio.value.url) {
    return existingAudio.value.url
  }
  
  return props.field.audioUrl || null
})

const audioMetadata = computed(() => {
  if (!existingAudio.value) return {}
  
  if (existingAudio.value instanceof File) {
    return {
      name: existingAudio.value.name,
      size: existingAudio.value.size,
      human_readable_size: formatFileSize(existingAudio.value.size),
      mime_type: existingAudio.value.type
    }
  }
  
  return props.field.audioMetadata || existingAudio.value || {}
})

const acceptedTypes = computed(() => {
  if (props.field.acceptedMimeTypes && Array.isArray(props.field.acceptedMimeTypes)) {
    return props.field.acceptedMimeTypes.join(',')
  }
  return 'audio/*'
})

const acceptedTypesDisplay = computed(() => {
  if (props.field.acceptedMimeTypes && Array.isArray(props.field.acceptedMimeTypes)) {
    const extensions = props.field.acceptedMimeTypes
      .map(type => type.replace('audio/', '').toUpperCase())
      .join(', ')
    return `Supported formats: ${extensions}`
  }
  return 'Supported formats: MP3, WAV, OGG, M4A, AAC, FLAC'
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
    const currentFiles = existingAudio.value ? [existingAudio.value] : []
    emit('update:modelValue', [...currentFiles, ...fileArray])
  }
  
  emit('change', fileArray)
}

const validateFile = (file) => {
  // Check file type
  if (props.field.acceptedMimeTypes && Array.isArray(props.field.acceptedMimeTypes)) {
    if (!props.field.acceptedMimeTypes.includes(file.type)) {
      uploadError.value = `Invalid file type. Accepted types: ${props.field.acceptedMimeTypes.join(', ')}`
      return false
    }
  } else if (!file.type.startsWith('audio/')) {
    uploadError.value = 'Please select an audio file'
    return false
  }

  // Check file size
  if (props.field.maxFileSize && file.size > props.field.maxFileSize * 1024) {
    uploadError.value = `File size exceeds maximum limit of ${formatFileSize(props.field.maxFileSize * 1024)}`
    return false
  }

  return true
}

const removeAudio = () => {
  if (props.readonly) return

  emit('update:modelValue', null)
  emit('change', null)

  // Reset file input
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

const downloadAudio = () => {
  if (!audioUrl.value) return

  const link = document.createElement('a')
  link.href = audioUrl.value
  link.download = audioMetadata.value.name || 'audio-file'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const handleAudioError = (event) => {
  console.error('Audio playback error:', event)
  uploadError.value = 'Unable to load audio file for playback'
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 B'

  const units = ['B', 'KB', 'MB', 'GB', 'TB']
  const power = Math.floor(Math.log(bytes) / Math.log(1024))
  const size = bytes / Math.pow(1024, power)

  return `${size.toFixed(power === 0 ? 0 : 1)} ${units[power]}`
}

// Watchers
watch(() => props.modelValue, (newValue) => {
  uploadProgress.value = 0
  uploadError.value = null
}, { deep: true })

// Cleanup
const cleanup = () => {
  if (existingAudio.value instanceof File && audioUrl.value) {
    URL.revokeObjectURL(audioUrl.value)
  }
}

// Lifecycle
import { onUnmounted } from 'vue'
onUnmounted(cleanup)
</script>

<style scoped>
.media-library-audio-field {
  @apply w-full;
}

.upload-area {
  @apply border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer transition-colors;
}

.upload-area:hover {
  @apply border-gray-400 bg-gray-50;
}

.upload-area-dragover {
  @apply border-blue-500 bg-blue-50;
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

.upload-area-dark.upload-area-dragover {
  @apply border-blue-400 bg-blue-900;
}

.upload-content {
  @apply flex flex-col items-center justify-center;
}

.audio-player audio {
  @apply w-full h-10 bg-gray-100 rounded;
}

.audio-player audio::-webkit-media-controls-panel {
  @apply bg-gray-100;
}

/* Dark theme audio player */
.dark .audio-player audio {
  @apply bg-gray-800;
}

.dark .audio-player audio::-webkit-media-controls-panel {
  @apply bg-gray-800;
}

/* Focus styles */
.upload-area:focus-within {
  @apply ring-2 ring-blue-500 ring-offset-2;
}

/* Animation for upload progress */
.upload-progress {
  @apply transition-all duration-300 ease-in-out;
}

/* Responsive design */
@media (max-width: 640px) {
  .upload-area {
    @apply p-4;
  }

  .upload-content {
    @apply text-sm;
  }
}
</style>
