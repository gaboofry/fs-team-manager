<?php
namespace FabrielSoftware\TeamManager\Admin;

use FabrielSoftware\TeamManager\Data\TeamRepository;

if (!defined('ABSPATH')) exit;

class ExportImport {
    const RESTORE_OPTION = 'fs_tm_teams_restore_point';
    const MAX_UPLOAD_BYTES = 2097152;

    public static function handleExport() {
        if (!check_admin_referer('fs_tm_export_teams_action', 'fs_tm_export_teams_nonce')) {
            wp_die(esc_html__('Keine Berechtigung.', 'fabriel-team-manager'));
        }
        $teams = TeamRepository::exportTeams();
        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

        // sanitize_file_name() schliesst Zeilenumbrueche im Header aus.
        $fn = sanitize_file_name('fs-teams-backup-' . gmdate('Y-m-d-His') . '.json');
        nocache_headers();
        header('Content-Disposition: attachment; filename="' . $fn . '"');

        // Dateidownload im JSON-Format, kein HTML-Kontext: wp_send_json()
        // setzt den Content-Type, kodiert die Daten genau einmal und beendet
        // die Anfrage. Eine HTML-Maskierung (z. B. wp_kses_post()) würde die
        // Sicherungsdatei beschädigen.
        wp_send_json($teams, null, $flags);
    }

    public static function hasRestorePoint() {
        return is_array(get_option(self::RESTORE_OPTION, null));
    }

    /**
     * Stellt den Stand vor dem letzten Import einmalig wieder her.
     */
    public static function handleRestore() {
        if (!check_admin_referer('fs_tm_restore_backup_action', 'fs_tm_restore_nonce')) {
            wp_die(esc_html__('Keine Berechtigung.', 'fabriel-team-manager'));
        }
        $restore_point = get_option(self::RESTORE_OPTION, null);
        if (!is_array($restore_point)) {
            Notices::redirect(SettingsPage::SLUG, array('error' => 'no_restore_point', 'section' => 'backup'));
        }
        TeamRepository::importTeams($restore_point, 'replace');
        delete_option(self::RESTORE_OPTION);
        TeamRepository::clearPagesCache();
        Notices::redirect(SettingsPage::SLUG, array('restored' => 1, 'section' => 'backup'));
    }

    /**
     * Liest die hochgeladene JSON-Datei und liefert das dekodierte Array.
     *
     * Das Format (Core-Format oder externes Add-on-Format) wird in
     * `handleImport()` über die Metadaten-Struktur erkannt.
     */
    private static function readUploadedJson() {
        if (!check_admin_referer('fs_tm_import_teams_action', 'fs_tm_import_teams_nonce')) {
            return null;
        }
        if (empty($_FILES['fs_tm_import_teams_file']['tmp_name'])) return null;

        // Jeder Einzelwert wird auf Existenz geprüft, aus dem Upload-Array extrahiert
        // und mit dem passenden Sanitizer behandelt. `tmp_name` ist ein von PHP selbst
        // erzeugter Pfad und dient hier ausschließlich als Nachschlagewert für
        // `is_uploaded_file()`; sollte die Sanitisierung ihn verändern, schlägt diese
        // Prüfung fehl und der Import bricht ab.
        if (!isset($_FILES['fs_tm_import_teams_file']['name'], $_FILES['fs_tm_import_teams_file']['size'], $_FILES['fs_tm_import_teams_file']['error'])) {
            return null;
        }

        $tmp_name   = sanitize_text_field(wp_unslash($_FILES['fs_tm_import_teams_file']['tmp_name']));
        $file_name  = sanitize_file_name(wp_unslash($_FILES['fs_tm_import_teams_file']['name']));
        $file_size  = absint(wp_unslash($_FILES['fs_tm_import_teams_file']['size']));
        $file_error = absint(wp_unslash($_FILES['fs_tm_import_teams_file']['error']));

        if ($file_error !== UPLOAD_ERR_OK) return null;
        if ($file_size <= 0 || $file_size > self::MAX_UPLOAD_BYTES) return null;

        if (!is_uploaded_file($tmp_name)) return null;

        if (strtolower(pathinfo($file_name, PATHINFO_EXTENSION)) !== 'json') return null;

        $filetype = wp_check_filetype($file_name, array('json' => 'application/json'));
        if (empty($filetype['ext'])) return null;

        $raw_json = self::readFile($tmp_name);
        if ($raw_json === null) return null;

        $clean_json = preg_replace('/^\xEF\xBB\xBF/', '', trim($raw_json));
        $data       = json_decode($clean_json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data) || empty($data)) return null;

