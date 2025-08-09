/**
 * Multi-Component Page Store
 * 
 * Manages shared state, field tracking, and component communication
 * for pages with multiple Vue components.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { defineStore } from 'pinia'
import { ref, computed, reactive } from 'vue'

export const useMultiComponentPageStore = defineStore('multiComponentPage', () => {
    // State
    const currentPage = ref(null)
    const currentComponent = ref(null)
    const availableComponents = ref([])
    const fieldState = reactive({})
    const fieldChanges = reactive({})
    const componentHistory = ref([])
    const isLoading = ref(false)
    const isSaving = ref(false)

    // Getters
    const hasMultipleComponents = computed(() => availableComponents.value.length > 1)
    const hasUnsavedChanges = computed(() => Object.keys(fieldChanges.value).length > 0)
    const canNavigateBack = computed(() => componentHistory.value.length > 1)
    const previousComponent = computed(() => {
        const history = componentHistory.value
        return history.length > 1 ? history[history.length - 2] : null
    })

    // Actions
    function initializePage(pageData, components, initialFieldData = {}) {
        currentPage.value = pageData
        availableComponents.value = components
        currentComponent.value = components[0] // Primary component
        componentHistory.value = [components[0]]
        
        // Initialize field state
        Object.keys(fieldState).forEach(key => delete fieldState[key])
        Object.keys(fieldChanges).forEach(key => delete fieldChanges[key])
        
        Object.assign(fieldState, initialFieldData)
        
        console.log(`🎯 Initialized multi-component page: ${pageData.title}`)
        console.log(`📦 Available components: ${components.join(', ')}`)
    }

    function navigateToComponent(componentName) {
        if (!availableComponents.value.includes(componentName)) {
            console.warn(`Component ${componentName} not available`)
            return false
        }

        if (currentComponent.value !== componentName) {
            currentComponent.value = componentName
            
            // Add to history if not going back
            const lastComponent = componentHistory.value[componentHistory.value.length - 1]
            if (lastComponent !== componentName) {
                componentHistory.value.push(componentName)
            }
            
            console.log(`🔄 Navigated to component: ${componentName}`)
        }
        
        return true
    }

    function navigateBack() {
        if (canNavigateBack.value) {
            componentHistory.value.pop()
            const previousComp = componentHistory.value[componentHistory.value.length - 1]
            currentComponent.value = previousComp
            console.log(`⬅️ Navigated back to: ${previousComp}`)
            return true
        }
        return false
    }

    function updateField(fieldName, value, componentSource = null) {
        // Update field state
        fieldState[fieldName] = value
        
        // Track changes for reconciliation
        fieldChanges[fieldName] = {
            value,
            component: componentSource || currentComponent.value,
            timestamp: new Date().toISOString()
        }
        
        console.log(`📝 Field updated: ${fieldName} = ${value} (from ${componentSource || currentComponent.value})`)
    }

    function getFieldValue(fieldName, defaultValue = null) {
        return fieldState[fieldName] ?? defaultValue
    }

    function hasFieldChanged(fieldName) {
        return fieldName in fieldChanges
    }

    function getFieldChanges() {
        return { ...fieldChanges }
    }

    function reconcileChanges() {
        // Group changes by field, keeping the most recent change
        const reconciled = {}
        
        Object.entries(fieldChanges).forEach(([fieldName, change]) => {
            if (!reconciled[fieldName] || 
                new Date(change.timestamp) > new Date(reconciled[fieldName].timestamp)) {
                reconciled[fieldName] = change
            }
        })
        
        console.log(`🔄 Reconciled ${Object.keys(reconciled).length} field changes`)
        return reconciled
    }

    async function saveChanges(saveEndpoint, additionalData = {}) {
        if (!hasUnsavedChanges.value) {
            console.log('💾 No changes to save')
            return { success: true, message: 'No changes to save' }
        }

        isSaving.value = true
        
        try {
            const reconciledChanges = reconcileChanges()
            const saveData = {
                fields: Object.fromEntries(
                    Object.entries(reconciledChanges).map(([field, change]) => [field, change.value])
                ),
                metadata: {
                    page: currentPage.value?.title,
                    components: availableComponents.value,
                    changeHistory: reconciledChanges
                },
                ...additionalData
            }
            
            console.log('💾 Saving multi-component page changes:', saveData)
            
            // Make the save request (this would be customized per implementation)
            const response = await fetch(saveEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify(saveData)
            })
            
            if (!response.ok) {
                throw new Error(`Save failed: ${response.statusText}`)
            }
            
            const result = await response.json()
            
            // Clear changes after successful save
            Object.keys(fieldChanges).forEach(key => delete fieldChanges[key])
            
            console.log('✅ Multi-component page saved successfully')
            return { success: true, data: result }
            
        } catch (error) {
            console.error('❌ Failed to save multi-component page:', error)
            return { success: false, error: error.message }
        } finally {
            isSaving.value = false
        }
    }

    function resetPage() {
        currentPage.value = null
        currentComponent.value = null
        availableComponents.value = []
        componentHistory.value = []
        Object.keys(fieldState).forEach(key => delete fieldState[key])
        Object.keys(fieldChanges).forEach(key => delete fieldChanges[key])
        isLoading.value = false
        isSaving.value = false
        
        console.log('🔄 Multi-component page state reset')
    }

    function getComponentUrl(componentName, basePath = null) {
        if (!basePath && currentPage.value) {
            basePath = `/admin/pages/${currentPage.value.slug || 'unknown'}`
        }
        
        return componentName === availableComponents.value[0] 
            ? basePath  // Primary component uses base URL
            : `${basePath}/${componentName.toLowerCase()}`
    }

    return {
        // State
        currentPage,
        currentComponent,
        availableComponents,
        fieldState,
        fieldChanges,
        componentHistory,
        isLoading,
        isSaving,

        // Getters
        hasMultipleComponents,
        hasUnsavedChanges,
        canNavigateBack,
        previousComponent,

        // Actions
        initializePage,
        navigateToComponent,
        navigateBack,
        updateField,
        getFieldValue,
        hasFieldChanged,
        getFieldChanges,
        reconcileChanges,
        saveChanges,
        resetPage,
        getComponentUrl
    }
})
