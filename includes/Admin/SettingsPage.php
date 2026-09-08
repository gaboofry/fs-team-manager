<?php
namespace FabrielSoftware\TeamManager\Admin;

use FabrielSoftware\TeamManager\Plugin;

if (!defined('ABSPATH')) exit;

/**
 * Seite „Einstellungen“: Datenschutz, Datensicherung und Handbuch.
 *
 * Add-ons hängen eigene Bereiche über `fs_tm_settings_sections` ein, statt eine zweite
 * Einstellungsseite anzulegen.
 */
class SettingsPage {

    const SLUG = 'fs-tm-settings';

    public static function init() {
        add_action('admin_init', array(__CLASS__, 'handleActions'));
    }

    public static function handleActions() {
        if (!Plugin::currentUserCan()) return;

        if (isset($_POST['fs_tm_save_privacy']) && check_admin_referer('fs_tm_privacy_action', 'fs_tm_privacy_nonce')) {
            update_option('fs_tm_click_to_load', empty($_POST['fs_tm_click_to_load']) ? 0 : 1, 'no');
            update_option('fs_tm_show_attribution', empty($_POST['fs_tm_show_attribution']) ? 0 : 1, 'no');
            Notices::redirect(self::SLUG, array('privacy_saved' => 1));
        }
    }

    public static function render() {
        if (!Plugin::currentUserCan()) {
            wp_die(esc_html__('Keine Berechtigung.', 'fabriel-team-manager'));
        }

        // Reiner Anzeigeparameter: wählt nur den geöffneten Abschnitt. Er stammt aus dem
        // eigenen Redirect und wird über dessen Nonce geprüft.
        $fs_tm_nonce = isset($_GET[Notices::NONCE_FIELD]) ? sanitize_text_field(wp_unslash($_GET[Notices::NONCE_FIELD])) : '';
        $section = '';
        if ($fs_tm_nonce !== '' && wp_verify_nonce($fs_tm_nonce, Notices::NONCE_ACTION)) {
            $section = isset($_GET['section']) ? sanitize_key(wp_unslash($_GET['section'])) : '';
        }

        Layout::open(self::SLUG);
        Notices::render();

        do_action('fs_tm_settings_sections_before', $section);

        self::renderPrivacy($section === 'privacy');

        // Das Add-on bringt eine Vollsicherung mit, die auch die Mannschaften umfasst.
        // Beide Bereiche nebeneinander wären redundant und verwirrend.
        if (apply_filters('fs_tm_show_core_backup', !Plugin::addonActive())) {
            self::renderBackup($section === 'backup');
        }

        do_action('fs_tm_settings_sections', $section);

        // Additiv: Add-ons können eigene Sektionen anhängen, nachdem alle
        // Einstellungssektionen gerendert sind (ARCHITECTURE.md §7.2).
        do_action('fs_tm_admin_sections_after_settings');

        HelpSection::render();

        Layout::close();
    }

    /* ------------------------------------------------------------------ */

    private static function renderPrivacy($open) {
        Layout::collapsibleOpen('fs-tm-privacy-box', '🔒', __('Datenschutz & externe Inhalte', 'fabriel-team-manager'), array('collapsed' => !$open));
        ?>
        <div class="fs-tm-privacy-grid">
            <div class="fs-tm-privacy-row fs-tm-privacy-row--title">
                <h3><?php esc_html_e('Datenschutz & externe Inhalte', 'fabriel-team-manager'); ?></h3>
            </div>
            <div class="fs-tm-privacy-row fs-tm-privacy-row--description">
                <p>
                    <?php esc_html_e('Spielpläne und Tabellen werden vom externen Anbieter fussball.de geladen. Dabei wird die IP-Adresse der Besucher an den Anbieter übertragen.', 'fabriel-team-manager'); ?>
                </p>
            </div>
            <div class="fs-tm-privacy-row fs-tm-privacy-row--actions">
                <form method="post" action="">
                    <?php wp_nonce_field('fs_tm_privacy_action', 'fs_tm_privacy_nonce'); ?>
                    <label class="fs-tm-checkbox-pill">
                        <input type="checkbox" name="fs_tm_click_to_load" value="1" <?php checked(get_option('fs_tm_click_to_load', 0), 1); ?>>
                        <span><?php esc_html_e('Zwei-Klick-Lösung aktivieren', 'fabriel-team-manager'); ?></span>
                        <span class="fs-tm-help-tip" data-tip="<?php esc_attr_e('Externe Inhalte werden erst nach ausdrücklicher Bestätigung durch den Besucher geladen. Die Einstellung gilt für alle Widgets.', 'fabriel-team-manager'); ?>">?</span>
                    </label>
                    <label class="fs-tm-checkbox-pill">
                        <input type="checkbox" name="fs_tm_show_attribution" value="1" <?php checked(get_option('fs_tm_show_attribution', 0), 1); ?>>
                        <span><?php esc_html_e('Hinweis „Eingebunden durch Fabriel Software Teammanager“ anzeigen', 'fabriel-team-manager'); ?></span>
                        <span class="fs-tm-help-tip" data-tip="<?php esc_attr_e('Blendet unter jeder Karte im Frontend einen Hinweis mit Link auf die Herstellerseite ein. Standardmäßig ausgeschaltet; das Plugin funktioniert ohne diesen Hinweis vollständig.', 'fabriel-team-manager'); ?>">?</span>
                    </label>
                    <input type="submit" name="fs_tm_save_privacy" class="button fs-tm-btn-primary" value="<?php esc_attr_e('Einstellung speichern', 'fabriel-team-manager'); ?>">
                </form>
            </div>
        </div>
        <?php
        Layout::collapsibleClose();
    }

