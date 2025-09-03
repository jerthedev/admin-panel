<template>
  <Transition
    enter-active-class="transition ease-out duration-200"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition ease-in duration-150"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="modelValue" class="fixed inset-0 z-50 bg-white dark:bg-gray-900">
      <!-- Search Header -->
      <div class="flex items-center gap-4 p-4 border-b border-gray-200 dark:border-gray-700">
        <button
          @click="close"
          class="p-2 -ml-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800"
          aria-label="Close search"
        >
          <XMarkIcon class="w-5 h-5 text-gray-500 dark:text-gray-400" />
        </button>
        
        <div class="flex-1 relative">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            ref="searchInput"
            v-model="searchQuery"
            type="search"
            :placeholder="placeholder"
            class="w-full pl-10 pr-4 py-2 bg-gray-100 dark:bg-gray-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            @input="handleSearch"
          />
        </div>
      </div>

      <!-- Search Results -->
      <div class="p-4">
        <div v-if="loading" class="text-center py-8">
          <div class="inline-flex items-center gap-2 text-gray-500">
            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Searching...
          </div>
        </div>

        <div v-else-if="searchQuery && results.length === 0" class="text-center py-8 text-gray-500">
          No results found for "{{ searchQuery }}"
        </div>

        <div v-else-if="results.length > 0" class="space-y-2">
          <a
            v-for="result in results"
            :key="result.id"
            :href="result.url"
            class="block p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800"
            @click="handleResultClick(result)"
          >
            <div class="font-medium text-gray-900 dark:text-gray-100">
              {{ result.title }}
            </div>
            <div v-if="result.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              {{ result.description }}
            </div>
          </a>
        </div>

        <!-- Recent Searches -->
        <div v-else-if="recentSearches.length > 0" class="space-y-4">
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Recent Searches</h3>
          <div class="space-y-2">
            <button
              v-for="search in recentSearches"
              :key="search"
              @click="searchQuery = search; handleSearch()"
              class="flex items-center gap-2 w-full text-left p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800"
            >
              <ClockIcon class="w-4 h-4 text-gray-400" />
              <span class="text-gray-700 dark:text-gray-300">{{ search }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch, nextTick, onMounted } from 'vue'
import { XMarkIcon, MagnifyingGlassIcon, ClockIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  placeholder: {
    type: String,
    default: 'Search...'
  },
  onSearch: {
    type: Function,
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'search'])

const searchInput = ref(null)
const searchQuery = ref('')
const results = ref([])
const loading = ref(false)
const recentSearches = ref([])

const close = () => {
  emit('update:modelValue', false)
}

const handleSearch = async () => {
  if (!searchQuery.value) {
    results.value = []
    return
  }

  loading.value = true
  
  if (props.onSearch) {
    try {
      results.value = await props.onSearch(searchQuery.value)
    } catch (error) {
      console.error('Search error:', error)
      results.value = []
    }
  } else {
    emit('search', searchQuery.value)
  }
  
  loading.value = false
}

const handleResultClick = (result) => {
  if (!recentSearches.value.includes(searchQuery.value)) {
    recentSearches.value.unshift(searchQuery.value)
    recentSearches.value = recentSearches.value.slice(0, 5)
    localStorage.setItem('mobileRecentSearches', JSON.stringify(recentSearches.value))
  }
  close()
}

watch(() => props.modelValue, async (newVal) => {
  if (newVal) {
    await nextTick()
    searchInput.value?.focus()
  } else {
    searchQuery.value = ''
    results.value = []
  }
})

onMounted(() => {
  const saved = localStorage.getItem('mobileRecentSearches')
  if (saved) {
    try {
      recentSearches.value = JSON.parse(saved)
    } catch (e) {
      recentSearches.value = []
    }
  }
})
</script>

<style scoped>
@import '../../../css/admin.css' reference;
</style>