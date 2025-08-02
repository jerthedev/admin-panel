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
            };
        </script>

        <!-- Load pre-built JavaScript from published location -->
        <script type="module" src="{{ admin_panel_vite_asset('resources/js/app.js') }}"></script>
    @endif
</body>
</html>
