<?php

/**
 * Shortcodes — Pub Crawls Plugin
 *
 * Registered shortcodes:
 *  [post_content_dynamic]   — renders current post content through the_content filters
 *  [event_booking_deadline] — human-readable booking deadline from ACF field 'booking_deadline'
 *  [pc_cities]              — city grid with upcoming event counts from ACF field 'select_event_category'
 *
 * Shortcode options for [pc_cities]:
 *  limit  — number of cities to show (default: 5, use -1 for all)
 *
 * Example: [pc_cities limit="10"]
 */

if (! defined('ABSPATH')) exit;



/**
 * 5. Event Date
 * Shortcode: [event_date]
 * Reads ACF field "eventdate" (stored as Ymd) and outputs M j, Y format.
 * Optionally pass a post ID: [event_date id="123"]
 */
if (! function_exists('event_date_shortcode')) :
    function event_date_shortcode($atts)
    {
        $atts = shortcode_atts(
            ['id' => get_the_ID()],
            $atts,
            'event_date'
        );

        $raw = get_field('eventdate', (int) $atts['id']);

        if (! $raw) {
            return '';
        }

        $date = DateTime::createFromFormat('Ymd', $raw);

        if (! $date) {
            return esc_html($raw); // fallback: return as-is if parsing fails
        }

        return $date->format('M j, Y'); // e.g. "Apr 5, 2025"
    }
endif; // function_exists 'event_date_shortcode'
if (! shortcode_exists('event_date')) {
    add_shortcode('event_date', 'event_date_shortcode');
}






// Pubcrawls Custom Social Share


/*
|--------------------------------------------------------------------------
| Facebook Share URL
|--------------------------------------------------------------------------
*/
if (! function_exists('pubcrawls_facebook_share_url_shortcode')) :
    function pubcrawls_facebook_share_url_shortcode()
    {

        return 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode(get_permalink());
    }
endif; // function_exists 'pubcrawls_facebook_share_url_shortcode'
if (! shortcode_exists('facebook_share_url')) {
    add_shortcode('facebook_share_url', 'pubcrawls_facebook_share_url_shortcode');
}


/*
|--------------------------------------------------------------------------
| X / Twitter Share URL
|--------------------------------------------------------------------------
*/
if (! function_exists('pubcrawls_twitter_share_url_shortcode')) :
    function pubcrawls_twitter_share_url_shortcode()
    {

        return 'https://twitter.com/intent/tweet?url=' . urlencode(get_permalink()) . '&text=' . urlencode(get_the_title());
    }
endif; // function_exists 'pubcrawls_twitter_share_url_shortcode'
if (! shortcode_exists('twitter_share_url')) {
    add_shortcode('twitter_share_url', 'pubcrawls_twitter_share_url_shortcode');
}


/*
|--------------------------------------------------------------------------
| LinkedIn Share URL
|--------------------------------------------------------------------------
*/
if (! function_exists('pubcrawls_linkedin_share_url_shortcode')) :
    function pubcrawls_linkedin_share_url_shortcode()
    {

        return 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode(get_permalink());
    }
endif; // function_exists 'pubcrawls_linkedin_share_url_shortcode'
if (! shortcode_exists('linkedin_share_url')) {
    add_shortcode('linkedin_share_url', 'pubcrawls_linkedin_share_url_shortcode');
}


/*
|--------------------------------------------------------------------------
| Current Page URL
|--------------------------------------------------------------------------
*/
if (! function_exists('pubcrawls_current_url_shortcode')) :
    function pubcrawls_current_url_shortcode()
    {

        return esc_url(get_permalink());
    }
endif; // function_exists 'pubcrawls_current_url_shortcode'
if (! shortcode_exists('current_url')) {
    add_shortcode('current_url', 'pubcrawls_current_url_shortcode');
}



/**
 * Pub Crawl Agenda Shortcode
 * Usage: [pubcrawl_agenda]
 *
 * ACF Repeater: agenda_steps
 * Sub-fields:
 *   - time        (Text)
 *   - title       (Text)
 *   - description (Textarea)
 */

if (! defined('ABSPATH')) exit;

