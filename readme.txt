=== Fabriel Team Manager ===
Contributors: fabrielsoftware
Donate link: https://paypal.me/fabergab
Tags: fussball, soccer, sports, tables, blocks
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Zentrale Verwaltung von Mannschaften, Zeiträumen, Spielplänen und Tabellen für Widgets von fussball.de.

== Description ==

**Einmal zentral erfassen statt jedes Jahr mühsam suchen und verteilen.**

**Das Problem bisher:**
Vereinswebmaster müssen, wenn sie die offiziellen kostenlosen Widgets von fussball.de nutzen möchten, auf jeder Mannschaftsseite Widgets separat pflegen. Bei Tabellen-Widgets muss mit jeder neuen Saison ein neues Widget angelegt und hinterlegt werden. Das bedeutet: Jede Saison für 10 bis 20 Mannschaften (Herren, Frauen, Jugend) jeweils ein oder zwei kryptische, rein IT-technische Widgets mit 36-stelligen UUIDs auf 20 verschiedenen WordPress-Unterseiten von Hand austauschen – meist spätabends im Ehrenamt, fehleranfällig und hektisch kurz vor dem ersten Spieltag.

**Die Lösung:**
Eine zentrale Schaltzentrale im WordPress-Backend: Alle Teams, Zeiträume und IDs werden an einem einzigen Ort gepflegt. Auf den Unterseiten liegt lediglich ein eingebundener Block – ganz ohne manuelle Auswahl einer kryptischen ID oder dergleichen. Die Website erledigt den Rest vollautomatisch.

= Die fünf wichtigsten Vorteile (Der Riesengewinn) =

**1. Ein zentraler Ort statt 20 Einzelseiten:** Mannschaften, Widget-IDs und Zeiträume werden gebündelt an einer Stelle verwaltet. Die zwei intelligenten Gutenberg-Blöcke holen sich die passenden Daten selbstständig.

**2. Den Saisonwechsel vorbereiten statt durchleiden:** Neue IDs werden einfach mit einem Startdatum versehen – Wochen im Voraus und in aller Ruhe. Am Stichtag stellt die Website vollautomatisch und punktgenau um. Niemand muss mehr um Mitternacht vor dem Computer sitzen, um den Saisonwechsel live zu schalten.

**3. Tippfehler verhindern statt reparieren:** Jede ID wird bereits bei der Eingabe auf das gültige Format (UUID) geprüft. Über den integrierten Widget-Inspector lässt sich jedes Widget direkt im Backend auf Desktop-, Tablet- und Smartphone-Breite live testen.

**4. Routinearbeit wird zu echtem Mehrwert (Automatisches Saisonarchiv):** Da abgelaufene Zeiträume erhalten bleiben, entsteht im Frontend ganz von allein ein Archiv: Besucher können direkt über der Tabelle zwischen aktueller Saison, Vorsaison oder Hin- und Rückrunde umschalten – ohne jeden zusätzlichen Pflegeschritt für den Webmaster.

**5. Blöcke einmal einrichten und nie wieder anfassen:** Sobald einer Mannschaft eine WordPress-Seite zugewiesen ist, erkennt der Block automatisch, auf welcher Seite er sich befindet. Dieselbe Block-Konfiguration funktioniert universell auf allen Mannschaftsseiten.

= Funktionen im Überblick =

**Mannschaftsverwaltung**
* Beliebig viele Mannschaften anlegen und verwalten
* Reihenfolge komfortabel per Drag & Drop sortieren
* Schnelle Live-Suche über alle Mannschaften
* Zuordnung einer WordPress-Seite pro Team (für automatische Block-Erkennung)
* Schutz vor Datenverlust bei ungespeicherten Eingaben

**Zeitraum-Steuerung**
* Beliebig viele Zeiträume pro Team (z. B. Saison 2026/2027, Hinrunde, Rückrunde)
* Getrennte Widget-IDs für Spielplan und Tabelle je Zeitraum
* Flexibles Umschalten zwischen Mannschafts- und Vereinsspielplan
* Automatische, datumsgesteuerte Aktivierung (Stichtag)
* Klare Warnhinweise mit optischer Hervorhebung bei überlappenden oder fehlenden Enddaten
* Intelligenter Fallback: Ist für das heutige Datum kein Zeitraum aktiv, wird automatisch der letzte gültige Stand mit einem dezenten Hinweis angezeigt – die Vereinsseite bleibt niemals leer

