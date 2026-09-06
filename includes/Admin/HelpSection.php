<?php
namespace FabrielSoftware\TeamManager\Admin;

if (!defined('ABSPATH')) exit;

/**
 * Feldreferenz für die Verwaltungsoberfläche und die Blöcke.
 */
class HelpSection {

    /**
     * Je Feld: input = was einzutragen ist, effect = Wirkung, relation = Wechselwirkungen,
     * editor = Sichtbarkeit im Blockeditor, frontend = Sichtbarkeit auf der Website.
     */
    private static function groups() {
        return apply_filters('fs_tm_help_groups', array(

            array(
                'title' => __('Neue Mannschaft anlegen', 'fs-team-manager'),
                'intro' => __('Diese drei Felder erscheinen nur beim erstmaligen Anlegen. Danach werden sie im Bereich „Stammdaten & Zuordnung“ der jeweiligen Mannschaft gepflegt.', 'fs-team-manager'),
                'fields' => array(
                    array(
                        'label'    => __('Mannschaftsname', 'fs-team-manager'),
                        'input'    => __('Den Namen, unter dem die Mannschaft auf der Website erscheinen soll, zum Beispiel „1. Mannschaft“ oder „B-Jugend (U17)“. Pflichtfeld.', 'fs-team-manager'),
                        'effect'   => __('Dient als Überschrift der Karte, als Eintrag in der Auswahlliste des Blocks und als Titel in der Mannschaftsliste im Backend.', 'fs-team-manager'),
                        'relation' => __('Kann jederzeit geändert werden, ohne dass bestehende Blöcke ihre Zuordnung verlieren. Die Zuordnung erfolgt über das Kürzel, nicht über den Namen.', 'fs-team-manager'),
                        'editor'   => __('In der Auswahlliste „Mannschaft auswählen“ des Blocks „Spielplan & Tabelle“.', 'fs-team-manager'),
                        'frontend' => __('Fett gesetzt in der Kopfzeile der Karte.', 'fs-team-manager'),
                    ),
                    array(
                        'label'    => __('Kürzel (Slug)', 'fs-team-manager'),
                        'input'    => __('Eine eindeutige technische Kennung aus Kleinbuchstaben, Ziffern und Bindestrichen, zum Beispiel „senioren-1“. Bleibt das Feld leer, wird das Kürzel automatisch aus dem Namen erzeugt.', 'fs-team-manager'),
                        'effect'   => __('Verbindet die Mannschaft mit den eingefügten Blöcken und dient als Schlüssel in Sicherungsdateien.', 'fs-team-manager'),
                        'relation' => __('Nach dem Anlegen nicht mehr änderbar, da bereits eingefügte Blöcke sonst ihre Zuordnung verlieren würden. Ein bereits vergebenes Kürzel wird abgelehnt. Beim Import gilt: gleiches Kürzel aktualisiert die vorhandene Mannschaft, ein neues legt eine zusätzliche an.', 'fs-team-manager'),
                        'editor'   => __('Nicht sichtbar, wird intern verwendet.', 'fs-team-manager'),
                        'frontend' => __('Nicht sichtbar.', 'fs-team-manager'),
                    ),
                ),
            ),

            array(
                'title' => __('Stammdaten & Zuordnung', 'fs-team-manager'),
                'intro' => __('Grundangaben einer bereits angelegten Mannschaft.', 'fs-team-manager'),
                'fields' => array(
                    array(
                        'label'    => __('Zugeordnete WordPress-Seite', 'fs-team-manager'),
                        'input'    => __('Die Unterseite, auf der diese Mannschaft dargestellt wird. Alternativ „Keine automatische Zuordnung“, wenn die Mannschaft keine eigene Seite hat.', 'fs-team-manager'),
                        'effect'   => __('Hat zwei getrennte Wirkungen: Der Block erkennt die Mannschaft auf dieser Seite automatisch, und die Kartenüberschrift wird zu einem Link auf diese Seite.', 'fs-team-manager'),
                        'relation' => __('Der Link erscheint nur, wenn die Seite veröffentlicht ist; bei Entwürfen bleibt die Überschrift unverlinkt. Jede Seite sollte nur einer Mannschaft zugeordnet sein – bei Doppelbelegung gewinnt die in der Liste weiter oben stehende Mannschaft. Die Auswahlliste ist in „Seiten mit Team/Fussball Block“ und „Weitere Seiten“ gruppiert und wird bis zu zwölf Stunden zwischengespeichert; neu angelegte Seiten erscheinen daher unter Umständen verzögert.', 'fs-team-manager'),
                        'editor'   => __('Wirkt, sobald im Block „Automatisch (anhand Seite)“ eingestellt ist.', 'fs-team-manager'),
                        'frontend' => __('Die Kartenüberschrift wird zum Link auf diese Seite.', 'fs-team-manager'),
                    ),
                ),
            ),

            array(
                'title' => __('Zeitraum', 'fs-team-manager'),
                'intro' => __('Ein Zeitraum bündelt die Widget-IDs, die in einer bestimmten Spanne gelten – üblicherweise eine Saison. Mehrere Zeiträume ergeben die automatische Umschaltung und das Saison-Archiv im Frontend.', 'fs-team-manager'),
                'fields' => array(
                    array(
                        'label'    => __('Bezeichnung des Zeitraums', 'fs-team-manager'),
                        'input'    => __('Einen sprechenden Namen wie „Saison 2026/2027“ oder „Hinrunde“. Pflichtfeld.', 'fs-team-manager'),
                        'effect'   => __('Wird als Auswahltext angezeigt, überall dort, wo ein Zeitraum gewählt werden kann.', 'fs-team-manager'),
                        'relation' => __('Tragen zwei aufeinanderfolgende Zeiträume dieselbe Widget-ID, fasst die Website sie zu einem einzigen Eintrag zusammen und verbindet beide Bezeichnungen mit „&“.', 'fs-team-manager'),
                        'editor'   => __('In der Auswahlliste „Start-Zeitraum“, ergänzt um die Datumsspanne.', 'fs-team-manager'),
                        'frontend' => __('Im Auswahlfeld rechts oben auf der Karte. Der heute gültige Zeitraum heißt dort „Aktuell“, sofern der Block auf „Immer aktuell“ steht.', 'fs-team-manager'),
                    ),
                    array(
                        'label'    => __('Gültig ab (Start)', 'fs-team-manager'),
                        'input'    => __('Das Datum, ab dem dieser Zeitraum gelten soll. Pflichtfeld. Das Datum darf in der Zukunft liegen – genau das erlaubt es, den Saisonwechsel vorzubereiten.', 'fs-team-manager'),
                        'effect'   => __('Steuert die automatische Umschaltung. Ab diesem Tag zeigt die Website die hier hinterlegten IDs.', 'fs-team-manager'),
                        'relation' => __('Zeiträume werden nach diesem Datum sortiert, der neueste steht oben. Das Startdatum muss nach dem Enddatum des vorherigen Zeitraums liegen, sonst meldet das Plugin eine Überlappung und verweigert das Speichern.', 'fs-team-manager'),
                        'editor'   => __('Erscheint in Klammern hinter der Bezeichnung.', 'fs-team-manager'),
                        'frontend' => __('Erscheint als Datumsspanne in Klammern hinter der Bezeichnung im Auswahlfeld.', 'fs-team-manager'),
                    ),
                    array(
                        'label'    => __('Gültig bis (optional)', 'fs-team-manager'),
                        'input'    => __('Das Enddatum. Bleibt es leer, gilt der Zeitraum unbegrenzt weiter – sinnvoll für den jeweils aktuellen Zeitraum.', 'fs-team-manager'),
                        'effect'   => __('Beendet die Gültigkeit. Nach diesem Tag wechselt die Website auf den nächsten passenden Zeitraum.', 'fs-team-manager'),
                        'relation' => __('Existiert ein späterer Zeitraum, ist das Enddatum Pflicht. Fällt der heutige Tag in eine Lücke zwischen zwei Zeiträumen, zeigt die Website den zuletzt gültigen Zeitraum und kennzeichnet ihn mit dem Hinweis „Letzter Zeitraum“ – es bleibt also nie leer.', 'fs-team-manager'),
                        'editor'   => __('Erscheint in Klammern hinter der Bezeichnung.', 'fs-team-manager'),
                        'frontend' => __('Erscheint als Datumsspanne in Klammern hinter der Bezeichnung im Auswahlfeld.', 'fs-team-manager'),
                    ),
                    array(
                        'label'    => __('Spielplan-ID', 'fs-team-manager'),
                        'input'    => __('Die ID aus dem Einbettungscode von fussball.de – der Wert des Attributs „data-id“ beim Widget-Typ „team-matches“. Sie besteht aus 36 Zeichen mit Bindestrichen. Leer lassen, wenn für diesen Zeitraum kein Spielplan angezeigt werden soll.', 'fs-team-manager'),
                        'effect'   => __('Liefert die Daten für die Blockansicht „Spielplan“.', 'fs-team-manager'),
                        'relation' => __('Das Format wird beim Speichern geprüft; eine fehlerhafte Eingabe wird rot markiert und blockiert das Speichern. Ist das Feld leer, erscheint dieser Zeitraum in der Ansicht „Spielplan“ gar nicht erst zur Auswahl. Die Schaltfläche „Testen“ öffnet das Widget in der Vorschau, ohne es zu speichern.', 'fs-team-manager'),
                        'editor'   => __('Bestimmt, welche Zeiträume unter „Start-Zeitraum“ auswählbar sind, wenn die Ansicht auf „Spielplan“ steht.', 'fs-team-manager'),
                        'frontend' => __('Der eingebettete Spielplan im Inhaltsbereich der Karte.', 'fs-team-manager'),
                    ),
                    array(
                        'label'    => __('Tabellen-ID', 'fs-team-manager'),
                        'input'    => __('Die ID aus dem Einbettungscode von fussball.de für den Widget-Typ „table“. Leer lassen, wenn für diesen Zeitraum keine Tabelle angezeigt werden soll.', 'fs-team-manager'),
                        'effect'   => __('Liefert die Daten für die Blockansicht „Tabelle“ und für den Block „Tabellen-Übersicht (Grid)“.', 'fs-team-manager'),
                        'relation' => __('In der Tabellen-Übersicht wird ausschließlich der heute gültige Zeitraum verwendet. Mehrere Mannschaften mit derselben Tabellen-ID – also aus derselben Liga – werden dort automatisch zu einer gemeinsamen Karte zusammengefasst.', 'fs-team-manager'),
                        'editor'   => __('Bestimmt, welche Zeiträume unter „Start-Zeitraum“ auswählbar sind, wenn die Ansicht auf „Tabelle“ steht.', 'fs-team-manager'),
                        'frontend' => __('Die eingebettete Ligatabelle im Inhaltsbereich der Karte.', 'fs-team-manager'),
                    ),
                    array(
                        'label'    => __('Club-Matches', 'fs-team-manager'),
                        'input'    => __('Häkchen setzen, wenn die hinterlegte Spielplan-ID nicht den Spielplan einer einzelnen Mannschaft, sondern den aller Mannschaften des Vereins enthält.', 'fs-team-manager'),
                        'effect'   => __('Schaltet den angeforderten Widget-Typ von „team-matches“ auf „club-matches“ um.', 'fs-team-manager'),
                        'relation' => __('Betrifft ausschließlich die Spielplan-ID, nie die Tabellen-ID. Die Beschriftung des Spielplan-Feldes wechselt beim Setzen des Häkchens entsprechend.', 'fs-team-manager'),
                        'editor'   => __('Nicht sichtbar.', 'fs-team-manager'),
                        'frontend' => __('Statt des Mannschaftsspielplans erscheint der Spielplan des gesamten Vereins.', 'fs-team-manager'),
                    ),
                ),
            ),

            array(
                'title' => __('Reihenfolge, Suche und Vorschau', 'fs-team-manager'),
                'intro' => __('Bedienelemente der Mannschaftsliste.', 'fs-team-manager'),
                'fields' => array(
                    array(
                        'label'    => __('Sortiergriff (Drag & Drop)', 'fs-team-manager'),
                        'input'    => __('Die Mannschaft am Griff links im Kopfbereich anfassen und an die gewünschte Position ziehen. Die neue Reihenfolge wird sofort gespeichert.', 'fs-team-manager'),
                        'effect'   => __('Legt die Reihenfolge im Backend und im Block „Tabellen-Übersicht (Grid)“ fest.', 'fs-team-manager'),
                        'relation' => __('Entscheidet zusätzlich, welche Mannschaft gewinnt, wenn versehentlich zwei Mannschaften derselben Seite zugeordnet wurden.', 'fs-team-manager'),
                        'frontend' => __('Reihenfolge der Karten in der Tabellen-Übersicht.', 'fs-team-manager'),
                    ),
                    array(
                        'label'    => __('Suchfeld', 'fs-team-manager'),
                        'input'    => __('Einen Teil des Mannschaftsnamens oder des Kürzels eingeben.', 'fs-team-manager'),
                        'effect'   => __('Blendet nicht passende Mannschaften vorübergehend aus. Es werden keine Daten verändert.', 'fs-team-manager'),
                    ),
                    array(
                        'label'    => __('Schaltfläche „Testen“', 'fs-team-manager'),
                        'input'    => __('Erst eine ID in das zugehörige Feld eintragen, dann auf „Testen“ klicken.', 'fs-team-manager'),
                        'effect'   => __('Öffnet das Widget in einer Vorschau mit umschaltbarer Desktop-, Tablet- und Handybreite.', 'fs-team-manager'),
                        'relation' => __('Die Vorschau greift auf den aktuell im Feld stehenden Wert zu, nicht auf den gespeicherten. Sie lädt Inhalte direkt von fussball.de.', 'fs-team-manager'),
                    ),
                ),
            ),

            array(
                'title' => __('Datensicherung', 'fs-team-manager'),
                'intro' => __('Sicherung und Wiederherstellung aller Mannschaften, Zeiträume und Zuordnungen als JSON-Datei.', 'fs-team-manager'),
                'fields' => array(
                    array(
                        'label'    => __('Backup herunterladen', 'fs-team-manager'),
                        'input'    => __('Keine Eingabe nötig. Ein Klick erzeugt die Datei.', 'fs-team-manager'),
                        'effect'   => __('Lädt alle Mannschaften mit Zeiträumen und Seitenzuordnungen als JSON-Datei herunter.', 'fs-team-manager'),
                        'relation' => __('Empfehlenswert vor jedem Import und vor größeren Umbauten.', 'fs-team-manager'),
                    ),
                    array(
                        'label'    => __('Import-Modus „Ergänzen“', 'fs-team-manager'),
                        'input'    => __('Voreinstellung. Auswählen, wenn Mannschaften hinzugefügt oder aktualisiert werden sollen.', 'fs-team-manager'),
                        'effect'   => __('Mannschaften aus der Datei werden angelegt oder überschrieben. Alle übrigen bleiben unverändert erhalten.', 'fs-team-manager'),
                        'relation' => __('Maßgeblich ist das Kürzel. Ein bereits vorhandenes Kürzel wird vollständig durch den Inhalt der Datei ersetzt, einschließlich aller Zeiträume.', 'fs-team-manager'),
                    ),
                    array(
                        'label'    => __('Import-Modus „Ersetzen“', 'fs-team-manager'),
                        'input'    => __('Nur auswählen, wenn der aktuelle Bestand vollständig durch die Datei ersetzt werden soll.', 'fs-team-manager'),
                        'effect'   => __('Mannschaften, die nicht in der Datei stehen, werden gelöscht.', 'fs-team-manager'),
                        'relation' => __('Der vorherige Stand wird automatisch gesichert und lässt sich direkt nach dem Import über den Link in der Erfolgsmeldung einmalig wiederherstellen.', 'fs-team-manager'),
                    ),
                    array(
                        'label'    => __('JSON-Datei', 'fs-team-manager'),
                        'input'    => __('Eine zuvor mit diesem Plugin erzeugte Sicherungsdatei auswählen. Zulässig sind Dateien mit der Endung .json bis 2 MB.', 'fs-team-manager'),
                        'effect'   => __('Quelle für den Import.', 'fs-team-manager'),
                        'relation' => __('Enthaltene IDs werden beim Import genauso geprüft wie bei der Eingabe von Hand.', 'fs-team-manager'),
                    ),
                ),
            ),

            array(
                'title' => __('Datenschutz & externe Inhalte', 'fs-team-manager'),
                'intro' => __('Spielpläne und Tabellen werden von fussball.de geladen. Dabei wird die IP-Adresse der Besucher an den Anbieter übertragen.', 'fs-team-manager'),
                'fields' => array(
                    array(
                        'label'    => __('Zwei-Klick-Lösung aktivieren', 'fs-team-manager'),
                        'input'    => __('Häkchen setzen, wenn externe Inhalte erst nach ausdrücklicher Bestätigung durch den Besucher geladen werden sollen.', 'fs-team-manager'),
                        'effect'   => __('Anstelle des Widgets erscheint zunächst ein Hinweisfeld mit der Auswahl „Auswahl für diesen Browser merken“ und der Schaltfläche „Inhalt laden“. Erst danach wird eine Verbindung zu fussball.de aufgebaut.', 'fs-team-manager'),
                        'relation' => __('Setzt der Besucher zusätzlich das Häkchen „Auswahl merken“, gilt die Zustimmung in seinem Browser auch für künftige Seitenaufrufe. Ohne dieses Häkchen gilt sie nur für den aktuellen Aufruf. Weil die Entscheidung im Browser fällt, wirkt sie auch hinter einem Seiten-Cache.', 'fs-team-manager'),
                        'frontend' => __('Hinweisfeld mit Schaltfläche anstelle des Widgets.', 'fs-team-manager'),
                    ),
                    array(
                        'label'    => __('Anbindung an ein Consent-Management (z. B. Complianz)', 'fs-team-manager'),
                        'input'    => __('Keine Einstellung, sondern eine Zeile Code in der functions.php des Child-Themes oder in einem kleinen Mu-Plugin.', 'fs-team-manager'),
                        'effect'   => __('Der Filter „fs_tm_load_remote_widgets“ wird vor jeder Widget-Ausgabe geprüft. Gibt er false zurück, entsteht weder ein Widget-Container noch die Zwei-Klick-Box, und das Skript des Anbieters wird nicht eingebunden – es geht keine Anfrage an fussball.de.', 'fs-team-manager'),
                        'relation' => __('Beispiel für Complianz: add_filter( \'fs_tm_load_remote_widgets\', function ( $enabled ) { return function_exists( \'cmplz_has_consent\' ) ? (bool) cmplz_has_consent( \'marketing\' ) : $enabled; } ); – Der Filter wird beim Seitenaufbau ausgewertet; hinter einem Seiten-Cache ist die Zwei-Klick-Lösung die verlässlichere Wahl. Blockiert ein Consent-Plugin stattdessen das Skript des Anbieters, lädt das Plugin auch beim Umschalten eines Zeitraums nichts nach.', 'fs-team-manager'),
                        'frontend' => __('Ohne Einwilligung bleibt die Stelle des Widgets leer bzw. zeigt den Hinweis, dass externe Inhalte deaktiviert sind.', 'fs-team-manager'),
                    ),
                ),
            ),

            array(
                'title' => __('Block „Spielplan & Tabelle“', 'fs-team-manager'),
                'intro' => __('Einstellungen in der rechten Seitenleiste des Blockeditors, sichtbar bei ausgewähltem Block.', 'fs-team-manager'),
                'fields' => array(
                    array(
                        'label'    => __('Mannschaft auswählen', 'fs-team-manager'),
                        'input'    => __('Entweder „Automatisch (anhand Seite)“ oder eine feste Mannschaft. Die automatische Erkennung ist die empfohlene Einstellung für Mannschaftsseiten.', 'fs-team-manager'),
                        'effect'   => __('Legt fest, wessen Daten der Block anzeigt.', 'fs-team-manager'),
                        'relation' => __('Die automatische Erkennung funktioniert nur, wenn der Seite in den Stammdaten einer Mannschaft zugeordnet wurde. Fehlt die Zuordnung, bleibt der Block im Frontend leer. Beim Wechsel der Mannschaft wird der Start-Zeitraum auf „Immer aktuell“ zurückgesetzt.', 'fs-team-manager'),
                        'editor'   => __('Der ausgewählte Name erscheint in der Blockvorschau.', 'fs-team-manager'),
                        'frontend' => __('Bestimmt Überschrift und Inhalt der Karte.', 'fs-team-manager'),
                    ),
                    array(
                        'label'    => __('Ansicht', 'fs-team-manager'),
                        'input'    => __('„Spielplan“ oder „Tabelle“.', 'fs-team-manager'),
                        'effect'   => __('Legt fest, welche der beiden hinterlegten IDs verwendet wird.', 'fs-team-manager'),
                        'relation' => __('Bestimmt zugleich, welche Zeiträume unter „Start-Zeitraum“ zur Auswahl stehen – nämlich nur solche mit einer passenden ID. Beim Wechsel wird der Start-Zeitraum zurückgesetzt.', 'fs-team-manager'),
                        'editor'   => __('Wird unter der Mannschaft in der Blockvorschau angezeigt.', 'fs-team-manager'),
                        'frontend' => __('Symbol und Kennzeichnung rechts oben auf der Karte sowie der eingebettete Inhalt.', 'fs-team-manager'),
                    ),
                    array(
                        'label'    => __('Start-Zeitraum', 'fs-team-manager'),
                        'input'    => __('„Immer aktuell (Datum)“ ist die empfohlene Einstellung. Ein fester Zeitraum ist nur für Sonderfälle sinnvoll, etwa eine Rückblickseite.', 'fs-team-manager'),
                        'effect'   => __('Legt fest, welcher Zeitraum beim Aufruf der Seite vorausgewählt ist.', 'fs-team-manager'),
                        'relation' => __('Bei „Immer aktuell“ wählt die Website den Zeitraum anhand des heutigen Datums – der Block braucht beim Saisonwechsel nicht angefasst zu werden. Wird ein fester Zeitraum gewählt, entfällt diese Automatik für diesen Block dauerhaft.', 'fs-team-manager'),
                        'editor'   => __('Auswahlliste mit Bezeichnung und Datumsspanne.', 'fs-team-manager'),
                        'frontend' => __('Bestimmt lediglich die Vorauswahl. Besucher können über das Auswahlfeld auf der Karte jederzeit einen anderen Zeitraum ansehen.', 'fs-team-manager'),
                    ),
                    array(
                        'label'    => __('Farbschema', 'fs-team-manager'),
                        'input'    => __('„Zentrales Design“, „Hell“, „Dunkel“ oder „Eigene Farben“.', 'fs-team-manager'),
                        'effect'   => __('Bestimmt Kartenkopf und Inhaltsfläche. Schrift-, Linien- und Plakettenfarben werden daraus berechnet und halten den Mindestkontrast nach WCAG 2.1 AA ein.', 'fs-team-manager'),
                        'relation' => __('„Zentrales Design“ folgt der Vorgabe des Add-ons; ohne Add-on gilt das dunkle Blau der Voreinstellung. Erst „Eigene Farben“ blendet die Farbfelder ein.', 'fs-team-manager'),
                        'editor'   => __('Die Blockvorschau zeigt die berechneten Farben unmittelbar.', 'fs-team-manager'),
                        'frontend' => __('Gesamte Karte.', 'fs-team-manager'),
                    ),
                    array(
                        'label'    => __('Kartenkopf', 'fs-team-manager'),
                        'input'    => __('Eine Farbe aus der Palette des Themes oder ein eigener Wert. Nur bei „Eigene Farben“ sichtbar.', 'fs-team-manager'),
                        'effect'   => __('Grundfarbe der gesamten Karte.', 'fs-team-manager'),
                        'relation' => __('Über der Kopfzeile liegt eine leichte Abstufung, damit sie sich vom Inhaltsbereich absetzt. Sie erscheint deshalb etwas kräftiger als der gewählte Wert.', 'fs-team-manager'),
                        'editor'   => __('Die Blockvorschau übernimmt die Farbe unmittelbar.', 'fs-team-manager'),
                        'frontend' => __('Kopfzeile und Rahmen der Karte.', 'fs-team-manager'),
                    ),
                    array(
                        'label'    => __('Inhaltsfläche', 'fs-team-manager'),
                        'input'    => __('Die Fläche hinter dem eingebetteten Widget. Nur bei „Eigene Farben“ sichtbar.', 'fs-team-manager'),
                        'effect'   => __('Färbt den Inhaltsbereich der Karte. Ein dunkler Wert stellt alle Beschriftungen selbsttätig auf helle Schrift um.', 'fs-team-manager'),
                        'relation' => __('Das Widget selbst wird von fussball.de geliefert und behält sein eigenes Erscheinungsbild — es folgt der Farbwahl nicht.', 'fs-team-manager'),
                        'editor'   => __('Die Blockvorschau übernimmt die Farbe unmittelbar.', 'fs-team-manager'),
                        'frontend' => __('Fläche rund um den eingebetteten Spielplan oder die Tabelle.', 'fs-team-manager'),
                    ),
                    array(
                        'label'    => __('Schrift im Kartenkopf (optional)', 'fs-team-manager'),
                        'input'    => __('Nur ausfüllen, wenn die berechnete Schriftfarbe nicht passt.', 'fs-team-manager'),
                        'effect'   => __('Ersetzt die berechnete Schriftfarbe der Kopfzeile.', 'fs-team-manager'),
                        'relation' => __('Reicht der Kontrast zum Kartenkopf nicht aus, weist der Blockeditor darauf hin. Ein leeres Feld ist der sichere Weg.', 'fs-team-manager'),
                        'editor'   => __('Warnhinweis bei zu geringem Kontrast.', 'fs-team-manager'),
                        'frontend' => __('Sämtliche Beschriftungen in der Kopfzeile.', 'fs-team-manager'),
                    ),
                ),
            ),

            array(
                'title' => __('Block „Tabellen-Übersicht (Grid)“', 'fs-team-manager'),
                'intro' => __('Zeigt automatisch die aktuellen Ligatabellen aller Mannschaften an, für die eine Tabellen-ID hinterlegt ist. Dieser Block benötigt keine Mannschaftsauswahl.', 'fs-team-manager'),
                'fields' => array(
                    array(
                        'label'    => __('Spalten', 'fs-team-manager'),
                        'input'    => __('Eine, zwei oder drei Spalten für die Darstellung auf großen Bildschirmen.', 'fs-team-manager'),
                        'effect'   => __('Legt die Rasterbreite fest.', 'fs-team-manager'),
                        'relation' => __('Auf schmaleren Bildschirmen reduziert sich das Raster automatisch: drei Spalten werden unterhalb von 992 Pixeln zu zwei, unterhalb von 768 Pixeln wird stets einspaltig dargestellt.', 'fs-team-manager'),
                        'editor'   => __('Die gewählte Anzahl wird in der Blockvorschau genannt.', 'fs-team-manager'),
                        'frontend' => __('Anordnung der Tabellenkarten.', 'fs-team-manager'),
                    ),
                    array(
                        'label'    => __('Farbschema', 'fs-team-manager'),
                        'input'    => __('Wie beim Block „Spielplan & Tabelle“: zentrale Vorgabe, hell, dunkel oder eigene Flächenfarben.', 'fs-team-manager'),
                        'effect'   => __('Gilt einheitlich für alle Karten des Rasters.', 'fs-team-manager'),
                        'relation' => __('Eine getrennte Einfärbung einzelner Karten ist nicht vorgesehen. Die Kopfzeilen aller Karten einer Zeile werden automatisch auf gleiche Höhe gebracht.', 'fs-team-manager'),
                        'editor'   => __('Die Blockvorschau übernimmt die Farben unmittelbar.', 'fs-team-manager'),
                        'frontend' => __('Alle Karten des Rasters.', 'fs-team-manager'),
                    ),
                ),
            ),
        ));
    }

