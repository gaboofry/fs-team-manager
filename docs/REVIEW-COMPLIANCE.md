# WordPress.org Review — Umsetzungsstand (CORE-Plugin `fabriel-team-manager`)

Dieses Dokument beschreibt, **was tatsächlich im Code umgesetzt ist**, um die
Beanstandungen der automatischen Vorprüfung des WordPress.org-Plugin-Review-Teams
zu beheben. Es ersetzt den früheren Planungsstand (`REVIEW-FIX-PLAN.md`), der
nicht mehr dem Code entsprach.

Geltungsbereich: **ausschließlich dieses Verzeichnis** (CORE). Das PRO-Add-on ist
ein eigenes, nicht öffentliches Projekt; in keiner ausgelieferten Datei dieses
Plugins darf ein PRO-Pfad, -Dateiname, -Blockname oder eine PRO-Lizenzlogik
auftauchen. `docs/` steht nicht auf der Datei-Liste, die `build.sh` / `build.ps1`
in das ZIP kopieren, und wird deshalb nicht ausgeliefert.

Öffentliche Adressen des Plugins:

- Repository: <https://github.com/gaboofry/fs-team-manager.git>
- Internetauftritt: <https://teammanager.fabrielsoftware.de/>

---

## 1. Kompilierte Dateien ohne zugängliche Quellen (Richtlinien 1 und 4)

**Beanstandung:** Das ZIP enthielt kompilierte/minifizierte Assets, ohne dass die
menschenlesbaren Quellen und das Build-Werkzeug öffentlich zugänglich waren.

**Umsetzung:**

| Ort | Maßnahme |
| --- | --- |
| `src/` | Vollständige, unkomprimierte Quellen (JS/SCSS/Blöcke) sind Teil der Distribution. |
| `package.json`, `package-lock.json`, `webpack.config.js` | Build-Werkzeug (`@wordpress/scripts`) und exakte Abhängigkeitsversionen liegen bei. |
| `build.sh`, `build.ps1` | Reproduzierbarer Build inkl. ZIP-Erstellung; kopieren `src/` und die Build-Konfiguration in das ZIP. `docs/` und die Build-Skripte selbst stehen nicht auf dieser Liste und werden daher nicht ausgeliefert — der Plugin Check meldet ausführbare Dateien (`.sh`, `.ps1`) als „application_detected“. Wer das ZIP nachbauen will, findet die Skripte im Repository. |
| `webpack.config.js` → `SourceHeaderPlugin` | Stellt jeder erzeugten `.js`/`.css`-Datei **nach** der Minifizierung einen Kommentar voran, der Quelldatei, Rebuild-Befehl und Repository nennt. |
| `src/**/*.js` | Jede Einstiegsdatei nennt im Kopf die erzeugte Zieldatei und den Build-Befehl. |
| `readme.txt` → `== Source code & development ==` | Zuordnung Build-Datei → Quelldatei, Repository-URL, Klon-/Build-Befehle, Hinweis, dass keine Fremdbibliotheken gebündelt werden. |
| `fabriel-team-manager.php` | Plugin-Header verweist auf Quellcode-Repository und Build-Werkzeug; `Plugin URI` zeigt auf den Internetauftritt. |
| `composer.json` | `homepage` sowie `support.source` / `support.issues` gepflegt. |

Der Build ist reproduzierbar: `npm ci && npm run build` erzeugt `build/`
byte-identisch zum ausgelieferten Stand.

**Offener Punkt (extern):** Das Repository muss noch öffentlich geschaltet
werden. Alle Verweise zeigen bereits auf die endgültige Adresse.

## 2. Nicht escapte Rückgabewerte von Callbacks

**Beanstandung:** Rückgabewerte der Block-`render_callback`s bzw. der darin
angewendeten Filter wurden nicht escaped.

**Umsetzung in `includes/Blocks/BlockRegistrar.php`:**

- Alle dynamischen Werte werden bereits beim Zusammenbau des Markups mit
  `esc_html()`, `esc_attr()` bzw. `esc_url()` behandelt.
