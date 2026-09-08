import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';

export default function Edit({ attributes, setAttributes }) {
    const { columns = '2' } = attributes;

    // Bereitgestellt von build/design.js (window.fsTmDesign) — dieselbe Berechnung
    // wie im Frontend, damit die Vorschau nicht abweicht.
    const design = window.fsTmDesign || {};
    const colorVars = design.previewStyle ? design.previewStyle(attributes) : {};
    const SchemePanel = design.SchemePanel;

    const blockProps = useBlockProps({
        style: {
            ...colorVars,
            borderRadius: '12px',
            overflow: 'hidden',
            border: '1px solid rgba(0,0,0,0.12)',
            boxShadow: '0 4px 14px rgba(0,0,0,0.08)',
            cursor: 'pointer'
        }
    });

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Layout Einstellungen', 'fabriel-team-manager')} initialOpen={true}>
                    <SelectControl
                        label={__('Spalten:', 'fabriel-team-manager')}
                        help={__('Anzahl der Spalten auf Desktop-Bildschirmen.', 'fabriel-team-manager')}
                        value={columns}
                        options={[
                            { label: '1 ' + __('Spalte', 'fabriel-team-manager'), value: '1' },
                            { label: '2 ' + __('Spalten', 'fabriel-team-manager'), value: '2' },
                            { label: '3 ' + __('Spalten', 'fabriel-team-manager'), value: '3' }
                        ]}
                        onChange={(val) => setAttributes({ columns: val })}
                    />
                </PanelBody>
                {SchemePanel && <SchemePanel attributes={attributes} setAttributes={setAttributes} />}
            </InspectorControls>

            <div {...blockProps}>
                <div style={{ padding: '12px 18px', background: 'var(--fs-tm-card-head-bg)', color: 'var(--fs-tm-card-ink)', textAlign: 'center', fontWeight: 700, fontSize: '14px' }}>
                    <span style={{ marginRight: '6px' }}>🏆</span>
                    Tabellen-Übersicht (Grid)
                </div>
                <div style={{ padding: '18px 20px', background: 'var(--fs-tm-card-body-bg)', color: 'var(--fs-tm-body-ink)', textAlign: 'center', fontSize: '13px' }}>
                    <strong style={{ display: 'block', marginBottom: '4px' }}>{__('Raster-Ansicht', 'fabriel-team-manager')} ({columns} {__('Spalten', 'fabriel-team-manager')})</strong>
                    <span style={{ color: 'var(--fs-tm-body-ink-soft)', fontSize: '12px' }}>{__('Zeigt alle aktiven Tabellen automatisch gruppiert an.', 'fabriel-team-manager')}</span>
                </div>
            </div>
        </>
    );
}
