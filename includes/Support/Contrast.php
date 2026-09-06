<?php
namespace FabrielSoftware\TeamManager\Support;

if (!defined('ABSPATH')) exit;

/**
 * Farbmathematik nach WCAG 2.1 (relative Leuchtdichte, Kontrastverhältnis).
 *
 * Dies ist die einzige Stelle im Produkt, die entscheidet, welche Schriftfarbe auf
 * einer Fläche lesbar ist. Dadurch gilt für helle und dunkle Hintergründe dieselbe
 * Regel und niemand muss Textfarben von Hand nachpflegen.
 */
class Contrast {

    /** Dunkelster Schriftton. */
    const INK_DARK = '#0f172a';

    /** Hellster Schriftton. */
    const INK_LIGHT = '#ffffff';

    /** Mindestkontrast für Fließtext (WCAG 2.1 AA). */
    const MIN_TEXT = 4.5;

    /** Mindestkontrast für Nebentext, Symbole und Rahmen (WCAG 2.1 AA, groß). */
    const MIN_LARGE = 3.0;

    /**
     * Zerlegt eine CSS-Farbe in ihre Kanäle.
     *
     * @return array{0:int,1:int,2:int}|null null bei unbekannter Notation.
     */
    public static function rgb($color) {
        if (is_array($color)) {
            return count($color) >= 3 ? array((int) $color[0], (int) $color[1], (int) $color[2]) : null;
        }
        if (!is_scalar($color)) return null;

        $value = strtolower(trim((string) $color));
        if ($value === '') return null;

        if ($value[0] === '#') {
            $hex = substr($value, 1);
            $len = strlen($hex);
            if ($len === 3 || $len === 4) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            } elseif ($len === 6 || $len === 8) {
                $hex = substr($hex, 0, 6);
            } else {
                return null;
            }
            if (!ctype_xdigit($hex)) return null;
            return array(
                (int) hexdec(substr($hex, 0, 2)),
                (int) hexdec(substr($hex, 2, 2)),
                (int) hexdec(substr($hex, 4, 2)),
            );
        }

        if (preg_match('/^rgba?\(([^()]*)\)$/', $value, $match)) {
            $parts = preg_split('#[\s,/]+#', trim($match[1]));
            if (!is_array($parts) || count($parts) < 3) return null;
            $out = array();
            for ($i = 0; $i < 3; $i++) {
                $raw    = $parts[$i];
                $number = (float) $raw;
                if (substr($raw, -1) === '%') $number = $number * 255 / 100;
                $out[] = (int) max(0, min(255, round($number)));
            }
            return $out;
        }

        if (preg_match('/^hsla?\(([^()]*)\)$/', $value, $match)) {
            $parts = preg_split('#[\s,/]+#', trim($match[1]));
            if (!is_array($parts) || count($parts) < 3) return null;
            return self::hslToRgb(
                (float) preg_replace('/[^0-9.\-]/', '', $parts[0]),
                (float) rtrim($parts[1], '%'),
                (float) rtrim($parts[2], '%')
            );
        }

