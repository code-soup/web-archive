const config = require('../config');
const webpack = require('webpack');

const { CleanWebpackPlugin } = require('clean-webpack-plugin');
// const { WebpackManifestPlugin } = require('webpack-manifest-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
// const StyleLintPlugin = require('stylelint-webpack-plugin');
const ReactRefreshWebpackPlugin = require('@pmmmwh/react-refresh-webpack-plugin');
const isDevelopment = process.env.NODE_ENV !== 'production';

module.exports = [
    new CleanWebpackPlugin(),
    new MiniCssExtractPlugin({
        filename: `styles/${config.fileName}.css`,
        chunkFilename: `styles/[id].${config.fileName}.css`,
    }),
    isDevelopment && new ReactRefreshWebpackPlugin(),
    new webpack.DefinePlugin({
        'process.env.NAME': JSON.stringify(process.env.NODE_ENV),
    }),
    // new WebpackManifestPlugin(),
    // new webpack.ProvidePlugin({
    //     $: 'jquery',
    //     jQuery: 'jquery',
    //     'window.jQuery': 'jquery',
    // }),
    // new StyleLintPlugin({
    //     failOnError: config.enabled.production,
    //     syntax: "scss",
    // }),
].filter(Boolean);
