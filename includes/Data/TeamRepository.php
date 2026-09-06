<?php
namespace FabrielSoftware\TeamManager\Data;

if (!defined('ABSPATH')) exit;

class TeamRepository {
    const POST_TYPE     = 'fs_tm_team';
    const META_SLUG     = '_fs_tm_slug';
    const META_PAGE_ID  = '_fs_tm_page_id';
    const META_PERIODS  = '_fs_tm_periods';
    const LEGACY_OPTION = 'fs_tm_teams';
    const LEGACY_BACKUP = 'fs_tm_teams_pre_cpt';
    const CACHE_KEY     = 'all_teams';
    const CACHE_GROUP   = 'fs_tm';

    private static $runtime_cache = null;

    public static function init() {
        add_action('init', array(__CLASS__, 'registerPostType'), 5);
        add_action('init', array(__CLASS__, 'checkVersionAndMigrate'), 6);
        add_action('save_post_page', array(__CLASS__, 'clearPagesCache'));
        add_action('deleted_post', array(__CLASS__, 'clearPagesCache'));
        add_action('trash_post', array(__CLASS__, 'clearPagesCache'));
        add_action('save_post_' . self::POST_TYPE, array(__CLASS__, 'flushCache'));
    }

    /**
     * Mannschaften liegen als interner Post-Type vor. Die Oberfläche bleibt die
     * eigene Adminseite, deshalb ist die WordPress-UI bewusst deaktiviert.
     */
    public static function registerPostType() {
        if (post_type_exists(self::POST_TYPE)) return;

        register_post_type(self::POST_TYPE, apply_filters('fs_tm_post_type_args', array(
            'label'               => __('Mannschaften', 'fs-team-manager'),
            'public'              => false,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'show_ui'             => false,
            'show_in_menu'        => false,
            'show_in_nav_menus'   => false,
            'show_in_rest'        => false,
            'hierarchical'        => false,
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
            'can_export'          => true,
            'delete_with_user'    => false,
            'supports'            => array('title', 'page-attributes'),
            'capability_type'     => 'post',
        )));
    }

    public static function checkVersionAndMigrate() {
        $installed_ver = get_option('fs_tm_version', '0.0.0');
        if (version_compare($installed_ver, FS_TM_VERSION, '<')) {
            self::migrateData();
            update_option('fs_tm_version', FS_TM_VERSION, 'no');
        }
    }

    /**
     * Normalisiert das Format vor Einführung der Zeiträume.
     */
    private static function normalizeLegacyEntry($data, $slug) {
        if (isset($data['periods']) && is_array($data['periods'])) {
            return array(
                'name'    => $data['name'] ?? $slug,
                'page_id' => intval($data['page_id'] ?? 0),
                'periods' => $data['periods'],
            );
        }

        $periods = array();
        if (!empty($data['id_matches']) || !empty($data['id_table'])) {
            $periods[] = array(
                'label'           => __('Standard-Zeitraum', 'fs-team-manager'),
                'valid_from'      => '2020-01-01',
                'valid_to'        => '',
                'id_matches'      => $data['id_matches'] ?? '',
                'id_table'        => $data['id_table'] ?? '',
                'is_club_matches' => !empty($data['is_club_matches']) ? 1 : 0
            );
        }

        return array(
            'name'    => $data['name'] ?? $slug,
            'page_id' => intval($data['page_id'] ?? 0),
            'periods' => $periods,
        );
    }

    private static function defaultTeam() {
        $current_year = intval(current_time('Y'));
        return array(
            'senioren-1' => array(
                'name'    => __('1. Mannschaft', 'fs-team-manager'),
                'page_id' => 0,
                'periods' => array(
                    array(
                        /* translators: 1: start year, 2: end year */
                        'label'           => sprintf(__('Zeitraum %1$d/%2$d', 'fs-team-manager'), $current_year, $current_year + 1),
                        'valid_from'      => $current_year . '-07-01',
                        'valid_to'        => ($current_year + 1) . '-06-30',
                        'id_matches'      => '',
                        'id_table'        => '',
                        'is_club_matches' => 0
                    )
                )
            )
        );
    }

