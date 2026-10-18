/**
 * PubCrawls - Steps Timeline Widget Interactive Scroll Script
 *
 * Dynamically computes line fill progress and activates timeline items on scroll into view.
 * Fully supports live preview in Elementor Editor.
 *
 * @package Pubcrawl_Addon
 */
(function ($) {
    'use strict';

    function initTimeline($scope) {
        var $timeline = $scope.find('.pc-timeline');
        if (!$timeline.length) {
            return;
        }

        var el = $timeline[0];
        var lineTrack = el.querySelector('.pc-timeline__line-track');
        var lineFill = el.querySelector('.pc-timeline__line-fill');
        var items = el.querySelectorAll('.pc-timeline__item');

        if (!lineTrack || !lineFill || !items.length) {
            return;
        }

        var triggerPercent = parseFloat($timeline.data('trigger-offset'));
        if (isNaN(triggerPercent)) {
            triggerPercent = 50;
        }

        var isScrollFill = $timeline.data('scroll-fill') !== 'no';
        var editorPreview = $timeline.data('editor-preview') || 'live';

        // Check if inside Elementor editor
        var isEditMode = Boolean(
            window.elementorFrontend &&
            window.elementorFrontend.isEditMode &&
            window.elementorFrontend.isEditMode()
        );

        if (isEditMode && editorPreview !== 'live') {
            var staticPct = (editorPreview === '100%') ? 100 : ((editorPreview === '50%') ? 50 : 0);
            lineFill.style.height = staticPct + '%';
            for (var p = 0; p < items.length; p++) {
                var threshold = (p + 0.5) / items.length * 100;
                if (staticPct >= threshold) {
                    items[p].classList.add('pc-timeline__item--active');
                } else {
                    items[p].classList.remove('pc-timeline__item--active');
                }
            }
            return;
        }

        if (!isScrollFill) {
            lineFill.style.height = '100%';
            for (var q = 0; q < items.length; q++) {
                items[q].classList.add('pc-timeline__item--active');
            }
            return;
        }

        // Measure actual badge centers to clamp the line track between first and last badge
        function updateLineBounds() {
            if (!items.length) return;
            var firstBadge = items[0].querySelector('.pc-timeline__badge');
            var lastBadge = items[items.length - 1].querySelector('.pc-timeline__badge');
            if (firstBadge && lastBadge) {
                var elRect = el.getBoundingClientRect();
                var firstRect = firstBadge.getBoundingClientRect();
                var lastRect = lastBadge.getBoundingClientRect();

                var topOffset = (firstRect.top + firstRect.height / 2) - elRect.top;
                var bottomOffset = elRect.bottom - (lastRect.top + lastRect.height / 2);

                lineTrack.style.top = Math.max(0, topOffset) + 'px';
                lineTrack.style.bottom = Math.max(0, bottomOffset) + 'px';
            }
        }

        var ticking = false;

        function updateProgress() {
            var trackRect = lineTrack.getBoundingClientRect();
            var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
            var triggerY = viewportHeight * (triggerPercent / 100);

            var trackTop = trackRect.top;
            var trackHeight = trackRect.height;

            if (trackHeight <= 0) {
                ticking = false;
                return;
            }

            var progressDistance = triggerY - trackTop;
            var progress = (progressDistance / trackHeight) * 100;
            progress = Math.min(Math.max(progress, 0), 100);

            lineFill.style.height = progress.toFixed(1) + '%';

            // Check each item's badge position relative to triggerY
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                var badge = item.querySelector('.pc-timeline__badge');
                if (badge) {
                    var badgeRect = badge.getBoundingClientRect();
                    var badgeCenterY = badgeRect.top + badgeRect.height / 2;
                    if (badgeCenterY <= triggerY) {
                        item.classList.add('pc-timeline__item--active');
                    } else {
                        item.classList.remove('pc-timeline__item--active');
                    }
                }
            }

            ticking = false;
        }

        function requestTick() {
            if (!ticking) {
                ticking = true;
                window.requestAnimationFrame(updateProgress);
            }
        }

        updateLineBounds();
        updateProgress();

        window.addEventListener('scroll', requestTick, { passive: true });
        window.addEventListener('resize', function () {
            updateLineBounds();
            requestTick();
        });

        if (window.ResizeObserver) {
            var ro = new ResizeObserver(function () {
                updateLineBounds();
                requestTick();
            });
            ro.observe(el);
        }
    }

    $(window).on('elementor/frontend/init', function () {
        elementorFrontend.hooks.addAction('frontend/element_ready/pc-timeline.default', initTimeline);
    });

    $(document).ready(function () {
        $('.elementor-widget-pc-timeline, .pc-timeline-wrap').each(function () {
            initTimeline($(this));
        });
    });

})(jQuery);