- Zusätzlich läuft **jeder** Rückgabewert der vier öffentlichen Filter
  (`fs_tm_card_body_before`, `fs_tm_card_body_after`, `fs_tm_widget_card_html`,
  `fs_tm_tables_overview_html`) durch `self::kses()`, also durch `wp_kses()`
  gegen `BlockRegistrar::allowedCardHtml()`.
- `allowedCardHtml()` erweitert `wp_kses_allowed_html( 'post' )` um genau die
  Elemente, die die Karten benötigen: `template`, `select`, `option`, `optgroup`,
  `input` — jeweils mit den nötigen globalen Attributen (`class`, `id`, `style`,
  `title`, `data-*`, `aria-*`). Das ist notwendig, weil `wp_kses_post()` diese
  Elemente **ersatzlos entfernen** und damit Zeitraum-Auswahl, Lazy-Load-Panes
  und die Consent-Checkbox zerstören würde.
- Die Liste ist über den Filter `fs_tm_allowed_card_html` erweiterbar.
- Die Docblocks der vier Filter dokumentieren den Escaping-Vertrag.

**Weitere Fundstellen derselben Art:**

- `includes/Admin/ExportImport.php` — die Sicherung wird mit `wp_send_json()`
  ausgeliefert. Die Kernfunktion setzt den Content-Type, kodiert die Daten
  genau einmal und beendet die Anfrage; ein HTML-Escaping würde die Datei
  beschädigen. Im gesamten Plugin gibt es **keine** `phpcs:ignore`-Kommentare:
  Prüfmeldungen werden behoben, nicht unterdrückt.
- `includes/Plugin.php` — `PHP_VERSION` in der Aktivierungsmeldung wird mit
  `esc_html()` ausgegeben.

## 3. Dateizugriffe über die WordPress-API

**Hintergrund:** Die WordPress-Coding-Standards verlangen, dass Plugins statt
direkter PHP-Dateifunktionen die Dateisystem-API der Installation verwenden
(`WordPress.WP.AlternativeFunctions`). Ein manueller Review prüft diesen Punkt
mit.

**Umsetzung in `includes/Admin/ExportImport.php`:**

- Der Import liest die hochgeladene Datei über `WP_Filesystem` statt über
  `file_get_contents()`.
- Hochgeladene Dateien liegen im temporären Verzeichnis von PHP. Ein für die
  Installation eingerichteter FTP- oder SSH-Transport erreicht dieses
  Verzeichnis nicht, deshalb wird die Transportart vorab mit
  `get_filesystem_method()` bestimmt und nur bei `direct` über
  `WP_Filesystem()` eingebunden. Andernfalls kommt `WP_Filesystem_Direct`
  unmittelbar zum Einsatz — so wird nie eine unnötige FTP-Verbindung
  aufgebaut und der Import funktioniert unabhängig von der Konfiguration
  der Installation.
- Vor dem Lesen prüft `is_file()` und `is_readable()` der API; ein nicht
  lesbarer Pfad bricht den Import kontrolliert ab.

Im gesamten Plugin gibt es damit keinen einzigen direkten Dateizugriff
(`file_get_contents`, `fopen`, `file_put_contents`, `unlink` …).

## 4. Credits und Links im Frontend (Richtlinie 10)

Richtlinie 10 verlangt wörtlich: *„All 'Powered By' or credit displays and links
included in the plugin code must be optional and default to not show on users'
front-facing websites."*

Der Hinweis „Eingebunden durch Fabriel Software Teammanager" unterhalb jeder
Karte ist deshalb ein Opt-in:

* `TeamRepository::attributionEnabled()` liest `fs_tm_show_attribution` mit dem
  Standardwert `0`; der Filter `fs_tm_show_attribution` erlaubt eine
  programmatische Steuerung.
* Der Wert wird über `wp_localize_script()` als `showAttribution` an
  `build/frontend.js` übergeben; `addCardAttribution()` gibt Text und Link nur
  aus, wenn er wahr ist.
