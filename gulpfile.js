var gulp = require('gulp');


// Import Gulp plugins.
var babel = require('gulp-babel');
var plumber = require('gulp-plumber');


var NAME = "blicon-sf";
var PATHS = {
	"app" : {
		"css" : "./src/**/*.css",
		"js" : "./src/**/*.js",
		"json" : "./src/**/*.json",
		"png" : "./src/**/*.png",
		"html" : "./src/**/*.html",
		"php" : "./src/**/*.php",
		"work" : "./dist",
	}
};



/** PLUGIN **/


gulp.task('app-css', function () {
	return (
		gulp.src(PATHS.app.css)
			/*.pipe(postcss([
				cssnext({warnForDuplicates: false}),
				csshex(),
			]))*/
			.pipe(gulp.dest(PATHS.app.work))
	);
});

gulp.task('app-js', function () {
	return (
		gulp.src(PATHS.app.js)
		.pipe(plumber())
	    .pipe(babel({
	      presets: [
	        ['@babel/env', {
	          modules: false
	        }]
	      ]
	    }))
		.pipe(gulp.dest(PATHS.app.work))
	);
});

gulp.task('app-json', function () {
	return (
		gulp.src(PATHS.app.json)
			.pipe(gulp.dest(PATHS.app.work))
	);
});

gulp.task('app-png', function () {
	return (
		gulp.src(PATHS.app.png)
			.pipe(gulp.dest(PATHS.app.work))
	);
});

gulp.task('app-html', function () {
	return (
		gulp.src(PATHS.app.html)
			.pipe(gulp.dest(PATHS.app.work))
	);
});

gulp.task('app-php', function () {
	return (
		gulp.src(PATHS.app.php)
			.pipe(gulp.dest(PATHS.app.work))
	);
});

// gulp.task('watch.app', function () {
// 	gulp.watch(PATHS.app.css, ['app-style-css']);
// 	gulp.watch(PATHS.app.js, ['app-js']);
// 	gulp.watch(PATHS.app.json, ['app-json']);
// 	gulp.watch(PATHS.app.html, ['app-html']);
// 	gulp.watch(PATHS.app.php, ['app-php']);
// });


gulp.task('build', () => {
	return gulp.src(PATHS.app.js)
	   .pipe(babel())
	.pipe(gulp.dest('./dist'))
 });