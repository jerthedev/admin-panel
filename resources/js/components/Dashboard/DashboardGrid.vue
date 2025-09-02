<template>
  <div
    ref="gridRef"
    class="dashboard-grid"
    :class="gridClasses"
    :style="gridStyles"
    role="grid"
    aria-label="Dashboard grid"
    @drop="handleDrop"
    @dragover.prevent
    @dragenter.prevent
  >
    <div
      v-for="card in cards"
      :key="card.id"
      class="grid-item"
      :class="getItemClasses(card)"
      :style="getItemStyles(card)"
      :draggable="draggable"
      :tabindex="draggable ? 0 : -1"
      role="gridcell"
      :aria-label="`Card: ${card.title || card.id}`"
      @click="handleCardClick(card, $event)"
      @dragstart="handleDragStart(card, $event)"
      @dragend="handleDragEnd(card, $event)"
      @keydown="handleKeyDown(card, $event)"
    >
      <component
        :is="card.component"
        v-bind="card.props"
        :card="card"
        @error="handleCardError"
        @refresh="handleCardRefresh"
      />
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'

export default {
  name: 'DashboardGrid',
  props: {
    cards: {
      type: Array,
      required: true,
      default: () => []
    },
    columns: {
      type: Object,
      default: () => ({
        mobile: 1,
        tablet: 2,
        desktop: 3,
        wide: 4
      })
    },
    gap: {
      type: String,
      default: '1rem'
    },
    autoRows: {
      type: String,
      default: 'minmax(200px, auto)'
    },
    responsive: {
      type: Boolean,
      default: true
    },
    draggable: {
      type: Boolean,
      default: false
    },
    minCardWidth: {
      type: String,
      default: '280px'
    },
    maxCardWidth: {
      type: String,
      default: 'none'
    }
  },
  emits: [
    'card-click',
    'card-drop',
    'drag-start',
    'drag-end',
    'card-activate',
    'grid-resize',
    'card-error',
    'card-refresh'
  ],
  setup(props, { emit }) {
    const gridRef = ref(null)
    const currentBreakpoint = ref('desktop')
    const draggedCard = ref(null)
    const resizeObserver = ref(null)

    // Breakpoint detection
    const breakpoints = {
      mobile: '(max-width: 767px)',
      tablet: '(min-width: 768px) and (max-width: 1023px)',
      desktop: '(min-width: 1024px) and (max-width: 1535px)',
      wide: '(min-width: 1536px)'
    }

    const mediaQueries = ref({})

    // Computed properties
    const currentColumns = computed(() => {
      if (!props.responsive) {
        return props.columns.desktop || 3
      }
      return props.columns[currentBreakpoint.value] || 3
    })

    const gridClasses = computed(() => [
      'dashboard-grid-base',
      `breakpoint-${currentBreakpoint.value}`,
      {
        'draggable-enabled': props.draggable,
        'responsive-grid': props.responsive
      }
    ])

    const gridStyles = computed(() => ({
      display: 'grid',
      gridTemplateColumns: `repeat(${currentColumns.value}, 1fr)`,
      gap: props.gap,
      gridAutoRows: props.autoRows,
      minWidth: '100%'
    }))

    // Methods
    const detectBreakpoint = () => {
      if (!props.responsive) return

      for (const [breakpoint, query] of Object.entries(breakpoints)) {
        if (mediaQueries.value[breakpoint]?.matches) {
          currentBreakpoint.value = breakpoint
          break
        }
      }
    }

    const setupMediaQueries = () => {
      if (!props.responsive) return

      Object.entries(breakpoints).forEach(([breakpoint, query]) => {
        const mq = window.matchMedia(query)
        mediaQueries.value[breakpoint] = mq
        mq.addEventListener('change', detectBreakpoint)
      })

      detectBreakpoint()
    }

    const cleanupMediaQueries = () => {
      Object.values(mediaQueries.value).forEach(mq => {
        mq.removeEventListener('change', detectBreakpoint)
      })
    }

    const getGridAreaString = (gridArea) => {
      if (!gridArea) return 'auto'

      const {
        row = 1,
        column = 1,
        rowSpan = 1,
        columnSpan = 1
      } = gridArea

      // Ensure positive values
      const safeRow = Math.max(1, row)
      const safeColumn = Math.max(1, column)
      const safeRowSpan = Math.max(1, rowSpan)
      const safeColumnSpan = Math.max(1, columnSpan)

      return `${safeRow} / ${safeColumn} / ${safeRow + safeRowSpan} / ${safeColumn + safeColumnSpan}`
    }

    const getItemClasses = (card) => [
      'grid-item-base',
      {
        'draggable': props.draggable,
        'dragging': draggedCard.value?.id === card.id,
        'has-error': card.error
      },
      card.classes || []
    ]

    const getItemStyles = (card) => ({
      gridArea: getGridAreaString(card.gridArea),
      minWidth: props.minCardWidth,
      maxWidth: props.maxCardWidth,
      ...card.styles
    })

    // Event handlers
    const handleCardClick = (card, event) => {
      emit('card-click', card, event)
    }

    const handleDragStart = (card, event) => {
      if (!props.draggable) return

      draggedCard.value = card

      // Handle dataTransfer safely (might not exist in test environment)
      if (event.dataTransfer) {
        event.dataTransfer.setData('text/plain', card.id)
        event.dataTransfer.effectAllowed = 'move'
      }

      emit('drag-start', card, event)
    }

    const handleDragEnd = (card, event) => {
      if (!props.draggable) return

      draggedCard.value = null
      emit('drag-end', card, event)
    }

    const handleDrop = (event) => {
      if (!props.draggable) return

      event.preventDefault()

      // Handle dataTransfer safely (might not exist in test environment)
      if (event.dataTransfer) {
        const cardId = event.dataTransfer.getData('text/plain')
        const card = props.cards.find(c => c.id === cardId)

        if (card) {
          emit('card-drop', card, event)
        }
      }
    }

    const handleKeyDown = (card, event) => {
      if (!props.draggable) return

      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault()
        emit('card-activate', card, event)
      }
    }

    const handleCardError = (error, card) => {
      emit('card-error', error, card)
    }

    const handleCardRefresh = (card) => {
      emit('card-refresh', card)
    }

    const handleResize = () => {
      emit('grid-resize', {
        breakpoint: currentBreakpoint.value,
        columns: currentColumns.value,
        gridElement: gridRef.value
      })
    }

    const setupResizeObserver = () => {
      if (!window.ResizeObserver) return

      resizeObserver.value = new ResizeObserver(() => {
        handleResize()
      })

      if (gridRef.value) {
        resizeObserver.value.observe(gridRef.value)
      }
    }

    const cleanupResizeObserver = () => {
      if (resizeObserver.value) {
        resizeObserver.value.disconnect()
      }
    }

    // Lifecycle
    onMounted(async () => {
      await nextTick()
      setupMediaQueries()
      setupResizeObserver()
    })

    onUnmounted(() => {
      cleanupMediaQueries()
      cleanupResizeObserver()
    })

    // Expose methods for testing
    return {
      gridRef,
      currentBreakpoint,
      currentColumns,
      gridClasses,
      gridStyles,
      getGridAreaString,
      getItemClasses,
      getItemStyles,
      handleCardClick,
      handleDragStart,
      handleDragEnd,
      handleDrop,
      handleKeyDown,
      handleCardError,
      handleCardRefresh,
      handleResize,
      cards: computed(() => props.cards)
    }
  }
}
</script>

<style scoped>
.dashboard-grid {
  width: 100%;
  min-height: 200px;
}

.grid-item {
  position: relative;
  overflow: hidden;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.grid-item.draggable {
  cursor: grab;
}

.grid-item.draggable:active,
.grid-item.dragging {
  cursor: grabbing;
  transform: scale(1.02);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  z-index: 1000;
}

.grid-item.has-error {
  border: 2px solid #ef4444;
  border-radius: 0.5rem;
}

.grid-item:focus {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

/* Responsive adjustments */
@media (max-width: 767px) {
  .dashboard-grid {
    gap: 0.75rem;
  }
}

@media (min-width: 768px) and (max-width: 1023px) {
  .dashboard-grid {
    gap: 1rem;
  }
}

@media (min-width: 1024px) {
  .dashboard-grid {
    gap: 1.25rem;
  }
}

/* Animation for drag and drop */
.grid-item {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.draggable-enabled .grid-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>
