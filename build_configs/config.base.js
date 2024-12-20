const path = require('path');
const {SRC, DIST} = require('./paths.js');
const {DefinePlugin} = require('webpack');
const JSONMinifyPlugin = require('node-json-minify');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const HtmlWebpackSkipAssetsPlugin = require('html-webpack-skip-assets-plugin').HtmlWebpackSkipAssetsPlugin;
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const svgToMiniDataURI = require('mini-svg-data-uri');
const generateFile = require('generate-file-webpack-plugin');

const packageVersion = require(path.resolve(__dirname, '..', 'package.json')).version

module.exports = {
	entry: {
		main: [
			path.resolve(SRC, 'frontend', 'css', 'style.css'),
			path.resolve(SRC, 'frontend', 'css', 'site.css'),
			path.resolve(SRC, 'frontend', 'css', 'animations.css'),
			path.resolve(SRC, 'frontend', 'css', 'btnWidgets.css'),
			path.resolve(SRC, 'frontend', 'css', 'dash.css'),
			path.resolve(SRC, 'frontend', 'css', 'navigationRow.css'),
			path.resolve(SRC, 'frontend', 'css', 'inputDesign.css'),
			path.resolve(SRC, 'frontend', 'css', 'widgets.css'),
			path.resolve(SRC, 'frontend', 'ts', 'index.ts'),
		],
		nojs: [
			path.resolve(SRC, 'frontend', 'css', 'style.css'),
			path.resolve(SRC, 'frontend', 'css', 'site.css'),
			path.resolve(SRC, 'frontend', 'css', 'animations.css'),
			path.resolve(SRC, 'frontend', 'css', 'nojs.css'),
			path.resolve(SRC, 'frontend', 'css', 'inputDesign.css'),
			path.resolve(SRC, 'frontend', 'ts', 'index_nojs.ts')
		]
	},
	resolve: {
		extensions: [".ts", ".tsx", ".js"],
		// modules: ['src', 'node_modules'],
	},
	output: {
		path: path.resolve(DIST, 'frontend'),
		
		filename: '[name].[contenthash].js',
		assetModuleFilename: 'assets/[name].[contenthash].js',
		
		library: {
			name: 'ESMira',
			type: 'var'
		},
		clean: true,
	},
	module: {
		rules: [
			{
				test: /\.tsx?$/,
				exclude: [/node_modules/],
				loader: 'ts-loader'
			},
			{
				test: /\.(html|php)$/,
				loader: "html-loader"
			},
			{
				test: /\.(png|ico)$/,
				type: "asset/inline",
			},
			{
				test: /\.svg$/,
				oneOf: [
					{ //use an inline svg
						// use: 'raw-loader',
						resourceQuery: /raw/,
						type: "asset/source"
					},
					{//jsoneditor uses svgs in its stylesheet
						// include: [/jsoneditor/, /pages/],
						//TODO: minimize:
						//https://github.com/webpack-contrib/svg-inline-loader#user-content--no-longer-maintained-
						//https://github.com/webpack-contrib/image-minimizer-webpack-plugin#user-content-optimize-with-imagemin
						type: "asset/inline",
						generator: {
							dataUrl: function(content) {
								return svgToMiniDataURI(content.toString());
							}
						}
					},
				],
			},
			{
				test: /\.css$/i,
				use: [MiniCssExtractPlugin.loader, 'css-loader'],
			},
		]
	},
	plugins: [
		new CopyWebpackPlugin({
			patterns: [
				{
					from: path.resolve(SRC, 'locales'),
					transform: function(content) {
						return JSONMinifyPlugin(content.toString());
					},
					to: path.resolve(DIST, 'locales')
				},
				{
					from: path.resolve(SRC, 'backend'),
					to: path.resolve(DIST, 'backend')
				},
				{
					from: path.resolve(SRC, 'api'),
					to: path.resolve(DIST, 'api')
				},
				{
					from: path.resolve(SRC, '.htaccess'),
					to: path.resolve(DIST)
				},
				{
					from: path.resolve(SRC, 'frontend', 'imgs', 'screenshots'),
					to: path.resolve(DIST, 'frontend', 'screenshots')
				},
				{
					from: path.resolve(__dirname, '..', 'LICENSE'),
					to: path.resolve(DIST, 'LICENSE'),
					toType: 'file'
				},
				{
					from: path.resolve(__dirname, '..', 'README.md'),
					to: path.resolve(DIST)
				},
				{
					from: path.resolve(__dirname, '..', 'CHANGELOG.md'),
					to: path.resolve(DIST)
				}
			]}),
		
		new HtmlWebpackPlugin({
			template: path.resolve(SRC, 'index.php'),
			filename: path.resolve(DIST, 'index.php'),
			skipAssets: [/.nojs/] //dont add js files into index_nojs.php
		}),
		new HtmlWebpackPlugin({
			template: path.resolve(SRC, 'index_nojs.php'),
			filename: path.resolve(DIST, 'index_nojs.php'),
			skipAssets: [/.main/] //dont add js files into index_nojs.php
		}),
		new MiniCssExtractPlugin({
			filename: '[name].[contenthash].css',
		}),
		new DefinePlugin({
			PACKAGE_VERSION: JSON.stringify(packageVersion)
		}),
		new HtmlWebpackSkipAssetsPlugin(), //so we are able to use "skipAssets" in HtmlWebpackPlugin
		generateFile({
			file: path.resolve(DIST, 'VERSION'),
			content: packageVersion
		}),
	]
}