<?php
namespace FabrielSoftware\TeamManager\Design;

use FabrielSoftware\TeamManager\Support\Contrast;

if (!defined('ABSPATH')) exit;

/**
 * Farbschema der Karten.
 *
 * Ein Block wählt genau eine von vier Möglichkeiten:
 *
 * | Schema     | Bedeutung                                                        |
 * | ---------- | ---------------------------------------------------------------- |
 * | ''         | Zentrale Vorgabe (Corporate Design des Add-ons oder Standardwert) |
 * | 'light'    | Helles Schema                                                     |
 * | 'dark'     | Dunkles Schema                                                    |
 * | 'custom'   | Eigene Farben; nur gesetzte Felder übersteuern die Vorgabe        |
 *
 * Aus den drei Flächenfarben leitet `Support\Contrast` sämtliche Schrift-, Linien-
 * und Plakettenfarben ab. Der Betreiber wählt also nur Flächen — die Lesbarkeit
 * ergibt sich zwangsläufig.
 */
class Scheme {

    const DEFAULT_HEAD_BG = '#314e82';
    const DEFAULT_BODY_BG = '#ffffff';

    /**
     * Zentrale Vorgabe. Add-ons liefern hierüber das Corporate Design.
     *
     * @return array{head_bg:string,head_text:string,body_bg:string}
     */
    public static function central() {
        /**
         * Öffentlicher Erweiterungs-Punkt (public extension point).
         *
         * @param array $palette head_bg, head_text (leer = automatisch), body_bg, page_bg.
         */
        $palette = apply_filters('fs_tm_design_palette', array(
            'head_bg'   => self::DEFAULT_HEAD_BG,
            'head_text' => '',
            'body_bg'   => self::DEFAULT_BODY_BG,
            'page_bg'   => '',
        ));

        return self::sanitizePalette((array) $palette, array(
            'head_bg'   => self::DEFAULT_HEAD_BG,
            'head_text' => '',
            'body_bg'   => self::DEFAULT_BODY_BG,
            'page_bg'   => '',
        ));
    }

    /**
     * Die beiden fest geprüften Schemata für die Blockauswahl.
     *
     * @return array<string,array{head_bg:string,head_text:string,body_bg:string}>
     */
    public static function presets() {
        return array(
            'light' => array('head_bg' => '#e8edf5', 'head_text' => '', 'body_bg' => '#ffffff'),
            'dark'  => array('head_bg' => '#111827', 'head_text' => '', 'body_bg' => '#1e293b'),
        );
    }

    /**
     * Löst die Blockattribute zu drei Flächenfarben auf.
     *
     * @return array{head_bg:string,head_text:string,body_bg:string}
     */
    public static function resolve(array $attributes) {
        $scheme  = isset($attributes['colorScheme']) ? (string) $attributes['colorScheme'] : '';
        $presets = self::presets();
        $base    = isset($presets[$scheme]) ? $presets[$scheme] : self::central();

        // Einzelne Farbattribute übersteuern das Schema. Alte Blöcke ohne
        // `colorScheme` verhalten sich dadurch unverändert.
        $overrides = array(
            'head_bg'   => Contrast::normalize($attributes['backgroundColor'] ?? ''),
            'head_text' => Contrast::normalize($attributes['textColor'] ?? ''),
            'body_bg'   => Contrast::normalize($attributes['bodyBackgroundColor'] ?? ''),
        );
        foreach ($overrides as $key => $value) {
            if ($value !== '') $base[$key] = $value;
        }

        return $base;
    }