**Zwei native Gutenberg-Blöcke**
* *Spielplan & Tabelle*: Feste Mannschaftsauswahl oder vollautomatische Seitenerkennung, direktes Umschalten zwischen Spielplan und Tabelle, wählbarer Start-Zeitraum, frei anpassbare Farben für Kopf- und Inhaltsbereich.
* *Tabellen-Übersicht (Grid)*: Alle aktuellen Ligatabellen der Mannschaften übersichtlich in 1, 2 oder 3 Spalten im Raster dargestellt. Mannschaften, die in derselben Liga spielen, werden automatisch in einer gemeinsamen Karte gebündelt, statt doppelt zu erscheinen.
* Direkte Verlinkung der jeweiligen Mannschaftsseite im Kartentitel.
* Einheitliche Kartenhöhen im Raster, vollkommen responsiv bis zum Smartphone.

**Kontrolle, Datenschutz und Sicherheit**
* Live-Vorschau mit nativer Gerätesimulation (Desktop, Tablet, Smartphone) direkt im Backend.
* Vollständiges JSON-Backup aller Daten mit einem Klick.
* Wiederherstellung wahlweise additiv (ergänzend) oder ersetzend, inklusive Ein-Klick-Wiederherstellungspunkt (Undo).
* DSGVO-konforme Zwei-Klick-Lösung: Externe Inhalte von fussball.de werden erst nach ausdrücklicher Bestätigung durch den Seitenbesucher geladen.
* Vollständige Anbindung an gängige Consent-Management-Plugins (z. B. Complianz, Borlabs Cookie, Real Cookie Banner) über eine sauber dokumentierte Filterschnittstelle (`fs_tm_load_remote_widgets`).
* Optionaler Credit-Hinweis unter den Karten: standardmäßig vollständig deaktiviert (Opt-in) – kein unerwünschter Backlink auf Ihrer Website.
* Saubere und rückstandsfreie Deinstallation.

**Unsere Qualitätsversprechen**
* Kein Benutzerkonto, keine Registrierung, keine Aktivierungsschlüssel nötig.
* Das Plugin selbst sendet keinerlei Daten an uns.
* Eigene Skripte und Styles werden ausschließlich auf den Seiten geladen, die tatsächlich einen Plugin-Block enthalten.
* Vollständig übersetzbar (vollständige Textdomain-Integration).

= Für wen ist dieses Plugin gedacht? =

Für alle Fußballvereine mit mehr als zwei Mannschaften, die ihre Website eigenständig pflegen. Der Zeitgewinn und die Entlastung wachsen mit jeder weiteren Mannschaft. Ebenso ideal für Agenturen und Webmaster, die mehrere Vereins-Websites betreuen und administrative Routinearbeiten minimieren möchten.

= Hilfe direkt im Plugin =

Unter „Fabriel Software > Einstellungen“ finden Sie das integrierte „Handbuch & die Feld-Referenz“. Dort wird jedes Eingabefeld detailliert erläutert: was eingegeben werden muss, welche Wechselwirkungen bestehen und wo sich die jeweilige Einstellung im Gutenberg-Editor sowie im Frontend auswirkt.

**Markenhinweis**

Dieses Plugin ist ein unabhängiges Open-Source-Produkt. Es steht in keiner geschäftlichen Verbindung zum Deutschen Fußball-Bund e.V. (DFB) oder den Betreibern der Plattform fussball.de und wird von diesen weder unterstützt noch gesponsert. Alle genannten Marken und Produktnamen sind Eigentum der jeweiligen Inhaber und dienen ausschließlich der Beschreibung der technischen Kompatibilität.

== External services ==

Dieses Plugin bindet Spielpläne und Ligatabellen über den externen Dienst **fussball.de** ein. Ohne diesen Dienst kann das Plugin keine Spieldaten darstellen.

**Wann werden Daten übertragen?**

* Im Frontend: Sobald eine Seite aufgerufen wird, die einen Block des Plugins enthält und für die eine gültige Widget-ID hinterlegt ist.
* Im Backend: Wenn eine Widget-ID über die Schaltfläche „Testen“ in der Vorschau geöffnet wird.