    private static function rowLabels() {
        return array(
            'input'    => __('Eingabe', 'fs-team-manager'),
            'effect'   => __('Wirkung', 'fs-team-manager'),
            'relation' => __('Wechselwirkungen', 'fs-team-manager'),
            'editor'   => __('Im Blockeditor', 'fs-team-manager'),
            'frontend' => __('Im Frontend', 'fs-team-manager'),
        );
    }

    public static function render() {
        $labels = self::rowLabels();
        ?>
        <div class="postbox fs-tm-box fs-tm-collapsible-box is-collapsed" id="fs-tm-help-box">
            <div class="fs-tm-collapsible-header" title="<?php esc_attr_e('Klicken zum Auf-/Zuklappen', 'fs-team-manager'); ?>">
                <div class="fs-tm-collapsible-title">
                    <span class="dashicons dashicons-arrow-down-alt2 fs-tm-accordion-arrow"></span>
                    <h2>📖 <?php esc_html_e('Handbuch & Feldreferenz', 'fs-team-manager'); ?></h2>
                </div>
                <span class="fs-tm-collapsible-hint"><?php esc_html_e('Klicken zum Öffnen', 'fs-team-manager'); ?></span>
            </div>
            <div class="fs-tm-collapsible-body">
                <p class="fs-tm-help-intro">
                    <?php esc_html_e('Jedes Eingabefeld dieses Plugins ist hier erklärt: was einzutragen ist, welche Wechselwirkungen bestehen und wo sich die Einstellung im Blockeditor und auf der Website auswirkt.', 'fs-team-manager'); ?>
                </p>

                <?php foreach (self::groups() as $group): ?>
                    <div class="fs-tm-help-group">
                        <h3><?php echo esc_html($group['title']); ?></h3>
                        <?php if (!empty($group['intro'])): ?>
                            <p class="fs-tm-help-group-intro"><?php echo esc_html($group['intro']); ?></p>
                        <?php endif; ?>

                        <div class="fs-tm-help-grid">
                            <?php foreach ($group['fields'] as $field): ?>
                                <div class="fs-tm-help-field">
                                    <h4><?php echo esc_html($field['label']); ?></h4>
                                    <dl>
                                        <?php foreach ($labels as $key => $label): ?>
                                            <?php if (empty($field[$key])) continue; ?>
                                            <dt><?php echo esc_html($label); ?></dt>
                                            <dd><?php echo esc_html($field[$key]); ?></dd>
                                        <?php endforeach; ?>
                                    </dl>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php do_action('fs_tm_help_sections'); ?>
            </div>
        </div>
        <?php
    }
}
