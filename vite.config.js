import { defineConfig } from 'vite';
import { resolve } from 'node:path';

export default defineConfig({
    build: {
        outDir: 'resources/dist',
        emptyOutDir: true,
        cssCodeSplit: false,
        lib: {
            entry: resolve(__dirname, 'resources/js/index.js'),
            name: 'Loyalty',
            formats: ['umd', 'es'],
            fileName: (format) => (format === 'es' ? 'loyalty.mjs' : 'loyalty.js'),
        },
        rollupOptions: {
            output: {
                assetFileNames: 'loyalty.[ext]',
            },
        },
    },
    test: {
        environment: 'jsdom',
        include: ['tests/js/**/*.test.js'],
    },
});
