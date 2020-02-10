var gulp = require('gulp');
var zip = require('gulp-zip');

function defaultTask(cb) {
  // place code for your default task here
  cb();
}

gulp.task('zip', function () {
    return gulp.src(['./**',
      '!node_modules{,/**}',
      '!dist{,/**}',
      '!package-lock.json',
      '!package.json',
      '!gulpfile.js'])
        .pipe(zip('blog-subscriptions.zip'))
        .pipe(gulp.dest('./dist'));
});

exports.default = defaultTask;
