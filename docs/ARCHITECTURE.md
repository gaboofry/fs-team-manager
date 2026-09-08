# Architektur: Fabriel Team Manager (Core)

Referenz für Menschen **und** für Coding-Agents. Wer an diesem Plugin arbeitet, liest zuerst
dieses Dokument und danach [`DESIGN-SYSTEM.md`](DESIGN-SYSTEM.md).

---

## 1. Produktaufbau

Dieses Plugin ist eine eigenständige, vollständig nutzbare Anwendung. Alle Funktionen sind
unbeschnitten verfügbar; es gibt keine gesperrten oder lizenzierten Bereiche.

Das Plugin ist als **Add-on-fähiger Core** aufgebaut: Es stellt eine dokumentierte,
öffentliche Hook-Schnittstelle bereit (siehe §7), über die separate, eigenständige
Add-on-Plugins zusätzliche Seiten, Blöcke oder Backup-Formate anbinden können. Der Core
kennt ein Add-on ausschließlich über diese neutralen Hooks und Filter — niemals über
fremde Plugin-Slugs, Klassennamen, Dateinamen oder Produktnamen.

### Grundprinzip der Add-on-Kopplung

- Der Core bleibt für WordPress.org eine vollständig nutzbare Anwendung.
- Alle Add-on-Fähigkeiten werden ausschließlich über die Hooks und Filter aus §7 erreicht.
- Der Core referenziert kein fremdes Plugin über Slug, Namespace, Klassen- oder Dateinamen.
- Die Datenhaltung der Mannschaften liegt genau einmal im Core; Add-ons ergänzen, ohne
  diese zu duplizieren.

Details zur Lizenz- und Datenschutzlage siehe [`LEGAL.md`](LEGAL.md).

---

## 2. Bootstrap und Aktivierung

### 2.1 Aktivierung (`Plugin::activate()`)

Prüft in dieser Reihenfolge:

1. PHP ≥ 7.4
2. WordPress ≥ 6.0

Schlägt eine Prüfung fehl, wird das Plugin deaktiviert und ein `wp_die()` mit
`back_link` angezeigt. Danach:

3. `Data\TeamRepository::migrateData()` — führt die CPT-Migration aus (siehe §4.4)
4. `update_option('fs_tm_version', FS_TM_VERSION, 'no')`

### 2.2 Bootstrap (`Plugin::init()`)

Hängt an `plugins_loaded` (Priorität: Standard = 10). Reihenfolge:

1. `load_plugin_textdomain('fabriel-team-manager', …)`
2. `Data\TeamRepository::init()` — registriert den Post-Type und prüft die Version
3. `Admin\AdminPage::init()` — Menü, Assets, AJAX, `admin_init`-Handler
4. `Admin\SettingsPage::init()` — `admin_init`-Handler für die Einstellungsseite
5. `Blocks\BlockRegistrar::init()` — Blockkategorie + `init`-Registrierung
6. `do_action('fs_tm_loaded', self::API_VERSION)` — Startpunkt für Add-ons

### 2.3 Versionierung

| Konstante / Option | Zweck |
| --- | --- |
| `FS_TM_VERSION` | Aktuelle Plugin-Version (z. B. `1.2.2`) |
| `Plugin::API_VERSION` | Stabile API-Version für Add-on-Kompatibilitätsprüfung |
| Option `fs_tm_version` | Zuletzt migrierte Version; wird bei Update verglichen |

`TeamRepository::checkVersionAndMigrate()` läuft auf `init` (Priorität 6) und führt
`migrateData()` aus, wenn `fs_tm_version < FS_TM_VERSION`.

---

## 3. Verzeichnisstruktur

