<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('admin-panel.name', 'Admin Panel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @if(app()->environment('testing'))
        <!-- In testing, just include basic styles -->
        <style>
            body { font-family: system-ui, sans-serif; }
        </style>
    @else
        <!-- Load pre-built CSS from published location -->
        <link rel="stylesheet" href="{{ admin_panel_vite_asset('resources/css/admin.css', 'css') }}">
    @endif
    @inertiaHead
</head>
<body class="font-sans antialiased bg-gray-50">
    @inertia

    @if(!app()->environment('testing'))
        <!-- Ziggy Routes -->
        @routes

        <!-- Admin Panel Configuration -->
        <script>
            window.adminPanelConfig = {
                path: '{{ config('admin-panel.path', 'admin') }}',
                theme: '{{ config('admin-panel.theme.name', 'default') }}',
                debug: {{ config('app.debug') ? 'true' : 'false' }},
                basePath: '{{ base_path() }}',
                publicPath: '{{ public_path() }}',
                resourcesPath: '{{ resource_path() }}',
            };
        </script>

        <!-- Load Custom Pages Manifests (Multi-Package Support) -->
        <script>
            window.adminPanelComponentManifests = {};

            @php
                $adminPanel = app(\JTD\AdminPanel\Support\AdminPanel::class);
                $aggregatedManifests = $adminPanel->getAggregatedManifest();

                // Also load the main app manifest for backward compatibility
                $appManifestPath = public_path('admin-panel-pages-manifest.json');
                $appManifestData = file_exists($appManifestPath) ? json_decode(file_get_contents($appManifestPath), true) : null;
            @endphp

            @if($appManifestData && isset($appManifestData['Pages']))
                // Main application manifest (priority 0 - highest)
                window.adminPanelComponentManifests['app'] = {
                    @foreach($appManifestData['Pages'] as $componentName => $componentData)
                        'Pages/{{ $componentName }}': '{{ asset('build/' . $componentData['file']) }}',
                    @endforeach
                };
                console.log('📦 Loaded main app manifest with {{ count($appManifestData['Pages']) }} components');
            @endif

            @if(!empty($aggregatedManifests))
                // Package manifests (priority-based)
                @foreach($aggregatedManifests as $packageName => $packageManifest)
                    window.adminPanelComponentManifests['{{ $packageName }}'] = {
                        @if(isset($packageManifest['components']))
                            @foreach($packageManifest['components'] as $componentName => $componentData)
                                @if(is_array($componentData) && isset($componentData['useFallback']) && $componentData['useFallback'])
                                    'Pages/{{ $componentName }}': {!! json_encode($componentData) !!},
                                @else
                                    'Pages/{{ $componentName }}': '{{ $packageManifest['base_url'] ?? '' }}/{{ $componentData['file'] ?? $componentData }}',
                                @endif
                            @endforeach
                        @endif
                    };
                    console.log('📦 Loaded {{ $packageName }} manifest (priority: {{ $packageManifest['priority'] ?? 100 }})');
                @endforeach
            @endif

            @if(empty($appManifestData) && empty($aggregatedManifests))
                console.log('📦 No custom page manifests found');
            @endif
        </script>

        <!-- Load pre-built JavaScript from published location -->
        <script type="module" src="{{ admin_panel_vite_asset('resources/js/app.js') }}"></script>
    @endif
</body>
</html>
