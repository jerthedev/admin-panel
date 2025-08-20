/**
 * JTD Admin Panel Vite Plugin (CommonJS)
 * 
 * CommonJS wrapper for the ES module version.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

const { resolve, relative, join } = require('path');
const { existsSync, statSync, writeFileSync } = require('fs');
const { glob } = require('glob');

/**
 * Admin Panel Vite Plugin
 * 
 * @param {Object} options - Plugin configuration options
 * @returns {Object} Vite plugin configuration
 */
function adminPanel(options = {}) {
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

            const pagesExist = existsSync(adminPagesFullPath);
            const cardsExist = existsSync(adminCardsFullPath);

            if (!pagesExist && !cardsExist) {
                console.log('📁 Admin pages and cards directories not found, skipping admin panel plugin');
                return {};
            }

            const pageComponents = pagesExist ? detectAdminPageComponents(adminPagesFullPath) : [];
            const cardComponents = cardsExist ? detectAdminCardComponents(adminCardsFullPath) : [];

            const totalComponents = pageComponents.length + cardComponents.length;

            if (totalComponents === 0) {
                console.log('📄 No admin components found, skipping admin panel plugin');
                return {};
            }

            console.log(`🎯 Admin Panel Plugin: Found ${pageComponents.length} page components and ${cardComponents.length} card components`);

            const pageEntries = generateBuildEntries(pageComponents, adminPagesFullPath, config.adminPagesPath, 'admin-pages');
            const cardEntries = generateBuildEntries(cardComponents, adminCardsFullPath, config.adminCardsPath, 'admin-cards');
            const allEntries = { ...pageEntries, ...cardEntries };

            const existingInput = userConfig.build?.rollupOptions?.input || {};
            const mergedInput = typeof existingInput === 'string'
                ? { main: existingInput, ...allEntries }
                : { ...existingInput, ...allEntries };

            return {
                build: {
                    rollupOptions: {
                        input: mergedInput,
                    },
                },
            };
        },

        generateBundle(options, bundle) {
            if (!isProduction) return;

            const root = viteConfig.root || process.cwd();
            const adminPagesFullPath = resolve(root, config.adminPagesPath);
            const adminCardsFullPath = resolve(root, config.adminCardsPath);

            const pagesExist = existsSync(adminPagesFullPath);
            const cardsExist = existsSync(adminCardsFullPath);

            if (!pagesExist && !cardsExist) return;

            const manifest = generateManifest(bundle, config.adminPagesPath, config.adminCardsPath);

            const totalComponents = Object.keys(manifest['admin-pages']).length + Object.keys(manifest['admin-cards']).length;

            if (totalComponents > 0) {
                const manifestFullPath = resolve(root, config.manifestPath);
                writeFileSync(manifestFullPath, JSON.stringify(manifest, null, 2));
                console.log(`📋 Admin Panel Manifest: Generated ${manifestFullPath}`);
                console.log(`📦 Page Components: ${Object.keys(manifest['admin-pages']).length}`);
                console.log(`🎯 Card Components: ${Object.keys(manifest['admin-cards']).length}`);
            }
        },

        handleHotUpdate({ file, server }) {
            if (!config.hotReload) return;

            const root = viteConfig.root || process.cwd();
            const adminPagesFullPath = resolve(root, config.adminPagesPath);
            const adminCardsFullPath = resolve(root, config.adminCardsPath);

            const isPageComponent = file.startsWith(adminPagesFullPath) && file.endsWith('.vue');
            const isCardComponent = file.startsWith(adminCardsFullPath) && file.endsWith('.vue');

            if (isPageComponent || isCardComponent) {
                const componentType = isPageComponent ? 'page' : 'card';
                console.log(`🔥 Hot reload: Admin ${componentType} component updated - ${relative(root, file)}`);

                server.ws.send({
                    type: 'full-reload'
                });

                return [];
            }
        }
    };
}

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

function generateBuildEntries(components, adminFullPath, adminRelativePath, prefix = 'admin-pages') {
    const entries = {};

    components.forEach(componentPath => {
        const relativePath = relative(adminFullPath, componentPath);
        const entryName = `${prefix}/${relativePath.replace(/\.vue$/, '')}`;
        const viteInputPath = join(adminRelativePath, relativePath).replace(/\\/g, '/');

        entries[entryName] = viteInputPath;
    });

    return entries;
}

function generateManifest(bundle, adminPagesPath, adminCardsPath) {
    const manifest = {
        'admin-pages': {},
        'admin-cards': {}
    };

    Object.entries(bundle).forEach(([fileName, chunk]) => {
        if (chunk.isEntry && chunk.name) {
            if (chunk.name.startsWith('admin-pages/')) {
                const componentName = chunk.name.replace('admin-pages/', '');

                manifest['admin-pages'][componentName] = {
                    file: fileName,
                    css: chunk.viteMetadata?.importedCss ? Array.from(chunk.viteMetadata.importedCss) : [],
                    assets: chunk.viteMetadata?.importedAssets ? Array.from(chunk.viteMetadata.importedAssets) : []
                };
            } else if (chunk.name.startsWith('admin-cards/')) {
                const componentName = chunk.name.replace('admin-cards/', '');

                manifest['admin-cards'][componentName] = {
                    file: fileName,
                    css: chunk.viteMetadata?.importedCss ? Array.from(chunk.viteMetadata.importedCss) : [],
                    assets: chunk.viteMetadata?.importedAssets ? Array.from(chunk.viteMetadata.importedAssets) : []
                };
            }
        }
    });

    return manifest;
}

module.exports = { adminPanel };
module.exports.default = adminPanel;
