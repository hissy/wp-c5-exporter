var gulp = require('gulp');
var zip = require('gulp-zip');
var composer = require('gulp-composer');
var minimist = require('minimist');

var knownOptions = {
  string: 'path',
  default: { path: 'dist' }
};

var options = minimist(process.argv.slice(2), knownOptions);

gulp.task('build', function () {
    return gulp.src('src/*', {base: "./src"})
        .pipe(gulp.dest('build'))
        .pipe(composer({ cwd: './build' }));
});

gulp.task('zip', function () {
    return gulp.src(['build/*','!build/composer.*','build/**/*'], {base: "./build"})
        .pipe(zip('wp-c5-exporter.zip'))
        .pipe(gulp.dest(options.path));
});

gulp.task('mv', function () {
    return gulp.src(['build/*','!build/composer.*','build/**/*'], {base: "./build"})
        .pipe(gulp.dest(options.path));
});

gulp.task('default', ['build', 'zip']);
