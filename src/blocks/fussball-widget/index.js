/**
 * Fabriel Software Team-Manager - block "Spielplan & Tabelle"
 *
 * Human-readable source of build/blocks/fussball-widget/index.js.
 * Compile with: npm ci && npm run build
 *
 * @package   fabriel-team-manager
 * @author    Fabriel Software (https://fabrielsoftware.de/)
 * @copyright Fabriel Software
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
 * @link      https://github.com/gaboofry/fs-team-manager
 */

import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';

registerBlockType(metadata.name, {
    edit: Edit,
    save: () => null,
});
