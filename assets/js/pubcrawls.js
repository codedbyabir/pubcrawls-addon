
// ── Popular / All Cities Toggle ──────────────────────────────
jQuery(document).ready(function ($) {
    var $btn = $('#pubcrawls-city-toggle');
    if (!$btn.length) return;

    var $btnText     = $btn.find('.elementor-button-text').length ? $btn.find('.elementor-button-text') : $btn;
    var $popularList = $('.pubcrawls-city-grid#pubcrawls-popular-list');
    var $allList     = $('.pubcrawls-city-grid#pubcrawls-all-list');

    // Store custom button label from Elementor (e.g. "View All Cities")
    var defaultLabel = $btnText.text().trim() || 'View All Cities';
    var toggleLabel  = 'Show Popular Cities';

    // Check whether All list is currently visible based on live DOM or localized data
    function isShowingAll() {
        if ($allList.length && $popularList.length) {
            if ($allList.is(':visible') && $popularList.is(':hidden')) return true;
            if ($popularList.is(':visible') && $allList.is(':hidden')) return false;
        }
        if (typeof pubcrawlsCityData !== 'undefined' && pubcrawlsCityData.showingAll) {
            return pubcrawlsCityData.showingAll === '1';
        }
        return false;
    }

    var showingAll = isShowingAll();

    // Only update button text on load if All Cities is the one active
    if (showingAll) {
        $btnText.text(toggleLabel);
    } else {
        $btnText.text(defaultLabel);
    }

    $(document).on('click', '#pubcrawls-city-toggle', function (e) {
        e.preventDefault();

        $popularList = $('.pubcrawls-city-grid#pubcrawls-popular-list');
        $allList     = $('.pubcrawls-city-grid#pubcrawls-all-list');
        $btnText     = $(this).find('.elementor-button-text').length ? $(this).find('.elementor-button-text') : $(this);

        // Always check current live DOM visibility state
        var currentlyAll = $allList.is(':visible');

        if (!currentlyAll) {
            $popularList.hide();
            $allList.fadeIn(150);
            $btnText.text(toggleLabel);
        } else {
            $allList.hide();
            $popularList.fadeIn(150);
            $btnText.text(defaultLabel);
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
