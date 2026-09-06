# Anbindung an ein Consent-Management (CORE-Plugin `fs-team-manager`)

Spielpläne und Tabellen stammen vom externen Dienst **fussball.de**. Beim Laden
werden Daten der Besucher – unter anderem die IP-Adresse – an den Anbieter
übertragen. Dieses Dokument beschreibt, wie sich das Laden an eine Einwilligung
koppeln lässt.

Es gibt drei Wege. Sie lassen sich kombinieren.

---

## 1. Eingebaute Zwei-Klick-Lösung (ohne Zusatz-Plugin)

„Fabriel Software → Einstellungen → Datenschutz & externe Inhalte →
**Zwei-Klick-Lösung aktivieren**".

Statt des Widgets erscheint ein Hinweisfeld mit der Auswahl „Auswahl für diesen
Browser merken" und der Schaltfläche „Inhalt laden". Erst der Klick baut die
Verbindung zu fussball.de auf.

* Die Entscheidung fällt **im Browser** – sie funktioniert daher auch hinter
  einem Seiten-Cache.
* Ohne Häkchen gilt sie für die laufende Sitzung, mit Häkchen dauerhaft für
  diesen Browser (`localStorage`).
* Unter jeder Karte steht der Link „Widget-Einwilligung zurücksetzen".

---

## 2. Filter `fs_tm_load_remote_widgets` (serverseitig, empfohlen)

Der Filter ist unverändert wirksam. Er wird in
`TeamRepository::remoteWidgetsEnabled()` ausgewertet und von
`BlockRegistrar::renderWidgetSlot()` **vor jeder** Ausgabe geprüft. Gibt er
`false` zurück, entsteht weder ein Widget-Container noch die Zwei-Klick-Box,
und das Skript des Anbieters wird gar nicht erst eingebunden
(`wp_enqueue_script()` wird in diesem Fall nicht erreicht). Es verlässt also
kein einziges Byte in Richtung fussball.de.

### Complianz

Complianz stellt `cmplz_has_consent( $category )` bereit
(`functional`, `preferences`, `statistics`, `marketing`). Widgets Dritter
gehören in der Regel zu `marketing`.

```php
// In der functions.php des Child-Themes oder in einem kleinen Mu-Plugin.
add_filter( 'fs_tm_load_remote_widgets', function ( $enabled ) {
    if ( ! function_exists( 'cmplz_has_consent' ) ) {
        return $enabled;
    }

    return (bool) cmplz_has_consent( 'marketing' );
} );
```

Damit die Seite nach der Einwilligung sofort die Widgets zeigt, empfiehlt sich
zusätzlich die Complianz-Einstellung „Seite nach Zustimmung neu laden"
(*Reload after consent*).

### Borlabs Cookie

Ab Version 3 stellt Borlabs Cookie die PHP-Schnittstelle `borlabsCookieApi()`
bereit; der Dienstschlüssel steht im Borlabs-Backend unter „Services".

```php
add_filter( 'fs_tm_load_remote_widgets', function ( $enabled ) {
    if ( ! function_exists( 'borlabsCookieApi' ) ) {
        return $enabled;
    }

    return (bool) borlabsCookieApi()->consentApi()->hasConsent( 'fussball-de' );
} );
```

### Real Cookie Banner

`wp_rcb_consent_given()` beantwortet die Frage nach der Einwilligung für einen
Dienst; der Slug stammt aus der Dienstliste des Plugins.

```php
add_filter( 'fs_tm_load_remote_widgets', function ( $enabled ) {
    if ( ! function_exists( 'wp_rcb_consent_given' ) ) {
        return $enabled;
    }

    return (bool) wp_rcb_consent_given( 'fussball-de' );
} );
```

### Kombination: Zwei-Klick-Lösung nur ohne Einwilligung

Wer die Widgets bei erteilter Einwilligung direkt zeigen und andernfalls das
Hinweisfeld einblenden möchte, schaltet die Zwei-Klick-Lösung dynamisch:

```php
add_filter( 'fs_tm_click_to_load', function ( $enabled ) {
    if ( ! function_exists( 'cmplz_has_consent' ) ) {
        return $enabled;
    }

    return ! cmplz_has_consent( 'marketing' );
} );
```

### Hinweis zum Seiten-Cache

Beide Filter werden **beim Aufbau der Seite** ausgewertet. Liefert ein
Seiten-Cache (WP Rocket, LiteSpeed, Varnish …) dieselbe HTML-Datei an alle
Besucher, gilt für alle die Entscheidung des ersten Aufrufs. Dann entweder den
Cache nach Einwilligungsstatus variieren lassen oder die Zwei-Klick-Lösung
(Weg 1) verwenden – sie entscheidet im Browser und ist daher cache-fest.

---

## 3. Blockieren des Anbieter-Skripts durch das Consent-Plugin

Ohne aktive Zwei-Klick-Lösung bindet das Plugin das Skript des Anbieters ganz
normal über `wp_enqueue_script()` ein (Handle `fussballde-widgets`, URL
`https://www.fussball.de/widgets.js`). Consent-Plugins mit Skript-Blocker
erkennen es dadurch und stellen es auf einen nicht ausführbaren Typ um
(`type="text/plain"` und ein eigenes Attribut für die Adresse).

`build/frontend.js` erkennt diesen Zustand: Ist das Skript stillgelegt, lädt das
Plugin auch beim Umschalten eines Zeitraums **keine** Inhalte nach und zeigt
keine Ladeanzeige. Es entsteht also kein Umweg an der Blockade vorbei.

Der Skript-Blocker allein blendet allerdings keinen Platzhalter des
Consent-Plugins ein und verhindert nichts, was serverseitig bereits im HTML
steht. Verlässlicher ist deshalb Weg 2 – oder Weg 1.

---

## 4. Prüfliste für Betreiber

1. Datenschutzerklärung um den Dienst fussball.de ergänzen (Anbieter,
   übertragene Daten, Links siehe `readme.txt`, Abschnitt „External services").
2. Einen der drei Wege aktivieren.
3. Im privaten Fenster prüfen: Vor der Einwilligung darf im Netzwerk-Protokoll
   des Browsers **keine** Anfrage an `fussball.de` oder `next.fussball.de`
   erscheinen.
4. Zeitraum-Umschaltung ebenfalls vor der Einwilligung prüfen – auch dort darf
   keine Anfrage entstehen.
