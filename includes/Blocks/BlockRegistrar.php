<?php
namespace FabrielSoftware\TeamManager\Blocks;

use FabrielSoftware\TeamManager\Data\TeamRepository;
use FabrielSoftware\TeamManager\Design\Scheme;

if (!defined('ABSPATH')) exit;

class BlockRegistrar {
    public static function init() {
        add_filter('block_categories_all', array(__CLASS__, 'addCategory'), 10, 2);
        add_action('init', array(__CLASS__, 'registerBlocks'));
    }

    public static function addCategory($categories, $block_editor_context = null) {
        return array_merge(array(array('slug' => 'fs-tm-blocks', 'title' => '⚽ Fabriel Software Team-Manager')), $categories);
    }

    /**
     * Erlaubte HTML-Elemente der Block-Ausgabe.
     *
     * Die Rückgabewerte der `render_callback`s werden von WordPress unverändert
     * ausgegeben und müssen daher vollständig maskiert sein. Der letzte Schritt vor
     * der Rückgabe ist deshalb `wp_kses()` mit genau dieser Liste.
     *
     * `wp_kses_post()` allein genügt nicht: Die Standardliste kennt weder
     * `<template>` (Lazy-Loading der inaktiven Zeiträume) noch `<select>`,
     * `<option>` oder `<input>` (Zeitraum-Auswahl und Zwei-Klick-Lösung). Diese
     * Elemente würden stillschweigend entfernt und die Karte wäre unbedienbar.
     *
     * @return array<string,array<string,bool>> Liste im Format von wp_kses_allowed_html().
     */
    public static function allowedCardHtml() {
        $allowed = wp_kses_allowed_html('post');

        $global = array(
            'class'      => true,
            'id'         => true,
            'style'      => true,
            'title'      => true,
            'hidden'     => true,
            // wp_kses() unterstützt den Platzhalter `data-*` seit WordPress 5.0
            // (siehe wp_kses_attr_check()); das Plugin verlangt mindestens 6.0.
            'data-*'     => true,
            'aria-label' => true,
            'aria-hidden' => true,
        );

        $allowed['template'] = $global;
        $allowed['select']   = array_merge($global, array(
            'name'     => true,
            'disabled' => true,
            'multiple' => true,
            'size'     => true,
            'required' => true,
        ));
        $allowed['option'] = array_merge($global, array(
            'value'    => true,
            'selected' => true,
            'label'    => true,
            'disabled' => true,
        ));
        $allowed['optgroup'] = array_merge($global, array(
            'label'    => true,
            'disabled' => true,
        ));
        $allowed['input'] = array_merge($global, array(
            'type'        => true,
            'name'        => true,
            'value'       => true,
            'checked'     => true,
            'disabled'    => true,
            'readonly'    => true,
            'placeholder' => true,
        ));

        /**
         * Öffentlicher Erweiterungs-Punkt (public extension point).
         *
         * Add-ons, die über die Karten-Filter zusätzliche Elemente ausgeben,
         * ergänzen die Liste hierüber. Ausschließlich unbedenkliche Elemente
         * aufnehmen – die Liste ist die letzte Verteidigungslinie vor der Ausgabe.
         *
         * @param array $allowed Liste im Format von wp_kses_allowed_html().
         */
        return apply_filters('fs_tm_allowed_card_html', $allowed);
    }

    /**
     * Maskiert die fertige Block-Ausgabe abschließend.
     */
    private static function kses($html) {
        return wp_kses((string) $html, self::allowedCardHtml());
    }

