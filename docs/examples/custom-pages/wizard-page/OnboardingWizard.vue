<template>
    <div class="onboarding-wizard">
        <!-- Wizard Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                {{ page.title }}
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Complete your account setup in {{ data.wizard.totalSteps }} easy steps
            </p>
        </div>

        <!-- Progress Bar -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Progress
                </span>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ data.progress.stepsCompleted }} of {{ data.wizard.totalSteps }} completed
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div 
                    class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                    :style="{ width: data.progress.percentage + '%' }"
                ></div>
            </div>
        </div>

        <!-- Step Navigation -->
        <div class="mb-8">
            <nav class="flex space-x-4" aria-label="Wizard Steps">
                <button
                    v-for="(step, stepNumber) in data.steps"
                    :key="stepNumber"
                    @click="navigateToStep(stepNumber)"
                    :disabled="!canAccessStep(stepNumber)"
                    :class="[
                        'flex items-center px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                        stepNumber === data.wizard.currentStep 
                            ? 'bg-blue-600 text-white' 
                            : canAccessStep(stepNumber)
                                ? 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                                : 'bg-gray-50 text-gray-400 cursor-not-allowed dark:bg-gray-800 dark:text-gray-600'
                    ]"
                >
                    <!-- Step Number -->
                    <span 
                        :class="[
                            'flex items-center justify-center w-6 h-6 rounded-full text-xs mr-2',
                            stepNumber === data.wizard.currentStep 
                                ? 'bg-white text-blue-600' 
                                : isStepCompleted(stepNumber)
                                    ? 'bg-green-500 text-white'
                                    : 'bg-gray-300 text-gray-600 dark:bg-gray-600 dark:text-gray-400'
                        ]"
                    >
                        <CheckIcon v-if="isStepCompleted(stepNumber)" class="w-4 h-4" />
                        <span v-else>{{ stepNumber }}</span>
                    </span>
                    
                    <!-- Step Title -->
                    <span>{{ step.title }}</span>
                </button>
            </nav>
        </div>

        <!-- Current Step Content -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-8">
            <!-- Step Header -->
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ currentStepData.title }}
                </h2>
                <p class="mt-1 text-gray-600 dark:text-gray-400">
                    {{ currentStepData.description }}
                </p>
            </div>

            <!-- Step Fields -->
            <div class="space-y-6">
                <component
                    v-for="field in currentStepFields"
                    :key="field.attribute"
                    :is="field.component"
                    :field="field"
                    v-model="wizardForm[field.attribute]"
                    @update:modelValue="updateFieldValue(field.attribute, $event)"
                />
            </div>
        </div>

        <!-- Navigation Controls -->
        <div class="flex justify-between items-center">
            <!-- Previous Button -->
            <button
                v-if="data.wizard.currentStep > 1"
                @click="previousStep"
                class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                <ChevronLeftIcon class="w-4 h-4 inline mr-2" />
                Previous
            </button>
            <div v-else></div>

            <!-- Next/Complete Button -->
            <button
                @click="nextStep"
                :disabled="!canProceedFromCurrentStep"
                :class="[
                    'px-6 py-2 rounded-lg font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                    canProceedFromCurrentStep
                        ? 'bg-blue-600 text-white hover:bg-blue-700'
                        : 'bg-gray-300 text-gray-500 cursor-not-allowed dark:bg-gray-600 dark:text-gray-400'
                ]"
            >
                <span v-if="data.wizard.currentStep < data.wizard.totalSteps">
                    Next
                    <ChevronRightIcon class="w-4 h-4 inline ml-2" />
                </span>
                <span v-else>
                    Complete Setup
                    <CheckIcon class="w-4 h-4 inline ml-2" />
                </span>
            </button>
        </div>

        <!-- Debug Information (development only) -->
        <div v-if="showDebugInfo" class="mt-8 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
            <h3 class="text-sm font-medium mb-2">Debug Information</h3>
            <pre class="text-xs text-gray-600 dark:text-gray-400">{{ JSON.stringify({
                currentStep: data.wizard.currentStep,
                completedSteps: data.wizard.completedSteps,
                wizardData: data.wizardData,
                formData: wizardForm
            }, null, 2) }}</pre>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    CheckIcon,
    ChevronLeftIcon,
    ChevronRightIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    page: Object,
    fields: Array,
    actions: Array,
    data: Object
})