* Die Checkbox steht sichtbar im Abschnitt „Datenschutz & externe Inhalte" der
  Einstellungsseite, nicht in einer Dokumentation oder in Nutzungsbedingungen.
* Das Plugin funktioniert ohne den Hinweis vollständig; er ist keine Bedingung
  für irgendeine Funktion.

Der Link „Widget-Einwilligung zurücksetzen" bleibt unabhängig davon bestehen:
Er ist ein funktionales Bedienelement der Zwei-Klick-Lösung und kein Credit.

## 5. Banner und Screenshots (Verzeichnis-Assets)

Banner und Screenshots werden von WordPress.org aus dem `assets/`-Verzeichnis des
SVN-Repositories bezogen und gehören nicht in das ausgelieferte Plugin. Sie
liegen deshalb unter `.wordpress-org/` und werden von `build.sh`/`build.ps1`
bewusst **nicht** in das ZIP kopiert. Das Banner hat das von WordPress.org
erwartete Format `banner-772x250.png`. Der in `readme.txt` vorhandene
`Banner tag:`-Header existiert in der Readme-Spezifikation nicht und wurde
entfernt; die Screenshot-Sektion enthält nur noch die nummerierte Liste.

## 6. Prüfschritte

```bash
php -l <jede PHP-Datei>          # Syntaxprüfung
npm ci && npm run build          # reproduzierbarer Build
bash build.sh                    # ZIP-Erstellung (enthält src/, nicht docs/)
```

Zusätzlich wurde das CORE-Plugin mit PHP_CodeSniffer 3.13.6, den WordPress
Coding Standards und den Sniffs des offiziellen Plugin-Check-Werkzeugs geprüft:

```bash
phpcs --standard=<plugin-check>/phpcs-rulesets/plugin-review.xml fs-team-manager/
phpcs --standard=PluginCheck fs-team-manager/
phpcs --standard=WordPress \
      --sniffs=WordPress.NamingConventions.PrefixAllGlobals,WordPress.WP.I18n \
      --runtime-set prefixes fs_tm,FS_TM,FabrielSoftware \
      --runtime-set text_domain fs-team-manager fs-team-manager/
```