    /**
     * Registriert die Frontend-Assets als Handles. Die block.json referenziert sie
     * über "style" / "viewScript", damit WordPress sie nur bei Bedarf ausliefert.
     */
    private static function registerFrontendAssets() {
        // Einbindung exakt nach Vorgabe von fussball.de: unveränderte Skript-URL.
        wp_register_script('fussballde-widgets', TeamRepository::widgetScriptUrl(), array(), FS_TM_VERSION, true);

        $css_file = FS_TM_PATH . 'build/frontend.css';
        $css_ver  = file_exists($css_file) ? filemtime($css_file) : FS_TM_VERSION;
        wp_register_style('fs-tm-frontend-css', FS_TM_URL . 'build/frontend.css', array(), $css_ver);

        $asset_file = FS_TM_PATH . 'build/frontend.asset.php';
        $asset      = file_exists($asset_file) ? require $asset_file : array('dependencies' => array(), 'version' => FS_TM_VERSION);
        wp_register_script('fs-tm-frontend-js', FS_TM_URL . 'build/frontend.js', $asset['dependencies'], $asset['version'], true);
        wp_localize_script('fs-tm-frontend-js', 'fsTmFrontend', array(
            'widgetScriptUrl'  => TeamRepository::widgetScriptUrl(),
            'clickToLoad'      => TeamRepository::clickToLoadEnabled(),
            'showAttribution'  => TeamRepository::attributionEnabled(),
            // Zeichenketten, die das Skript selbst erzeugt (Ladeanzeige und
            // der nach einem Widerruf neu aufgebaute Hinweis).
            'i18n'             => array(
                'loading'             => __('Inhalte werden geladen …', 'fs-team-manager'),
                'consentText'         => __('An dieser Stelle wird ein Inhalt des externen Anbieters fussball.de eingebunden. Beim Laden werden Daten – unter anderem Ihre IP-Adresse – an den Anbieter übertragen.', 'fs-team-manager'),
                'consentRemember'     => __('Auswahl für diesen Browser merken', 'fs-team-manager'),
                'consentButton'       => __('Inhalt laden', 'fs-team-manager'),
                'consentProviderLink' => __('Zur Website des Anbieters', 'fs-team-manager'),
            ),
        ));
    }

    /**
     * Registriert die gemeinsame Farbsteuerung des Blockeditors.
     *
     * Das Skript stellt `window.fsTmDesign` bereit — dieselbe Farbmathematik wie
     * `Support\Contrast` in PHP. Add-ons binden das Handle ein, statt die Berechnung
     * ein zweites Mal umzusetzen.
     */
    public static function designScriptHandle() {
        return 'fs-tm-design-js';
    }

    private static function registerDesignScript() {
        $handle = self::designScriptHandle();
        if (wp_script_is($handle, 'registered')) return;

        $asset_file = FS_TM_PATH . 'build/design.asset.php';
        $asset      = file_exists($asset_file) ? require $asset_file : array('dependencies' => array(), 'version' => FS_TM_VERSION);

        wp_register_script($handle, FS_TM_URL . 'build/design.js', $asset['dependencies'], $asset['version'], true);
        wp_set_script_translations($handle, 'fs-team-manager', FS_TM_PATH . 'languages');
        wp_localize_script($handle, 'fsTmDesignData', array(
            'central' => Scheme::central(),
            'presets' => Scheme::presets(),
        ));
    }

    public static function registerBlocks() {
        self::registerFrontendAssets();
        self::registerDesignScript();

        $teams = TeamRepository::getSavedTeams();
        $teams_data = array();
        $options = array(array('label' => '⚡ ' . __('Automatisch (anhand Seite)', 'fs-team-manager'), 'value' => 'auto', 'cleanName' => __('Automatisch (aktuelle Seite)', 'fs-team-manager')));
        
        foreach ($teams as $slug => $data) {
            $options[] = array('label' => '👉 ' . $data['name'], 'value' => $slug, 'cleanName' => $data['name']);
            $teams_data[$slug] = array(
                'name'    => $data['name'],
                'periods' => !empty($data['periods']) ? $data['periods'] : array()
            );
        }

        // 1. Spielplan/Tabellen-Block
        $widget_meta = FS_TM_PATH . 'build/blocks/fussball-widget';
        if (file_exists($widget_meta . '/block.json')) {
            register_block_type($widget_meta, array(
                'render_callback' => array(__CLASS__, 'renderSingleWidget')
            ));
            wp_set_script_translations('fstm-fussball-widget-editor-script', 'fs-team-manager', FS_TM_PATH . 'languages');
            wp_localize_script('fstm-fussball-widget-editor-script', 'fsTmBlockData', apply_filters('fs_tm_block_editor_data', array(
                'teamOptions' => $options,
                'teamsData'   => $teams_data,
                'viewOptions' => array(
                    array('label' => '📅 ' . __('Spielplan', 'fs-team-manager'), 'value' => 'matches'),
                    array('label' => '🏆 ' . __('Tabelle', 'fs-team-manager'), 'value' => 'table')
                )
            ), $teams));
        }

        // 2. Tabellen Übersicht Block
        $overview_meta = FS_TM_PATH . 'build/blocks/tables-overview';
        if (file_exists($overview_meta . '/block.json')) {
            register_block_type($overview_meta, array(
                'render_callback' => array(__CLASS__, 'renderTablesOverview')
            ));
            wp_set_script_translations('fstm-fussball-tables-overview-editor-script', 'fs-team-manager', FS_TM_PATH . 'languages');
        }

        do_action('fs_tm_register_addon_blocks');
    }

