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
    <SelectField
      :field="selectLikeField"
      :model-value="modelValue"
      :errors="errors"
      :disabled="disabled"
      :readonly="readonly"
      :size="size"
      @update:modelValue="$emit('update:modelValue', $event)"
      @focus="$emit('focus', $event)"
      @blur="$emit('blur', $event)"
      @change="$emit('change', $event)"
    />
  </BaseField>
</template>

<script setup>
import { computed } from 'vue'
import BaseField from './BaseField.vue'
import SelectField from './SelectField.vue'

const props = defineProps({
  field: { type: Object, required: true },
  modelValue: { type: [String, Number], default: null },
  errors: { type: Object, default: () => ({}) },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  size: { type: String, default: 'default', validator: v => ['small','default','large'].includes(v) },
})

const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'change'])

// The Country field behaves like a Select with predefined options and optional search
const selectLikeField = computed(() => {
  return {
    ...props.field,
    component: 'SelectField',
    options: props.field.options || props.field.countries || props.field.options,
    searchable: Boolean(props.field.searchable),
  }
})
</script>

