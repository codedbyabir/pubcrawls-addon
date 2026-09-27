<?php
/**
 * PubCrawls Quick Edit Featured Image Helper
 *
 * Adds a Featured Image column to WordPress admin post lists and integrates
 * full image selection/removal controls directly into WordPress Quick Edit.
 *
 * @package Pubcrawl_Addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Pubcrawls_Quick_Featured_Image
 */
class Pubcrawls_Quick_Featured_Image {

	/**
	 * Single instance.
	 *
	 * @var Pubcrawls_Quick_Featured_Image|null
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
	 * Initialize helper feature.
	 */
	public static function init(): void {
		self::get_instance();
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_init', array( $this, 'register_column_hooks' ) );
		add_action( 'quick_edit_custom_box', array( $this, 'render_quick_edit_box' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_footer-edit.php', array( $this, 'render_quick_edit_script' ) );
		add_action( 'save_post', array( $this, 'save_quick_edit_thumbnail' ) );
	}

	/**
	 * Get all post types that support featured images.
	 *
	 * @return array List of supported post type slugs.
	 */
	public static function get_supported_post_types(): array {
		$types = array();

		// Fetch all registered post types
		$all_types = get_post_types( array(), 'names' );
		foreach ( $all_types as $pt ) {
			if ( post_type_supports( $pt, 'thumbnail' ) ) {
				$types[] = $pt;
			}
		}

		/**
		 * Filter supported post types for Quick Edit Featured Image.
		 *
		 * @param array $types List of post types supporting featured images.
		 */
		return apply_filters( 'pubcrawls_qefi_post_types', array_unique( $types ) );
	}

	/**
	 * Hook columns and custom column renders for all supported post types.
	 */
	public function register_column_hooks(): void {
		$types = self::get_supported_post_types();

		foreach ( $types as $pt ) {
			if ( 'page' === $pt ) {
				add_filter( 'manage_pages_columns', array( $this, 'add_thumbnail_column' ) );
				add_action( 'manage_pages_custom_column', array( $this, 'render_thumbnail_column' ), 10, 2 );
			} else {
				add_filter( "manage_{$pt}_posts_columns", array( $this, 'add_thumbnail_column' ) );
				add_action( "manage_{$pt}_posts_custom_column", array( $this, 'render_thumbnail_column' ), 10, 2 );
			}
		}
	}

	/**
	 * Insert the Featured Image column right after the checkbox (cb) column.
	 *
	 * @param array $columns Existing table columns.
	 * @return array Modified columns.
	 */
	public function add_thumbnail_column( array $columns ): array {
		$new_columns = array();

		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;
			if ( 'cb' === $key ) {
				$new_columns['quick_featured_image'] = esc_html__( 'Image', 'pubcrawls-addon' );
			}
		}

		// Fallback if 'cb' column didn't exist
		if ( ! isset( $new_columns['quick_featured_image'] ) ) {
			$new_columns['quick_featured_image'] = esc_html__( 'Image', 'pubcrawls-addon' );
		}

		return $new_columns;
	}

	/**
	 * Render the thumbnail content in the post list table.
	 *
	 * @param string $column_name Current column name.
	 * @param int    $post_id     Current post ID.
	 */
	public function render_thumbnail_column( string $column_name, int $post_id ): void {
		if ( 'quick_featured_image' !== $column_name ) {
			return;
		}

		$thumbnail_id = get_post_thumbnail_id( $post_id );

		echo '<div class="qefi-list-thumbnail" data-thumbnail-id="' . esc_attr( (string) $thumbnail_id ) . '" data-post-id="' . esc_attr( (string) $post_id ) . '">';

		if ( $thumbnail_id ) {
			$full_url = wp_get_attachment_image_url( $thumbnail_id, 'full' );
			echo '<a href="' . esc_url( $full_url ? $full_url : '#' ) . '" class="qefi-thumb-link" target="_blank" title="' . esc_attr__( 'View full image', 'pubcrawls-addon' ) . '">';
			echo wp_get_attachment_image(
				$thumbnail_id,
				array( 54, 54 ),
				false,
				array(
					'style' => 'width:54px;height:54px;object-fit:cover;border-radius:4px;display:block;border:1px solid #dcdcde;box-shadow:0 1px 2px rgba(0,0,0,0.05);',
					'class' => 'qefi-thumb-img',
				)
			);
			echo '</a>';
		} else {
			echo '<span class="qefi-no-image" style="display:inline-block;width:54px;height:54px;line-height:54px;background:#f6f7f7;border:1px dashed #cbd5e1;border-radius:4px;color:#94a3b8;font-size:11px;text-align:center;">' . esc_html__( 'No image', 'pubcrawls-addon' ) . '</span>';
		}

		echo '</div>';
	}

	/**
	 * Add the Featured Image controls inside the Quick Edit inline form.
	 *
	 * @param string $column_name Current column name.
	 * @param string $post_type   Current post type.
	 */
	public function render_quick_edit_box( string $column_name, string $post_type ): void {
		if ( 'quick_featured_image' !== $column_name ) {
			return;
		}

		if ( ! post_type_supports( $post_type, 'thumbnail' ) ) {
			return;
		}

		wp_nonce_field( 'qefi_save_featured_image', 'qefi_nonce' );
		?>
		<fieldset class="inline-edit-col-right qefi-fieldset">
			<div class="inline-edit-col">
				<div class="inline-edit-group">
					<label>
						<span class="title" style="font-weight:600;display:inline-block;margin-bottom:4px;"><?php esc_html_e( 'Featured Image', 'pubcrawls-addon' ); ?></span>
					</label>
					<div class="qefi-wrapper" style="margin-top:4px;">
						<input type="hidden" name="qefi_thumbnail_id" class="qefi-thumbnail-id" value="" />
						
						<div class="qefi-preview" style="width:90px;height:90px;border:1px solid #dcdcde;background:#f8fafc;display:flex;align-items:center;justify-content:center;margin:6px 0 8px;border-radius:6px;overflow:hidden;box-shadow:inset 0 1px 2px rgba(0,0,0,0.05);">
							<span style="color:#64748b;font-size:12px;"><?php esc_html_e( 'No image', 'pubcrawls-addon' ); ?></span>
						</div>

						<div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
							<button type="button" class="button button-small qefi-select-image">
								<span class="dashicons dashicons-format-image" style="font-size:14px;width:14px;height:14px;vertical-align:middle;margin-top:-2px;"></span>
								<?php esc_html_e( 'Select / Change Image', 'pubcrawls-addon' ); ?>
							</button>

							<button type="button" class="button button-small qefi-remove-image" style="color:#b32d2e;">
								<span class="dashicons dashicons-trash" style="font-size:14px;width:14px;height:14px;vertical-align:middle;margin-top:-2px;"></span>
								<?php esc_html_e( 'Remove', 'pubcrawls-addon' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Enqueue WordPress Media Library on post list pages.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( 'edit.php' !== $hook_suffix ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen && in_array( $screen->post_type, self::get_supported_post_types(), true ) ) {
			wp_enqueue_media();
		}
	}

	/**
	 * Client-side JavaScript handling Quick Edit inline synchronization and wp.media picker.
	 */
	public function render_quick_edit_script(): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, self::get_supported_post_types(), true ) ) {
			return;
		}
		?>
		<style>
			.manage-column.column-quick_featured_image {
				width: 68px;
				text-align: center;
			}
			.qefi-list-thumbnail {
				display: flex;
				align-items: center;
				justify-content: center;
			}
			.qefi-thumb-img:hover {
				transform: scale(1.05);
			}
		</style>

