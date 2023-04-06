import * as esbuild from 'esbuild'
import autoprefixer from "autoprefixer";
import postcss from "postcss";
import {sassPlugin} from 'esbuild-sass-plugin'

// Use --watch flag to watch for changes and rebuild automatically.
const isWatch = process.argv.slice(2).includes('--watch');
let cssStartTime;

const watchPlugin = {
    name: 'watch-plugin',
    setup(build) {
        build.onStart(() => {
            cssStartTime = Date.now();
        });

        build.onEnd((result) => {
            if (result.errors.length) {
                console.log(result.errors);
            }

            console.log(`Compiled styles with esbuild (${esbuild.version}) in ${Date.now() - cssStartTime}ms`);
        });
    }
};

let context = await esbuild.context({
    entryPoints: [
        {
            in: 'assets/admin/scss/admin.scss',
            out: 'assets/admin/css/admin.min'
        },
        {
            in: 'assets/admin/js/admin.js',
            out: 'assets/admin/js/admin.min'
        },
        {
            in: 'assets/ckeditor-bootstrap/scss/editor.scss',
            out: 'assets/ckeditor-bootstrap/editor'
        },
        {
            in: 'assets/ckeditor-bootstrap/scss/dialog.scss',
            out: 'assets/ckeditor-bootstrap/dialog'
        },
        {
            in: 'assets/ckeditor-extra/js/plugin.js',
            out: 'assets/ckeditor-extra/js/plugin.min'
        },{
            in: 'assets/fontawesome/css/all.scss',
            out: 'assets/fontawesome/css/all.min'
        },
        {
            in: 'assets/signup/js/signup.js',
            out: 'assets/signup/js/signup.min'
        },

    ],
    minify: true,
    outdir: './',
    plugins: [watchPlugin, sassPlugin({
        async transform(source) {
            const {css} = await postcss([autoprefixer,]).process(source, {from: undefined});
            return css;
        }
    })],
    sourcemap: true,
    target: 'es5',
})

if (isWatch) {
    await context.watch();
} else {
    await context.rebuild();
    await context.dispose();
}