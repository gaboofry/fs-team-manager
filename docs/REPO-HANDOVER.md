# Übergabe an das Repository `fs-team-manager`

Diese Datei beschreibt, wie der Ordner `fs-team-manager` aus dem privaten
Arbeitsbereich in das eigenständige Repository
<https://github.com/gaboofry/fs-team-manager> übernommen wird und was vor dem
manuellen Umschalten auf **public** geprüft sein muss.

Kurzfassung: Es wird **nur** der Ordner `fs-team-manager` übernommen. Das
PRO-Add-on (`fs-team-manager-pro`) und die Entwicklungshilfen
(`fs-team-manager-dev`) bleiben im privaten Arbeitsbereich.

---

## 1. Was in das öffentliche Repository gehört

Der Inhalt von `fs-team-manager/` wird zum **Wurzelverzeichnis** des neuen
Repositories. Damit liegt `fs-team-manager.php` direkt in der Wurzel — so
erwartet es das WordPress.org-Prüfteam und so funktioniert ein Clone direkt als
Plugin-Ordner.

| Übernehmen | Grund |
| --- | --- |
| `fs-team-manager.php`, `uninstall.php`, `index.php` | Plugin-Einstieg |
| `includes/` | PHP-Quellcode |
| `src/` | JS-/SCSS-Quellcode (Richtlinie 4: Quellcode zu allem Kompilierten) |
| `build/` | Laufzeit-Assets; wird mit ausgeliefert und muss versioniert sein |
| `languages/fs-team-manager.pot` | Übersetzungsvorlage |
| `readme.txt`, `license.txt`, `README.md` | Verzeichnis-Readme, Lizenz, Repo-Startseite |
| `package.json`, `package-lock.json`, `webpack.config.js`, `composer.json` | Build-Werkzeug |
| `build.sh`, `build.ps1` | Reproduzierbarer Build |
| `docs/` | Entwicklerdokumentation (nicht Teil des ZIPs) |
| `.wordpress-org/` | Banner/Screenshots für das SVN-`assets/`-Verzeichnis |
| `.gitignore` | schließt `node_modules/`, `vendor/`, ZIPs und Entwicklungshilfen aus |

| Nicht übernehmen | Grund |
| --- | --- |
| `fs-team-manager-pro/` | nicht öffentliches Add-on |
| `fs-team-manager-dev/` | interne Hilfsdateien, Dumps, Testdaten |
| `.roo/` | lokale Werkzeugkonfiguration |
| `node_modules/`, `vendor/`, `*.zip` | erzeugte Artefakte |

## 2. Prüfliste vor dem Public-Schalten

**Inhalt**

- [ ] Keine Zeichenkette aus dem PRO-Add-on im Quellcode: `grep -ri "fs-team-manager-pro\|fs_tm_pro" includes src readme.txt` liefert nichts.
- [ ] Keine Zugangsdaten, Schlüssel, Tokens, Lizenzserver-Adressen oder Kundendaten (auch nicht in `docs/` und in Beispiel-JSON).
- [ ] Keine echten Personen-, Vereins- oder Mannschaftsdaten in Beispielen und Screenshots.
- [ ] `.wordpress-org/` enthält nur eigene oder eindeutig lizenzierte Bilder.
- [ ] Keine unterdrückten Prüfmeldungen (`phpcs:ignore`, `eslint-disable`) — Ursachen sind zu beheben.

**Versionen und Auslieferung**

- [ ] Gleichstand von `fs-team-manager.php` (Header `Version` und `FS_TM_VERSION`), `readme.txt` (`Stable tag`) und `package.json`.
- [ ] `readme.txt` hat einen Changelog-Eintrag für die neue Version.
- [ ] `languages/fs-team-manager.pot` neu erzeugt (`wp i18n make-pot …`).
- [ ] `npm ci && npm run build` erzeugt `build/` ohne Unterschied zum committeten Stand (`git status` bleibt sauber).
- [ ] `bash build.sh` erzeugt ein ZIP, dessen Wurzelordner `fs-team-manager` heißt.

**Recht und Richtlinien**

- [ ] `license.txt` (GPL-2.0-or-later) vorhanden, Kopfzeilen des Plugins nennen dieselbe Lizenz.
- [ ] `readme.txt` beschreibt unter „External services“ vollständig, welche Daten wann an fussball.de gehen.
- [ ] Markenhinweis (keine Verbindung zum DFB / zu fussball.de) steht in `readme.txt` und `README.md`.
- [ ] Stand der Prüfpunkte in `docs/REVIEW-COMPLIANCE.md` entspricht dem Code.

**Verlauf**

- [ ] Der Verlauf des neuen Repositories enthält keine Commits mit PRO-Code oder Entwicklungsdaten. Im Zweifel mit einem einzigen Anfangs-Commit starten, statt den Verlauf des Arbeitsbereichs zu übertragen.

## 3. Ablauf der Übernahme

1. Leeres privates Repository `gaboofry/fs-team-manager` anlegen (bereits vorhanden).
2. Inhalt von `fs-team-manager/` — ohne `node_modules/`, `vendor/` und ZIPs — in einen frischen Klon dieses Repositories kopieren.
3. Prüfliste aus Abschnitt 2 abarbeiten.
4. Commit und Push nach `main`.
5. Repository-Beschreibung, Themen (`wordpress`, `wordpress-plugin`, `gutenberg`, `fussball`) und Startseite <https://teammanager.fabrielsoftware.de/> setzen.
6. Issues aktivieren; `composer.json` verweist bereits auf `…/fs-team-manager/issues`.
7. Version taggen (`v1.2.2`) und optional ein Release mit dem ZIP aus `build.sh` anhängen.
8. Erst danach in den Repository-Einstellungen auf **public** umstellen.

## 4. Nach dem Public-Schalten

- Die Links in `fs-team-manager.php` („Source code“), `readme.txt` (Abschnitt „Source code & development“) und `composer.json` sind bereits gesetzt und werden mit dem Umschalten gültig — nach der Umstellung einmal aufrufen und prüfen.
- Für WordPress.org: nur die in `build.sh` gelistete Dateimenge in das SVN-`trunk/` einchecken; `docs/` und `.wordpress-org/` gehören nicht dazu. Die Bilder aus `.wordpress-org/` kommen in das SVN-`assets/`-Verzeichnis.
- Nach Freigabe im Verzeichnis den Tag im SVN (`tags/1.2.2`) setzen und `Stable tag` prüfen.

## 5. Zusammenspiel mit dem PRO-Add-on

Das PRO-Add-on hängt an den Hooks `fs_tm_addon_active`,
`fs_tm_register_addon_blocks`, `fs_tm_admin_pages` und
`fs_tm_import_external_backup`. Diese Namen sind Teil der öffentlichen
Schnittstelle des CORE-Plugins (siehe `docs/ARCHITECTURE.md`) und dürfen nicht
umbenannt werden, ohne das Add-on anzupassen — auch nicht nach der Trennung der
Repositories.
