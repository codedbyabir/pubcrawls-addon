<?php
/**
 * FAQ & SEO Importer Admin Page View
 *
 * @package Pubcrawl_Addon
 *
 * @var string $post_type Target post type (e.g. 'city', 'tribe_events').
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_events  = ( 'tribe_events' === $post_type );
$pt_obj     = get_post_type_object( $post_type );
$pt_name    = $pt_obj ? $pt_obj->labels->singular_name : ucfirst( str_replace( '_', ' ', $post_type ) );
$page_title = sprintf( /* translators: %s: Post type singular label */ esc_html__( '%s FAQ & SEO Import', 'pubcrawls-addon' ), $pt_name );
$nonce_key  = 'fbi_run_' . $post_type;

$log      = array();
$json_raw = '';

// ── Process Submitted Batch ──────────────────────────────────────────────────
// phpcs:ignore WordPress.Security.NonceVerification.Missing
if ( isset( $_POST['fbi_json'] ) && check_admin_referer( $nonce_key ) ) {

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$json_raw  = wp_unslash( $_POST['fbi_json'] );
	$dry       = ! empty( $_POST['fbi_dry'] );
	$overwrite = ! empty( $_POST['fbi_overwrite'] );
	$offset    = isset( $_POST['fbi_offset'] ) ? (int) $_POST['fbi_offset'] : 0;
	$limit     = Pubcrawls_FAQ_SEO_Importer::BATCH_LIMIT;

	$data = json_decode( $json_raw, true );

	if ( ! is_array( $data ) ) {
		$log[] = array( 'err', 'Invalid JSON: ' . json_last_error_msg() );
	} else {
		$mode    = Pubcrawls_FAQ_SEO_Importer::detect_mode( $data );
		$total   = count( $data );
		$batch   = array_slice( $data, $offset, $limit, true );
		$done    = 0;
		$skipped = 0;

		foreach ( $batch as $key => $entry ) {
			$post = Pubcrawls_FAQ_SEO_Importer::resolve_post( (string) $key, $post_type );

			if ( ! $post ) {
				$log[] = array( 'warn', sprintf( 'No `%s` post found for key: %s', esc_html( $post_type ), esc_html( (string) $key ) ) );
				$skipped++;
				continue;
			}

			$post_id = $post->ID;
			$parts   = array();

			// ── FAQs ──────────────────────────────────────────────────────
			if ( $mode['has_faqs'] ) {
				$raw_faqs = isset( $entry['faqs'] ) ? $entry['faqs'] : $entry;

				if ( is_array( $raw_faqs ) && isset( $raw_faqs[0] ) ) {
					$existing_faq = (int) get_post_meta( $post_id, 'faqs', true );

					if ( $existing_faq > 0 && ! $overwrite ) {
						$parts[] = "FAQs skipped ($existing_faq existing)";
					} else {
						$clean = array();
						foreach ( $raw_faqs as $row ) {
							$q = isset( $row['question'] ) ? trim( $row['question'] ) : '';
							if ( '' === $q ) {
								continue;
							}
							$clean[] = array(
								'question' => sanitize_text_field( $q ),
								'answer'   => isset( $row['answer'] ) ? wp_kses_post( $row['answer'] ) : '',
							);
						}

						if ( ! empty( $clean ) ) {
							if ( ! $dry ) {
								Pubcrawls_FAQ_SEO_Importer::write_faqs( $post_id, $clean, $existing_faq );
							}
							$parts[] = count( $clean ) . ' FAQ(s)' . ( $existing_faq ? " (replaced $existing_faq)" : '' );
						}
					}
				}
			}

			// ── SEO ───────────────────────────────────────────────────────
			if ( $mode['has_seo'] ) {
				$seo_title = isset( $entry['seo_title'] ) ? trim( $entry['seo_title'] ) : '';
				$seo_desc  = isset( $entry['seo_desc'] ) ? trim( $entry['seo_desc'] ) : '';

				if ( '' !== $seo_title || '' !== $seo_desc ) {
					if ( ! $dry ) {
						$written = Pubcrawls_FAQ_SEO_Importer::write_seo( $post_id, $seo_title, $seo_desc, $overwrite );
					} else {
						$written = array_filter(
							array(
								$seo_title ? 'title' : '',
								$seo_desc  ? 'desc'  : '',
							)
						);
					}

					if ( ! empty( $written ) ) {
						$parts[] = 'SEO: ' . implode( ' + ', $written );
					} else {
						$parts[] = 'SEO: skipped (existing, overwrite off)';
					}
				}
			}

			if ( empty( $parts ) ) {
				$log[] = array( 'warn', "#{$post_id} {$post->post_name} — nothing to write" );
				$skipped++;
				continue;
			}

			if ( ! $dry ) {
				clean_post_cache( $post_id );
			}

			$log[] = array(
				'ok',
				sprintf(
					'%s#%d %s — %s',
					$dry ? '[dry] ' : '',
					$post_id,
					$post->post_name,
					implode( ' | ', $parts )
				),
			);
			$done++;
		}

		$next  = $offset + $limit;
		$log[] = array(
			'done',
			sprintf(
				'Batch %d–%d of %d | Updated: %d | Skipped: %d',
				$offset + 1,
				min( $next, $total ),
				$total,
				$done,
				$skipped
			),
		);

		if ( $next < $total ) {
			$log[] = array( 'next', $next );
		}
	}
}