```
fs-team-manager/
├── fabriel-team-manager.php       Plugin-Header, Konstanten, Autoloader, Bootstrap
├── uninstall.php                  Vollständige Deinstallation
├── README.md                      Startseite des öffentlichen Repositories
├── docs/
│   ├── ARCHITECTURE.md            dieses Dokument
│   ├── CONSENT-MANAGEMENT.md      Anbindung an Consent-Plugins (Complianz & Co.)
│   ├── DESIGN-SYSTEM.md           Gestaltungsvorgaben
│   ├── LEGAL.md                   Lizenz- und Datenschutzrahmen (Core-spezifisch)
│   ├── REPO-HANDOVER.md           Übergabe in das öffentliche Repository
│   └── REVIEW-COMPLIANCE.md       Umsetzungsstand der WordPress.org-Prüfung
├── includes/
│   ├── Plugin.php                 Bootstrap, Berechtigung, Add-on-Erkennung, Aktivierung
│   ├── Admin/
│   │   ├── AdminPage.php          Hauptseite „Teams & Widgets“, AJAX, Aktionen
│   │   ├── SettingsPage.php       Seite „Einstellungen“ (Datenschutz, Backup, Handbuch)
│   │   ├── Layout.php             Gemeinsamer Seitenrahmen (Bar, Tabs, View, Footer)
│   │   ├── Notices.php            Meldungen aus Redirect-Parametern
│   │   ├── HelpSection.php        Handbuch & Feldreferenz
│   │   └── ExportImport.php       JSON-Export/Import, Delegierung externer Formate
│   ├── Data/
│   │   └── TeamRepository.php     CPT fs_tm_team, Zeiträume, Seitenbaum, Migration
│   ├── Design/
│   │   ├── Scheme.php             Farbschema der Karten, abgeleitete Custom-Properties
│   │   └── StyleProvider.php      Zentrale Farbvorgabe als :root-Variablen
│   ├── Support/
│   │   └── Contrast.php           Farbmathematik nach WCAG 2.1 (Leuchtdichte, Kontrast)
│   └── Blocks/
│       └── BlockRegistrar.php     Blockkategorie, Registrierung, Rendering
├── src/
│   ├── admin/
│   │   ├── admin.js               Admin-Logik (SPA-Navigation, Drag&Drop, Modal, Suche)
│   │   └── admin.scss             Admin-Stile
│   ├── design/
│   │   └── design.js              window.fsTmDesign: Farbmathematik und Schema-Bedienelement
│   ├── frontend/
│   │   ├── frontend.js            Frontend-Logik (Widget-Mounting, Consent, Lazy-Load)
│   │   └── frontend.scss          Frontend-Stile
│   └── blocks/
│       ├── fussball-widget/       block.json | index.js | edit.js
│       └── tables-overview/       block.json | index.js | edit.js
├── build/                         Erzeugt durch `npm run build` — nie manuell bearbeiten
│                                  Jede erzeugte Datei trägt einen Kopfkommentar
│                                  mit Quelldatei, Build-Befehl und Repository
│   ├── admin.js / admin.css / admin.asset.php
│   ├── frontend.js / frontend.css / frontend.asset.php
│   ├── design.js / design.asset.php   Handle `fs-tm-design-js` (nur Blockeditor)
│   └── blocks/
│       ├── fussball-widget/       block.json | index.js | index.asset.php
│       └── tables-overview/       block.json | index.js | index.asset.php
├── languages/fabriel-team-manager.pot
├── package.json                   @wordpress/scripts ^27
├── webpack.config.js              Einstiegspunkte + SourceHeaderPlugin (Quellhinweis)
├── composer.json                  PSR-4-Autoloader (optional, Fallback im Plugin-Header)
├── build.sh                       Build + ZIP-Verpackung (Linux/macOS)
├── build.ps1                      Build + ZIP-Verpackung (Windows)
├── readme.txt                     WordPress.org-README
├── license.txt                    GPL-2.0-or-later im Volltext
└── index.php                      Leere Datei (Verzeichnis-Schutz)
```

---

## 4. Datenmodell

### 4.1 Grundsätze

- Mannschaften sind der **einzige** Post-Type des Core: `fs_tm_team`.
- Die WordPress-UI ist deaktiviert (`show_ui => false`); die eigene Admin-Seite ist die
  einzige Oberfläche.
