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
    <div class="media-library-image-field">
      <!-- Existing Images Gallery -->
      <div v-if="existingImages.length > 0" class="mb-4">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          <div
            v-for="(image, index) in existingImages"
            :key="image.id || index"
            class="relative group aspect-square bg-gray-100 rounded-lg overflow-hidden border-2 border-transparent hover:border-blue-300 transition-colors"
            :class="{ 'bg-gray-800': isDarkTheme }"
          >
            <!-- Image Preview -->
            <img
              :src="getImagePreviewUrl(image)"
              :alt="image.name || image.file_name || 'Image'"
              class="w-full h-full object-cover cursor-pointer"
              @click="openLightbox(image, index)"
              @error="handleImageError"
            />

            <!-- Image Overlay -->
            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-200 flex items-center justify-center">
              <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex space-x-2">
                <!-- View button -->
                <button
                  type="button"
                  class="p-2 bg-white bg-opacity-90 rounded-full text-gray-700 hover:bg-opacity-100 transition-all"
                  @click="openLightbox(image, index)"
                  title="View image"
                >
                  <EyeIcon class="h-4 w-4" />
                </button>
                <!-- Remove button -->
                <button
                  v-if="!readonly"
                  type="button"
                  class="p-2 bg-red-500 bg-opacity-90 rounded-full text-white hover:bg-opacity-100 transition-all"
                  @click="removeImage(index)"
                  title="Remove image"
                >
                  <XMarkIcon class="h-4 w-4" />
                </button>
              </div>
            </div>

            <!-- Image Info -->
            <div v-if="field.showImageDimensions && image.width && image.height" class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-75 text-white text-xs p-1 text-center">
              {{ image.width }} × {{ image.height }}
            </div>

            <!-- Drag Handle for Reordering -->
            <div
              v-if="!readonly && field.multiple && existingImages.length > 1"
              class="absolute top-1 left-1 p-1 bg-black bg-opacity-50 rounded cursor-move opacity-0 group-hover:opacity-100 transition-opacity"
              @mousedown="startDrag(index)"
            >
              <Bars3Icon class="h-3 w-3 text-white" />
            </div>
          </div>
        </div>

        <!-- Image Count and Limit Info -->
        <div v-if="field.limit" class="mt-2 text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
          {{ existingImages.length }} of {{ field.limit }} images
        </div>
      </div>

      <!-- Upload Area -->
      <div
        v-if="!readonly && (!field.limit || existingImages.length < field.limit)"
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
          <PhotoIcon class="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <p class="text-sm text-gray-600 mb-2" :class="{ 'text-gray-300': isDarkTheme }">
            <span class="font-medium">Click to upload images</span> or drag and drop
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
          <span>Uploading images...</span>
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

    <!-- Lightbox Modal -->
    <div
      v-if="lightboxImage"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75"
      @click="closeLightbox"
    >
      <div class="relative max-w-4xl max-h-full p-4">
        <img
          :src="getLightboxImageUrl(lightboxImage)"
          :alt="lightboxImage.name || 'Image'"
          class="max-w-full max-h-full object-contain"
          @click.stop
        />
        <button
          type="button"
          class="absolute top-4 right-4 p-2 bg-black bg-opacity-50 rounded-full text-white hover:bg-opacity-75 transition-all"
          @click="closeLightbox"
        >
          <XMarkIcon class="h-6 w-6" />
        </button>

        <!-- Navigation arrows for multiple images -->
        <button
          v-if="existingImages.length > 1 && lightboxIndex > 0"
          type="button"
          class="absolute left-4 top-1/2 transform -translate-y-1/2 p-2 bg-black bg-opacity-50 rounded-full text-white hover:bg-opacity-75 transition-all"
          @click.stop="navigateLightbox(-1)"
        >
          <ChevronLeftIcon class="h-6 w-6" />
        </button>
        <button
          v-if="existingImages.length > 1 && lightboxIndex < existingImages.length - 1"
          type="button"
          class="absolute right-4 top-1/2 transform -translate-y-1/2 p-2 bg-black bg-opacity-50 rounded-full text-white hover:bg-opacity-75 transition-all"
          @click.stop="navigateLightbox(1)"
        >
          <ChevronRightIcon class="h-6 w-6" />
        </button>
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * MediaLibraryImageField Component
 *
 * An image upload field with Media Library integration, gallery view,
 * lightbox preview, drag-and-drop support, and image management capabilities.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed, ref, nextTick } from 'vue'