    /**
     * Erzeugt den Widget-Container. Ohne Zwei-Klick-Lösung entspricht die Ausgabe
     * exakt dem Einbettungscode von fussball.de.
     */
    private static function renderWidgetSlot($widget_id, $widget_type) {
        if (empty($widget_id)) {
            return '<p class="fs-tm-no-data">' . esc_html__('Für diesen Zeitraum ist kein Widget hinterlegt.', 'fs-team-manager') . '</p>';
        }
        if (!TeamRepository::remoteWidgetsEnabled()) {
            return '<p class="fs-tm-no-data">' . esc_html__('Das Laden externer Inhalte ist auf dieser Website deaktiviert.', 'fs-team-manager') . '</p>';
        }

        if (!TeamRepository::clickToLoadEnabled()) {
            wp_enqueue_script('fussballde-widgets');
            return sprintf(
                '<div class="fussballde_widget" data-id="%s" data-type="%s" style="width: 100%%"></div>',
                esc_attr($widget_id),
                esc_attr($widget_type)
            );
        }

        // Zwei-Klick-Lösung: Der identische Container wird erst nach Bestätigung eingefügt.
        // Reihenfolge der Bedienelemente: erst die Auswahl „merken“, dann die
        // Schaltfläche, die sie ausführt.
        //
        // Das Skript wertet die gespeicherte Einwilligung aus. Es wird hier ausdrücklich
        // angefordert, weil diese Methode auch über renderSingleWidget() aus Add-ons
        // aufgerufen wird — dort greift das viewScript der Core-block.json nicht.
        wp_enqueue_script('fs-tm-frontend-js');
        wp_enqueue_style('fs-tm-frontend-css');

        $html  = sprintf(
            '<div class="fs-tm-widget-slot" data-widget-id="%s" data-widget-type="%s">',
            esc_attr($widget_id),
            esc_attr($widget_type)
        );
        $html .= '<div class="fs-tm-consent">';
        $html .= '<span class="fs-tm-consent-icon" aria-hidden="true">🔒</span>';
        $html .= '<p class="fs-tm-consent-text">' . esc_html__('An dieser Stelle wird ein Inhalt des externen Anbieters fussball.de eingebunden. Beim Laden werden Daten – unter anderem Ihre IP-Adresse – an den Anbieter übertragen.', 'fs-team-manager') . '</p>';
        $html .= '<label class="fs-tm-consent-remember"><input type="checkbox"> ' . esc_html__('Auswahl für diesen Browser merken', 'fs-team-manager') . '</label>';
        $html .= '<button type="button" class="fs-tm-consent-btn">' . esc_html__('Inhalt laden', 'fs-team-manager') . '</button>';
        $html .= sprintf(
            '<a class="fs-tm-consent-link" href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
            esc_url('https://www.fussball.de/'),
            esc_html__('Zur Website des Anbieters', 'fs-team-manager')
        );
        $html .= '</div></div>';

        return $html;
    }