    /**
     * Sämtliche CSS-Custom-Properties einer Karte, inklusive der abgeleiteten
     * Schrift-, Linien- und Plakettenfarben.
     *
     * @return array<string,string>
     */
    public static function variables(array $palette) {
        $palette = self::sanitizePalette($palette, self::central());

        // Der Kartenkopf ist bewusst etwas kräftiger als die Kartenfläche. Die
        // Verschiebung erfolgt in Richtung des Kontrasttons, wirkt also auf hellen
        // Flächen abdunkelnd und auf dunklen aufhellend.
        $head_bg = Contrast::isDark($palette['head_bg'])
            ? Contrast::mix('#000000', $palette['head_bg'], 20)
            : Contrast::step($palette['head_bg'], 12);

        $head = Contrast::surface($head_bg);
        $body = Contrast::surface($palette['body_bg']);

        $page_bg = !empty($palette['page_bg'])
            ? Contrast::normalize($palette['page_bg'])
            : Contrast::pageBackground();
        $page = Contrast::surface($page_bg);

        if ($palette['head_text'] !== '') {
            $head['ink']      = $palette['head_text'];
            $head['ink_soft'] = Contrast::mix($palette['head_text'], $head_bg, 72);
        }

        return array(
            '--fs-tm-page-bg'          => $page['bg'],
            '--fs-tm-ink'              => $page['ink'],
            '--fs-tm-text'             => $page['ink_soft'],
            '--fs-tm-muted'            => $page['ink_soft'],
            '--fs-tm-faint'            => $page['ink_faint'],
            '--fs-tm-line'             => $page['line'],
            '--fs-tm-line-strong'      => $page['line_strong'],
            '--fs-tm-card-bg'          => $palette['head_bg'],
            '--fs-tm-card-text'        => $head['ink'],
            '--fs-tm-card-body-bg'     => $body['bg'],
            '--fs-tm-card-head-bg'     => $head['bg'],
            '--fs-tm-card-ink'         => $head['ink'],
            '--fs-tm-card-ink-soft'    => $head['ink_soft'],
            '--fs-tm-card-line'        => $head['line'],
            '--fs-tm-card-chip-bg'     => $head['chip_bg'],
            '--fs-tm-card-chip-ink'    => $head['chip_ink'],
            '--fs-tm-body-ink'         => $body['ink'],
            '--fs-tm-body-ink-soft'    => $body['ink_soft'],
            '--fs-tm-body-ink-faint'   => $body['ink_faint'],
            '--fs-tm-body-line'        => $body['line'],
            '--fs-tm-body-line-strong' => $body['line_strong'],
            '--fs-tm-body-sunken'      => $body['sunken'],
            '--fs-tm-body-raised'      => $body['raised'],
            '--fs-tm-body-chip-bg'     => $body['chip_bg'],
            '--fs-tm-body-chip-ink'    => $body['chip_ink'],
        );
    }

    /**
     * Fertiges style-Attribut für das Wurzelelement einer Karte.
     *
     * @param array $extra Zusätzliche Custom-Properties, etwa Mannschaftsfarben.
     */
    public static function styleAttr(array $attributes, array $extra = array()) {
        $declarations = array();

        foreach (self::variables(self::resolve($attributes)) as $property => $value) {
            $declarations[] = $property . ':' . $value;
        }
        foreach ($extra as $property => $value) {
            $property = self::sanitizeProperty($property);
            $value    = trim((string) $value);
            if ($property === '' || $value === '') continue;
            $declarations[] = $property . ':' . $value;
        }

        if (empty($declarations)) return '';
        return ' style="' . esc_attr(implode(';', $declarations) . ';') . '"';
    }

    /**
     * Nur echte Custom-Property-Namen. Verhindert das Ausbrechen aus der Deklaration.
     */
    public static function sanitizeProperty($property) {
        $property = trim((string) $property);
        return preg_match('/^--[a-z0-9\-]{1,64}$/i', $property) ? $property : '';
    }

    /**
     * @param array $defaults Werte, die bei unbrauchbarer Eingabe einspringen.
     */
    private static function sanitizePalette(array $palette, array $defaults) {
        $clean = array();
        foreach ($defaults as $key => $default) {
            $value       = Contrast::normalize($palette[$key] ?? '');
            $clean[$key] = ($value === '' && $key !== 'head_text' && $key !== 'page_bg') ? $default : $value;
        }
        return $clean;
    }
}