    /**
     * Überführt die frühere Options-Speicherung in den Post-Type. Der alte Stand
     * bleibt als Sicherung erhalten, bis er manuell entfernt wird.
     */
    public static function migrateData() {
        self::registerPostType();

        $legacy = get_option(self::LEGACY_OPTION, null);
        if (!is_array($legacy)) {
            $poc = get_option('fs_fm_fussball_teams', null);
            $legacy = is_array($poc) && !empty($poc) ? $poc : null;
        }

        if ($legacy === null) {
            if (!self::hasTeams()) {
                foreach (self::defaultTeam() as $slug => $data) {
                    self::saveTeam($slug, $data);
                }
            }
            return;
        }

        foreach ($legacy as $slug => $data) {
            $slug = sanitize_key($slug);
            if (empty($slug) || !is_array($data)) continue;
            if (self::getPostIdBySlug($slug)) continue;
            self::saveTeam($slug, self::normalizeLegacyEntry($data, $slug));
        }

        if (get_option(self::LEGACY_OPTION, null) !== null) {
            update_option(self::LEGACY_BACKUP, $legacy, 'no');
            delete_option(self::LEGACY_OPTION);
        }
    }

    public static function flushCache() {
        self::$runtime_cache = null;
        wp_cache_delete(self::CACHE_KEY, self::CACHE_GROUP);
    }

    private static function hydrate($post) {
        $periods = get_post_meta($post->ID, self::META_PERIODS, true);
        $slug    = get_post_meta($post->ID, self::META_SLUG, true);

        $entry = array(
            'post_id'    => (int) $post->ID,
            'slug'       => !empty($slug) ? $slug : $post->post_name,
            'name'       => $post->post_title,
            'page_id'    => (int) get_post_meta($post->ID, self::META_PAGE_ID, true),
            'menu_order' => (int) $post->menu_order,
            'periods'    => is_array($periods) ? $periods : array(),
        );

        return apply_filters('fs_tm_team_data', $entry, $post);
    }

    /**
     * @return array<string,array> Mannschaften, indiziert nach Slug, in Anzeigereihenfolge.
     */
    public static function getSavedTeams() {
        if (is_array(self::$runtime_cache)) return self::$runtime_cache;

        $cached = wp_cache_get(self::CACHE_KEY, self::CACHE_GROUP);
        if (is_array($cached)) {
            self::$runtime_cache = $cached;
            return $cached;
        }

        $posts = get_posts(array(
            'post_type'        => self::POST_TYPE,
            'post_status'      => 'publish',
            'numberposts'      => -1,
            'orderby'          => array('menu_order' => 'ASC', 'ID' => 'ASC'),
            'suppress_filters' => false,
            'no_found_rows'    => true,
        ));

        $teams = array();
        foreach ($posts as $post) {
            $entry = self::hydrate($post);
            $teams[$entry['slug']] = $entry;
        }

        self::$runtime_cache = $teams;
        wp_cache_set(self::CACHE_KEY, $teams, self::CACHE_GROUP);
        return $teams;
    }

    public static function hasTeams() {
        $teams = self::getSavedTeams();
        return !empty($teams);
    }

    public static function getTeam($slug) {
        $teams = self::getSavedTeams();
        return isset($teams[$slug]) ? $teams[$slug] : null;
    }

    public static function getPostIdBySlug($slug) {
        $team = self::getTeam($slug);
        return $team ? (int) $team['post_id'] : 0;
    }

    private static function nextMenuOrder() {
        $max = 0;
        foreach (self::getSavedTeams() as $team) {
            if (isset($team['menu_order']) && $team['menu_order'] > $max) $max = (int) $team['menu_order'];
        }
        return $max + 1;
    }

