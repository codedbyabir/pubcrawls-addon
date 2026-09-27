/**
 * PubCrawls Admin Dashboard JS
 */

(function ($) {
	'use strict';

	$(document).ready(function () {
		// ── Tab Switching ──────────────────────────────────────
		var $tabs = $('#pubcrawls-tab-nav a');
		var $panes = $('.pubcrawls-tab-pane');

		$tabs.on('click', function (e) {
			var tab = $(this).data('tab');
			if (!tab) return;

			e.preventDefault();

			// Update tabs
			$tabs.removeClass('nav-tab-active');
			$(this).addClass('nav-tab-active');

			// Update panes
			$panes.removeClass('is-active');
			$('#tab-' + tab).addClass('is-active');

			// Update URL state without page reload
			if (window.history && window.history.replaceState) {
				var url = new URL(window.location.href);
				url.searchParams.set('tab', tab);
				window.history.replaceState(null, '', url.toString());
			}
		});

		// ── Card Toggles Visual State ──────────────────────────
		function bindCardToggle(selector) {
			$(selector).on('change', function () {
				var $card = $(this).closest('.pubcrawls-widget-card');
				if ($(this).is(':checked')) {
					$card.addClass('is-disabled').removeClass('is-enabled');
				} else {
					$card.removeClass('is-disabled').addClass('is-enabled');
				}
			});
		}

		bindCardToggle('.pubcrawls-widget-toggle');
		bindCardToggle('.pubcrawls-pt-toggle');
		bindCardToggle('.pubcrawls-tax-toggle');

		// ── Bulk Widget Actions ────────────────────────────────
		$('#pubcrawls-enable-all').on('click', function () {
			$('.pubcrawls-widget-toggle').prop('checked', false).trigger('change');
		});

		$('#pubcrawls-disable-all').on('click', function () {
			$('.pubcrawls-widget-toggle').prop('checked', true).trigger('change');
		});

		// ── Bulk Post Type Actions ─────────────────────────────
		$('#pubcrawls-enable-all-pt').on('click', function () {
			$('.pubcrawls-pt-toggle').prop('checked', false).trigger('change');
		});

		$('#pubcrawls-disable-all-pt').on('click', function () {
			$('.pubcrawls-pt-toggle').prop('checked', true).trigger('change');
		});

		// ── Bulk Taxonomy Actions ──────────────────────────────
		$('#pubcrawls-enable-all-tax').on('click', function () {
			$('.pubcrawls-tax-toggle').prop('checked', false).trigger('change');
		});

		$('#pubcrawls-disable-all-tax').on('click', function () {
			$('.pubcrawls-tax-toggle').prop('checked', true).trigger('change');
		});
	});

})(jQuery);
