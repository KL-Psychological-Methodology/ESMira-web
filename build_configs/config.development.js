const {merge} = require('webpack-merge');
const path = require('path');
const {SRC} = require('./paths.js');
const FaviconsWebpackPlugin = require('favicons-webpack-plugin');

module.exports = merge(require('./config.base.js'), {
	mode: 'development',
	devtool: 'inline-source-map',
	watch: true,
	stats: {
		errorDetails: true
	},
	output: {
		filename: '[name].js',
		//when watch:true then images embedded in html (coming from HtmlWebpackPlugin) will be deleted after a file change (in watch mode)
		//but we inline images anyway, so we dont need this workaround:
		// clean: {
		// 	keep: /.*\.(png|svg)/
		// }
	},
	plugins: [
		new FaviconsWebpackPlugin( {
			logo: path.resolve(SRC, 'frontend', 'imgs', 'faviconDev.svg'),
			cache: true,
			favicons: {
				icons: {
					android: false,
					appleIcon: false,
					appleStartup: false,
					firefox: false,
					windows: false,
					yandex: false,
				}
			}
		}),
	]
})