/**
 * JTD Admin Panel Vite Plugin
 *
 * Automatically detects admin pages, generates build entries, and creates
 * component manifests for the hybrid asset system.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { resolve, relative, join } from 'path';
import { existsSync, readdirSync, statSync, writeFileSync } from 'fs';
import pkg from 'glob';
const { glob } = pkg;

/**
 * Admin Panel Vite Plugin
 *
 * @param {Object} options - Plugin configuration options
 * @param {string} options.adminPagesPath - Path to admin pages directory (default: 'resources/js/admin-pages')
 * @param {string} options.adminCardsPath - Path to admin cards directory (default: 'resources/js/admin-cards')
 * @param {string} options.manifestPath - Path to output manifest file (default: 'public/admin-pages-manifest.json')
 * @param {boolean} options.hotReload - Enable hot reloading for admin pages and cards (default: true)
 * @returns {Object} Vite plugin configuration
 */
export function adminPanel(options = {}) {
    const config = {
        adminPagesPath: 'resources/js/admin-pages',
        adminCardsPath: 'resources/js/admin-cards',
        manifestPath: 'public/admin-pages-manifest.json',
        hotReload: true,
        ...options
    };

    let viteConfig;
    let isProduction = false;

    return {
        name: 'admin-panel',

        configResolved(resolvedConfig) {
            viteConfig = resolvedConfig;
            isProduction = resolvedConfig.command === 'build';
        },

        config(userConfig, { command }) {
            const root = userConfig.root || process.cwd();
            const adminPagesFullPath = resolve(root, config.adminPagesPath);
            const adminCardsFullPath = resolve(root, config.adminCardsPath);

            // Check if admin pages directory exists
            const pagesExist = existsSync(adminPagesFullPath);
            const cardsExist = existsSync(adminCardsFullPath);

            if (!pagesExist && !cardsExist) {
                console.log('📁 Admin pages and cards directories not found, skipping admin panel plugin');
                return {};
            }

            // Detect admin page components
            const pageComponents = pagesExist ? detectAdminPageComponents(adminPagesFullPath) : [];

            // Detect admin card components
            const cardComponents = cardsExist ? detectAdminCardComponents(adminCardsFullPath) : [];

            const totalComponents = pageComponents.length + cardComponents.length;

            if (totalComponents === 0) {
                console.log('📄 No admin components found, skipping admin panel plugin');
                return {};
            }

            console.log(`🎯 Admin Panel Plugin: Found ${pageComponents.length} page components and ${cardComponents.length} card components`);
            console.log(`📦 Admin Panel Plugin: Components will be dynamically imported (not built as entries)`);

            // Store components for manifest generation but don't add as build entries
            // This allows components to be dynamically imported while still generating a manifest

            // Don't modify build input - let components be dynamically imported
            return {};
        },

        generateBundle(options, bundle) {
            console.log(`🔧 generateBundle called, isProduction: ${isProduction}`);
            if (!isProduction) return;

            const root = viteConfig.root || process.cwd();
            const adminPagesFullPath = resolve(root, config.adminPagesPath);
            const adminCardsFullPath = resolve(root, config.adminCardsPath);

            console.log(`🔍 Checking admin pages path: ${adminPagesFullPath}`);
            console.log(`🔍 Checking admin cards path: ${adminCardsFullPath}`);

            const pagesExist = existsSync(adminPagesFullPath);
            const cardsExist = existsSync(adminCardsFullPath);

            if (!pagesExist && !cardsExist) {
                console.log(`❌ Neither admin pages nor cards directories exist`);
                return;
            }

            // Detect components and generate simple manifest for dynamic imports
            const pageComponents = pagesExist ? detectAdminPageComponents(adminPagesFullPath) : [];
            const cardComponents = cardsExist ? detectAdminCardComponents(adminCardsFullPath) : [];

            console.log(`🎯 Found ${pageComponents.length} page components and ${cardComponents.length} card components for manifest`);

            const manifest = generateSimpleManifest(
                pageComponents,
                cardComponents,
                adminPagesFullPath,
                adminCardsFullPath,
                config.adminPagesPath,
                config.adminCardsPath
            );

            const totalComponents = Object.keys(manifest['Pages']).length + Object.keys(manifest['Cards']).length;

            if (totalComponents > 0) {
                // Write manifest file
                const manifestFullPath = resolve(root, config.manifestPath);
                writeFileSync(manifestFullPath, JSON.stringify(manifest, null, 2));
                console.log(`📋 Admin Panel Manifest: Generated ${manifestFullPath} (dynamic import mode)`);
                console.log(`📦 Page Components: ${Object.keys(manifest['Pages']).length}`);
                console.log(`🎯 Card Components: ${Object.keys(manifest['Cards']).length}`);
            } else {
                console.log(`⚠️ No components found for manifest generation`);
            }
        },

        handleHotUpdate({ file, server }) {
            if (!config.hotReload) return;

            const root = viteConfig.root || process.cwd();
            const adminPagesFullPath = resolve(root, config.adminPagesPath);
            const adminCardsFullPath = resolve(root, config.adminCardsPath);

            // Check if the updated file is an admin page or card component
            const isPageComponent = file.startsWith(adminPagesFullPath) && file.endsWith('.vue');
            const isCardComponent = file.startsWith(adminCardsFullPath) && file.endsWith('.vue');

            if (isPageComponent || isCardComponent) {
                const componentType = isPageComponent ? 'page' : 'card';
                console.log(`🔥 Hot reload: Admin ${componentType} component updated - ${relative(root, file)}`);

                // Trigger full reload for admin components to ensure proper registration
                server.ws.send({
                    type: 'full-reload'
                });

                return [];
            }
        }
    };
}

