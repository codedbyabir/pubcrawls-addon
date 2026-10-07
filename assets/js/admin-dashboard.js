/**
 * PubCrawls Admin Dashboard JS
 */

(function ($) {
	'use strict';

	$(document).ready(function () {
		// ── Tab Switching ──────────────────────────────────────
		// Every tab lives in ONE form, so switching tabs never discards edits.
		var $form = $('#pubcrawls-settings-form');
		var $tabs = $('#pubcrawls-tab-nav .pubcrawls-nav__item');
		var $panes = $('.pubcrawls-tab-pane');

		function updateRefererTabs(tab) {
			$('input[name="_wp_http_referer"]').each(function () {
				try {
					var raw = $(this).val();
					if (!raw) return;
					var u = new URL(raw, window.location.origin);
					u.searchParams.set('tab', tab);
					$(this).val(u.pathname + u.search);
				} catch (e) {
					var val = $(this).val();
					if (val.indexOf('tab=') > -1) {
						val = val.replace(/tab=[^&]*/, 'tab=' + tab);
					} else {
						val += (val.indexOf('?') > -1 ? '&' : '?') + 'tab=' + tab;
					}
					$(this).val(val);
				}
			});
		}

		function activateTab(tab) {
			if (!tab || !$('#tab-' + tab).length) return;
			$tabs.removeClass('is-active').filter('[data-tab="' + tab + '"]').addClass('is-active');
			$panes.removeClass('is-active');
			$('#tab-' + tab).addClass('is-active');
			updateRefererTabs(tab); // After saving, WordPress returns to this tab.
			if (window.history && window.history.replaceState) {
				var url = new URL(window.location.href);
				url.searchParams.set('tab', tab);
				url.searchParams.delete('settings-updated');
				window.history.replaceState(null, '', url.toString());
			}
		}

		// Sidebar items and any in-page link carrying data-tab.
		$(document).on('click', 'a[data-tab]', function (e) {
			var tab = $(this).data('tab');
			if (!tab) return;
			e.preventDefault();
			activateTab(tab);
			window.scrollTo({ top: 0, behavior: 'smooth' });
		});

		updateRefererTabs($tabs.filter('.is-active').data('tab') || 'overview');

		// ── Unsaved-changes tracking ───────────────────────────
		var $savebar = $('#pubcrawls-savebar');
		var $saveText = $savebar.find('.pubcrawls-savebar__text');
		var $saveIcon = $savebar.find('.pubcrawls-savebar__status .dashicons');
		var dirty = false;
		var submitting = false;
		var i18n = {
			saved: 'All changes saved',
			unsaved: 'You have unsaved changes',
			saving: 'Saving\u2026',
			leave: 'You have unsaved changes. Leave this page without saving?'
		};

		function setDirty(state) {
			if (dirty === state) return;
			dirty = state;
			$savebar.toggleClass('is-dirty', dirty);
			$saveText.text(dirty ? i18n.unsaved : i18n.saved);
			$saveIcon.toggleClass('dashicons-yes-alt', !dirty).toggleClass('dashicons-warning', dirty);
		}

		// Any edit to a setting field (not the AJAX test widgets) marks the form dirty.
		$form.on('input change', 'input, select, textarea', function () {
			var name = this.name || '';
			if (!name || name === '_wpnonce' || name === '_wp_http_referer' || name === 'option_page' || name === 'action') return;
			setDirty(true);
		});

		$form.on('submit', function () {
			submitting = true;
			$savebar.removeClass('is-dirty').addClass('is-saving');
			$saveText.text(i18n.saving);
			$('#pubcrawls-save-btn').prop('disabled', true);
		});

		$(window).on('beforeunload', function (e) {
			if (dirty && !submitting) {
				e.preventDefault();
				e.returnValue = i18n.leave;
				return i18n.leave;
			}
		});

		// The Eventbrite tester/queue widgets sit inside the form: they must never submit it.
		$form.find('button:not([type])').attr('type', 'button');
		$form.on('keydown', 'input:not([type="submit"]):not([type="button"])', function (e) {
			if (e.key === 'Enter') {
				e.preventDefault();
				if (this.id === 'pubcrawls-test-order-id') {
					$('#pubcrawls-test-order-btn').trigger('click');
				}
			}
		});

		// ── URL Slug fields ────────────────────────────────────
		function normalizeSlug(v) {
			return String(v || '')
				.toLowerCase()
				.replace(/[^a-z0-9_\- ]+/g, '')
				.replace(/\s+/g, '-')
				.replace(/-{2,}/g, '-')
				.replace(/^[-_]+|[-_]+$/g, '');
		}

		function refreshSlug($input) {
			var def = String($input.data('default') || '');
			var raw = $input.val();
			var clean = normalizeSlug(raw);
			var effective = clean || def;
			var $wrap = $input.closest('.pubcrawls-slug');
			$wrap.find('.pubcrawls-slug__field').toggleClass('is-modified', effective !== def);
			$wrap.find('.pubcrawls-slug__reset').prop('hidden', effective === def);
			var $prev = $wrap.find('.pubcrawls-slug__preview');
			$prev.text('/' + effective + '/' + ($prev.data('suffix') || '') + '/');
		}

		$('.pubcrawls-slug__input').each(function () { refreshSlug($(this)); });

		$(document).on('input', '.pubcrawls-slug__input', function () {
			refreshSlug($(this));
		});

		// Tidy the value when leaving the field (spaces -> hyphens, strip unsafe characters).
		$(document).on('blur', '.pubcrawls-slug__input', function () {
			var $i = $(this);
			var clean = normalizeSlug($i.val());
			$i.val(clean || $i.data('default'));
			refreshSlug($i);
		});

		$(document).on('click', '.pubcrawls-slug__reset', function () {
			var $i = $(this).closest('.pubcrawls-slug').find('.pubcrawls-slug__input');
			$i.val($i.data('default')).trigger('input').trigger('change').trigger('focus');
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

		// ── Process Backlog Action ─────────────────────────────
		$('#pubcrawls-process-backlog-btn').on('click', function (e) {
			e.preventDefault();
			if (typeof pubcrawlsAdminData === 'undefined') return;

			var $btn = $(this);
			var $resBox = $('#pubcrawls-queue-action-result');

			$btn.prop('disabled', true).text('Processing Backlog...');
			$resBox.show().html('<div style="padding:12px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:6px;color:#0369a1;">Processing queued orders and dispatching server-side events...</div>');

			$.ajax({
				url: pubcrawlsAdminData.ajax_url,
				method: 'POST',
				data: {
					action: 'pubcrawls_process_eb_backlog',
					limit: 50,
					nonce: pubcrawlsAdminData.nonce
				},
				success: function (res) {
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-controls-play" style="font-size:16px;width:16px;height:16px;"></span> Process Backlog Now');
					if (res.success && res.data) {
						var d = res.data;
						var html = '<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:6px;padding:16px;">';
						html += '<h4 style="margin:0 0 8px;color:#166534;"><span class="dashicons dashicons-yes-alt" style="color:#16a34a;"></span> Queue Batch Completed (' + d.duration_ms + 'ms)</h4>';
						html += '<p style="margin:0 0 6px;color:#15803d;font-size:13px;">Found: <strong>' + d.total_found + '</strong> | Processed: <strong>' + d.processed + '</strong> | Completed: <strong>' + d.completed + '</strong> | Partial: <strong>' + d.partial + '</strong> | Failed: <strong>' + d.failed + '</strong></p>';
						html += '<p style="margin:0 0 6px;color:#15803d;font-size:13px;">Events Dispatched: GA4: <strong>' + d.ga4_sent + '</strong> | Meta: <strong>' + d.meta_sent + '</strong> | TikTok: <strong>' + d.tiktok_sent + '</strong></p>';
						html += '<button type="button" class="button button-small" onclick="window.location.reload();" style="margin-top:6px;">Refresh Table</button>';
						html += '</div>';
						$resBox.html(html);
					} else {
						var err = (res.data && res.data.message) ? res.data.message : 'Unknown error during queue processing.';
						$resBox.html('<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:12px;color:#991b1b;">Error: ' + err + '</div>');
					}
				},
				error: function (xhr) {
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-controls-play" style="font-size:16px;width:16px;height:16px;"></span> Process Backlog Now');
					$resBox.html('<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:12px;color:#991b1b;">AJAX Error: HTTP ' + xhr.status + '</div>');
				}
			});
		});

		// ── Release Stale Locks Action ─────────────────────────
		$('#pubcrawls-release-stale-btn').on('click', function (e) {
			e.preventDefault();
			if (typeof pubcrawlsAdminData === 'undefined') return;

			var $btn = $(this);
			$btn.prop('disabled', true).text('Releasing...');

			$.ajax({
				url: pubcrawlsAdminData.ajax_url,
				method: 'POST',
				data: {
					action: 'pubcrawls_release_stale_locks',
					nonce: pubcrawlsAdminData.nonce,
					force: 1
				},
				success: function (res) {
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-unlock" style="font-size:16px;width:16px;height:16px;"></span> Release Stale Locks');
					if (res.success && res.data) {
						alert(res.data.message || 'Stale locks released.');
						window.location.reload();
					} else {
						alert('Failed to release stale locks.');
					}
				},
				error: function (xhr) {
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-unlock" style="font-size:16px;width:16px;height:16px;"></span> Release Stale Locks');
					alert('AJAX Error: HTTP ' + xhr.status);
				}
			});
		});
	});

})(jQuery);