**Welche Daten werden übertragen?**

* Die IP-Adresse des Besuchers
* Der User-Agent des Browsers sowie technische Verbindungsdaten
* Die im Plugin hinterlegte Widget-ID und der Widget-Typ

Es wird das Skript `https://www.fussball.de/widgets.js` geladen sowie die durch dieses Skript eingebettete Ansicht unter `https://next.fussball.de/`.

Dienstanbieter: DFB GmbH & Co. KG, fussball.de – https://www.fussball.de/
Nutzungsbedingungen: https://www.fussball.de/terms-of-use.action
Datenschutzerklärung: https://www.fussball.de/privacy-policy.action

Website-Betreiber sind dafür verantwortlich, die Einbindung dieses Dienstes in ihrer Datenschutzerklärung aufzuführen. Mit der Option „Zwei-Klick-Lösung aktivieren“ werden externe Inhalte erst nach vorheriger Zustimmung durch den Besucher geladen. Zudem kann das Laden über den PHP-Filter `fs_tm_load_remote_widgets` vollständig unterbunden und somit an ein Consent-Management-Plugin gekoppelt werden.

== Installation ==

1. Laden Sie das Plugin über das WordPress-Dashboard unter „Plugins > Installieren“ hoch und aktivieren Sie es.
2. Öffnen Sie im Administrationsmenü den Menüpunkt „Fabriel Software > Teams & Widgets“.
3. Legen Sie eine Mannschaft an und weisen Sie ihr optional die passende WordPress-Unterseite zu.
4. Tragen Sie die Widget-IDs aus dem fussball.de-Einbindungscode in den Zeitraum ein.
5. Fügen Sie auf den gewünschten Seiten den Block „Spielplan & Tabelle“ oder „Tabellen-Übersicht (Grid)“ ein.

== Frequently Asked Questions ==

= Woher erhalte ich die Widget-IDs? =

Die IDs befinden sich im Einbindungscode, den fussball.de für das jeweilige Widget generiert. Es handelt sich um den Wert des Attributs `data-id`, eine UUID im Format `01234567-89ab-cdef-0123-456789abcdef`.

= Was geschieht, wenn für das heutige Datum kein Zeitraum konfiguriert ist? =

Das Plugin zeigt automatisch den letzten gültigen Zeitraum an und markiert diesen in der Kopfzeile der Karte dezent mit dem Hinweis „Letzter Zeitraum“. So bleibt die Seite niemals leer.

= Werden meine Daten bei einem Import überschrieben? =

Nur, wenn Sie explizit den Modus „Ersetzen“ wählen. Der Standardmodus lautet „Hinzufügen“ (bestehende Teams bleiben erhalten). In beiden Fällen sichert das Plugin den vorherigen Zustand automatisch, sodass Sie den Stand direkt nach dem Import mit einem Klick wiederherstellen können.

= Welche Daten speichert das Plugin in der Datenbank? =

Mannschaften werden als interner Post-Type `fs_tm_team` gespeichert; Seitenzuordnung und Zeiträume liegen als Post-Meta vor. Zusätzlich existieren die Optionen `fs_tm_version`, `fs_tm_click_to_load` und `fs_tm_teams_restore_point` sowie ein Transient für die Seitenliste. Sämtliche Einträge werden bei einer Deinstallation des Plugins vollständig und sauber gelöscht.

= Ich habe von einer älteren Version aktualisiert. Muss ich etwas tun? =

Nein. Beim ersten Seitenaufruf nach dem Update werden die Daten automatisch in das aktuelle Datenformat migriert. Der frühere Zustand wird zur Sicherheit zusätzlich in der Option `fs_tm_teams_pre_cpt` vorgehalten. Dennoch empfehlen wir vor jedem Update ein Backup.

= Wie verbinde ich das Plugin mit einem Consent-Management-Plugin wie Complianz? =

Es stehen drei Integrationswege zur Verfügung, die sich auch kombinieren lassen:

