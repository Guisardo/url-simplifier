'use strict';

var gulp = require('gulp');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var uglifycss = require('gulp-uglifycss');
var sourcemaps = require('gulp-sourcemaps');
var runSequence = require('run-sequence');

gulp.task('js', function()  {
  return gulp.src([
    './bower_components/jquery/dist/jquery.js',
    './bower_components/bootstrap/dist/js/bootstrap.js',
    './bower_components/angular/angular.js',
    './bower_components/angular-notification/angular-notification.js',
    './bower_components/angular-translate/angular-translate.js',
    './bower_components/angular-translate-loader-static-files/angular-translate-loader-static-files.js'
    ])
    .pipe(concat('libs.min.js'))
    .pipe(sourcemaps.init({loadMaps: true}))
    .pipe(uglify())
    .pipe(sourcemaps.write(''))
    .pipe(gulp.dest('./admin/assets/js'));
});
gulp.task('jsapp', function()  {
  return gulp.src([
    './admin/assets/js/ui.js'
    ])
    .pipe(concat('app.min.js'))
    .pipe(sourcemaps.init())
    .pipe(uglify())
    .pipe(sourcemaps.write(''))
    .pipe(gulp.dest('./admin/assets/js'));
});
gulp.task('css', function() {
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
gulp.task('copyfonts', function() {
  gulp.src([
    './bower_components/components-font-awesome/fonts/*'
    ])
    .pipe(gulp.dest('./admin/assets/fonts'));
});

gulp.task('build', [], function() {
  runSequence(['js', 'css', 'copyfonts'], 'jsapp');
});

gulp.task('default', ['build'], function() {});
