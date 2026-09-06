# Design-System: Fabriel Software Team-Manager

Gestaltungsgrundlage für alle Oberflächen des Plugins: Admin, Frontend und
Blockeditor.

Dieses Dokument ist selbsttragend und definiert die verbindlichen Grundregeln,
die Design-Tokens (Farben, Abstände, Radien, Schatten, Typografie,
Bedienelement-Maße), das Markenbild, die Ikonografie, die Zustandsklassen, das
responsive Verhalten, die Barrierefreiheit und die Druckansicht sowie die
Komponenten-Kataloge der Oberflächen und die spezifischen Konventionen
(Farb-Fallback-Kette, Lazy-Load-Panes, Kopfzeilen-Höhenangleichung,
Attributions-Hinweis).

---

## 1. Komponenten-Katalog: Admin

Alle Klassen existieren genau einmal und werden von `src/admin/admin.scss`
gepflegt. Historisch gewachsene Namen (`fs-tm-team-card`, `fs-tm-period-card`, …)
bleiben unverändert; neue Komponenten folgen dem Namensschema aus der
Gemeindenorm (`fs-tm-<block>[__<element>][--<variante>]` + `is-*`/`has-*`
Modifier).

### 1.1 Seitenrahmen

| Klasse | Zweck |
| --- | --- |
| `fs-tm-wrap` | Seiten-Wrapper. Setzt Schrift und Breite. |
| `fs-tm-bar` | Kopfleiste: Marke, Navigation, Support. |
| `fs-tm-brand` + `-mark` `-text` `-name` `-version` | Logo (SVG aus `TeamRepository::getSvgIcon()`), Wortmarke, Versionsangabe. Führt zu fabrielsoftware.de. |
| `fs-tm-tabs` / `fs-tm-tab` | Navigation zwischen den Bereichen. Zustand `is-active`. |
| `fs-tm-bar-actions` / `fs-tm-bar-support` | Rechte Seite der Leiste: Hook `fs_tm_admin_header_actions`, Support-Mail. |
| `fs-tm-view` | Austauschbarer Inhaltsbereich (SPA-Wechsel, ID `fs-tm-view`). |
| `fs-tm-toast-stack` / `fs-tm-toast` | Schwebende Meldungen. Verschieben den Inhalt nicht. |
| `fs-tm-footer-row` | Fußzeile: Entwickler, Spenden, Support, Hook `fs_tm_admin_footer_links`. |
| `fs-tm-notice` | Serverseitige Meldung; wird vom Admin-Skript in den Toast-Stapel verschoben. |

### 1.2 Mannschaftsseite (`fs-tm-manager`)

