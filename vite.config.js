import path from 'path';
// Vite Plugins
import VuePlugin from '@vitejs/plugin-vue';
import EslintPlugin from 'vite-plugin-eslint';

// Rollup Plugins
import { nodeResolve } from '@rollup/plugin-node-resolve';


export default ({ command }) => ({
    build: {
        outDir: 'src/web/dist',
        emptyOutDir: true,
        manifest: false,
        sourcemap: true,
        rollupOptions: {
            input: {
                'mux-dashboard': 'src/web/src/js/mux-dashboard.js',
            },
            output: {
                entryFileNames: 'js/[name].js',
                chunkFileNames: 'js/[name].js',
                assetFileNames: 'css/[name].[ext]',
            },
        },
    },

    plugins: [

        // Keep JS looking good with eslint
        // https://github.com/gxmari007/vite-plugin-eslint
        EslintPlugin({
            cache: false,
            fix: true,
            include: './src/web/src/**/*.{js,vue}',
            exclude: '',
        }),

        // Vue 3 support
        // https://github.com/vitejs/vite/tree/main/packages/plugin-vue
        VuePlugin({
            isProduction: true,
        }),

        // Ensure Vite can find the modules it needs
        // https://github.com/rollup/plugins/tree/master/packages/node-resolve
        // nodeResolve({
        //     moduleDirectories: [
        //         path.resolve('./node_modules'),
        //     ],
        // }),
    ],

    resolve: {
        alias: {
            // Vue 3 doesn't support the template compiler out of the box
            'vue': 'vue/dist/vue.esm-bundler.js',
        },
    },

    // Add in any components to optimise them early.
    optimizeDeps: {
        include: [
            'vue',
        ],
    },
});