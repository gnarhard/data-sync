let mix = require('laravel-mix');

/*
 * Sets the development path to assets. By default, this is the `/resources`
 * folder in the theme.
 */
const devPath  = 'views/assets';

mix.setPublicPath('views/dist');

/*
 * Set Laravel Mix options.
 *
 * @link https://laravel.com/docs/5.6/mix#postcss
 * @link https://laravel.com/docs/5.6/mix#url-processing
 */
// mix.options({
//   postCss: [
//     require( 'postcss-preset-env' ) ({
//       autoprefixer: { grid: true }
//     }) ],
//   processCssUrls: false
// });


const MomentLocalesPlugin = require('moment');

module.exports = {
    plugins: [
    // To strip all locales except “en”
    new MomentLocalesPlugin(),
    ],
};


/*
 * Builds sources maps for assets.
 *
 * @link https://laravel.com/docs/5.6/mix#css-source-maps
 */
mix.sourceMaps();


/*
 * Versioning and cache busting. Append a unique hash for production assets. If
 * you only want versioned assets in production, do a conditional check for
 * `mix.inProduction()`.
 *
 * @link https://laravel.com/docs/5.6/mix#versioning-and-cache-busting
 */
mix.version();

/*
 * Compile CSS. Mix supports Sass, Less, Stylus, and plain CSS, and has functions
 * for each of them.
 *
 * @link https://laravel.com/docs/5.6/mix#working-with-stylesheets
 * @link https://laravel.com/docs/5.6/mix#sass
 * @link https://github.com/sass/node-sass#options
 */

// Sass configuration.
let sassConfig = {
    outputStyle: 'expanded',
    indentType: 'tab',
    indentWidth: 1,
};

// mix.sass( `${devPath}/sass/data-sync.scss`, 'styles', sassConfig );
mix.sass(`${devPath}/sass/data-sync.scss`, 'styles');

/*
 * Compile JavaScript.
 *
 * @link https://laravel.com/docs/5.6/mix#working-with-scripts
 */

// CREATE
mix.js(`${devPath}/js/admin/admin-autoloader.es6.js`, 'js');