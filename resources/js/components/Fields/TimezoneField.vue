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
    <div class="relative">
      <!-- Timezone select dropdown -->
      <div
        ref="dropdownRef"
        class="relative"
      >
        <!-- Selected timezone display / Search input -->
        <div
          class="admin-input min-h-[2.5rem] p-2 cursor-pointer flex items-center justify-between"
          :class="[
            { 'admin-input-dark': isDarkTheme },
            { 'border-red-300': hasError },
            { 'opacity-50 cursor-not-allowed': disabled || readonly }
          ]"
          @click="toggleDropdown"
        >
          <!-- Selected timezone or search input -->
          <div class="flex-1">
            <input
              v-if="field.searchable && isOpen"
              ref="searchInputRef"
              v-model="searchQuery"
              type="text"
              class="w-full border-none outline-none bg-transparent text-sm"
              :placeholder="selectedTimezone ? selectedTimezone : 'Search timezones...'"
              @click.stop
              @keydown="handleKeydown"
            />
            <span
              v-else
              class="text-sm"
              :class="{ 'text-gray-500': !modelValue, 'text-gray-400': !modelValue && isDarkTheme }"
            >
              {{ selectedTimezone || field.placeholder || 'Select timezone...' }}
            </span>
          </div>

          <!-- Dropdown arrow -->
          <ChevronDownIcon
            class="w-5 h-5 text-gray-400 transition-transform duration-200 ml-2"
            :class="{ 'transform rotate-180': isOpen }"
          />
        </div>

        <!-- Dropdown menu -->
        <Transition
          enter-active-class="transition ease-out duration-100"
          enter-from-class="transform opacity-0 scale-95"
          enter-to-class="transform opacity-100 scale-100"
          leave-active-class="transition ease-in duration-75"
          leave-from-class="transform opacity-100 scale-100"
          leave-to-class="transform opacity-0 scale-95"
        >
          <div
            v-if="isOpen"
            class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
            :class="{ 'bg-gray-800 border-gray-600': isDarkTheme }"
          >
            <!-- No results message -->
            <div
              v-if="filteredTimezones.length === 0"
              class="px-3 py-2 text-sm text-gray-500"
              :class="{ 'text-gray-400': isDarkTheme }"
            >
              No timezones found
            </div>

            <!-- Grouped timezones -->
            <template v-if="field.groupByRegion && !field.searchable">
              <div
                v-for="(timezones, region) in filteredTimezones"
                :key="region"
                class="border-b border-gray-100 last:border-b-0"
                :class="{ 'border-gray-700': isDarkTheme }"
              >
                <div
                  class="px-3 py-1 text-xs font-semibold text-gray-600 bg-gray-50 sticky top-0"
                  :class="{ 'text-gray-300 bg-gray-700': isDarkTheme }"
                >
                  {{ region }}
                </div>
                <div
                  v-for="(name, identifier) in timezones"
                  :key="identifier"
                  class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 flex items-center justify-between"
                  :class="[
                    { 'hover:bg-gray-700': isDarkTheme },
                    { 'bg-blue-50 text-blue-700': isSelected(identifier) && !isDarkTheme },
                    { 'bg-blue-900 text-blue-200': isSelected(identifier) && isDarkTheme }
                  ]"
                  @click="selectTimezone(identifier)"
                >
                  <span>{{ name }}</span>
                  <CheckIcon
                    v-if="isSelected(identifier)"
                    class="w-4 h-4 text-blue-600"
                    :class="{ 'text-blue-400': isDarkTheme }"
                  />
                </div>
              </div>
            </template>

            <!-- Flat timezone list -->
            <template v-else>
              <div
                v-for="(name, identifier) in filteredTimezones"
                :key="identifier"
                class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 flex items-center justify-between"
                :class="[
                  { 'hover:bg-gray-700': isDarkTheme },
                  { 'bg-blue-50 text-blue-700': isSelected(identifier) && !isDarkTheme },
                  { 'bg-blue-900 text-blue-200': isSelected(identifier) && isDarkTheme }
                ]"
                @click="selectTimezone(identifier)"
              >
                <span>{{ name }}</span>
                <CheckIcon
                  v-if="isSelected(identifier)"
                  class="w-4 h-4 text-blue-600"
                  :class="{ 'text-blue-400': isDarkTheme }"
                />
              </div>
            </template>
          </div>
        </Transition>
      </div>

      <!-- Current time display -->
      <div
        v-if="modelValue && showCurrentTime"
        class="mt-2 text-xs text-gray-500"
        :class="{ 'text-gray-400': isDarkTheme }"
      >
        Current time: {{ currentTime }}
      </div>
    </div>
  </BaseField>
