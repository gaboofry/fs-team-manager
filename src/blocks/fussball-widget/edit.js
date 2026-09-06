import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';

export default function Edit({ attributes, setAttributes }) {
    const { team = 'auto', view = 'matches', period = 'current', showTeamName = true } = attributes;

    // Bereitgestellt von build/design.js (window.fsTmDesign) — dieselbe Berechnung
    // wie im Frontend, damit die Vorschau nicht abweicht.
    const design = window.fsTmDesign || {};
    const colorVars = design.previewStyle ? design.previewStyle(attributes) : {};
    const SchemePanel = design.SchemePanel;

    const teamOptions = (window.fsTmBlockData && window.fsTmBlockData.teamOptions) || [
        { label: '⚡ ' + __('Automatisch (anhand Seite)', 'fs-team-manager'), value: 'auto' }
    ];

    const teamsData = (window.fsTmBlockData && window.fsTmBlockData.teamsData) || {};

    const viewOptions = (window.fsTmBlockData && window.fsTmBlockData.viewOptions) || [
        { label: '📅 ' + __('Spielplan', 'fs-team-manager'), value: 'matches' },
        { label: '🏆 ' + __('Tabelle', 'fs-team-manager'), value: 'table' }
    ];

    function getMergedPeriodOptions(teamSlug, viewVal) {
        const options = [{ label: '🌟 ' + __('Immer aktuell (Datum)', 'fs-team-manager'), value: 'current' }];
        if (!teamSlug || teamSlug === 'auto' || !teamsData[teamSlug]) return options;

        const periods = teamsData[teamSlug].periods || [];
        const filtered = [];

        periods.forEach((p, i) => {
            const uuid = (viewVal === 'table' ? p.id_table || '' : p.id_matches || '').trim();
            if (uuid) {
                filtered.push({
                    orig_idx: i,
                    label: p.label || __('Zeitraum', 'fs-team-manager') + ' ' + (i + 1),
                    valid_from: p.valid_from || '',
                    valid_to: p.valid_to || '',
                    uuid
                });
            }
        });

        if (!filtered.length) return options;
        filtered.sort((a, b) => a.valid_from.localeCompare(b.valid_from));

        const merged = [];
        filtered.forEach((cur) => {
            if (!merged.length) {
                merged.push({ key: 'p-' + cur.orig_idx, label: cur.label, valid_from: cur.valid_from, valid_to: cur.valid_to, uuid: cur.uuid });
                return;
            }
            const last = merged[merged.length - 1];
            if (last.uuid === cur.uuid) {
                last.valid_to = cur.valid_to;
                if (last.label !== cur.label) last.label += ' & ' + cur.label;
            } else {
                merged.push({ key: 'p-' + cur.orig_idx, label: cur.label, valid_from: cur.valid_from, valid_to: cur.valid_to, uuid: cur.uuid });
            }
        });

        merged.sort((a, b) => b.valid_from.localeCompare(a.valid_from));
        merged.forEach((m) => {
            const dates = m.valid_from ? ` (${m.valid_from}${m.valid_to ? ' – ' + m.valid_to : ''})` : '';
            options.push({ label: '📌 ' + m.label + dates, value: m.key });
        });

        return options;
    }

    const periodOptions = getMergedPeriodOptions(team, view);

    let selectedLabel = __('Automatisch (aktuelle Seite)', 'fs-team-manager');
    const matched = teamOptions.find((t) => t.value === team);
    if (matched) selectedLabel = matched.cleanName || matched.label;

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

    const viewLabel = view === 'table' ? '🏆 ' + __('Tabelle', 'fs-team-manager') : '📅 ' + __('Spielplan', 'fs-team-manager');

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Einstellungen', 'fs-team-manager')} initialOpen={true}>
                    <SelectControl
                        label={__('Mannschaft auswählen:', 'fs-team-manager')}
                        help={__('Wähle ein festes Team oder die automatische Erkennung anhand der aktuellen Seite.', 'fs-team-manager')}
                        value={team}
                        options={teamOptions}
                        onChange={(val) => setAttributes({ team: val, period: 'current' })}
                    />
                    <SelectControl
                        label={__('Ansicht:', 'fs-team-manager')}
                        help={__('Bestimmt, ob der Spielplan oder die Ligatabelle geladen werden soll.', 'fs-team-manager')}
                        value={view}
                        options={viewOptions}
                        onChange={(val) => setAttributes({ view: val, period: 'current' })}
                    />
                    <SelectControl
                        label={__('Start-Zeitraum:', 'fs-team-manager')}
                        help={__('Immer aktuell wählt automatisch den passenden Zeitraum anhand des Datums.', 'fs-team-manager')}
                        value={period}
                        options={periodOptions}
                        onChange={(val) => setAttributes({ period: val })}
                    />
                    <ToggleControl
                        label={__('Mannschaftsnamen übernehmen', 'fs-team-manager')}
                        help={__('Bestimmt, ob der Mannschaftsname im Widget-Kartenheader im Frontend angezeigt wird.', 'fs-team-manager')}
                        checked={showTeamName}
                        onChange={(val) => setAttributes({ showTeamName: val })}
                    />
                </PanelBody>
                {SchemePanel && <SchemePanel attributes={attributes} setAttributes={setAttributes} />}
            </InspectorControls>

            <div {...blockProps}>
                <div style={{ padding: '12px 18px', background: 'var(--fs-tm-card-head-bg)', color: 'var(--fs-tm-card-ink)', textAlign: 'center', fontWeight: 700, fontSize: '14px' }}>
                    <span style={{ marginRight: '6px' }}>⚽</span>
                    Fabriel Software Widget
                </div>
                <div style={{ padding: '18px 20px', background: 'var(--fs-tm-card-body-bg)', color: 'var(--fs-tm-body-ink)', textAlign: 'center', fontSize: '13px' }}>
                    <strong style={{ display: 'block', marginBottom: '4px' }}>{__('Mannschaft:', 'fs-team-manager')} {selectedLabel}</strong>
                    <span style={{ color: 'var(--fs-tm-body-ink-soft)', fontSize: '12px' }}>({viewLabel})</span>
                </div>
            </div>
        </>
    );
}