    /**
     * Legt eine Mannschaft an oder aktualisiert sie.
     *
     * @return int|\WP_Error Post-ID der Mannschaft.
     */
    public static function saveTeam($slug, $data) {
        $slug = sanitize_key($slug);
        if (empty($slug)) return new \WP_Error('fs_tm_invalid_slug', __('Ungültiges Kürzel.', 'fs-team-manager'));

        $post_id  = self::getPostIdBySlug($slug);
        $is_new   = empty($post_id);
        $postarr  = array(
            'post_type'   => self::POST_TYPE,
            'post_status' => 'publish',
            'post_name'   => $slug,
            'post_title'  => !empty($data['name']) ? $data['name'] : $slug,
        );

        if ($is_new) {
            $postarr['menu_order'] = self::nextMenuOrder();
            $post_id = wp_insert_post($postarr, true);
        } else {
            $postarr['ID'] = $post_id;
            $post_id = wp_update_post($postarr, true);
        }

        if (is_wp_error($post_id)) return $post_id;

        // Der Slug ist der Schlüssel in Block-Attributen und Backups und darf nicht
        // von wp_unique_post_slug() verändert werden.
        update_post_meta($post_id, self::META_SLUG, $slug);
        update_post_meta($post_id, self::META_PAGE_ID, intval($data['page_id'] ?? 0));
        update_post_meta($post_id, self::META_PERIODS, isset($data['periods']) && is_array($data['periods']) ? $data['periods'] : array());

        self::flushCache();
        do_action('fs_tm_team_saved', $post_id, $slug, $data, $is_new);

        return $post_id;
    }

    public static function deleteTeam($slug) {
        $post_id = self::getPostIdBySlug($slug);
        if (!$post_id) return false;

        do_action('fs_tm_before_team_deleted', $post_id, $slug);
        wp_delete_post($post_id, true);
        self::flushCache();

        return true;
    }

    public static function saveOrder(array $slugs) {
        $teams = self::getSavedTeams();
        $order = 0;

        foreach ($slugs as $slug) {
            if (!isset($teams[$slug])) continue;
            wp_update_post(array('ID' => $teams[$slug]['post_id'], 'menu_order' => ++$order));
        }
        foreach ($teams as $slug => $team) {
            if (in_array($slug, $slugs, true)) continue;
            wp_update_post(array('ID' => $team['post_id'], 'menu_order' => ++$order));
        }

        self::flushCache();
    }

    /**
     * Backup-Format ohne interne Felder, damit Exporte versionsunabhängig bleiben.
     */
    public static function exportTeams() {
        $export = array();
        foreach (self::getSavedTeams() as $slug => $team) {
            $entry = array(
                'name'    => $team['name'],
                'page_id' => $team['page_id'],
                'periods' => $team['periods'],
            );
            $export[$slug] = apply_filters('fs_tm_export_team_entry', $entry, $team, $slug);
        }
        return $export;
    }

    /**
     * @param string $mode 'merge' ergänzt, 'replace' entfernt nicht enthaltene Mannschaften.
     */
    public static function importTeams(array $teams, $mode = 'merge') {
        if ($mode === 'replace') {
            foreach (self::getSavedTeams() as $slug => $team) {
                if (!isset($teams[$slug])) self::deleteTeam($slug);
            }
        }
        foreach ($teams as $slug => $data) {
            self::saveTeam($slug, $data);
        }
        self::flushCache();
    }

    public static function getSvgIcon() {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="fill:none !important;"><circle cx="12" cy="12" r="10" style="fill:none !important;" /><path d="M 12 2 v 1.5 M 12 20.5 v 1.5 M 2 12 h 1.5 M 20.5 12 h 1.5" style="fill:none !important;" /><path d="M 10 18 V 9 a 3 3 0 0 1 3 -3 h 1" style="fill:none !important;" /><path d="M 5 12 H 14" style="fill:none !important;" /><path d="M 14 9 H 16 A 2 2 0 0 1 18 11 H 16 A 1 1 0 0 0 16 13 H 18 A 2 2 0 0 1 16 15 H 14 Z" style="fill:none !important;" /></svg>';
    }