Ergebnis: **0 Errors**. Es verbleiben ausschließlich Meldungen der Kategorie
`WordPress.Security.NonceVerification.Recommended` auf reinen Anzeigeparametern
(`$_GET['section']`, `$_GET['opened']` und die Schlüssel der Erfolgsmeldungen).
Diese Parameter lösen keine Zustandsänderung aus, werden ausschließlich über
`sanitize_key()` gelesen und dienen nur der Auswahl eines Abschnitts oder einer
Meldung. Das Regelwerk von Plugin Check stuft diese Sniff-Gruppe selbst
ausdrücklich als Warnung herab („This is triggered on all GET/POST access, it
can't be an error"). Zustandsändernde Aktionen laufen ausnahmslos über
`check_admin_referer()`.

Zusätzlich wurde das Kses-Verhalten gegen die WordPress-Kernimplementierung
geprüft: Das vollständige Karten-Markup übersteht `allowedCardHtml()`
unverändert, während `<script>`, `on*`-Attribute, `javascript:`-URLs, `<iframe>`
und `<form>` entfernt werden.

Ebenso wurde der Dateizugriff gegen die Kernimplementierung geprüft: Der
Upload wird byteweise identisch gelesen (inklusive BOM und Sonderzeichen),
fehlende Pfade und Verzeichnisse liefern `null`, und bei erzwungenem
`FS_METHOD = 'ftpext'` greift die direkte Transportart, ohne dass eine
FTP-Verbindung aufgebaut wird.

## 7. Abgleich mit den 18 „Detailed Plugin Guidelines"

| # | Richtlinie | Stand im CORE-Plugin |
|---|---|---|
| 1 | GPL-kompatibel | `License: GPL-2.0-or-later` in Plugin-Header und `readme.txt`, `license.txt` liegt bei. Keine fremden Bibliotheken. |
| 2 | Verantwortung der Entwickler | Kein fremder Code, keine Fernwartung, keine Telemetrie. |
| 3 | Stabile Version verfügbar | `Stable tag` und `Version:` sind identisch (1.2.2). |
| 4 | Code menschenlesbar | `src/` und die Build-Werkzeuge liegen im Paket, jede Datei in `build/` trägt einen Kopf mit Quelldatei und Rebuild-Befehl, `readme.txt` verlinkt das öffentliche Repository. |
| 5 | Keine Trialware | Keine gesperrten Funktionen, kein Zeitlimit, kein Upsell in der Oberfläche. Zusatzfunktionen liegen in einem separaten Add-on außerhalb von WordPress.org. |
| 6 | SaaS erlaubt, aber dokumentiert | Abschnitt „External services" beschreibt fussball.de inklusive Anbieter, übertragener Daten sowie Links zu Nutzungsbedingungen und Datenschutzerklärung. |
| 7 | Kein Tracking ohne Einwilligung | Das Plugin selbst ruft keinen externen Server auf (kein `wp_remote_*`, kein cURL). Der externe Widget-Dienst kann über die Zwei-Klick-Lösung und den Filter `fs_tm_load_remote_widgets` an eine Einwilligung gekoppelt werden. |
| 8 | Kein ausführbarer Fremdcode | Eigene Skripte und Styles liegen lokal in `build/`. Extern geladen wird ausschließlich das dokumentierte Dienst-Skript des Widget-Anbieters. Kein eigener Updater, keine Admin-Iframes. |
| 9 | Nichts Illegales oder Irreführendes | Eigenständiges Produkt, Markenhinweis in der `readme.txt`. |
| 10 | Keine Credits ohne Zustimmung | Siehe Abschnitt 4: Opt-in, Standard aus. |
| 11 | Kein Kapern des Dashboards | Meldungen erscheinen nur auf den eigenen Plugin-Seiten; `admin_notices` wird nicht global bespielt, es gibt keine Weiterleitung nach der Aktivierung und keine Werbebanner. |
| 12 | Kein Spam in der Readme | 5 Tags (erlaubt sind bis zu 12), keine Konkurrenz-Tags, keine Affiliate-Links; der `Donate link` ist der offizielle Header. |
| 13 | WordPress-Standardbibliotheken nutzen | Keine mitgelieferte Kopie von jQuery o. Ä.; Block-Skripte binden `wp.*` als registrierte Abhängigkeiten ein. |
| 14 | Keine häufigen Commits | Betrifft das SVN-Release-Repository, nicht den Code. |
| 15 | Versionsnummer erhöhen | Auf 1.2.2 erhöht, Changelog ergänzt. |
| 16 | Vollständiges Plugin einreichen | Funktionsumfang vollständig, ZIP wird über `build.sh` erzeugt. |
| 17 | Marken respektieren | Slug `fabriel-team-manager` (vom Review-Team bestätigt) beginnt mit der eigenen Marke, nicht mit einer fremden; Markenhinweis in der `readme.txt`. |
| 18 | Rechte des Plugin-Teams | Zur Kenntnis genommen. |

---

## 8. Review-Runde 2: Plugin-Header „Tested up to“

**Beanstandung:** Der Header `Tested up to` war sowohl in `fabriel-team-manager.php` als auch in `readme.txt` angegeben. Laut WordPress.org-Vorgaben ist `Tested up to` kein gültiger Header für die Haupt-PHP-Datei und darf ausschließlich in `readme.txt` deklariert werden, um Fehlinterpretationen bei der Versions- und Kompatibilitätsanzeige im Plugin-Verzeichnis auszuschließen.

**Umsetzung:**
- Zeile `Tested up to: 7.1` aus dem Docblock-Header von `fabriel-team-manager.php` entfernt.
- Deklaration `Tested up to: 7.1` verbleibt unverändert und ausschließlich in `readme.txt`.
- Slug `fabriel-team-manager` und Anzeigename `Fabriel Team Manager` wurden vom WordPress.org-Review-Team final übernommen.

