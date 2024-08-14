let mix = require('laravel-mix');

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

mix.js('resources/assets/js/app.js', 'public/js').vue()
   .sass('resources/assets/sass/app.scss', 'public/css');

mix.js('resources/assets/js/admin_chart.js', 'public/vue').vue()

mix.js('resources/assets/js/admin_map.js', 'public/vue').vue()

mix.js('resources/assets/js/layer_map_marker.js', 'public/vue').vue()
