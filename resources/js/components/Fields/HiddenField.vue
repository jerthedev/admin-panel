<template>
  <!-- Hidden fields don't need BaseField wrapper since they're not visible -->
  <input
    :id="fieldId"
    ref="inputRef"
    type="hidden"
    :name="field.attribute"
    :value="modelValue || field.default || ''"
    @input="handleInput"
  />
</template>

<script setup>
/**
 * HiddenField Component
 * 
 * A hidden input field for storing values that should not be visible
 * to users but need to be included in forms.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { computed, ref } from 'vue'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: [String, Number, Boolean],
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

// Emits
const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'change'])

// Refs
const inputRef = ref(null)

// Computed
const fieldId = computed(() => {
  return `hidden-field-${props.field.attribute}-${Math.random().toString(36).substr(2, 9)}`
})

// Methods
const handleInput = (event) => {
  const value = event.target.value
  emit('update:modelValue', value)
  emit('change', value)
}

// Focus method for external use (though rarely needed for hidden fields)
const focus = () => {
  inputRef.value?.focus()
}

defineExpose({
  focus
})
</script>
