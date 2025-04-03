const gulp = require('gulp');
const vinylFtp = require('vinyl-ftp');
const gutil = require('gulp-util');
const watch = require('gulp-watch');
const dotenv = require('dotenv');
const browserify = require('browserify');
const babelify = require('babelify');
const source = require('vinyl-source-stream');
const buffer = require('vinyl-buffer');
const terser = require('gulp-terser');
const rename = require('gulp-rename');
const sourcemaps = require('gulp-sourcemaps');
const sass = require('gulp-sass')(require('sass'));

// Load environment variables from .env file
dotenv.config();

// FTP connection details from environment variables
const ftpConfig = {
  host: process.env.FTP_HOST,
  user: process.env.FTP_USER,
  password: process.env.FTP_PASSWORD,
  parallel: process.env.FTP_PARALLEL,
  log: gutil.log
};

// Source files to watch and deploy
const globs = [
  '**/*',
  '!js/**',
  '!scss/**',
  '!.env',
  '!node_modules/**',
  '!node_modules',
  '!gulpfile.js',
  '!package.json',
  '!package-lock.json',
  '!pnpm-lock.yaml',
  '!README.md',
  '!yarn.lock',
  '!error.log',
  '!okzea-chatbot.zip',
  '!temp-plugin-dir/**'
];

// Create a vinyl-ftp connection
function getFtpConnection() {
  return vinylFtp.create(ftpConfig);
}

// Gulp task to deploy files
gulp.task('deploy', function () {
  const conn = getFtpConnection();

  return gulp.src(globs, { base: '.', buffer: false })
    .pipe(conn.newer(process.env.FTP_PATH)) // only upload newer files
    .pipe(conn.dest(process.env.FTP_PATH));
});

// JavaScript build task for main chatbot script
function buildChatbotJS() {
  return browserify({
    entries: './js/okzea-chatbot.js',
    debug: true,
    transform: [babelify.configure({
      presets: ['@babel/preset-env']
    })]
  })
    .bundle()
    .pipe(source('okzea-chatbot.js'))
    .pipe(buffer())
    .pipe(sourcemaps.init({ loadMaps: true }))
    .pipe(terser())
    .pipe(rename({ suffix: '.min' }))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('./dist/js/'));
}

// JavaScript build task for contact modal
function buildContactModalJS() {
  return browserify({
    entries: './js/okzea-contact-modal.js',
    debug: true,
    transform: [babelify.configure({
      presets: ['@babel/preset-env']
    })]
  })
    .bundle()
    .pipe(source('okzea-contact-modal.js'))
    .pipe(buffer())
    .pipe(sourcemaps.init({ loadMaps: true }))
    .pipe(terser())
    .pipe(rename({ suffix: '.min' }))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('./dist/js/'));
}

// Build all JS files
async function buildJS() {
  return new Promise((resolve) => {
    gulp.parallel(buildChatbotJS, buildContactModalJS)(resolve);
  });
}

// Watch task for development
function watchJS() {
  gulp.watch('./js/**/*.js', buildJS);
}

// SCSS build task
async function buildSCSS() {
  const autoprefixer = (await import('gulp-autoprefixer')).default;
  const cleanCSS = (await import('gulp-clean-css')).default;

  return gulp.src('./scss/**/*.scss')
    .pipe(sourcemaps.init())
    .pipe(sass().on('error', sass.logError))
    .pipe(autoprefixer())
    .pipe(cleanCSS())
    .pipe(rename({ suffix: '.min' }))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('./dist/css/'));
}

// Watch task for SCSS
function watchSCSS() {
  gulp.watch('./scss/**/*.scss', buildSCSS);
}

// Define tasks
gulp.task('build', gulp.series(buildJS, buildSCSS));
gulp.task('watch', gulp.series(
  gulp.parallel(buildJS, buildSCSS),
  gulp.parallel(watchJS, watchSCSS),
));

// Default task
gulp.task('default', gulp.series('build'));