if (! function_exists('pubcrawl_agenda_shortcode')) :
    function pubcrawl_agenda_shortcode($atts)
    {
        $atts    = shortcode_atts(['id' => get_the_ID()], $atts, 'event_agenda');
        $post_id = intval($atts['id']);

        if (! function_exists('have_rows') || ! have_rows('event_agenda', $post_id)) {
            return '';
        }

        ob_start(); ?>

        <div class="pubcrawl-agenda">
            <?php while (have_rows('event_agenda', $post_id)) : the_row(); ?>
                <div class="pubcrawl-step">
                    <div class="pubcrawl-time-badge">
                        <?php echo esc_html(get_sub_field('time')); ?>
                    </div>
                    <div class="pubcrawl-card">
                        <h3 class="pubcrawl-card-title"><?php echo esc_html(get_sub_field('title')); ?></h3>
                        <p class="pubcrawl-card-desc"><?php echo wp_kses_post(get_sub_field('description')); ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>



        <?php
        return ob_get_clean();
    }
endif; // function_exists 'pubcrawl_agenda_shortcode'
if (! shortcode_exists('pubcrawl_agenda')) {
    add_shortcode('pubcrawl_agenda', 'pubcrawl_agenda_shortcode');
}




/**
 * Pubcrawls Dynamic participating Venues
 * Usage: [pubcrawls_event_venues]
 */

if (! function_exists('pubcrawls_event_venues_shortcode')) :
    function pubcrawls_event_venues_shortcode()
    {

        $venues = get_field('event_venue');

        ob_start();

        if ($venues) : ?>

            <div class="pubcrawl-event-venues">

                <?php foreach ($venues as $venue) : ?>

                    <div class="pubcrawl-venue-item">

                        <div class="pubcrawl-venue-thumb">

                            <a href="<?php echo esc_url(get_permalink($venue->ID)); ?>">
                                <?php
                                if (has_post_thumbnail($venue->ID)) :

                                    echo wp_kses_post(get_the_post_thumbnail(
                                        $venue->ID,
                                        'large'
                                    ));

                                endif;
                                ?>
                            </a>

                        </div>

                        <div class="pubcrawl-venue-content">

                            <h3>
                                <a class="pubcrawl-venue-title" href="<?php echo esc_url(get_permalink($venue->ID)); ?>">
                                    <?php echo esc_html(get_the_title($venue->ID)); ?>
                                </a>
                            </h3>

                            <p class="pubcrawl-venue-location">
                                <svg xmlns="http://www.w3.org/2000/svg" height="14" width="10.5" viewBox="0 0 384 512"><!--!Font Awesome Free v5.15.4 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.-->
                                    <path fill="#e6b325" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z" />
                                </svg> <?php echo esc_html(get_field('address', $venue->ID)); ?>
                            </p>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif;

        return ob_get_clean();
    }
endif; // function_exists 'pubcrawls_event_venues_shortcode'

if (! shortcode_exists('pubcrawls_event_venues')) {
    add_shortcode('pubcrawls_event_venues', 'pubcrawls_event_venues_shortcode');
}





/**
 * Dynamic Event City Name
 * Usage: [pubcrawls_event_cities]
 */

if (! function_exists('pubcrawls_event_cities_shortcode')) :
    function pubcrawls_event_cities_shortcode()
    {

        $cities = get_field('event_city');

        ob_start();

        if ($cities) : ?>

            <div class="pubcrawl-event-cities">

                <?php foreach ($cities as $city) : ?>

                    <div class="pubcrawl-event-city">


                        <span class="pubcrawl-city-title">
                            <?php echo esc_html(get_the_title($city->ID)); ?>
                        </span>


                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif;

        return ob_get_clean();
    }
endif; // function_exists 'pubcrawls_event_cities_shortcode'

if (! shortcode_exists('pubcrawls_event_cities')) {
    add_shortcode('pubcrawls_event_cities', 'pubcrawls_event_cities_shortcode');
}




// ── Dynamic Page / Post FAQs Shortcode ─────────────────────────────────────────
if (! function_exists('pubcrawl_page_faqs_shortcode')) :
    function pubcrawl_page_faqs_shortcode()
    {

        ob_start(); // START BUFFER

        // Get correct object (post or term)
        if (is_category() || is_tax()) {
            $term   = get_queried_object();
            $source = $term;
        } else {
            $source = get_the_ID();
        }

        if (function_exists('have_rows') && have_rows('faqs', $source)) :
        ?>
            <div class="pubcrawls-faq-grid">

                <?php if (is_singular('post')) : ?>
                    <h2 class="pubcrawl-faq-title"><?php esc_html_e('Frequently Asked Questions (FAQs)', 'pubcrawls-addon'); ?></h2>
                <?php endif; ?>

                <?php while (have_rows('faqs', $source)) : the_row();
                    $qsn = get_sub_field('question');
                    $ans = get_sub_field('answer');
                ?>
                    <div class="pubcrawls-faq-item">
                        <h3 class="pubcrawls-faq-question"><?php echo esc_html($qsn); ?></h3>
                        <p class="pubcrawls-faq-answer"><?php echo esc_html($ans); ?></p>
                    </div>
                <?php endwhile; ?>

            </div>
<?php
        endif;

        return (string) ob_get_clean(); // RETURN instead of echo
    }
