<?php
/**
 * PubCrawls Bulk Title & Slug Editor
 *
 * Provides bulk actions to Append, Prepend, Remove (start/end), and Find & Replace
 * in post titles across tribe_events, city, venue, and custom post types directly
 * from the WordPress admin list tables.
 *
 * @package Pubcrawl_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Pubcrawls_Bulk_Title_Editor
 */
class Pubcrawls_Bulk_Title_Editor {

	/**
	 * Single instance.
	 *
	 * @var Pubcrawls_Bulk_Title_Editor|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize feature.
	 */
	public static function init(): void {
		self::get_instance();
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Hook filters immediately for known types
		$this->register_hooks();

		// Also register on admin_init in case post types were registered late
		add_action( 'admin_init', array( $this, 'register_hooks' ) );

		add_action( 'admin_footer-edit.php', array( $this, 'render_admin_panel' ) );
		add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
	}

	/**
	 * Register bulk actions and handlers for all supported post types.
	 */
	public function register_hooks(): void {
		$target_types = self::get_target_post_types();

		foreach ( $target_types as $pt ) {
			if ( ! has_filter( "bulk_actions-edit-{$pt}", array( $this, 'register_bulk_actions' ) ) ) {
				add_filter( "bulk_actions-edit-{$pt}", array( $this, 'register_bulk_actions' ) );
			}
			if ( ! has_filter( "handle_bulk_actions-edit-{$pt}", array( $this, 'handle_bulk_actions' ) ) ) {
				add_filter( "handle_bulk_actions-edit-{$pt}", array( $this, 'handle_bulk_actions' ), 10, 3 );
			}
		}
	}

	/**
	 * Get post types supported by the Bulk Title Editor.
	 *
	 * Includes PubCrawls core types (tribe_events, city, venue, faq) as well as
	 * standard post/page, and allows custom post types via filter.
	 *
	 * @return array List of post type slugs.
	 */
	public static function get_target_post_types(): array {
		$types = array(
			'tribe_events',
			'city',
			'venue',
			'faq',
			'post',
			'page',
		);

		/**
		 * Filter supported post types for Bulk Title Editor.
		 *
		 * @param array $types Array of post type slugs.
		 */
		return apply_filters( 'pubcrawls_bulk_title_editor_post_types', $types );
	}

	/**
	 * Register bulk actions in the table dropdown.
	 *
	 * @param array $actions Array of bulk action slug => label.
	 * @return array
	 */
	public function register_bulk_actions( array $actions ): array {
		$actions['append_to_title']     = esc_html__( 'Append to title', 'pubcrawls-addon' );
		$actions['prepend_to_title']    = esc_html__( 'Prepend to title', 'pubcrawls-addon' );
		$actions['remove_from_title']   = esc_html__( 'Remove from title', 'pubcrawls-addon' );
		$actions['remove_prefix_title'] = esc_html__( 'Remove prefix from title', 'pubcrawls-addon' );
		$actions['replace_in_title']    = esc_html__( 'Find & Replace in title', 'pubcrawls-addon' );

		return $actions;
	}

