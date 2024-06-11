const path = require('path', true);
const glob = require('glob', true);
const CopyWebpackPlugin = require('copy-webpack-plugin', true);
const { CleanWebpackPlugin } = require('clean-webpack-plugin', true);

const defaultConfig = require('@wordpress/scripts/config/webpack.config', true);
const { fromProjectRoot } = require('@wordpress/scripts/utils/file', true);

const srcPath = fromProjectRoot('assets-src');
const distPath = fromProjectRoot('assets');

function getCopyPatterns() {
    let patterns = [];

    glob.sync(
        path.join(srcPath, 'icons', '*')
    ).forEach((file) => {
        patterns.push({
            from: file,
            to: path.relative(srcPath, file)
        });
    });

    return patterns;
}

module.exports = {
    ...defaultConfig,
    entry: {
        'ry-checkout': path.join(srcPath, 'ry-checkout.js'),
        'ry-payment': path.join(srcPath, 'ry-payment.scss'),

        'admin/ry-shipping': path.join(srcPath, 'admin/ry-shipping.js')
    },
    output: {
        ...defaultConfig.output,
        path: distPath,
        filename: '[name].js',
    },
    plugins: [
        ...defaultConfig.plugins.filter((plugin) => plugin.constructor.name !== 'CleanWebpackPlugin'),
        new CleanWebpackPlugin({
            //cleanOnceBeforeBuildPatterns: [],
            cleanAfterEveryBuildPatterns: ['!fonts/**', '!images/**'],
            cleanStaleWebpackAssets: false,
        }),
        new CopyWebpackPlugin({
            patterns: getCopyPatterns()
        })
    ]
};
