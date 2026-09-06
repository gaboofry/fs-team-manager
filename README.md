# Fabriel Software Team-Manager

Zentrale Verwaltung von Mannschaften, Zeiträumen, Spielplänen und Tabellen für
Widgets von [fussball.de](https://www.fussball.de/) — als WordPress-Plugin mit
zwei Gutenberg-Blöcken.

* Internetauftritt: <https://teammanager.fabrielsoftware.de/>
* Lizenz: GPL-2.0-or-later (`license.txt`)
* Benötigt: WordPress ab 6.0, PHP ab 7.4

Dieses Repository enthält den **vollständigen, menschenlesbaren Quellcode** des
Plugins sowie das Build-Werkzeug. Es gibt keinen minifizierten Code ohne Quelle
und keine mitgelieferten Fremdbibliotheken.

---

## Aufbau

| Verzeichnis | Inhalt |
| --- | --- |
| `includes/` | PHP-Klassen (`FabrielSoftware\TeamManager\…`, PSR-4) |
| `src/` | Quellen für JavaScript, SCSS und die beiden Blöcke |
| `build/` | Erzeugt durch `npm run build` — nie von Hand bearbeiten |
| `languages/` | Übersetzungsvorlage `fs-team-manager.pot` |
| `docs/` | Architektur, Design-System, Recht, Consent-Management, Prüfstand |
| `.wordpress-org/` | Banner und Screenshots für das WordPress.org-Verzeichnis |

`docs/`, `.wordpress-org/` sowie die Build-Skripte selbst gehören **nicht** in das
ausgelieferte Plugin und werden von `build.sh` / `build.ps1` bewusst nicht in das
ZIP kopiert: Der Plugin Check von WordPress.org wertet Shell- und PowerShell-Dateien
als „application_detected“. Der Build bleibt trotzdem reproduzierbar, weil `src/`,
`package.json`, `package-lock.json` und `webpack.config.js` beiliegen.

## Bauen

```bash
npm ci          # exakte Build-Abhängigkeiten (@wordpress/scripts)
npm run build   # erzeugt build/ aus src/
bash build.sh   # zusätzlich: fertiges Distributions-ZIP
```

Unter Windows leistet `build.ps1` dasselbe. Der Build ist reproduzierbar:
`npm ci && npm run build` erzeugt `build/` byte-identisch zum ausgelieferten
Stand. Jede erzeugte Datei trägt einen Kopfkommentar mit Quelldatei,
Rebuild-Befehl und Repository.

Übersetzungsvorlage aktualisieren (benötigt WP-CLI):

```bash
wp i18n make-pot . languages/fs-team-manager.pot \
   --slug=fs-team-manager --domain=fs-team-manager \
   --exclude=node_modules,vendor,src
```

## Dokumentation

| Datei | Inhalt |
| --- | --- |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Aufbau, Datenmodell, Blöcke, Hook-Schnittstelle |
| [`docs/CONSENT-MANAGEMENT.md`](docs/CONSENT-MANAGEMENT.md) | Anbindung an Complianz, Borlabs Cookie, Real Cookie Banner |
| [`docs/DESIGN-SYSTEM.md`](docs/DESIGN-SYSTEM.md) | Gestaltungsvorgaben für Backend und Frontend |
| [`docs/LEGAL.md`](docs/LEGAL.md) | Lizenz- und Datenschutzrahmen |
| [`docs/REVIEW-COMPLIANCE.md`](docs/REVIEW-COMPLIANCE.md) | Umsetzungsstand der WordPress.org-Prüfung |
| [`docs/REPO-HANDOVER.md`](docs/REPO-HANDOVER.md) | Übergabe und Veröffentlichung dieses Repositories |
| [`readme.txt`](readme.txt) | Readme des WordPress.org-Verzeichnisses |

## Externe Dienste

Spielpläne und Tabellen stammen von fussball.de (DFB GmbH & Co. KG). Beim Laden
werden Daten der Besucher — unter anderem die IP-Adresse — an den Anbieter
übertragen. Umfang, Anlass und Anbieterangaben stehen in `readme.txt` unter
„External services“; die Kopplung an eine Einwilligung beschreibt
`docs/CONSENT-MANAGEMENT.md`.

## Mitwirken

Fehlerberichte und Vorschläge bitte als Issue. Für Pull Requests gilt:

1. Änderungen ausschließlich in `src/` und `includes/` vornehmen — `build/`
   entsteht durch `npm run build` und wird mit committet.
2. Keine Prüfmeldungen unterdrücken (`phpcs:ignore`, `eslint-disable` o. ä.):
   Ursachen beheben.
3. Ausgaben vollständig maskieren (`esc_html()`, `esc_attr()`, `esc_url()`); die
   Block-Ausgabe läuft zusätzlich durch `wp_kses()`.
4. Neue Zeichenketten immer mit der Textdomain `fs-team-manager`.

## Marken

Dieses Plugin ist ein eigenständiges Produkt. Es steht in keiner Verbindung zum
Deutschen Fußball-Bund (DFB) oder den Betreibern von fussball.de und wird von
ihnen weder unterstützt noch gesponsert. Genannte Marken und Produktnamen sind
Eigentum der jeweiligen Rechteinhaber und werden ausschließlich zur Beschreibung
der technischen Kompatibilität verwendet.