- Verknüpfungen zu WordPress-Seiten laufen über die **Post-ID** (`page_id`).
- Verknüpfungen zu Blöcken und Backups laufen über den **Slug**. Der Slug ist der
  stabile Schlüssel und darf sich nicht ändern.
- Zeiträume sind ein **Array** im Post-Meta `_fs_tm_periods`. Jeder Zeitraum hat
  getrennte IDs für Spielplan und Tabelle.
- Es gibt **keine** personenbezogenen Daten im Core.

### 4.2 Post-Type `fs_tm_team`

Registriert in `TeamRepository::registerPostType()` auf `init` (Priorität 5).

| Eigenschaft | Wert |
| --- | --- |
| `post_title` | Anzeigename (»1. Mannschaft«) |
| `post_name` | Slug (»senioren-1«) |
| `menu_order` | Manuelle Reihenfolge (Drag & Drop) |
| `post_status` | `publish` |
| `public` | `false` |
| `show_ui` | `false` |
| `show_in_rest` | `false` |
| `supports` | `title`, `page-attributes` |
| `capability_type` | `post` |

### 4.3 Post-Meta

| Meta-Key | Typ | Inhalt |
| --- | --- | --- |
| `_fs_tm_slug` | string | Stabiler Schlüssel für Blöcke und Backups |
| `_fs_tm_page_id` | int | Post-ID der zugeordneten WordPress-Seite |
| `_fs_tm_periods` | array | Zeiträume (siehe unten) |

Zeitraum-Eintrag:

```php
[
  'label'           => 'Saison 2026/2027',
  'valid_from'      => '2026-07-01',   // YYYY-MM-DD
  'valid_to'        => '2027-06-30',   // YYYY-MM-DD, leer = unbegrenzt
  'id_matches'      => 'uuid',         // fussball.de data-id, data-type="team-matches"
  'id_table'        => 'uuid',         // fussball.de data-id, data-type="table"
  'is_club_matches' => 0              // 1 = club-matches statt team-matches
]
```

### 4.4 Migration

`TeamRepository::migrateData()` überführt die frühere Options-Speicherung
(`fs_tm_teams`) in den Post-Type. Der alte Stand bleibt als Sicherung in der Option
`fs_tm_teams_pre_cpt` erhalten.

Ab Version 1.1.0: Wenn keine Mannschaften existieren, wird eine Default-Mannschaft
(»1. Mannschaft«) mit einem Standard-Zeitraum für das aktuelle Kalenderjahr angelegt.

### 4.5 Optionen und Transients

| Schlüssel | Typ | Inhalt |
| --- | --- | --- |
| Option `fs_tm_version` | string | Zuletzt migrierte Plugin-Version |
| Option `fs_tm_click_to_load` | int (0\|1) | Zwei-Klick-Lösung aktiviert |
| Option `fs_tm_show_attribution` | int (0\|1) | Frontend-Credit anzeigen (Opt-in, Standard 0) |
| Option `fs_tm_teams_restore_point` | array | Einmaliger Wiederherstellungspunkt vor Import |
| Option `fs_tm_teams_pre_cpt` | array | Sicherung des alten Formats (nur nach Migration) |
| Transient `fs_tm_pages_tree` | array | Seitenbaum (12 h Cache) |
| WP-Cache `all_teams` / `fs_tm` | array | Objekt-Cache aller Mannschaften |

---

## 5. Zeitraum-Auflösung

Alle Datumslogik liegt in `Data\TeamRepository`. Kein anderes Modul rechnet selbst mit
Datumsfeldern.

| Methode | Zweck |
| --- | --- |
| `isPeriodActive($period, $date)` | Gilt der Zeitraum am Stichtag? |
| `resolvePeriodIndex($periods, $date)` | Index des heute gültigen Zeitraums; Fallback: zuletzt gültiger (`is_fallback = true`) |
| `getMergedPeriodsForView($team, $view)` | Zeiträume mit gleicher UUID werden zu einem Eintrag zusammengefasst |
| `getActiveTeamIds($team, $date)` | Aktive IDs inkl. `period_label` und `is_fallback` |
| `checkPeriodsContinuity($periods, $name, &$errors)` | Meldet Überlappungen und fehlende Enddaten |

