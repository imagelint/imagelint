const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.combine([
    'resources/assets/css/landingpage/app_reset.css',
    'resources/assets/css/landingpage/page_main.css'
], 'public/css/landingpage.css');
mix.combine([
    'resources/assets/js/landingpage/app_header.js',
], 'public/js/landingpage.js')
    .browserSync('imagelint.test')
    .disableNotifications();
