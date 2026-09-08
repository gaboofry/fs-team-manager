/**
 * Fabriel Software Team-Manager - admin script
 *
 * Human-readable source of build/admin.js.
 * Compile with: npm ci && npm run build
 *
 * @package   fabriel-team-manager
 * @author    Fabriel Software (https://fabrielsoftware.de/)
 * @copyright Fabriel Software
 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0-or-later
 * @link      https://github.com/gaboofry/fs-team-manager
 */

import './admin.scss';
import { __, sprintf } from '@wordpress/i18n';

(function($) {
    'use strict';

    var isSubmitting = false;
    var lastSubmitter = null;

    /* ------------------------------------------------------------------
     * Schwebende Meldungen
     * ---------------------------------------------------------------- */

    function toastStack() {
        var el = document.getElementById('fs-tm-toast-stack');
        if (!el) {
            el = document.createElement('div');
            el.id = 'fs-tm-toast-stack';
            el.className = 'fs-tm-toast-stack';
            el.setAttribute('aria-live', 'polite');
            document.body.appendChild(el);
        }
        return el;
    }

    function dismissAfter($el, delay) {
        setTimeout(function() {
            $el.addClass('is-leaving');
            setTimeout(function() { $el.remove(); }, 300);
        }, delay || 6000);
    }

    function showToast(message, type) {
        type = type || 'warning';
        var titlePrefix = type === 'error'
            ? __('Fehler:', 'fabriel-team-manager')
            : (type === 'success' ? __('Erfolg:', 'fabriel-team-manager') : __('Hinweis:', 'fabriel-team-manager'));

        var $toast = $('<div class="notice fs-tm-notice fs-tm-toast"></div>').addClass('notice-' + type);
        $toast.append($('<p></p>').append($('<strong></strong>').text(titlePrefix)).append(document.createTextNode(' ' + message)));
        $(toastStack()).append($toast);
        dismissAfter($toast, type === 'error' ? 9000 : 5000);
    }

    /** Holt serverseitige Meldungen aus dem Fluss in den schwebenden Stapel. */
    function collectNotices(root) {
        $(root || document).find('.fs-tm-notice').each(function() {
            var $notice = $(this);
            if ($notice.closest('#fs-tm-toast-stack').length) return;
            $notice.addClass('fs-tm-toast').appendTo(toastStack());
            dismissAfter($notice, $notice.hasClass('notice-error') ? 9000 : 6000);
        });
    }

    $(document).on('click', '.fs-tm-toast .notice-dismiss, .fs-tm-toast-close', function() {
        $(this).closest('.fs-tm-toast').remove();
    });

    /* ------------------------------------------------------------------
     * Hilfetexte
     *
     * Die Karten tragen `overflow: hidden` für ihre runden Ecken; ein Tooltip als
     * Pseudo-Element würde daran abgeschnitten. Deshalb liegt er am Body.
     * ---------------------------------------------------------------- */

    function tooltipEl() {
        var el = document.getElementById('fs-tm-tooltip');
        if (!el) {
            el = document.createElement('div');
            el.id = 'fs-tm-tooltip';
            el.className = 'fs-tm-tooltip';
            el.setAttribute('role', 'tooltip');
            document.body.appendChild(el);
        }
        return el;
    }

    function showTooltip(source) {
        var text = source.getAttribute('data-tip');
        if (!text) return;

        var el = tooltipEl();
        el.textContent = text;
        el.classList.add('is-visible');

        var anchor = source.getBoundingClientRect();
        var box = el.getBoundingClientRect();
        var margin = 8;

        var left = anchor.left + (anchor.width / 2) - (box.width / 2);
        left = Math.max(margin, Math.min(left, window.innerWidth - box.width - margin));

        var top = anchor.top - box.height - margin;
        el.classList.toggle('is-below', top < margin);
        if (top < margin) top = anchor.bottom + margin;

        el.style.left = left + 'px';
        el.style.top = top + 'px';
    }

    function hideTooltip() {
        var el = document.getElementById('fs-tm-tooltip');
        if (el) el.classList.remove('is-visible');
    }

    $(document).on('mouseenter focusin', '.fs-tm-help-tip', function() { showTooltip(this); });
    $(document).on('mouseleave focusout', '.fs-tm-help-tip', hideTooltip);
    window.addEventListener('scroll', hideTooltip, true);

    /* ------------------------------------------------------------------
     * Navigation ohne vollständigen Seitenaufbau
     *
     * Geladen wird die Zielseite ganz normal; ausgetauscht wird nur `#fs-tm-view`
     * samt Leiste. Dadurch bleiben alle serverseitigen Abläufe unverändert.
     * ---------------------------------------------------------------- */

    var nav = {
        busy: false,

        isInternal: function(url) {
            try {
                var target = new URL(url, window.location.href);
                if (target.origin !== window.location.origin) return false;
                if (target.pathname !== window.location.pathname) return false;
                return (target.searchParams.get('page') || '').indexOf('fs-tm-') === 0;
            } catch (err) {
                return false;
            }
        },

        swap: function(html, url) {
            var doc = new DOMParser().parseFromString(html, 'text/html');
            var view = doc.getElementById('fs-tm-view');
            if (!view) { window.location.href = url; return; }

            var $current = $('#fs-tm-view');
            $current.replaceWith(view);

            var bar = doc.querySelector('.fs-tm-bar');
            if (bar) $('.fs-tm-bar').first().replaceWith(bar);

            // Meldungen der Zielseite liegen teils außerhalb der View.
            $(doc).find('.fs-tm-notice').each(function() {
                $(toastStack()).append(this);
                dismissAfter($(this), $(this).hasClass('notice-error') ? 9000 : 6000);
            });

            isSubmitting = false;
            initView();
            hideTooltip();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            $(document).trigger('fs-tm-view-loaded');
        },

        go: function(url, push) {
            if (nav.busy) return;
            nav.busy = true;
            $('#fs-tm-view').addClass('is-loading');

            fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fs-tm-view' } })
                .then(function(res) { return res.text().then(function(html) { return { html: html, url: res.url || url }; }); })
                .then(function(result) {
                    if (push) window.history.pushState({ fsTm: true }, '', result.url);
                    nav.swap(result.html, result.url);
                })
                .catch(function() { window.location.href = url; })
                .finally(function() {
                    nav.busy = false;
                    $('#fs-tm-view').removeClass('is-loading');
                });
        },

        submit: function(form) {
            if (nav.busy) return;
            nav.busy = true;
            $('#fs-tm-view').addClass('is-loading');

            var data = new FormData(form);
            if (lastSubmitter && lastSubmitter.form === form && lastSubmitter.name) {
                data.append(lastSubmitter.name, lastSubmitter.value || '');
            }

            fetch(form.action || window.location.href, {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'fs-tm-view' }
            })
                .then(function(res) { return res.text().then(function(html) { return { html: html, url: res.url }; }); })
                .then(function(result) {
                    // Das Post/Redirect/Get-Muster liefert bereits die Zieladresse.
                    window.history.pushState({ fsTm: true }, '', result.url);
                    nav.swap(result.html, result.url);
                })
                .catch(function() { form.submit(); })
                .finally(function() {
                    nav.busy = false;
                    $('#fs-tm-view').removeClass('is-loading');
                });
        }
    };

    function isValidUuid(uuid) {
        if (!uuid || uuid.trim() === '') return true;
        return /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(uuid.trim());
    }


    // Speichert den Anfangswert für bidirektionales Dirty-Checking
    function snapshotFormInitialValues($form) {
        $form.find('input, select').each(function() {
            var $el = $(this);
            if ($el.is(':checkbox')) {
                $el.data('initial-value', $el.is(':checked') ? '1' : '0');
            } else {
                $el.data('initial-value', $el.val());
            }
        });
    }

    // Prüft, ob ein Formular tatsächlich vom Originalzustand abweicht
    function evaluateFormDirtyState($form) {
        var hasChanges = false;
        $form.find('input, select').each(function() {
            var $el = $(this);
            var initVal = $el.data('initial-value');
            if (typeof initVal === 'undefined') return;

            var curVal = $el.is(':checkbox') ? ($el.is(':checked') ? '1' : '0') : $el.val();
            if (curVal !== initVal) {
                $el.addClass('fs-tm-input-dirty');
                hasChanges = true;
            } else {
                $el.removeClass('fs-tm-input-dirty');
            }
        });

        var $teamCard = $form.closest('.fs-tm-team-card');
        if (hasChanges) {
            $teamCard.addClass('has-unsaved-changes');
        } else {
            $teamCard.removeClass('has-unsaved-changes');
        }
    }

    // Setzt ein Formular auf seine initialen Werte zurück
    function resetFormToInitialValues($form) {
        $form.find('input, select').each(function() {
            var $el = $(this);
            var initVal = $el.data('initial-value');
            if (typeof initVal !== 'undefined') {
                if ($el.is(':checkbox')) {
                    $el.prop('checked', initVal === '1').trigger('change');
                } else {
                    $el.val(initVal).trigger('input');
                }
            }
            $el.removeClass('fs-tm-input-dirty fs-tm-input-error');
        });
        var $teamCard = $form.closest('.fs-tm-team-card');
        $teamCard.removeClass('has-unsaved-changes');
    }

    function validateSingleIdInput($input) {
        var val = $input.val();
        if (val && !isValidUuid(val)) {
            $input.addClass('fs-tm-input-error');
            return false;
        } else {
            $input.removeClass('fs-tm-input-error');
            return true;
        }
    }

    function validateFormIds($form) {
        var hasError = false;
        var firstErrorMsg = '';
        $form.find('.code-font').each(function() {
            var $input = $(this);
            var val = $input.val();
            if (val && !isValidUuid(val)) {
                $input.addClass('fs-tm-input-error');
                hasError = true;
                if (!firstErrorMsg) {
                    var label = $input.closest('.fs-tm-period-card').find('.period-label-input').val() || __('Zeitraum', 'fabriel-team-manager');
                    var isMatches = $input.attr('name').indexOf('id_matches') > -1;
                    firstErrorMsg = isMatches
                        /* translators: 1: period label, 2: entered value */
                        ? sprintf(__('Ungültige Spielplan-ID im Zeitraum „%1$s“ (%2$s).', 'fabriel-team-manager'), label, val)
                        /* translators: 1: period label, 2: entered value */
                        : sprintf(__('Ungültige Tabellen-ID im Zeitraum „%1$s“ (%2$s).', 'fabriel-team-manager'), label, val);
                }
            } else {
                $input.removeClass('fs-tm-input-error');
            }
        });
        return { isValid: !hasError, errorMsg: firstErrorMsg };
    }

    function validatePeriodContinuity($form, triggerToast) {
        var periods = [];
        var $cards = $form.find('.fs-tm-period-card');
        $cards.removeClass('has-conflict');
        $cards.find('.fs-tm-conflict-badge').remove();

        $cards.each(function() {
            var $card = $(this);
            var from = $card.find('.period-from-input').val();
            var to = $card.find('.period-to-input').val();
            if (from) {
                periods.push({
                    $el: $card,
                    from: from,
                    to: to,
                    label: $card.find('.period-label-input').val() || __('Unbenannter Zeitraum', 'fabriel-team-manager')
                });
            }
        });

        if (periods.length <= 1) return true;
        periods.sort(function(a, b) { return a.from.localeCompare(b.from); });

        var hasConflict = false;
        var firstErrorMsg = '';

        for (var i = 0; i < periods.length - 1; i++) {
            var curr = periods[i], next = periods[i + 1];
            if (!curr.to) {
                curr.$el.addClass('has-conflict');
                next.$el.addClass('has-conflict');
                hasConflict = true;
                /* translators: 1: earlier period label, 2: following period label */
                if (!firstErrorMsg) firstErrorMsg = sprintf(__('„%1$s“ hat kein Enddatum, obwohl danach „%2$s“ folgt.', 'fabriel-team-manager'), curr.label, next.label);
            } else if (curr.to >= next.from) {
                curr.$el.addClass('has-conflict');
                next.$el.addClass('has-conflict');
                hasConflict = true;
                /* translators: 1: earlier period label, 2: its end date, 3: following period label, 4: its start date */
                if (!firstErrorMsg) firstErrorMsg = sprintf(__('Überlappung: „%1$s“ (bis %2$s) überschneidet sich mit „%3$s“ (ab %4$s).', 'fabriel-team-manager'), curr.label, curr.to, next.label, next.from);
            }
        }

        if (hasConflict) {
            $cards.filter('.has-conflict').each(function() { 
                if ($(this).find('.fs-tm-conflict-badge').length === 0) {
                    $(this).find('.fs-tm-period-card-badges').prepend($('<span class="fs-tm-conflict-badge"></span>').text('⚠️ ' + __('Zeitüberlappung', 'fabriel-team-manager')));
                }
            });
            if (triggerToast) showToast(firstErrorMsg, 'error');
            return false;
        }
        return true;
    }

    // Bindet das Widget im Vorschau-Modal exakt nach Vorgabe von fussball.de ein.
    function renderWidgetPreview(uuid, type) {
        var scriptUrl = (window.fsTmAdminData && fsTmAdminData.widgetScriptUrl) || '';
        var $stage = $('#fs-tm-preview-stage');

        $stage.empty().append(
            $('<div class="fussballde_widget"></div>')
                .attr({ 'data-id': uuid, 'data-type': type })
                .css('width', '100%')
        );

        if (!scriptUrl) return;

        // Das Script initialisiert beim Ausführen alle vorhandenen Container; da die
        // Bühne zuvor geleert wurde, ist das genau der neue Container.
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = scriptUrl;
        $stage[0].appendChild(script);
    }

    /**
     * Alles, was nach einem Austausch des Inhaltsbereichs neu gebunden werden muss.
     * Alle übrigen Handler hängen am Dokument und überstehen den Wechsel.
     */
    /* ------------------------------------------------------------------
     * Aktiver Bereich im WordPress-Menü
     *
     * Der Wechsel zwischen den Bereichen tauscht nur `#fs-tm-view` und die
     * eigene Leiste aus. Das Untermenü in der Seitenleiste stammt dagegen
     * vom ersten Seitenaufbau und würde weiterhin den zuerst geöffneten
     * Eintrag hervorheben. Der aktive Bereich wird deshalb nach jedem
     * Wechsel auch dort gesetzt – ausschließlich im eigenen Untermenü.
     * ---------------------------------------------------------------- */

    function pageSlugOf(url) {
        if (!url) return '';
        try {
            return new URL(url, window.location.href).searchParams.get('page') || '';
        } catch (err) {
            return '';
        }
    }

    function syncAdminMenu() {
        var current = $('#fs-tm-view').data('fs-tm-page') || pageSlugOf(window.location.href);
        if (!current) return;

        var $ownLinks = $('#adminmenu .wp-submenu a').filter(function() {
            return pageSlugOf($(this).attr('href')).indexOf('fs-tm-') === 0;
        });
        if (!$ownLinks.length) return;

        var $submenu = $ownLinks.first().closest('.wp-submenu');
        $submenu.find('li').removeClass('current');
        $submenu.find('a').removeClass('current');

        $ownLinks.filter(function() {
            return pageSlugOf($(this).attr('href')) === current;
        }).addClass('current').closest('li').addClass('current');

        // Die eigene Leiste kennzeichnet denselben Bereich.
        $('.fs-tm-tabs .fs-tm-tab').each(function() {
            var isActive = pageSlugOf($(this).attr('href')) === current;
            $(this).toggleClass('is-active', isActive);
            if (isActive) {
                $(this).attr('aria-current', 'page');
            } else {
                $(this).removeAttr('aria-current');
            }
        });
    }

    function initView() {
        $('.fs-tm-team-form').each(function() {
            snapshotFormInitialValues($(this));
        });

        collectNotices();
        syncAdminMenu();

        if ($.fn.sortable) {
            $('#fs-tm-team-sortable').sortable({
                handle: '.fs-tm-drag-handle',
                axis: 'y',
                placeholder: 'fs-tm-team-placeholder',
                forcePlaceholderSize: true,
                update: function() {
                    var order = [];
                    $('#fs-tm-team-sortable .fs-tm-team-card').each(function() {
                        order.push($(this).data('slug'));
                    });

                    $.post(fsTmAdminData.ajaxUrl, {
                        action: 'fs_tm_save_order',
                        nonce: fsTmAdminData.nonce,
                        order: order
                    }, function(res) {
                        if (res.success) {
                            showToast(__('Reihenfolge der Mannschaften aktualisiert!', 'fabriel-team-manager'), 'success');
                        }
                    });
                }
            });
        }
    }

    $(document).ready(function() {
        initView();

        // 2. Live-Suche
        $(document).on('input', '#fs-tm-search', function() {
            var val = $.trim($(this).val()).toLowerCase();
            $('#fs-tm-team-sortable .fs-tm-team-card').each(function() {
                var rowText = $(this).text().toLowerCase();
                if (!val || rowText.indexOf(val) > -1) {
                    $(this).removeClass('fs-tm-hidden');
                } else {
                    $(this).addClass('fs-tm-hidden');
                }
            });
        });


        // 3. Exklusives Team-Akkordeon mit Lazy Loading & Verwerfen-Abfrage
        $(document).on('click', '.fs-tm-team-header', function(e) {
            if ($(e.target).closest('a, button, input, select').length) return;

            var $card = $(this).closest('.fs-tm-team-card');
            var $body = $card.find('.fs-tm-team-body');
            var isOpen = $card.hasClass('is-expanded');
            var slug = $card.data('slug');

            if (isOpen) {
                $card.removeClass('is-expanded').addClass('is-collapsed');
                $body.slideUp(180);
            } else {
                var $dirtyCard = $('.fs-tm-team-card.has-unsaved-changes').not($card);
                if ($dirtyCard.length) {
                    var dirtyName = $dirtyCard.find('.fs-tm-team-title-text').text() || __('einer Mannschaft', 'fabriel-team-manager');
                    var confirmMsg = sprintf(
                        /* translators: %s: team name */
                        __('Achtung: Sie haben ungespeicherte Änderungen in „%s“.\n\nWenn Sie zu einer anderen Mannschaft wechseln, werden diese Änderungen verworfen.\n\nMöchten Sie fortfahren und die Änderungen verwerfen?', 'fabriel-team-manager'),
                        dirtyName
                    );
                    if (!confirm(confirmMsg)) {
                        return false;
                    }
                    resetFormToInitialValues($dirtyCard.find('.fs-tm-team-form'));
                }

                $('.fs-tm-team-card.is-expanded').not($card).each(function() {
                    $(this).removeClass('is-expanded').addClass('is-collapsed');
                    $(this).find('.fs-tm-team-body').slideUp(180);
                });

                var isLoaded = $body.attr('data-loaded') === '1';
                if (!isLoaded) {
                    $card.removeClass('is-collapsed').addClass('is-expanded');
                    $body.html('<div class="fs-tm-loading-spinner"><span class="spinner is-active"></span> ' + __('Teamdaten werden geladen …', 'fabriel-team-manager') + '</div>').slideDown(180);

                    $.post(fsTmAdminData.ajaxUrl, {
                        action: 'fs_tm_get_team_form',
                        nonce: fsTmAdminData.nonce,
                        team_slug: slug
                    }, function(res) {
                        if (res.success && res.data.html) {
                            $body.html(res.data.html).attr('data-loaded', '1');
                            var $form = $body.find('.fs-tm-team-form');
                            snapshotFormInitialValues($form);
                            
                            // Issue 4: Filter auf neu geladene Sektionen anwenden
                            $(document).trigger('fs-tm-team-form-loaded', [$body]);
                        } else {
                            $body.html($('<p class="notice notice-error"></p>').text(__('Fehler beim Nachladen der Daten.', 'fabriel-team-manager')));
                        }
                    }).fail(function() {
                        $body.html($('<p class="notice notice-error"></p>').text(__('Netzwerkfehler beim Laden.', 'fabriel-team-manager')));
                    });
                } else {
                    $card.removeClass('is-collapsed').addClass('is-expanded');
                    $body.slideDown(180);
                }

                // Issue 3: Nach dem Öffnen nur "Stammdaten & Zuordnung" aufklappen, Rest geschlossen halten
                $body.find('.fs-tm-section-box').each(function() {
                    var $section = $(this);
                    if ($section.data('fs-tm-section') === 'core-basics') {
                        $section.removeClass('is-collapsed').addClass('is-expanded');
                        $section.find('.fs-tm-section-body').show();
                    } else {
                        $section.removeClass('is-expanded').addClass('is-collapsed');
                        $section.find('.fs-tm-section-body').hide();
                    }
                });
            }
        });

        // 4. Verwerfen Button
        $(document).on('click', '.fs-tm-btn-reset-team', function(e) {
            e.preventDefault();
            var $form = $(this).closest('.fs-tm-team-form');
            if (!confirm(__('Möchten Sie alle ungespeicherten Änderungen in diesem Team verwerfen?', 'fabriel-team-manager'))) {
                return false;
            }
            resetFormToInitialValues($form);
            showToast(__('Änderungen wurden verworfen.', 'fabriel-team-manager'), 'success');
        });

        // 5. Zuklappen Button
        $(document).on('click', '.fs-tm-btn-close-team', function(e) {
            e.preventDefault();
            var $card = $(this).closest('.fs-tm-team-card');
            if ($card.hasClass('has-unsaved-changes')) {
                if (!confirm(__('Sie haben ungespeicherte Änderungen in diesem Team. Möchten Sie es wirklich zuklappen und die Änderungen verwerfen?', 'fabriel-team-manager'))) {
                    return false;
                }
                resetFormToInitialValues($card.find('.fs-tm-team-form'));
            }
            $card.removeClass('is-expanded').addClass('is-collapsed');
            $card.find('.fs-tm-team-body').slideUp(180);
        });

        // 6. Collapsible Boxen & Unter-Akkordeons
        $(document).on('click', '.fs-tm-collapsible-header', function(e) {
            if ($(e.target).closest('a, button, input, select').length) return;
            var $box = $(this).closest('.fs-tm-collapsible-box');
            var $body = $box.find('.fs-tm-collapsible-body');

            if ($box.hasClass('is-collapsed')) {
                $box.removeClass('is-collapsed').addClass('is-expanded');
                $body.slideDown(180);
            } else {
                $box.removeClass('is-expanded').addClass('is-collapsed');
                $body.slideUp(180);
            }
        });

        // Unter-Akkordeons ("Stammdaten" & "Zeiträume")
        $(document).on('click', '.fs-tm-section-header', function(e) {
            if ($(e.target).closest('a, button, input, select, .fs-tm-help-tip').length) return;
            var $box = $(this).closest('.fs-tm-section-box');
            var $body = $box.find('.fs-tm-section-body');

            if ($box.hasClass('is-collapsed')) {
                $box.removeClass('is-collapsed').addClass('is-expanded');
                $body.slideDown(180);
            } else {
                $box.removeClass('is-expanded').addClass('is-collapsed');
                $body.slideUp(180);
            }
        });

        // Sub-Akkordeons für einzelne Zeiträume
        $(document).on('click', '.fs-tm-period-card-header', function(e) {
            if ($(e.target).closest('button, a, input, select, .fs-tm-help-tip').length) return;
            var $pCard = $(this).closest('.fs-tm-period-card');
            var $pBody = $pCard.find('.fs-tm-period-card-body');

            if ($pCard.hasClass('is-collapsed')) {
                $pCard.removeClass('is-collapsed').addClass('is-expanded');
                $pBody.slideDown(180);
            } else {
                $pCard.removeClass('is-expanded').addClass('is-collapsed');
                $pBody.slideUp(180);
            }
        });

        // 7. Live Synchronisation & Dirty-State Auswertung
        $(document).on('input', '.fs-tm-team-name-input', function() {
            var val = $.trim($(this).val());
            var $card = $(this).closest('.fs-tm-team-card');
            $card.find('.fs-tm-team-title-text').text(val || __('Unbenanntes Team', 'fabriel-team-manager'));
        });

        $(document).on('input', '.period-label-input', function() {
            var val = $.trim($(this).val());
            var $pCard = $(this).closest('.fs-tm-period-card');
            $pCard.find('.fs-tm-period-title-text').text(val || __('Unbenannter Zeitraum', 'fabriel-team-manager'));
        });

        $(document).on('change', '.fs-tm-club-matches-toggle', function() {
            var isChecked = $(this).is(':checked');
            var $pCard = $(this).closest('.fs-tm-period-card');
            var $label = $pCard.find('.fs-tm-matches-label-text');
            if (isChecked) {
                $label.text(__('Spielplan-ID des Vereins:', 'fabriel-team-manager'));
            } else {
                $label.text(__('Spielplan-ID der Mannschaft:', 'fabriel-team-manager'));
            }
        });

        $(document).on('input change', '.fs-tm-team-form input, .fs-tm-team-form select', function() {
            var $form = $(this).closest('.fs-tm-team-form');
            evaluateFormDirtyState($form);
        });

        $(document).on('input blur', '.fs-tm-periods-container .code-font', function() {
            validateSingleIdInput($(this));
        });

        // 8. Formular Absenden
        $(document).on('submit', '.fs-tm-team-form', function(e) {
            var $form = $(this);
            var idCheck = validateFormIds($form);
            if (!idCheck.isValid) {
                e.preventDefault();
                var $firstError = $form.find('.fs-tm-input-error').first();
                if ($firstError.length) {
                    var $errCard = $firstError.closest('.fs-tm-period-card');
                    if ($errCard.hasClass('is-collapsed')) {
                        $errCard.removeClass('is-collapsed').addClass('is-expanded');
                        $errCard.find('.fs-tm-period-card-body').slideDown(180);
                    }
                    $('html, body').animate({ scrollTop: $firstError.offset().top - 80 }, 300);
                    $firstError.focus();
                }
                showToast(__('Speichern blockiert:', 'fabriel-team-manager') + ' ' + idCheck.errorMsg, 'error');
                return false;
            }

            if (!validatePeriodContinuity($form, true)) {
                e.preventDefault();
                var $firstConflict = $form.find('.fs-tm-period-card.has-conflict').first();
                if ($firstConflict.length) {
                    if ($firstConflict.hasClass('is-collapsed')) {
                        $firstConflict.removeClass('is-collapsed').addClass('is-expanded');
                        $firstConflict.find('.fs-tm-period-card-body').slideDown(180);
                    }
                    $('html, body').animate({ scrollTop: $firstConflict.offset().top - 80 }, 300);
                }
                showToast(__('Speichern blockiert: Bitte korrigiere die rot markierten Zeiträume.', 'fabriel-team-manager'), 'error');
                return false;
            }

            isSubmitting = true;
        });

        // 9. Neuen Zeitraum anlegen
        $(document).on('click', '.fs-tm-btn-add-period', function(e) {
            e.preventDefault();
            var $button = $(this);
            var $form = $button.closest('.fs-tm-team-form');
            var $section = $form.closest('.fs-tm-section-box');
            
            // Sektion aufklappen wenn sie zugeklappt ist
            if ($section.hasClass('is-collapsed')) {
                $section.removeClass('is-collapsed').addClass('is-expanded');
            }
            
            var $container = $form.find('.fs-tm-periods-container');
            $container.find('.fs-tm-no-periods-msg').hide();
            var idx = new Date().getTime();
            
            // Heute-Datum für "Gültig ab" berechnen (YYYY-MM-DD)
            var today = new Date();
            var todayStr = today.getFullYear() + '-' +
                String(today.getMonth() + 1).padStart(2, '0') + '-' +
                String(today.getDate()).padStart(2, '0');
            
            var t = {
                toggleHint: __('Klicken zum Auf-/Zuklappen', 'fabriel-team-manager'),
                newPeriod: __('Neuer Zeitraum', 'fabriel-team-manager'),
                newBadge: __('Neu (ungespeichert)', 'fabriel-team-manager'),
                deletePeriod: __('Zeitraum löschen', 'fabriel-team-manager'),
                deleteLabel: __('Löschen', 'fabriel-team-manager'),
                labelField: __('Bezeichnung des Zeitraums:', 'fabriel-team-manager'),
                labelTip: __('Wird im Frontend-Dropdown und im Header angezeigt.', 'fabriel-team-manager'),
                labelPlaceholder: __('z. B. Saison 2026/2027 oder Hinrunde', 'fabriel-team-manager'),
                fromField: __('Gültig ab (Start):', 'fabriel-team-manager'),
                fromTip: __('Datum, ab dem die Website automatisch umschaltet.', 'fabriel-team-manager'),
                toField: __('Gültig bis (optional):', 'fabriel-team-manager'),
                toTip: __('Enddatum.', 'fabriel-team-manager'),
                matchesField: __('Spielplan-ID der Mannschaft:', 'fabriel-team-manager'),
                matchesTip: __('UUID aus dem Einbettungscode von fussball.de (data-type „team-matches").', 'fabriel-team-manager'),
                tableField: __('Tabellen-ID:', 'fabriel-team-manager'),
                tableTip: __('UUID aus dem Einbettungscode von fussball.de (data-type „table").', 'fabriel-team-manager'),
                testBtn: __('Testen', 'fabriel-team-manager'),
                testTip: __('Widget live testen / Vorschau', 'fabriel-team-manager'),
                clubToggle: __('Club-Matches', 'fabriel-team-manager'),
                clubTip: __('Schaltet den Widget-Typ von „team-matches" auf „club-matches" um.', 'fabriel-team-manager'),
                idPlaceholder: __('z. B. 01234567-89ab-cdef-0123-456789abcdef', 'fabriel-team-manager')
            };

            var periodHtml = `
<div class="fs-tm-period-card is-new-period is-expanded">
  <div class="fs-tm-period-card-header" title="${t.toggleHint}">
    <div class="fs-tm-period-card-header-left">
      <span class="dashicons dashicons-arrow-down-alt2 fs-tm-accordion-arrow"></span>
      <span class="fs-tm-period-icon">✨</span>
      <strong class="fs-tm-period-title-text">${t.newPeriod}</strong>
      <span class="fs-tm-new-badge">🟡 ${t.newBadge}</span>
    </div>
    <div class="fs-tm-period-card-badges">
      <button type="button" class="button-link-delete fs-tm-delete-period" title="${t.deletePeriod}">
        <span class="dashicons dashicons-trash"></span> ${t.deleteLabel}
      </button>
    </div>
  </div>
  <div class="fs-tm-period-card-body">
    <div class="fs-tm-field-group fs-tm-label-row">
      <label>${t.labelField} <span class="fs-tm-help-tip" data-tip="${t.labelTip}">?</span></label>
      <input type="text" name="team[periods][${idx}][label]" placeholder="${t.labelPlaceholder}" class="fs-tm-input period-label-input fs-tm-input-dirty" required>
    </div>
    <div class="fs-tm-period-dates-row">
      <div class="fs-tm-field-group">
        <label>${t.fromField} <span class="fs-tm-help-tip" data-tip="${t.fromTip}">?</span></label>
        <div class="fs-tm-date-input-wrap">
          <input type="date" name="team[periods][${idx}][valid_from]" value="${todayStr}" class="fs-tm-input period-from-input fs-tm-input-dirty" required>
        </div>
      </div>
      <div class="fs-tm-date-separator">→</div>
      <div class="fs-tm-field-group">
        <label>${t.toField} <span class="fs-tm-help-tip" data-tip="${t.toTip}">?</span></label>
        <div class="fs-tm-date-input-wrap">
          <input type="date" name="team[periods][${idx}][valid_to]" class="fs-tm-input period-to-input fs-tm-input-dirty">
        </div>
      </div>
    </div>
    <div class="fs-tm-period-ids-row">
      <div class="fs-tm-field-group">
        <label class="fs-tm-matches-label">
          📅 <span class="fs-tm-matches-label-text">${t.matchesField}</span>
          <span class="fs-tm-help-tip" data-tip="${t.matchesTip}">?</span>
        </label>
        <div class="fs-tm-uuid-input-wrap">
          <input type="text" name="team[periods][${idx}][id_matches]" class="fs-tm-input code-font fs-tm-input-dirty" placeholder="${t.idPlaceholder}">
          <button type="button" class="button button-secondary fs-tm-btn-test-uuid" data-target="matches" title="${t.testTip}">
            <span class="dashicons dashicons-visibility"></span> <span>${t.testBtn}</span>
          </button>
        </div>
      </div>
      <div class="fs-tm-field-group">
        <label>
          🏆 ${t.tableField}
          <span class="fs-tm-help-tip" data-tip="${t.tableTip}">?</span>
        </label>
        <div class="fs-tm-uuid-input-wrap">
          <input type="text" name="team[periods][${idx}][id_table]" class="fs-tm-input code-font fs-tm-input-dirty" placeholder="${t.idPlaceholder}">
          <button type="button" class="button button-secondary fs-tm-btn-test-uuid" data-target="table" title="${t.testTip}">
            <span class="dashicons dashicons-visibility"></span> <span>${t.testBtn}</span>
          </button>
        </div>
      </div>
    </div>
    <div class="fs-tm-period-footer-row">
      <label class="fs-tm-checkbox-pill">
        <input type="checkbox" name="team[periods][${idx}][is_club_matches]" value="1" class="fs-tm-club-matches-toggle">
        <span>${t.clubToggle}</span>
        <span class="fs-tm-help-tip" data-tip="${t.clubTip}">?</span>
      </label>
    </div>
  </div>
</div>`;

            $container.prepend(periodHtml);
            evaluateFormDirtyState($form);
            
            // Issue 7: Neu hinzugefügten Zeitraum automatisch aufklappen
            var $newPeriod = $container.find('.fs-tm-period-card.is-new-period').first();
            if ($newPeriod.length) {
                $newPeriod.removeClass('is-collapsed').addClass('is-expanded');
                $newPeriod.find('.fs-tm-period-card-body').slideDown(180);
                // Zum neuen Zeitraum scrollen
                $newPeriod.get(0).scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        });

        // 10. Zeitraum löschen
        $(document).on('click', '.fs-tm-delete-period', function(e) {
            e.preventDefault();
            if (!confirm(__('Möchten Sie diesen Zeitraum wirklich löschen?', 'fabriel-team-manager'))) return false;
            var $card = $(this).closest('.fs-tm-period-card');
            var $container = $card.closest('.fs-tm-periods-container');
            var $form = $card.closest('.fs-tm-team-form');

            $card.fadeOut(200, function() {
                $(this).remove();
                if ($container.find('.fs-tm-period-card').length === 0) {
                    $container.find('.fs-tm-no-periods-msg').show();
                }
                validatePeriodContinuity($form, true);
                evaluateFormDirtyState($form);
            });
        });

        // 11. Widget Live-Test / Inspector Logik
        $(document).on('click', '.fs-tm-btn-test-uuid', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var target = $btn.data('target');
            var $wrap = $btn.closest('.fs-tm-uuid-input-wrap');
            var $input = $wrap.find('.code-font');
            var uuid = $.trim($input.val());

            if (!uuid) {
                showToast(__('Bitte zuerst eine UUID in das Feld eintragen.', 'fabriel-team-manager'), 'warning');
                $input.focus();
                return;
            }

            if (!isValidUuid(uuid)) {
                showToast(__('Ungültiges UUID-Format (erwartet: 36 Zeichen mit Bindestrichen).', 'fabriel-team-manager'), 'error');
                $input.focus();
                return;
            }

            var type = 'table';
            if (target === 'matches') {
                var $pCard = $btn.closest('.fs-tm-period-card');
                var isClub = $pCard.find('.fs-tm-club-matches-toggle').is(':checked');
                type = isClub ? 'club-matches' : 'team-matches';
            }

            $('#fs-tm-preview-type-badge').text(type);
            $('#fs-tm-preview-uuid-badge').text(uuid);

            renderWidgetPreview(uuid, type);

            $('#fs-tm-preview-modal').fadeIn(200).attr('aria-hidden', 'false');
            $('body').addClass('modal-open');
        });

        // Device Switcher im Modal
        $(document).on('click', '.fs-tm-device-btn', function(e) {
            e.preventDefault();
            $('.fs-tm-device-btn').removeClass('is-active');
            $(this).addClass('is-active');
            var device = $(this).data('device');
            $('.fs-tm-modal-frame-wrapper').attr('data-device', device);
        });

        // Modal Schließen
        function closePreviewModal() {
            $('#fs-tm-preview-modal').fadeOut(200, function() {
                $('#fs-tm-preview-stage').empty();
            }).attr('aria-hidden', 'true');
            $('body').removeClass('modal-open');
        }

        $(document).on('click', '.fs-tm-modal-close', function(e) {
            e.preventDefault();
            closePreviewModal();
        });

        $(document).on('click', '#fs-tm-preview-modal', function(e) {
            if ($(e.target).is('#fs-tm-preview-modal')) {
                closePreviewModal();
            }
        });

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#fs-tm-preview-modal').is(':visible')) {
                closePreviewModal();
            }
        });

        // 12. Backup Import Bestätigung
        $(document).on('submit', '#fs-tm-import-form', function(e) {
            var mode = $(this).find('input[name="fs_tm_import_mode"]:checked').val();
            var msg = mode === 'replace'
                ? __('Achtung: Im Modus „Ersetzen" wird der komplette aktuelle Bestand durch den Inhalt der Datei ersetzt.\n\nMöchten Sie das Backup jetzt einspielen?', 'fabriel-team-manager')
                : __('Die Mannschaften aus der Datei werden hinzugefügt bzw. aktualisiert.\n\nMöchten Sie das Backup jetzt einspielen?', 'fabriel-team-manager');
            if (!confirm(msg)) {
                e.preventDefault();
                return false;
            }
        });

        /* --------------------------------------------------------------
         * KRITISCH: Delete-Button - VOR Navigation handler binden!
         * ------------------------------------------------------------ */
        $(document).on('click', '#fs-tm-view .fs-tm-btn-del', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var $link = $(this);
            var confirmMsg = $link.data('fs-tm-confirm');
            var href = $link.attr('href');
            
            if (!confirmMsg) {
                window.location.href = href;
                return;
            }
            
            if (confirm(confirmMsg)) {
                window.location.href = href;
            }
            // Abbrechen - passiert nichts
            return false;
        });

        /* --------------------------------------------------------------
         * Navigation: zuletzt gebunden, damit vorherige Prüfungen greifen.
         * ------------------------------------------------------------ */

        $(document).on('click', 'input[type="submit"], button[type="submit"]', function() {
            lastSubmitter = this;
        });

        $(document).on('click', '#fs-tm-view a, .fs-tm-tabs a', function(e) {
            if (e.defaultPrevented || e.which > 1 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

            var $link = $(this);
            var href = $link.attr('href');

            // Delete-Buttons und data-fs-tm-confirm Links ignorieren (eigener Handler oben)
            if ($link.is('.fs-tm-btn-del') || $link.is('[data-fs-tm-confirm]')) return;

            if (!href || $link.attr('target') === '_blank' || $link.is('[data-fs-tm-download]')) return;
            if (!nav.isInternal(href)) return;

            // Downloads liefern kein HTML und müssen den Browser erreichen.
            if (href.indexOf('fs_tm_export') > -1 || href.indexOf('action=fs_tm_export_teams_json') > -1) return;

            e.preventDefault();
            nav.go(href, true);
        });

        $(document).on('submit', '#fs-tm-view form', function(e) {
            if (e.isDefaultPrevented()) return;
            if (this.method && this.method.toLowerCase() === 'get') return;

            e.preventDefault();
            isSubmitting = true;
            nav.submit(this);
        });

        window.addEventListener('popstate', function(event) {
            if (!event.state || !event.state.fsTm) return;
            nav.go(window.location.href, false);
        });

        // Warnung bei ungespeicherten Daten vor Tab-Schließen
        window.addEventListener('beforeunload', function(e) {
            if ($('.fs-tm-team-card.has-unsaved-changes').length > 0 && !isSubmitting) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    });
})(jQuery);
