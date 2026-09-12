import * as esbuild from 'esbuild';
import autoprefixer from 'autoprefixer';
import postcss from 'postcss';
import {sassPlugin} from 'esbuild-sass-plugin';
import {join} from 'node:path';
import process from 'node:process';
import {fileURLToPath} from 'node:url';

const skeletonDir = fileURLToPath(new URL('.', import.meta.url));

// Sass resolves an unqualified `@use` against these, so `@use "shared/breakpoints" as *` works from every bundle.
// Passing them at all replaces the plugin's own node_modules lookup, hence the two module directories.
const loadPaths = [...new Set([
    join(skeletonDir, 'resources/assets/src/css'),
    join(skeletonDir, 'node_modules'),
    join(process.cwd(), 'node_modules'),
])];

const isWatch = process.argv.slice(2).includes('--watch');

const logger = (type) => {
    let startTime;

    return {
        name: 'logger',
        setup(build) {
            build.onStart(() => void (startTime = Date.now()));

            build.onEnd((result) => result.errors.length
                ? console.error(result.errors)
                : console.info(`Compiled ${type} with esbuild (${esbuild.version}) in ${Date.now() - startTime}ms`));
        },
    };
};

const sass = () => sassPlugin({
    loadPaths,
    async transform(source) {
        const {css} = await postcss([autoprefixer]).process(source, {from: undefined});
        return css;
    },
});

const run = async (type, options) => {
    const context = await esbuild.context({
        ...options,
        plugins: [logger(type), ...(options.plugins ?? [])],
    });

    if (isWatch) {
        return context.watch();
    }

    await context.rebuild();
    await context.dispose();
};

export const buildScripts = (options = {}) => run('scripts', {
    bundle: true,
    entryPoints: ['resources/assets/src/js/*.ts'],
    format: 'esm',
    minify: true,
    outdir: 'resources/assets/dist/js',
    sourcemap: true,
    splitting: true,
    target: 'esnext',
    ...options,
});

export const buildStyles = (options = {}) => run('styles', {
    entryPoints: ['resources/assets/src/css/*.scss'],
    minify: true,
    outdir: 'resources/assets/dist/css',
    sourcemap: true,
    ...options,
    plugins: [sass(), ...(options.plugins ?? [])],
});
