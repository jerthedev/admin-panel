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
        :class="badgeClasses"
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
      >
        <!-- Icon (if enabled and available) -->
        <component
          v-if="showIcon && iconComponent"
          :is="iconComponent"
          class="w-3 h-3 mr-1.5"
          aria-hidden="true"
        />

        <!-- Badge Label -->
        {{ displayLabel }}
      </span>
    </div>
  </BaseField>
</template>

<script>
import BaseField from './BaseField.vue'

export default {
  name: 'BadgeField',

  components: {
    BaseField,
  },

  props: {
    field: {
      type: Object,
      required: true,
    },
    modelValue: {
      type: [String, Number, Boolean, null],
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
      default: true, // Badge fields are display-only by default
    },
    size: {
      type: String,
      default: 'default',
    },
  },

  computed: {
    /**
     * Default built-in badge types (fallback if not provided by PHP)
     */
    defaultBuiltInTypes() {
      return {
        info: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        success: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        danger: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
      }
    },

    /**
     * Get the badge type for the current value
     */
    badgeType() {
      if (!this.modelValue) return 'info'

      const valueMap = this.field.valueMap || {}
      return valueMap[this.modelValue] || 'info'
    },

    /**
     * Get the CSS classes for the badge
     */
    badgeClasses() {
      const customTypes = this.field.customTypes || {}
      const builtInTypes = this.field.builtInTypes || this.defaultBuiltInTypes

      // Use custom types if defined, otherwise fall back to built-in types
      if (Object.keys(customTypes).length > 0) {
        return customTypes[this.badgeType] || builtInTypes[this.badgeType] || builtInTypes['info']
      }

      return builtInTypes[this.badgeType] || builtInTypes['info']
    },

    /**
     * Get the display label for the current value
     */
    displayLabel() {
      if (!this.modelValue) return ''

      const labelMap = this.field.labelMap || {}

      // Use label mapping if defined
      if (labelMap[this.modelValue]) {
        return labelMap[this.modelValue]
      }

      // Default to the value itself
      return String(this.modelValue)
    },

    /**
     * Whether to show an icon
     */
    showIcon() {
      return this.field.withIcons && !!this.iconName
    },

    /**
     * Get the icon name for the current badge type
     */
    iconName() {
      const iconMap = this.field.iconMap || {}
      return iconMap[this.badgeType] || null
    },

    /**
     * Get the icon component for the current badge type
     */
    iconComponent() {
      if (!this.iconName) return null

      // Map common icon names to Heroicons components
      const iconMapping = {
        'check-circle': 'CheckCircleIcon',
        'exclamation-circle': 'ExclamationCircleIcon',
        'exclamation-triangle': 'ExclamationTriangleIcon',
        'x-circle': 'XCircleIcon',
        'information-circle': 'InformationCircleIcon',
        'clock': 'ClockIcon',
        'shield-check': 'ShieldCheckIcon',
        'shield-exclamation': 'ShieldExclamationIcon',
      }

      const componentName = iconMapping[this.iconName] || null

      if (componentName) {
        try {
          // Dynamically import the icon component
          return () => import(`@heroicons/vue/24/solid/${componentName}.js`)
        } catch (error) {
          console.warn(`Icon component ${componentName} not found`)
          return null
        }
      }

      return null
    },
  },

  methods: {
    /**
     * Handle field focus (no-op for badge fields)
     */
    focus() {
      // Badge fields are display-only, so focus is a no-op
    },

    /**
     * Handle field blur (no-op for badge fields)
     */
    blur() {
      // Badge fields are display-only, so blur is a no-op
    },
  },
}
</script>

<style scoped>
@import '../../../css/admin.css' reference;

/* Additional badge-specific styles if needed */
.badge-field {
  /* Custom badge field styles */
}

/* Ensure proper spacing for icons */
.inline-flex .w-3 {
  flex-shrink: 0;
}
</style>