1. **Integrierte Zwei-Klick-Lösung:** Aktivieren Sie diese unter „Fabriel Software > Einstellungen > Datenschutz & externe Inhalte“. Die Entscheidung wird lokal im Browser gespeichert und funktioniert daher auch hinter einem Page-Cache.
2. **Filter `fs_tm_load_remote_widgets` (Empfohlen):** Dieser Filter wird vor dem Rendern jedes Widgets ausgewertet. Gibt er `false` zurück, werden weder der Widget-Container noch die Zwei-Klick-Box ausgegeben, und das Skript von fussball.de wird gar nicht erst registriert – es geht keine Anfrage an fussball.de. Beispiel für Complianz:

`add_filter( 'fs_tm_load_remote_widgets', function ( $enabled ) { return function_exists( 'cmplz_has_consent' ) ? (bool) cmplz_has_consent( 'marketing' ) : $enabled; } );`

   Da der Filter beim Seitenaufbau serverseitig greift, kombinieren Sie ihn bitte mit einem Cache, der nach Consent-Status unterscheidet – oder nutzen Sie die integrierte Zwei-Klick-Lösung.
3. **Skript-Blockierung durch das Consent-Plugin:** Ohne Zwei-Klick-Lösung wird das Skript regulär eingereiht (Handle `fussballde-widgets`), sodass Skript-Blocker es erkennen. Wird das Skript blockiert, erkennt das Plugin dies und unterbindet auch beim nachträglichen Umschalten zwischen Zeiträumen eigenständige Ladeversuche.

Vollständige Code-Beispiele für Complianz, Borlabs Cookie und Real Cookie Banner finden Sie in der Datei `docs/CONSENT-MANAGEMENT.md` im Quellcode-Repository.

== Source code & development ==

Dieses Plugin enthält keinen verschleierten, minifizierten oder auf sonstige Weise unlesbaren Code ohne Quelldatei. Zu jeder kompilierten Datei im Ordner `build/` liegt das lesbare Gegenstück im Ordner `src/` bei, und beide sind im ausgelieferten Paket enthalten:

* `build/admin.js` und `build/admin.css` ← `src/admin/admin.js`, `src/admin/admin.scss`
* `build/frontend.js` und `build/frontend.css` ← `src/frontend/frontend.js`, `src/frontend/frontend.scss`
* `build/blocks/fussball-widget/index.js` ← `src/blocks/fussball-widget/index.js`, `edit.js`, `block.json`
* `build/blocks/tables-overview/index.js` ← `src/blocks/tables-overview/index.js`, `edit.js`, `block.json`

Jede generierte JavaScript-Datei trägt einen Kopfkommentar, der die Quelldatei und das öffentliche Repository ausweist.

Öffentliches Quellcode-Repository (vollständiger Code, Build-Tools und Historie):
https://github.com/gaboofry/fs-team-manager

Klonen mit: `git clone https://github.com/gaboofry/fs-team-manager.git`

= Build-Werkzeuge und Schritte zur Neuerstellung =

Die Build-Konfiguration ist dem Plugin beigelegt: `package.json`, `package-lock.json` und `webpack.config.js`.

1. Abhängigkeiten exakt installieren: `npm ci` (oder `npm install`)
2. JavaScript, SCSS und Block-Assets nach `build/` kompilieren: `npm run build`
3. Optional: Übersetzungsvorlage mit WP-CLI aktualisieren: `wp i18n make-pot . languages/fabriel-team-manager.pot --slug=fabriel-team-manager --domain=fabriel-team-manager --exclude=node_modules,vendor,src`

Der Build basiert auf `@wordpress/scripts` (webpack + Babel + Dart Sass); es wird kein weiteres Build-System benötigt. Das Repository enthält zudem die fertigen Skripte `build.sh` (Linux/macOS) und `build.ps1` (Windows), die den kompletten Build durchführen und das fertige Distributions-ZIP schnüren. Diese Skripte gehören nicht zum ausgelieferten Plugin; klonen Sie dafür das Repository.

= Verzeichnis-Assets (Banner, Screenshots) =

Banner- und Screenshot-Grafiken sind nicht Bestandteil des installierbaren Plugin-Pakets. Sie befinden sich im Verzeichnis `.wordpress-org/` des Quellcode-Repositories und gehören in den `assets/`-Ordner des WordPress.org-SVN-Repositories, damit sie beim normalen Plugin-Download nicht unnötig heruntergeladen werden.

= Drittanbieter-Bibliotheken =

