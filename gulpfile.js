// Requirements.
var gulp=require('gulp');

var autoprefixer=require('gulp-autoprefixer');
var imagemin=require('gulp-imagemin');
var cssnano=require('gulp-cssnano');
var rename=require('gulp-rename');
var sass=require('gulp-sass');
var sourcemaps=require('gulp-sourcemaps');
var uglify=require('gulp-uglify');


// Options.
var options={
	autoprefixer:[
		'last 2 versions',
		'ie >= 9'
	],
	sass:{
		errLogToConsole:true,
		outputStyle:'expanded',
		includePaths:[
			'vendor/davidhirtz/assets/site/scss/'
		]
	}
};

// Console.
function getPathFromConsole()
{
	var minimist=require('minimist');

	/** @var {Object} */
	var options=minimist(process.argv.slice(2));
	var index=options.output.lastIndexOf('/');

	return {
		src:options.src,
		dir:options.output.substr(0, index),
		file:options.output.substr(index+1)
	};
}

// Combine and minify CSS.
// Used by command-line via "yii asset"
gulp.task('combine-css', function()
{
	var path=getPathFromConsole();

	return gulp.src(path.src)
		.pipe(cssnano())
		.pipe(rename(path.file))
		.pipe(gulp.dest(path.dir))
});

// Combine and minify Javascripts.
// Used by command-line via "yii asset"
gulp.task('combine-js', function()
{
	var path=getPathFromConsole();

	return gulp.src(path.src)
		.pipe(uglify())
		.pipe(rename(path.file))
		.pipe(gulp.dest(path.dir))
});

// CSS.
function scss()
{
	// noinspection JSUnresolvedFunction
	return gulp.src('assets/site/scss/site.scss', {base:'./'})
		.pipe(sourcemaps.init())
		.pipe(sass(options.sass).on('error', sass.logError))
		.pipe(autoprefixer(options.autoprefixer))
		.pipe(sourcemaps.write())
		.pipe(rename(function(path)
		{
			var temp=path.dirname.slice(0, -4);
			path.dirname=temp+'css';

		}))
		.pipe(gulp.dest('.'))
		.pipe(cssnano())
		.pipe(rename({suffix:'.min'}))
		.pipe(gulp.dest('.'));
}

// JS.
function scripts()
{
	// noinspection JSUnresolvedFunction
	return gulp.src('assets/site/js/*.js', {base:'./'})
		.pipe(uglify())
		.pipe(rename({suffix:'.min'}))
		.pipe(gulp.dest('.'))
}

// Images.
function images()
{
	return gulp.src('web/images/**', {base:'./'})
		.pipe(imagemin({
			svgoPlugins:[
				{
					removeViewBox:false
				}
			]
		}))
		.pipe(gulp.dest('./'));
}

// Watcher.
function watch()
{
	gulp.watch('assets/site/scss/**/_*.scss', scss);
	gulp.watch('assets/site/scss/*.scss', scss);
	gulp.watch('assets/site/js/*.js', scripts);
}

// Tasks.
gulp.task('scss', scss);
gulp.task('images', images);
gulp.task('scripts', scripts);

gulp.task('build', gulp.parallel(scss, images, scripts));
gulp.task('default', gulp.series('build', watch));