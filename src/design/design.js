/**
 * Farbmathematik und Farbsteuerung für den Blockeditor.
 *
 * Gegenstück zu `Support\Contrast` und `Design\Scheme` in PHP: Beide Seiten müssen
 * für dieselbe Eingabe dieselben Werte liefern, damit die Editor-Vorschau dem
 * Frontend entspricht.
 *
 * Das Modul wird als eigenes Skript ausgeliefert und über `window.fsTmDesign`
 * bereitgestellt, damit das Pro-Add-on dieselbe Umsetzung verwendet, statt sie zu
 * kopieren.
 */

import { __ } from '@wordpress/i18n';
import { PanelColorSettings } from '@wordpress/block-editor';
import { Notice, PanelBody, SelectControl } from '@wordpress/components';

import {
    INK_DARK,
    INK_LIGHT,
    MIN_TEXT,
    MIN_LARGE,
    rgb,
    hex,
    normalize,
    luminance,
    ratio,
    isDark,
    ink,
    mix,
    step,
    softInk,
    surface,
    detectBackgroundColor,
    applyPageContrast,
    applyAllContainerContrasts,
} from '../shared/contrast';

export {
    INK_DARK,
    INK_LIGHT,
    MIN_TEXT,
    MIN_LARGE,
    rgb,
    hex,
    normalize,
    luminance,
    ratio,
    isDark,
    ink,
    mix,
    step,
    softInk,
    surface,
    detectBackgroundColor,
    applyPageContrast,
    applyAllContainerContrasts,
};

const DEFAULTS = {
    central: { head_bg: '#314e82', head_text: '', body_bg: '#ffffff', page_bg: '' },
    presets: {
        light: { head_bg: '#e8edf5', head_text: '', body_bg: '#ffffff', page_bg: '' },
        dark: { head_bg: '#111827', head_text: '', body_bg: '#1e293b', page_bg: '' },
    },
};

function data() {
    const provided = window.fsTmDesignData || {};
    return {
        central: { ...DEFAULTS.central, ...(provided.central || {}) },
        presets: { ...DEFAULTS.presets, ...(provided.presets || {}) },
    };
}

/* --------------------------------------------------------------------------
   Schema-Auflösung
   -------------------------------------------------------------------------- */

export function resolve(attributes = {}) {
    const { central, presets } = data();
    const scheme = attributes.colorScheme || '';
    const base = { ...(presets[scheme] || central) };

    const overrides = {
        head_bg: normalize(attributes.backgroundColor || ''),
        head_text: normalize(attributes.textColor || ''),
        body_bg: normalize(attributes.bodyBackgroundColor || ''),
    };
    Object.keys(overrides).forEach((key) => {
        if (overrides[key]) base[key] = overrides[key];
    });

    return base;
}

export function variables(palette) {
    const headBg = isDark(palette.head_bg)
        ? mix('#000000', palette.head_bg, 20)
        : step(palette.head_bg, 12);

    const head = surface(headBg);
    const body = surface(palette.body_bg);
    const page = surface(palette.page_bg || INK_LIGHT);

    if (palette.head_text) {
        head.ink = palette.head_text;
        head.ink_soft = mix(palette.head_text, headBg, 72);
    }

    return {
        '--fs-tm-page-bg': page.bg,
        '--fs-tm-ink': page.ink,
        '--fs-tm-text': page.ink_soft,
        '--fs-tm-muted': page.ink_soft,
        '--fs-tm-faint': page.ink_faint,
        '--fs-tm-line': page.line,
        '--fs-tm-line-strong': page.line_strong,
        '--fs-tm-card-bg': palette.head_bg,
        '--fs-tm-card-text': head.ink,
        '--fs-tm-card-body-bg': body.bg,
        '--fs-tm-card-head-bg': head.bg,
        '--fs-tm-card-ink': head.ink,
        '--fs-tm-card-ink-soft': head.ink_soft,
        '--fs-tm-card-line': head.line,
        '--fs-tm-card-chip-bg': head.chip_bg,
        '--fs-tm-card-chip-ink': head.chip_ink,
        '--fs-tm-body-ink': body.ink,
        '--fs-tm-body-ink-soft': body.ink_soft,
        '--fs-tm-body-ink-faint': body.ink_faint,
        '--fs-tm-body-line': body.line,
        '--fs-tm-body-line-strong': body.line_strong,
        '--fs-tm-body-sunken': body.sunken,
        '--fs-tm-body-raised': body.raised,
        '--fs-tm-body-chip-bg': body.chip_bg,
        '--fs-tm-body-chip-ink': body.chip_ink,
    };
}

