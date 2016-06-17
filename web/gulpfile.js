'use strict';

var gulp = require('gulp');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var uglifycss = require('gulp-uglifycss');
var sourcemaps = require('gulp-sourcemaps');
var runSequence = require('run-sequence');

gulp.task('lib.js', function()  {
  return gulp.src([
    './bower_components/jquery/dist/jquery.js',
    './bower_components/bootstrap/dist/js/bootstrap.js',
    './bower_components/angular/angular.js',
    './bower_components/angular-notification/angular-notification.js',
    './bower_components/angular-translate/angular-translate.js',
    './bower_components/angular-translate-loader-static-files/angular-translate-loader-static-files.js',
    './bower_components/ngstorage/ngStorage.js'
    ])
    .pipe(concat('libs.min.js'))
    .pipe(sourcemaps.init({loadMaps: true}))
    .pipe(uglify())
    .pipe(sourcemaps.write(''))
    .pipe(gulp.dest('./admin/assets/js'));
});
gulp.task('app.js', function()  {
  return gulp.src([
    './admin/assets/js/ui.js'
    ])
    .pipe(concat('app.min.js'))
    .pipe(sourcemaps.init())
    .pipe(uglify())
    .pipe(sourcemaps.write(''))
    .pipe(gulp.dest('./admin/assets/js'));
});
gulp.task('lib.css', function() {
  gulp.src([
    './bower_components/bootstrap/dist/css/bootstrap.css',
    './bower_components/components-font-awesome/css/font-awesome.css'
    ])
    .pipe(concat('libs.min.css'))
    .pipe(uglifycss({
      'maxLineLen': 80,
      'uglyComments': true
    }))
    .pipe(gulp.dest('./admin/assets/css'));
});
gulp.task('lib.fonts', function() {
  gulp.src([
    './bower_components/components-font-awesome/fonts/*'
    ])
    .pipe(gulp.dest('./admin/assets/fonts'));
});

gulp.task('build', [], function() {
  runSequence(['lib.js', 'lib.css', 'lib.fonts'], 'app.js');
});

gulp.task('build.lib', [], function() {
  runSequence('lib.js', 'lib.css', 'lib.fonts');
});

gulp.task('build.app', [], function() {
  runSequence('app.js');
});

gulp.task('default', ['build'], function() {});
