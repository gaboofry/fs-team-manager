<?php
namespace FabrielSoftware\TeamManager\Admin;

if (!defined('ABSPATH')) exit;

/**
 * Meldungen nach einer Weiterleitung.
 *
 * Nach jeder schreibenden Aktion wird umgeleitet. Der Grund steht als Parameter in der
 * Adresse und wird hier in Text übersetzt. Die Ausgabe trägt `fs-tm-notice`; das
 * Admin-Skript verschiebt sie in den schwebenden Stapel, damit nichts den Inhalt verrückt.
 */
class Notices {

    /** Nonce, mit dem die eigenen Weiterleitungen ihre Anzeigeparameter kennzeichnen. */
    const NONCE_ACTION = 'fs_tm_notice';
    const NONCE_FIELD  = 'fs_tm_notice_nonce';

    /**
     * Leitet nach einer schreibenden Aktion auf eine Verwaltungsseite um (Post/Redirect/Get)
     * und beendet die Ausführung. Der mitgegebene Nonce weist die Anzeigeparameter als
     * eigene aus; fremd gesetzte Parameter werden dadurch ignoriert.
     */
    public static function redirect($page, array $args = array()) {
        wp_safe_redirect(Layout::url($page, self::withNonce($args)));
        exit;
    }

    /**
     * Ergänzt eine Parameterliste um den Anzeige-Nonce.
     *
     * Öffentlicher Erweiterungs-Punkt: Add-ons, die auf eine Seite des Core umleiten,
     * lassen ihre Anzeigeparameter hier kennzeichnen, damit der Core sie akzeptiert.
     *
     * @param array<string,mixed> $args
     * @return array<string,mixed>
     */
    public static function withNonce(array $args) {
        $args[self::NONCE_FIELD] = wp_create_nonce(self::NONCE_ACTION);
        return $args;
    }

    public static function render() {
        // PRG-Muster: Die Statusparameter werden ausschließlich über den eigenen Redirect
        // gesetzt. Der Nonce wird dort erzeugt und hier geprüft, damit fremde Parameter
        // in der Adresse keine Meldung auslösen.
        $fs_tm_nonce = isset($_GET[self::NONCE_FIELD]) ? sanitize_text_field(wp_unslash($_GET[self::NONCE_FIELD])) : '';
        if ($fs_tm_nonce === '' || !wp_verify_nonce($fs_tm_nonce, self::NONCE_ACTION)) {
            do_action('fs_tm_admin_notices');
            return;
        }

        $success = array(
            'deleted'       => __('Mannschaft gelöscht.', 'fs-team-manager'),
            'saved'         => __('Mannschaft und Zeiträume gespeichert.', 'fs-team-manager'),
            'created'       => __('Neue Mannschaft angelegt. Trage nun die Widget-IDs ein.', 'fs-team-manager'),
            'privacy_saved' => __('Datenschutz-Einstellungen gespeichert.', 'fs-team-manager'),
            'restored'      => __('Der Stand vor dem letzten Import wurde wiederhergestellt.', 'fs-team-manager'),
        );

        foreach ($success as $key => $message) {
            if (isset($_GET[$key])) {
                Layout::notice('success', $message);
            }
        }

        if (isset($_GET['imported'])) {
            $mode = isset($_GET['mode']) ? sanitize_key(wp_unslash($_GET['mode'])) : 'replace';
            $text = ($mode === 'merge')
                ? __('Sicherung eingespielt: vorhandene Mannschaften wurden ergänzt bzw. aktualisiert.', 'fs-team-manager')
                : __('Sicherung eingespielt: der bisherige Bestand wurde ersetzt.', 'fs-team-manager');

            $undo = '';
            if (ExportImport::hasRestorePoint()) {
                $url  = wp_nonce_url(
                    Layout::url(SettingsPage::SLUG, array('action' => 'fs_tm_restore_backup')),
                    'fs_tm_restore_backup_action',
                    'fs_tm_restore_nonce'
                );
                $undo = ' <a href="' . esc_url($url) . '">' . esc_html__('Import rückgängig machen', 'fs-team-manager') . '</a>';
            }
            Layout::notice('success', $text, $undo);
        }

        if (isset($_GET['error'])) {
            $errors = array(
                'slug_exists'       => __('Eine Mannschaft mit diesem Kürzel existiert bereits.', 'fs-team-manager'),
                'invalid_json'      => __('Ungültige JSON-Datei oder Datei zu groß.', 'fs-team-manager'),
                'import_failed'     => __('Die Sicherung konnte nicht eingespielt werden. Möglicherweise stammt sie aus einer neueren Version.', 'fs-team-manager'),
                'empty_name'        => __('Bitte einen Namen für die Mannschaft eingeben.', 'fs-team-manager'),
                'no_restore_point'  => __('Es ist kein Wiederherstellungspunkt mehr vorhanden.', 'fs-team-manager'),
                'validation_failed' => __('Speichern abgebrochen. Bitte die rot markierten Felder korrigieren; die Eingaben wurden beibehalten.', 'fs-team-manager'),
            );

            $code = sanitize_key(wp_unslash($_GET['error']));
            if (isset($errors[$code])) {
                Layout::notice('error', $errors[$code]);
                }
            }

        do_action('fs_tm_admin_notices');
    }
}