/**
 * Style-Objekt für die Editor-Vorschau. React reicht Custom-Properties unverändert
 * durch, sodass Vorschau und Frontend dieselben Regeln verwenden.
 */
export function previewStyle(attributes = {}) {
    return variables(resolve(attributes));
}

/* --------------------------------------------------------------------------
   Bedienelement
   -------------------------------------------------------------------------- */

const schemeOptions = () => [
    { value: '', label: __('Zentrales Design', 'fabriel-team-manager') },
    { value: 'light', label: __('Hell', 'fabriel-team-manager') },
    { value: 'dark', label: __('Dunkel', 'fabriel-team-manager') },
    { value: 'custom', label: __('Eigene Farben', 'fabriel-team-manager') },
];

const schemeHelp = () =>
    __(
        'Schrift-, Linien- und Plakettenfarben werden aus den Flächen berechnet und bleiben dadurch immer lesbar.',
        'fabriel-team-manager'
    );

/**
 * Farbsteuerung eines Blocks: ein Schema, bei Bedarf eigene Flächenfarben.
 *
 * @param {Object}   props.attributes    Blockattribute.
 * @param {Function} props.setAttributes Setter des Blocks.
 * @param {string}   props.textDomain    Textdomain des aufrufenden Plugins.
 */
export function SchemePanel({ attributes = {}, setAttributes, title, initialOpen = false }) {
    const scheme = attributes.colorScheme || '';
    const hasOwnColors = Boolean(
        attributes.backgroundColor || attributes.textColor || attributes.bodyBackgroundColor
    );
    const showColors = scheme === 'custom' || hasOwnColors;

    const palette = resolve(attributes);
    const vars = variables(palette);
    const headRatio = ratio(vars['--fs-tm-card-ink'], vars['--fs-tm-card-head-bg']);

    const onScheme = (value) => {
        // Beim Wechsel zurück auf ein Schema werden eigene Farben verworfen,
        // sonst würde die Auswahl wirkungslos wirken.
        setAttributes(
            value === 'custom'
                ? { colorScheme: 'custom' }
                : { colorScheme: value, backgroundColor: '', textColor: '', bodyBackgroundColor: '' }
        );
    };

    return (
        <PanelBody title={title || __('Farben', 'fabriel-team-manager')} initialOpen={initialOpen}>
            <SelectControl
                label={__('Farbschema', 'fabriel-team-manager')}
                help={schemeHelp()}
                value={showColors && scheme !== 'custom' ? 'custom' : scheme}
                options={schemeOptions()}
                onChange={onScheme}
            />

            {showColors && (
                <>
                    <PanelColorSettings
                        className="fs-tm-editor-colors"
                        title={__('Flächen', 'fabriel-team-manager')}
                        initialOpen
                        colorSettings={[
                            {
                                value: attributes.backgroundColor || '',
                                onChange: (val) => setAttributes({ backgroundColor: val || '' }),
                                label: __('Kartenkopf', 'fabriel-team-manager'),
                            },
                            {
                                value: attributes.bodyBackgroundColor || '',
                                onChange: (val) => setAttributes({ bodyBackgroundColor: val || '' }),
                                label: __('Inhaltsfläche', 'fabriel-team-manager'),
                            },
                            {
                                value: attributes.textColor || '',
                                onChange: (val) => setAttributes({ textColor: val || '' }),
                                label: __('Schrift im Kartenkopf (optional)', 'fabriel-team-manager'),
                            },
                        ]}
                    />

                    {attributes.textColor && headRatio < MIN_TEXT && (
                        <Notice status="warning" isDismissible={false}>
                            {__(
                                'Diese Schriftfarbe ist auf dem gewählten Kartenkopf schwer lesbar. Feld leeren, dann wird eine lesbare Farbe berechnet.',
                                'fabriel-team-manager'
                            )}
                        </Notice>
                    )}
                </>
            )}
        </PanelBody>
    );
}

window.fsTmDesign = {
    rgb,
    hex,
    normalize,
    luminance,
    ratio,
    isDark,
    ink,
    mix,
    step,
    softInk,
    surface,
    detectBackgroundColor,
    applyPageContrast,
    resolve,
    variables,
    previewStyle,
    SchemePanel,
};
