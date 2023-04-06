import * as esbuild from 'esbuild'
import autoprefixer from "autoprefixer";
import mQPacker from '@hail2u/css-mqpacker';
import postcss from "postcss";
import {sassPlugin} from 'esbuild-sass-plugin'
import glob from 'glob';

// Use --watch flag to watch for changes and rebuild automatically.
const isWatch = process.argv.slice(2).includes('--watch');

// The output directory for JavaScript files.
// const jsDir = 'web/js/';

let cssStartTime;
// let jsStartTime;

// const jsWatchPlugin = {
//     name: 'watch-plugin',
//     setup(build) {
//         build.onStart(() => {
//             jsStartTime = Date.now();
//         });
//
//         build.onEnd((result) => {
//             if (result.errors.length) {
//                 console.log(result.errors);
//             }
//
//             console.log(`Bundled scripts with esbuild (${esbuild.version}) in ${Date.now() - jsStartTime}ms`);
//         });
//     }
// };
//
// // The JavaScript build context.
// let jsContext = await esbuild.context({
//     bundle: true,
//     entryPoints: ['assets/js/site.js'],
//     external: ['/js/vendor/*'],
//     format: 'esm',
//     minify: true,
//     outdir: jsDir,
//     plugins: [jsWatchPlugin],
//     sourcemap: true,
// });

// The CSS watch plugin is only used to log the time it takes to compile the CSS and print out errors.
const cssWatchPlugin = {
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


// The CSS build context.
let cssContext = await esbuild.context({
    entryPoints: await glob('assets/**/scss/*.scss'),
    minify: true,
    outdir: './',
    plugins: [cssWatchPlugin, sassPlugin({
        async transform(source) {
            const {css} = await postcss([
                autoprefixer,
                mQPacker({sort: true})
            ]).process(source, {from: undefined});

            return css;
        }
    })],
    sourcemap: true,
})

if (isWatch) {
    // await jsContext.watch();
    await cssContext.watch();
} else {
    // Run both js and css builds once.
    // await jsContext.rebuild();
    await cssContext.rebuild();
    // await jsContext.dispose();
    await cssContext.dispose();
}