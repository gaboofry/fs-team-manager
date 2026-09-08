<?php
namespace FabrielSoftware\TeamManager\Admin;

use FabrielSoftware\TeamManager\Data\TeamRepository;

if (!defined('ABSPATH')) exit;

/**
 * Gemeinsamer Rahmen aller Verwaltungsseiten.
 *
 * Eine Leiste aus Marke, Navigation und Support, darunter der austauschbare Bereich
 * `#fs-tm-view`. Beim Seitenwechsel wird ausschließlich dieser Bereich neu geladen.
 */
class Layout {

    const VIEW_ID = 'fs-tm-view';

    /**
     * Alle Bereiche. Add-ons können über den Filter `fs_tm_admin_pages`
     * eigene Seiten ergänzen.
     *
     * @return array<string,array{icon:string,label:string}>
     */
    public static function pages() {
        return apply_filters('fs_tm_admin_pages', array(
            'fs-tm-manager'  => array('icon' => '⚽',  'label' => __('Teams & Widgets', 'fabriel-team-manager')),
            'fs-tm-settings' => array('icon' => '⚙️', 'label' => __('Einstellungen', 'fabriel-team-manager')),
        ));
    }

    public static function url($slug, array $args = array()) {
        return add_query_arg(array_merge(array('page' => $slug), $args), admin_url('admin.php'));
    }

    public static function open($current_slug) {
        ?>
        <div class="wrap fs-tm-wrap">
            <?php self::bar($current_slug); ?>
            <div class="fs-tm-toast-stack" id="fs-tm-toast-stack" aria-live="polite"></div>
            <div class="fs-tm-view" id="<?php echo esc_attr(self::VIEW_ID); ?>" data-fs-tm-page="<?php echo esc_attr($current_slug); ?>">
        <?php
    }

    public static function close() {
        self::footer();
        echo '</div></div>';
    }

    /**
     * Marke, Navigation und Support in einer Zeile.
     */
    public static function bar($current_slug) {
        ?>
        <div class="fs-tm-bar">
            <a href="https://fabrielsoftware.de/" target="_blank" rel="noopener noreferrer" class="fs-tm-brand" title="<?php esc_attr_e('Zu fabrielsoftware.de', 'fabriel-team-manager'); ?>">
                <span class="fs-tm-brand-mark" aria-hidden="true">
                    <?php echo wp_kses(TeamRepository::getSvgIcon(), TeamRepository::allowedSvgTags()); ?>
                </span>
                <span class="fs-tm-brand-text">
                    <span class="fs-tm-brand-name">Fabriel Software</span>
                    <span class="fs-tm-brand-version"><?php echo esc_html(self::versionLabel()); ?></span>
                </span>
            </a>

            <nav class="fs-tm-tabs" aria-label="<?php esc_attr_e('Bereiche des Team-Managers', 'fabriel-team-manager'); ?>">
                <?php foreach (self::pages() as $slug => $page): ?>
                    <a href="<?php echo esc_url(self::url($slug)); ?>"
                       class="fs-tm-tab<?php echo $slug === $current_slug ? ' is-active' : ''; ?>"
                       <?php echo $slug === $current_slug ? 'aria-current="page"' : ''; ?>>
                        <span aria-hidden="true"><?php echo esc_html($page['icon']); ?></span>
                        <span><?php echo esc_html($page['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="fs-tm-bar-actions">
                <?php do_action('fs_tm_admin_header_actions'); ?>
                <a href="mailto:support-tmc@fabrielsoftware.de" class="fs-tm-bar-support" title="<?php esc_attr_e('Support per E-Mail', 'fabriel-team-manager'); ?>">
                    <span class="dashicons dashicons-email-alt" aria-hidden="true"></span>
                    <span class="fs-tm-bar-support-text"><?php esc_html_e('Support', 'fabriel-team-manager'); ?></span>
                </a>
            </div>
        </div>
        <?php
    }

    /** Wird vom Add-on um die eigene Version ergänzt. */
    public static function versionLabel() {
        return (string) apply_filters('fs_tm_admin_version_label', 'v' . FS_TM_VERSION);
    }

    public static function footer() {
        ?>
        <div class="fs-tm-footer-row">
            <span><?php esc_html_e('Entwickelt von', 'fabriel-team-manager'); ?> <a href="https://fabrielsoftware.de/" target="_blank" rel="noopener noreferrer">Fabriel Software</a></span>
            <span>&bull; <a href="https://paypal.me/fabergab" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Spenden', 'fabriel-team-manager'); ?></a></span>
            <span>&bull; <a href="mailto:support-tmc@fabrielsoftware.de">support-tmc@fabrielsoftware.de</a></span>
            <?php do_action('fs_tm_admin_footer_links'); ?>
        </div>
        <?php
    }

    public static function collapsibleOpen($id, $icon, $title, array $args = array()) {
        $args = array_merge(array('collapsed' => true, 'hint' => __('Klicken zum Öffnen', 'fabriel-team-manager')), $args);
        ?>
        <div class="postbox fs-tm-box fs-tm-collapsible-box <?php echo $args['collapsed'] ? 'is-collapsed' : 'is-expanded'; ?>" id="<?php echo esc_attr($id); ?>">
            <div class="fs-tm-collapsible-header" title="<?php esc_attr_e('Klicken zum Auf-/Zuklappen', 'fabriel-team-manager'); ?>">
                <div class="fs-tm-collapsible-title">
                    <span class="dashicons dashicons-arrow-down-alt2 fs-tm-accordion-arrow"></span>
                    <h2><?php echo esc_html(trim($icon . ' ' . $title)); ?></h2>
                </div>
                <span class="fs-tm-collapsible-hint"><?php echo esc_html($args['hint']); ?></span>
            </div>
            <div class="fs-tm-collapsible-body">
        <?php
    }

    public static function collapsibleClose() {
        echo '</div></div>';
    }

    /**
     * Meldung, die das Skript in den schwebenden Stapel verschiebt.
     */
    public static function notice($type, $message, $extra_html = '') {
        printf(
            '<div class="notice notice-%1$s is-dismissible fs-tm-notice"><p><strong>%2$s</strong>%3$s</p></div>',
            esc_attr($type),
            esc_html($message),
            wp_kses_post($extra_html)
        );
    }
}
