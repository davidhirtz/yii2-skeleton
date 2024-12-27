import * as esbuild from 'esbuild'
import autoprefixer from "autoprefixer";
import postcss from "postcss";
import {sassPlugin} from 'esbuild-sass-plugin'

// Use the "--watch" flag to watch for changes and rebuild automatically
const isWatch = process.argv.slice(2).includes('--watch');
let cssStartTime;

const watchPlugin = (type) => {
    return {
        name: 'watch-plugin',
        setup(build) {
            build.onStart(() => {
                cssStartTime = Date.now();
            });

            build.onEnd((result) => {
                if (result.errors.length) {
                    console.log(result.errors);
                }

                console.log(`Compiled ${type} with esbuild (${esbuild.version}) in ${Date.now() - cssStartTime}ms`);
            });
        },
    }
};

const scripts = await esbuild.context({
    entryPoints: ['src/assets/ts/*.ts'],
    bundle: true,
    format: 'esm',
    minify: true,
    outdir: 'src/assets/dist/js',
    plugins: [watchPlugin('scripts')],
    sourcemap: true,
    target: 'es6',
})

const styles = await esbuild.context({
    entryPoints: [
        {
            in: 'src/assets/admin/scss/admin.scss',
            out: 'src/assets/admin/css/admin.min'
        },
        // {
        //     in: 'src/assets/admin/scss/tinymce.scss',
        //     out: 'src/assets/admin/css/tinymce.min'
        // },
        // {
        //     in: 'src/assets/fontawesome/css/all.css',
        //     out: 'src/assets/fontawesome/css/all.min'
        // },
    ],
    minify: true,
    outdir: './',
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
})

if (isWatch) {
    await scripts.watch();
    await styles.watch();
} else {
    await scripts.rebuild();
    await scripts.dispose();
    await styles.rebuild();
    await styles.dispose();
}