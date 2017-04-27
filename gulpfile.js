// Dependencies
var gulp         = require('gulp');
var concat       = require('gulp-concat');
var rename       = require('gulp-rename');
var uglify       = require('gulp-uglify');
var saveLicense  = require('uglify-save-license');

// Path Configs
var config = {
  npmPath:  './node_modules'
}
var uglifyOptions = {
  output: {
    comments: saveLicense
  }
}

// concat scripts
gulp.task( 'dev-js', function( ) {
  return gulp
    .src( [
      'js/bcswiftype-custom.js'
    ] )
    .pipe( concat( 'bcswiftype.js' ) )
    .pipe( gulp.dest( './js' ) );
});

gulp.task( 'dev-css', function( ) {
  return gulp
    .src( [
      //config.npmPath + '/swiftype-autocomplete-jquery/autocomplete.css',
      //'js/bcswiftype-custom.css'
    ] )
    .pipe( concat( 'bcswiftype.css' ) )
    .pipe( gulp.dest( './css' ) );
});

// concat and uglify scripts
gulp.task( 'default', function( ) {
  return gulp
    .src( [
      //config.npmPath + '/hashchange/jquery.ba-hashchange.js',
      //config.npmPath + '/swiftype-search-jquery/jquery.swiftype.search.js',
      //config.npmPath + '/swiftype-autocomplete-jquery/jquery.swiftype.autocomplete.js',
      //'js/bcswiftype-custom.js'
    ] )
    .pipe( concat( 'bcswiftype.js' ) )
    .pipe( uglify( uglifyOptions ) )
    .pipe( gulp.dest( './js' ) );
});

// Watch function (sass) - dev use only
gulp.task('watch',function() {
  gulp
    .watch(['js/*.js', 'css/*.css'], ['dev-js', 'dev-css']);
});