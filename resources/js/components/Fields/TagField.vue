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
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <h3 class="text-lg font-medium text-gray-900" :class="{ 'text-gray-100': isDarkTheme }">
            {{ field.name }}
          </h3>
          
          <!-- Tag Count Badge -->
          <span
            data-testid="tag-count"
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
            :class="{ 'bg-blue-900 text-blue-200': isDarkTheme }"
          >
            {{ tagCount }} {{ tagCount === 1 ? 'tag' : 'tags' }}
          </span>
        </div>

        <div class="flex items-center space-x-2">
          <!-- Add Tag button -->
          <button
            v-if="!readonly && !disabled"
            data-testid="add-tags-button"
            type="button"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
            aria-label="Add tags"
            @click="showTagSelector"
          >
            <PlusIcon class="w-4 h-4 mr-1" />
            Add Tags
          </button>

          <!-- Create Tag button -->
          <button
            v-if="field.showCreateRelationButton && !readonly && !disabled"
            data-testid="create-tag-button"
            type="button"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            aria-label="Create new tag"
            @click="showCreateModal"
          >
            <PlusIcon class="w-4 h-4 mr-1" />
            Create Tag
          </button>
        </div>
      </div>

      <!-- Tag Search (when adding tags) -->
      <div
        v-if="showingTagSelector && field.searchable"
        data-testid="tag-search"
        class="relative"
      >
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
        </div>
        <input
          v-model="searchQuery"
          type="text"
          class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          :class="{ 'border-gray-600 bg-gray-800 text-gray-100 placeholder-gray-400': isDarkTheme }"
          placeholder="Search tags..."
          @input="debouncedSearch"
        />
      </div>

      <!-- Available Tags (when searching) -->
      <div
        v-if="showingTagSelector && availableTags.length > 0"
        data-testid="tag-selector"
        class="border border-gray-200 rounded-md max-h-60 overflow-y-auto"
        :class="{ 'border-gray-700': isDarkTheme }"
      >
        <div
          v-for="tag in availableTags"
          :key="tag.id"
          class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
          :class="{ 
            'hover:bg-gray-800 border-gray-700': isDarkTheme,
            'bg-blue-50': isTagSelected(tag.id),
            'bg-blue-900': isDarkTheme && isTagSelected(tag.id)
          }"
          @click="toggleTag(tag)"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div
                v-if="tag.image"
                class="w-8 h-8 rounded-full bg-gray-200 flex-shrink-0"
                :style="{ backgroundImage: `url(${tag.image})`, backgroundSize: 'cover' }"
              />
              <TagIcon v-else class="w-5 h-5 text-gray-400" />
              <div>
                <p class="font-medium text-gray-900" :class="{ 'text-gray-100': isDarkTheme }">
                  {{ tag.title }}
                </p>
                <p v-if="tag.subtitle" class="text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                  {{ tag.subtitle }}
                </p>
              </div>
            </div>
            <CheckIcon
              v-if="isTagSelected(tag.id)"
              class="w-5 h-5 text-blue-600"
              :class="{ 'text-blue-400': isDarkTheme }"
            />
          </div>
        </div>
      </div>

      <!-- Loading state -->
      <div
        v-if="loading"
        data-testid="loading-spinner"
        class="flex items-center justify-center py-8"
      >
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>

      <!-- Empty state -->
      <div
        v-else-if="selectedTags.length === 0"
        data-testid="empty-state"
        class="text-center py-8"
      >
        <div class="text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
          <TagIcon class="mx-auto h-12 w-12 mb-4" />
          <p class="text-lg font-medium">No tags selected</p>
          <p class="text-sm">
            {{ field.showCreateRelationButton ? 'Add existing tags or create new ones to get started.' : 'Add existing tags to get started.' }}
          </p>
        </div>
      </div>

      <!-- Selected Tags Display -->
      <div
        v-else
        class="space-y-3"
      >
        <!-- Tags as List -->
        <div
          v-if="field.displayAsList"
          data-testid="list-tags"
          class="space-y-2"
        >
          <div
            v-for="tag in selectedTags"
            :key="tag.id"
            data-testid="list-tag"
            class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50"
            :class="{
              'border-gray-700 hover:bg-gray-800': isDarkTheme
            }"
          >
            <div
              class="flex items-center space-x-3"
              :data-testid="field.withPreview ? 'preview-tag' : null"
              :class="{ 'cursor-pointer': field.withPreview }"
              @click="field.withPreview ? showPreview(tag) : null"
            >
              <div
                v-if="tag.image"
                class="w-8 h-8 rounded-full bg-gray-200 flex-shrink-0"
                :style="{ backgroundImage: `url(${tag.image})`, backgroundSize: 'cover' }"
              />
              <TagIcon v-else class="w-5 h-5 text-gray-400" />
              <div>
                <p class="font-medium text-gray-900" :class="{ 'text-gray-100': isDarkTheme }">
                  {{ tag.title }}
                </p>
                <p v-if="tag.subtitle" class="text-sm text-gray-500" :class="{ 'text-gray-400': isDarkTheme }">
                  {{ tag.subtitle }}
                </p>
              </div>
            </div>
            
            <!-- Remove Tag Button -->
            <button
              v-if="!readonly && !disabled"
              data-testid="remove-tag-button"
              type="button"
              class="inline-flex items-center px-2 py-1 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
              :class="{ 'border-red-600 text-red-400 bg-gray-800 hover:bg-red-900': isDarkTheme }"
              tabindex="0"
              @click="removeTag(tag)"
            >
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Tags as Inline Group (default) -->
        <div
          v-else
          data-testid="inline-tags"
          class="flex flex-wrap gap-2"
        >
          <span
            v-for="tag in selectedTags"
            :key="tag.id"
            data-testid="inline-tag"
            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800"
            :class="{ 'bg-blue-900 text-blue-200': isDarkTheme }"
          >
            <div
              v-if="tag.image"
              class="w-4 h-4 rounded-full bg-gray-200 mr-2 flex-shrink-0"
              :style="{ backgroundImage: `url(${tag.image})`, backgroundSize: 'cover' }"
            />
            <TagIcon v-else class="w-4 h-4 mr-2 text-blue-600" :class="{ 'text-blue-400': isDarkTheme }" />
            {{ tag.title }}
            <button
              v-if="!readonly && !disabled"
              data-testid="remove-tag-button"
              type="button"
              class="ml-2 inline-flex items-center p-0.5 rounded-full text-blue-600 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              :class="{ 'text-blue-400 hover:bg-blue-800': isDarkTheme }"
              tabindex="0"
              @click="removeTag(tag)"
            >
              <XMarkIcon class="w-3 h-3" />
            </button>
          </span>
        </div>
      </div>

      <!-- Preview Modal -->
      <div
        v-if="field.withPreview && showingPreview"
        data-testid="preview-modal"
        class="fixed inset-0 z-50 overflow-y-auto"
        @click="closePreview"
      >
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
          </div>
          
          <div
            class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
            :class="{ 'bg-gray-800': isDarkTheme }"
            @click.stop
          >
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4" :class="{ 'bg-gray-800': isDarkTheme }">
              <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" :class="{ 'text-gray-100': isDarkTheme }">
                Tag Preview
              </h3>
              <div v-if="previewTag">
                <p class="font-medium">{{ previewTag.title }}</p>
                <p v-if="previewTag.subtitle" class="text-sm text-gray-500 mt-1">{{ previewTag.subtitle }}</p>
              </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse" :class="{ 'bg-gray-700': isDarkTheme }">
              <button
                type="button"
                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                :class="{ 'border-gray-600 bg-gray-800 text-gray-300 hover:bg-gray-700': isDarkTheme }"
                @click="closePreview"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Field Errors -->
      <div
        v-if="errors && errors.length > 0"
        data-testid="field-errors"
        class="mt-2"
      >
        <div
          v-for="error in errors"
          :key="error"
          class="text-sm text-red-600"
          :class="{ 'text-red-400': isDarkTheme }"
        >
          {{ error }}
        </div>
      </div>
    </div>
  </BaseField>
