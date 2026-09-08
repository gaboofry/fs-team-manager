/**
 * Fabriel Software Team-Manager - frontend script
 *
 * Human-readable source of build/frontend.js.
 * Compile with: npm ci && npm run build
 *
 * @package   fabriel-team-manager
 * @author    Fabriel Software (https://fabrielsoftware.de/)
 * @copyright Fabriel Software
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
 * @link      https://github.com/gaboofry/fs-team-manager
 */

import './frontend.scss';
import { applyAllContainerContrasts, applyPageContrast, detectBackgroundColor } from '../shared/contrast';

(function() {
    'use strict';

    var settings = window.fsTmFrontend || {};
    settings.applyPageContrast = applyAllContainerContrasts;
    settings.detectBackgroundColor = detectBackgroundColor;
    window.fsTmFrontend = settings;

    var CONSENT_KEY = 'fsTmWidgetConsent';
    var CONSENT_SESSION_KEY = 'fsTmWidgetConsentSession';
    var WIDGET_BASE_URL = 'https://next.fussball.de/widget/';
    var WIDGET_ORIGIN_PATTERN = /^https:\/\/([a-z0-9-]+\.)?fussball\.de$/i;

    // Übersetzte Zeichenketten kommen über wp_localize_script aus PHP.
    var strings = settings.i18n || {};

    function t(key, fallback) {
        return typeof strings[key] === 'string' && strings[key] !== '' ? strings[key] : fallback;
    }

    // In-Memory-Fallback-Stufe: "einmal bestätigt in diesem Tab ⇒ nie
    // wieder fragen", auch wenn beide Speicher blockiert sind
    // (z. B. Private Browsing).
    var sessionConsentGiven = false;

    function hasStoredConsent() {
        if (sessionConsentGiven) return true;
        try {
            if (window.localStorage.getItem(CONSENT_KEY) === '1') return true;
            if (window.sessionStorage.getItem(CONSENT_SESSION_KEY) === '1') return true;
        } catch (err) {
            /* Speicher nicht verfügbar - nur In-Memory-Stufe zählt. */
        }
        return false;
    }

    function storeConsent(remember) {
        sessionConsentGiven = true;
        try {
            if (remember) {
                window.localStorage.setItem(CONSENT_KEY, '1');
            } else {
                window.sessionStorage.setItem(CONSENT_SESSION_KEY, '1');
            }
        } catch (err) {
            /* Speicher nicht verfügbar - In-Memory-Flag gilt für diesen Tab. */
        }
    }

    // Die Zwei-Klick-Lösung gilt als aktiv, sobald die Seite einen Slot
    // enthält - unabhängig davon, ob das lokalisierte Flag ankommt. Slots
    // können auch von Add-ons stammen, die dieses Skript nur als
    // Abhängigkeit mitladen; ohne diese Erkennung bliebe die gespeicherte
    // Zustimmung dort wirkungslos.
    function clickToLoadActive() {
        if (settings.clickToLoad) return true;
        return !!document.querySelector('.fs-tm-widget-slot[data-widget-id]');
    }

    function equalizeHeaderHeights() {
        var grids = document.querySelectorAll('.fs-tm-tables-grid');
        grids.forEach(function(grid) {
            var headers = grid.querySelectorAll('.fs-tm-card-header');
            if (!headers.length) return;

            headers.forEach(function(h) {
                h.style.minHeight = '';
            });

            if (window.innerWidth <= 768) return;

            var maxH = 0;
            headers.forEach(function(h) {
                var hH = h.offsetHeight;
                if (hH > maxH) maxH = hH;
            });

            if (maxH > 0) {
                headers.forEach(function(h) {
                    h.style.minHeight = maxH + 'px';
                });
            }
        });
    }

    // ------------------------------------------------------------------
    // Ladeanzeige
    //
    // Der externe Dienst braucht gelegentlich einige Sekunden. Bis das
    // eingebettete iframe seine erste Höhenmeldung sendet, erscheint an
    // seiner Stelle die Marke als kleine, animierte Ladeanzeige.
    // Sie besteht ausschließlich aus lokalem Markup (kein Bild, kein
    // zusätzlicher Netzwerkaufruf) und respektiert
    // `prefers-reduced-motion` (siehe frontend.scss).
    // ------------------------------------------------------------------

    var LOADER_CLASS = 'fs-tm-loader';

    // Sicherung: Bleibt eine Rückmeldung des Anbieters aus, verschwindet
    // die Anzeige trotzdem, statt dauerhaft stehen zu bleiben.
    var LOADER_TIMEOUT = 15000;

    // Marke aus dem Verwaltungsbereich (identisch zu TeamRepository::getSvgIcon()).
    var BRAND_MARK =
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
        'stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">' +
        '<circle cx="12" cy="12" r="10" />' +
        '<path d="M 12 2 v 1.5 M 12 20.5 v 1.5 M 2 12 h 1.5 M 20.5 12 h 1.5" />' +
        '<path d="M 10 18 V 9 a 3 3 0 0 1 3 -3 h 1" />' +
        '<path d="M 5 12 H 14" />' +
        '<path d="M 14 9 H 16 A 2 2 0 0 1 18 11 H 16 A 1 1 0 0 0 16 13 H 18 A 2 2 0 0 1 16 15 H 14 Z" />' +
        '</svg>';

    function buildLoader() {
        var loader = document.createElement('div');
        loader.className = LOADER_CLASS;
        loader.setAttribute('role', 'status');
        loader.setAttribute('aria-live', 'polite');

        var orb = document.createElement('span');
        orb.className = 'fs-tm-loader-orb';
        orb.setAttribute('aria-hidden', 'true');

        var ring = document.createElement('span');
        ring.className = 'fs-tm-loader-ring';

        var mark = document.createElement('span');
        mark.className = 'fs-tm-loader-mark';
        // Unveränderliche Zeichenkette aus dieser Datei, keine Fremddaten.
        mark.innerHTML = BRAND_MARK;

        orb.appendChild(ring);
        orb.appendChild(mark);

        var text = document.createElement('span');
        text.className = 'fs-tm-loader-text';
        text.textContent = t('loading', 'Inhalte werden geladen …');

        loader.appendChild(orb);
        loader.appendChild(text);

        return loader;
    }

    function currentLoader(container) {
        if (!container || !container.parentNode) return null;
        return container.parentNode.querySelector(':scope > .' + LOADER_CLASS);
    }

    function showLoader(container) {
        if (!container || !container.parentNode) return;
        if (currentLoader(container)) return;

        container.parentNode.insertBefore(buildLoader(), container);
        window.setTimeout(function() {
            hideLoader(container);
        }, LOADER_TIMEOUT);
    }

    function hideLoader(container) {
        var loader = currentLoader(container);
        if (loader && loader.parentNode) {
            loader.parentNode.removeChild(loader);
        }
    }

    // Das iframe meldet seine Höhe erst, wenn der Inhalt steht. Bis dahin
    // bleibt die Ladeanzeige sichtbar; `load` dient als zweite Stufe.
    function bindIframe(container, frame) {
        if (!frame || frame.hasAttribute('data-fstm-bound')) return;
        frame.setAttribute('data-fstm-bound', '1');

        frame.addEventListener('load', function() {
            window.setTimeout(function() {
                hideLoader(container);
            }, 250);
        });
    }

    // Container, deren iframe das offizielle Einbettungs-Script erzeugt,
    // werden beobachtet, damit auch dort eine Ladeanzeige erscheint.
    function watchContainer(container) {
        if (!container || container.hasAttribute('data-fstm-watched')) return;
        container.setAttribute('data-fstm-watched', '1');

        var frame = container.querySelector('iframe');
        if (frame) {
            // Bereits sichtbarer Inhalt braucht keine Ladeanzeige mehr.
            if (frame.offsetHeight > 0) return;
            showLoader(container);
            bindIframe(container, frame);
            return;
        }

        showLoader(container);

        if (typeof window.MutationObserver === 'undefined') return;

        var observer = new window.MutationObserver(function() {
            var added = container.querySelector('iframe');
            if (!added) return;
            observer.disconnect();
            bindIframe(container, added);
        });
        observer.observe(container, { childList: true });
    }

    // ------------------------------------------------------------------
    // Widget-Mounting
    //
    // Das offizielle Einbettungs-Script (widgets.js) ist eine IIFE ohne
    // Guard: Jeder Aufruf hängt pro .fussballde_widget-Container ein
    // NEUES iframe an. Es darf daher pro Seite maximal EINMAL laufen und
    // wird ausschließlich serverseitig über wp_enqueue_script() geladen
    // (BlockRegistrar::renderWidgetSlot()). Ein zusätzliches Nachladen im
    // Skript würde die IIFE ein zweites Mal ausführen und jedes Widget
    // doppelt darstellen.
    // Später erscheinende Container (Lazy-Panes) werden stattdessen
    // idempotent inline initialisiert (gleiche iframe-Eigenschaften).
    // ------------------------------------------------------------------

    function randomId(length) {
        var chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        var out = '';
        for (var i = 0; i < length; i++) {
            out += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return out;
    }

    // Ein iframe pro .fussballde_widget-Container erzeugen (gleiche
    // Eigenschaften wie das offizielle widgets.js).
    // Guard: Container mit vorhandenem iframe werden NICHT erneut
    // initialisiert (idempotent, verhindert doppelte iframes).
    function mountWidgetContainer(container) {
        var widgetId = container.getAttribute('data-id');
        var widgetType = container.getAttribute('data-type');
        if (!widgetId || !widgetType) return;

        // Defensive: Falls mehrere iframes existieren (z. B. weil das
        // offizielle Script durch ein weiteres Plugin ein zweites Mal
        // ausgeführt wurde), alle außer dem ersten entfernen.
        var existing = container.querySelectorAll('iframe');
        for (var i = 1; i < existing.length; i++) {
            existing[i].parentNode.removeChild(existing[i]);
        }
        if (existing.length >= 1) {
            watchContainer(container);
            return;
        }

        container.setAttribute('data-fstm-watched', '1');
        showLoader(container);

        var iframeName = randomId(4) + '_fussballde_widget-' + widgetId;
        var iframe = document.createElement('iframe');
        iframe.setAttribute('src', WIDGET_BASE_URL + widgetType + '/' + widgetId);
        iframe.setAttribute('name', iframeName);
        iframe.style.width = '100%';
        iframe.style.border = 'none';
        iframe.setAttribute('frameborder', '0');
        iframe.setAttribute('scrolling', 'no');
        container.appendChild(iframe);

        bindIframe(container, iframe);
    }

    // Alle bereits im Dokument liegenden Container beobachten. Ihr iframe
    // stammt vom offiziellen Script; die Ladeanzeige gehört trotzdem dazu.
    function watchExistingContainers() {
        document.querySelectorAll('.fussballde_widget').forEach(watchContainer);
    }

    // Alle .fs-tm-widget-slot-Container initialisieren (Zwei-Klick-Lösung).
    // Pro Slot wird der Widget-Container genau EINMAL erzeugt
    // (data-fstm-state-Guard), das iframe wird idempotent gemountet.
    function loadConsentedWidgets() {
        document.querySelectorAll('.fs-tm-widget-slot[data-widget-id]').forEach(function(slot) {
            if (slot.getAttribute('data-fstm-state')) return;

            slot.setAttribute('data-fstm-state', 'ready');
            slot.innerHTML = '';

            var widget = document.createElement('div');
            widget.className = 'fussballde_widget';
            widget.setAttribute('data-id', slot.getAttribute('data-widget-id'));
            widget.setAttribute('data-type', slot.getAttribute('data-widget-type'));
            widget.style.width = '100%';
            slot.appendChild(widget);

            // Guard: Nur mounten, wenn noch kein iframe vorhanden ist.
            mountWidgetContainer(widget);
        });
    }

    // ------------------------------------------------------------------
    // MutationObserver-Sicherung (Bug: Consent-Overlay erscheint trotz
    // gespeicherter Zustimmung immer wieder)
    //
    // Jede Slot-Einfuegung, die erst NACH dem Bootstrap ins DOM kommt
    // (Lazy-Panes, Zeitraum-Umschaltung, dynamisches Re-Rendering),
    // kann einen .fs-tm-widget-slot ohne data-fstm-State hinterlassen.
    // Der Observer fuehrt loadConsentedWidgets() dann erneut aus,
    // solange eine Zustimmung gespeichert ist.
    //
    // Re-Entrancy: loadConsentedWidgets() ist idempotent
    // (data-fstm-State-Guard) und mutiert nur noch UNGEWANDelte Slots.
    // Das consentObserverBusy-Flag verhindert, dass der Callback
    // waehrend einer eigenen Ausfuehrung erneut startet; der
    // "pending"-Check stellt sicher, dass der Callback nach der
    // eigenen Konvertierung (die neue Mutation-Records erzeugt)
    // ein reiner No-Op wird und der Loop damit terminiert.
    //
    // Fallback ohne MutationObserver: loadConsentedWidgets() wird
    // zusaetzlich am Ende von resolveLazyPane() und bei jeder
    // Period-Select-Aenderung aufgerufen.
    // ------------------------------------------------------------------
    var consentObserverInstalled = false;
    var consentObserverBusy = false;

    function installConsentObserver() {
        if (consentObserverInstalled) return;
        consentObserverInstalled = true;

        // Feature-Detection: Ohne MutationObserver greift der Fallback
        // (explizite Aufrufe in resolveLazyPane/bindPeriodSelect).
        if (typeof window.MutationObserver === 'undefined') {
            return;
        }

        var observer = new window.MutationObserver(function() {
            if (!hasStoredConsent() || consentObserverBusy) return;
            consentObserverBusy = true;

            // Nur ausfuehren, wenn noch ein Slot ohne State existiert
            // (sonst waere der eigene Mutation-Loop der Observer).
            var pending = document.querySelector(
                '.fs-tm-widget-slot[data-widget-id]:not([data-fstm-state])'
            );
            if (pending) {
                loadConsentedWidgets();
            }

            consentObserverBusy = false;
        });

        // document.body kann in fruehen Ladephasen null sein - dann
        // auf documentElement zurueckfallen (beide sind zum Zeitpunkt
        // des DOMContentLoaded-Bootstrap vorhanden).
        var target = document.body || document.documentElement;
        if (!target) return;

        observer.observe(target, { childList: true, subtree: true });
    }

    // ------------------------------------------------------------------
    // Period-Umschaltung (Bug 1): Der Scope wird auf die eigene
    // fs-tm-table-card eingeschränkt. Der change-Listener wird pro
    // Select-Element nur EINMAL registriert (data-Attribut-Guard),
    // damit bei dynamischem Nachladen/Re-Mounting keine doppelten
    // Listener entstehen.
    // ------------------------------------------------------------------
    function bindPeriodSelect(select) {
        if (select.hasAttribute('data-fstm-bound')) return;
        select.setAttribute('data-fstm-bound', '1');

        select.addEventListener('change', function() {
            // Scope: Nur die Panes der eigenen Karte umschalten.
            var card = select.closest('.fs-tm-table-card');
            if (!card) return;

            var targetKey = select.value;
            card.querySelectorAll('.fs-tm-period-pane').forEach(function(pane) {
                var isActive = pane.getAttribute('data-period-key') === targetKey;
                pane.classList.toggle('is-active', isActive);

                // Lazy-Load: Inaktive Zeiträume werden erst bei der
                // ersten Auswahl aufgelöst.
                if (isActive && pane.hasAttribute('data-fs-tm-lazy')) {
                    resolveLazyPane(pane);
                }
            });

            // Fallback ohne MutationObserver: Nach jeder Umschaltung
            // die (ggf. neu eingefuegten) Slots idempotent konvertieren,
            // solange eine Zustimmung gespeichert ist.
            if (clickToLoadActive() && hasStoredConsent()) {
                loadConsentedWidgets();
            }

            setTimeout(equalizeHeaderHeights, 50);
        });
    }

    // ------------------------------------------------------------------
    // Attributions-Hinweis
    //
    // Unterhalb des Karten-Body wird ein dezent kleiner Hinweis als
    // LETZTES Kind der .fs-tm-table-card eingefügt (idempotent, damit
    // dynamisch nachgeladene/Re-Mounted Karten keine Duplikate
    // erzeugen).
    //
    // Der Credit-Text samt Link auf die Herstellerseite ist gemäß
    // Richtlinie 10 des WordPress.org-Plugin-Verzeichnisses standardmäßig
    // ausgeschaltet und wird nur ausgegeben, wenn die Website-Betreiberin
    // ihn in den Plugin-Einstellungen ausdrücklich aktiviert hat.
    // Der Link zum Widerruf der Widget-Einwilligung ist dagegen ein
    // funktionales Element der Zwei-Klick-Lösung und kein Credit.
    // ------------------------------------------------------------------
    var ATTRIBUTION_HOME_URL = 'https://fabrielsoftware.de/';

    function addCardAttribution(card) {
        var showCredit = !!settings.showAttribution;

        // Der Widerruf gehört nur unter Karten, die tatsächlich einen externen
        // Inhalt einbetten — sonst stünde er auch unter reinen Add-on-Karten.
        var showReset = clickToLoadActive()
            && !!card.querySelector('.fs-tm-widget-slot, .fussballde_widget');

        if (!showCredit && !showReset) return;

        // Guard: Attributions-Element bereits als Kind der Karte vorhanden?
        if (card.querySelector(':scope > .fs-tm-card-attribution')) return;

        var el = document.createElement('div');
        el.className = 'fs-tm-card-attribution fs-tm-no-print';
        el.setAttribute('data-fstm-attribution', '1');

        if (showCredit) {
            var link = document.createElement('a');
            link.href = ATTRIBUTION_HOME_URL;
            link.textContent = 'Fabriel Software';
            link.target = '_blank';
            link.rel = 'noopener noreferrer';

            el.appendChild(document.createTextNode('Eingebunden durch '));
            el.appendChild(link);
            el.appendChild(document.createTextNode(' Teammanager'));
        }

        // Bei aktiver Zwei-Klick-Lösung: Link zum Widerruf der Einwilligung
        if (showReset) {
            if (showCredit) {
                el.appendChild(document.createTextNode(' · '));
            }
            var resetLink = document.createElement('a');
            resetLink.href = '#';
            resetLink.className = 'fs-tm-consent-reset-link';
            resetLink.textContent = 'Widget-Einwilligung zurücksetzen';
            el.appendChild(resetLink);
        }

        card.appendChild(el);
    }

    function revokeConsent() {
        sessionConsentGiven = false;
        try {
            window.localStorage.removeItem(CONSENT_KEY);
            window.sessionStorage.removeItem(CONSENT_SESSION_KEY);
        } catch (err) {
            /* Speicher nicht verfügbar – In-Memory-Flag wurde bereits zurückgesetzt. */
        }

        // Alle bereits geladenen Widget-Slots in den Consent-Zustand zurückversetzen.
        document.querySelectorAll('.fs-tm-widget-slot[data-fstm-state]').forEach(function(slot) {
            var widgetId   = slot.getAttribute('data-widget-id');
            var widgetType = slot.getAttribute('data-widget-type');
            slot.removeAttribute('data-fstm-state');
            slot.innerHTML = '';

            if (!widgetId || !widgetType) {
                // Fehlender ID/Typ: State entfernen und leer lassen.
                return;
            }

            slot.appendChild(buildConsentOverlay());
        });
    }

    // Reihenfolge der Bedienelemente: erst die Auswahl „merken“, dann die
    // Schaltfläche, die die Auswahl ausführt.
    function buildConsentOverlay() {
        var box = document.createElement('div');
        box.className = 'fs-tm-consent';

        var icon = document.createElement('span');
        icon.className = 'fs-tm-consent-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = '\uD83D\uDD12';

        var text = document.createElement('p');
        text.className = 'fs-tm-consent-text';
        text.textContent = t(
            'consentText',
            'An dieser Stelle wird ein Inhalt des externen Anbieters fussball.de eingebunden. Beim Laden werden Daten \u2013 unter anderem Ihre IP-Adresse \u2013 an den Anbieter \u00fcbertragen.'
        );

        var label = document.createElement('label');
        label.className = 'fs-tm-consent-remember';
        var checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        label.appendChild(checkbox);
        label.appendChild(document.createTextNode(' ' + t('consentRemember', 'Auswahl f\u00fcr diesen Browser merken')));

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'fs-tm-consent-btn';
        button.textContent = t('consentButton', 'Inhalt laden');

        var link = document.createElement('a');
        link.className = 'fs-tm-consent-link';
        link.href = 'https://www.fussball.de/';
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = t('consentProviderLink', 'Zur Website des Anbieters');

        box.appendChild(icon);
        box.appendChild(text);
        box.appendChild(label);
        box.appendChild(button);
        box.appendChild(link);

        return box;
    }

    // Rückmeldung unmittelbar nach dem Klick: Die Schaltfläche zeigt bis
    // zum Austausch des Inhalts einen kleinen Spinner.
    function markConsentButtonBusy(button) {
        if (!button || button.classList.contains('is-loading')) return;
        button.classList.add('is-loading');
        button.disabled = true;

        var spinner = document.createElement('span');
        spinner.className = 'fs-tm-consent-btn-spinner';
        spinner.setAttribute('aria-hidden', 'true');
        button.insertBefore(spinner, button.firstChild);
    }

    // Kontrast-Überwachung für Theme-Wechsel (z. B. Dark-Mode-Plugins)
    function installThemeObserver() {
        if (typeof window.MutationObserver === 'undefined') return;
        var themeObserver = new window.MutationObserver(function(mutations) {
            for (var i = 0; i < mutations.length; i++) {
                if (
                    mutations[i].attributeName === 'class' ||
                    mutations[i].attributeName === 'data-theme' ||
                    mutations[i].attributeName === 'style'
                ) {
                    applyAllContainerContrasts();
                    break;
                }
            }
        });
        if (document.documentElement) {
            themeObserver.observe(document.documentElement, { attributes: true });
        }
        if (document.body) {
            themeObserver.observe(document.body, { attributes: true });
        }
    }

    // Sofortige Ausführung bei Skriptstart, falls body bereits existiert (vermeidet FOUC)
    if (typeof document !== 'undefined' && document.body) {
        applyAllContainerContrasts();
    }

    document.addEventListener('DOMContentLoaded', function() {
        applyAllContainerContrasts();
        installThemeObserver();
        equalizeHeaderHeights();

        // Attributions-Hinweis unterhalb jeder Karte einfügen — auch unter den
        // Karten der Add-ons, wie es die Einstellung ankündigt.
        document.querySelectorAll('.fs-tm-table-card, .fs-tm-card').forEach(addCardAttribution);

        // Alle Period-Selects der Seite binden (idempotent pro Select).
        document.querySelectorAll('.fs-tm-period-select').forEach(bindPeriodSelect);

        if (clickToLoadActive()) {
            if (hasStoredConsent()) {
                loadConsentedWidgets();
            }

            // Sicherung: Slots, die erst spaeter ins DOM kommen
            // (Lazy-Panes, dynamisches Re-Rendering), werden sofort
            // nachgefuehrt, solange die Zustimmung gespeichert ist.
            installConsentObserver();
        } else {
            // Ohne Zwei-Klick-Lösung erzeugt das serverseitig eingebundene
            // Script des Anbieters die iframes der bereits sichtbaren
            // Container. Hier wird deshalb NICHT nachgeladen (das würde die
            // IIFE ein zweites Mal ausführen und jedes Widget doppelt
            // darstellen); es wird nur die Ladeanzeige ergänzt.
            if (providerScriptAvailable()) {
                watchExistingContainers();
            }
        }

        document.addEventListener('click', function(e) {
            // Widerruf der Einwilligung
            var resetLink = e.target.closest('.fs-tm-consent-reset-link');
            if (resetLink) {
                e.preventDefault();
                revokeConsent();
                return;
            }

            var btn = e.target.closest('.fs-tm-consent-btn');
            if (!btn) return;

            e.preventDefault();
            var consentBox = btn.closest('.fs-tm-consent');
            var remember = consentBox ? consentBox.querySelector('.fs-tm-consent-remember input') : null;
            // Zustimmung wird IMMER protokolliert; die Checkbox wählt nur
            // die Stufe (dauerhaft vs. Sitzung).
            storeConsent(remember ? remember.checked : false);

            markConsentButtonBusy(btn);
            loadConsentedWidgets();
        });
    });

    window.addEventListener('load', function() {
        applyAllContainerContrasts();
        equalizeHeaderHeights();
        setTimeout(equalizeHeaderHeights, 500);
        setTimeout(equalizeHeaderHeights, 1500);
    });

    window.addEventListener('resize', equalizeHeaderHeights);

    /**
     * Lazy-Load-Auflösung eines Zeitraum-Panes: Der Inhalt liegt in einem
     * <template> und wird erst bei der ersten Auswahl in das Pane
     * übernommen. Danach werden die jetzt vorhandenen Container
     * idempotent initialisiert (maximal ein iframe pro Container).
     */
    function resolveLazyPane(pane) {
        var template = pane.querySelector(':scope > template');
        if (!template) return;

        pane.appendChild(template.content);
        pane.removeAttribute('data-fs-tm-lazy');

        if (clickToLoadActive()) {
            // Zwei-Klick-Lösung: Die Slots werden erst nach erteilter
            // Zustimmung in Widget-Container umgewandelt.
            if (hasStoredConsent()) {
                loadConsentedWidgets();
            }
            return;
        }

        // Ohne Zwei-Klick-Lösung liegen fertige Container im Pane.
        // Das offizielle Script (widgets.js) wurde pro Seite bereits EINMAL
        // ausgeführt und initialisiert nur die Container, die zu diesem
        // Zeitpunkt bereits im DOM waren. Die jetzt aus dem <template>
        // freigegebenen Container müssen daher selbst idempotent gemountet
        // werden (gleiche iframe-Eigenschaften wie widgets.js), sonst bleibt
        // das freigegebene Pane leer (kein Tabellen-/Spielplan-iframe).
        //
        // Hat ein Consent-Management-Plugin das Script des Anbieters
        // stillgelegt, unterbleibt auch dieses Nachladen: Ohne Einwilligung
        // darf kein Umweg zum Anbieter entstehen.
        if (!providerScriptAvailable()) return;

        pane.querySelectorAll('.fussballde_widget').forEach(function(container) {
            mountWidgetContainer(container);
        });
    }

    /**
     * Prüft, ob das Einbettungs-Script des Anbieters wirken darf.
     *
     * Consent-Management-Plugins (Complianz, Borlabs, Real Cookie Banner …)
     * stellen blockierte Skripte auf einen nicht ausführbaren Typ um
     * (`text/plain`, `text/template` …) und merken sich die Adresse in einem
     * eigenen Attribut. Genau das wird hier erkannt.
     *
     * Serverseitig bleibt der Filter `fs_tm_load_remote_widgets` die
     * verlässlichste Kopplung – er verhindert die Ausgabe vollständig.
     */
    var EXECUTABLE_SCRIPT_TYPES = ['', 'text/javascript', 'application/javascript', 'module'];

    function providerScriptAvailable() {
        // Wurde bereits ein Inhalt eingebettet, ist nichts blockiert.
        if (document.querySelector('.fussballde_widget iframe')) return true;

        var scripts = document.querySelectorAll('script');
        for (var i = 0; i < scripts.length; i++) {
            var script = scripts[i];
            var src = script.getAttribute('src') || '';
            var deferredSrc = script.getAttribute('data-cmplz-src') ||
                script.getAttribute('data-src') ||
                script.getAttribute('data-borlabs-cookie-src') || '';

            if ((src + deferredSrc).indexOf('fussball.de') === -1) continue;

            var type = (script.getAttribute('type') || '').toLowerCase();
            if (EXECUTABLE_SCRIPT_TYPES.indexOf(type) === -1) return false;
            if (!src && deferredSrc) return false;
        }

        return true;
    }

    /**
     * Höhenmeldungen des eingebetteten Inhalts.
     *
     * Ein einziger Zuhörer für die ganze Seite: Er prüft die Herkunft der
     * Nachricht, überträgt die gemeldete Höhe auf das benannte iframe und
     * beendet dessen Ladeanzeige. Frühere Fassungen registrierten einen
     * eigenen Zuhörer je iframe – ohne Herkunftsprüfung.
     */
    function findIframeByName(name) {
        if (!name) return null;
        var candidates = document.getElementsByName(name);
        for (var i = 0; i < candidates.length; i++) {
            if (candidates[i].tagName === 'IFRAME') return candidates[i];
        }
        return null;
    }

    window.addEventListener('message', function(event) {
        if (!event.origin || !WIDGET_ORIGIN_PATTERN.test(event.origin)) {
            return;
        }
        if (!event.data || event.data.type !== 'fussballde_widget:resize') {
            return;
        }

        var frame = findIframeByName(event.data.iframeName);
        if (frame) {
            var height = parseInt(event.data.height, 10);
            if (height > 0) {
                frame.style.height = height + 'px';
            }
            var container = frame.closest('.fussballde_widget');
            if (container) hideLoader(container);
        }

        setTimeout(equalizeHeaderHeights, 50);
    });
})();