        return $data;
    }

    /**
     * Liest eine Datei über die Dateisystem-API von WordPress.
     *
     * Direkte PHP-Dateifunktionen sind laut den WordPress-Coding-Standards zu
     * vermeiden. Hochgeladene Dateien liegen im temporären Verzeichnis von PHP;
     * ein für die Installation eingerichteter FTP- oder SSH-Transport hat auf
     * dieses Verzeichnis keinen Zugriff. Deshalb wird die Transportart vorab
     * bestimmt und der direkte Zugriff verwendet, sobald die Installation auf
     * einen entfernten Transport eingestellt ist.
     *
     * @param string $path Absoluter Pfad der zu lesenden Datei.
     * @return string|null Inhalt oder `null`, wenn die Datei nicht lesbar ist.
     */
    private static function readFile($path) {
        global $wp_filesystem;

        require_once ABSPATH . 'wp-admin/includes/file.php';

        $directory  = dirname($path);
        $filesystem = null;

        // Nur der direkte Transport kommt infrage; ein für die Installation
        // eingerichteter FTP- oder SSH-Transport wird gar nicht erst
        // aufgebaut, da er dieses Verzeichnis ohnehin nicht erreicht.
        if ('direct' === get_filesystem_method(array(), $directory, true)
            && WP_Filesystem(false, $directory, true)
            && $wp_filesystem instanceof \WP_Filesystem_Direct) {
            $filesystem = $wp_filesystem;
        } else {
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
            $filesystem = new \WP_Filesystem_Direct(null);
        }

        if (!$filesystem->is_file($path) || !$filesystem->is_readable($path)) return null;

        $raw = $filesystem->get_contents($path);

        return is_string($raw) ? $raw : null;
    }

    /**
     * Erkennt das Core-Backup-Format an der Metadaten-Struktur:
     * Core-Backups haben auf der Root-Ebene Team-Slugs als Keys, bei denen
     * jeder Wert ein Array mit dem Pflichtfeld `name` ist. Alle anderen
     * Strukturen (z. B. fremde Backup-Formate mit Metadatenblöcken) sind
     * externe Formate und werden über den Filter
     * `fs_tm_import_external_backup` an Add-ons delegiert.
     */
    private static function isCoreFormat($data) {
        if (!is_array($data) || empty($data)) return false;
        foreach ($data as $slug => $team_data) {
            if (!is_string($slug) || empty($slug) || !is_array($team_data)) return false;
            if (!isset($team_data['name']) || !is_string($team_data['name']) || empty($team_data['name'])) return false;
        }
        return true;
    }

    /**
     * Validiert das komplette Core-Backup-Schema.
     *
     * Erwartete Struktur:
     * {
     *   "slug-mannschaft": {
     *     "name": "...",
     *     "page_id": 123,
     *     "periods": [
     *       {
     *         "label": "...",
     *         "valid_from": "YYYY-MM-DD",
     *         "valid_to": "YYYY-MM-DD",
     *         "id_matches": "uuid",
     *         "id_table": "uuid",
     *         "is_club_matches": 0
     *       }
     *     ]
     *   }
     * }
     */
    private static function validateTeamSchema($data) {
        if (!is_array($data)) return false;

        // Mindestens ein Team muss vorhanden sein
        $team_count = 0;

        foreach ($data as $slug => $team_data) {
            // Key muss ein nicht-leerer String sein (Slug)
            if (!is_string($slug) || empty($slug)) continue;

            // Team-Daten müssen ein Array sein
            if (!is_array($team_data)) continue;

            // Team MUSS einen 'name' Key haben
            if (!isset($team_data['name']) || !is_string($team_data['name']) || empty($team_data['name'])) {
                continue;
            }

            // 'page_id' muss eine Integer sein (optional, aber wenn vorhanden, dann Integer)
            if (isset($team_data['page_id']) && !is_int($team_data['page_id'])) {
                continue;
            }

            // 'periods' muss ein Array sein (optional)
            if (!isset($team_data['periods'])) {
                // Kein periods - trotzdem gültig, wenn name existiert
                $team_count++;
                continue;
            }

            if (!is_array($team_data['periods'])) {
                continue;
            }

            // Jedes Period validieren
            $valid_periods = 0;
            foreach ($team_data['periods'] as $period) {
                if (!is_array($period)) continue;

                // Period MUSS 'valid_from' haben
                if (!isset($period['valid_from']) || !is_string($period['valid_from'])) continue;

                // 'valid_from' muss ein gültiges Datum sein (YYYY-MM-DD)
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $period['valid_from'])) continue;

                // Weitere optional Felder prüfen
                if (isset($period['valid_to']) && !is_string($period['valid_to'])) continue;
                if (isset($period['id_matches']) && !is_string($period['id_matches'])) continue;
                if (isset($period['id_table']) && !is_string($period['id_table'])) continue;
                if (isset($period['is_club_matches']) && !is_int($period['is_club_matches'])) continue;

                $valid_periods++;
            }

            // Team ist gültig wenn es mindestens ein gültiges Period hat ODER nur name ohne periods
            if ($valid_periods > 0 || !isset($team_data['periods']) || empty($team_data['periods'])) {
                $team_count++;
            }
        }

        // Mindestens ein gültiges Team erforderlich
        return $team_count >= 1;
    }

    public static function handleImport() {
        if (!check_admin_referer('fs_tm_import_teams_action', 'fs_tm_import_teams_nonce')) {
            wp_die(esc_html__('Keine Berechtigung.', 'fabriel-team-manager'));
        }

        $data = self::readUploadedJson();
        if ($data === null) {
            Notices::redirect(SettingsPage::SLUG, array('error' => 'invalid_json', 'section' => 'backup'));
        }

        $mode = (isset($_POST['fs_tm_import_mode']) && sanitize_key(wp_unslash($_POST['fs_tm_import_mode'])) === 'replace') ? 'replace' : 'merge';

        /**
         * Öffentlicher Erweiterungs-Punkt (public extension point).
         *
         * Add-ons mit eigenem, erweitertem Backup-Format übernehmen hier den
         * Import und geben einen Wert ungleich `null` zurück (z. B. eine
         * Statistik der übernommenen Datensätze). Der Core bricht die eigene
         * Verarbeitung dann ab und meldet den Import als erfolgreich. Ein
         * `WP_Error` bedeutet: Die Sicherung stammt vom Add-on, konnte aber
         * nicht eingespielt werden — der Core meldet in diesem Fall einen
         * Fehler, statt die Datei als eigenes Format zu deuten.
         *
         * Das Core erkennt fremde Formate ausschließlich über die Metadaten-
         * Struktur (siehe `isCoreFormat()`), nicht über Plugin-Slugs oder
         * Klassennamen.
         *
         * @filter mixed
         * @param mixed  $result Statistik des Add-on-Imports, sonst `null`.
         * @param array  $data   Dekodiertes Backup-Array.
         * @param string $mode   Import-Modus: `merge` oder `replace`.
         */
        $handled = apply_filters('fs_tm_import_external_backup', null, $data, $mode);

        if (is_wp_error($handled)) {
            Notices::redirect(SettingsPage::SLUG, array('error' => 'import_failed', 'section' => 'backup'));
        }

        if (null !== $handled) {
            TeamRepository::clearPagesCache();
            Notices::redirect(SettingsPage::SLUG, array('imported' => 1, 'mode' => $mode, 'section' => 'backup'));
        }

        if (!self::isCoreFormat($data) || !self::validateTeamSchema($data)) {
            Notices::redirect(SettingsPage::SLUG, array('error' => 'invalid_json', 'section' => 'backup'));
        }

        update_option(self::RESTORE_OPTION, TeamRepository::exportTeams(), 'no');

        $errors   = array();
        $imported = TeamRepository::sanitizeTeamsArray($data, $errors);
        if (empty($imported)) {
            Notices::redirect(SettingsPage::SLUG, array('error' => 'invalid_json', 'section' => 'backup'));
        }

        TeamRepository::importTeams($imported, $mode);
        TeamRepository::clearPagesCache();

        Notices::redirect(SettingsPage::SLUG, array('imported' => 1, 'mode' => $mode, 'section' => 'backup'));
    }
}
