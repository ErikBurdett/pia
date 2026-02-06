/**
 * External Dependencies
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const { getWebpackEntryPoints } = require( '@wordpress/scripts/utils/config' );

const isProduction = process.env.NODE_ENV === 'production';

if ( ! isProduction ) {
	defaultConfig.devServer.allowedHosts = 'all';
}

const basePath = path.resolve( __dirname, 'src' );

module.exports = {
	...defaultConfig,
	externals: {
		...defaultConfig.externals,
		window: 'window',
		jquery: 'jQuery',
		'@tracking/utils': 'advancedAds.tracking.utils',
	},
	entry: {
		...getWebpackEntryPoints(),
		// CSS
		admin: path.join( basePath, '/scss/admin/admin.js' ),
		settings: path.join( basePath, '/scss/settings.js' ),
		'filesystem-form': path.join( basePath, '/scss/filesystem-form.js' ),

		// JavaScript
		// Backend
		'wp-dashboard': path.join(
			basePath,
			'/js/admin/wp-dashboard/index.js'
		),
		'screen-ads-listing': path.join(
			basePath,
			'/js/admin/screen-ads-listing/index.js'
		),

		// Frontend
		tracking: path.join( basePath, '/js/frontend/tracking.js' ),
		'ga-tracking': path.join( basePath, '/js/frontend/ga-tracking.js' ),
		'public-stats': path.join( basePath, '/js/frontend/public-stats.js' ),
		delayed: path.join( basePath, '/js/frontend/delayed.js' ),
	},
	output: {
		filename: '[name].js', // Dynamically generate output file names
		path: path.resolve( __dirname, 'assets/dist' ),
	},
};