import {
  PhotoIcon,
  EyeIcon,
  XMarkIcon,
  ExclamationCircleIcon,
  Bars3Icon,
  ChevronLeftIcon,
  ChevronRightIcon
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
const lightboxImage = ref(null)
const lightboxIndex = ref(-1)
const dragIndex = ref(-1)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const fieldId = computed(() => {
  return `media-library-image-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

const existingImages = computed(() => {
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
  return 'image/*'
})

const acceptedTypesText = computed(() => {
  if (props.field.acceptedMimeTypes && props.field.acceptedMimeTypes.length > 0) {
    const types = props.field.acceptedMimeTypes.map(type => {
      if (type.startsWith('image/jpeg')) return 'JPEG'
      if (type.startsWith('image/jpg')) return 'JPG'
      if (type.startsWith('image/png')) return 'PNG'
      if (type.startsWith('image/webp')) return 'WebP'
      if (type.startsWith('image/gif')) return 'GIF'
      if (type.startsWith('image/svg')) return 'SVG'
      return type.split('/')[1]?.toUpperCase() || type
    })
    return types.join(', ') + ' images'
  }
  return 'All image types'
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

  // Filter only image files
  const imageFiles = Array.from(files).filter(file => file.type.startsWith('image/'))

  if (imageFiles.length === 0) {
    uploadError.value = 'Please select valid image files'
    return
  }

  // Validate files
  for (let file of imageFiles) {
    if (!validateFile(file)) {
      return
    }
  }

  // Check limit
  if (props.field.limit) {
    const totalImages = existingImages.value.length + imageFiles.length
    if (totalImages > props.field.limit) {
      uploadError.value = `Cannot upload more than ${props.field.limit} images`
      return
    }
  }

  // Process files
  if (props.field.singleFile) {
    emit('update:modelValue', imageFiles[0])
  } else {
    const currentImages = existingImages.value
    emit('update:modelValue', [...currentImages, ...imageFiles])
  }

  emit('change', imageFiles)
}

const validateFile = (file) => {
  // Check file size
  if (props.field.maxFileSize && file.size > props.field.maxFileSize * 1024) {
    uploadError.value = `Image size exceeds maximum allowed size of ${formatFileSize(props.field.maxFileSize * 1024)}`
    return false
  }

  // Check MIME type
  if (props.field.acceptedMimeTypes && props.field.acceptedMimeTypes.length > 0) {
    if (!props.field.acceptedMimeTypes.includes(file.type)) {
      uploadError.value = `Image type not allowed. Accepted types: ${acceptedTypesText.value}`
      return false
    }
  }

  return true
}

const removeImage = (index) => {
  const currentImages = [...existingImages.value]
  currentImages.splice(index, 1)

  if (props.field.singleFile) {
    emit('update:modelValue', currentImages[0] || null)
  } else {
    emit('update:modelValue', currentImages)
  }

  emit('change', currentImages)
}

const getImagePreviewUrl = (image) => {
  if (image.preview_url) return image.preview_url
  if (image.medium_url) return image.medium_url
  if (image.url) return image.url
  if (image instanceof File) {
    return URL.createObjectURL(image)
  }
  return '/images/placeholder-image.png'
}

const getLightboxImageUrl = (image) => {
  if (image.large_url) return image.large_url
  if (image.url) return image.url
  if (image instanceof File) {
    return URL.createObjectURL(image)
  }
  return '/images/placeholder-image.png'
}

const openLightbox = (image, index) => {
  lightboxImage.value = image
  lightboxIndex.value = index
}

const closeLightbox = () => {
  lightboxImage.value = null
  lightboxIndex.value = -1
}

const navigateLightbox = (direction) => {
  const newIndex = lightboxIndex.value + direction
  if (newIndex >= 0 && newIndex < existingImages.value.length) {
    lightboxIndex.value = newIndex
    lightboxImage.value = existingImages.value[newIndex]
  }
}

const handleImageError = (event) => {
  event.target.src = '/images/placeholder-image.png'
}

const startDrag = (index) => {
  dragIndex.value = index
  // Add drag and drop reordering logic here
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

.aspect-square {
  aspect-ratio: 1 / 1;
}
</style>
