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

    function parseGa4CookieValue( rawVal ) {
        if ( ! rawVal ) return null;
        var val = decodeURIComponent( rawVal ).replace( /^"+|"+$/g, '' );
        var sid = '';
        var snum = '';

        // Match session ID timestamp (9 to 12 digits):
        // GS2 format: GS2.1.s1728800050$o14$g0$t1728800050 or GS2.1.1728800050...
        // GS1 format: GS1.1.1728800050.14.1.1728800050...
        var sidMatch = val.match( /(?:^|[.$~|])s?(\d{9,12})(?:[.$~|]|$)/ ) || val.match( /\b(\d{9,12})\b/ );
        if ( sidMatch ) {
            sid = sidMatch[1];
        }

        // Match session number ($o14 or GS1 4th dot segment)
        var snumMatch = val.match( /\$o(\d+)/ ) || val.match( /^GS1\.\d+\.\d+\.(\d+)/ );
        if ( snumMatch ) {
            snum = snumMatch[1];
        }

        return sid ? { sessionId: sid, sessionNumber: snum } : null;
    }

    function collectAttribution() {
        // 1. Client ID extraction (_ga cookie: GA1.1.<cid>, GA1.2.<cid>, or <cid>)
        var clientId = '';
        var gaCookie = getCookie( '_ga' );
        if ( gaCookie ) {
            var gaParts = gaCookie.match( /^GA\d+\.\d+\.(.+)$/ );
            if ( gaParts && gaParts[1] ) {
                clientId = gaParts[1];
            } else {
                var parts = gaCookie.split( '.' );
                if ( parts.length >= 4 ) {
                    clientId = parts[2] + '.' + parts[3];
                } else if ( parts.length >= 2 ) {
                    clientId = parts.slice( -2 ).join( '.' );
                } else {
                    clientId = gaCookie;
                }
            }
        }
        if ( ! clientId && window.sessionStorage ) {
            try { clientId = sessionStorage.getItem( 'pc_ga_client_id' ) || ''; } catch ( e ) {}
        }
        if ( clientId && window.sessionStorage ) {
            try { sessionStorage.setItem( 'pc_ga_client_id', clientId ); } catch ( e ) {}
        }

        // 2. GA4 Session ID & Session Number extraction
        var sessionId = '';
        var sessionNumber = '';
        var configuredMid = ( window.pubcrawlsTicketConfig && window.pubcrawlsTicketConfig.ga4MeasurementId )
            ? window.pubcrawlsTicketConfig.ga4MeasurementId.replace( /^G-/, '' ).toUpperCase()
            : '';

        // Priority A: Try configured container cookie first if present
        if ( configuredMid ) {
            var directCookie = getCookie( '_ga_' + configuredMid );
            var parsedDirect = parseGa4CookieValue( directCookie );
            if ( parsedDirect ) {
                sessionId     = parsedDirect.sessionId;
                sessionNumber = parsedDirect.sessionNumber;
            }
        }

        // Priority B: Scan ALL cookies starting with _ga_ (case-insensitive, all containers)
        if ( ! sessionId && document.cookie ) {
            var cookiePairs = document.cookie.split( ';' );
            var candidates = [];
            for ( var i = 0; i < cookiePairs.length; i++ ) {
                var pair = cookiePairs[i].trim();
                var eqIdx = pair.indexOf( '=' );
                if ( eqIdx === -1 ) continue;
                var cName = pair.substring( 0, eqIdx ).trim();
                var cVal  = pair.substring( eqIdx + 1 ).trim();

                // Match _ga_<CONTAINER_ID> (excluding _ga, _gac_, _gat)
                if ( /^(_ga_[A-Za-z0-9_]+)$/i.test( cName ) ) {
                    var parsed = parseGa4CookieValue( cVal );
                    if ( parsed ) {
                        candidates.push( { name: cName, parsed: parsed } );
                    }
                }
            }

            if ( candidates.length > 0 ) {
                var selected = candidates[0];
                for ( var j = 0; j < candidates.length; j++ ) {
                    if ( configuredMid && candidates[j].name.toUpperCase().indexOf( configuredMid ) !== -1 ) {
                        selected = candidates[j];
                        break;
                    }
                    if ( Number( candidates[j].parsed.sessionId ) > Number( selected.parsed.sessionId ) ) {
                        selected = candidates[j];
                    }
                }
                sessionId     = selected.parsed.sessionId;
                sessionNumber = selected.parsed.sessionNumber;
            }
        }

        // Priority C: Fallback to sessionStorage for session continuity
        if ( ! sessionId && window.sessionStorage ) {
            try {
                sessionId     = sessionStorage.getItem( 'pc_ga_session_id' ) || '';
                sessionNumber = sessionStorage.getItem( 'pc_ga_session_number' ) || '';
            } catch ( e ) {}
        }

        // Cache active values in sessionStorage for cross-page / iframe resilience
        if ( sessionId && window.sessionStorage ) {
            try {
                sessionStorage.setItem( 'pc_ga_session_id', sessionId );
                if ( sessionNumber ) sessionStorage.setItem( 'pc_ga_session_number', sessionNumber );
            } catch ( e ) {}
        }

        var urlParams = ( typeof URLSearchParams !== 'undefined' ) ? new URLSearchParams( window.location.search ) : null;
        var fbc = getCookie( '_fbc' );
        if ( ! fbc && urlParams && urlParams.get( 'fbclid' ) ) {
            fbc = 'fb.1.' + Date.now() + '.' + urlParams.get( 'fbclid' );
        }

        var ttclid = getCookie( 'ttclid' ) || ( urlParams ? urlParams.get( 'ttclid' ) : '' ) || '';

        var pageUrl = '';
        if ( typeof window !== 'undefined' && window.location && window.location.href ) {
            try {
                var parsedUrl = new URL( window.location.href );
                var sensitiveKeys = [ 'order_data', 'email', 'phone', 'first_name', 'last_name', 'fn', 'ln', 'em', 'ph', 'token', 'secret', 'key' ];
                sensitiveKeys.forEach( function ( k ) {
                    parsedUrl.searchParams.delete( k );
                } );
                pageUrl = parsedUrl.toString();
            } catch ( e ) {
                pageUrl = ( window.location.href || '' ).split( '?' )[0];
            }
        }

        // OpenAI attribution cookies (__obref: browser ID, __oppref: ad click ID)
        var oai_obref = getCookie( '__obref' ) || ( urlParams ? ( urlParams.get( '__obref' ) || urlParams.get( 'obref' ) ) : '' ) || '';
        var oai_oppref = getCookie( '__oppref' ) || ( urlParams ? ( urlParams.get( '__oppref' ) || urlParams.get( 'oppref' ) ) : '' ) || '';

        return {
            page_url: pageUrl,
            client_id: clientId,
            session_id: sessionId,
            session_number: sessionNumber,
            fbp: getCookie( '_fbp' ),
            fbc: fbc,
            ttp: getCookie( '_ttp' ),
            ttclid: ttclid,
            oai_obref: oai_obref,
            oai_oppref: oai_oppref,
            user_agent: navigator.userAgent || ''
        };
    }

    // Ensure shared global registry across single-event-tickets.js and city-events-tickets.js
    window._pubcrawlsSentAttributionOrders = window._pubcrawlsSentAttributionOrders || {};

    function sendAttribution( orderId, eventId ) {
        if ( ! orderId ) return;
        var cleanOrderId = String( orderId ).replace( /\D/g, '' );
        if ( ! cleanOrderId ) return;

        // In-memory global check across all scripts on this window
        if ( window._pubcrawlsSentAttributionOrders[ cleanOrderId ] ) {
            return;
        }

        // sessionStorage check to guard across callbacks and embedded navigation
        try {
            if ( window.sessionStorage ) {
                if ( window.sessionStorage.getItem( 'pc_sent_attr_' + cleanOrderId ) ) {
                    return;
                }
                window.sessionStorage.setItem( 'pc_sent_attr_' + cleanOrderId, '1' );
            }
        } catch ( e ) {}

        window._pubcrawlsSentAttributionOrders[ cleanOrderId ] = true;

        var data = collectAttribution();
        data.order_id = cleanOrderId;
        data.event_id = String( eventId || '' );

        var endpoint = ( window.pubcrawlsTicketConfig && window.pubcrawlsTicketConfig.attributionUrl )
            ? window.pubcrawlsTicketConfig.attributionUrl
            : '/wp-json/pubcrawls/v1/eventbrite/attribution';

        var payload = JSON.stringify( data );
        var beaconSent = false;

        // Method 1: sendBeacon (guaranteed to complete across page unload / navigation)
        if ( navigator.sendBeacon ) {
            try {
                var blob = new Blob( [ payload ], { type: 'application/json' } );
                beaconSent = navigator.sendBeacon( endpoint, blob );
            } catch ( e ) {
                beaconSent = false;
            }
        }

        // Method 2: fallback to fetch only if sendBeacon failed or is unavailable
        if ( ! beaconSent && window.fetch ) {
            fetch( endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: payload,
                keepalive: true
            } ).catch( function () {} );
        }
    }

    window.pubcrawlsSendAttribution = sendAttribution;

    // Listen to postMessage from Eventbrite embedded checkout iframe once globally
    if ( ! window._pubcrawlsEbMessageListenerAttached ) {
        window._pubcrawlsEbMessageListenerAttached = true;
        window.addEventListener( 'message', function ( event ) {
            if ( ! event.origin || ( event.origin.indexOf( 'eventbrite.com' ) === -1 && event.origin.indexOf( 'eventbriteapi.com' ) === -1 ) ) {
                return;
            }
            var msg = event.data;
            if ( typeof msg === 'string' ) {
                try { msg = JSON.parse( msg ); } catch ( e ) {}
            }
            if ( msg && typeof msg === 'object' ) {
                var rawId = msg.orderId || msg.order_id || msg.id || ( msg.data && ( msg.data.orderId || msg.data.order_id || msg.data.id ) ) || ( msg.result && ( msg.result.orderId || msg.result.order_id || msg.result.id ) );
                var orderId = rawId ? String( rawId ).replace( /\D/g, '' ) : '';
                if ( orderId ) {
                    var eventId = msg.eventId || msg.event_id || ( msg.data && ( msg.data.eventId || msg.data.event_id ) ) || '';
                    if ( typeof window.pubcrawlsSendAttribution === 'function' ) {
                        window.pubcrawlsSendAttribution( orderId, eventId );
                    } else {
                        sendAttribution( orderId, eventId );
                    }
                }
            }
        } );
    }

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
                        var rawId = ( response && ( response.orderId || response.order_id || response.id || ( response.order && ( response.order.id || response.order.order_id ) ) || ( response.data && ( response.data.orderId || response.data.order_id || response.data.id ) ) ) ) || '';
                        var orderId = rawId ? String( rawId ).replace( /\D/g, '' ) : '';
                        if ( orderId ) {
                            sendAttribution( orderId, ebId );
                        }
                        console.log('EB Order complete:', uid, orderId);
                        if ( window.pubcrawlsTicketConfig && window.pubcrawlsTicketConfig.eventbriteConfirmationUrl ) {
                            setTimeout( function () {
                                window.location.href = window.pubcrawlsTicketConfig.eventbriteConfirmationUrl;
                            }, 350 );
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
            var cfg = (typeof pubcrawlsTicketConfig !== 'undefined') ? pubcrawlsTicketConfig : {};
            window.squadup = {
                title: cfg.squadupTitle || 'Pubcrawls Events',
                root: 'squadup-checkout',
                userId: [cfg.squadupUserId || 3438001],
                onDomReady: false,
                shoppingCartEnabled: true,
                ticketGuardianEnabled: false,
                showCheckout: false,
                reservedSeatingEnabled: true,
                descriptionEnabled: false,
                brandingPosition: 'none',
                autoScrollElementId: 'squadup-checkout',
                confirmationUrl: cfg.squadupConfirmationUrl || ( ( typeof window !== 'undefined' && window.location ? window.location.origin : '' ) + '/purchase-confirmation/' ),
                orderQuestions: cfg.squadupOrderQuestions || [{
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

    // ── Update card ticket state ──────────────────────────
    function updateCardTicketState(card, data) {
        var uid      = data.cardUid || card.dataset.cardUid || '';
        var platform = data.platform || '';
        var ebId     = data.ebId   || '';
        var ebLink   = data.ebLink || '';
        var suId     = data.suId   || '';

        var isEventbrite = platform === 'eventbrite' && Boolean(ebId);
        var isSquadUp    = platform === 'squadup' && Boolean(suId);

        var btnWrap = card.querySelector('.pc-event-card-buttons');
        if (btnWrap) {
            var ebBtn = btnWrap.querySelector('button.pc-event-btn--buy:not(.pc-su-trigger)');
            var suBtn = btnWrap.querySelector('.pc-su-trigger');

            if (ebBtn) {
                ebBtn.style.display = isEventbrite ? '' : 'none';
                ebBtn.dataset.ebId   = ebId;
                ebBtn.dataset.ebLink = ebLink;
            }
            if (suBtn) {
                suBtn.style.display = isSquadUp ? '' : 'none';
                suBtn.dataset.suId  = suId;
            }

            if (isEventbrite && ebBtn) {
                var newBtn = rebuildEBButton(btnWrap, uid, ebId, ebLink);
                if (newBtn) initEBWidget(uid, newBtn.id, ebId, ebLink);
            }
        }
    }

    // ── Date buttons click ─────────────────────────────────
    function initDateButtons(card) {
        var buttons = card.querySelectorAll('.pc-event-date-btn');
        if (!buttons.length) return;

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                if (this.classList.contains('is-selected')) {
                    return;
                }

                // Remove selected state from sibling buttons
                buttons.forEach(function (b) {
                    b.classList.remove('is-selected');
                    b.setAttribute('aria-pressed', 'false');
                });

                // Highlight clicked button
                this.classList.add('is-selected');
                this.setAttribute('aria-pressed', 'true');

                // Update Buy Tickets button state
                updateCardTicketState(card, {
                    cardUid:  this.dataset.cardUid,
                    platform: this.dataset.platform,
                    ebId:     this.dataset.ebId   || '',
                    ebLink:   this.dataset.ebLink || '',
                    suId:     this.dataset.suId   || ''
                });
            });
        });
    }

    // ── Date select change (fallback / backwards compatibility) ──
    function initSelectChange(card) {
        var select = card.querySelector('.pc-event-date-select');
        if (!select) return;

        select.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            if (!opt) return;

            updateCardTicketState(card, {
                cardUid:  this.dataset.cardUid,
                platform: opt.dataset.platform,
                ebId:     opt.dataset.ebId   || '',
                ebLink:   opt.dataset.ebLink || '',
                suId:     opt.dataset.suId   || ''
            });
        });
    }

    // ── Squad Up button click ──────────────────────────────
    function initSuTriggers() {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.pc-su-trigger');
            if (!btn) return;
            e.preventDefault();

            var card = btn.closest('.pc-event-card');
            var suId = btn.dataset.suId || '';

            if (card) {
                var selectedBtn = card.querySelector('.pc-event-date-btn.is-selected');
                if (selectedBtn && selectedBtn.dataset.suId) {
                    suId = selectedBtn.dataset.suId;
                } else {
                    var select = card.querySelector('.pc-event-date-select');
                    if (select && select.selectedIndex >= 0 && select.options[select.selectedIndex]) {
                        var opt = select.options[select.selectedIndex];
                        if (opt.dataset && opt.dataset.suId) {
                            suId = opt.dataset.suId;
                        }
                    }
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
        initDateButtons(card);
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