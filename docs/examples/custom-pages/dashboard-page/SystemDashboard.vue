<template>
    <div class="system-dashboard">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                {{ page.title }}
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Monitor your system's performance, health, and configuration
            </p>
        </div>

        <!-- System Alerts -->
        <div v-if="data.alerts && data.alerts.length > 0" class="mb-8">
            <div 
                v-for="alert in data.alerts" 
                :key="alert.message"
                :class="[
                    'p-4 rounded-lg mb-4',
                    alert.type === 'warning' ? 'bg-yellow-50 border border-yellow-200 text-yellow-800' : '',
                    alert.type === 'error' ? 'bg-red-50 border border-red-200 text-red-800' : '',
                    alert.type === 'info' ? 'bg-blue-50 border border-blue-200 text-blue-800' : ''
                ]"
            >
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <ExclamationTriangleIcon v-if="alert.type === 'warning'" class="h-5 w-5" />
                        <XCircleIcon v-if="alert.type === 'error'" class="h-5 w-5" />
                        <InformationCircleIcon v-if="alert.type === 'info'" class="h-5 w-5" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium">{{ alert.message }}</h3>
                        <p class="mt-1 text-sm">{{ alert.action }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Metrics Cards -->
        <div v-if="data.system_info" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <ClockIcon class="h-8 w-8 text-blue-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Uptime</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ data.system_info.uptime }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <UsersIcon class="h-8 w-8 text-green-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Users</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ data.system_info.active_users }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <ChartBarIcon class="h-8 w-8 text-purple-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Requests Today</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ data.system_info.total_requests_today?.toLocaleString() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <BoltIcon class="h-8 w-8 text-yellow-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Response</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ data.performance_metrics?.response_time_avg }}s
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Server Information Fields -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Server Info -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">
                    Server Information
                </h2>
                <div class="space-y-4">
                    <component
                        v-for="field in serverFields"
                        :key="field.attribute"
                        :is="field.component"
                        :field="field"
                        :value="field.value"
                        :readonly="field.readonly"
                    />
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">
                    Performance Metrics
                </h2>
                <div class="space-y-4">
                    <component
                        v-for="field in performanceFields"
                        :key="field.attribute"
                        :is="field.component"
                        :field="field"
                        :value="field.value"
                        :readonly="field.readonly"
                    />
                </div>
            </div>
        </div>

        <!-- System Configuration -->
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow mb-8">
            <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">
                System Configuration
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <component
                    v-for="field in configFields"
                    :key="field.attribute"
                    :is="field.component"
                    :field="field"
                    :value="field.value"
                    :readonly="field.readonly"
                />
            </div>
        </div>

        <!-- Recent Activity -->
        <div v-if="data.recent_activity" class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Recent Logins -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium mb-4 text-gray-900 dark:text-gray-100">Recent Logins</h3>
                <div class="space-y-3">
                    <div 
                        v-for="login in data.recent_activity.recent_logins" 
                        :key="login.email"
                        class="flex items-center space-x-3"
                    >
                        <div class="flex-shrink-0">
                            <UserIcon class="h-5 w-5 text-gray-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                {{ login.name }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                {{ formatDate(login.last_login_at) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Errors -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium mb-4 text-gray-900 dark:text-gray-100">Recent Errors</h3>
                <div class="space-y-3">
                    <div 
                        v-for="error in data.recent_activity.recent_errors" 
                        :key="error.message"
                        class="flex items-start space-x-3"
                    >
                        <div class="flex-shrink-0">
                            <ExclamationCircleIcon class="h-5 w-5 text-red-500" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ error.message }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ formatDate(error.time) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Events -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium mb-4 text-gray-900 dark:text-gray-100">System Events</h3>
                <div class="space-y-3">
                    <div 
                        v-for="event in data.recent_activity.system_events" 
                        :key="event.event + event.time"
                        class="flex items-start space-x-3"
                    >
                        <div class="flex-shrink-0">
                            <CogIcon class="h-5 w-5 text-blue-500" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ event.event }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ event.user }} • {{ formatDate(event.time) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div v-if="actions.length > 0" class="flex flex-wrap gap-4">
            <button
                v-for="action in actions"
                :key="action.name"
                @click="executeAction(action)"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                {{ action.name }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import {
    ClockIcon,
    UsersIcon,
    ChartBarIcon,
    BoltIcon,
    UserIcon,
    ExclamationCircleIcon,
    ExclamationTriangleIcon,
    XCircleIcon,
    InformationCircleIcon,
    CogIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    page: {
        type: Object,
        required: true
    },
    fields: {
        type: Array,
        default: () => []
    },
    actions: {
        type: Array,
        default: () => []
    },
    metrics: {
        type: Array,
        default: () => []
    },
    data: {
        type: Object,
        default: () => ({})
    }
})

// Group fields by category for organized display
const serverFields = computed(() => {
    return props.fields.filter(field => 
        ['server_name', 'php_version', 'laravel_version'].includes(field.attribute)
    )
})

const performanceFields = computed(() => {
    return props.fields.filter(field => 
        ['memory_usage', 'cpu_load', 'disk_usage'].includes(field.attribute)
    )
})

const configFields = computed(() => {
    return props.fields.filter(field => 
        ['debug_mode', 'environment', 'maintenance_mode', 'cache_driver', 'queue_driver'].includes(field.attribute)
    )
})

const executeAction = async (action) => {
    try {
        // Execute the action via API call
        const response = await fetch(`/admin/actions/${action.uriKey}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({})
        })
        
        const result = await response.json()
        
        if (result.success) {
            // Show success notification
            console.log('Action executed successfully:', result.message)
            // You could integrate with a notification system here
        } else {
            console.error('Action failed:', result.message)
        }
    } catch (error) {
        console.error('Error executing action:', error)
    }
}

const formatDate = (date) => {
    if (!date) return 'N/A'
    return new Date(date).toLocaleString()
}
</script>

<style scoped>
@import '../../../../resources/css/admin.css' reference;

.system-dashboard {
    @apply p-6;
}

/* Custom styles for dashboard-specific elements */
.metric-card {
    @apply bg-white dark:bg-gray-800 p-6 rounded-lg shadow;
}

.metric-value {
    @apply text-2xl font-bold;
}

.metric-label {
    @apply text-sm text-gray-600 dark:text-gray-400;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .system-dashboard {
        @apply p-4;
    }
}
</style>