| Klasse | Zweck |
| --- | --- |
| `fs-tm-collapsible-box` + `-header` / `-title` / `-body` / `-hint` | Aufklappbare Box auf oberster Ebene (Neu anlegen, Handbuch). |
| `fs-tm-accordion-arrow` | Auf-/Zuklapp-Pfeil (Dashicon) aller Akkordeons und Karten. |
| `fs-tm-toolbar` / `fs-tm-search-wrap` | Suchleiste; Hook `fs_tm_admin_toolbar` für Filterchips. |
| `fs-tm-team-accordion-list` | Sortierbare Liste (`jquery-ui-sortable`). |
| `fs-tm-team-card` | Entitätskarte einer Mannschaft. Zustände `is-expanded`, `is-collapsed`. |
| `fs-tm-team-header` + `-left` / `-right` | Kartenkopf: Griff, Pfeil, Name, Slug, Löschen. |
| `fs-tm-drag-handle` | Sortiergriff (Drag & Drop). |
| `fs-tm-team-title-text` / `slug-info` | Name und technisches Kürzel. |
| `fs-tm-team-body` | Formularbereich; `data-loaded="0\|1"` steuert das Lazy Loading. |
| `fs-tm-section-box` + `-header` / `-body` | Aufklappbare Sektion **innerhalb** der Karte. Trägt `data-fs-tm-section` (`core-basics`, `core-periods`). |
| `fs-tm-period-card` | Zeitraum-Karte. Zustände `is-expanded`, `is-collapsed`, `is-active-period`. |
| `fs-tm-period-card-header` / `-body` | Kopf und Inhalt einer Zeitraum-Karte. |
| `fs-tm-period-icon` / `-title-text` | Piktogramm und Bezeichnung. |
| `fs-tm-active-badge` / `fs-tm-past-badge` / `fs-tm-future-badge` | Statusanzeige „Aktiver Zeitraum" / „Vergangen" / „Zukünftig". |
| `fs-tm-period-dates-row` / `-separator` | Start- und Enddatum nebeneinander. |
| `fs-tm-period-ids-row` | Spielplan- und Tabellen-ID nebeneinander. |
| `fs-tm-period-footer-row` | Fußzeile der Zeitraum-Karte (Club-Matches-Häkchen). |
| `fs-tm-uuid-input-wrap` | UUID-Eingabe mit integrierter Test-Schaltfläche. |
| `fs-tm-btn-test-uuid` | „Testen": öffnet den Widget-Inspector. |
| `fs-tm-input-error` | Feld mit ungültigem UUID-Format. |
| `fs-tm-form-grid` | Formularraster der Sektionen. |
| `fs-tm-field-group` | Label + Feld + Hilfe. |
| `fs-tm-input` / `fs-tm-select` / `fs-tm-textarea` | Formularfelder. |
| `fs-tm-checkbox-pill` | Checkbox/Radio als Pille. |
| `fs-tm-help-tip` | Fragezeichen mit Tooltip (`data-tip`). |
| `fs-tm-btn-primary` / `-secondary` / `-danger` / `-ghost` | Buttons. |
| `fs-tm-btn-add-period` / `-text` | „Neuen Zeitraum hinzufügen". |
| `fs-tm-delete-period` | Zeitraum löschen. |
| `fs-tm-team-actions-bar` | Action-Bar: Speichern, Verwerfen, Zuklappen. |
| `fs-tm-btn-save-main` / `-reset-team` / `-close-team` | Buttons der Action-Bar. |
| `fs-tm-no-periods-msg` | Leerzustand ohne Zeiträume. |
| `fs-tm-form-errors` | Validierungsfehlerliste nach abgebrochenem Speichern. |
| `fs-tm-empty-notice` | Leerzustand ohne Mannschaften. |
| `fs-tm-add-grid` | Formularraster „Neue Mannschaft anlegen". |

### 1.3 Widget-Inspector (Modal)