// Reactive form data
const wizardForm = ref({})
const showDebugInfo = ref(false) // Set to true for development

// Initialize form with existing wizard data
onMounted(() => {
    wizardForm.value = { ...props.data.wizardData }
})

// Computed properties
const currentStepData = computed(() => {
    return props.data.steps[props.data.wizard.currentStep] || {}
})

const currentStepFields = computed(() => {
    const stepFieldNames = currentStepData.value.fields || []
    return props.fields.filter(field => 
        stepFieldNames.includes(field.attribute)
    )
})

const canProceedFromCurrentStep = computed(() => {
    // Check if required fields for current step are filled
    const requiredFields = currentStepFields.value.filter(field => 
        field.rules && field.rules.includes('required')
    )
    
    return requiredFields.every(field => 
        wizardForm.value[field.attribute] && 
        wizardForm.value[field.attribute].toString().trim() !== ''
    )
})

// Methods
const navigateToStep = (stepNumber) => {
    if (canAccessStep(stepNumber)) {
        saveCurrentStep()
        router.visit(`/admin/pages/onboardingwizard?step=${stepNumber}`)
    }
}

const canAccessStep = (stepNumber) => {
    // Can access current step or any completed step
    return stepNumber <= props.data.wizard.currentStep || 
           isStepCompleted(stepNumber)
}

const isStepCompleted = (stepNumber) => {
    return props.data.wizard.completedSteps.includes(stepNumber)
}

const previousStep = () => {
    if (props.data.wizard.currentStep > 1) {
        saveCurrentStep()
        const prevStep = props.data.wizard.currentStep - 1
        router.visit(`/admin/pages/onboardingwizard?step=${prevStep}`)
    }
}

const nextStep = () => {
    if (canProceedFromCurrentStep.value) {
        saveCurrentStep()
        
        if (props.data.wizard.currentStep < props.data.wizard.totalSteps) {
            const nextStep = props.data.wizard.currentStep + 1
            router.visit(`/admin/pages/onboardingwizard?step=${nextStep}`)
        } else {
            completeWizard()
        }
    }
}

const updateFieldValue = (attribute, value) => {
    wizardForm.value[attribute] = value
}

const saveCurrentStep = async () => {
    try {
        const saveAction = props.actions.find(action => action.name === 'Save Wizard Step')
        if (saveAction) {
            await executeAction(saveAction, {
                stepData: wizardForm.value,
                currentStep: props.data.wizard.currentStep
            })
        }
    } catch (error) {
        console.error('Error saving step:', error)
    }
}

const completeWizard = async () => {
    try {
        const completeAction = props.actions.find(action => action.name === 'Complete Onboarding')
        if (completeAction) {
            const result = await executeAction(completeAction, {
                wizardData: wizardForm.value
            })
            
            if (result.redirect) {
                router.visit(result.redirect)
            }
        }
    } catch (error) {
        console.error('Error completing wizard:', error)
    }
}

const executeAction = async (action, payload = {}) => {
    const response = await fetch(`/admin/actions/${action.uriKey}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(payload)
    })
    
    return await response.json()
}
</script>

<style scoped>
@import '../../../../resources/css/admin.css' reference;

.onboarding-wizard {
    @apply max-w-4xl mx-auto p-6;
}

/* Custom wizard-specific styles */
.step-indicator {
    @apply flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium;
}

.step-indicator.completed {
    @apply bg-green-500 text-white;
}

.step-indicator.current {
    @apply bg-blue-600 text-white;
}

.step-indicator.pending {
    @apply bg-gray-300 text-gray-600;
}
</style>