endif;

if (! shortcode_exists('pubcrawl_page_faqs')) {
    add_shortcode('pubcrawl_page_faqs', 'pubcrawl_page_faqs_shortcode');
}

/**
 * ── Author Box Shortcode ──────────────────────────────────────────────────────
 *
 * Usage:
 *   [pc_author_box]
 *   [pc_author_box user_id="1" pre_title="ABOUT THE WRITER:" show_social="yes"]
 */
if (! function_exists('pc_author_box_shortcode')) :
    function pc_author_box_shortcode($atts)
    {
        $atts = shortcode_atts([
            'user_id'           => 0,
            'pre_title'         => 'ABOUT THE AUTHOR:',
            'show_pre_title'    => 'yes',
            'follow_title'      => 'FOLLOW US:',
            'show_follow_title' => 'yes',
            'show_social'       => 'yes',
            'link_name'         => 'no',
            'class'             => '',
        ], $atts, 'pc_author_box');

        // Enqueue author box styles if registered
        if (wp_style_is('pc-author-box-css', 'registered') && ! wp_style_is('pc-author-box-css', 'enqueued')) {
            wp_enqueue_style('pc-author-box-css');
        }

        $user_id = (int) $atts['user_id'];
        if (! $user_id) {
            $post = get_post();
            if ($post && ! empty($post->post_author)) {
                $user_id = (int) $post->post_author;
            } elseif (is_author()) {
                $user_id = (int) get_queried_object_id();
            } else {
                $user_id = (int) get_current_user_id();
            }
        }
        if (! $user_id) {
            $user_id = 1;
        }

        $user = get_userdata($user_id);
        $name = $user ? $user->display_name : 'Author';

        // 1. Bio
        $bio = '';
        if (function_exists('get_field')) {
            $bio = (string) get_field('author_bio', 'user_' . $user_id);
        }
        if (empty($bio)) {
            $bio = (string) get_user_meta($user_id, 'author_bio', true);
        }
        if (empty($bio)) {
            $bio = (string) get_the_author_meta('description', $user_id);
        }

        // 2. Image
        $image_url = '';
        $raw_img   = null;
        if (function_exists('get_field')) {
            $raw_img = get_field('author_image', 'user_' . $user_id);
        }
        if (empty($raw_img)) {
            $raw_img = get_user_meta($user_id, 'author_image', true);
        }

        if (! empty($raw_img)) {
            if (is_array($raw_img) && ! empty($raw_img['url'])) {
                $image_url = $raw_img['url'];
            } elseif (is_numeric($raw_img)) {
                $image_url = wp_get_attachment_image_url((int) $raw_img, 'full') ?: '';
            } elseif (is_string($raw_img) && filter_var($raw_img, FILTER_VALIDATE_URL)) {
                $image_url = $raw_img;
            }
        }

        if (empty($image_url)) {
            $image_url = get_avatar_url($user_id, ['size' => 300]) ?: '';
        }

        // 3. Social URLs
        $fb = '';
        $ig = '';
        $x  = '';
        $li = '';

        if (function_exists('get_field')) {
            $fb = (string) get_field('facebook_url', 'user_' . $user_id);
            $ig = (string) get_field('instagram_url', 'user_' . $user_id);
            $x  = (string) get_field('x_url', 'user_' . $user_id);
            $li = (string) get_field('linkdin_url', 'user_' . $user_id);
        }

        if (empty($fb)) {
            $fb = (string) get_user_meta($user_id, 'facebook_url', true);
        }
        if (empty($ig)) {
            $ig = (string) get_user_meta($user_id, 'instagram_url', true);
        }
        if (empty($x)) {
            $x = (string) get_user_meta($user_id, 'x_url', true) ?: (string) get_user_meta($user_id, 'twitter_url', true);
        }
        if (empty($li)) {
            $li = (string) get_user_meta($user_id, 'linkdin_url', true) ?: (string) get_user_meta($user_id, 'linkedin_url', true);
        }

        $fb = trim($fb);
        $ig = trim($ig);
        $x  = trim($x);
        $li = trim($li);

        $show_pretitle    = ('yes' === $atts['show_pre_title']);
        $show_follow      = ('yes' === $atts['show_follow_title']);
        $show_social      = ('yes' === $atts['show_social']);
        $link_name        = ('yes' === $atts['link_name']);
        $has_social       = ! empty($fb) || ! empty($ig) || ! empty($x) || ! empty($li);
        $archive_url      = get_author_posts_url($user_id);

        $custom_class     = ! empty($atts['class']) ? ' ' . esc_attr($atts['class']) : '';

        ob_start();
        ?>
        <div class="pubcrawl-author-box<?php echo $custom_class; ?>">

            <?php if (! empty($image_url)) : ?>
                <div class="pubcrawl-author-box__avatar-wrap">
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($name); ?>" class="pubcrawl-author-box__avatar" loading="lazy" />
                </div>
            <?php endif; ?>

            <div class="pubcrawl-author-box__content">

                <?php if ($show_pretitle && ! empty($atts['pre_title'])) : ?>
                    <div class="pubcrawl-author-box__pretitle">
                        <?php echo esc_html($atts['pre_title']); ?>
                    </div>
                <?php endif; ?>

                <?php if (! empty($name)) : ?>
                    <h3 class="pubcrawl-author-box__name">
                        <?php if ($link_name && ! empty($archive_url)) : ?>
                            <a href="<?php echo esc_url($archive_url); ?>">
                                <?php echo esc_html($name); ?>
                            </a>
                        <?php else : ?>
                            <?php echo esc_html($name); ?>
                        <?php endif; ?>
                    </h3>
                <?php endif; ?>

                <?php if (! empty($bio)) : ?>
                    <div class="pubcrawl-author-box__bio">
                        <?php echo wp_kses_post($bio); ?>
                    </div>
                <?php endif; ?>

                <?php if ($show_social && $has_social) : ?>
                    <div class="pubcrawl-author-box__social-row">

                        <?php if ($show_follow && ! empty($atts['follow_title'])) : ?>
                            <span class="pubcrawl-author-box__follow-label">
                                <?php echo esc_html($atts['follow_title']); ?>
                            </span>
                        <?php endif; ?>

                        <ul class="pubcrawl-author-box__social-list">

                            <?php if (! empty($fb)) : ?>
                                <li class="pubcrawl-author-box__social-item">
                                    <a href="<?php echo esc_url($fb); ?>" class="pubcrawl-author-box__social-link pubcrawl-author-box__social-link--facebook" aria-label="<?php esc_attr_e('Facebook', 'pubcrawls-addon'); ?>" target="_blank" rel="noopener noreferrer">
                                        <svg class="pubcrawl-author-box__social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                        </svg>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if (! empty($ig)) : ?>
                                <li class="pubcrawl-author-box__social-item">
                                    <a href="<?php echo esc_url($ig); ?>" class="pubcrawl-author-box__social-link pubcrawl-author-box__social-link--instagram" aria-label="<?php esc_attr_e('Instagram', 'pubcrawls-addon'); ?>" target="_blank" rel="noopener noreferrer">
                                        <svg class="pubcrawl-author-box__social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                                            <circle cx="17.5" cy="6.5" r="1.5"/>
                                        </svg>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if (! empty($x)) : ?>
                                <li class="pubcrawl-author-box__social-item">
                                    <a href="<?php echo esc_url($x); ?>" class="pubcrawl-author-box__social-link pubcrawl-author-box__social-link--x" aria-label="<?php esc_attr_e('X (Twitter)', 'pubcrawls-addon'); ?>" target="_blank" rel="noopener noreferrer">
                                        <svg class="pubcrawl-author-box__social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                        </svg>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php if (! empty($li)) : ?>
                                <li class="pubcrawl-author-box__social-item">
                                    <a href="<?php echo esc_url($li); ?>" class="pubcrawl-author-box__social-link pubcrawl-author-box__social-link--linkedin" aria-label="<?php esc_attr_e('LinkedIn', 'pubcrawls-addon'); ?>" target="_blank" rel="noopener noreferrer">
                                        <svg class="pubcrawl-author-box__social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                                        </svg>
                                    </a>
                                </li>
                            <?php endif; ?>

                        </ul>
                    </div>
                <?php endif; ?>

            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }
endif;

if (! shortcode_exists('pc_author_box')) {
    add_shortcode('pc_author_box', 'pc_author_box_shortcode');
}
if (! shortcode_exists('author_box')) {
    add_shortcode('author_box', 'pc_author_box_shortcode');
}