    public static function allowedSvgTags() {
        return array(
            'svg'    => array('xmlns' => true, 'viewbox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'style' => true, 'width' => true, 'height' => true, 'class' => true, 'aria-hidden' => true, 'focusable' => true),
            'circle' => array('cx' => true, 'cy' => true, 'r' => true, 'style' => true, 'fill' => true),
            'path'   => array('d' => true, 'style' => true, 'fill' => true),
        );
    }

    public static function getSvgIconHtml() {
        return wp_kses(self::getSvgIcon(), self::allowedSvgTags());
    }

    /**
     * URL des Einbettungs-Skripts des externen Dienstes fussball.de.
     */
    public static function widgetScriptUrl() {
        return apply_filters('fs_tm_widget_script_url', 'https://www.fussball.de/widgets.js');
    }

    /**
     * Ermöglicht Consent-Plugins, das Laden externer Inhalte komplett zu unterbinden.
     */
    public static function remoteWidgetsEnabled() {
        return (bool) apply_filters('fs_tm_load_remote_widgets', true);
    }

    public static function clickToLoadEnabled() {
        $enabled = get_option('fs_tm_click_to_load', 0) ? true : false;
        return (bool) apply_filters('fs_tm_click_to_load', $enabled);
    }

    /**
     * Opt-in für den Attributions-Hinweis im Frontend.
     *
     * Richtlinie 10 des WordPress.org-Plugin-Verzeichnisses verlangt, dass
     * „Powered by“-Hinweise und Credits standardmäßig ausgeschaltet sind und
     * nur nach ausdrücklicher Zustimmung der Website-Betreiberin erscheinen.
     */
    public static function attributionEnabled() {
        $enabled = get_option('fs_tm_show_attribution', 0) ? true : false;
        return (bool) apply_filters('fs_tm_show_attribution', $enabled);
    }

    public static function isValidUuid($uuid) {
        if (empty($uuid)) return true;
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', trim($uuid));
    }

    public static function sanitizeSingleTeam($data, $slug, &$errors = array()) {
        $name    = !empty($data['name']) ? sanitize_text_field($data['name']) : $slug;
        $page_id = isset($data['page_id']) ? intval($data['page_id']) : 0;

        $clean_periods = array();
        if (!empty($data['periods']) && is_array($data['periods'])) {
            foreach ($data['periods'] as $p_idx => $p) {
                /* translators: %d: sequential number of the period */
                $p_label   = !empty($p['label']) ? sanitize_text_field($p['label']) : sprintf(__('Zeitraum %d', 'fs-team-manager'), $p_idx + 1);
                $p_from    = !empty($p['valid_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($p['valid_from'])) ? trim($p['valid_from']) : '';
                $p_to      = !empty($p['valid_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($p['valid_to'])) ? trim($p['valid_to']) : '';
                $p_matches = !empty($p['id_matches']) ? trim(sanitize_text_field($p['id_matches'])) : '';
                $p_table   = !empty($p['id_table']) ? trim(sanitize_text_field($p['id_table'])) : '';
                $p_club    = !empty($p['is_club_matches']) ? 1 : 0;

                if (!empty($p_matches) && !self::isValidUuid($p_matches)) {
                    /* translators: 1: period label, 2: entered value */
                    $errors[] = sprintf(__('Ungültige Spielplan-ID im Zeitraum „%1$s“ (%2$s).', 'fs-team-manager'), $p_label, $p_matches);
                }
                if (!empty($p_table) && !self::isValidUuid($p_table)) {
                    /* translators: 1: period label, 2: entered value */
                    $errors[] = sprintf(__('Ungültige Tabellen-ID im Zeitraum „%1$s“ (%2$s).', 'fs-team-manager'), $p_label, $p_table);
                }

                if (!empty($p_from) || !empty($p_matches) || !empty($p_table)) {
                    $clean_period = array(
                        'label'           => $p_label,
                        'valid_from'      => $p_from,
                        'valid_to'        => $p_to,
                        'id_matches'      => $p_matches,
                        'id_table'        => $p_table,
                        'is_club_matches' => $p_club
                    );
                    $clean_periods[] = apply_filters('fs_tm_sanitize_period', $clean_period, $p, $slug, $p_idx);
                }
            }
            usort($clean_periods, function($a, $b) { return strcmp($b['valid_from'], $a['valid_from']); });
        }

        self::checkPeriodsContinuity($clean_periods, $name, $errors);

        $clean_entry = array('name' => $name, 'page_id' => $page_id, 'periods' => $clean_periods);
        return apply_filters('fs_tm_sanitize_team_entry', $clean_entry, $data, $slug);
    }

    public static function checkPeriodsContinuity($periods, $team_name, &$errors = array()) {
        if (count($periods) <= 1) return;
        $sorted = $periods;
        usort($sorted, function($a, $b) { return strcmp($a['valid_from'], $b['valid_from']); });

        for ($i = 0; $i < count($sorted) - 1; $i++) {
            $curr = $sorted[$i];
            $next = $sorted[$i + 1];

            if (empty($curr['valid_to'])) {
                /* translators: 1: earlier period label, 2: following period label */
                $errors[] = sprintf(__('Zeitraum „%1$s“ benötigt ein Enddatum, da danach „%2$s“ folgt.', 'fs-team-manager'), $curr['label'], $next['label']);
            } elseif ($curr['valid_to'] >= $next['valid_from']) {
                /* translators: 1: earlier period label, 2: its end date, 3: following period label, 4: its start date */
                $errors[] = sprintf(__('Überlappung: „%1$s“ (bis %2$s) überschneidet sich mit „%3$s“ (ab %4$s).', 'fs-team-manager'), $curr['label'], $curr['valid_to'], $next['label'], $next['valid_from']);
            }
        }
    }

    public static function sanitizeTeamsArray($raw_teams, &$errors = array()) {
        $clean = array();
        if (!is_array($raw_teams)) return $clean;
        foreach ($raw_teams as $slug => $data) {
            $slug = sanitize_key($slug);
            if (empty($slug) || !is_array($data)) continue;
            $clean[$slug] = self::sanitizeSingleTeam($data, $slug, $errors);
        }
        return $clean;
    }

    public static function isPeriodActive($period, $target_date = null) {
        if ($target_date === null) $target_date = current_time('Y-m-d');
        $from = !empty($period['valid_from']) ? trim($period['valid_from']) : '';
        $to   = !empty($period['valid_to']) ? trim($period['valid_to']) : '';
        $match_from = empty($from) || ($target_date >= $from);
        $match_to   = empty($to) || ($target_date <= $to);
        return ($match_from && $match_to);
    }

    public static function getMergedPeriodsForView($team, $view = 'matches') {
        $periods = !empty($team['periods']) && is_array($team['periods']) ? $team['periods'] : array();
        $filtered = array();
        foreach ($periods as $idx => $p) {
            $uuid = ($view === 'table') ? trim($p['id_table'] ?? '') : trim($p['id_matches'] ?? '');
            if (!empty($uuid)) {
                $p['orig_idx'] = $idx;
                $filtered[] = $p;
            }
        }
        if (empty($filtered)) return array();

        usort($filtered, function($a, $b) { return strcmp($a['valid_from'], $b['valid_from']); });

        $merged = array();
        foreach ($filtered as $p) {
            $current_uuid = ($view === 'table') ? trim($p['id_table'] ?? '') : trim($p['id_matches'] ?? '');
            if (empty($merged)) {
                $p['merged_keys'] = array('p-' . $p['orig_idx']);
                $merged[] = $p;
                continue;
            }

            $last_idx = count($merged) - 1;
            $last = $merged[$last_idx];
            $last_uuid = ($view === 'table') ? trim($last['id_table'] ?? '') : trim($last['id_matches'] ?? '');

            if ($last_uuid === $current_uuid) {
                $merged[$last_idx]['valid_to'] = $p['valid_to'];
                if ($last['label'] !== $p['label']) {
                    $merged[$last_idx]['label'] = $last['label'] . ' & ' . $p['label'];
                }
                if (!empty($p['is_club_matches'])) {
                    $merged[$last_idx]['is_club_matches'] = 1;
                }
                $merged[$last_idx]['merged_keys'][] = 'p-' . $p['orig_idx'];
            } else {
                $p['merged_keys'] = array('p-' . $p['orig_idx']);
                $merged[] = $p;
            }
        }

        usort($merged, function($a, $b) { return strcmp($b['valid_from'], $a['valid_from']); });
        return $merged;
    }

    /**
     * Liefert den Index des heute gültigen Zeitraums. Greift bei Lücken zwischen
     * Zeiträumen auf den zuletzt gültigen (bzw. den nächsten künftigen) zurück.
     *
     * @return array{index:int,is_fallback:bool}|null
     */
    public static function resolvePeriodIndex($periods, $target_date = null) {
        if (empty($periods) || !is_array($periods)) return null;
        if ($target_date === null) $target_date = current_time('Y-m-d');

        foreach ($periods as $idx => $p) {
            if (self::isPeriodActive($p, $target_date)) {
                return array('index' => $idx, 'is_fallback' => false);
            }
        }

        // Absteigend sortiert: der erste bereits begonnene Zeitraum ist der zuletzt gültige.
        foreach ($periods as $idx => $p) {
            $from = !empty($p['valid_from']) ? trim($p['valid_from']) : '';
            if ($from === '' || $from <= $target_date) {
                return array('index' => $idx, 'is_fallback' => true);
            }
        }

        // Alle Zeiträume liegen in der Zukunft: den am frühesten startenden zeigen.
        $keys = array_keys($periods);
        return array('index' => end($keys), 'is_fallback' => true);
    }

    public static function getActiveTeamIds($team, $target_date = null) {
        if ($target_date === null) $target_date = current_time('Y-m-d');
        $periods = !empty($team['periods']) && is_array($team['periods']) ? $team['periods'] : array();
        if (empty($periods)) {
            return array('id_matches' => '', 'id_table' => '', 'is_club_matches' => false, 'period_label' => __('Kein Zeitraum', 'fs-team-manager'), 'is_fallback' => false);
        }

        $resolved = self::resolvePeriodIndex($periods, $target_date);
        $p = $periods[$resolved['index']];

        return array(
            'id_matches'      => $p['id_matches'] ?? '',
            'id_table'        => $p['id_table'] ?? '',
            'is_club_matches' => !empty($p['is_club_matches']),
            'period_label'    => $p['label'] ?? __('Aktueller Zeitraum', 'fs-team-manager'),
            'is_fallback'     => $resolved['is_fallback'],
        );
    }

    public static function clearPagesCache() {
        delete_transient('fs_tm_pages_tree');
    }

    public static function getPagesTree() {
        $cached = get_transient('fs_tm_pages_tree');
        if ($cached !== false && is_array($cached)) return $cached;
        $pages = get_pages(array('sort_column' => 'post_title', 'sort_order' => 'ASC', 'hierarchical' => 0, 'post_status' => array('publish', 'draft')));
        $with_block = array(); $without_block = array();
        $plugin_blocks = apply_filters('fs_tm_recognized_blocks', array('fstm/fussball-widget', 'fstm/fussball-tables-overview'));

        if ($pages) {
            foreach ($pages as $p) {
                $has_block = false;
                foreach ($plugin_blocks as $block_name) {
                    if (has_block($block_name, $p->post_content)) { $has_block = true; break; }
                }
                $item = array('id' => $p->ID, 'title' => $p->post_title);
                if ($has_block) { $with_block[] = $item; } else { $without_block[] = $item; }
            }
        }
        $result = array('with_block' => $with_block, 'without_block' => $without_block);
        set_transient('fs_tm_pages_tree', $result, 12 * HOUR_IN_SECONDS);
        return $result;
    }
}
