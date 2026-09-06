# Lizenz- und Datenschutzrahmen: Fabriel Software Team-Manager

Grundlage für das Plugin auf WordPress.org.

> Dieses Dokument ist selbsttragend: Es beschreibt ausschließlich die
> Lizenz- und Datenschutzlage dieses Plugins. Es enthält keine Verweise auf
> andere Projekte und keine Vertriebs- oder Lizenzlogik von Add-ons.

---

## 1. Lizenz

Das Plugin steht unter **GPL-2.0-or-later** — für WordPress.org verpflichtend.
Die Plugin-Kopfzeile in `fs-team-manager.php` und der `readme.txt` tragen:

```
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
```

Der komplette Quellcode ist in der Distribution enthalten (`src/` als
Quelltext, `build/` als erzeugtes Artefakt) und im öffentlichen
Quellcode-Repository verfügbar. Die Lizenzdatei `license.txt` enthält den
vollständigen Lizenztext.

**Konsequenz für den Code:** Es enthält keinen Bezahlcode, keinen
lizenzierten Codeabschnitt und keine Feature-Flags. Alle Funktionen sind
unbeschnitten verfügbar; es existieren nur neutrale Erweiterungs-Hooks.

---

## 2. Anforderungen von WordPress.org

| Anforderung | Umsetzung |
| --- | --- |
| Vollständig funktionsfähig ohne Bezahlung | Das Plugin verwaltet Mannschaften, Zeiträume, Spielpläne und Tabellen vollständig. Keine Funktion ist gesperrt. |
| Kein Bezahlcode im Plugin | Es existieren nur neutrale Hooks; es gibt keine lizenzierten Codeabschnitte. |
| Kein Nachladen von ausführbarem Code | Das Plugin lädt außer dem dokumentierten fussball.de-Widget-Skript keinen externen Code. |
| Keine Werbung | Es gibt keine Werbebannner, keine Upsell-Meldungen und keine Modal-Hinweise. |
| Keine Datenübertragung ohne Zustimmung | Das Plugin sendet keine Telemetrie. Externe Inhalte nur von fussball.de, mit optionaler Zwei-Klick-Lösung. |
| Deinstallation räumt auf | `uninstall.php` entfernt Post-Type, Meta, Optionen, Transient und Cache. |

---

## 3. Datenübertragung durch das Plugin

Das Plugin speichert **keine** personenbezogenen Daten. Übertragen wird
ausschließlich das fussball.de-Widget; der Rahmen (Zwei-Klick-Lösung,
Filter `fs_tm_load_remote_widgets`, Verantwortung des Betreibers) ist in der
`readme.txt` unter „External services" beschrieben. Fertige Beispiele für
Complianz, Borlabs Cookie und Real Cookie Banner stehen in
`docs/CONSENT-MANAGEMENT.md`.

| Anlass | Ziel | Übertragene Daten | Auslöser |
| --- | --- | --- | --- |
| Widget-Anzeige im Frontend | fussball.de | IP-Adresse, User-Agent des Besuchers | Seitenaufruf, bzw. Klick bei aktiver Zwei-Klick-Lösung |
| Widget-Vorschau im Backend | fussball.de | IP-Adresse des Redakteurs | Klick auf „Testen" im Widget-Inspector |

Es gibt keine Telemetrie, keine Nutzungsstatistik und kein „Phone Home"
ohne Anlass. Das Plugin stellt keine Verbindung zu einer eigenen
Hintergrund-Infrastruktur her; die einzige externe Adresse ist die
dokumentierte fussball.de-Widget-URL.

---

## 4. Datenhaltung

| Daten | Speicher | Personenbezug |
| --- | --- | --- |
| Mannschaften, Zeiträume, Seitenzuordnung | Post-Type `fs_tm_team` + Post-Meta | Nein |
| Zwei-Klick-Lösung, Wiederherstellungspunkt, Version | WordPress-Optionen | Nein |
| Seitenbaum-Cache | Transient `fs_tm_pages_tree` | Nein |

Die vollständige Löschbarkeit ist durch `uninstall.php` und die
JSON-Export-/Import-Funktion gegeben (Details:
[`ARCHITECTURE.md`](ARCHITECTURE.md), Abschnitte 4 und 11).

**Hinweis:** Personenbezogene Daten werden von diesem Plugin **nicht**
verarbeitet. Add-on-Plugins, die eigene Datenfelder einführen, sind für
deren DSGVO-Pflichten selbst verantwortlich.

---

## 5. Prüfliste vor Veröffentlichung

- [ ] Das Plugin enthält keine lizenzierten Codeabschnitte, keine
      Feature-Flags und keine Werbeanzeigen
- [ ] Das Plugin ist ohne Add-ons vollständig bedienbar
- [ ] Das Plugin trägt GPL-2.0-or-later in Kopfzeile und `readme.txt`
- [ ] Externe Dienste sind im `readme.txt` unter „External services"
      vollständig aufgeführt
- [ ] `uninstall.php` entfernt alle Optionen, Beiträge, Meta, Transient und Cache