        return null;
    }

    /**
     * @param float $h Farbwinkel in Grad, $s und $l in Prozent.
     *
     * @return array{0:int,1:int,2:int}
     */
    private static function hslToRgb($h, $s, $l) {
        $h = fmod(fmod((float) $h, 360) + 360, 360) / 360;
        $s = max(0, min(100, (float) $s)) / 100;
        $l = max(0, min(100, (float) $l)) / 100;

        if ($s <= 0) {
            $grey = (int) round($l * 255);
            return array($grey, $grey, $grey);
        }

        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;

        return array(
            (int) round(self::hueChannel($p, $q, $h + 1 / 3) * 255),
            (int) round(self::hueChannel($p, $q, $h) * 255),
            (int) round(self::hueChannel($p, $q, $h - 1 / 3) * 255),
        );
    }

    private static function hueChannel($p, $q, $t) {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1 / 6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1 / 2) return $q;
        if ($t < 2 / 3) return $p + ($q - $p) * (2 / 3 - $t) * 6;
        return $p;
    }

    /**
     * Kanonische, injektionssichere Schreibweise. Nicht deutbare Werte ergeben ''.
     */
    public static function normalize($color) {
        $rgb = self::rgb($color);
        return $rgb === null ? '' : self::hex($rgb);
    }

    public static function hex(array $rgb) {
        return sprintf(
            '#%02x%02x%02x',
            max(0, min(255, (int) round($rgb[0]))),
            max(0, min(255, (int) round($rgb[1]))),
            max(0, min(255, (int) round($rgb[2])))
        );
    }

    /**
     * Relative Leuchtdichte nach WCAG 2.1.
     *
     * @return float 0.0 (Schwarz) bis 1.0 (Weiß).
     */
    public static function luminance($color) {
        $rgb = self::rgb($color);
        if ($rgb === null) return 0.0;

        $channels = array();
        foreach ($rgb as $channel) {
            $value = $channel / 255;
            $channels[] = $value <= 0.04045 ? $value / 12.92 : pow(($value + 0.055) / 1.055, 2.4);
        }
        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * Kontrastverhältnis zweier Farben, 1.0 bis 21.0.
     */
    public static function ratio($foreground, $background) {
        $a = self::luminance($foreground);
        $b = self::luminance($background);
        if ($a < $b) {
            $swap = $a;
            $a    = $b;
            $b    = $swap;
        }
        return ($a + 0.05) / ($b + 0.05);
    }

    /**
     * Wahr, wenn heller Text auf dieser Fläche besser lesbar ist als dunkler.
     */
    public static function isDark($background) {
        return self::ratio(self::INK_LIGHT, $background) >= self::ratio(self::INK_DARK, $background);
    }

    /**
     * Lesbarste Schriftfarbe für eine Fläche.
     */
    public static function ink($background, $dark = self::INK_DARK, $light = self::INK_LIGHT) {
        return self::ratio($light, $background) >= self::ratio($dark, $background) ? $light : $dark;
    }

    /**
     * Mischt zwei Farben.
     *
     * @param int $weight Anteil von $color in Prozent.
     */
    public static function mix($color, $base, $weight) {
        $a = self::rgb($color);
        $b = self::rgb($base);
        if ($a === null) return self::normalize($base);
        if ($b === null) return self::hex($a);

        $weight = max(0, min(100, (float) $weight)) / 100;
        return self::hex(array(
            $a[0] * $weight + $b[0] * (1 - $weight),
            $a[1] * $weight + $b[1] * (1 - $weight),
            $a[2] * $weight + $b[2] * (1 - $weight),
        ));
    }

    /**
     * Verschiebt eine Fläche um $amount Prozent in Richtung ihres Kontrasttons —
     * helle Flächen werden dunkler, dunkle heller. So bleibt der Unterschied auf
     * jedem Untergrund sichtbar.
     */
    public static function step($background, $amount) {
        return self::mix(self::ink($background), $background, $amount);
    }

    /**
     * Zurückgenommene Schriftfarbe, die den geforderten Mindestkontrast einhält.
     *
     * @param int   $amount    Gewünschte Rücknahme in Prozent.
     * @param float $min_ratio Kontrast, der nicht unterschritten werden darf.
     */
    public static function softInk($background, $amount = 32, $min_ratio = self::MIN_TEXT) {
        $ink = self::ink($background);

        for ($step = (int) $amount; $step > 0; $step -= 4) {
            $candidate = self::mix($background, $ink, $step);
            if (self::ratio($candidate, $background) >= $min_ratio) return $candidate;
        }
        return $ink;
    }

    /**
     * Vollständiger Satz abgeleiteter Werte einer Fläche.
     *
     * `chip_bg` ist die Gegenfläche für Plaketten: Auf dunklen Karten nahezu weiß,
     * auf hellen nahezu schwarz. `chip_ink` ist der dazu passende Schriftton.
     *
     * @return array<string,string|bool>
     */
    public static function surface($background) {
        $bg = self::normalize($background);
        if ($bg === '') $bg = self::INK_LIGHT;

        $ink     = self::ink($bg);
        $chip_bg = self::mix($ink, $bg, 90);

        return array(
            'bg'          => $bg,
            'is_dark'     => self::isDark($bg),
            'ink'         => $ink,
            'ink_soft'    => self::softInk($bg, 28, self::MIN_TEXT),
            'ink_faint'   => self::softInk($bg, 48, self::MIN_LARGE),
            'line'        => self::mix($ink, $bg, 14),
            'line_strong' => self::mix($ink, $bg, 26),
            'sunken'      => self::mix($ink, $bg, 5),
            'raised'      => self::mix($ink, $bg, 9),
            'chip_bg'     => $chip_bg,
            'chip_ink'    => self::ink($chip_bg),
        );
    }

    /**
     * Ermittelt die Hintergrundfarbe der Seite (Theme / body.background-color).
     *
     * Berücksichtigt:
     * 1. Filter `fs_tm_page_background`
     * 2. Block-Themes (theme.json / Global Styles: color.background)
     * 3. Klassische Themes (get_background_color() / theme_mod)
     * 4. Rückfallwert: #ffffff
     *
     * @return string Kanonischer Hex-Farbwert (#rrggbb).
     */
    public static function pageBackground() {
        /**
         * Ermöglicht Themes oder Add-ons, den Seitenhintergrund explizit vorzugeben.
         *
         * @param string|null $color Hex-Farbwert oder null für automatische Erkennung.
         */
        $filtered = apply_filters('fs_tm_page_background', null);
        if ($filtered !== null && is_scalar($filtered)) {
            $norm = self::normalize($filtered);
            if ($norm !== '') return $norm;
        }

        // 1. Block-Themes (WordPress 5.9+)
        if (function_exists('wp_get_global_styles')) {
            $bg = wp_get_global_styles(array('color', 'background'));
            if (!empty($bg) && is_string($bg)) {
                if (strpos($bg, 'var(--wp--preset--color--') !== false && function_exists('wp_get_global_settings')) {
                    if (preg_match('/var\(--wp--preset--color--([a-z0-9\-]+)\)/', $bg, $m)) {
                        $palette = wp_get_global_settings(array('color', 'palette', 'theme'));
                        if (is_array($palette)) {
                            foreach ($palette as $item) {
                                if (isset($item['slug'], $item['color']) && $item['slug'] === $m[1]) {
                                    $bg = $item['color'];
                                    break;
                                }
                            }
                        }
                    }
                }
                $norm = self::normalize($bg);
                if ($norm !== '') return $norm;
            }
        }

        // 2. Klassische Themes (Custom-Background Support)
        if (function_exists('get_background_color')) {
            $bg = get_background_color();
            if (!empty($bg)) {
                $norm = self::normalize('#' . $bg);
                if ($norm !== '') return $norm;
            }
        }

        if (function_exists('get_theme_mod')) {
            $bg = get_theme_mod('background_color');
            if (!empty($bg)) {
                $norm = self::normalize('#' . $bg);
                if ($norm !== '') return $norm;
            }
        }

        return '#ffffff';
    }
}
