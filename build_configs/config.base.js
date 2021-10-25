const path = require('path');
const {SRC, DIST} = require('./paths.js');
const JSONMinifyPlugin = require('node-json-minify');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const HtmlWebpackSkipAssetsPlugin = require('html-webpack-skip-assets-plugin').HtmlWebpackSkipAssetsPlugin;
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const svgToMiniDataURI = require('mini-svg-data-uri');

module.exports = {
	entry: {
		// scripts: path.resolve(SRC, 'js', 'index.js'),
		// style: path.resolve(SRC, 'css', 'style.css'),
		// input_design: path.resolve(SRC, 'css', 'input_design.css'),
		// loader: path.resolve(SRC, 'css', 'loader.css'),
		// widgets: path.resolve(SRC, 'css', 'widgets.css'),
		
		main: [
			path.resolve(SRC, 'css', 'style.css'),
			path.resolve(SRC, 'css', 'input_design.css'),
			path.resolve(SRC, 'css', 'widgets.css'),
			path.resolve(SRC, 'js', 'index.js'),
		],
		nojs: [
			path.resolve(SRC, 'css', 'style.css'),
			path.resolve(SRC, 'css', 'nojs.css'),
			path.resolve(SRC, 'css', 'input_design.css'),
			path.resolve(SRC, 'js', 'index_nojs.js')
		]
	},
	output: {
		path: path.resolve(DIST, 'parts'),
		
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
					to: path.resolve(DIST, 'parts', 'locales')
				},
				{
					from: path.resolve(SRC, 'backend'),
					to: path.resolve(DIST)
				},
				{
					from: path.resolve(SRC, 'imgs', 'screenshots'),
					to: path.resolve(DIST, 'parts', 'screenshots')
				},
				{
					from: path.resolve(__dirname, '..', 'LICENSE'),
					to: path.resolve(DIST, 'LICENSE'),
					toType: 'file'
				},
				{
					from: path.resolve(__dirname, '..', 'README.md'),
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
		
		
		new HtmlWebpackSkipAssetsPlugin() //so we are able to use "skipAssets" in HtmlWebpackPlugin
	]
}