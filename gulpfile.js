"use strict";

// Requirements.
var gulp = require('gulp');

var autoprefixer = require('gulp-autoprefixer');
var imagemin = require('gulp-imagemin');
var cssnano = require('gulp-cssnano');
var rename = require('gulp-rename');
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var uglify = require('gulp-uglify');


// Options.
var options = {
    autoprefixer: [
        'last 2 versions',
        'ie >= 9'
    ],
    sass: {
        errLogToConsole: true,
        outputStyle: 'expanded'
    }
};

// CSS.
function scss() {
    gulp.src('assets/admin/scss/*.scss', {base: './'})
        .pipe(sourcemaps.init())
        .pipe(sass(options.sass).on('error', sass.logError))
        .pipe(autoprefixer(options.autoprefixer))
        .pipe(sourcemaps.write())
        .pipe(rename(function (path) {
            var temp = path.dirname.slice(0, -4);
            path.dirname = temp + 'css';
        }))
        .pipe(gulp.dest('.'))
        .pipe(cssnano())
        .pipe(rename({suffix: '.min'}))
        .pipe(gulp.dest('.'));

    return gulp.src('assets/ckeditor-bootstrap/scss/*.scss', {base: './'})
        .pipe(sourcemaps.init())
        .pipe(sass(options.sass).on('error', sass.logError))
        .pipe(autoprefixer(options.autoprefixer))
        .pipe(sourcemaps.write())
        .pipe(rename(function (path) {
            path.dirname = path.dirname.slice(0, -4);
        }))
        .pipe(cssnano())
        .pipe(gulp.dest('.'));
}

// JS.
function scripts() {
    return gulp.src(['assets/*/js/*.js', '!assets/*/js/*.min.js'], {base: './'})
        .pipe(uglify())
        .pipe(rename({suffix: '.min'}))
        .pipe(gulp.dest('.'));
}

// Images.
function images() {
    return gulp.src('assets/*/images/**', {base: './'})
        .pipe(imagemin({
            svgoPlugins: [
                {
                    removeViewBox: false
                }
            ]
        }))
        .pipe(gulp.dest('./'));
}

// Watcher.
function watch() {
    gulp.watch(['assets/*/scss/*.scss', 'assets/*/scss/**/_*.scss'], scss);
    gulp.watch(['assets/*/js/*.js', '!assets/*/js/*.min.js'], scripts);
}

// Tasks.
gulp.task('scss', scss);
gulp.task('images', images);
gulp.task('scripts', scripts);

gulp.task('build', gulp.parallel(scss, images, scripts));
gulp.task('default', gulp.series('build', watch));