| Klasse | Zweck |
| --- | --- |
| `fs-tm-modal-overlay` / `fs-tm-modal-dialog` | Modal-Overlay und Dialog. |
| `fs-tm-modal-header` / `-title` / `-icon` | Kopf des Modals („Widget-Inspector"). |
| `fs-tm-modal-devices` / `fs-tm-device-btn` | Breitenumschaltung Desktop / Tablet / Mobile. Zustand `is-active`. |
| `fs-tm-modal-close` | Schließen. |
| `fs-tm-modal-meta` | Metadaten: Typ und UUID des getesteten Widgets. |
| `fs-tm-modal-body` / `-frame-wrapper` / `fs-tm-preview-stage` | Vorschaufläche mit Gerätebreite. |

### 1.4 Einstellungsseite (`fs-tm-settings`)

| Klasse | Zweck |
| --- | --- |
| `fs-tm-privacy-grid` / `fs-tm-privacy-row` + `--title` `--description` `--actions` | Sektion „Datenschutz & externe Inhalte". |
| `fs-tm-backup-grid` | Raster der Sektion „Datensicherung". |
| `fs-tm-backup-card` + `fs-tm-backup-card-row` + `--title` `--description` `--action` | Karten „Teams exportieren" / „Teams wiederherstellen". |
| `fs-tm-import-mode` | Radio-Auswahl „Ergänzen" / „Ersetzen". |
| `fs-tm-file-upload-wrap` / `fs-tm-file-input` | Datei-Auswahl für den JSON-Import. |
| `fs-tm-help-box` / `fs-tm-help-group` / `-intro` / `fs-tm-help-grid` / `fs-tm-help-field` / `fs-tm-help-intro` | Handbuch & Feldreferenz (`HelpSection`). |

### 1.5 Zustandsklassen

Die verbindliche Zustands-Tabelle (Farben, Deckkraft, Rahmen) folgt den
Grundregeln dieses Dokuments. Konkret verwendet werden:

| Zustand | Wo |
| --- | --- |
| `is-expanded` / `is-collapsed` | `fs-tm-collapsible-box`, `fs-tm-section-box`, `fs-tm-team-card`, `fs-tm-period-card` |
| `is-active` | Aktiver Zeitraum (`is-active-period`), aktiver Tab, aktive Gerätebreite |
| `is-invalid` / `fs-tm-input-error` | Ungültige UUID-Eingabe |
| `has-unsaved-changes` | Karte mit ungespeicherten Änderungen |

---

## 2. Komponenten-Katalog: Frontend

Gepflegt in `src/frontend/frontend.scss`. Alle Karten werden serverseitig von
`Blocks\BlockRegistrar` gerendert.

| Klasse | Zweck |
| --- | --- |
| `fs-tm-table-card` | Basiskarte für fussball.de-Widgets. Kopf + Körper, gefärbt über `--fs-tm-card-*`. |
| `fs-tm-widget-card` | Alias-Klasse am selben Element (Block „Spielplan & Tabelle"). |
| `fs-tm-card-header` + `-header-left` / `-header-right` | Kartenkopf. |
| `fs-tm-card-title` / `-icon` / `-badge` / `-note` | Kopf-Bestandteile; `-note` = „Letzter Zeitraum"-Hinweis. |
| `fs-tm-card-link` | Mannschaftsname als Link auf die zugeordnete Seite. |
| `fs-tm-card-title-line` | Ein Zeileneintrag in der Tabellen-Übersicht (mehrere Teams je Liga). |
| `fs-tm-card-body` | Inhaltsfläche. |
| `fs-tm-card-attribution` | „Eingebunden durch Fabriel Software Teammanager" unterhalb jeder Karte (wird vom Frontend-Skript idempotent eingefügt — sowohl unter `.fs-tm-table-card` als auch unter `.fs-tm-card` der Add-ons). Der Link zum Widerruf der Widget-Einwilligung erscheint nur unter Karten, die tatsächlich einen externen Inhalt einbetten. |
| `fs-tm-period-select-wrap` / `fs-tm-period-select` | Auswahlfeld der Zeiträume rechts im Kartenkopf. |
| `fs-tm-period-pane` + `is-active` | Zeitraum-Pane. Inaktive Panes tragen `data-fs-tm-lazy="1"` und halten ihren Inhalt in `<template>`. |
| `fs-tm-tables-grid` + `fs-tm-cols-1\|2\|3` | Responsives Raster der Tabellen-Übersicht. |
| `fs-tm-widget-slot` + `data-widget-id` / `data-widget-type` | Zwei-Klick-Container; `data-fstm-state` verhindert Doppelinitialisierung. |
| `fs-tm-consent` + `-icon` `-text` `-btn` `-remember` `-link` | Zwei-Klick-Box für externe Inhalte. |
| `fs-tm-no-data` | Hinweis, wenn keine Widget-ID hinterlegt ist. |
| `fussballde_widget` | **Vorgegebene** Klassen des fussball.de-Einbettungscode. Bleibt unverändert (Ausnahme R1b). |

### 2.1 Farbschema und abgeleitete Farben

Der Betreiber wählt **nur Flächen**. Schrift-, Linien- und Plakettenfarben rechnet
`Support\Contrast` (WCAG 2.1: relative Leuchtdichte, Kontrastverhältnis) daraus aus.
Dadurch bleibt jede Beschriftung lesbar — auf hellem wie auf dunklem Untergrund.

Ein Block wählt über das Attribut `colorScheme` genau eine Möglichkeit:

| Wert | Bedeutung |
| --- | --- |
| `''` | Zentrale Vorgabe (`Design\Scheme::central()`, Filter `fs_tm_design_palette`) |
| `light` | Helles Schema |
| `dark` | Dunkles Schema |
| `custom` | Eigene Farben; blendet die Farbfelder im Editor ein |

Gesetzte Farbattribute (`backgroundColor`, `bodyBackgroundColor`, `textColor`)
übersteuern das Schema unabhängig davon. Blöcke aus früheren Fassungen ohne
`colorScheme` verhalten sich deshalb unverändert.

`Design\Scheme::styleAttr()` setzt den vollständigen Satz Custom-Properties auf die
Karte beziehungsweise auf das Raster:

| Custom-Property | Bedeutung |
| --- | --- |
| `--fs-tm-page-bg` | Seitenhintergrund (automatisch aus Theme/body.background-color oder Vorgabe) |
| `--fs-tm-ink` / `--fs-tm-text` | Schrift für Elemente direkt vor dem Seitenhintergrund (Titel, Fließtext) |
| `--fs-tm-muted` / `--fs-tm-faint` | Sekundärtext und feine Achsenmarken auf dem Seitenhintergrund |
| `--fs-tm-line` / `-line-strong` | Trennlinien auf dem Seitenhintergrund |
| `--fs-tm-card-bg` | Kartenfläche (Nutzerwahl) |
| `--fs-tm-card-head-bg` | Kopfzeile, gegenüber der Kartenfläche abgestuft |
| `--fs-tm-card-ink` / `-ink-soft` | Schrift in der Kopfzeile |
| `--fs-tm-card-line` | Trennlinie der Kopfzeile |
| `--fs-tm-card-chip-bg` / `-chip-ink` | Plakette und Auswahlfeld in der Kopfzeile |
| `--fs-tm-card-body-bg` | Inhaltsfläche (Nutzerwahl) |
| `--fs-tm-body-ink` / `-ink-soft` / `-ink-faint` | Schrift auf der Inhaltsfläche |
| `--fs-tm-body-line` / `-line-strong` | Linien auf der Inhaltsfläche |
| `--fs-tm-body-sunken` / `-raised` | abgesetzte Flächen |
| `--fs-tm-body-chip-bg` / `-chip-ink` | Chips auf der Inhaltsfläche |

`--fs-tm-card-text` bleibt als Alias von `--fs-tm-card-ink` erhalten, damit ältere
Add-on-Stile weiter greifen.

Dieselben Werte liegen zusätzlich auf `:root` (`Design\StyleProvider`), damit Bereiche
außerhalb einer Karte dieselbe Sprache sprechen. Elemente direkt vor dem Seitenhintergrund
(z. B. `.fs-tm-teampage-title`, `.fs-tm-overview-title`, `.fs-tm-validity`,
`.fs-tm-legend-label`, `.fs-tm-cal-tick`, `.fs-tm-doc-daybreak`, `.fs-tm-doc-team`)
berechnen ihren Kontrast automatisch gegen `body.background-color` (bzw. den Hintergrund
ihres übergeordneten Containers). Das Frontend-Skript (`frontend.js`) misst die
tatsächliche Hintergrundfarbe zur Laufzeit nach und passt die Tokens dynamisch an.
Das Frontend-Stylesheet enthält nur noch Rückfallwerte auf `:root` —
**keine festen Textfarben in Komponenten**.

Im Blockeditor liefert `build/design.js` (`window.fsTmDesign`) dieselbe Berechnung.
Add-ons binden das Handle `fs-tm-design-js` ein, statt sie zu kopieren.

---

## 3. Komponenten-Katalog: Blockeditor

Die Blöcke sind serverseitig gerendert; der Editor zeigt eine **Vorschau**
(`edit.js`) mit den echten Blockattributen, nicht das fertige Frontend.

| Element / Klasse | Zweck |
| --- | --- |
| `edit.js` (beide Blöcke) | `SelectControl` Mannschaft / Ansicht / Zeitraum, `ToggleControl` Teamname, `SchemePanel` aus `window.fsTmDesign` für das Farbschema. |
| `fsTmBlockData` | Lokalisierte Editordaten: `teamOptions`, `teamsData`, `viewOptions`; erweiterbar über `fs_tm_block_editor_data`. |
| `fsTmDesignData` | Zentrale Vorgabe und Schemata für die Editor-Vorschau; lokalisiert auf `fs-tm-design-js`. |

Die Editor-Platzhalter-Klassen (`fs-tm-editor-*`) folgen dem Namensschema
`fs-tm-editor-<block>[__<element>][--<variante>]`.

---

## 4. Core-spezifische Konventionen

### 4.1 Lazy-Load der Zeitraum-Panes

Inaktive Zeiträume werden **nicht** gerendert: Ihr Inhalt liegt in einem
`<template>`-Element, das `frontend.js` bei der ersten Auswahl in das Pane
übernimmt (`resolveLazyPane`). Danach werden die Container idempotent
initialisiert — maximal ein `iframe` pro `.fussballde_widget`-Container.

**Regel:** Neue Panes oder dynamisch nachgeladene Karten dürfen nie doppelt
initialisiert werden. Guards: `data-fs-tm-lazy` (einmalig),
`data-fstm-state` (Slot-Konvertierung), `data-fstm-bound` (Select-Listener).

### 4.2 Widget-Mounting

Das offizielle Einbettungs-Skript `widgets.js` ist eine IIFE ohne Guard und
hängt pro Aufruf ein **neues** `iframe` an. Es darf daher pro Seite maximal
einmal laufen (`ensureWidgetScript`). Später erscheinende Container (Lazy-Panes,
Zwei-Klick-Lösung) werden stattdessen inline mit identischen `iframe`-Eigenschaften
montiert. Höhenmeldungen (`fussballde_widget:resize`) werden über das
`message`-Event weitergeleitet; die `origin` wird gegen `fussball.de` geprüft.

### 4.3 Zwei-Klick-Lösung

Zustandsstufen der Zustimmung (je Browser):

| Stufe | Speicher | Gültigkeit |
| --- | --- | --- |
| Sitzung | `sessionStorage` (`fsTmWidgetConsentSession`) | Aktueller Tab |
| Dauerhaft | `localStorage` (`fsTmWidgetConsent`) | Bis zur Löschung |
| In-Memory | JS-Flag | Aktueller Tab, wenn beide Speicher blockiert sind |

Ein `MutationObserver` fängt Slots ab, die erst nach dem Bootstrap ins DOM
kommen (Lazy-Panes, Zeitraum-Umschaltung). `loadConsentedWidgets()` ist
idempotent.

Als aktiv gilt die Zwei-Klick-Lösung, sobald `fsTmFrontend.clickToLoad` gesetzt ist
**oder** die Seite mindestens einen `.fs-tm-widget-slot[data-widget-id]` enthält
(`clickToLoadActive()`). Damit wirkt die gespeicherte Zustimmung auch dort, wo die
Slots aus einem Add-on stammen. Ergänzend fordert `renderWidgetSlot()` das
Frontend-Skript ausdrücklich an, weil `renderSingleWidget()` auch außerhalb der
Core-Blöcke aufgerufen wird und das `viewScript` der `block.json` dort nicht greift.

### 4.4 Kopfzeilen-Höhenangleichung

Innerhalb einer `.fs-tm-tables-grid` werden alle `.fs-tm-card-header` auf die
höchste Kopfzeile ihrer Zeile gedehnt (`minHeight`), damit die Kartenkörper
bündig beginnen. Ausnahmen: Mobile (≤ 768 px) und nach jedem `resize`/`load`
wird neu gemessen.

### 4.5 Frontend-Breakpoints

Die Breakpoint-Tabelle:

| Breakpoint | Verhalten |
| --- | --- |
| `≤ 992px` | `fs-tm-cols-3` → 2 Spalten |
| `≤ 768px` | Alle Raster → 1 Spalte; Kopfzeilen-Höhenangleichung aus |

---

## 5. Checkliste vor jedem Commit

Die Checkliste:

- [ ] Neue Frontend-Klassen im Komponenten-Katalog (Abschnitt 2) eingetragen.
- [ ] Neue Admin-Klassen im Komponenten-Katalog (Abschnitt 1) eingetragen.
- [ ] Neue Zustandsklassen tragen den Präfix oder sind `is-*`/`has-*` und stehen
      nie allein.
- [ ] Lazy-Load-Guards (`data-fs-tm-lazy`, `data-fstm-state`, `data-fstm-bound`)
      bleiben idempotent — keine doppelten `iframe`s, keine doppelten Listener.
- [ ] Neue Hooks in `ARCHITECTURE.md` Abschnitt 7.1 dokumentiert.
- [ ] Alle sichtbaren Zeichenketzen übersetzbar (`__()`, Textdomain
      `fs-team-manager`).
