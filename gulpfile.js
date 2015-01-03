var gulp = require('gulp');
var zip = require('gulp-zip');
var composer = require('gulp-composer');

gulp.task('build', function () {
    return gulp.src('src/*', {base: "./src"})
        .pipe(gulp.dest('build'))
        .pipe(composer({ cwd: './build' }));
});

gulp.task('zip', function () {
    return gulp.src(['build/*','!build/composer.*','build/**/*'], {base: "./build"})
        .pipe(zip('wp-c5-exporter.zip'))
        .pipe(gulp.dest('dist'));
});

gulp.task('default', ['build', 'zip']);