**Regel:** Kein Modul außerhalb von `TeamRepository` prüft Datumsfelder oder löst
Zeiträume auf.

### 5.1 Zusammenfassung (Merging)

`getMergedPeriodsForView()` fasst aufeinanderfolgende Zeiträume mit **derselben**
Widget-ID zu einem einzigen Eintrag zusammen. Die Bezeichnungen werden mit `&`
verbunden. Dies reduziert die Auswahl im Frontend, wenn sich die ID über mehrere
Zeiträume nicht geändert hat.

### 5.2 Fallback-Verhalten

Ist für das heutige Datum kein Zeitraum aktiv, wird der **zuletzt gültige** Zeitraum
angezeigt. Die Karte trägt dann den Hinweis „Letzter Zeitraum"
(`.fs-tm-card-note`). Die Seite bleibt nie leer.

---

## 6. Blöcke

Beide Core-Blöcke liegen in der Kategorie `fs-tm-blocks` und werden **serverseitig**
gerendert (`render_callback`). Der Editor rendert nur die Vorschau.

| Blockname | Titel | Wichtigste Attribute |
| --- | --- | --- |
| `fstm/fussball-widget` | Spielplan & Tabelle | `team` (`auto`\|Slug), `view` (`matches`\|`table`), `period` (`current`\|Key), `showTeamName`, `backgroundColor`, `textColor`, `bodyBackgroundColor` |
| `fstm/fussball-tables-overview` | Tabellen-Übersicht (Grid) | `columns` (`1`\|`2`\|`3`), `backgroundColor`, `textColor`, `bodyBackgroundColor` |

Konventionen:

1. `team` unterstützt immer `auto` (Erkennung anhand der Seitenzuordnung).
2. Farbattribute heißen einheitlich `backgroundColor`, `textColor`, `bodyBackgroundColor`.
   Leer bedeutet: Theme-Palette verwenden (Fallback-Kette, siehe Design-System).
3. Der Renderer gibt `''` zurück, wenn keine Daten vorliegen — kein leerer Rahmen.
4. Externe Inhalte laufen immer über `renderWidgetSlot()` mit Zwei-Klick-Lösung
   und `fs_tm_load_remote_widgets`.

### 6.1 Rendering: `fstm/fussball-widget`

`BlockRegistrar::renderSingleWidget($attributes)`:

1. `resolveTeam($slug)` — löst `auto` über `page_id` auf.
2. `getMergedPeriodsForView($team, $view)` — Zeiträume mit passender ID.
3. `resolvePeriodIndex()` — aktiver Zeitraum; `is_fallback` bei Lücke.
4. Zeiträume werden als `.fs-tm-period-pane` ausgegeben. Der aktive ist sichtbar
   (`is-active`), inaktive liegen in `<template>` (Lazy-Load).
5. `renderWidgetSlot($id, $type)` — erzeugt den fussball.de-Container oder die
   Consent-Box.
6. Filter: `fs_tm_card_body_before`, `fs_tm_card_body_after`, `fs_tm_widget_card_html`.

### 6.2 Rendering: `fstm/fussball-tables-overview`

`BlockRegistrar::renderTablesOverview($attributes)`:

1. Alle Mannschaften mit aktiver `id_table` werden geladen.
2. Mannschaften mit **derselben** Tabellen-ID (gleiche Liga) werden zu einer
   gemeinsamen Karte zusammengefasst.
3. Jede Karte zeigt den Teamnamen als Link auf die zugeordnete Seite.
4. Raster: `.fs-tm-tables-grid.fs-tm-cols-{1|2|3}`.
5. Filter: `fs_tm_tables_overview_html`.

---

## 7. Anbindung von Add-ons

Der Core bietet eine **öffentliche Hook-Schnittstelle**. Add-on-Plugins greifen
ausschließlich darüber zu; der Core kennt Add-ons nicht über Namen, Slugs oder
Klassen, sondern ausschließlich über diese neutralen Hooks und Filter.

