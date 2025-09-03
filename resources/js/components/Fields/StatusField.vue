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
    <div class="flex items-center">
      <span
        :class="statusClasses"
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
      >
        <!-- Icon (if enabled and available) -->
        <span
          v-if="showIcon && iconName"
          :class="iconClasses"
          class="w-3 h-3 mr-1.5 inline-block"
          aria-hidden="true"
        >
          <!-- Simple icon placeholder for now - can be enhanced with actual icons later -->
          <span class="text-xs">●</span>
        </span>

        <!-- Status Label -->
        {{ displayLabel }}
      </span>
    </div>
  </BaseField>
</template>

<script>
import BaseField from './BaseField.vue'

export default {
  name: 'StatusField',

  components: {
    BaseField,
  },

  props: {
    field: {
      type: Object,
      required: true,
    },
    modelValue: {
      type: [String, Number, Boolean, Object, null],
      default: null,
    },
    errors: {
      type: Array,
      default: () => [],
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    readonly: {
      type: Boolean,
      default: true, // Status fields are display-only by default
    },
    size: {
      type: String,
      default: 'default',
    },
  },

  computed: {
    /**
     * Default built-in status types (fallback if not provided by PHP)
     */
    defaultBuiltInTypes() {
      return {
        loading: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        failed: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        success: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        default: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
      }
    },

    /**
     * Default built-in status icons (fallback if not provided by PHP)
     */
    defaultBuiltInIcons() {
      return {
        loading: 'spinner',
        failed: 'exclamation-circle',
        success: 'check-circle',
        default: 'information-circle',
      }
    },

    /**
     * Get the status information from the resolved value
     */
    statusInfo() {
      if (!this.modelValue) return null

      // If the value is already resolved by PHP (object format)
      if (typeof this.modelValue === 'object' && this.modelValue.type) {
        return this.modelValue
      }

      // Fallback: resolve status type on frontend
      return {
        value: this.modelValue,
        label: this.resolveLabel(this.modelValue),
        type: this.resolveStatusType(this.modelValue),
        classes: null, // Will be computed below
        icon: null, // Will be computed below
      }
    },

    /**
     * Get the status type for the current value
     */
    statusType() {
      return this.statusInfo?.type || 'default'
    },

    /**
     * Get the CSS classes for the status
     */
    statusClasses() {
      // Use classes from PHP resolution if available
      if (this.statusInfo?.classes) {
        return this.statusInfo.classes
      }

      const customTypes = this.field.customTypes || {}
      const builtInTypes = this.field.builtInTypes || this.defaultBuiltInTypes

      // Use custom types if defined, otherwise fall back to built-in types
      if (Object.keys(customTypes).length > 0) {
        return customTypes[this.statusType] || builtInTypes[this.statusType] || builtInTypes['default']
      }

      return builtInTypes[this.statusType] || builtInTypes['default']
    },

    /**
     * Get the display label for the current value
     */
    displayLabel() {
      if (!this.statusInfo) return ''

      // Use label from PHP resolution if available
      if (this.statusInfo.label) {
        return this.statusInfo.label
      }

      return this.resolveLabel(this.statusInfo.value)
    },

    /**
     * Whether to show an icon
     */
    showIcon() {
      return this.field.withIcons !== false && !!this.iconName
    },

    /**
     * Get the icon name for the current status type
     */
    iconName() {
      // Use icon from PHP resolution if available
      if (this.statusInfo?.icon) {
        return this.statusInfo.icon
      }

      const customIcons = this.field.customIcons || {}
      const builtInIcons = this.field.builtInIcons || this.defaultBuiltInIcons

      // Use custom icons if defined, otherwise fall back to built-in icons
      if (Object.keys(customIcons).length > 0) {
        return customIcons[this.statusType] || builtInIcons[this.statusType] || builtInIcons['default']
      }

      return builtInIcons[this.statusType] || builtInIcons['default']
    },

    /**
     * Get additional classes for the icon based on status type
     */
    iconClasses() {
      const classes = []

      // Add spinning animation for loading status
      if (this.statusType === 'loading') {
        classes.push('animate-spin')
      }

      return classes.join(' ')
    },


  },

  methods: {
    /**
     * Resolve the status type for a given value (frontend fallback)
     */
    resolveStatusType(value) {
      const loadingWhen = this.field.loadingWhen || []
      const failedWhen = this.field.failedWhen || []
      const successWhen = this.field.successWhen || []

      if (loadingWhen.includes(value)) {
        return 'loading'
      }

      if (failedWhen.includes(value)) {
        return 'failed'
      }

      if (successWhen.includes(value)) {
        return 'success'
      }

      return 'default'
    },

    /**
     * Resolve the display label for a given value (frontend fallback)
     */
    resolveLabel(value) {
      if (!value) return ''

      const labelMap = this.field.labelMap || {}

      // Use label mapping if defined
      if (labelMap[value]) {
        return labelMap[value]
      }

      // Default to the value itself, formatted nicely
      return String(value).replace(/[_-]/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
    },

    /**
     * Handle field focus (no-op for status fields)
     */
    focus() {
      // Status fields are display-only, so focus is a no-op
    },

    /**
     * Handle field blur (no-op for status fields)
     */
    blur() {
      // Status fields are display-only, so blur is a no-op
    },
  },
}
</script>

<style scoped>
@import '../../../css/admin.css' reference;

/* Additional status-specific styles if needed */
.status-field {
  /* Custom status field styles */
}

/* Ensure proper spacing for icons */
.inline-flex .w-3 {
  flex-shrink: 0;
}

/* Spinning animation for loading icons */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