	/**
	 * Handle bulk actions execution.
	 *
	 * @param string $redirect_to Redirect URL.
	 * @param string $action      Action name.
	 * @param array  $post_ids    Array of selected post IDs.
	 * @return string
	 */
	public function handle_bulk_actions( string $redirect_to, string $action, array $post_ids ): string {
		$valid_actions = array(
			'append_to_title',
			'prepend_to_title',
			'remove_from_title',
			'remove_prefix_title',
			'replace_in_title',
		);

		if ( ! in_array( $action, $valid_actions, true ) ) {
			return $redirect_to;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$string    = isset( $_REQUEST['bat_string'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['bat_string'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$find      = isset( $_REQUEST['bat_find'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['bat_find'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$replace   = isset( $_REQUEST['bat_replace'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['bat_replace'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$add_space = isset( $_REQUEST['bat_space'] ) && '1' === $_REQUEST['bat_space'];
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sync_slug = isset( $_REQUEST['bat_sync_slug'] ) && '1' === $_REQUEST['bat_sync_slug'];

		// Validation: Replace mode requires $find; other modes require $string
		if ( 'replace_in_title' === $action ) {
			if ( '' === $find ) {
				return add_query_arg( 'bat_notice', 'empty', $redirect_to );
			}
		} else {
			if ( '' === $string ) {
				return add_query_arg( 'bat_notice', 'empty', $redirect_to );
			}
		}

		$updated      = 0;
		$skipped      = 0;
		$target_types = self::get_target_post_types();

		foreach ( $post_ids as $post_id ) {
			$post = get_post( (int) $post_id );
			if ( ! $post || ! in_array( $post->post_type, $target_types, true ) ) {
				continue;
			}

			// Capability check
			if ( ! current_user_can( 'edit_post', $post->ID ) ) {
				$skipped++;
				continue;
			}

			$current = $post->post_title;
			$new     = $current;

			if ( 'append_to_title' === $action ) {
				$append_val = $add_space ? ' ' . $string : $string;
				$new        = $current . $append_val;
			} elseif ( 'prepend_to_title' === $action ) {
				$prepend_val = $add_space ? $string . ' ' : $string;
				$new         = $prepend_val . $current;
			} elseif ( 'remove_from_title' === $action ) {
				// Try removing with preceding space first, then without
				if ( $add_space && self::str_ends_with( $current, ' ' . $string ) ) {
					$new = substr( $current, 0, -strlen( ' ' . $string ) );
				} elseif ( self::str_ends_with( $current, $string ) ) {
					$new = substr( $current, 0, -strlen( $string ) );
				} else {
					$skipped++;
					continue;
				}
			} elseif ( 'remove_prefix_title' === $action ) {
				// Try removing with trailing space first, then without
				if ( $add_space && self::str_starts_with( $current, $string . ' ' ) ) {
					$new = substr( $current, strlen( $string . ' ' ) );
				} elseif ( self::str_starts_with( $current, $string ) ) {
					$new = substr( $current, strlen( $string ) );
				} else {
					$skipped++;
					continue;
				}
			} elseif ( 'replace_in_title' === $action ) {
				if ( '' !== $find && false !== strpos( $current, $find ) ) {
					$new = str_replace( $find, $replace, $current );
				} else {
					$skipped++;
					continue;
				}
			}

			// Avoid unnecessary database update if title didn't change
			if ( $new === $current ) {
				$skipped++;
				continue;
			}

			$post_data = array(
				'ID'         => $post->ID,
				'post_title' => $new,
			);

			// Optional slug synchronization
			if ( $sync_slug ) {
				$post_data['post_name'] = sanitize_title( $new );
			}

			wp_update_post( $post_data );
			clean_post_cache( $post->ID );
			$updated++;
		}

		$query_args = array(
			'bat_notice'  => 'done',
			'bat_action'  => $action,
			'bat_updated' => $updated,
			'bat_skipped' => $skipped,
		);

		if ( 'replace_in_title' === $action ) {
			$query_args['bat_find']    = urlencode( $find );
			$query_args['bat_replace'] = urlencode( $replace );
		} else {
			$append_report = $add_space ? ' ' . $string : $string;
			$query_args['bat_string'] = urlencode( $append_report );
		}

		return add_query_arg( $query_args, $redirect_to );
	}

	/**
	 * Render the interactive control panel and client-side logic on post list screens.
	 */
	public function render_admin_panel(): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, self::get_target_post_types(), true ) ) {
			return;
		}
		?>
		<style>
			#bat-panel {
				display: none;
				background: #ffffff;
				border: 1px solid #2271b1;
				border-left: 4px solid #2271b1;
				border-radius: 4px;
				padding: 14px 18px;
				margin: 12px 0 16px;
				align-items: center;
				gap: 12px;
				flex-wrap: wrap;
				box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
				transition: all 0.2s ease;
			}
			#bat-panel.bat-visible { display: flex !important; }
			#bat-panel.bat-remove  { border-color: #b32d2e; border-left-color: #b32d2e; }
			#bat-panel.bat-replace { border-color: #0d9488; border-left-color: #0d9488; }
			#bat-panel label       { font-weight: 600; font-size: 13px; color: #1e293b; white-space: nowrap; }
			#bat-panel input[type="text"] { min-width: 220px; font-size: 13px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 4px; }
			#bat-panel .bat-hint   { color: #64748b; font-size: 12px; margin-left: 4px; }
			#bat-space-wrap, #bat-slug-wrap { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: #334155; white-space: nowrap; cursor: pointer; }
			#bat-space-wrap input, #bat-slug-wrap input { margin: 0; cursor: pointer; }
			#bat-preview           { font-size: 12px; color: #0f172a; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px 10px; white-space: nowrap; font-family: monospace; }
			#bat-close-btn         { margin-left: auto; cursor: pointer; color: #64748b; font-size: 20px; line-height: 1; border: none; background: none; padding: 2px 6px; border-radius: 4px; }
			#bat-close-btn:hover   { color: #0f172a; background: #f1f5f9; }
		</style>

		<script>
		jQuery(function ($) {
			var $form = $('#posts-filter');
			if (!$form.length) return;

			if ($('#bat-panel').length) return;

			var panelHtml = 
				'<div id="bat-panel">' +
					'<span id="bat-string-wrap" style="display:inline-flex; align-items:center; gap:8px;">' +
						'<label for="bat-string-input" id="bat-label">Text to append:</label>' +
						'<input type="text" id="bat-string-input" name="bat_string" placeholder="e.g. 2027" autocomplete="off">' +
					'</span>' +
					'<span id="bat-replace-wrap" style="display:none; align-items:center; gap:8px;">' +
						'<label for="bat-find-input" style="font-weight:600">Find:</label>' +
						'<input type="text" id="bat-find-input" name="bat_find" placeholder="e.g. 2026" autocomplete="off">' +
						'<label for="bat-replace-input" style="font-weight:600">Replace with:</label>' +
						'<input type="text" id="bat-replace-input" name="bat_replace" placeholder="e.g. 2027" autocomplete="off">' +
					'</span>' +
					'<span id="bat-space-wrap">' +
						'<input type="checkbox" id="bat-space-cb" name="bat_space" value="1" checked>' +
						'<label for="bat-space-cb" id="bat-space-label" style="font-weight:normal; cursor:pointer;">Add space before</label>' +
					'</span>' +
					'<span id="bat-slug-wrap" title="Update the URL permalink slug to match the new title">' +
						'<input type="checkbox" id="bat-slug-cb" name="bat_sync_slug" value="1">' +
						'<label for="bat-slug-cb" style="font-weight:normal; cursor:pointer;">Update URL slug</label>' +
					'</span>' +
					'<span id="bat-preview"></span>' +
					'<span class="bat-hint" id="bat-hint">Added to the end of selected titles.</span>' +
					'<button type="button" id="bat-close-btn" title="Cancel bulk edit">&times;</button>' +
				'</div>';

			var $table = $form.find('.wp-list-table, table.posts').first();
			if ($table.length) {
				$table.before(panelHtml);
			} else {
				$form.append(panelHtml);
			}

			var $panel       = $('#bat-panel');
			var $label       = $('#bat-label');
			var $hint        = $('#bat-hint');
			var $stringWrap  = $('#bat-string-wrap');
			var $inp         = $('#bat-string-input');
			var $replaceWrap = $('#bat-replace-wrap');
			var $findInp     = $('#bat-find-input');
			var $replaceInp  = $('#bat-replace-input');
			var $spaceCb     = $('#bat-space-cb');
			var $spaceLabel  = $('#bat-space-label');
			var $spaceWrap   = $('#bat-space-wrap');
			var $preview     = $('#bat-preview');

			function getActiveAction() {
				var topVal = $('#bulk-action-selector-top').val();
				var botVal = $('#bulk-action-selector-bottom').val();
				var valid = ['append_to_title', 'prepend_to_title', 'remove_from_title', 'remove_prefix_title', 'replace_in_title'];
				if (topVal && valid.indexOf(topVal) !== -1) return topVal;
				if (botVal && valid.indexOf(botVal) !== -1) return botVal;
				return null;
			}

			function updatePreview() {
				var action = getActiveAction();
				if (!action) {
					$preview.text('');
					return;
				}

				if (action === 'replace_in_title') {
					var f = $findInp.val();
					var r = $replaceInp.val();
					if (!f) { $preview.text(''); return; }
					$preview.text('Preview: "Event ' + f + '" → "Event ' + r + '"');
					return;
				}

				var val = $inp.val();
				if (!val) {
					$preview.text('');
					return;
				}

				var addSpace = $spaceCb.is(':checked');
				if (action === 'append_to_title') {
					$preview.text('Preview: "Title' + (addSpace ? ' ' : '') + val + '"');
				} else if (action === 'prepend_to_title') {
					$preview.text('Preview: "' + val + (addSpace ? ' ' : '') + 'Title"');
				} else if (action === 'remove_from_title') {
					$preview.text('Preview: "Title' + (addSpace ? ' ' : '') + val + '" → "Title"');
				} else if (action === 'remove_prefix_title') {
					$preview.text('Preview: "' + val + (addSpace ? ' ' : '') + 'Title" → "Title"');
				}
			}

			$(document).on('input', '#bat-string-input, #bat-find-input, #bat-replace-input', function () {
				$(this).css('outline', '');
				updatePreview();
			});

			$(document).on('change', '#bat-space-cb', updatePreview);

			function syncPanel(e) {
				var val = e ? $(this).val() : getActiveAction();
				var action = getActiveAction();

				// Sync both dropdowns
				if (val && val !== '-1') {
					$('#bulk-action-selector-top').val(val);
					$('#bulk-action-selector-bottom').val(val);
				}

				if (!action) {
					$panel.removeClass('bat-visible bat-remove bat-replace');
					return;
				}

				$panel.addClass('bat-visible').removeClass('bat-remove bat-replace');

				if (action === 'replace_in_title') {
					$panel.addClass('bat-replace');
					$stringWrap.hide();
					$replaceWrap.css('display', 'inline-flex');
					$spaceWrap.hide();
					$hint.text('Replaces all occurrences within selected titles.');
				} else {
					$stringWrap.css('display', 'inline-flex');
					$replaceWrap.hide();
					$spaceWrap.css('display', 'inline-flex');

					if (action === 'append_to_title') {
						$label.text('Text to append:');
						$hint.text('Added to the end of every selected title.');
						$inp.attr('placeholder', 'e.g. 2027');
						$spaceLabel.text('Add space before');
					} else if (action === 'prepend_to_title') {
						$label.text('Text to prepend:');
						$hint.text('Added to the start of every selected title.');
						$inp.attr('placeholder', 'e.g. Official: ');
						$spaceLabel.text('Add space after');
					} else if (action === 'remove_from_title') {
						$panel.addClass('bat-remove');
						$label.text('Text to remove:');
						$hint.text('Stripped from the end of selected titles. Non-matching titles skipped.');
						$inp.attr('placeholder', 'e.g. 2027');
						$spaceLabel.text('Remove preceding space');
					} else if (action === 'remove_prefix_title') {
						$panel.addClass('bat-remove');
						$label.text('Prefix to remove:');
						$hint.text('Stripped from the start of selected titles. Non-matching titles skipped.');
						$inp.attr('placeholder', 'e.g. Official: ');
						$spaceLabel.text('Remove trailing space');
					}
				}

				updatePreview();
			}

			$(document).on('change', '#bulk-action-selector-top, #bulk-action-selector-bottom', syncPanel);

			// Initial sync if already selected on page load
			if (getActiveAction()) {
				syncPanel();
			}

			// Close button
			$(document).on('click', '#bat-close-btn', function () {
				$('#bulk-action-selector-top, #bulk-action-selector-bottom').val('-1');
				$panel.removeClass('bat-visible');
			});

			// Validation on Apply button clicks
			$(document).on('click', '#doaction, #doaction2, #bulk-action-submit, #bulk-action-submit-2', function (e) {
				var action = getActiveAction();
				if (!action) return;

				if (action === 'replace_in_title') {
					if (!$findInp.val().trim()) {
						e.preventDefault();
						$findInp.css('outline', '2px solid #b32d2e').focus();
						$findInp.attr('placeholder', 'Enter search text');
						$panel[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
					}
				} else {
					if (!$inp.val().trim()) {
						e.preventDefault();
						$inp.css('outline', '2px solid #b32d2e').focus();
						$inp.attr('placeholder', 'Enter text first');
						$panel[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
					}
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Render admin notification banners after execution.
	 */
	public function render_admin_notices(): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, self::get_target_post_types(), true ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notice = isset( $_GET['bat_notice'] ) ? sanitize_key( $_GET['bat_notice'] ) : '';
		if ( 'done' !== $notice && 'empty' !== $notice ) {
			return;
		}

		if ( 'empty' === $notice ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please enter the required text before applying the bulk action.', 'pubcrawls-addon' ) . '</p></div>';
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action  = isset( $_GET['bat_action'] ) ? sanitize_key( $_GET['bat_action'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$updated = isset( $_GET['bat_updated'] ) ? (int) $_GET['bat_updated'] : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$skipped = isset( $_GET['bat_skipped'] ) ? (int) $_GET['bat_skipped'] : 0;

		$msg = '';
		if ( 'replace_in_title' === $action ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$find    = isset( $_GET['bat_find'] ) ? sanitize_text_field( urldecode( $_GET['bat_find'] ) ) : '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$replace = isset( $_GET['bat_replace'] ) ? sanitize_text_field( urldecode( $_GET['bat_replace'] ) ) : '';

			$msg = sprintf(
				/* translators: 1: Find text, 2: Replace text, 3: Updated count */
				esc_html__( 'Replaced "%1$s" with "%2$s" in %3$d title(s).', 'pubcrawls-addon' ),
				'<strong>' . esc_html( $find ) . '</strong>',
				'<strong>' . esc_html( $replace ) . '</strong>',
				$updated
			);
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$string = isset( $_GET['bat_string'] ) ? sanitize_text_field( urldecode( $_GET['bat_string'] ) ) : '';

			$verb = ( 'remove_from_title' === $action || 'remove_prefix_title' === $action ) ? 'Removed' : ( 'prepend_to_title' === $action ? 'Prepended' : 'Appended' );
			$prep = ( 'remove_from_title' === $action || 'remove_prefix_title' === $action ) ? 'from' : 'to';

			$msg = sprintf(
				'%s <strong>%s</strong> %s <strong>%d</strong> event title%s.',
				$verb,
				esc_html( $string ),
				$prep,
				$updated,
				1 === $updated ? '' : 's'
			);
		}

		if ( $skipped > 0 ) {
			$msg .= sprintf(
				' <strong>%d</strong> title%s skipped (did not match criteria).',
				$skipped,
				1 === $skipped ? ' was' : 's were'
			);
		}

		echo '<div class="notice notice-success is-dismissible"><p>' . wp_kses_post( $msg ) . '</p></div>';
	}

	/**
	 * Polyfill helper: check if string ends with needle (compatible with PHP 7.4+).
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to search for.
	 * @return bool
	 */
	public static function str_ends_with( string $haystack, string $needle ): bool {
		if ( function_exists( 'str_ends_with' ) ) {
			return str_ends_with( $haystack, $needle );
		}
		$needle_len = strlen( $needle );
		return 0 === $needle_len || 0 === substr_compare( $haystack, $needle, -$needle_len );
	}

	/**
	 * Polyfill helper: check if string starts with needle (compatible with PHP 7.4+).
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to search for.
	 * @return bool
	 */
	public static function str_starts_with( string $haystack, string $needle ): bool {
		if ( function_exists( 'str_starts_with' ) ) {
			return str_starts_with( $haystack, $needle );
		}
		return '' === $needle || 0 === strncmp( $haystack, $needle, strlen( $needle ) );
	}
}