### 7.1 Vom Core bereitgestellte Hooks

| Hook | Zweck |
| --- | --- |
| `fs_tm_loaded` | Startpunkt; trägt `Plugin::API_VERSION` als Argument |
| `fs_tm_addon_active` | Filter: Add-on meldet `true`, Core blendet redundante Bereiche aus |
| `fs_tm_capability` | Filter: Einheitliche Berechtigung (Standard: `manage_options`) |
| `fs_tm_admin_menu` | Add-ons registrieren Untermenü-Einträge |
| `fs_tm_admin_toolbar` | Filterchips über der Mannschaftsliste |
| `fs_tm_admin_header_actions` | Rechte Seite der Leiste |
| `fs_tm_admin_footer_links` | Footer-Links |
| `fs_tm_admin_version_label` | Filter: Versionsangabe in der Leiste |
| `fs_tm_admin_notices` | Zusätzliche Meldungen |
| `fs_tm_admin_pages` | Filter: Seitenliste der Navigation |
| `fs_tm_team_edit_sections_before` | Vor den Core-Sektionen der Mannschaftskarte |
| `fs_tm_team_edit_sections` | Nach den Core-Sektionen, vor der Action-Bar |
| `fs_tm_team_form_saved` | Nach erfolgreichem Speichern einer Mannschaft |
| `fs_tm_team_data` | Filter: Reichert einen Mannschaftsdatensatz an |
| `fs_tm_export_team_entry` | Filter: Nimmt Add-on-Angaben in den Export auf |
| `fs_tm_before_team_deleted` | Vor der Löschung einer Mannschaft |
| `fs_tm_register_addon_blocks` | Add-ons registrieren eigene Blöcke |
| `fs_tm_import_external_backup` | Add-ons übernehmen externe Backup-Formate; ein Rückgabewert ungleich `null` gilt als „verarbeitet“, ein `WP_Error` als „erkannt, aber fehlgeschlagen“ |
| `fs_tm_block_editor_data` | Filter: Ergänzt Editordaten |
| `fs_tm_design_palette` | Filter: Zentrale Farbvorgabe (`head_bg`, `head_text`, `body_bg`, optional `page_bg`) für `Design\Scheme`. Add-ons speisen hierüber ihr Corporate Design ein; daraus rechnet `Support\Contrast` alle Schrift-, Linien- und Plakettenfarben |
| `fs_tm_page_background` | Filter: Ermöglicht Themes oder Add-ons, den Seitenhintergrund explizit vorzugeben (Standard: automatische Erkennung aus Theme/body.background-color) |
| `fs_tm_settings_sections_before` | Vor den Core-Einstellungssektionen |
| `fs_tm_settings_sections` | Nach den Core-Einstellungssektionen |
| `fs_tm_admin_sections_after_settings` | Nach allen Einstellungssektionen |
| `fs_tm_show_core_backup` | Filter: Add-on blendet die Core-Backup-Sektion aus |
| `fs_tm_help_groups` | Filter: Ergänzt die Feldreferenz |
| `fs_tm_help_sections` | Zusätzliche Handbuch-Abschnitte |
| `fs_tm_card_body_before` / `fs_tm_card_body_after` | Vor/nach dem Widget-Body |
| `fs_tm_widget_card_html` | Filter: Ganze Karten-Ausgabe |
| `fs_tm_tables_overview_html` | Filter: Tabellen-Übersicht-Ausgabe |
| `fs_tm_sanitize_period` | Filter: Zeiträume bereinigen |
| `fs_tm_sanitize_team_entry` | Filter: Mannschaftsdaten bereinigen |
| `fs_tm_load_remote_widgets` | Filter: Externe Inhalte komplett unterbinden (Anbindung an Consent-Plugins, siehe `docs/CONSENT-MANAGEMENT.md`) |
| `fs_tm_click_to_load` | Filter: Zwei-Klick-Lösung überschreiben |
| `fs_tm_show_attribution` | Filter: Frontend-Credit überschreiben (Standard: aus) |
| `fs_tm_widget_script_url` | Filter: Skript-URL von fussball.de |
| `fs_tm_recognized_blocks` | Filter: Blocknamen für die Seiten-Erkennung |
| `fs_tm_post_type_args` | Filter: Post-Type-Argumente |
| `fs_tm_allowed_card_html` | Filter: Erlaubte HTML-Elemente/Attribute für die `wp_kses()`-Prüfung der Block-Ausgabe |

