const path = require('path');
const fs = require('fs');
const CopyWebpackPlugin = require('copy-webpack-plugin', true);

const defaultConfig = require('@wordpress/scripts/config/webpack.config', true);
const { fromProjectRoot } = require('@wordpress/scripts/utils/file', true);

const srcPath = fromProjectRoot('assets-src');
const distPath = fromProjectRoot('assets');

function getFilesInDir(dirPath) {
    if (!fs.existsSync(dirPath)) {
        return [];
    }

    const files = [];

    function walk(currentDir) {
        for (const entry of fs.readdirSync(currentDir, { withFileTypes: true })) {
            const fullPath = path.join(currentDir, entry.name);

            if (entry.isDirectory()) {
                walk(fullPath);
                continue;
            }

            files.push(fullPath);
        }
    }

    walk(dirPath);

    return files;
}

function getCopyPatterns() {
    let patterns = [];

    getFilesInDir(path.join(srcPath, 'icons')).forEach((file) => {
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

        'admin/ry-options': path.join(srcPath, 'admin/ry-options.js'),
        'admin/ry-shipping': path.join(srcPath, 'admin/ry-shipping.js')
    },
    output: {
        ...defaultConfig.output,
        path: distPath,
        filename: '[name].js'
    },
    plugins: [
        ...defaultConfig.plugins,
        new CopyWebpackPlugin({
            patterns: getCopyPatterns()
        })
    ]
};
