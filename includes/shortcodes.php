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