### 7.2 Vom Add-on ergänzte Hooks

Add-ons können eigene Hooks bereitstellen und Core-Hooks ergänzen. Diese Hooks
sind in der Dokumentation des jeweiligen Add-ons beschrieben; der Core hängt
sie ausschließlich über die in §7.1 gelisteten Punkte ein.

---

## 8. Admin-Oberfläche

### 8.1 Seiten

| Seite | Slug | Inhalt |
| --- | --- | --- |
| ⚽ Teams & Widgets | `fs-tm-manager` | Mannschaftsliste, Formular, Widget-Inspector |
| ⚙️ Einstellungen | `fs-tm-settings` | Datenschutz, Datensicherung, Handbuch |

Add-ons können über den Filter `fs_tm_admin_pages` eigene Seiten in die
Navigation einhängen; der Core kennt und rendert diese Seiten nicht.

### 8.2 Aufbau jeder Verwaltungsseite

```
fs-tm-wrap
├── fs-tm-bar               Marke · Navigation · Support
├── fs-tm-toast-stack       schwebende Meldungen
└── fs-tm-view              austauschbarer Inhaltsbereich
    ├── fs-tm-collapsible-box   „Neue Mannschaft anlegen" (eingeklappt)
    ├── fs-tm-toolbar           Suche
    ├── Liste aus fs-tm-team-card
    │   └── fs-tm-section-box   je Themenblock eine Untersektion
    └── fs-tm-footer-row
```

Den Rahmen stellt `Admin\Layout`. `Layout::open($slug)` und `Layout::close()`
klammern den Inhalt.

### 8.3 Navigation ohne vollständigen Seitenaufbau

Das Admin-Skript (`src/admin/admin.js`) fängt Klicks auf interne Verweise und
Formular-Sendungen ab, holt die Zielseite per `fetch` und tauscht daraus nur
`#fs-tm-view` und die Leiste aus.

Entscheidend: Serverseitig ändert sich **nichts**. Nonces, Uploads,
Weiterleitungen und Transients bleiben unberührt — `fetch` folgt dem 302 des
Post/Redirect/Get-Musters von selbst, `response.url` liefert die Zieladresse
für `history.pushState`.

Ausgenommen sind Verweise mit `target="_blank"`, `data-fs-tm-download` und
die Export-Adressen; sie müssen den Browser erreichen.

Nach jedem Austausch feuert `fs-tm-view-loaded`. Alles, was nicht am Dokument
delegiert ist (Sortierung, Formular-Ausgangswerte), wird dort neu aufgesetzt.

### 8.4 Widget-Inspector (Modal)

Das Modal `#fs-tm-preview-modal` zeigt das Widget in drei Breiten
(Desktop, Tablet 640 px, Mobile 360 px). Es lädt das fussball.de-Skript
direkt und zeigt UUID und Typ als Metadaten.

### 8.5 Lazy Loading der Mannschaftskarte

Nur die geöffnete Karte (`data-loaded="1"`) enthält das Formular. Beim Öffnen
wird das Formular per AJAX (`fs_tm_get_team_form`) nachgeladen und in
`.fs-tm-team-body` eingefügt. So bleibt die Seitenladung schnell.

### 8.6 Validierung und Draft-Verhalten

Bricht das Speichern wegen einer Validierungsfehler ab (z. B. Überlappung),
werden die Rohdaten als Transient `fs_tm_temp_draft_{slug}` (60 s) und die
Fehler als `fs_tm_edit_errors_{slug}` gespeichert. Beim erneuten Öffnen werden
die Daten übernommen — der Nutzer verliert seine Eingaben nicht.

---

