import * as esbuild from 'esbuild';
import autoprefixer from 'autoprefixer';
import postcss from 'postcss';
import {sassPlugin} from 'esbuild-sass-plugin';
import {execFile} from 'node:child_process';
import {existsSync} from 'node:fs';
import {join} from 'node:path';
import process from 'node:process';
import {fileURLToPath} from 'node:url';
import {promisify} from 'node:util';

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

// esbuild strips types without checking them, so a type error would otherwise reach the committed dist/ unnoticed.
// Answers the compiler's report, or null when the bundle has no tsconfig.json or type-checks cleanly.
const checkTypes = async () => {
    if (!existsSync(join(process.cwd(), 'tsconfig.json'))) {
        return null;
    }

    try {
        await promisify(execFile)(join(skeletonDir, 'node_modules/.bin/tsc'), ['--noEmit', '--pretty', 'false']);
        return null;
    } catch (error) {
        return error.stdout || error.message;
    }
};

// Watch mode reports a type error per rebuild and keeps emitting; errors returned from `onStart` would not stop
// esbuild writing the output anyway, which is why a build checks before esbuild runs at all.
const typecheck = () => ({
    name: 'typecheck',
    setup(build) {
        build.onStart(async () => {
            const report = await checkTypes();
            return report ? {warnings: [{text: `Type check failed:\n${report}`}]} : undefined;
        });
    },
});

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

export const buildScripts = async (options = {}) => {
    if (!isWatch) {
        const report = await checkTypes();

        if (report) {
            console.error(`Type check failed, nothing was written:\n${report}`);
            process.exit(1);
        }
    }

    return run('scripts', {
        bundle: true,
        entryPoints: ['resources/assets/src/js/*.ts'],
        format: 'esm',
        minify: true,
        outdir: 'resources/assets/dist/js',
        sourcemap: true,
        splitting: true,
        target: 'esnext',
        ...options,
        plugins: [...(isWatch ? [typecheck()] : []), ...(options.plugins ?? [])],
    });
};

export const buildStyles = (options = {}) => run('styles', {
    entryPoints: ['resources/assets/src/css/*.scss'],
    minify: true,
    outdir: 'resources/assets/dist/css',
    sourcemap: true,
    ...options,
    plugins: [sass(), ...(options.plugins ?? [])],
});
