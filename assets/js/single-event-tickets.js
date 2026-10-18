/**
 * Single Event Tickets Card & Sticky Bar Script
 *
 * Synchronizes ticket instances (sidebar card & sticky footer bar) on a single event page.
 * Manages date selection, date broadcasts (df:dateChanged), and checkout triggers (Eventbrite / SquadUp).
 *
 * Used by:
 * - Elementor: Event Tickets Card Widget (widget-event-tickets.php)
 * - Elementor: Sticky Ticket Bar Widget (widget-sticky-ticket-bar.php)
 * - Shortcodes: [pubcrawls_event_tickets], [pubcrawls_sticky_ticket_bar]
 *
 * Handle: 'pcet-tickets'
 * Target Selectors: [data-pcet-instance], [data-pcet-select], [data-pcet-trigger], [data-df-field]
 */
( function () {
    'use strict';

    var _widgetRegistry = {};
    var suModal        = null;
    var suEventLoaded  = false;

    // SquadUp fires this when an event has loaded into the embed.
    document.addEventListener( 'eventLoaded', function () {
        suEventLoaded = true;
    } );

    // ── Field broadcast ────────────────────────────────────
    function broadcastDateChange( data ) {
        document.querySelectorAll( '[data-df-field]' ).forEach( function ( el ) {
            var field = el.getAttribute( 'data-df-field' );
            switch ( field ) {
                case 'date':       if ( data.date )      el.textContent = data.date;      break;
                case 'time':       if ( data.time )      el.textContent = data.time;      break;
                case 'date-short': if ( data.dateShort ) el.textContent = data.dateShort; break;
                case 'day':        if ( data.day )       el.textContent = data.day;       break;
                case 'month':      if ( data.month )     el.textContent = data.month;     break;
                case 'year':       if ( data.year )      el.textContent = data.year;      break;
            }
        } );
        document.dispatchEvent( new CustomEvent( 'df:dateChanged', { detail: data } ) );
    }

    function updateDisplay( instance, date, time ) {
        var dateEl = instance.querySelector( '[data-pcet-field="date"]' );
        var timeEl = instance.querySelector( '[data-pcet-field="time"]' );
        if ( dateEl && date ) dateEl.textContent = date;
        if ( timeEl && time ) timeEl.textContent = time;
    }

    // ── Attribution & Eventbrite ─────────────────────────
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

        // GA4 session id: Support dynamic measurement cookie matching (_ga_<MEASUREMENT_ID>)
        // Supports the current GS2 format (GS2.1.s<session_id>$o..$t..) and the older GS1 format.
        var sessionId = '';
        var configuredMid = ( window.pubcrawlsTicketConfig && window.pubcrawlsTicketConfig.ga4MeasurementId )
            ? window.pubcrawlsTicketConfig.ga4MeasurementId.replace( /^G-/, '' )
            : '';
        var targetCookieName = configuredMid ? ( '_ga_' + configuredMid ) : '';
        var ga4Cookie = targetCookieName ? getCookie( targetCookieName ) : '';
        if ( ! ga4Cookie ) {
            var ga4Match = document.cookie.match( /(?:^|;\s*)(_ga_[A-Z0-9]+)=([^;]*)/ );
            ga4Cookie = ga4Match ? decodeURIComponent( ga4Match[2] ) : getCookie( '_ga_65M38WNEJH' );
        }
        if ( ga4Cookie ) {
            var sMatch = ga4Cookie.match( /^GS[12]\.\d+\.(?:s)?(\d+)/ ) || ga4Cookie.match( /(?:^|[$~|])s?(\d{9,12})/ );
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
            fbp: getCookie( '_fbp' ),
            fbc: fbc,
            ttp: getCookie( '_ttp' ),
            ttclid: ttclid,
            oai_obref: oai_obref,
            oai_oppref: oai_oppref,
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

    window.pubcrawlsSendAttribution = sendAttribution;

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

    function attachFallback( triggerId, ebLink ) {
        if ( ! triggerId || ! ebLink ) return;
        var btn = document.getElementById( triggerId );
        if ( ! btn ) return;
        btn.addEventListener( 'click', function ( e ) {
            e.preventDefault();
            window.open( ebLink, '_blank', 'noopener,noreferrer' );
        } );
    }

    function initWidget( uid, triggerId, ebId, ebLink ) {
        if ( ! ebId || ! triggerId ) {
            if ( ebLink ) attachFallback( triggerId, ebLink );
            return;
        }
        var attempts = 0, maxMs = 5000, interval = 200;

        function tryInit() {
            attempts += interval;
            if ( window.EBWidgets ) {
                try {
                    window.EBWidgets.createWidget( {
                        widgetType            : 'checkout',
                        eventId               : ebId,
                        modal                 : true,
                        modalTriggerElementId : triggerId,
                        onOrderComplete       : function ( response ) {
                            var orderId = ( response && ( response.orderId || response.order_id || response.id ) ) || '';
                            if ( orderId ) {
                                sendAttribution( orderId, ebId );
                            }
                            document.dispatchEvent( new CustomEvent( 'pcet:orderComplete', {
                                detail: { ebId: ebId, uid: uid, orderId: orderId }
                            } ) );
                            if ( window.pubcrawlsTicketConfig && window.pubcrawlsTicketConfig.eventbriteConfirmationUrl ) {
                                window.location.href = window.pubcrawlsTicketConfig.eventbriteConfirmationUrl;
                            }
                        }
                    } );
                    _widgetRegistry[ triggerId ] = true;
                } catch ( e ) {
                    attachFallback( triggerId, ebLink );
                }
            } else if ( attempts < maxMs ) {
                setTimeout( tryInit, interval );
            } else {
                attachFallback( triggerId, ebLink );
            }
        }
        tryInit();
    }

    // ── SquadUp ────────────────────────────────────────────
    function openSuModal( suId ) {
        if ( ! suId ) return;
        suId = String( suId ).trim();

        if ( ! window.squadup ) {
            window.squadup = {};
        }
        window.squadup.eventId = suId;
        window.squadup.showCheckout = false;

        if ( window.SquadupConfig ) {
            if ( ! window.SquadupConfig.configValues ) {
                window.SquadupConfig.configValues = window.squadup;
            } else {
                window.SquadupConfig.configValues.eventId = suId;
                window.SquadupConfig.configValues.showCheckout = false;
            }
        }

        document.dispatchEvent( new CustomEvent( 'destroySquadupEmbed' ) );
        document.dispatchEvent( new CustomEvent( 'createSquadupEmbed' ) );

        if ( suModal ) {
            suModal.classList.add( 'is-open' );
            suModal.setAttribute( 'aria-hidden', 'false' );
            document.body.style.overflow = 'hidden';
        }
    }

    function closeSuModal() {
        document.dispatchEvent( new CustomEvent( 'destroySquadupEmbed' ) );
        if ( suModal ) {
            suModal.classList.remove( 'is-open' );
            suModal.setAttribute( 'aria-hidden', 'true' );
            document.body.style.overflow = '';
        }
    }

    function initSuModal() {
        suModal = document.getElementById( 'pcet-su-modal' );
        if ( ! suModal ) return;

        suModal.querySelectorAll( '[data-pcet-su-close]' ).forEach( function ( el ) {
            el.addEventListener( 'click', closeSuModal );
        } );

        document.addEventListener( 'keydown', function ( e ) {
            if ( e.key === 'Escape' && suModal.classList.contains( 'is-open' ) ) {
                closeSuModal();
            }
        } );

        // SquadUp's in-embed shopping-cart link.
        document.addEventListener( 'click', function ( e ) {
            var cartLink = e.target.closest( 'a[href="#shopping-cart"]' );
            if ( ! cartLink ) return;
            e.preventDefault();
            if ( window.squadup ) {
                window.squadup.eventId = null;
                window.squadup.showCheckout = true;
            }
            if ( window.SquadupConfig && window.SquadupConfig.configValues ) {
                window.SquadupConfig.configValues.eventId = null;
                window.SquadupConfig.configValues.showCheckout = true;
            }
            document.dispatchEvent( new CustomEvent( 'destroySquadupEmbed' ) );
            document.dispatchEvent( new CustomEvent( 'createSquadupEmbed' ) );
        } );
    }

    // ── Button wiring (provider-aware) ─────────────────────
    function wireButton( uid, btn ) {
        if ( ! btn ) return;
        var provider = btn.dataset.provider || 'eventbrite';

        if ( provider === 'squadup' ) {
            btn.addEventListener( 'click', function ( e ) {
                e.preventDefault();
                openSuModal( btn.dataset.suId || '' );
            } );
        } else {
            initWidget( uid, btn.id, btn.dataset.ebId || '', btn.dataset.ebLink || '' );
        }
    }

    function rebuildButton( uid, wrap, data, callback ) {
        var oldBtn = wrap.querySelector( '[data-pcet-trigger]' );
        var cls    = oldBtn ? oldBtn.className : 'pcet-btn pcet-btn--primary';
        var txt    = oldBtn ? oldBtn.textContent : 'Buy Tickets';
        if ( oldBtn ) wrap.removeChild( oldBtn );

        var b               = document.createElement( 'button' );
        b.id                = uid + '_btn_' + Date.now();
        b.className         = cls;
        b.type              = 'button';
        b.dataset.pcetTrigger = uid;
        b.dataset.provider  = data.provider || 'eventbrite';
        b.dataset.ebId      = data.ebId  || '';
        b.dataset.ebLink    = data.ebLink || '';
        b.dataset.suId      = data.suId  || '';
        b.textContent       = txt;
        wrap.appendChild( b );

        if ( typeof callback === 'function' ) callback( b );
    }

    function readOption( opt ) {
        return {
            provider : opt.dataset.provider  || 'eventbrite',
            ebId     : opt.dataset.ebId      || '',
            ebLink   : opt.dataset.ebLink    || '',
            suId     : opt.dataset.suId      || '',
            date     : opt.dataset.date      || '',
            time     : opt.dataset.time      || '',
            day      : opt.dataset.day       || '',
            month    : opt.dataset.month     || '',
            year     : opt.dataset.year      || '',
            short    : opt.dataset.dateShort || '',
            iso      : opt.dataset.dateIso   || ''
        };
    }

    function readDateBtn( btn ) {
        return {
            provider : btn.dataset.provider  || 'eventbrite',
            ebId     : btn.dataset.ebId      || '',
            ebLink   : btn.dataset.ebLink    || '',
            suId     : btn.dataset.suId      || '',
            date     : btn.dataset.date      || '',
            time     : btn.dataset.time      || '',
            day      : btn.dataset.day       || '',
            month    : btn.dataset.month     || '',
            year     : btn.dataset.year      || '',
            short    : btn.dataset.dateShort || '',
            iso      : btn.dataset.dateIso   || ''
        };
    }

    function syncOtherInstances( d, sourceEl ) {
        // Sync other select dropdowns
        document.querySelectorAll( '[data-pcet-select]' ).forEach( function ( other ) {
            if ( other === sourceEl ) return;
            Array.from( other.options ).forEach( function ( oOpt, index ) {
                if ( oOpt.dataset.dateIso !== d.iso ) return;
                other.selectedIndex = index;

                var oUid  = other.dataset.pcetSelect;
                var oInst = document.querySelector( '[data-pcet-instance="' + oUid + '"]' );
                if ( ! oInst ) return;

                var od = readOption( oOpt );
                updateDisplay( oInst, od.date, od.time );

                var oWrap = oInst.querySelector( '[data-pcet-btn-wrap="' + oUid + '"]' );
                if ( oWrap ) {
                    rebuildButton( oUid, oWrap, od, function ( nb ) {
                        wireButton( oUid, nb );
                    } );
                }
            } );
        } );

        // Sync other date button groups
        document.querySelectorAll( '[data-pcet-buttons]' ).forEach( function ( oButtons ) {
            var otherBtns = oButtons.querySelectorAll( '.pcet-date-btn' );
            otherBtns.forEach( function ( oBtn ) {
                if ( oBtn === sourceEl ) return;
                if ( oBtn.dataset.dateIso === d.iso ) {
                    otherBtns.forEach( function ( b ) {
                        b.classList.remove( 'is-selected' );
                        b.setAttribute( 'aria-pressed', 'false' );
                    } );
                    oBtn.classList.add( 'is-selected' );
                    oBtn.setAttribute( 'aria-pressed', 'true' );

                    var oUid  = oButtons.dataset.pcetButtons;
                    var oInst = document.querySelector( '[data-pcet-instance="' + oUid + '"]' );
                    if ( ! oInst ) return;

                    var od = readDateBtn( oBtn );
                    updateDisplay( oInst, od.date, od.time );

                    var oWrap = oInst.querySelector( '[data-pcet-btn-wrap="' + oUid + '"]' );
                    if ( oWrap ) {
                        rebuildButton( oUid, oWrap, od, function ( nb ) {
                            wireButton( oUid, nb );
                        } );
                    }
                }
            } );
        } );
    }

    // ── Per-instance init ──────────────────────────────────
    function initInstance( instance ) {
        var uid = instance.dataset.pcetInstance;
        if ( ! uid ) return;

        var select   = instance.querySelector( '[data-pcet-select="' + uid + '"]' );
        var btnWrap  = instance.querySelector( '[data-pcet-btn-wrap="' + uid + '"]' );
        var btn      = instance.querySelector( '[data-pcet-trigger="' + uid + '"]' );
        var dateBtns = instance.querySelectorAll( '.pcet-date-btn[data-pcet-btn-date="' + uid + '"]' );

        wireButton( uid, btn );

        // Broadcast first date.
        if ( select && select.options.length > 0 ) {
            broadcastDateChange( fromOpt( select.options[0] ) );
        } else if ( dateBtns && dateBtns.length > 0 ) {
            broadcastDateChange( fromBtn( dateBtns[0] ) );
        } else {
            var dEl = instance.querySelector( '[data-pcet-field="date"]' );
            var tEl = instance.querySelector( '[data-pcet-field="time"]' );
            broadcastDateChange( {
                date: dEl ? dEl.textContent.trim() : '',
                time: tEl ? tEl.textContent.trim() : '',
                dateShort: '', day: '', month: '', year: '', iso: ''
            } );
        }

        function handleDateSelection( d, sourceEl ) {
            updateDisplay( instance, d.date, d.time );

            if ( btnWrap ) {
                rebuildButton( uid, btnWrap, d, function ( newBtn ) {
                    wireButton( uid, newBtn );
                } );
            }

            syncOtherInstances( d, sourceEl );
            broadcastDateChange( d );
        }

        // V1 Dropdown change
        if ( select ) {
            select.addEventListener( 'change', function () {
                var d = readOption( this.options[ this.selectedIndex ] );
                handleDateSelection( d, select );
            } );
        }

        // V2 Date Buttons click
        if ( dateBtns && dateBtns.length > 0 ) {
            dateBtns.forEach( function ( dBtn ) {
                dBtn.addEventListener( 'click', function ( e ) {
                    e.preventDefault();
                    if ( this.classList.contains( 'is-selected' ) ) {
                        return;
                    }

                    dateBtns.forEach( function ( b ) {
                        b.classList.remove( 'is-selected' );
                        b.setAttribute( 'aria-pressed', 'false' );
                    } );

                    this.classList.add( 'is-selected' );
                    this.setAttribute( 'aria-pressed', 'true' );

                    var d = readDateBtn( this );
                    handleDateSelection( d, this );
                } );
            } );
        }
    }

    function fromOpt( opt ) {
        var d = readOption( opt );
        return {
            date: d.date, time: d.time, dateShort: d.short,
            day: d.day, month: d.month, year: d.year, iso: d.iso
        };
    }

    function fromBtn( btn ) {
        var d = readDateBtn( btn );
        return {
            date: d.date, time: d.time, dateShort: d.short,
            day: d.day, month: d.month, year: d.year, iso: d.iso
        };
    }

    function boot() {
        initSuModal();
        document.querySelectorAll( '[data-pcet-instance]' ).forEach( initInstance );
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', boot );
    } else {
        boot();
    }

} )();