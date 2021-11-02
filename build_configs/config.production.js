const {merge} = require('webpack-merge');
const path = require('path');
const {SRC} = require('./paths.js');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const FaviconsWebpackPlugin = require('favicons-webpack-plugin');

module.exports = merge(require('./config.base.js'), {
	mode: 'production',
	devtool: 'source-map',
	
	optimization: {
		minimizer: [
			`...`, //we dont want to override the default minimization
			new CssMinimizerPlugin(),
		],
	},
	plugins: [
		new FaviconsWebpackPlugin( {
			logo: path.resolve(SRC, 'frontend', 'imgs', 'favicon.svg'),
			cache: true,
			favicons: {
				icons: {
					android: false,
					appleIcon: false,
					appleStartup: false,
					coast: false,
					favicons: true,
					firefox: false,
					windows: false,
					yandex: false,
				}
			}
		}),
		new FaviconsWebpackPlugin( {
			logo: path.resolve(SRC, 'frontend', 'imgs', 'ic_round.svg'),
			cache: true,
			favicons: {
				icons: {
					android: true,
					appleIcon: true,
					appleStartup: false,
					coast: true,
					favicons: false,
					firefox: true,
					windows: true,
					yandex: true,
				}
			}
		}),
	]
})