// ── Stats (direct DB query) ──────────────────────────────────────────────────
$stats       = Pubcrawls_FAQ_SEO_Importer::get_stats( $post_type );
$total_posts = $stats['total'];
$with_faqs   = $stats['with_faqs'];
$with_seo    = $stats['with_seo'];

$supported_types = Pubcrawls_FAQ_SEO_Importer::get_supported_post_types();
?>

<div class="wrap pubcrawls-dashboard-wrap">

	<!-- Header Banner -->
	<header class="pubcrawls-header">
		<div class="pubcrawls-header__logo">
			<span class="dashicons dashicons-upload"></span>
			<div>
				<h1><?php echo esc_html( $page_title ); ?></h1>
				<span class="pubcrawls-version"><?php esc_html_e( 'Bulk Helper Tool', 'pubcrawls-addon' ); ?></span>
			</div>
		</div>
		<div class="pubcrawls-header__meta">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=pubcrawls-addon&tab=helpers' ) ); ?>" class="button button-secondary">
				<span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Back to Helpers', 'pubcrawls-addon' ); ?>
			</a>
		</div>
	</header>

	<!-- Post Type Switcher Nav -->
	<nav class="nav-tab-wrapper pubcrawls-tabs" style="margin-bottom: 20px;">
		<?php foreach ( $supported_types as $s_slug => $s_label ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $s_slug . '-faq-import' ) ); ?>"
			   class="nav-tab <?php echo $s_slug === $post_type ? 'nav-tab-active' : ''; ?>">
				<span class="dashicons <?php echo 'city' === $s_slug ? 'dashicons-location-alt' : 'dashicons-calendar-alt'; ?>"></span>
				<?php echo esc_html( $s_label ); ?> <?php esc_html_e( 'Import', 'pubcrawls-addon' ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<!-- Stats Card -->
	<div class="pubcrawls-card" style="margin-bottom: 24px;">
		<div class="pubcrawls-card__header">
			<h2><span class="dashicons dashicons-chart-bar"></span> <?php printf( /* translators: %s: Post type */ esc_html__( '%s Statistics & Status', 'pubcrawls-addon' ), esc_html( $pt_name ) ); ?></h2>
			<span class="pubcrawls-card__subtitle"><?php esc_html_e( 'Live database counts for posts, FAQs repeater fields, and SEO metadata.', 'pubcrawls-addon' ); ?></span>
		</div>
		<div class="pubcrawls-card__body">
			<div class="pubcrawls-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
				<div class="pubcrawls-stat-card">
					<div class="pubcrawls-stat-card__icon" style="background:#eff6ff;color:#2563eb;">
						<span class="dashicons dashicons-admin-post"></span>
					</div>
					<div class="pubcrawls-stat-card__data">
						<span class="pubcrawls-stat-card__value"><?php echo esc_html( $total_posts ); ?></span>
						<span class="pubcrawls-stat-card__label"><?php printf( /* translators: %s: Post type */ esc_html__( 'Total %s Posts', 'pubcrawls-addon' ), esc_html( $pt_name ) ); ?></span>
					</div>
				</div>

				<div class="pubcrawls-stat-card">
					<div class="pubcrawls-stat-card__icon" style="background:#f0fdf4;color:#16a34a;">
						<span class="dashicons dashicons-editor-help"></span>
					</div>
					<div class="pubcrawls-stat-card__data">
						<span class="pubcrawls-stat-card__value"><?php echo esc_html( $with_faqs ); ?></span>
						<span class="pubcrawls-stat-card__label">
							<?php esc_html_e( 'Have FAQs Repeater', 'pubcrawls-addon' ); ?>
							<span style="color:#64748b;font-weight:normal;">(<?php echo esc_html( max( 0, $total_posts - $with_faqs ) ); ?> empty)</span>
						</span>
					</div>
				</div>

				<div class="pubcrawls-stat-card">
					<div class="pubcrawls-stat-card__icon" style="background:#fefce8;color:#ca8a04;">
						<span class="dashicons dashicons-search"></span>
					</div>
					<div class="pubcrawls-stat-card__data">
						<span class="pubcrawls-stat-card__value"><?php echo esc_html( $with_seo ); ?></span>
						<span class="pubcrawls-stat-card__label">
							<?php esc_html_e( 'Have SEO Meta', 'pubcrawls-addon' ); ?>
							<span style="color:#64748b;font-weight:normal;">(<?php echo esc_html( max( 0, $total_posts - $with_seo ) ); ?> empty)</span>
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Execution Log -->
	<?php if ( ! empty( $log ) ) : ?>
		<div class="pubcrawls-card" style="margin-bottom: 24px;">
			<div class="pubcrawls-card__header">
				<h2><span class="dashicons dashicons-terminal"></span> <?php esc_html_e( 'Import Execution Console', 'pubcrawls-addon' ); ?></h2>
			</div>
			<div class="pubcrawls-card__body">
				<div style="background:#0f172a;color:#f8fafc;border-radius:8px;padding:16px;font:13px/1.7 'SFMono-Regular', Consolas, Menlo, monospace;max-height:420px;overflow:auto;box-shadow:inset 0 2px 4px rgba(0,0,0,0.5);">
					<?php
					$next_offset = null;
					foreach ( $log as $line ) {
						if ( 'next' === $line[0] ) {
							$next_offset = $line[1];
							continue;
						}
						$color_map = array(
							'ok'   => '#4ade80',
							'warn' => '#fde047',
							'err'  => '#f87171',
							'skip' => '#94a3b8',
							'done' => '#60a5fa',
						);
						$color = isset( $color_map[ $line[0] ] ) ? $color_map[ $line[0] ] : '#ffffff';
						$bold  = in_array( $line[0], array( 'done', 'err' ), true ) ? 'font-weight:700;' : '';
						printf(
							'<div style="color:%s;%s margin-bottom:2px;">%s</div>',
							esc_attr( $color ),
							esc_attr( $bold ),
							esc_html( $line[1] )
						);
					}
					?>
				</div>

				<?php if ( null !== $next_offset ) : ?>
					<form method="post" style="margin-top:16px;">
						<?php wp_nonce_field( $nonce_key ); ?>
						<input type="hidden" name="fbi_json" value="<?php echo esc_attr( $json_raw ); ?>">
						<input type="hidden" name="fbi_offset" value="<?php echo (int) $next_offset; ?>">
						<input type="hidden" name="fbi_dry" value="<?php echo ! empty( $_POST['fbi_dry'] ) ? '1' : ''; ?>">
						<input type="hidden" name="fbi_overwrite" value="<?php echo ! empty( $_POST['fbi_overwrite'] ) ? '1' : ''; ?>">
						<?php submit_button( sprintf( /* translators: %d: Batch count */ esc_html__( 'Continue with next %d posts →', 'pubcrawls-addon' ), Pubcrawls_FAQ_SEO_Importer::BATCH_LIMIT ), 'primary large' ); ?>
					</form>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<!-- JSON Format Documentation Accordion -->
	<details style="margin-bottom:20px;background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:16px 20px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
		<summary style="cursor:pointer;font-weight:600;font-size:14px;color:#1e293b;outline:none;">
			<span class="dashicons dashicons-info" style="vertical-align:middle;color:#2563eb;margin-right:4px;"></span>
			<?php esc_html_e( 'JSON Format Reference & Schema (Click to expand)', 'pubcrawls-addon' ); ?>
		</summary>
		<div style="margin-top:14px;font-size:13px;line-height:1.6;color:#334155;">

			<p><strong>1. FAQs Only</strong> (flat list per slug or ID):</p>
			<pre style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:12px;overflow:auto;font-family:monospace;font-size:12px;">{
  "kent-oh": [
    { "question": "Is Kent walkable?", "answer": "Very." }
  ]
}</pre>

			<p style="margin-top:14px;"><strong>2. SEO Fields Only</strong> (Title and Meta Description):</p>
			<pre style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:12px;overflow:auto;font-family:monospace;font-size:12px;">{
  "kent-oh": {
    "seo_title": "Bar Crawls in Kent, OH | PubCrawls.com",
    "seo_desc":  "Find and book the best bar crawls in Kent, Ohio. Halloween, St. Patrick's Day, and more."
  }
}</pre>

			<p style="margin-top:14px;"><strong>3. FAQs + SEO Together</strong> (Combined object):</p>
			<pre style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:12px;overflow:auto;font-family:monospace;font-size:12px;">{
  "kent-oh": {
    "seo_title": "Bar Crawls in Kent, OH | PubCrawls.com",
    "seo_desc":  "Find and book the best bar crawls in Kent, Ohio.",
    "faqs": [
      { "question": "Is Kent walkable?", "answer": "Very." },
      { "question": "When is the next crawl?", "answer": "Check our events schedule." }
    ]
  }
}</pre>

			<ul style="margin:12px 0 0 18px;list-style:disc;color:#64748b;font-size:12px;">
				<li>Keys can be <strong>post slugs</strong> (e.g. <code>"kent-oh"</code>) or <strong>numeric post IDs</strong> (e.g. <code>"33216"</code>).</li>
				<li><code>seo_title</code> optimal length is ~60 characters; <code>seo_desc</code> optimal length is ~155 characters.</li>
				<li>Yoast snippet variables such as <code>%%title%%</code> and <code>%%sitename%%</code> are supported.</li>
				<li>Compatible with <strong>Yoast SEO</strong>, <strong>Rank Math</strong>, and <strong>All In One SEO</strong> automatically.</li>
			</ul>
		</div>
	</details>

	<!-- Input Form Card -->
	<div class="pubcrawls-card">
		<div class="pubcrawls-card__header">
			<h2><span class="dashicons dashicons-database-import"></span> <?php printf( /* translators: %s: Post type label */ esc_html__( 'Import Payload for %s', 'pubcrawls-addon' ), esc_html( $pt_name ) ); ?></h2>
			<span class="pubcrawls-card__subtitle"><?php esc_html_e( 'Paste your JSON payload below. Supports dry-run verification before database commit.', 'pubcrawls-addon' ); ?></span>
		</div>
		<div class="pubcrawls-card__body">
			<form method="post">
				<?php wp_nonce_field( $nonce_key ); ?>

				<div style="margin-bottom:16px;">
					<label for="fbi_json" style="display:block;font-weight:600;margin-bottom:6px;color:#0f172a;">
						<?php esc_html_e( 'JSON Payload:', 'pubcrawls-addon' ); ?>
					</label>
					<textarea
						id="fbi_json"
						name="fbi_json"
						rows="18"
						style="width:100%;font:13px/1.5 monospace;background:#fafafa;border:1px solid #cbd5e1;border-radius:6px;padding:12px;"
						placeholder='{ "<?php echo $is_events ? 'albany-oktoberfest-bar-crawl' : 'kent-oh'; ?>": { "seo_title": "...", "seo_desc": "...", "faqs": [ {"question":"...","answer":"..."} ] } }'
						required
					><?php echo esc_textarea( $json_raw ); ?></textarea>
				</div>

				<div style="margin-bottom:10px;">
					<label style="cursor:pointer;display:inline-flex;align-items:center;gap:8px;font-size:13px;color:#1e293b;">
						<input type="checkbox" name="fbi_dry" value="1" <?php checked( true, empty( $_POST ) || ! empty( $_POST['fbi_dry'] ) ); ?>>
						<strong><?php esc_html_e( 'Dry run', 'pubcrawls-addon' ); ?></strong> — <?php esc_html_e( 'Preview parsed changes without writing to database', 'pubcrawls-addon' ); ?>
					</label>
				</div>

				<div style="margin-bottom:20px;">
					<label style="cursor:pointer;display:inline-flex;align-items:center;gap:8px;font-size:13px;color:#1e293b;">
						<input type="checkbox" name="fbi_overwrite" value="1" <?php checked( ! empty( $_POST['fbi_overwrite'] ) ); ?>>
						<strong><?php esc_html_e( 'Overwrite', 'pubcrawls-addon' ); ?></strong> — <?php esc_html_e( 'Replace existing FAQs repeater and SEO fields (leave unticked to only fill empty posts)', 'pubcrawls-addon' ); ?>
					</label>
				</div>

				<div class="pubcrawls-card__footer" style="padding:0;background:transparent;border-top:none;">
					<?php submit_button( esc_html__( 'Run Import Batch', 'pubcrawls-addon' ), 'primary large', 'submit', false ); ?>
					<span style="margin-left:14px;color:#64748b;font-size:12px;">
						<?php printf( /* translators: %d: Batch limit */ esc_html__( 'Processes %d posts per batch to prevent server timeouts.', 'pubcrawls-addon' ), (int) Pubcrawls_FAQ_SEO_Importer::BATCH_LIMIT ); ?>
					</span>
				</div>
			</form>
		</div>
	</div>

</div>
