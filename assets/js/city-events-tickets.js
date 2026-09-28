/**
 * City Events Grid & Archive Tickets Script
 *
 * Handles date dropdown switching and modal checkout triggers (Eventbrite / SquadUp)
 * for each individual event card in multi-event grids and archives.
 *
 * Used by:
 * - Elementor: City Events Grid Widget (widget-city-events.php)
 * - Elementor: City Archive Widget (widget-city-archive.php)
 * - Shortcodes: [pubcrawls_city_events], [pubcrawls_city_archive]
 *
 * Handle: 'pubcrawls-ticket-js'
 * Target Selectors: .pc-event-card, .pc-event-date-select, .pc-event-btn--buy, .pc-su-trigger
 */
(function () {
    'use strict';

    // ── Eventbrite ─────────────────────────────────────────
    var widgetRegistry = {};

    function waitForEB(callback) {
        if (window.EBWidgets) { callback(); return; }
        var interval = setInterval(function () {
            if (window.EBWidgets) { clearInterval(interval); callback(); }
        }, 100);
    }

    function getCookie( name ) {
        var match = document.cookie.match( new RegExp( '(^|;\\s*)(' + name + ')=([^;]*)' ) );
        return match ? decodeURIComponent( match[3] ) : '';
    }

    function collectAttribution() {
        var gaCookie = getCookie( '_ga' );
        var clientId = '';
        if ( gaCookie ) {
            var parts = gaCookie.split( '.' );
            if ( parts.length >= 4 ) {
                clientId = parts[2] + '.' + parts[3];
            } else if ( parts.length >= 2 ) {
                clientId = parts.slice( -2 ).join( '.' );
            }
        }

        // GA4 session id from the PubCrawls property cookie (_ga_65M38WNEJH).
        // Supports the current GS2 format (GS2.1.s<session_id>$o..$t..) and the older GS1 format.
        var sessionId = '';
        var ga4Cookie = getCookie( '_ga_65M38WNEJH' );
        if ( ga4Cookie ) {
            var sMatch = ga4Cookie.match( /^GS2\.\d+\.s(\d+)/ ) || ga4Cookie.match( /^GS1\.\d+\.(\d+)/ );
            if ( sMatch ) {
                sessionId = sMatch[1];
            }
        }

        var urlParams = ( typeof URLSearchParams !== 'undefined' ) ? new URLSearchParams( window.location.search ) : null;
        var fbc = getCookie( '_fbc' );
        if ( ! fbc && urlParams && urlParams.get( 'fbclid' ) ) {
            fbc = 'fb.1.' + Date.now() + '.' + urlParams.get( 'fbclid' );
        }

        var ttclid = getCookie( 'ttclid' ) || ( urlParams ? urlParams.get( 'ttclid' ) : '' ) || '';

        return {
            client_id: clientId,
            session_id: sessionId,
            fbp: getCookie( '_fbp' ),
            fbc: fbc,
            ttp: getCookie( '_ttp' ),
            ttclid: ttclid,
            user_agent: navigator.userAgent || ''
        };
    }

    function sendAttribution( orderId, eventId ) {
        if ( ! orderId ) return;
        var data = collectAttribution();
        data.order_id = String( orderId );
        data.event_id = String( eventId || '' );

        var endpoint = ( window.pubcrawlsTicketConfig && window.pubcrawlsTicketConfig.attributionUrl )
            ? window.pubcrawlsTicketConfig.attributionUrl
            : '/wp-json/pubcrawls/v1/eventbrite/attribution';

        if ( window.fetch ) {
            fetch( endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify( data ),
                keepalive: true
            } ).catch( function () {} );
        }
    }

    // Listen to postMessage from Eventbrite embedded checkout iframe
    window.addEventListener( 'message', function ( event ) {
        if ( ! event.origin || ( event.origin.indexOf( 'eventbrite.com' ) === -1 && event.origin.indexOf( 'eventbriteapi.com' ) === -1 ) ) {
            return;
        }
        var msg = event.data;
        if ( typeof msg === 'string' ) {
            try { msg = JSON.parse( msg ); } catch ( e ) {}
        }
        if ( msg && typeof msg === 'object' ) {
            var orderId = msg.orderId || msg.order_id || ( msg.data && ( msg.data.orderId || msg.data.order_id ) );
            if ( orderId ) {
                sendAttribution( orderId, msg.eventId || msg.event_id );
            }
        }
    } );

    function initEBWidget(uid, triggerId, ebId, ebLink) {
        if (!ebId || !triggerId) return;
        if (widgetRegistry[triggerId]) return;

        waitForEB(function () {
            try {
                window.EBWidgets.createWidget({
                    widgetType: 'checkout',
                    eventId: ebId,
                    modal: true,
                    modalTriggerElementId: triggerId,
                    onOrderComplete: function ( response ) {
                        var orderId = ( response && ( response.orderId || response.order_id || response.id ) ) || '';
                        if ( orderId ) {
                            sendAttribution( orderId, ebId );
                        }
                        console.log('EB Order complete:', uid, orderId);
                        if ( window.pubcrawlsTicketConfig && window.pubcrawlsTicketConfig.eventbriteConfirmationUrl ) {
                            window.location.href = window.pubcrawlsTicketConfig.eventbriteConfirmationUrl;
                        }
                    }
                });
                widgetRegistry[triggerId] = true;
            } catch (e) {
                if (ebLink) {
                    var btn = document.getElementById(triggerId);
                    if (btn) {
                        btn.addEventListener('click', function (e) {
                            e.preventDefault();
                            window.open(ebLink, '_blank', 'noopener,noreferrer');
                        });
                    }
                }
            }
        });
    }

    function rebuildEBButton(wrap, uid, ebId, ebLink) {
        var oldBtn = wrap.querySelector('button.pc-event-btn--buy:not(.pc-su-trigger)');
        if (!oldBtn) return null;

        var newBtn = document.createElement('button');
        newBtn.id              = uid + '_btn_' + Date.now();
        newBtn.className       = oldBtn.className;
        newBtn.type            = 'button';
        newBtn.dataset.cardUid = uid;
        newBtn.dataset.ebId    = ebId;
        newBtn.dataset.ebLink  = ebLink;
        newBtn.textContent     = oldBtn.textContent;

        wrap.replaceChild(newBtn, oldBtn);
        return newBtn;
    }

    // ── Squad Up Modal (vanilla JS, no Bootstrap) ──────────
    var suModal = null;

    function openSuModal(suId) {
        if (!suId) {
            console.warn('SquadUp: No event ID provided');
            return;
        }

        suId = String(suId).trim();

        // 1. Ensure baseline squadup object exists
        if (!window.squadup) {
            window.squadup = {
                title: 'Pubcrawls Events',
                root: 'squadup-checkout',
                userId: [3438001],
                onDomReady: false,
                shoppingCartEnabled: true,
                ticketGuardianEnabled: false,
                showCheckout: false,
                reservedSeatingEnabled: true,
                descriptionEnabled: false,
                brandingPosition: 'none',
                autoScrollElementId: 'squadup-checkout',
                confirmationUrl: 'https://pubcrawls.com/purchase-confirmation/',
                orderQuestions: [{
                    question: 'Phone Number for SMS Updates related to your event(s).',
                    type: 'text',
                    required: false
                }]
            };
        }

        // 2. CRITICAL: Mutate in-place so reference held by SquadupConfig.configValues receives eventId
        window.squadup.eventId = suId;
        window.squadup.showCheckout = false;

        // Directly sync SquadupConfig if already instantiated by SDK
        if (window.SquadupConfig) {
            if (!window.SquadupConfig.configValues) {
                window.SquadupConfig.configValues = window.squadup;
            } else {
                window.SquadupConfig.configValues.eventId = suId;
                window.SquadupConfig.configValues.showCheckout = false;
            }
        }

        function triggerEmbed() {
            // Unmount previous instance if any, then mount with the new eventId
            document.dispatchEvent(new CustomEvent('destroySquadupEmbed'));
            document.dispatchEvent(new CustomEvent('createSquadupEmbed'));
        }

        if (window.SquadupConfig) {
            triggerEmbed();
        } else {
            var attempts = 0;
            var timer = setInterval(function () {
                attempts++;
                if (window.SquadupConfig || attempts > 30) {
                    clearInterval(timer);
                    if (window.SquadupConfig && window.SquadupConfig.configValues) {
                        window.SquadupConfig.configValues.eventId = suId;
                        window.SquadupConfig.configValues.showCheckout = false;
                    }
                    triggerEmbed();
                }
            }, 100);
        }

        suModal = suModal || document.getElementById('pcet-su-modal') || document.getElementById('pc-su-modal');
        if (suModal) {
            suModal.classList.add('is-open');
            suModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeSuModal() {
        document.dispatchEvent(new CustomEvent('destroySquadupEmbed'));
        suModal = suModal || document.getElementById('pcet-su-modal') || document.getElementById('pc-su-modal');
        if (suModal) {
            suModal.classList.remove('is-open');
            suModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
    }

    function initSuModal() {
        // Remove duplicate modals if any exist (keeping only the first)
        var modals = document.querySelectorAll('#pcet-su-modal, #pc-su-modal');
        if (modals.length > 1) {
            for (var m = 1; m < modals.length; m++) {
                modals[m].parentNode.removeChild(modals[m]);
            }
        }

        suModal = document.getElementById('pcet-su-modal') || document.getElementById('pc-su-modal');
        if (!suModal) return;

        // Close buttons
        suModal.querySelectorAll('[data-pcet-su-close], .pcet-su-modal__close, .pc-su-modal-close, #pc-su-modal-close').forEach(function (btn) {
            if (!btn.dataset.suBound) {
                btn.dataset.suBound = 'true';
                btn.addEventListener('click', closeSuModal);
            }
        });

        // Click outside on backdrop overlay or modal wrapper to close
        if (!suModal.dataset.suBound) {
            suModal.dataset.suBound = 'true';
            suModal.addEventListener('click', function (e) {
                if (e.target === suModal || e.target.classList.contains('pcet-su-modal__overlay') || e.target.classList.contains('pc-su-modal-overlay')) {
                    closeSuModal();
                }
            });
        }

        // ESC key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && suModal && suModal.classList.contains('is-open')) {
                closeSuModal();
            }
        });

        // Squad Up shopping cart link inside embed
        document.addEventListener('click', function (e) {
            var cartLink = e.target.closest('a[href="#shopping-cart"]');
            if (!cartLink) return;
            e.preventDefault();
            if (window.squadup) {
                window.squadup.eventId = null;
                window.squadup.showCheckout = true;
            }
            if (window.SquadupConfig && window.SquadupConfig.configValues) {
                window.SquadupConfig.configValues.eventId = null;
                window.SquadupConfig.configValues.showCheckout = true;
            }
            document.dispatchEvent(new CustomEvent('destroySquadupEmbed'));
            document.dispatchEvent(new CustomEvent('createSquadupEmbed'));
        });
    }

    // ── Date select change ─────────────────────────────────
    function initSelectChange(card) {
        var select  = card.querySelector('.pc-event-date-select');
        var btnWrap = card.querySelector('.pc-event-card-buttons');

        if (!select) return;

        select.addEventListener('change', function () {
            var opt      = this.options[this.selectedIndex];
            var uid      = this.dataset.cardUid;
            var platform = opt.dataset.platform;
            var ebId     = opt.dataset.ebId   || '';
            var ebLink   = opt.dataset.ebLink || '';
            var suId     = opt.dataset.suId   || '';

            var isEventbrite = platform === 'eventbrite' && Boolean(ebId);
            var isSquadUp    = platform === 'squadup' && Boolean(suId);

            if (btnWrap) {
                var ebBtn = btnWrap.querySelector('button.pc-event-btn--buy:not(.pc-su-trigger)');
                var suBtn = btnWrap.querySelector('.pc-su-trigger');

                if (ebBtn) ebBtn.style.display = isEventbrite ? '' : 'none';
                if (suBtn) {
                    suBtn.style.display = isSquadUp ? '' : 'none';
                    suBtn.dataset.suId  = suId;
                }

                if (isEventbrite && ebBtn) {
                    var newBtn = rebuildEBButton(btnWrap, uid, ebId, ebLink);
                    if (newBtn) initEBWidget(uid, newBtn.id, ebId, ebLink);
                }
            }
        });
    }

    // ── Squad Up button click ──────────────────────────────
    function initSuTriggers() {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.pc-su-trigger');
            if (!btn) return;
            e.preventDefault();

            var card   = btn.closest('.pc-event-card');
            var uid    = btn.dataset.cardUid;
            var select = card
                ? card.querySelector('.pc-event-date-select')
                : (uid ? document.querySelector('.pc-event-date-select[data-card-uid="' + uid + '"]') : null);

            var suId = btn.dataset.suId || '';
            if (select && select.selectedIndex >= 0 && select.options[select.selectedIndex]) {
                var opt = select.options[select.selectedIndex];
                if (opt.dataset && opt.dataset.suId) {
                    suId = opt.dataset.suId;
                }
            }

            openSuModal(suId);
        });
    }

    // ── Init each card ─────────────────────────────────────
    function initCard(card) {
        var ebBtn = card.querySelector('.pc-event-btn--buy:not(.pc-su-trigger)');
        if (ebBtn && ebBtn.style.display !== 'none') {
            initEBWidget('', ebBtn.id, ebBtn.dataset.ebId, ebBtn.dataset.ebLink);
        }
        initSelectChange(card);
    }

    // ── Boot ───────────────────────────────────────────────
    function boot() {
        document.querySelectorAll('.pc-event-card').forEach(initCard);
        initSuModal();
        initSuTriggers();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

})();