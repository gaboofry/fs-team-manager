/**
 * Farbmathematik nach WCAG 2.1 (relative Leuchtdichte, Kontrastverhältnis)
 * und automatische Kontrastberechnung gegen den Seitenhintergrund.
 *
 * Frei von UI-Abhängigkeiten, läuft gleichermaßen in Frontend und Editor.
 *
 * @package   fabriel-team-manager
 * @author    Fabriel Software (https://fabrielsoftware.de/)
 * @copyright Fabriel Software
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
 */

export const INK_DARK = '#0f172a';
export const INK_LIGHT = '#ffffff';
export const MIN_TEXT = 4.5;
export const MIN_LARGE = 3.0;

function hueChannel(p, q, t) {
    let value = t;
    if (value < 0) value += 1;
    if (value > 1) value -= 1;
    if (value < 1 / 6) return p + (q - p) * 6 * value;
    if (value < 1 / 2) return q;
    if (value < 2 / 3) return p + (q - p) * (2 / 3 - value) * 6;
    return p;
}

function hslToRgb(h, s, l) {
    const hue = (((h % 360) + 360) % 360) / 360;
    const sat = Math.max(0, Math.min(100, s)) / 100;
    const light = Math.max(0, Math.min(100, l)) / 100;

    if (sat <= 0) {
        const grey = Math.round(light * 255);
        return [grey, grey, grey];
    }

    const q = light < 0.5 ? light * (1 + sat) : light + sat - light * sat;
    const p = 2 * light - q;

    return [
        Math.round(hueChannel(p, q, hue + 1 / 3) * 255),
        Math.round(hueChannel(p, q, hue) * 255),
        Math.round(hueChannel(p, q, hue - 1 / 3) * 255),
    ];
}

export function rgb(color) {
    if (Array.isArray(color)) return color.length >= 3 ? color.slice(0, 3).map(Number) : null;
    if (typeof color !== 'string') return null;

    const value = color.trim().toLowerCase();
    if (!value) return null;

    if (value[0] === '#') {
        let hexValue = value.slice(1);
        if (hexValue.length === 3 || hexValue.length === 4) {
            hexValue = hexValue
                .slice(0, 3)
                .split('')
                .map((char) => char + char)
                .join('');
        } else if (hexValue.length === 6 || hexValue.length === 8) {
            hexValue = hexValue.slice(0, 6);
        } else {
            return null;
        }
        if (!/^[0-9a-f]{6}$/.test(hexValue)) return null;
        return [
            parseInt(hexValue.slice(0, 2), 16),
            parseInt(hexValue.slice(2, 4), 16),
            parseInt(hexValue.slice(4, 6), 16),
        ];
    }

    const parts = value.match(/^(rgba?|hsla?)\(([^()]*)\)$/);
    if (!parts) return null;

    const values = parts[2].trim().split(/[\s,/]+/);
    if (values.length < 3) return null;

    if (parts[1].startsWith('rgba') && values.length >= 4) {
        const alpha = parseFloat(values[3]);
        if (alpha === 0) return null;
    }

    if (parts[1].startsWith('hsl')) {
        return hslToRgb(parseFloat(values[0]), parseFloat(values[1]), parseFloat(values[2]));
    }

    return values.slice(0, 3).map((raw) => {
        const number = raw.endsWith('%') ? (parseFloat(raw) * 255) / 100 : parseFloat(raw);
        return Math.max(0, Math.min(255, Math.round(number || 0)));
    });
}

export function hex(channels) {
    return (
        '#' +
        channels
            .map((channel) => Math.max(0, Math.min(255, Math.round(channel))).toString(16).padStart(2, '0'))
            .join('')
    );
}

export function normalize(color) {
    const channels = rgb(color);
    return channels ? hex(channels) : '';
}

