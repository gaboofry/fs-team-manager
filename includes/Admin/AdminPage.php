<?php
namespace FabrielSoftware\TeamManager\Admin;

use FabrielSoftware\TeamManager\Plugin;
use FabrielSoftware\TeamManager\Data\TeamRepository;

if (!defined('ABSPATH')) exit;

class AdminPage {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'addMenu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueueAssets'));
        add_action('admin_init', array(__CLASS__, 'handleActions'));
        add_action('wp_ajax_fs_tm_save_order', array(__CLASS__, 'ajaxSaveOrder'));
        add_action('wp_ajax_fs_tm_get_team_form', array(__CLASS__, 'ajaxGetTeamForm'));
    }

    public static function addMenu() {
        $icon_data = 'data:image/svg+xml;base64,' . base64_encode(TeamRepository::getSvgIcon());
        $cap       = Plugin::capability();
        add_menu_page('Fabriel Software Team-Manager', 'Fabriel Software', $cap, 'fs-tm-manager', array(__CLASS__, 'renderHubPage'), $icon_data, 30);
        add_submenu_page('fs-tm-manager', 'Fabriel Software &bull; Teams &amp; Widgets', '⚽ Teams &amp; Widgets', $cap, 'fs-tm-manager', array(__CLASS__, 'renderHubPage'));
        do_action('fs_tm_admin_menu', $cap);

        // Zuletzt, damit die Einstellungen unter den Seiten der Add-ons stehen.
        add_submenu_page(
            'fs-tm-manager',
            'Fabriel Software &bull; ' . __('Einstellungen', 'fs-team-manager'),
            '⚙️ ' . __('Einstellungen', 'fs-team-manager'),
            $cap,
            SettingsPage::SLUG,
            array(SettingsPage::class, 'render')
        );
    }

    public static function enqueueAssets($hook) {
        if (strpos($hook, 'fs-tm-') === false) return;
        wp_enqueue_style('dashicons');
        wp_enqueue_script('jquery-ui-sortable');

        $css_file = FS_TM_PATH . 'build/admin.css';
        $css_ver  = file_exists($css_file) ? filemtime($css_file) : FS_TM_VERSION;
        wp_enqueue_style('fs-tm-admin-css', FS_TM_URL . 'build/admin.css', array('dashicons'), $css_ver);

        $asset_file = FS_TM_PATH . 'build/admin.asset.php';
        $asset      = file_exists($asset_file) ? require $asset_file : array('dependencies' => array(), 'version' => FS_TM_VERSION);
        $deps       = array_values(array_unique(array_merge($asset['dependencies'], array('jquery', 'jquery-ui-sortable'))));

        wp_enqueue_script('fs-tm-admin-js', FS_TM_URL . 'build/admin.js', $deps, $asset['version'], true);
        wp_set_script_translations('fs-tm-admin-js', 'fs-team-manager', FS_TM_PATH . 'languages');
        wp_localize_script('fs-tm-admin-js', 'fsTmAdminData', array(
            'ajaxUrl'         => admin_url('admin-ajax.php'),
            'nonce'           => wp_create_nonce('fs_tm_admin_nonce'),
            'widgetScriptUrl' => TeamRepository::widgetScriptUrl()
        ));
    }

    public static function ajaxSaveOrder() {
        check_ajax_referer('fs_tm_admin_nonce', 'nonce');
        if (!Plugin::currentUserCan()) {
            wp_send_json_error(array('message' => __('Keine Berechtigung.', 'fs-team-manager')));
        }
        $order = isset($_POST['order']) && is_array($_POST['order']) ? array_map('sanitize_key', wp_unslash($_POST['order'])) : array();
        TeamRepository::saveOrder($order);
        wp_send_json_success();
    }

    public static function ajaxGetTeamForm() {
        check_ajax_referer('fs_tm_admin_nonce', 'nonce');
        if (!Plugin::currentUserCan()) {
            wp_send_json_error(array('message' => __('Keine Berechtigung.', 'fs-team-manager')));
        }
        $slug = isset($_POST['team_slug']) ? sanitize_key(wp_unslash($_POST['team_slug'])) : '';
        $team = TeamRepository::getTeam($slug);
        if (empty($slug) || !$team) {
            wp_send_json_error(array('message' => __('Mannschaft nicht gefunden.', 'fs-team-manager')));
        }
        $pages_data = TeamRepository::getPagesTree();
        $today = current_time('Y-m-d');

        ob_start();
        self::renderTeamFormInner($slug, $team, $pages_data, $today);
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    public static function handleActions() {
        if (!Plugin::currentUserCan()) return;

        $get_action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';

        if ($get_action === 'fs_tm_export_teams_json') {
            ExportImport::handleExport();
        }

        if ($get_action === 'fs_tm_restore_backup') {
            ExportImport::handleRestore();
        }

        if (isset($_POST['fs_tm_import_teams_submit'])) {
            ExportImport::handleImport();
        }

        if (isset($_POST['fs_tm_add_team_submit']) && check_admin_referer('fs_tm_add_team_action', 'fs_tm_add_team_nonce')) {
            $name     = isset($_POST['new_team_name']) ? sanitize_text_field(wp_unslash($_POST['new_team_name'])) : '';
            $raw_slug = isset($_POST['new_team_slug']) ? sanitize_title(wp_unslash($_POST['new_team_slug'])) : '';
            $slug     = !empty($raw_slug) ? $raw_slug : sanitize_title($name);
            $page_id  = isset($_POST['new_team_page']) ? intval(wp_unslash($_POST['new_team_page'])) : 0;

            if (!empty($slug) && !empty($name)) {
                if (TeamRepository::getTeam($slug)) {
                    Notices::redirect('fs-tm-manager', array('error' => 'slug_exists'));
                }
                $current_year = intval(current_time('Y'));
                $default_period = array(
                    /* translators: 1: start year, 2: end year */
                    'label'           => sprintf(__('Zeitraum %1$d/%2$d', 'fs-team-manager'), $current_year, $current_year + 1),
                    'valid_from'      => $current_year . '-07-01',
                    'valid_to'        => ($current_year + 1) . '-06-30',
                    'id_matches'      => '',
                    'id_table'        => '',
                    'is_club_matches' => 0
                );
                TeamRepository::saveTeam($slug, array('name' => $name, 'page_id' => $page_id, 'periods' => array($default_period)));
                TeamRepository::clearPagesCache();
                Notices::redirect('fs-tm-manager', array('opened' => $slug, 'created' => 1));
            } else {
                Notices::redirect('fs-tm-manager', array('error' => 'empty_name'));
            }
        }

        if (isset($_GET['delete_team'])) {
            $del_slug = sanitize_key(wp_unslash($_GET['delete_team']));
            check_admin_referer('fs_tm_delete_team_' . $del_slug);
            TeamRepository::deleteTeam($del_slug);
            TeamRepository::clearPagesCache();
            Notices::redirect('fs-tm-manager', array('deleted' => 1));
        }

        if (isset($_POST['fs_tm_save_team_single'])) {
            $slug = isset($_POST['team_slug']) ? sanitize_key(wp_unslash($_POST['team_slug'])) : '';
            if (!empty($slug) && check_admin_referer('fs_tm_edit_team_' . $slug, 'fs_tm_nonce')) {
                $raw_data = isset($_POST['team']) && is_array($_POST['team']) ? map_deep( wp_unslash($_POST['team']), 'sanitize_text_field' ) : array();
                $errors   = array();
                $clean_entry = TeamRepository::sanitizeSingleTeam($raw_data, $slug, $errors);

                if (!empty($errors)) {
                    set_transient('fs_tm_edit_errors_' . $slug, $errors, 60);
                    set_transient('fs_tm_temp_draft_' . $slug, $raw_data, 60);
                    Notices::redirect('fs-tm-manager', array('opened' => $slug, 'error' => 'validation_failed'));
                }
                delete_transient('fs_tm_temp_draft_' . $slug);
                TeamRepository::saveTeam($slug, $clean_entry);
                TeamRepository::clearPagesCache();

                // Add-ons speichern hier ihre eigenen Felder aus demselben Formular.
                do_action('fs_tm_team_form_saved', $slug, $raw_data);

                Notices::redirect('fs-tm-manager', array('opened' => $slug, 'saved' => 1));
            }
        }
    }

    public static function renderHubPage() {
        $teams       = TeamRepository::getSavedTeams();
        $pages_data  = TeamRepository::getPagesTree();
        $today       = current_time('Y-m-d');
        // Reiner Anzeigeparameter: steuert nur, welche Karte geöffnet dargestellt wird.
        // Er stammt ausschließlich aus dem eigenen Redirect und wird über dessen Nonce geprüft.
        $fs_tm_nonce = isset($_GET[Notices::NONCE_FIELD]) ? sanitize_text_field(wp_unslash($_GET[Notices::NONCE_FIELD])) : '';
        $opened_slug = '';
        if ($fs_tm_nonce !== '' && wp_verify_nonce($fs_tm_nonce, Notices::NONCE_ACTION)) {
            $opened_slug = isset($_GET['opened']) ? sanitize_key(wp_unslash($_GET['opened'])) : '';
        }

        Layout::open('fs-tm-manager');
        Notices::render();
        ?>

            <!-- 1. Neue Mannschaft anlegen (Akkordeon, Standard: eingeklappt) -->
            <div class="postbox fs-tm-box fs-tm-collapsible-box is-collapsed" id="fs-tm-add-box">
                <div class="fs-tm-collapsible-header" title="<?php esc_attr_e('Klicken zum Auf-/Zuklappen', 'fs-team-manager'); ?>">
                    <div class="fs-tm-collapsible-title">
                        <span class="dashicons dashicons-arrow-down-alt2 fs-tm-accordion-arrow"></span>
                        <h2>➕ <?php esc_html_e('Neue Mannschaft anlegen', 'fs-team-manager'); ?></h2>
                    </div>
                    <span class="fs-tm-collapsible-hint"><?php esc_html_e('Klicken zum Öffnen', 'fs-team-manager'); ?></span>
                </div>
                <div class="fs-tm-collapsible-body">
                    <form method="post" action="">
                        <?php wp_nonce_field('fs_tm_add_team_action', 'fs_tm_add_team_nonce'); ?>
                        <div class="fs-tm-add-grid">
                            <div class="fs-tm-field-group">
                                <label for="new_team_name">
                                    <?php esc_html_e('Mannschaftsname', 'fs-team-manager'); ?>
                                    <span class="fs-tm-help-tip" data-tip="<?php esc_attr_e('Vollständiger Name des Teams, z. B. 1. Mannschaft oder B-Jugend.', 'fs-team-manager'); ?>">?</span>
                                </label>
                                <input type="text" id="new_team_name" name="new_team_name" placeholder="<?php esc_attr_e('z. B. 1. Mannschaft', 'fs-team-manager'); ?>" class="fs-tm-input" required>
                            </div>
                            <div class="fs-tm-field-group">
                                <label for="new_team_slug">
                                    <?php esc_html_e('Kürzel (Slug)', 'fs-team-manager'); ?>
                                    <span class="fs-tm-help-tip" data-tip="<?php esc_attr_e('Eindeutige technische Kennung (z. B. 1-mannschaft). Bleibt es leer, wird es automatisch erzeugt.', 'fs-team-manager'); ?>">?</span>
                                </label>
                                <input type="text" id="new_team_slug" name="new_team_slug" placeholder="<?php esc_attr_e('z. B. 1-mannschaft', 'fs-team-manager'); ?>" class="fs-tm-input">
                            </div>
                            <div class="fs-tm-field-group">
                                <label for="new_team_page">
                                    <?php esc_html_e('Zugeordnete Seite', 'fs-team-manager'); ?>
                                    <span class="fs-tm-help-tip" data-tip="<?php esc_attr_e('Auf dieser Unterseite erkennt der Gutenberg-Block das Team automatisch. Stellen Sie dazu im Block Backend „Automatisch (anhand Seite)“ ein.', 'fs-team-manager'); ?>">?</span>
                                </label>
                                <select id="new_team_page" name="new_team_page" class="fs-tm-select">
                                    <?php
echo wp_kses(
    self::renderPageOptions(0, $pages_data),
    array(
        'option'   => array( 'value' => true, 'selected' => true ),
        'optgroup' => array( 'label' => true ),
    )
);
?>
                                </select>
                            </div>
                            <div class="fs-tm-btn-wrap">
                                <input type="submit" name="fs_tm_add_team_submit" class="button fs-tm-btn-primary" value="+ <?php esc_attr_e('Mannschaft anlegen', 'fs-team-manager'); ?>">
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 2. Suchleiste -->
            <div class="fs-tm-toolbar">
                <div class="fs-tm-search-wrap">
                    <span class="dashicons dashicons-search"></span>
                    <input type="text" id="fs-tm-search" placeholder="<?php esc_attr_e('Mannschaften durchsuchen...', 'fs-team-manager'); ?>" class="fs-tm-input">
                </div>
                <?php do_action('fs_tm_admin_toolbar'); ?>
            </div>

            <!-- 3. Teams-Akkordeon Liste mit Lazy Loading -->
            <div id="fs-tm-team-sortable" class="fs-tm-team-accordion-list">
                <?php if (empty($teams)): ?>
                    <div class="fs-tm-empty-notice postbox fs-tm-box">
                        <p><?php esc_html_e('Noch keine Mannschaften angelegt. Nutze das Formular oben, um das erste Team anzulegen!', 'fs-team-manager'); ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($teams as $slug => $team): 
                        $delete_url   = wp_nonce_url(admin_url('admin.php?page=fs-tm-manager&delete_team=' . urlencode($slug)), 'fs_tm_delete_team_' . $slug);
                        $is_team_open = ($opened_slug === $slug);
                    ?>
                        <div class="fs-tm-team-card fs-tm-collapsible-box <?php echo $is_team_open ? 'is-expanded' : 'is-collapsed'; ?>" data-slug="<?php echo esc_attr($slug); ?>">
                            <div class="fs-tm-team-header" title="<?php esc_attr_e('Klicken zum Öffnen/Schließen', 'fs-team-manager'); ?>">
                                <div class="fs-tm-team-header-left">
                                    <span class="dashicons dashicons-menu fs-tm-drag-handle" title="<?php esc_attr_e('Ziehen zum Sortieren', 'fs-team-manager'); ?>"></span>
                                    <span class="dashicons dashicons-arrow-down-alt2 fs-tm-accordion-arrow"></span>
                                    <strong class="fs-tm-team-title-text"><?php echo esc_html($team['name']); ?></strong>
                                    <span class="slug-info"><code><?php echo esc_html($slug); ?></code></span>
                                </div>
                                <div class="fs-tm-team-header-right">
                                    <a href="<?php echo esc_url($delete_url); ?>" class="button button-small button-link-delete fs-tm-btn-del" title="<?php esc_attr_e('Mannschaft löschen', 'fs-team-manager'); ?>" data-fs-tm-confirm="<?php echo esc_attr(sprintf(/* translators: %s: team name */ __('Mannschaft „%s" wirklich löschen?', 'fs-team-manager'), $team['name'])); ?>">🗑️</a>
                                </div>
                            </div>

                            <div class="fs-tm-team-body" data-loaded="<?php echo $is_team_open ? '1' : '0'; ?>" <?php echo $is_team_open ? '' : 'style="display:none;"'; ?>>
                                <?php if ($is_team_open): ?>
                                    <?php self::renderTeamFormInner($slug, $team, $pages_data, $today); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Modal Overlay für Widget-Live-Test / Inspector -->
            <div id="fs-tm-preview-modal" class="fs-tm-modal-overlay" style="display:none;" aria-hidden="true">
                <div class="fs-tm-modal-dialog">
                    <div class="fs-tm-modal-header">
                        <div class="fs-tm-modal-title">
                            <span class="fs-tm-modal-icon">👁️</span>
                            <h3><?php esc_html_e('Widget-Inspector', 'fs-team-manager'); ?></h3>
                        </div>
                        <div class="fs-tm-modal-devices">
                            <button type="button" class="button fs-tm-device-btn is-active" data-device="desktop" title="<?php esc_attr_e('Desktop (100%)', 'fs-team-manager'); ?>">💻 <span>Desktop</span></button>
                            <button type="button" class="button fs-tm-device-btn" data-device="tablet" title="<?php esc_attr_e('Tablet (640px)', 'fs-team-manager'); ?>">📱 <span>Tablet</span></button>
                            <button type="button" class="button fs-tm-device-btn" data-device="mobile" title="<?php esc_attr_e('Smartphone (360px)', 'fs-team-manager'); ?>">📲 <span>Mobile</span></button>
                        </div>
                        <button type="button" class="fs-tm-modal-close" title="<?php esc_attr_e('Schließen', 'fs-team-manager'); ?>">&times;</button>
                    </div>
                    <div class="fs-tm-modal-meta">
                        <div><strong><?php esc_html_e('Typ:', 'fs-team-manager'); ?></strong> <code id="fs-tm-preview-type-badge">-</code></div>
                        <div><strong><?php esc_html_e('UUID:', 'fs-team-manager'); ?></strong> <code id="fs-tm-preview-uuid-badge">-</code></div>
                    </div>
                    <div class="fs-tm-modal-body">
                        <div class="fs-tm-modal-frame-wrapper" data-device="desktop">
                            <div id="fs-tm-preview-stage" class="fs-tm-preview-stage"></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        Layout::close();
    }

    /**
     * Übernimmt die verworfene Eingabe eines fehlgeschlagenen Speichervorgangs,
     * damit der Nutzer seine Korrekturen nicht neu eintippen muss.
     */
    private static function applyDraft($team, $draft) {
        if (isset($draft['name'])) {
            $team['name'] = sanitize_text_field($draft['name']);
        }
        if (isset($draft['page_id'])) {
            $team['page_id'] = intval($draft['page_id']);
        }

        $periods = array();
        if (!empty($draft['periods']) && is_array($draft['periods'])) {
            foreach ($draft['periods'] as $p) {
                if (!is_array($p)) continue;
                $periods[] = array(
                    'label'           => sanitize_text_field($p['label'] ?? ''),
                    'valid_from'      => sanitize_text_field($p['valid_from'] ?? ''),
                    'valid_to'        => sanitize_text_field($p['valid_to'] ?? ''),
                    'id_matches'      => sanitize_text_field($p['id_matches'] ?? ''),
                    'id_table'        => sanitize_text_field($p['id_table'] ?? ''),
                    'is_club_matches' => !empty($p['is_club_matches']) ? 1 : 0,
                );
            }
        }
        $team['periods'] = $periods;

        return $team;
    }

    public static function renderTeamFormInner($slug, $team, $pages_data, $today) {
        $draft = get_transient('fs_tm_temp_draft_' . $slug);
        if (is_array($draft)) {
            delete_transient('fs_tm_temp_draft_' . $slug);
            $team = self::applyDraft($team, $draft);
        }

        $errors = get_transient('fs_tm_edit_errors_' . $slug);
        if (!empty($errors) && is_array($errors)) {
            delete_transient('fs_tm_edit_errors_' . $slug);
        } else {
            $errors = array();
        }

        $periods = !empty($team['periods']) && is_array($team['periods']) ? $team['periods'] : array();
        ?>
        <form method="post" action="" class="fs-tm-team-form">
            <?php wp_nonce_field('fs_tm_edit_team_' . $slug, 'fs_tm_nonce'); ?>
            <input type="hidden" name="team_slug" value="<?php echo esc_attr($slug); ?>">

            <?php if (!empty($errors)): ?>
                <div class="notice notice-error fs-tm-form-errors">
                    <p><strong><?php esc_html_e('Die letzte Speicherung wurde abgebrochen:', 'fs-team-manager'); ?></strong></p>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo esc_html(wp_strip_all_tags($error)); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php do_action('fs_tm_team_edit_sections_before', $slug, $team); ?>

            <!-- Unter-Akkordeon 1: Stammdaten & Zuordnung -->
            <div class="postbox fs-tm-box fs-tm-section-box is-expanded" data-fs-tm-section="core-basics">
                <div class="fs-tm-section-header" title="<?php esc_attr_e('Klicken zum Auf-/Zuklappen', 'fs-team-manager'); ?>">
                    <div class="fs-tm-section-header-left">
                        <span class="dashicons dashicons-arrow-down-alt2 fs-tm-accordion-arrow"></span>
                        <h2>⚽ <?php esc_html_e('Stammdaten & Zuordnung', 'fs-team-manager'); ?></h2>
                    </div>
                </div>
                <div class="fs-tm-section-body">
                    <div class="fs-tm-form-grid">
                        <div class="fs-tm-field-group">
                            <label>
                                <?php esc_html_e('Mannschaftsname:', 'fs-team-manager'); ?>
                                <span class="fs-tm-help-tip" data-tip="<?php esc_attr_e('Offizieller Name der Mannschaft auf der Vereinswebsite.', 'fs-team-manager'); ?>">?</span>
                            </label>
                            <input type="text" name="team[name]" value="<?php echo esc_attr($team['name']); ?>" class="fs-tm-input bold-input fs-tm-team-name-input" required>
                        </div>
                        <div class="fs-tm-field-group">
                            <label>
                                <?php esc_html_e('Zugeordnete WordPress-Seite:', 'fs-team-manager'); ?>
                                <span class="fs-tm-help-tip" data-tip="<?php esc_attr_e('Auf dieser Unterseite erkennt der Gutenberg-Block das Team automatisch. Stellen Sie dazu im Block Backend „Automatisch (anhand Seite)“ ein.', 'fs-team-manager'); ?>">?</span>
                            </label>
                            <select name="team[page_id]" class="fs-tm-select">
                                <?php
echo wp_kses(
    self::renderPageOptions(intval($team['page_id'] ?? 0), $pages_data),
    array(
        'option'   => array( 'value' => true, 'selected' => true ),
        'optgroup' => array( 'label' => true ),
    )
);
?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unter-Akkordeon 2: Spielpläne & Tabellen -->
            <div class="postbox fs-tm-box fs-tm-section-box is-collapsed" data-fs-tm-section="core-periods">
                <div class="fs-tm-section-header" title="<?php esc_attr_e('Klicken zum Auf-/Zuklappen', 'fs-team-manager'); ?>">
                    <div class="fs-tm-section-header-left">
                        <span class="dashicons dashicons-arrow-down-alt2 fs-tm-accordion-arrow"></span>
                        <h2>
                            📅 <?php esc_html_e('Spielpläne & Tabellen', 'fs-team-manager'); ?>
                            <span class="fs-tm-help-tip" data-tip="<?php esc_attr_e('Klicke auf einen Zeitraum-Balken, um Details auf- oder zuzuklappen. Vergangene Zeiträume sind standardmäßig eingeklappt.', 'fs-team-manager'); ?>">?</span>
                        </h2>
                    </div>
                    <button type="button" class="button fs-tm-btn-add-period" title="<?php esc_attr_e('Neuen Zeitraum hinzufügen', 'fs-team-manager'); ?>">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <span class="fs-tm-btn-add-period-text"><?php esc_html_e('Neuen Zeitraum hinzufügen', 'fs-team-manager'); ?></span>
                    </button>
                </div>
                <div class="fs-tm-section-body">
                    <div class="fs-tm-periods-container">
                        <?php if (empty($periods)): ?>
                            <div class="fs-tm-no-periods-msg">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <p><?php echo wp_kses_post(__('Noch kein Zeitraum hinterlegt. Klicke auf <strong>„+ Neuen Zeitraum hinzufügen“</strong>, um Spielplan- und Tabellen-IDs einzutragen.', 'fs-team-manager')); ?></p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($periods as $p_idx => $p): 
                                $p_from = $p['valid_from'] ?? '';
                                $p_to   = $p['valid_to'] ?? '';
                                $is_act = TeamRepository::isPeriodActive($p, $today);
                                $is_past = (!empty($p_to) && $p_to < $today && !$is_act);
                                $is_future = (!empty($p_from) && $p_from > $today && !$is_act);
                                $p_matches = $p['id_matches'] ?? '';
                                $p_table   = $p['id_table'] ?? '';
                                $p_is_club = !empty($p['is_club_matches']);
                                $matches_invalid = !empty($p_matches) && !TeamRepository::isValidUuid($p_matches);
                                $table_invalid   = !empty($p_table) && !TeamRepository::isValidUuid($p_table);
                                /* translators: %d: sequential number of the period */
                                $p_label_display = !empty($p['label']) ? $p['label'] : sprintf(__('Zeitraum %d', 'fs-team-manager'), $p_idx + 1);

                                $card_classes = array('fs-tm-period-card');
                                if ($is_act) { $card_classes[] = 'is-active-period'; }
                                if ($is_past) { $card_classes[] = 'is-collapsed'; }
                                else { $card_classes[] = 'is-expanded'; }
                            ?>
                                <div class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
                                    <div class="fs-tm-period-card-header" title="<?php esc_attr_e('Klicken zum Auf-/Zuklappen', 'fs-team-manager'); ?>">
                                        <div class="fs-tm-period-card-header-left">
                                            <span class="dashicons dashicons-arrow-down-alt2 fs-tm-accordion-arrow"></span>
                                            <span class="fs-tm-period-icon">📅</span>
                                            <strong class="fs-tm-period-title-text"><?php echo esc_html($p_label_display); ?></strong>
                                            <?php if ($is_act): ?>
                                                <span class="fs-tm-active-badge">🟢 <?php esc_html_e('Aktiver Zeitraum', 'fs-team-manager'); ?></span>
                                            <?php elseif ($is_past): ?>
                                                <span class="fs-tm-past-badge">⚪ <?php esc_html_e('Vergangen', 'fs-team-manager'); ?></span>
                                            <?php elseif ($is_future): ?>
                                                <span class="fs-tm-future-badge">🔵 <?php esc_html_e('Zukünftig', 'fs-team-manager'); ?></span>
                                            <?php endif; ?>
                                            <button type="button" class="button-link-delete fs-tm-delete-period" title="<?php esc_attr_e('Zeitraum löschen', 'fs-team-manager'); ?>">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="fs-tm-period-card-body">
                                        <div class="fs-tm-field-group fs-tm-label-row">
                                            <label>
                                                <?php esc_html_e('Bezeichnung des Zeitraums:', 'fs-team-manager'); ?>
                                                <span class="fs-tm-help-tip" data-tip="<?php esc_attr_e('Wird im Frontend-Dropdown und im Header angezeigt (z. B. Saison 2026/2027 oder Hinrunde).', 'fs-team-manager'); ?>">?</span>
                                            </label>
                                            <input type="text" name="team[periods][<?php echo esc_attr($p_idx); ?>][label]" value="<?php echo esc_attr($p['label'] ?? ''); ?>" placeholder="<?php esc_attr_e('z. B. Saison 2026/2027 oder Hinrunde', 'fs-team-manager'); ?>" class="fs-tm-input period-label-input" required>
                                        </div>

                                        <div class="fs-tm-period-dates-row">
                                            <div class="fs-tm-field-group">
                                                <label>
                                                    <?php esc_html_e('Gültig ab (Start):', 'fs-team-manager'); ?>
                                                    <span class="fs-tm-help-tip" data-tip="<?php esc_attr_e('Datum, ab dem die Website automatisch auf diesen Zeitraum umschaltet.', 'fs-team-manager'); ?>">?</span>
                                                </label>
                                                <div class="fs-tm-date-input-wrap">
                                                    <input type="date" name="team[periods][<?php echo esc_attr($p_idx); ?>][valid_from]" value="<?php echo esc_attr($p_from); ?>" class="fs-tm-input period-from-input" required>
                                                </div>
                                            </div>
                                            <div class="fs-tm-date-separator">→</div>
                                            <div class="fs-tm-field-group">
                                                <label>
                                                    <?php esc_html_e('Gültig bis (optional):', 'fs-team-manager'); ?>
                                                    <span class="fs-tm-help-tip" data-tip="<?php esc_attr_e('Enddatum. Muss nahtlos an den nächsten Zeitraum anschließen.', 'fs-team-manager'); ?>">?</span>
                                                </label>
                                                <div class="fs-tm-date-input-wrap">
                                                    <input type="date" name="team[periods][<?php echo esc_attr($p_idx); ?>][valid_to]" value="<?php echo esc_attr($p_to); ?>" class="fs-tm-input period-to-input">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="fs-tm-period-ids-row">
                                            <!-- Spielplan-ID mit Test-Button -->
                                            <div class="fs-tm-field-group">
                                                <label class="fs-tm-matches-label">
                                                    📅 <span class="fs-tm-matches-label-text"><?php echo $p_is_club ? esc_html__('Spielplan-ID des Vereins:', 'fs-team-manager') : esc_html__('Spielplan-ID der Mannschaft:', 'fs-team-manager'); ?></span>
                                                    <span class="fs-tm-help-tip" data-tip="<?php esc_attr_e('UUID aus dem Einbettungscode von fussball.de (data-type „team-matches“).', 'fs-team-manager'); ?>">?</span>
                                                </label>
                                                <div class="fs-tm-uuid-input-wrap">
                                                    <input type="text" name="team[periods][<?php echo esc_attr($p_idx); ?>][id_matches]" value="<?php echo esc_attr($p_matches); ?>" class="fs-tm-input code-font <?php echo $matches_invalid ? 'fs-tm-input-error' : ''; ?>" placeholder="z. B. 01234567-89ab-cdef-0123-456789abcdef">
                                                    <button type="button" class="button fs-tm-btn-test-uuid" data-target="matches" title="<?php esc_attr_e('Widget live testen / Vorschau', 'fs-team-manager'); ?>">
                                                        <span class="dashicons dashicons-visibility"></span>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Tabellen-ID mit Test-Button -->
                                            <div class="fs-tm-field-group">
                                                <label>
                                                    🏆 <?php esc_html_e('Tabellen-ID:', 'fs-team-manager'); ?>
                                                    <span class="fs-tm-help-tip" data-tip="<?php esc_attr_e('UUID aus dem Einbettungscode von fussball.de (data-type „table“).', 'fs-team-manager'); ?>">?</span>
                                                </label>
                                                <div class="fs-tm-uuid-input-wrap">
                                                    <input type="text" name="team[periods][<?php echo esc_attr($p_idx); ?>][id_table]" value="<?php echo esc_attr($p_table); ?>" class="fs-tm-input code-font <?php echo $table_invalid ? 'fs-tm-input-error' : ''; ?>" placeholder="z. B. 01234567-89ab-cdef-0123-456789abcdef">
                                                    <button type="button" class="button fs-tm-btn-test-uuid" data-target="table" title="<?php esc_attr_e('Widget live testen / Vorschau', 'fs-team-manager'); ?>">
                                                        <span class="dashicons dashicons-visibility"></span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="fs-tm-period-footer-row">
                                            <label class="fs-tm-checkbox-pill">
                                                <input type="checkbox" name="team[periods][<?php echo esc_attr($p_idx); ?>][is_club_matches]" value="1" <?php checked($p_is_club, true); ?> class="fs-tm-club-matches-toggle">
                                                <span><?php esc_html_e('Club-Matches', 'fs-team-manager'); ?></span>
                                                <span class="fs-tm-help-tip" data-tip="<?php esc_attr_e('Schaltet den Widget-Typ von „team-matches“ auf „club-matches“ um.', 'fs-team-manager'); ?>">?</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php do_action('fs_tm_team_edit_sections', $slug, $team); ?>

            <!-- Action Bar unten im Team-Akkordeon -->
            <div class="fs-tm-team-actions-bar">
                <input type="submit" name="fs_tm_save_team_single" class="button fs-tm-btn-save-main" value="💾 <?php esc_attr_e('Änderungen speichern', 'fs-team-manager'); ?>">
                <button type="button" class="button fs-tm-btn-reset-team"><?php esc_html_e('🔄 Änderungen verwerfen', 'fs-team-manager'); ?></button>
                <button type="button" class="button fs-tm-btn-close-team"><?php esc_html_e('✖ Zuklappen', 'fs-team-manager'); ?></button>
            </div>
        </form>
        <?php
    }

    public static function renderPageOptions($selected_id, $pages_data) {
        $html = '<option value="0">— ' . esc_html__('Keine automatische Zuordnung', 'fs-team-manager') . ' —</option>';
        if (!empty($pages_data['with_block'])) {
            $html .= '<optgroup label="⭐ ' . esc_attr__('Seiten mit Team/Fussball Block', 'fs-team-manager') . '">';
            foreach ($pages_data['with_block'] as $p) {
                $html .= '<option value="' . esc_attr($p['id']) . '" ' . selected($selected_id, $p['id'], false) . '>' . esc_html($p['title']) . '</option>';
            }
            $html .= '</optgroup>';
        }
        if (!empty($pages_data['without_block'])) {
            $html .= '<optgroup label="📄 ' . esc_attr__('Weitere Seiten', 'fs-team-manager') . '">';
            foreach ($pages_data['without_block'] as $p) {
                $html .= '<option value="' . esc_attr($p['id']) . '" ' . selected($selected_id, $p['id'], false) . '>' . esc_html($p['title']) . '</option>';
            }
            $html .= '</optgroup>';
        }
        return $html;
    }
}
