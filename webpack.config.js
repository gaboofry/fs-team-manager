const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const pkg = require('./package.json');

const REPOSITORY = 'https://github.com/gaboofry/fs-team-manager';

/**
 * Generated file -> human-readable source file it is compiled from.
 *
 * The mapping ends up in the header of every generated asset so that the
 * source of a compiled file can be located without any external tooling
 * (WordPress.org guidelines 1 and 4).
 */
const SOURCE_OF = {
    'admin.js': 'src/admin/admin.js',
    'admin.css': 'src/admin/admin.scss',
    'admin-rtl.css': 'src/admin/admin.scss',
    'frontend.js': 'src/frontend/frontend.js',
    'frontend.css': 'src/frontend/frontend.scss',
    'frontend-rtl.css': 'src/frontend/frontend.scss',
    'design.js': 'src/design/design.js',
    'blocks/fussball-widget/index.js': 'src/blocks/fussball-widget/index.js',
    'blocks/tables-overview/index.js': 'src/blocks/tables-overview/index.js',
};

const header = (fileName) => [
    '/*! Fabriel Team Manager v' + pkg.version,
    ' *',
    ' * This file is GENERATED - do not edit it directly.',
    ' * Source file:  ' + (SOURCE_OF[fileName] || 'src/'),
    ' * Rebuild with: npm ci && npm run build',
    ' * Source code:  ' + REPOSITORY,
    ' *',
    ' * @package   fabriel-team-manager',
    ' * @author    Fabriel Software (https://fabrielsoftware.de/)',
    ' * @copyright Fabriel Software',
    ' * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later',
    ' * @link      https://teammanager.fabrielsoftware.de/',
    ' */',
    '',
].join('\n');

/**
 * Prepends the header to every emitted JS and CSS file.
 *
 * Runs in the very last asset stage, after minification, so the header cannot
 * be dropped by the minifier.
 */
class SourceHeaderPlugin {
    apply(compiler) {
        const { Compilation, sources } = compiler.webpack;

        compiler.hooks.thisCompilation.tap('SourceHeaderPlugin', (compilation) => {
            compilation.hooks.processAssets.tap(
                {
                    name: 'SourceHeaderPlugin',
                    stage: Compilation.PROCESS_ASSETS_STAGE_REPORT,
                },
                (assets) => {
                    Object.keys(assets).forEach((fileName) => {
                        if (!/\.(js|css)$/.test(fileName)) {
                            return;
                        }
                        compilation.updateAsset(
                            fileName,
                            (old) => new sources.ConcatSource(new sources.RawSource(header(fileName)), old)
                        );
                    });
                }
            );
        });
    }
}

module.exports = {
    ...defaultConfig,
    entry: {
        'blocks/fussball-widget/index': path.resolve(__dirname, 'src/blocks/fussball-widget/index.js'),
        'blocks/tables-overview/index': path.resolve(__dirname, 'src/blocks/tables-overview/index.js'),
        'admin': path.resolve(__dirname, 'src/admin/admin.js'),
        'frontend': path.resolve(__dirname, 'src/frontend/frontend.js'),
        'design': path.resolve(__dirname, 'src/design/design.js'),
    },
    output: {
        ...defaultConfig.output,
        path: path.resolve(__dirname, 'build'),
        filename: '[name].js',
    },
    plugins: [
        ...(defaultConfig.plugins || []),
        // Source reference and license header on all emitted JS/CSS files.
        new SourceHeaderPlugin(),
    ],
};
