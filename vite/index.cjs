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

            if (!existsSync(adminPagesFullPath)) {
                console.log('📁 Admin pages directory not found, skipping admin panel plugin');
                return {};
            }

            const components = detectAdminPageComponents(adminPagesFullPath);
            
            if (components.length === 0) {
                console.log('📄 No admin page components found, skipping admin panel plugin');
                return {};
            }

            console.log(`🎯 Admin Panel Plugin: Found ${components.length} components`);

            const entries = generateBuildEntries(components, adminPagesFullPath, config.adminPagesPath);

            const existingInput = userConfig.build?.rollupOptions?.input || {};
            const mergedInput = typeof existingInput === 'string' 
                ? { main: existingInput, ...entries }
                : { ...existingInput, ...entries };

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

            if (!existsSync(adminPagesFullPath)) return;

            const manifest = generateManifest(bundle, config.adminPagesPath);
            
            if (Object.keys(manifest['admin-pages']).length > 0) {
                const manifestFullPath = resolve(root, config.manifestPath);
                writeFileSync(manifestFullPath, JSON.stringify(manifest, null, 2));
                console.log(`📋 Admin Panel Manifest: Generated ${manifestFullPath}`);
                console.log(`📦 Components: ${Object.keys(manifest['admin-pages']).length}`);
            }
        },

        handleHotUpdate({ file, server }) {
            if (!config.hotReload) return;

            const root = viteConfig.root || process.cwd();
            const adminPagesFullPath = resolve(root, config.adminPagesPath);

            if (file.startsWith(adminPagesFullPath) && file.endsWith('.vue')) {
                console.log(`🔥 Hot reload: Admin page component updated - ${relative(root, file)}`);
                
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
        console.warn('⚠️  Admin Panel Plugin: Error detecting components:', error.message);
        return [];
    }
}

function generateBuildEntries(components, adminPagesFullPath, adminPagesRelativePath) {
    const entries = {};

    components.forEach(componentPath => {
        const relativePath = relative(adminPagesFullPath, componentPath);
        const entryName = `admin-pages/${relativePath.replace(/\.vue$/, '')}`;
        const viteInputPath = join(adminPagesRelativePath, relativePath).replace(/\\/g, '/');
        
        entries[entryName] = viteInputPath;
    });

    return entries;
}

function generateManifest(bundle, adminPagesPath) {
    const manifest = {
        'admin-pages': {}
    };

    Object.entries(bundle).forEach(([fileName, chunk]) => {
        if (chunk.isEntry && chunk.name && chunk.name.startsWith('admin-pages/')) {
            const componentName = chunk.name.replace('admin-pages/', '');
            
            manifest['admin-pages'][componentName] = {
                file: fileName,
                css: chunk.viteMetadata?.importedCss ? Array.from(chunk.viteMetadata.importedCss) : [],
                assets: chunk.viteMetadata?.importedAssets ? Array.from(chunk.viteMetadata.importedAssets) : []
            };
        }
    });

    return manifest;
}

module.exports = { adminPanel };
module.exports.default = adminPanel;
