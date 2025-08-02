<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('admin-panel.name', 'Admin Panel') }} - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-semibold text-gray-900">
                        {{ config('admin-panel.name', 'Admin Panel') }}
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-700">
                        Welcome, {{ auth()->guard(config('admin-panel.auth.guard', 'admin'))->user()?->name ?? 'User' }}
                    </span>
                    <form method="POST" action="{{ route('admin-panel.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Dashboard Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Dashboard</h2>
            <p class="text-gray-600">Welcome to your admin panel</p>
        </div>

        <!-- Metrics Grid -->
        @if(!empty($metrics))
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            @foreach($metrics as $metric)
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-indigo-500 rounded-md flex items-center justify-center">
                                <span class="text-white text-sm font-medium">{{ substr($metric['name'], 0, 1) }}</span>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">{{ $metric['name'] }}</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $metric['value'] ?? 'N/A' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Quick Actions Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        @if(!empty($quickActions))
                            @foreach($quickActions as $action)
                            <a href="{{ $action['href'] ?? $action['url'] ?? '#' }}"
                               class="flex items-center p-3 text-sm font-medium text-gray-900 rounded-md hover:bg-gray-50 border">
                                <span class="truncate">{{ $action['label'] ?? $action['name'] ?? 'Action' }}</span>
                            </a>
                            @endforeach
                        @else
                            <div class="space-y-2">
                                <a href="#"
                                   class="flex items-center p-3 text-sm font-medium text-gray-900 rounded-md hover:bg-gray-50 border">
                                    <span class="truncate">Manage Users</span>
                                </a>
                                <a href="#"
                                   class="flex items-center p-3 text-sm font-medium text-gray-900 rounded-md hover:bg-gray-50 border">
                                    <span class="truncate">Create Resource</span>
                                </a>
                                <a href="#"
                                   class="flex items-center p-3 text-sm font-medium text-gray-900 rounded-md hover:bg-gray-50 border">
                                    <span class="truncate">View Logs</span>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- System Info Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">System Information</h3>
                    <div class="space-y-3">
                        @if(!empty($systemInfo))
                            @foreach($systemInfo as $key => $value)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                <span class="text-gray-900">{{ $value }}</span>
                            </div>
                            @endforeach
                        @else
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Laravel Version:</span>
                                    <span class="text-gray-900">{{ app()->version() }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">PHP Version:</span>
                                    <span class="text-gray-900">{{ PHP_VERSION }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Environment:</span>
                                    <span class="text-gray-900">{{ app()->environment() }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Debug Mode:</span>
                                    <span class="text-gray-900">{{ config('app.debug') ? 'Enabled' : 'Disabled' }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        @if(!empty($recentActivity))
        <div class="mt-8">
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Activity</h3>
                    <div class="space-y-3">
                        @foreach($recentActivity as $activity)
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900">{{ $activity['description'] ?? 'Activity' }}</p>
                                <p class="text-xs text-gray-500">{{ $activity['time'] ?? 'Just now' }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</body>
</html>
