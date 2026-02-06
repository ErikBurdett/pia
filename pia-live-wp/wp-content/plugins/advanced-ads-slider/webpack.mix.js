/* eslint-disable import/no-extraneous-dependencies */
// webpack.mix.js

const mix = require('laravel-mix');
const { join } = require('path');
const packageData = require('./package.json');
require('./tools/laravel-mix/wp-pot');

// Local config.
let localConfig = {};

try {
	localConfig = require('./webpack.mix.local');
} catch {}

// Webpack Config.
mix.webpackConfig({
	externals: {
		jquery: 'jQuery',
		lodash: 'lodash',
		moment: 'moment',
	},
});

// Aliasing Paths.
mix.alias({
	'@root': join(__dirname, 'assets/src'),
});

// Browsersync
if (undefined !== localConfig.wpUrl && '' !== localConfig.wpUrl) {
	mix.browserSync({
		proxy: localConfig.wpUrl,
		ghostMode: false,
		notify: false,
		ui: false,
		open: true,
		online: false,
		files: ['assets/css/*.min.css', 'assets/js/*.js', '**/*.php'],
	});
}

/**
 * CSS Files
 */

/**
 * JavaScript Files
 */
mix.js('assets/src/admin.js', 'assets/js/admin.js');

/**
 * WordPress translation
 */
if (process.argv.includes('wpPot')) {
	mix.wpPot({
		output: packageData.wpPot.output,
		file: packageData.wpPot.file,
		skipJS: true,
		domain: packageData.wpPot.domain,
	});
}
