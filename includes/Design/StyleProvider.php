<?php
namespace FabrielSoftware\TeamManager\Design;

if (!defined('ABSPATH')) exit;

/**
 * Gibt die zentrale Farbvorgabe als CSS-Custom-Properties auf `:root` aus.
 *
 * Damit gelten dieselben abgeleiteten Schrift- und Linienfarben auch für Bereiche
 * außerhalb einer Karte — etwa für Listen, die Add-ons ohne eigenen Kartenrahmen
 * ausgeben.
 */
class StyleProvider {

    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'), 15);
        add_action('enqueue_block_editor_assets', array(__CLASS__, 'enqueue'), 15);
    }

    public static function enqueue() {
        $css = self::inlineCss();
        if ($css === '' || !wp_style_is('fs-tm-frontend-css', 'registered')) return;

        wp_add_inline_style('fs-tm-frontend-css', $css);
    }

    public static function inlineCss() {
        $declarations = array();
        foreach (Scheme::variables(Scheme::central()) as $property => $value) {
            $declarations[] = $property . ':' . $value;
        }

        return empty($declarations) ? '' : ':root{' . implode(';', $declarations) . ';}';
    }
}