</template>

<script setup>
/**
 * TimezoneField Component
 *
 * Timezone selection dropdown with searchable options and regional grouping.
 * Supports common timezones filtering and displays current time in selected timezone.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { ChevronDownIcon, CheckIcon } from '@heroicons/vue/24/outline'
import BaseField from './BaseField.vue'

// Props
const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  modelValue: {
    type: String,
    default: ''
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
const dropdownRef = ref(null)
const searchInputRef = ref(null)
const isOpen = ref(false)
const searchQuery = ref('')
const currentTime = ref('')
const timeInterval = ref(null)

// Store
const adminStore = useAdminStore()

// Computed
const isDarkTheme = computed(() => adminStore.isDarkTheme)

const hasError = computed(() => {
  return props.errors && Object.keys(props.errors).length > 0
})

const timezones = computed(() => {
  return props.field.timezones || {}
})

const selectedTimezone = computed(() => {
  if (!props.modelValue) return ''

  if (props.field.groupByRegion) {
    // Find in grouped timezones
    for (const region in timezones.value) {
      if (timezones.value[region][props.modelValue]) {
        return timezones.value[region][props.modelValue]
      }
    }
  } else {
    return timezones.value[props.modelValue] || props.modelValue
  }

  return props.modelValue
})

const filteredTimezones = computed(() => {
  if (!props.field.searchable || !searchQuery.value) {
    return timezones.value
  }

  const query = searchQuery.value.toLowerCase()

  if (props.field.groupByRegion) {
    const filtered = {}
    for (const [region, regionTimezones] of Object.entries(timezones.value)) {
      const filteredRegion = {}
      for (const [identifier, name] of Object.entries(regionTimezones)) {
        if (
          name.toLowerCase().includes(query) ||
          identifier.toLowerCase().includes(query) ||
          region.toLowerCase().includes(query)
        ) {
          filteredRegion[identifier] = name
        }
      }
      if (Object.keys(filteredRegion).length > 0) {
        filtered[region] = filteredRegion
      }
    }
    return filtered
  } else {
    const filtered = {}
    for (const [identifier, name] of Object.entries(timezones.value)) {
      if (
        name.toLowerCase().includes(query) ||
        identifier.toLowerCase().includes(query)
      ) {
        filtered[identifier] = name
      }
    }
    return filtered
  }
})

const showCurrentTime = computed(() => {
  return props.modelValue && !props.readonly && !props.disabled
})

// Methods
const isSelected = (identifier) => {
  return props.modelValue === identifier
}

const toggleDropdown = () => {
  if (props.disabled || props.readonly) return

  isOpen.value = !isOpen.value

  if (isOpen.value && props.field.searchable) {
    nextTick(() => {
      searchInputRef.value?.focus()
    })
  }
}

const selectTimezone = (identifier) => {
  if (props.disabled || props.readonly) return

  emit('update:modelValue', identifier)
  emit('change', identifier)

  isOpen.value = false
  searchQuery.value = ''

  updateCurrentTime()
}

const handleKeydown = (event) => {
  if (event.key === 'Escape') {
    isOpen.value = false
    searchQuery.value = ''
  }
}

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isOpen.value = false
    searchQuery.value = ''
  }
}

const updateCurrentTime = () => {
  if (!props.modelValue) {
    currentTime.value = ''
    return
  }

  try {
    const now = new Date()
    const formatter = new Intl.DateTimeFormat('en-US', {
      timeZone: props.modelValue,
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: true
    })
    currentTime.value = formatter.format(now)
  } catch (error) {
    currentTime.value = 'Invalid timezone'
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)

  if (props.modelValue) {
    updateCurrentTime()
    // Update time every second
    timeInterval.value = setInterval(updateCurrentTime, 1000)
  }
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  if (timeInterval.value) {
    clearInterval(timeInterval.value)
  }
})

// Watch for timezone changes
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    updateCurrentTime()
    if (!timeInterval.value) {
      timeInterval.value = setInterval(updateCurrentTime, 1000)
    }
  } else {
    currentTime.value = ''
    if (timeInterval.value) {
      clearInterval(timeInterval.value)
      timeInterval.value = null
    }
  }
})
</script>

<style scoped>
/* Ensure proper z-index for dropdown */
.z-10 {
  z-index: 10;
}

/* Smooth transitions */
.transition {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Sticky region headers */
.sticky {
  position: sticky;
}
</style>