    private static function fallbackNoteHtml($period_label) {
        return sprintf(
            '<span class="fs-tm-card-note" title="%s">%s</span>',
            esc_attr(sprintf(
                /* translators: %s: label of the displayed period */
                __('Für den heutigen Tag ist kein Zeitraum hinterlegt. Angezeigt wird der zuletzt gepflegte Zeitraum: %s', 'fs-team-manager'),
                $period_label
            )),
            esc_html__('Letzter Zeitraum', 'fs-team-manager')
        );
    }

    private static function resolveTeam($slug) {
        $teams = TeamRepository::getSavedTeams();
        if ($slug === 'auto') {
            $current_id = get_queried_object_id();
            if (!$current_id) $current_id = get_the_ID();
            if ($current_id) {
                foreach ($teams as $t_slug => $t_data) {
                    if (isset($t_data['page_id']) && intval($t_data['page_id']) === intval($current_id)) return array($t_slug, $t_data);
                }
            }
        } elseif (isset($teams[$slug])) { return array($slug, $teams[$slug]); }
        return array(null, null);
    }

    public static function renderSingleWidget($attributes) {
        list($slug, $matched_team) = self::resolveTeam($attributes['team'] ?? 'auto');
        $view = $attributes['view'] ?? 'matches';
        if (!$matched_team) return '';

        $merged_periods = TeamRepository::getMergedPeriodsForView($matched_team, $view);
        if (empty($merged_periods)) return '';

        $today = current_time('Y-m-d');
        $target_period_key = $attributes['period'] ?? 'current';
        $active_idx  = 0;
        $is_fallback = false;

        if ($target_period_key !== 'current') {
            foreach ($merged_periods as $idx => $mp) {
                if (in_array($target_period_key, $mp['merged_keys'], true)) {
                    $active_idx = $idx;
                    break;
                }
            }
        } else {
            $resolved    = TeamRepository::resolvePeriodIndex($merged_periods, $today);
            $active_idx  = $resolved['index'];
            $is_fallback = $resolved['is_fallback'];
        }

        $available_items = array();
        foreach ($merged_periods as $idx => $mp) {
            $is_matching_today = TeamRepository::isPeriodActive($mp, $today);

            $from_fmt = !empty($mp['valid_from']) ? date_i18n('d.m.Y', strtotime($mp['valid_from'])) : '';
            $to_fmt   = !empty($mp['valid_to']) ? date_i18n('d.m.Y', strtotime($mp['valid_to'])) : '';
            $date_str = ($from_fmt && $to_fmt) ? $from_fmt . ' – ' . $to_fmt : ($from_fmt ? __('Ab', 'fs-team-manager') . ' ' . $from_fmt : '');

            $label = $mp['label'];
            if ($target_period_key === 'current' && $is_matching_today) {
                $label = __('Aktuell', 'fs-team-manager');
            } else {
                if (!empty($date_str)) {
                    $label .= ' (' . $date_str . ')';
                }
            }

            $available_items[] = array(
                'key'             => 'm-' . $idx,
                'label'           => $label,
                'raw_label'       => $mp['label'],
                'is_active'       => ($idx === $active_idx),
                'id_matches'      => $mp['id_matches'] ?? '',
                'id_table'        => $mp['id_table'] ?? '',
                'is_club_matches' => !empty($mp['is_club_matches']),
            );
        }

        // Farbschema und Übersteuerungen werden hier zu einem vollständigen Satz
        // Custom-Properties aufgelöst — einschließlich der Schriftfarben, die sich
        // aus dem Kontrast zur jeweiligen Fläche ergeben.
        $style_attr = Scheme::styleAttr($attributes);

		$page_id   = !empty($matched_team['page_id']) ? intval($matched_team['page_id']) : 0;
        $has_page  = $page_id > 0 && get_post_status($page_id) === 'publish';
        $team_name = esc_html($matched_team['name']);
        $title_html = $has_page ? sprintf('<a href="%s" class="fs-tm-card-link">%s</a>', esc_url(get_permalink($page_id)), $team_name) : '<span>' . $team_name . '</span>';

        $icon       = ($view === 'table') ? '🏆' : '📅';
        $badge_text = ($view === 'table') ? __('Tabelle', 'fs-team-manager') : __('Spielplan', 'fs-team-manager');

        $show_team_name = (bool) ($attributes['showTeamName'] ?? true);

        $output = sprintf('<div class="fs-tm-table-card fs-tm-widget-card"%s>', $style_attr);
        $output .= '  <div class="fs-tm-card-header">';
		$output .= '    <div class="fs-tm-card-header-left">';
		
        if ($show_team_name) {
            $output .= '      <span class="fs-tm-card-icon">' . $icon . '</span>';
            $output .= '      <h3 class="fs-tm-card-title">' . $title_html . '</h3>';
        }

		$output .= '      <span class="fs-tm-card-badge">' . esc_html($badge_text) . '</span>';
		$output .= '    </div>';

        $output .= '    <div class="fs-tm-card-header-right">';

        if (count($available_items) > 1) {
            $output .= '<div class="fs-tm-period-select-wrap"><select class="fs-tm-period-select" aria-label="' . esc_attr__('Zeitraum auswählen', 'fs-team-manager') . '">';
            foreach ($available_items as $item) {
                $output .= sprintf('<option value="%s"%s>%s</option>', esc_attr($item['key']), selected($item['is_active'], true, false), esc_html($item['label']));
            }
            $output .= '</select></div>';
        }

        if ($is_fallback) {
            $output .= self::fallbackNoteHtml($available_items[$active_idx]['raw_label']);
        }

        $output .= '    </div>';
        $output .= '  </div>';

        $output .= '  <div class="fs-tm-card-body">';
        /**
         * Öffentlicher Erweiterungs-Punkt (public extension point).
         *
         * Escaping contract: the callback receives the fully escaped HTML and must
         * return escaped HTML only. Do not return unescaped user or option data.
         * The returned value is sanitized with wp_kses() against
         * self::allowedCardHtml() before output.
         *
         * @filter string
         * @param string $html          Escaped HTML to prepend before the card body.
         * @param array  $matched_team  Team data (name, page_id, periods, ...).
         * @param string $slug          Team slug or 'auto'.
         * @param string $view          'matches' or 'table'.
         */
        $output .= self::kses(apply_filters('fs_tm_card_body_before', '', $matched_team, $slug, $view));
        foreach ($available_items as $item) {
            $pane_class = 'fs-tm-period-pane' . ($item['is_active'] ? ' is-active' : '');
            $widget_id   = ($view === 'table') ? trim($item['id_table'] ?? '') : trim($item['id_matches'] ?? '');
            $widget_type = ($view === 'table') ? 'table' : ($item['is_club_matches'] ? 'club-matches' : 'team-matches');

            $pane_inner = self::renderWidgetSlot($widget_id, $widget_type);

            if ($item['is_active']) {
                $output .= sprintf('<div class="%s" data-period-key="%s">%s</div>', esc_attr($pane_class), esc_attr($item['key']), $pane_inner);
            } else {
                // Lazy-Load: Inaktive Zeiträume werden erst bei der ersten Auswahl im Frontend aufgelöst.
                $output .= sprintf('<div class="%s" data-period-key="%s" data-fs-tm-lazy="1"><template>%s</template></div>', esc_attr($pane_class), esc_attr($item['key']), $pane_inner);
            }
        }
        /**
         * Öffentlicher Erweiterungs-Punkt (public extension point).
         *
         * Escaping contract: the callback receives the fully escaped HTML and must
         * return escaped HTML only. Do not return unescaped user or option data.
         * The returned value is sanitized with wp_kses() against
         * self::allowedCardHtml() before output.
         *
         * @filter string
         * @param string $html          Escaped HTML to append after the card body.
         * @param array  $matched_team  Team data (name, page_id, periods, ...).
         * @param string $slug          Team slug or 'auto'.
         * @param string $view          'matches' or 'table'.
         */
        $output .= self::kses(apply_filters('fs_tm_card_body_after', '', $matched_team, $slug, $view));
        $output .= '  </div></div>';

        /**
         * Öffentlicher Erweiterungs-Punkt (public extension point).
         *
         * Escaping contract: the callback receives the fully escaped HTML and must
         * return escaped HTML only. Do not return unescaped user or option data.
         * The returned value is sanitized with wp_kses() against
         * self::allowedCardHtml() before output.
         *
         * @filter string
         * @param string $output       Fully escaped card HTML.
         * @param array  $matched_team Team data (name, page_id, periods, ...).
         * @param string $slug         Team slug or 'auto'.
         * @param array  $attributes   Block attributes.
         */
        return self::kses(apply_filters('fs_tm_widget_card_html', $output, $matched_team, $slug, $attributes));
    }

