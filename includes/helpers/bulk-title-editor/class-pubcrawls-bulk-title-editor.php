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
		$target_types = self::get_target_post_types();

		foreach ( $target_types as $pt ) {
			add_filter( "bulk_actions-edit-{$pt}", array( $this, 'register_bulk_actions' ) );
			add_filter( "handle_bulk_actions-edit-{$pt}", array( $this, 'handle_bulk_actions' ), 10, 3 );
		}

		add_action( 'admin_footer-edit.php', array( $this, 'render_admin_panel' ) );
		add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
	}

	/**
	 * Get post types supported by the Bulk Title Editor.
	 *
	 * Checks existing post types to ensure portability across different WordPress sites.
	 *
	 * @return array List of post type slugs.
	 */
	public static function get_target_post_types(): array {
		$types = array();

		if ( post_type_exists( 'tribe_events' ) ) {
			$types[] = 'tribe_events';
		}
		if ( post_type_exists( 'city' ) ) {
			$types[] = 'city';
		}
		if ( post_type_exists( 'venue' ) ) {
			$types[] = 'venue';
		}
		if ( post_type_exists( 'faq' ) ) {
			$types[] = 'faq';
		}

		// Fallback for standalone sites where CPTs might not be active yet
		if ( empty( $types ) ) {
			$types = array( 'post', 'page' );
		}

		/**
		 * Filter supported post types for Bulk Title Editor.
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
		$actions['append_to_title']        = esc_html__( 'Append to title (End)', 'pubcrawls-addon' );
		$actions['prepend_to_title']       = esc_html__( 'Prepend to title (Start)', 'pubcrawls-addon' );
		$actions['remove_from_title']      = esc_html__( 'Remove from title (End)', 'pubcrawls-addon' );
		$actions['remove_prefix_title']    = esc_html__( 'Remove from title (Start)', 'pubcrawls-addon' );
		$actions['replace_in_title']       = esc_html__( 'Find & Replace in title', 'pubcrawls-addon' );

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

		$updated = 0;
		$skipped = 0;
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

			switch ( $action ) {
				case 'append_to_title':
					$append_val = $add_space ? ' ' . $string : $string;
					$new        = $current . $append_val;
					break;

				case 'prepend_to_title':
					$prepend_val = $add_space ? $string . ' ' : $string;
					$new         = $prepend_val . $current;
					break;

				case 'remove_from_title':
					// Try removing with preceding space first, then without
					if ( $add_space && self::str_ends_with( $current, ' ' . $string ) ) {
						$new = substr( $current, 0, -strlen( ' ' . $string ) );
					} elseif ( self::str_ends_with( $current, $string ) ) {
						$new = substr( $current, 0, -strlen( $string ) );
					} else {
						$skipped++;
						continue 2;
					}
					break;

				case 'remove_prefix_title':
					// Try removing with trailing space first, then without
					if ( $add_space && self::str_starts_with( $current, $string . ' ' ) ) {
						$new = substr( $current, strlen( $string . ' ' ) );
					} elseif ( self::str_starts_with( $current, $string ) ) {
						$new = substr( $current, strlen( $string ) );
					} else {
						$skipped++;
						continue 2;
					}
					break;

				case 'replace_in_title':
					if ( false !== strpos( $current, $find ) ) {
						$new = str_replace( $find, $replace, $current );
					} else {
						$skipped++;
						continue 2;
					}
					break;
			}

			// Avoid unnecessary update if title didn't change
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
			$query_args['bat_string'] = urlencode( $string );
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
				border: 1px solid #2563eb;
				border-left: 4px solid #2563eb;
				border-radius: 6px;
				padding: 14px 18px;
				margin: 12px 0 16px;
				align-items: center;
				gap: 12px;
				flex-wrap: wrap;
				box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
			}
			#bat-panel.bat-visible { display: flex; }
			#bat-panel.bat-danger  { border-color: #dc2626; border-left-color: #dc2626; }
			#bat-panel.bat-replace { border-color: #0d9488; border-left-color: #0d9488; }
			#bat-panel label       { font-weight: 600; font-size: 13px; color: #1e293b; white-space: nowrap; }
			#bat-panel input[type="text"] { min-width: 220px; font-size: 13px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 4px; }
			#bat-panel .bat-hint   { color: #64748b; font-size: 12px; margin-left: 4px; }
			#bat-space-wrap, #bat-slug-wrap { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #334155; white-space: nowrap; cursor: pointer; }
			#bat-space-wrap input, #bat-slug-wrap input { margin: 0; cursor: pointer; }
			#bat-preview           { font-size: 12px; color: #0f172a; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px 10px; white-space: nowrap; font-family: monospace; }
			#bat-close-btn         { margin-left: auto; cursor: pointer; color: #64748b; font-size: 18px; line-height: 1; border: none; background: none; padding: 2px; }
			#bat-close-btn:hover   { color: #0f172a; }
		</style>

		<script>
		document.addEventListener('DOMContentLoaded', function () {
			var form = document.getElementById('posts-filter');
			if (!form) return;

			// ── Build Panel HTML ──────────────────────────────────────────
			var panel = document.createElement('div');
			panel.id  = 'bat-panel';
			panel.innerHTML =
				'<label for="bat-string-input" id="bat-label">Text to append:</label>' +
				'<input type="text" id="bat-string-input" name="bat_string" placeholder="e.g. 2027" autocomplete="off">' +
				
				// Find & Replace secondary input container
				'<span id="bat-replace-wrap" style="display:none; align-items:center; gap:8px;">' +
					'<label for="bat-replace-input" style="font-weight:600">Replace with:</label>' +
					'<input type="text" id="bat-replace-input" name="bat_replace" placeholder="e.g. 2028" autocomplete="off">' +
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
				'<button type="button" id="bat-close-btn" title="Cancel bulk edit">&times;</button>';

			var table = form.querySelector('.wp-list-table');
			if (table) {
				form.insertBefore(panel, table);
			} else {
				form.appendChild(panel);
			}

			var label       = document.getElementById('bat-label');
			var hint        = document.getElementById('bat-hint');
			var inp         = document.getElementById('bat-string-input');
			var replaceWrap = document.getElementById('bat-replace-wrap');
			var replaceInp  = document.getElementById('bat-replace-input');
			var spaceCb     = document.getElementById('bat-space-cb');
			var spaceLabel  = document.getElementById('bat-space-label');
			var spaceWrap   = document.getElementById('bat-space-wrap');
			var slugWrap    = document.getElementById('bat-slug-wrap');
			var preview     = document.getElementById('bat-preview');
			var closeBtn    = document.getElementById('bat-close-btn');

			// ── Get Active Bulk Action ──────────────────────────────────
			function getActiveAction() {
				var top = document.getElementById('bulk-action-selector-top');
				var bot = document.getElementById('bulk-action-selector-bottom');
				var valid = ['append_to_title', 'prepend_to_title', 'remove_from_title', 'remove_prefix_title', 'replace_in_title'];
				if (top && valid.indexOf(top.value) !== -1) return top.value;
				if (bot && valid.indexOf(bot.value) !== -1) return bot.value;
				return null;
			}

			// ── Live Preview Logic ──────────────────────────────────────
			function updatePreview() {
				var action = getActiveAction();
				if (!action) return;

				var val = inp.value;
				if (!val && action !== 'replace_in_title') {
					preview.textContent = '';
					return;
				}

				if (action === 'append_to_title') {
					var full = spaceCb.checked ? ' ' + val : val;
					preview.textContent = 'Preview: "Title' + full + '"';
				} else if (action === 'prepend_to_title') {
					var full = spaceCb.checked ? val + ' ' : val;
					preview.textContent = 'Preview: "' + full + 'Title"';
				} else if (action === 'remove_from_title') {
					preview.textContent = 'Preview: "Title ' + val + '" → "Title"';
				} else if (action === 'remove_prefix_title') {
					preview.textContent = 'Preview: "' + val + ' Title" → "Title"';
				} else if (action === 'replace_in_title') {
					var findVal = inp.value;
					var repVal  = replaceInp.value;
					if (!findVal) { preview.textContent = ''; return; }
					preview.textContent = 'Preview: "Event ' + findVal + '" → "Event ' + repVal + '"';
				}
			}

			inp.addEventListener('input', updatePreview);
			replaceInp.addEventListener('input', updatePreview);
			spaceCb.addEventListener('change', updatePreview);

			// ── Sync UI with Selected Action ─────────────────────────────
			function syncPanel() {
				var action = getActiveAction();
				if (!action) {
					panel.classList.remove('bat-visible', 'bat-danger', 'bat-replace');
					return;
				}

				panel.classList.add('bat-visible');
				panel.classList.remove('bat-danger', 'bat-replace');
				replaceWrap.style.display = 'none';
				spaceWrap.style.display   = 'flex';
				inp.name = 'bat_string';

				if (action === 'append_to_title') {
					label.textContent      = 'Text to append (end):';
					hint.textContent       = 'Added to the end of every selected title.';
					inp.placeholder        = 'e.g. 2027';
					spaceLabel.textContent = 'Add space before';
				} else if (action === 'prepend_to_title') {
					label.textContent      = 'Text to prepend (start):';
					hint.textContent       = 'Added to the start of every selected title.';
					inp.placeholder        = 'e.g. Official: ';
					spaceLabel.textContent = 'Add space after';
				} else if (action === 'remove_from_title') {
					panel.classList.add('bat-danger');
					label.textContent      = 'Text to remove (from end):';
					hint.textContent       = 'Stripped from the end of selected titles. Non-matching titles skipped.';
					inp.placeholder        = 'e.g. 2027';
					spaceLabel.textContent = 'Remove preceding space';
				} else if (action === 'remove_prefix_title') {
					panel.classList.add('bat-danger');
					label.textContent      = 'Text to remove (from start):';
					hint.textContent       = 'Stripped from the start of selected titles. Non-matching titles skipped.';
					inp.placeholder        = 'e.g. Official: ';
					spaceLabel.textContent = 'Remove trailing space';
				} else if (action === 'replace_in_title') {
					panel.classList.add('bat-replace');
					label.textContent         = 'Find text:';
					hint.textContent          = 'Replaces all occurrences within selected titles.';
					inp.placeholder           = 'e.g. 2026';
					inp.name                  = 'bat_find';
					replaceWrap.style.display = 'inline-flex';
					spaceWrap.style.display   = 'none';
				}

				updatePreview();
			}

			['bulk-action-selector-top', 'bulk-action-selector-bottom'].forEach(function (id) {
				var sel = document.getElementById(id);
				if (sel) sel.addEventListener('change', syncPanel);
			});

			// ── Close / Reset ──────────────────────────────────────────
			closeBtn.addEventListener('click', function () {
				['bulk-action-selector-top', 'bulk-action-selector-bottom'].forEach(function (id) {
					var sel = document.getElementById(id);
					if (sel) sel.value = '-1';
				});
				panel.classList.remove('bat-visible');
			});

			// ── Submit Validation ──────────────────────────────────────
			['bulk-action-submit', 'bulk-action-submit-2'].forEach(function (btnId) {
				var btn = document.getElementById(btnId);
				if (!btn) return;
				btn.addEventListener('click', function (e) {
					var action = getActiveAction();
					if (!action) return;

					if (inp && inp.value.trim() === '') {
						e.preventDefault();
						inp.style.outline = '2px solid #dc2626';
						inp.focus();
						inp.placeholder   = 'Please enter text first';
					}
				});
			});

			inp.addEventListener('input', function () { inp.style.outline = ''; });
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

			$verbs = array(
				'append_to_title'     => array( 'Appended', 'to' ),
				'prepend_to_title'    => array( 'Prepended', 'to' ),
				'remove_from_title'   => array( 'Removed', 'from end of' ),
				'remove_prefix_title' => array( 'Removed', 'from start of' ),
			);

			$verb_info = isset( $verbs[ $action ] ) ? $verbs[ $action ] : array( 'Modified', 'on' );

			$msg = sprintf(
				'%s <strong>%s</strong> %s <strong>%d</strong> title%s.',
				esc_html( $verb_info[0] ),
				esc_html( $string ),
				esc_html( $verb_info[1] ),
				$updated,
				1 === $updated ? '' : 's'
			);
		}

		if ( $skipped > 0 ) {
			$msg .= sprintf(
				/* translators: %d: Skipped count */
				esc_html__( ' (%d title(s) skipped — did not match criteria).', 'pubcrawls-addon' ),
				$skipped
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
