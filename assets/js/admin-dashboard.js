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

		// ── Bulk Post URL Column Actions ───────────────────────
		$('#pubcrawls-select-all-url-pts').on('click', function () {
			$('.pubcrawls-url-pt-checkbox').prop('checked', true);
		});

		$('#pubcrawls-deselect-all-url-pts').on('click', function () {
			$('.pubcrawls-url-pt-checkbox').prop('checked', false);
		});

		// ── Webhook URL Copy Button ────────────────────────────
		$(document).on('click', '.pubcrawls-copy-btn', function (e) {
			e.preventDefault();
			var text = $(this).data('copy');
			var $btn = $(this);
			if (!text) return;

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(function () {
					var original = $btn.html();
					$btn.html('<span class="dashicons dashicons-yes" style="vertical-align:text-bottom;margin-right:4px;"></span> Copied!');
					setTimeout(function () {
						$btn.html(original);
					}, 2000);
				});
			} else {
				var $temp = $('<input>');
				$('body').append($temp);
				$temp.val(text).select();
				document.execCommand('copy');
				$temp.remove();
				var original = $btn.html();
				$btn.html('<span class="dashicons dashicons-yes" style="vertical-align:text-bottom;margin-right:4px;"></span> Copied!');
				setTimeout(function () {
					$btn.html(original);
				}, 2000);
			}
		});

		// ── Webhook Order Tester Simulation ────────────────────
		$('#pubcrawls-test-order-btn').on('click', function (e) {
			e.preventDefault();
			var orderId = $('#pubcrawls-test-order-id').val().trim();
			if (!orderId) {
				alert('Please enter an Eventbrite Order ID to test.');
				$('#pubcrawls-test-order-id').focus();
				return;
			}

			if (typeof pubcrawlsAdminData === 'undefined') {
				alert('Admin data configuration error.');
				return;
			}

			var $btn = $(this);
			var $result = $('#pubcrawls-test-order-result');

			$btn.prop('disabled', true).text('Testing Pipeline...');
			$result.show().html('<div style="padding:12px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:6px;color:#475569;">Fetching order from Eventbrite and dispatching server-side events...</div>');

			$.ajax({
				url: pubcrawlsAdminData.ajax_url,
				method: 'POST',
				data: {
					action: 'pubcrawls_test_eb_order',
					order_id: orderId,
					nonce: pubcrawlsAdminData.nonce
				},
				success: function (res) {
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-controls-play" style="vertical-align:text-bottom;margin-right:4px;"></span> Test Process Order');
					if (res.success) {
						var html = '<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:6px;padding:16px;">';
						html += '<h4 style="margin:0 0 8px;color:#166534;"><span class="dashicons dashicons-yes-alt" style="color:#16a34a;"></span> Order Processed Successfully!</h4>';
						html += '<pre style="background:#0f172a;color:#f8fafc;padding:12px;border-radius:6px;overflow:auto;max-height:300px;font-size:12px;">' + JSON.stringify(res.data, null, 2) + '</pre>';
						html += '</div>';
						$result.html(html);
					} else {
						var errMsg = (res.data && res.data.error) ? res.data.error : ((res.data && res.data.message) ? res.data.message : 'Unknown error');
						var html = '<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:16px;">';
						html += '<h4 style="margin:0 0 8px;color:#991b1b;"><span class="dashicons dashicons-dismiss" style="color:#ef4444;"></span> Test Failed</h4>';
						html += '<p style="margin:0 0 10px;color:#b91c1c;">' + errMsg + '</p>';
						html += '<pre style="background:#0f172a;color:#f8fafc;padding:12px;border-radius:6px;overflow:auto;max-height:300px;font-size:12px;">' + JSON.stringify(res.data, null, 2) + '</pre>';
						html += '</div>';
						$result.html(html);
					}
				},
				error: function (xhr) {
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-controls-play" style="vertical-align:text-bottom;margin-right:4px;"></span> Test Process Order');
					$result.html('<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:14px;color:#991b1b;">AJAX Error: HTTP ' + xhr.status + ' ' + xhr.statusText + '</div>');
				}
			});
		});

		// ── Clear Logs Action ──────────────────────────────────
		$('#pubcrawls-clear-logs-btn').on('click', function (e) {
			e.preventDefault();
			if (!confirm('Are you sure you want to clear all webhook activity logs?')) {
				return;
			}

			if (typeof pubcrawlsAdminData === 'undefined') return;

			var $btn = $(this);
			$btn.prop('disabled', true).text('Clearing...');

			$.ajax({
				url: pubcrawlsAdminData.ajax_url,
				method: 'POST',
				data: {
					action: 'pubcrawls_clear_eb_logs',
					nonce: pubcrawlsAdminData.nonce
				},
				success: function () {
					window.location.reload();
				}
			});
		});

		// ── Retry Order Action ─────────────────────────────────
		$(document).on('click', '.pubcrawls-retry-order-btn', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var orderId = $btn.data('order-id');
			if (!orderId) return;

			if (!confirm('Retry conversion tracking for order #' + orderId + '? (Only failed platforms will be retried).')) {
				return;
			}

			$btn.prop('disabled', true).text('Retrying...');

			$.ajax({
				url: pubcrawlsAdminData.ajax_url,
				method: 'POST',
				data: {
					action: 'pubcrawls_retry_eb_order',
					order_id: orderId,
					nonce: pubcrawlsAdminData.nonce
				},
				success: function (res) {
					if (res.success) {
						alert('Order #' + orderId + ' retried successfully!');
						window.location.reload();
					} else {
						var err = (res.data && res.data.error) ? res.data.error : 'Retry failed.';
						alert('Retry error: ' + err);
						$btn.prop('disabled', false).text('Retry');
					}
				},
				error: function (xhr) {
					alert('AJAX Error: HTTP ' + xhr.status);
					$btn.prop('disabled', false).text('Retry');
				}
			});
		});
	});

})(jQuery);

