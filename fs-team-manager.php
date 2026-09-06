<?php
/**
 * Plugin Name:       Fabriel Software Team-Manager
 * Plugin URI:        https://teammanager.fabrielsoftware.de/
 * Description:       Zentrale Verwaltung von Mannschaften, Zeiträumen, Spielplänen und Tabellen für Widgets von fussball.de.
 * Version:           1.2.2
 * Requires at least: 6.0
 * Tested up to:      7.1
 * Requires PHP:      7.4
 * Author:            Fabriel Software
 * Author URI:        https://fabrielsoftware.de/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       fs-team-manager
 * Domain Path:       /languages
 *
 * Source code:      https://github.com/gaboofry/fs-team-manager.git
 * Build tooling:    package.json / webpack.config.js (see readme.txt, section
 *                   "Source code & development").
 */

if (!defined('ABSPATH')) exit;

define('FS_TM_VERSION', '1.2.2');
define('FS_TM_PATH', plugin_dir_path(__FILE__));
define('FS_TM_URL', plugin_dir_url(__FILE__));
define('FS_TM_FILE', __FILE__);

if (file_exists(FS_TM_PATH . 'vendor/autoload.php')) {
    require_once FS_TM_PATH . 'vendor/autoload.php';
} else {
    spl_autoload_register(function ($class) {
        $prefix = 'FabrielSoftware\\TeamManager\\';
        $base_dir = FS_TM_PATH . 'includes/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) return;
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) require_once $file;
    });
}

register_activation_hook(FS_TM_FILE, array('FabrielSoftware\\TeamManager\\Plugin', 'activate'));

add_action('plugins_loaded', function() {
    \FabrielSoftware\TeamManager\Plugin::init();
});
