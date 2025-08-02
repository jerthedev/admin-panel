<template>
  <div class="field-display">
    <!-- Text fields -->
    <span
      v-if="isTextType"
      class="text-sm"
      :class="{ 'text-gray-500': !value, 'font-mono': field.component === 'TextField' && field.monospace }"
    >
      {{ displayValue || 'N/A' }}
    </span>

    <!-- Email field -->
    <div v-else-if="field.component === 'EmailField'" class="flex items-center space-x-2">
      <span class="text-sm">{{ displayValue || 'N/A' }}</span>
      <a
        v-if="value && field.clickable"
        :href="`mailto:${value}`"
        class="text-blue-600 hover:text-blue-500"
        :class="{ 'text-blue-400 hover:text-blue-300': isDarkTheme }"
      >
        <EnvelopeIcon class="h-4 w-4" />
      </a>
    </div>

    <!-- Boolean field -->
    <div v-else-if="field.component === 'BooleanField'" class="flex items-center space-x-2">
      <div
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
        :class="[
          booleanValue 
            ? 'bg-green-100 text-green-800' 
            : 'bg-gray-100 text-gray-800',
          {
            'bg-green-900 text-green-200': booleanValue && isDarkTheme,
            'bg-gray-700 text-gray-300': !booleanValue && isDarkTheme
          }
        ]"
      >
        <CheckIcon v-if="booleanValue" class="h-3 w-3 mr-1" />
        <XMarkIcon v-else class="h-3 w-3 mr-1" />
        {{ booleanDisplayText }}
      </div>
    </div>

    <!-- Number field -->
    <span
      v-else-if="field.component === 'NumberField'"
      class="text-sm font-mono"
    >
      {{ formatNumber(value) }}
    </span>

    <!-- Date field -->
    <div v-else-if="field.component === 'DateField'" class="flex items-center space-x-2">
      <span class="text-sm">{{ formatDate(value) }}</span>
      <span v-if="context === 'index'" class="text-xs text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
        ({{ relativeDate(value) }})
      </span>
    </div>

    <!-- Select field -->
    <div v-else-if="field.component === 'SelectField'" class="flex items-center space-x-2">
      <span
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
        :class="{ 'bg-blue-900 text-blue-200': isDarkTheme }"
      >
        {{ selectDisplayValue }}
      </span>
    </div>

    <!-- File/Image fields (placeholder) -->
    <div v-else-if="isFileType" class="flex items-center space-x-2">
      <DocumentIcon class="h-4 w-4 text-gray-400" />
      <span class="text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
        {{ value ? 'File attached' : 'No file' }}
      </span>
    </div>

    <!-- URL/Link fields -->
    <div v-else-if="isUrlType" class="flex items-center space-x-2">
      <a
        v-if="value"
        :href="value"
        target="_blank"
        rel="noopener noreferrer"
        class="text-blue-600 hover:text-blue-500 text-sm"
        :class="{ 'text-blue-400 hover:text-blue-300': isDarkTheme }"
      >
        {{ truncateUrl(value) }}
        <ArrowTopRightOnSquareIcon class="h-3 w-3 inline ml-1" />
      </a>
      <span v-else class="text-sm text-gray-500">N/A</span>
    </div>

    <!-- JSON/Array fields -->
    <div v-else-if="isJsonType" class="text-sm">
      <details v-if="value" class="cursor-pointer">
        <summary class="text-blue-600 hover:text-blue-500" :class="{ 'text-blue-400 hover:text-blue-300': isDarkTheme }">
          View Data ({{ Array.isArray(value) ? value.length + ' items' : Object.keys(value).length + ' keys' }})
        </summary>
        <pre class="mt-2 p-3 bg-gray-100 rounded text-xs overflow-x-auto" :class="{ 'bg-gray-700': isDarkTheme }">{{ JSON.stringify(value, null, 2) }}</pre>
      </details>
      <span v-else class="text-gray-500">N/A</span>
    </div>

    <!-- Default fallback -->
    <span v-else class="text-sm">
      {{ displayValue || 'N/A' }}
    </span>
  </div>
</template>

