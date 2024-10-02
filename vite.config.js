import path from 'path';
// Vite Plugins
import VuePlugin from '@vitejs/plugin-vue';
import EslintPlugin from 'vite-plugin-eslint';

export default ({ command }) => {
    return {
        build: {
            outDir: 'src/web/dist',
            emptyOutDir: true,
            manifest: false,
            sourcemap: false,
            rollupOptions: {
                input: {
                    'mux-field': 'src/web/src/js/mux-field.js',
                    'mux-domain-restrictions': 'src/web/src/js/mux-domain-restrictions.js',
                    'mux-signed-keys': 'src/web/src/js/mux-signed-keys.js',
                    'mux-dashboard': 'src/web/src/js/mux-dashboard.js',
                    'mux-cp': 'src/web/src/css/mux-cp.css',
                },
                output: {
                    entryFileNames: 'js/[name].js',
                    chunkFileNames: 'js/[name].js',
                    assetFileNames: 'css/[name].[ext]',
                },
            },
            minify: 'terser',
            terserOptions: {
                mangle: {
                    // Define variables to exclude from mangling
                    reserved: ['$'],
                },
            },
        },

        plugins: [

            // Keep JS looking good with eslint
            // https://github.com/gxmari007/vite-plugin-eslint
            // EslintPlugin({
            //     cache: false,
            //     fix: true,
            //     include: './src/web/src/**/*.{js,vue}',
            //     exclude: '',
            //     config: './.eslintrc.js',
            // }),

            // Vue 3 support
            // https://github.com/vitejs/vite/tree/main/packages/plugin-vue
            VuePlugin(),
        ],

        resolve: {
            alias: {
            // Vue 3 doesn't support the template compiler out of the box
                vue: 'vue/dist/vue.esm-bundler.js',
            },
        },

        // Add in any components to optimise them early.
        optimizeDeps: {
            include: [
                'vue',
            ],
        },
    };
};
