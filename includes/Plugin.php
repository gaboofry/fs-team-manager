<?php
namespace FabrielSoftware\TeamManager;

if (!defined('ABSPATH')) exit;

class Plugin {
    /** Von Add-ons zur Kompatibilitätsprüfung genutzt. */
    const API_VERSION = '1.0';

    public static function init() {
        Data\TeamRepository::init();
        Admin\AdminPage::init();
        Admin\SettingsPage::init();
        Blocks\BlockRegistrar::init();
        Design\StyleProvider::init();
        do_action('fs_tm_loaded', self::API_VERSION);
    }

    /**
     * Berechtigung für sämtliche Verwaltungsfunktionen.
     */
    public static function capability() {
        return apply_filters('fs_tm_capability', 'manage_options');
    }

    public static function currentUserCan() {
        return current_user_can(self::capability());
    }

    /**
     * Add-ons melden sich hierüber an, damit der Core erkennt, dass ein
     * Add-on aktiv ist.
     */
    public static function addonActive() {
        return (bool) apply_filters('fs_tm_addon_active', false);
    }

    public static function activate() {
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(plugin_basename(FS_TM_FILE));
            wp_die(
                sprintf(
                    /* translators: %s: current PHP version */
                    esc_html__('Fabriel Team Manager erfordert mindestens PHP Version 7.4. Auf Ihrem Server läuft PHP Version %s.', 'fabriel-team-manager'),
                    esc_html(PHP_VERSION)
                ),
                esc_html__('Plugin-Aktivierungsfehler', 'fabriel-team-manager'),
                array('back_link' => true)
            );
        }

        global $wp_version;
        if (version_compare($wp_version, '6.0', '<')) {
            deactivate_plugins(plugin_basename(FS_TM_FILE));
            wp_die(
                sprintf(
                    /* translators: %s: current WordPress version */
                    esc_html__('Fabriel Team Manager erfordert mindestens WordPress Version 6.0. Auf Ihrer Website läuft WordPress Version %s.', 'fabriel-team-manager'),
                    esc_html($wp_version)
                ),
                esc_html__('Plugin-Aktivierungsfehler', 'fabriel-team-manager'),
                array('back_link' => true)
            );
        }

        Data\TeamRepository::migrateData();
        update_option('fs_tm_version', FS_TM_VERSION, 'no');
    }
}
