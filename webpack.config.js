const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry,
		'bdfgf-admin': path.resolve( process.cwd(), 'src', 'index.js' ),
	},
};
