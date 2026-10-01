import {defineConfig} from 'vitest/config';

export default defineConfig({
    resolve: {
        // The package's `main` is a script that assigns `window.htmx` and exports nothing; esbuild reads `module`.
        alias: [{find: /^htmx\.org$/, replacement: 'htmx.org/dist/htmx.esm.js'}],
    },
    test: {
        environment: 'happy-dom',
        include: ['resources/assets/tests/**/*.test.ts'],
    },
});