    public static function renderTablesOverview($attributes = array()) {
        $teams = TeamRepository::getSavedTeams();
        $cols  = isset($attributes['columns']) && in_array($attributes['columns'], array('1', '2', '3'), true) ? $attributes['columns'] : '2';

        $grouped_tables = array();
        foreach ($teams as $slug => $data) {
            $active_ids = TeamRepository::getActiveTeamIds($data);
            $raw_table  = trim($active_ids['id_table'] ?? '');
            if (!empty($raw_table)) {
                if (!isset($grouped_tables[$raw_table])) {
                    $grouped_tables[$raw_table] = array(
                        'id_table'     => $raw_table,
                        'teams'        => array($data),
                        'is_fallback'  => !empty($active_ids['is_fallback']),
                        'period_label' => $active_ids['period_label'],
                    );
                } else {
                    $grouped_tables[$raw_table]['teams'][] = $data;
                }
            }
        }

        if (empty($grouped_tables)) return '';

        $style_attr = Scheme::styleAttr($attributes);

        $output = sprintf('<div class="fs-tm-tables-grid fs-tm-cols-%s"%s>', esc_attr($cols), $style_attr);

        foreach ($grouped_tables as $table_id => $group) {
            $title_lines = array();
            foreach ($group['teams'] as $team_data) {
                $page_id   = !empty($team_data['page_id']) ? intval($team_data['page_id']) : 0;
                $has_page  = $page_id > 0 && get_post_status($page_id) === 'publish';
                $team_name = esc_html($team_data['name']);
                if ($has_page) {
                    $title_lines[] = sprintf('<span class="fs-tm-card-title-line"><a href="%s" class="fs-tm-card-link">%s</a></span>', esc_url(get_permalink($page_id)), $team_name);
                } else {
                    $title_lines[] = sprintf('<span class="fs-tm-card-title-line">%s</span>', $team_name);
                }
            }

            $output .= '<div class="fs-tm-table-card">';
            $output .= '  <div class="fs-tm-card-header">';
            $output .= '    <div class="fs-tm-card-header-left">';
            $output .= '      <span class="fs-tm-card-icon">🏆</span>';
            $output .= '      <h3 class="fs-tm-card-title">' . implode('', $title_lines) . '</h3>';
            $output .= '    </div>';
            if (!empty($group['is_fallback'])) {
                $output .= '    <div class="fs-tm-card-header-right">' . self::fallbackNoteHtml($group['period_label']) . '</div>';
            }
            $output .= '  </div>';
            $output .= '  <div class="fs-tm-card-body">';
            $output .= self::renderWidgetSlot($table_id, 'table');
            $output .= '  </div>';
            $output .= '</div>';
        }
        $output .= '</div>';
        /**
         * Öffentlicher Erweiterungs-Punkt (public extension point).
         *
         * Escaping contract: the callback receives the fully escaped HTML and must
         * return escaped HTML only. Do not return unescaped user or option data.
         * The returned value is sanitized with wp_kses() against
         * self::allowedCardHtml() before output.
         *
         * @filter string
         * @param string $output        Fully escaped tables-overview HTML.
         * @param array  $grouped_tables Grouped table data keyed by table widget ID.
         * @param array  $attributes     Block attributes.
         */
        return self::kses(apply_filters('fs_tm_tables_overview_html', $output, $grouped_tables, $attributes));
    }
}