</template>

<script>
import { ref, computed, watch, onMounted } from 'vue'
import { debounce } from 'lodash'
import BaseField from './BaseField.vue'
import {
  PlusIcon,
  MagnifyingGlassIcon,
  TagIcon,
  CheckIcon,
  XMarkIcon,
  EyeIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'TagField',
  components: {
    BaseField,
    PlusIcon,
    MagnifyingGlassIcon,
    TagIcon,
    CheckIcon,
    XMarkIcon,
    EyeIcon
  },
  props: {
    field: {
      type: Object,
      required: true
    },
    modelValue: {
      type: [Array, Object],
      default: () => []
    },
    errors: {
      type: Array,
      default: () => []
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
      default: 'md'
    }
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    // Reactive state
    const loading = ref(false)
    const searchQuery = ref('')
    const availableTags = ref([])
    const showingTagSelector = ref(false)
    const showingPreview = ref(false)
    const previewTag = ref(null)

    // Computed properties
    const isDarkTheme = computed(() => {
      return document.documentElement.classList.contains('dark')
    })

    const selectedTags = computed(() => {
      if (Array.isArray(props.modelValue)) {
        return props.modelValue
      }
      if (props.modelValue && props.modelValue.tags) {
        return props.modelValue.tags
      }
      return []
    })

    const tagCount = computed(() => {
      return selectedTags.value.length
    })

    // Methods
    const showTagSelector = () => {
      showingTagSelector.value = true
      if (props.field.preload) {
        loadAvailableTags()
      }
    }

    const hideTagSelector = () => {
      showingTagSelector.value = false
      searchQuery.value = ''
      availableTags.value = []
    }

    const loadAvailableTags = async () => {
      loading.value = true
      try {
        // In a real implementation, this would make an API call
        // For now, we'll simulate with mock data
        await new Promise(resolve => setTimeout(resolve, 500))

        availableTags.value = [
          { id: 1, title: 'PHP', subtitle: 'Programming Language' },
          { id: 2, title: 'Laravel', subtitle: 'PHP Framework' },
          { id: 3, title: 'Vue.js', subtitle: 'JavaScript Framework' },
          { id: 4, title: 'JavaScript', subtitle: 'Programming Language' },
          { id: 5, title: 'CSS', subtitle: 'Styling Language' }
        ].filter(tag =>
          !selectedTags.value.some(selected => selected.id === tag.id) &&
          (!searchQuery.value || tag.title.toLowerCase().includes(searchQuery.value.toLowerCase()))
        )
      } catch (error) {
        console.error('Failed to load available tags:', error)
      } finally {
        loading.value = false
      }
    }

    const debouncedSearch = debounce(() => {
      if (props.field.searchable) {
        loadAvailableTags()
      }
    }, 300)

    const isTagSelected = (tagId) => {
      return selectedTags.value.some(tag => tag.id === tagId)
    }

    const toggleTag = (tag) => {
      if (isTagSelected(tag.id)) {
        removeTag(tag)
      } else {
        addTag(tag)
      }
    }

    const addTag = (tag) => {
      const newTags = [...selectedTags.value, tag]
      emit('update:modelValue', newTags)

      // Remove from available tags
      availableTags.value = availableTags.value.filter(t => t.id !== tag.id)
    }

    const removeTag = (tag) => {
      const newTags = selectedTags.value.filter(t => t.id !== tag.id)
      emit('update:modelValue', newTags)

      // Add back to available tags if selector is open
      if (showingTagSelector.value) {
        availableTags.value.push(tag)
      }
    }

    const showCreateModal = () => {
      // Implementation for creating new tags
      console.log('Show create modal')
    }

    const showPreview = (tag) => {
      if (props.field.withPreview) {
        previewTag.value = tag
        showingPreview.value = true
      }
    }

    const closePreview = () => {
      showingPreview.value = false
      previewTag.value = null
    }

    // Watch for search query changes
    watch(searchQuery, () => {
      if (showingTagSelector.value) {
        debouncedSearch()
      }
    })

    // Lifecycle
    onMounted(() => {
      if (props.field.preload) {
        loadAvailableTags()
      }
    })

    return {
      loading,
      searchQuery,
      availableTags,
      showingTagSelector,
      showingPreview,
      previewTag,
      isDarkTheme,
      selectedTags,
      tagCount,
      showTagSelector,
      hideTagSelector,
      loadAvailableTags,
      debouncedSearch,
      isTagSelected,
      toggleTag,
      addTag,
      removeTag,
      showCreateModal,
      showPreview,
      closePreview
    }
  }
}
</script>