Das Plugin bündelt keinerlei externe JavaScript- oder PHP-Bibliotheken von Drittanbietern. Der Ordner `build/` enthält ausschließlich aus `src/` kompilierten Code sowie die Webpack-Laufzeitumgebung von `@wordpress/scripts` (GPL-2.0-or-later, https://github.com/WordPress/gutenberg/tree/trunk/packages/scripts). Alle von den Blöcken genutzten WordPress-Pakete (`wp.blocks`, `wp.blockEditor`, `wp.components`, `wp.i18n`, `wp.element`) werden direkt von WordPress als registrierte Skript-Abhängigkeiten bezogen. Das externe Skript `https://www.fussball.de/widgets.js` wird direkt vom Anbieter geladen und ist nicht Teil dieses Pakets (siehe „External services“).

== Security ==

Die öffentlichen Block-Filter `fs_tm_widget_card_html`, `fs_tm_tables_overview_html`, `fs_tm_card_body_before` und `fs_tm_card_body_after` dienen als Erweiterungspunkte für Add-ons. Filter-Callbacks erhalten das vollständig maskierte HTML und dürfen ausschließlich maskiertes HTML zurückgeben. Alle dynamischen Werte werden vor Ausführung der Filter mit `esc_html()`, `esc_attr()` oder `esc_url()` abgesichert. Der finale Rückgabewert der Render-Callbacks – inklusive aller durch Filter ergänzten Elemente – durchläuft als letzte Verteidigungslinie `wp_kses()` mit der plugin-eigenen Positivliste (`fs_tm_allowed_card_html`). Diese Liste erweitert `wp_kses_allowed_html( 'post' )` gezielt um die für die Karten erforderlichen Elemente (`template`, `select`, `option`, `optgroup`, `input`), da `wp_kses_post()` diese Elemente stillschweigend entfernen würde.

== Screenshots ==

1. Zentrale Verwaltung: Mannschaften konfigurieren, Zeiträume festlegen und IDs für Spielplan und Tabelle pflegen.
2. Integrierter Widget-Inspector: Responsive Live-Vorschau der fussball.de-Widgets für Desktop-, Tablet- und Smartphone-Ansicht direkt im Backend.
3. Block-Editor: Intuitive Einrichtung des Blocks „Spielplan & Tabelle“ mit automatischer Erkennung der zugewiesenen Unterseite.
4. Darstellung im Frontend: Fertig gerenderte Ligatabelle mit direktem Saisonarchiv über komfortable Dropdown-Auswahl.
5. DSGVO Zwei-Klick-Lösung: Integrierter Platzhalter, der externe iFrames von fussball.de blockiert, bis der Besucher explizit zustimmt.

== Changelog ==

= 1.2.2 =
* Behoben: Bei deaktivierter Zwei-Klick-Lösung wurde jeder Spielplan und jede Tabelle doppelt eingebunden – das Anbieterskript wurde vom Frontend-Skript ein zweites Mal geladen, obwohl WordPress es bereits eingereiht hatte.
* Neu: Kleine animierte Ladeanzeige im Design des Plugins während des Ladens von Spielplan oder Tabelle von fussball.de.
* Geändert: In der Zwei-Klick-Box steht die Checkbox „Auswahl für diesen Browser merken“ nun vor dem Button „Inhalt laden“; der Button zeigt während des Einfügens einen Ladespinner.
* Geändert: Der aktive Menübereich wird nach dem Umschalten zwischen den Seiten des Plugins nun auch im WordPress-Untermenü korrekt hervorgehoben.
* Geändert: Neutralisiert ein Consent-Management-Plugin das Anbieterskript, bindet das Plugin beim Wechsel von Zeiträumen keine Inhalte mehr eigenmächtig ein.
* Verbessert: Höhenanpassungen (Resize-Messages) des eingebetteten Inhalts werden jetzt von einem zentralen Listener verarbeitet, der den Ursprung (Origin) des Senders validiert.
* Verbessert: Dokumentierte Integration für Consent-Management-Plugins (Complianz, Borlabs Cookie, Real Cookie Banner) in der FAQ, im Handbuch und in `docs/CONSENT-MANAGEMENT.md`.

= 1.2.1 =
* Geändert: Der Hinweis „Eingebunden durch Fabriel Software Teammanager“ unter jeder Karte ist jetzt Opt-in und standardmäßig deaktiviert (WordPress.org Plugin-Richtlinie 10); der Link zum Zurücksetzen der Einwilligung bei der Zwei-Klick-Lösung bleibt davon unberührt.
* Geändert: Hochgeladene Backup-Dateien werden über die WordPress-Filesystem-API statt über `file_get_contents()` eingelesen.
* Geändert: Banner- und Screenshot-Grafiken sind nicht mehr Teil des Plugin-Pakets; sie gehören in das `assets/`-Verzeichnis des WordPress.org-SVN-Repositories.
* Verbessert: Jeder Wert eines Uploads wird vor der Verarbeitung einzeln auf Existenz und Gültigkeit geprüft (Sanitization).
* Behoben: Ungültiger Header `Banner tag:` sowie nicht standardkonforme Screenshot-Notationen aus der Readme entfernt.

= 1.2.0 =
* Neu: Eigene Seite „Einstellungen“ – Datenschutz, Backup und Handbuch dorthin aus „Teams & Widgets“ ausgelagert.
* Neu: Durchgehende Navigationsleiste mit Markenbereich, Menüabschnitten und Support-Link; der separate Kopfbereich entfällt.
* Neu: Seitenwechsel und Speichern laden jetzt nur noch den Inhaltsbereich neu statt der gesamten Seite.
* Neu: Benachrichtigungen erscheinen als schwebende Toasts und verschieben nicht mehr das Seitenlayout.
* Verbessert: Hilfetexte werden an den Kartenrändern nicht mehr abgeschnitten.
* Verbessert: Beim Öffnen einer Mannschaft ist zunächst nur „Stammdaten & Zuordnung“ aufgeklappt.
* Neu: Erweiterungspunkte für Add-ons (`fs_tm_admin_pages`, `fs_tm_admin_toolbar`, `fs_tm_admin_header_actions`, `fs_tm_admin_footer_links`, `fs_tm_admin_version_label`, `fs_tm_admin_notices`, `fs_tm_settings_sections`, `fs_tm_settings_sections_before`, `fs_tm_show_core_backup`, `fs_tm_team_edit_sections_before`, `fs_tm_team_form_saved`).

= 1.1.0 =
* Geändert: Mannschaften werden als eigener interner Post-Type gespeichert statt in einer einzelnen Option; die Migration erfolgt automatisch und der alte Stand bleibt gesichert.
* Neu: Erweiterungspunkte für Add-ons (`fs_tm_capability`, `fs_tm_addon_active`, `fs_tm_widget_card_html`, `fs_tm_card_body_before`, `fs_tm_card_body_after`, `fs_tm_tables_overview_html`, `fs_tm_sanitize_period`, `fs_tm_team_data`, `fs_tm_team_saved`, `fs_tm_before_team_deleted`).
* Verbessert: Mannschaften werden nur noch einmal pro Seitenaufruf geladen und im Object-Cache gehalten.

= 1.0.1 =
* Neu: Zwei-Klick-Lösung für externe Inhalte inklusive Filter `fs_tm_load_remote_widgets`.
* Neu: Import im Modus „Hinzufügen“ oder „Ersetzen“ mit einmaliger Wiederherstellung des vorherigen Zustands.
* Neu: Ist für heute kein Zeitraum hinterlegt, wird der letzte gültige Zeitraum mit Hinweis angezeigt.
* Verbessert: Frontend-Assets werden nur noch geladen, wenn die Seite tatsächlich einen Block des Plugins enthält.
* Behoben: Validierungsfehler beim Speichern im Server führten zum Verwerfen der Eingaben; Eingaben bleiben nun erhalten.
* Sicherheit: Zusätzliche Integritätsprüfungen beim JSON-Import.

= 1.0.0 =
* Erstveröffentlichung.

== Upgrade Notice ==

= 1.1.0 =
Mannschaften werden beim ersten Aufruf nach dem Update automatisch in ein neues Speicherformat migriert. Bitte erstellen Sie vorab ein Backup.

= 1.0.1 =
Behebt einen Fehler, durch den fehlgeschlagene Speichervorgänge Formulardaten verwarfen, und ergänzt eine Zwei-Klick-Lösung für externe Inhalte.
