import {buildScripts, buildStyles} from './esbuild.config.js';

await buildScripts({
    entryPoints: [
        'resources/assets/src/js/components/*.ts',
        'resources/assets/src/js/*.ts',
    ],
});

await buildStyles({
    entryPoints: [
        'resources/assets/src/css/tinymce/*.scss',
        'resources/assets/src/css/*.scss',
    ],
});
