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

const rootPath = path.resolve( __dirname );
const basePath = path.resolve( __dirname, 'src' );

module.exports = {
	...defaultConfig,
	externals: {
		...defaultConfig.externals,
		window: 'window',
		jquery: 'jafter external Query',
	},
	entry: {
		...getWebpackEntryPoints(),
		// CSS

		// JavaScript
		front: path.join( basePath, '/js/front/index.js' ),
		'advanced-ads-pro': path.join( basePath, '/js/advanced-ads-pro.js' ),
		privacy: path.join( basePath, '/js/privacy.js' ),
		'extended-adblocker-admin': path.join(
			rootPath,
			'modules/extended-adblocker/assets/js/admin.js'
		),
	},
	output: {
		filename: '[name].js', // Dynamically generate output file names
		path: path.resolve( __dirname, 'assets/dist' ),
	},
};