export function luminance(color) {
    const channels = rgb(color);
    if (!channels) return 0;

    const [r, g, b] = channels.map((channel) => {
        const value = channel / 255;
        return value <= 0.04045 ? value / 12.92 : Math.pow((value + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

export function ratio(foreground, background) {
    const a = luminance(foreground);
    const b = luminance(background);
    const high = Math.max(a, b);
    const low = Math.min(a, b);
    return (high + 0.05) / (low + 0.05);
}

export function isDark(background) {
    return ratio(INK_LIGHT, background) >= ratio(INK_DARK, background);
}

export function ink(background) {
    return isDark(background) ? INK_LIGHT : INK_DARK;
}

export function mix(color, base, weight) {
    const a = rgb(color);
    const b = rgb(base);
    if (!a) return normalize(base);
    if (!b) return hex(a);

    const share = Math.max(0, Math.min(100, weight)) / 100;
    return hex([0, 1, 2].map((i) => a[i] * share + b[i] * (1 - share)));
}

export function step(background, amount) {
    return mix(ink(background), background, amount);
}

export function softInk(background, amount = 32, minRatio = MIN_TEXT) {
    const strong = ink(background);

    for (let current = amount; current > 0; current -= 4) {
        const candidate = mix(background, strong, current);
        if (ratio(candidate, background) >= minRatio) return candidate;
    }
    return strong;
}

export function surface(background) {
    const bg = normalize(background) || INK_LIGHT;
    const strong = ink(bg);
    const chipBg = mix(strong, bg, 90);

    return {
        bg,
        is_dark: isDark(bg),
        ink: strong,
        ink_soft: softInk(bg, 28, MIN_TEXT),
        ink_faint: softInk(bg, 48, MIN_LARGE),
        line: mix(strong, bg, 14),
        line_strong: mix(strong, bg, 26),
        sunken: mix(strong, bg, 5),
        raised: mix(strong, bg, 9),
        chip_bg: chipBg,
        chip_ink: ink(chipBg),
    };
}

/**
 * Ermittelt die tatsächliche Hintergrundfarbe eines Elements im DOM, indem
 * die Elternknoten bis zum documentElement durchlaufen werden.
 */
export function detectBackgroundColor(element) {
    let el = element || (typeof document !== 'undefined' ? document.body : null);
    while (el && el !== document) {
        if (typeof window !== 'undefined' && window.getComputedStyle) {
            const style = window.getComputedStyle(el);
            if (style) {
                const bg = style.backgroundColor;
                if (bg && bg !== 'transparent' && !bg.startsWith('rgba(0, 0, 0, 0') && !bg.startsWith('rgba(0,0,0,0')) {
                    if (rgb(bg)) {
                        return bg;
                    }
                }
            }
        }
        el = el.parentElement;
    }
    return INK_LIGHT;
}

/**
 * Berechnet und setzt die Kontrast-Tokens gegen den ermittelten Seitenhintergrund.
 */
export function applyPageContrast(target) {
    if (typeof document === 'undefined') return null;
    const el = target || document.documentElement;
    const bg = detectBackgroundColor(el);
    const surf = surface(bg);

    const targetStyle = el.style;
    if (targetStyle) {
        targetStyle.setProperty('--fs-tm-page-bg', surf.bg);
        targetStyle.setProperty('--fs-tm-ink', surf.ink);
        targetStyle.setProperty('--fs-tm-text', surf.ink_soft);
        targetStyle.setProperty('--fs-tm-muted', surf.ink_soft);
        targetStyle.setProperty('--fs-tm-faint', surf.ink_faint);
        targetStyle.setProperty('--fs-tm-line', surf.line);
        targetStyle.setProperty('--fs-tm-line-strong', surf.line_strong);
        if (surf.is_dark) {
            targetStyle.setProperty('--fs-tm-surface', surf.raised);
            targetStyle.setProperty('--fs-tm-surface-alt', surf.sunken);
            targetStyle.setProperty('--fs-tm-surface-sunken', surf.raised);
        }
    }
    return surf;
}

/**
 * Durchsucht alle übergeordneten Blöcke und passt den Kontrast lokal an,
 * falls ein Container in einem abweichenden Hintergrundbereich (z. B. Group-Block)
 * liegt.
 */
export function applyAllContainerContrasts() {
    if (typeof document === 'undefined') return;

    // Globale Kontrastanpassung auf :root
    applyPageContrast(document.documentElement);

    // Alle relevanten Blöcke außerhalb von Karten prüfen
    const selectors = [
        '.fs-tm-teampage',
        '.fs-tm-overview',
        '.fs-tm-hub',
        '.fs-tm-table-card',
        '.fs-tm-card',
    ];
    const containers = document.querySelectorAll(selectors.join(','));

    containers.forEach((container) => {
        const bg = detectBackgroundColor(container.parentElement);
        const surf = surface(bg);
        const targetStyle = container.style;

        targetStyle.setProperty('--fs-tm-page-bg', surf.bg);
        targetStyle.setProperty('--fs-tm-ink', surf.ink);
        targetStyle.setProperty('--fs-tm-text', surf.ink_soft);
        targetStyle.setProperty('--fs-tm-muted', surf.ink_soft);
        targetStyle.setProperty('--fs-tm-faint', surf.ink_faint);
        targetStyle.setProperty('--fs-tm-line', surf.line);
        targetStyle.setProperty('--fs-tm-line-strong', surf.line_strong);
        if (surf.is_dark) {
            targetStyle.setProperty('--fs-tm-surface', surf.raised);
            targetStyle.setProperty('--fs-tm-surface-alt', surf.sunken);
            targetStyle.setProperty('--fs-tm-surface-sunken', surf.raised);
        }
    });
}
