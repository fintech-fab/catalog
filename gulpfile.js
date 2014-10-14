//*********** IMPORTS *****************
var gulp = require('gulp');
var sass = require('gulp-ruby-sass');
var gutil = require('gulp-util');
var rename = require("gulp-rename");
var map = require("map-stream");
var livereload = require("gulp-livereload");
var concat = require("gulp-concat");
var uglify = require('gulp-uglify');
var watch = require('gulp-watch');
var minifycss = require('gulp-minify-css');
var eol = require('gulp-eol');

var sourcePath = './static/';
var destPath = './public/';
var prodPath = './../../../public/packages/fintech-fab/catalog/';

console.log(sourcePath);
console.log(destPath);

gulp.task('app.css', function () {

	var cssFiles = [
		sourcePath + 'bower_components/bootswatch-dist/css/bootstrap.css',
		//sourcePath + 'bower_components/components-font-awesome/css/font-awesome.css',
		sourcePath + 'bower_components/angular-ui-tree/dist/angular-ui-tree.min.css',
		sourcePath + 'bower_components/angular-busy/dist/angular-busy.css',
		sourcePath + 'bower_components/ng-tags-input/ng-tags-input.css',
		sourcePath + 'main.css'
	];

	gulp.src(cssFiles)
		.pipe(minifycss())
		.pipe(concat('app.css'))
		.pipe(rename({suffix: '.min'}))
		.pipe(eol("\r\n"))
		.pipe(gulp.dest(destPath + 'css'))
		.pipe(gulp.dest(prodPath + 'css'));

	console.log('css compiled');

	gulp.src(sourcePath + "bower_components/bootswatch-dist/fonts/**.*")
		.pipe(eol("\r\n"))
		.pipe(gulp.dest(destPath + 'fonts'))
		.pipe(gulp.dest(prodPath + 'fonts'));
	gulp.src(sourcePath + "bower_components/components-font-awesome/fonts/**.*")
		.pipe(eol("\r\n"))
		.pipe(gulp.dest(destPath + 'fonts'))
		.pipe(gulp.dest(prodPath + 'fonts'));


});


gulp.task('app.js', function () {

	var srcFiles = [
		sourcePath + 'bower_components/angular/angular.js',
		sourcePath + 'bower_components/angular-ui-tree/dist/angular-ui-tree.js',
		sourcePath + 'bower_components/angular-strap/dist/angular-strap.js',
		sourcePath + 'bower_components/angular-strap/dist/angular-strap.tpl.js',
		sourcePath + 'bower_components/angular-busy/dist/angular-busy.js',
		sourcePath + 'bower_components/ng-tags-input/ng-tags-input.js',
		sourcePath + 'bower_components/jquery/dist/jquery.js',
		sourcePath + 'bower_components/bootstrap/dist/js/bootstrap.js',
		sourcePath + 'bower_components/angular-local-storage/dist/angular-local-storage.js'
	];

	var appFiles = [
		sourcePath + 'js/main.js',
		sourcePath + 'js/helpers/*.js',
		sourcePath + 'js/services/*.js',
		sourcePath + 'js/controllers/*.js'
	];

	gulp.src(srcFiles)
		.pipe(uglify())
		.pipe(concat('src.js'))
		.pipe(rename({suffix: '.min'}))
		.pipe(eol("\r\n"))
		.pipe(gulp.dest(destPath + 'js'))
		.pipe(gulp.dest(prodPath + 'js'));

	gulp.src(appFiles)
		.pipe(concat('app.js'))
		.pipe(eol("\r\n"))
		.pipe(gulp.dest(destPath + 'js'))
		.pipe(gulp.dest(prodPath + 'js'));

	console.log('js compiled');

});


var tasks = [
	'app.js',
	'app.css'
];

gulp.task('build-all', tasks);

gulp.task('watch', function () {
	var files = [
		sourcePath + 'js/*.js',
		sourcePath + 'js/controllers/*.js',
		sourcePath + 'js/helpers/*.js',
		sourcePath + 'js/services/*.js',
		sourcePath + 'main.css'
	];
	var watcher = gulp.watch(files, ['build-all']);
	watcher.on('change', function (event) {
		console.log('File ' + event.path + ' was ' + event.type + ' running tasks...');
	});
});


gulp.task('default', function () {
});