/**
 * Detect all Vue components in the admin pages directory
 *
 * @param {string} adminPagesPath - Full path to admin pages directory
 * @returns {Array<string>} Array of component file paths
 */
function detectAdminPageComponents(adminPagesPath) {
    try {
        const pattern = join(adminPagesPath, '**/*.vue').replace(/\\/g, '/');
        const components = glob.sync(pattern);

        return components.filter(component => {
            try {
                const stats = statSync(component);
                return stats.isFile() && stats.size > 0;
            } catch (error) {
                return false;
            }
        });
    } catch (error) {
        console.warn('⚠️  Admin Panel Plugin: Error detecting page components:', error.message);
        return [];
    }
}

/**
 * Detect all Vue components in the admin cards directory
 *
 * @param {string} adminCardsPath - Full path to admin cards directory
 * @returns {Array<string>} Array of component file paths
 */
function detectAdminCardComponents(adminCardsPath) {
    try {
        const pattern = join(adminCardsPath, '**/*.vue').replace(/\\/g, '/');
        const components = glob.sync(pattern);

        return components.filter(component => {
            try {
                const stats = statSync(component);
                return stats.isFile() && stats.size > 0;
            } catch (error) {
                return false;
            }
        });
    } catch (error) {
        console.warn('⚠️  Admin Panel Plugin: Error detecting card components:', error.message);
        return [];
    }
}

/**
 * Generate Vite build entries for admin page components
 *
 * @param {Array<string>} components - Array of component file paths
 * @param {string} adminPagesFullPath - Full path to admin pages directory
 * @param {string} adminPagesRelativePath - Relative path to admin pages directory
 * @returns {Object} Build entries object
 */
function generateBuildEntries(components, adminPagesFullPath, adminPagesRelativePath) {
    const entries = {};

    components.forEach(componentPath => {
        // Get relative path from admin pages directory
        const relativePath = relative(adminPagesFullPath, componentPath);

        // Remove .vue extension and create entry name with Pages/ prefix
        const entryName = `Pages/${relativePath.replace(/\.vue$/, '')}`;

        // Use relative path from project root for Vite
        const viteInputPath = join(adminPagesRelativePath, relativePath).replace(/\\/g, '/');

        entries[entryName] = viteInputPath;
    });

    return entries;
}

/**
 * Generate simple manifest for dynamic imports
 *
 * @param {Array<string>} pageComponents - Array of page component file paths
 * @param {Array<string>} cardComponents - Array of card component file paths
 * @param {string} adminPagesFullPath - Full path to admin pages directory
 * @param {string} adminCardsFullPath - Full path to admin cards directory
 * @param {string} adminPagesRelativePath - Relative path to admin pages directory
 * @param {string} adminCardsRelativePath - Relative path to admin cards directory
 * @returns {Object} Manifest object
 */
function generateSimpleManifest(pageComponents, cardComponents, adminPagesFullPath, adminCardsFullPath, adminPagesRelativePath, adminCardsRelativePath) {
    const manifest = {
        'Pages': {},
        'Cards': {}
    };

    // Process page components
    pageComponents.forEach(componentPath => {
        // Get relative path from admin pages directory
        const relativePath = relative(adminPagesFullPath, componentPath);

        // Remove .vue extension and create component name
        const componentName = relativePath.replace(/\.vue$/, '');

        // For main app components, we'll rely on development fallback
        // The manifest just indicates that the component exists
        manifest['Pages'][componentName] = {
            file: `fallback:${join(adminPagesRelativePath, relativePath).replace(/\\/g, '/')}`,
            isDynamicImport: true,
            useFallback: true
        };
    });

    // Process card components
    cardComponents.forEach(componentPath => {
        // Get relative path from admin cards directory
        const relativePath = relative(adminCardsFullPath, componentPath);

        // Remove .vue extension and create component name
        const componentName = relativePath.replace(/\.vue$/, '');

        // For main app components, we'll rely on development fallback
        // The manifest just indicates that the component exists
        manifest['Cards'][componentName] = {
            file: `fallback:${join(adminCardsRelativePath, relativePath).replace(/\\/g, '/')}`,
            isDynamicImport: true,
            useFallback: true
        };
    });

    return manifest;
}

/**
 * Default export for CommonJS compatibility
 */
export default adminPanel;
