import {defineConfig} from 'vitest/config';

export default defineConfig({
    test: {
        environment: 'happy-dom',
        include: ['resources/assets/tests/**/*.test.ts'],
    },
});
