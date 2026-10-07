
// ── Privacy & PII Protection: Clean sensitive URL parameters (order_data, emails, etc.) ──
(function () {
    if (typeof window === 'undefined' || !window.location || !window.history || !window.history.replaceState) return;
    try {
        var url = new URL(window.location.href);
        var sensitiveKeys = ['order_data', 'email', 'phone', 'first_name', 'last_name', 'fn', 'ln', 'em', 'ph', 'token', 'secret', 'key'];
        var changed = false;
        sensitiveKeys.forEach(function (k) {
            if (url.searchParams.has(k)) {
                url.searchParams.delete(k);
                changed = true;
            }
        });
        if (changed) {
            window.history.replaceState(null, '', url.pathname + (url.searchParams.toString() ? '?' + url.searchParams.toString() : '') + url.hash);
        }
    } catch (e) {}
})();

// ── Popular / All Cities Toggle ──────────────────────────────
jQuery(document).ready(function ($) {
    if (typeof pubcrawlsCityData === 'undefined') return;

    var $btn         = $('#pubcrawls-city-toggle');
    var $btnText     = $btn.find('.elementor-button-text'); // IMPORTANT
    var $popularList = $('.pubcrawls-city-grid#pubcrawls-popular-list');
    var $allList     = $('.pubcrawls-city-grid#pubcrawls-all-list');
    var showingAll   = pubcrawlsCityData.showingAll === '1';

    // Initial label
    if (showingAll) {
        $btnText.text('Show Popular Cities');
    } else {
        $btnText.text('View All Cities');
    }

    $(document).on('click', '#pubcrawls-city-toggle', function () {
        if (!showingAll) {
            $popularList.hide();
            $allList.show();
            $btnText.text('Show Popular Cities');
            showingAll = true;
        } else {
            $allList.hide();
            $popularList.show();
            $btnText.text('View All Cities');
            showingAll = false;
        }
    });
});





// City Event Page CTA Buy Now BTN Hit Buy Ticket
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.pubcrawl-hit-buy-btn').forEach(function (triggerBtn) {

        triggerBtn.addEventListener('click', function (e) {

            e.preventDefault();

            const buyBtn = document.querySelector('.pcet-btn');

            if (buyBtn) {
                buyBtn.click();
            }

        });

    });

});
