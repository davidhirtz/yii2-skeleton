import * as esbuild from 'esbuild'
import autoprefixer from 'autoprefixer';
import postcss from 'postcss';
import {sassPlugin} from 'esbuild-sass-plugin'

const isWatch = process.argv.slice(2).includes('--watch');
const jsDir = 'resources/assets/dist/js/';
let startTime;

const watchPlugin = (type) => {
    return {
        name: 'watch-plugin',
        setup(build) {
            build.onStart(() => {
                startTime = Date.now();

                // if ('scripts' === type) {
                //     fs.readdirSync(jsDir, {withFileTypes: true})
                //         .filter(file => file.isFile())
                //         .map((file) => fs.unlinkSync(jsDir + file.name))
                // }
            });

            build.onEnd((result) => {
                if (result.errors.length) {
                    console.error(result.errors);
                }

                console.info(`Compiled ${type} with esbuild (${esbuild.version}) in ${Date.now() - startTime}ms`);
            });
        },
    }
};

const contexts = [
    await esbuild.context({
        entryPoints: [
            'resources/assets/src/js/components/*.ts',
            'resources/assets/src/js/*.ts',
        ],
        bundle: true,
        format: 'esm',
        minify: true,
        outdir: jsDir,
        plugins: [watchPlugin('scripts')],
        sourcemap: true,
        splitting: true,
        target: 'esnext',
    }),
    await esbuild.context({
        entryPoints: [
            'resources/assets/src/css/tinymce/*',
            'resources/assets/src/css/*',
        ],
        minify: true,
        outdir: 'resources/assets/dist/css',
        plugins: [
            watchPlugin('styles'),
            sassPlugin({
                async transform(source) {
                    const {css} = await postcss([autoprefixer]).process(source, {from: undefined});
                    return css;
                }
            })
        ],
        sourcemap: true,
    }),
]

contexts.forEach(async (context) => {
    if (isWatch) {
        await context.watch();
    } else {
        await context.rebuild();
        await context.dispose();
    }
});