    private static function renderBackup($open) {
        $export_url = wp_nonce_url(
            Layout::url(self::SLUG, array('action' => 'fs_tm_export_teams_json')),
            'fs_tm_export_teams_action',
            'fs_tm_export_teams_nonce'
        );

        Layout::collapsibleOpen('fs-tm-backup-box', '💾', __('Datensicherung: Teams & Widgets', 'fabriel-team-manager'), array('collapsed' => !$open));
        ?>
        <div class="fs-tm-backup-grid">
            <!-- Export Card -->
            <div class="fs-tm-backup-card fs-tm-backup-card--three-row">
                <div class="fs-tm-backup-card-row fs-tm-backup-card-row--title">
                    <h3>📤 <?php esc_html_e('Teams exportieren', 'fabriel-team-manager'); ?></h3>
                </div>
                <div class="fs-tm-backup-card-row fs-tm-backup-card-row--description">
                    <p><?php esc_html_e('Lade alle Mannschaften, Zeiträume und Widget-Zuordnungen als JSON-Sicherung herunter.', 'fabriel-team-manager'); ?></p>
                </div>
                <div class="fs-tm-backup-card-row fs-tm-backup-card-row--action">
                    <a href="<?php echo esc_url($export_url); ?>" class="button fs-tm-btn-secondary" data-fs-tm-download>
                        <span class="dashicons dashicons-download"></span> <?php esc_html_e('Sicherung herunterladen', 'fabriel-team-manager'); ?>
                    </a>
                </div>
            </div>
            <!-- Import Card -->
            <div class="fs-tm-backup-card fs-tm-backup-card fs-tm-backup-card--three-row">
                <div class="fs-tm-backup-card-row fs-tm-backup-card-row--title">
                    <h3>📥 <?php esc_html_e('Teams wiederherstellen', 'fabriel-team-manager'); ?></h3>
                </div>
                <div class="fs-tm-backup-card-row fs-tm-backup-card-row--description">
                    <p><?php esc_html_e('Spiele eine zuvor exportierte JSON-Datei wieder ein.', 'fabriel-team-manager'); ?></p>
                </div>
                <div class="fs-tm-backup-card-row fs-tm-backup-card-row--action">
                    <form method="post" enctype="multipart/form-data" action="" id="fs-tm-import-form">
                        <?php wp_nonce_field('fs_tm_import_teams_action', 'fs_tm_import_teams_nonce'); ?>
                        <div class="fs-tm-import-mode">
                            <label class="fs-tm-checkbox-pill">
                                <input type="radio" name="fs_tm_import_mode" value="merge" checked>
                                <span><?php esc_html_e('Ergänzen', 'fabriel-team-manager'); ?></span>
                                <span class="fs-tm-help-tip" data-tip="<?php esc_attr_e('Mannschaften aus der Datei werden hinzugefügt oder aktualisiert. Vorhandene, die nicht in der Datei stehen, bleiben erhalten.', 'fabriel-team-manager'); ?>">?</span>
                            </label>
                            <label class="fs-tm-checkbox-pill">
                                <input type="radio" name="fs_tm_import_mode" value="replace">
                                <span><?php esc_html_e('Ersetzen', 'fabriel-team-manager'); ?></span>
                                <span class="fs-tm-help-tip" data-tip="<?php esc_attr_e('Der komplette Bestand wird durch den Inhalt der Datei ersetzt. Der bisherige Stand lässt sich direkt danach einmalig wiederherstellen.', 'fabriel-team-manager'); ?>">?</span>
                            </label>
                        </div>
                        <div class="fs-tm-file-upload-wrap">
                            <input type="file" name="fs_tm_import_teams_file" accept=".json,application/json" required class="fs-tm-file-input">
                            <input type="submit" name="fs_tm_import_teams_submit" class="button fs-tm-btn-secondary" value="<?php esc_attr_e('JSON einspielen', 'fabriel-team-manager'); ?>">
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        Layout::collapsibleClose();
    }
}