		<script>
		jQuery(function ($) {
			var qefiMediaFrame;

			if (typeof inlineEditPost === 'undefined') {
				return;
			}

			// Preserve native WordPress Quick Edit function
			var originalInlineEdit = inlineEditPost.edit;

			inlineEditPost.edit = function (id) {
				originalInlineEdit.apply(this, arguments);

				var postId = 0;
				if (typeof id === 'object') {
					postId = parseInt(this.getId(id), 10);
				} else {
					postId = parseInt(id, 10);
				}

				if (!postId) {
					return;
				}

				var $postRow = $('#post-' + postId);
				var $editRow = $('#edit-' + postId);
				var $thumbContainer = $postRow.find('.qefi-list-thumbnail');
				var thumbnailId = parseInt($thumbContainer.attr('data-thumbnail-id'), 10) || 0;

				var $input   = $editRow.find('.qefi-thumbnail-id');
				var $preview = $editRow.find('.qefi-preview');

				$input.val(thumbnailId);

				var $currentImage = $thumbContainer.find('img');
				if ($currentImage.length && thumbnailId > 0) {
					$preview.html(
						$('<img>', {
							src: $currentImage.attr('src'),
							css: {
								width: '100%',
								height: '100%',
								objectFit: 'cover'
							}
						})
					);
				} else {
					$preview.html('<span style="color:#64748b;font-size:12px;">No image</span>');
				}
			};

			// Select / Change Featured Image via wp.media
			$(document).on('click', '.qefi-select-image', function (e) {
				e.preventDefault();

				var $button  = $(this);
				var $wrapper = $button.closest('.qefi-wrapper');
				var $input   = $wrapper.find('.qefi-thumbnail-id');
				var $preview = $wrapper.find('.qefi-preview');

				qefiMediaFrame = wp.media({
					title: 'Select Featured Image',
					button: {
						text: 'Use as Featured Image'
					},
					library: {
						type: 'image'
					},
					multiple: false
				});

				qefiMediaFrame.on('select', function () {
					var attachment = qefiMediaFrame.state().get('selection').first().toJSON();
					$input.val(attachment.id);

					var imageUrl = attachment.url;
					if (attachment.sizes && attachment.sizes.thumbnail) {
						imageUrl = attachment.sizes.thumbnail.url;
					} else if (attachment.sizes && attachment.sizes.medium) {
						imageUrl = attachment.sizes.medium.url;
					}

					$preview.html(
						$('<img>', {
							src: imageUrl,
							css: {
								width: '100%',
								height: '100%',
								objectFit: 'cover'
							}
						})
					);
				});

				qefiMediaFrame.open();
			});

			// Remove Featured Image
			$(document).on('click', '.qefi-remove-image', function (e) {
				e.preventDefault();
				var $wrapper = $(this).closest('.qefi-wrapper');
				$wrapper.find('.qefi-thumbnail-id').val('0');
				$wrapper.find('.qefi-preview').html('<span style="color:#64748b;font-size:12px;">No image</span>');
			});
		});
		</script>
		<?php
	}

	/**
	 * Save the Featured Image when Quick Edit is submitted.
	 *
	 * @param int $post_id The post ID being updated.
	 */
	public function save_quick_edit_thumbnail( int $post_id ): void {
		// Ignore autosaves
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Only process when Quick Edit thumbnail input is present
		if ( ! isset( $_POST['qefi_thumbnail_id'] ) ) {
			return;
		}

		// Verify nonce
		if ( ! isset( $_POST['qefi_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qefi_nonce'] ) ), 'qefi_save_featured_image' ) ) {
			return;
		}

		// Check permission
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$thumbnail_id = absint( $_POST['qefi_thumbnail_id'] );

		if ( $thumbnail_id > 0 ) {
			if ( 'attachment' === get_post_type( $thumbnail_id ) ) {
				set_post_thumbnail( $post_id, $thumbnail_id );
			}
		} else {
			delete_post_thumbnail( $post_id );
		}
	}
}
