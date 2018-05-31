let gulp = require('gulp');

// templates
let panini = require('panini');

// styles
let postcss = require('gulp-postcss');
let cssnext = require('postcss-cssnext');
let postcssImport = require('postcss-import');
let cssnano = require('gulp-cssnano');
let sourcemaps = require('gulp-sourcemaps');

// scripts
let uglify = require('gulp-uglify');
let jshint = require('gulp-jshint');

// utilities
let concat = require('gulp-concat');
let rename = require('gulp-rename');
let del = require("del");
let gulpSequence = require('gulp-sequence')

// paths
let paths = {
	templates: {
		src: 'templates/source',
		dist: './'
	},
	styles: {
		src: 'styles/source',
		dist: 'styles/build'
	},
	scripts: {
		src: 'scripts/source',
		dist: 'scripts/build'
	}
};

/* ------------------------------------------------------------ *\
    # Task: Handle static pages
\* ------------------------------------------------------------ */
gulp.task('build:html', function(callback) {
	gulpSequence('clean:html', 'pages:refresh', 'pages')(callback)
});

gulp.task('clean:html', function() {
	return del(paths.templates.dist + '/*.html');
});

gulp.task('pages', function() {
	return gulp.src(paths.templates.src + '/pages/**/*.html')
	
	.pipe(panini({
		root: paths.templates.src + '/pages/',
		layouts: paths.templates.src + '/layouts/',
		partials: paths.templates.src + '/partials/'
	}))
	.pipe(gulp.dest(paths.templates.dist));
});

gulp.task('pages:refresh', function() {
	panini.refresh();
});

/* ------------------------------------------------------------ *\
    # Task: Handle styles
\* ------------------------------------------------------------ */
gulp.task('build:css:dev', function(done) {
    let plugins = [
		postcssImport,
		cssnext
	];

	return gulp.src(paths.styles.src + '/style.css')

	.pipe(sourcemaps.init())
	.pipe(postcss(plugins))
	.on('error', done)
	.pipe(rename('build.css'))
	.pipe(sourcemaps.write('.'))
	.pipe(gulp.dest(paths.styles.dist));
});

gulp.task('build:css:prod', function(done) {
    let plugins = [
		postcssImport,
		cssnext
	];

	return gulp.src(paths.styles.src + '/style.css')

	.pipe(sourcemaps.init())
	.pipe(postcss(plugins))
	.on('error', done)
	.pipe(rename('build.css'))
	.pipe(cssnano())
	.pipe(sourcemaps.write('.'))
	.pipe(gulp.dest(paths.styles.dist));
});

/* ------------------------------------------------------------ *\
    # Task: Combine and compress scripts
\* ------------------------------------------------------------ */
gulp.task('build:js:dev', function() {
	return gulp.src([paths.scripts.src + '/plugins/*.js', paths.scripts.src + '/main.js'])

	.pipe(concat('build.js'))
	.pipe(gulp.dest(paths.scripts.dist));
});

gulp.task('build:js:prod', function() {
	return gulp.src([paths.scripts.src + '/plugins/*.js', paths.scripts.src + '/main.js'])

	.pipe(concat('build.js'))
	.pipe(uglify({compress: {hoist_funs: false, hoist_vars: false}}))
	.pipe(gulp.dest(paths.scripts.dist));
});

/* ------------------------------------------------------------ *\
    # Task: Check main js for errors and optimizations
\* ------------------------------------------------------------ */
gulp.task('validate:js', function() {
	return gulp.src(paths.scripts.src + '/main.js')

	.pipe(jshint())
	.pipe(jshint.reporter('jshint-stylish', { beep: true }));
});

/* ------------------------------------------------------------ *\
    # Task: Watch files for changes
\* ------------------------------------------------------------ */
gulp.task('watch', function() {
	gulp.watch(paths.templates.src + '/**/*', ['build:html']);
	gulp.watch(paths.styles.src + '/**/*.css', ['build:css:dev']);
	gulp.watch(paths.scripts.src + '/**/*.js', ['validate:js', 'build:js:dev']);
});

/* ------------------------------------------------------------ *\
    # Task: Default
\* ------------------------------------------------------------ */
gulp.task('default', ['build:html', 'build:css:prod', 'validate:js', 'build:js:prod']);