<script setup>
/**
 * FieldDisplay Component
 * 
 * Displays field values in different contexts (index, detail) with
 * appropriate formatting and styling for each field type.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import {
  CheckIcon,
  XMarkIcon,
  EnvelopeIcon,
  DocumentIcon,
  ArrowTopRightOnSquareIcon
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  value: {
    type: [String, Number, Boolean, Array, Object],
    default: null
  },
  context: {
    type: String,
    default: 'index',
    validator: (value) => ['index', 'detail'].includes(value)
  }
})

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const displayValue = computed(() => {
  if (props.value === null || props.value === undefined) return null
  
  // Handle object values with display property
  if (typeof props.value === 'object' && props.value.display !== undefined) {
    return props.value.display
  }
  
  // Handle object values with label property
  if (typeof props.value === 'object' && props.value.label !== undefined) {
    return props.value.label
  }
  
  return props.value
})

const isTextType = computed(() => {
  return ['TextField', 'TextareaField', 'PasswordField'].includes(props.field.component)
})

const isFileType = computed(() => {
  return ['FileField', 'ImageField'].includes(props.field.component)
})

const isUrlType = computed(() => {
  return ['UrlField', 'LinkField'].includes(props.field.component)
})

const isJsonType = computed(() => {
  return typeof props.value === 'object' && props.value !== null && !Array.isArray(props.value) && 
         !props.value.hasOwnProperty('display') && !props.value.hasOwnProperty('label')
})

const booleanValue = computed(() => {
  if (typeof props.value === 'object' && props.value.value !== undefined) {
    return props.value.value
  }
  return Boolean(props.value)
})

const booleanDisplayText = computed(() => {
  if (typeof props.value === 'object' && props.value.display !== undefined) {
    return props.value.display
  }
  return booleanValue.value ? (props.field.trueText || 'Yes') : (props.field.falseText || 'No')
})

const selectDisplayValue = computed(() => {
  if (typeof props.value === 'object' && props.value.label !== undefined) {
    return props.value.label
  }
  
  // Look up in field options
  if (props.field.options && props.value !== null) {
    return props.field.options[props.value] || props.value
  }
  
  return props.value || 'N/A'
})

// Methods
const formatNumber = (value) => {
  if (value === null || value === undefined) return 'N/A'
  
  const num = Number(value)
  if (isNaN(num)) return value
  
  if (props.field.decimals !== null && props.field.decimals !== undefined) {
    return num.toFixed(props.field.decimals)
  }
  
  return num.toLocaleString()
}

const formatDate = (value) => {
  if (!value) return 'N/A'
  
  try {
    const date = new Date(value)
    if (isNaN(date.getTime())) return value
    
    const format = props.field.displayFormat || 'Y-m-d'
    
    switch (format) {
      case 'Y-m-d':
        return date.toISOString().split('T')[0]
      case 'd/m/Y':
        return date.toLocaleDateString('en-GB')
      case 'm/d/Y':
        return date.toLocaleDateString('en-US')
      case 'M d, Y':
        return date.toLocaleDateString('en-US', { 
          month: 'short', 
          day: 'numeric', 
          year: 'numeric' 
        })
      case 'F j, Y':
        return date.toLocaleDateString('en-US', { 
          month: 'long', 
          day: 'numeric', 
          year: 'numeric' 
        })
      default:
        return date.toLocaleDateString()
    }
  } catch (error) {
    return value
  }
}

const relativeDate = (value) => {
  if (!value) return ''
  
  try {
    const date = new Date(value)
    if (isNaN(date.getTime())) return ''
    
    const now = new Date()
    const diffInMs = now.getTime() - date.getTime()
    const diffInDays = Math.floor(diffInMs / (1000 * 60 * 60 * 24))
    
    if (diffInDays === 0) {
      return 'Today'
    } else if (diffInDays === 1) {
      return 'Yesterday'
    } else if (diffInDays === -1) {
      return 'Tomorrow'
    } else if (diffInDays > 0) {
      return `${diffInDays} days ago`
    } else {
      return `In ${Math.abs(diffInDays)} days`
    }
  } catch (error) {
    return ''
  }
}

const truncateUrl = (url) => {
  if (!url) return ''
  
  if (url.length <= 50) return url
  
  return url.substring(0, 47) + '...'
}
</script>

<style scoped>
.field-display {
}

/* JSON display styling */
pre {
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  white-space: pre-wrap;
  word-break: break-all;
}

/* Details/summary styling */
details summary {
}

details summary::-webkit-details-marker {
  display: none;
}

details summary::before {
  content: '▶';
}

details[open] summary::before {
  transform: rotate(90deg);
}

/* Badge styling */
.inline-flex.items-center {
}

/* Link styling */
a {
}
</style>