## 9. Assets und Build

- Build über `@wordpress/scripts` (webpack). Einstiegspunkte in `webpack.config.js`:
  - `blocks/fussball-widget/index`
  - `blocks/tables-overview/index`
  - `admin`
  - `frontend`
- `npm run build` erzeugt `build/`. **Nie** Dateien in `build/` bearbeiten.
- `webpack.config.js` enthält den `SourceHeaderPlugin`: Er stellt jeder erzeugten
  `.js`/`.css`-Datei **nach** der Minifizierung einen Kommentar voran, der die
  zugehörige Quelldatei in `src/`, den Rebuild-Befehl (`npm ci && npm run build`)
  und die Repository-URL nennt. Damit ist zu jeder ausgelieferten kompilierten
  Datei die menschenlesbare Quelle auffindbar (WordPress-Richtlinien 1 und 4).
- `build.sh` (Linux/macOS) und `build.ps1` (Windows) bauen, erzeugen die `.pot`
  (via WP-CLI) und packen das ZIP. Das ZIP enthält neben `build/` immer auch
  `src/`, `package.json`, `package-lock.json` und `webpack.config.js`, damit die
  Quellen und das Build-Werkzeug Teil der Distribution sind. `docs/` wird nicht
  mitgepackt.
- Registrierte Handles:
  - `fs-tm-admin-css` / `fs-tm-admin-js` — Admin
  - `fs-tm-frontend-css` / `fs-tm-frontend-js` — Frontend (nur auf Seiten mit Block)
  - `fussballde-widgets` — externes fussball.de-Skript (nur bei Bedarf)
- Add-ons können die registrierten Handles als Abhängigkeit ihrer eigenen
  Assets deklarieren.

### 9.1 Frontend-Assets nur bei Bedarf

`block.json` referenziert `style: "fs-tm-frontend-css"` und
`viewScript: "fs-tm-frontend-js"`. WordPress liefert die Assets nur aus, wenn
eine Seite einen Block des Plugins enthält.

---

## 10. Sicherheit

Verbindlich für jeden Beitrag:

| Bereich | Regel |
| --- | --- |
| Berechtigung | Jede schreibende Aktion prüft `Plugin::currentUserCan()`. |
| Nonce | Jedes Formular `wp_nonce_field()`, jede Aktion `check_admin_referer()`, jedes AJAX `check_ajax_referer()`. |
| Anzeigeparameter | Nach dem Speichern wird über `Notices::redirect()` umgeleitet. Diese Methode hängt an jede Adresse den Nonce `fs_tm_notice_nonce` (Aktion `fs_tm_notice`). Erst nach erfolgreicher Prüfung werten `Notices::render()`, `AdminPage::renderHubPage()` (`opened`) und `SettingsPage::render()` (`section`) die Parameter aus. Ohne gültigen Nonce zeigt die Seite ihren Normalzustand — untergeschobene Meldungen oder aufgeklappte Karten sind damit ausgeschlossen. Add-ons, die auf eine Core-Seite umleiten, ergänzen den Nonce über `Notices::withNonce()`. |
| Eingaben | `sanitize_text_field`, `sanitize_key`, `intval`, `wp_unslash`. Niemals `$_POST` direkt weiterreichen. Bei Feldern, die Felder (Arrays) liefern, `map_deep(wp_unslash($_POST[…]), 'wp_strip_all_tags')` verwenden — `sanitize_text_field()` liefert bei Arrays einen leeren String. |
| Ausgaben | `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`. Kein ungeprüftes `echo`. |
| Rückgabewerte von Callbacks | Die `render_callback`s der Blöcke geben ihr Markup erst nach `wp_kses()` gegen `BlockRegistrar::allowedCardHtml()` zurück. Die Liste erweitert `wp_kses_allowed_html( 'post' )` um `template`, `select`, `option`, `optgroup` und `input` (inkl. `data-*`), weil `wp_kses_post()` diese Elemente entfernen würde. Erweiterbar über `fs_tm_allowed_card_html`. |
| UUIDs | `TeamRepository::isValidUuid()` prüft das Format `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`. |
| Datenformate | `valid_from` / `valid_to` gegen `^\d{4}-\d{2}-\d{2}$` geprüft. |
| Externe Ziele | Skript-URL von fussball.de ist fest verdrahtet, filterbar über `fs_tm_widget_script_url`. |
| SQL | Ausschließlich `get_posts` / `WP_Query`. Kein direktes `$wpdb` (außer `uninstall.php`). |
| Dateien | Import nur `.json`, maximal 2 MB, `is_uploaded_file()`. Gelesen wird ausschließlich über die Dateisystem-API von WordPress (`WP_Filesystem`), nie mit direkten PHP-Dateifunktionen. |
| Import-Schema | `validateTeamSchema()` prüft Struktur und Typen; externe Formate werden über `fs_tm_import_external_backup` an Add-ons delegiert. |
| CSS-Injection | Farbwerte in Blockattributen werden als CSS-Custom-Properties ausgegeben;
  der Browser validiert die Werte. Keine `url()`-Injection möglich. |

