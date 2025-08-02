<?php

declare(strict_types=1);

if (! function_exists('admin_panel')) {
    /**
     * Get the AdminPanel facade instance.
     */
    function admin_panel(): \JTD\AdminPanel\Support\AdminPanel
    {
        return app(\JTD\AdminPanel\Support\AdminPanel::class);
    }
}

if (! function_exists('admin_panel_path')) {
    /**
     * Get the path to the admin panel.
     */
    function admin_panel_path(string $path = ''): string
    {
        return config('admin-panel.path', '/admin') . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (! function_exists('admin_panel_route')) {
    /**
     * Generate a URL to an admin panel route.
     */
    function admin_panel_route(string $name, array $parameters = []): string
    {
        return route('admin-panel.' . $name, $parameters);
    }
}

if (! function_exists('admin_panel_asset')) {
    /**
     * Generate a URL to an admin panel asset.
     */
    function admin_panel_asset(string $path): string
    {
        return asset('vendor/admin-panel/' . ltrim($path, '/'));
    }
}

if (! function_exists('admin_panel_vite_asset')) {
    /**
     * Get a Vite asset path from the admin panel manifest.
     */
    function admin_panel_vite_asset(string $entry, string $type = 'file'): string
    {
        $manifestPath = public_path('vendor/admin-panel/.vite/manifest.json');

        if (!file_exists($manifestPath)) {
            throw new \Exception('Admin panel manifest not found. Run: php artisan vendor:publish --tag=admin-panel-assets');
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (!isset($manifest[$entry])) {
            throw new \Exception("Asset entry '{$entry}' not found in admin panel manifest.");
        }

        $entryData = $manifest[$entry];

        // For CSS entries, use the first CSS file from the css array
        if ($type === 'css' && isset($entryData['css']) && !empty($entryData['css'])) {
            return admin_panel_asset($entryData['css'][0]);
        }

        // For JS entries, use the file property
        return admin_panel_asset($entryData['file']);
    }
}