---

## 11. Deinstallation

`uninstall.php` entfernt:

| Aktion | Details |
| --- | --- |
| `fs_tm_team`-Posts | Alle Posts des Post-Typs inkl. Meta (`wp_delete_post($id, true)`) |
| Option `fs_tm_teams` | Legacy-Option |
| Option `fs_tm_teams_pre_cpt` | Migration-Sicherung |
| Option `fs_tm_version` | Versionsstand |
| Option `fs_tm_click_to_load` | Zwei-Klick-Lösung |
| Option `fs_tm_show_attribution` | Frontend-Credit |
| Option `fs_tm_teams_restore_point` | Wiederherstellungspunkt |
| Transient `fs_tm_pages_tree` | Seitenbaum-Cache |
| WP-Cache `all_teams` / `fs_tm` | Objekt-Cache |

---

## 12. Tests

Im Ordner `Tests/` des Repositorys liegt eine Docker-WordPress-Umgebung samt
Prüfskripten.

| Skript | Zweck |
| --- | --- |
| `lint-php.ps1` | Syntaxprüfung aller PHP-Dateien des Plugins |
| `smoke-deploy.ps1` | WordPress einrichten, Plugin einspielen und aktivieren |
| `smoke-test.php` | Datenschicht: Repositories, Zeiträume, Migration, Rendering |
| `admin-test.php` | Rendert alle Verwaltungsseiten |
| `frontend-test.php` | Rendert eine Seite mit allen Blöcken über `do_blocks()` |
| `verify.ps1` | Gesamtlauf einschließlich Verhalten ohne aktives Add-on |

Ablauf:

```powershell
cd Tests
docker compose up -d
.\lint-php.ps1
.\smoke-deploy.ps1
.\verify.ps1
```

---

## 13. Arbeitsanweisung für Coding-Agents

1. **Erst lesen:** dieses Dokument, dann `DESIGN-SYSTEM.md`, dann die betroffene Klasse.
2. **Datenzugriff nur über `Data\TeamRepository`.** Kein `get_post_meta()` außerhalb.
3. **Kein Datumsrechnen außerhalb von `TeamRepository`.**
4. **Kein Add-on-Code kopieren.** Fehlt ein Hook, wird er im Core additiv ergänzt und
   in Abschnitt 7.1 dokumentiert.
5. **Neuer Block?** `src/blocks/<name>/{block.json,index.js,edit.js}`, Renderer in
   `BlockRegistrar`, Eintrag in `webpack.config.js`.
6. **Neue Klasse im Markup?** Zuerst in den Komponenten-Katalog des Design-Systems eintragen.
7. **Zeichenketten** immer mit Textdomain `fabriel-team-manager` und Übersetzer-Kommentar
   bei Platzhaltern.
8. **Nach jeder Änderung:** `npm run build`, danach `Tests\verify.ps1` und die
   Checkliste am Ende des Design-Systems.
9. **Niemals** Dateien in `build/` oder `node_modules/` ohne Not ändern.

---
