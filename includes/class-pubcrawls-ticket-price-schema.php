<?php
/**
 * Optional, read-only Eventbrite ticket-class prices for Event JSON-LD.
 * Does not alter checkout, purchases, tracking, or event administration.
 */
if (!defined('ABSPATH')) { exit; }

final class Pubcrawls_Ticket_Price_Schema {
    const CACHE_SECONDS = 21600;
    const ERROR_SECONDS = 1800;

    public static function init(): void {
        add_filter('pubcrawls_single_event_schema', array(__CLASS__, 'enrich'), 20, 3);
        add_action('admin_menu', array(__CLASS__, 'add_diagnostics_page'));
        add_action('admin_post_pubcrawls_price_refresh', array(__CLASS__, 'refresh_prices'));
    }

    public static function enrich($schema, $post_id, $row) {
        if (!is_array($schema) || !is_array($row)) { return $schema; }
        $eb_id = trim((string)($row['eventbrite_id'] ?? ''));
        $su_id = trim((string)($row['squadup_id'] ?? ''));
        $provider = sanitize_key((string)($row['ticket_provider'] ?? ''));
        // Never attach an Eventbrite price to a SquadUP row, even if both IDs are present.
        if ($provider === 'squadup' || !preg_match('/^[0-9]{1,30}$/', $eb_id)) { return $schema; }
        if ($provider !== 'eventbrite' && $su_id !== '') { return $schema; }
        if (!class_exists('Pubcrawls_Eventbrite_API')) { return $schema; }
        $token = Pubcrawls_Eventbrite_API::get_api_token();
        if ($token === '') { self::status($eb_id, 'No Eventbrite API token is configured. Checkout widgets can work without this token.'); return $schema; }

        $ticket_data = self::ticket_classes($eb_id, $token);
        if (empty($ticket_data)) { return $schema; }
        $url = trim((string)($row['eventbrite_link'] ?? ''));
        if (!wp_http_validate_url($url) || !in_array(strtolower((string)wp_parse_url($url, PHP_URL_HOST)), array('eventbrite.com', 'www.eventbrite.com'), true)) {
            $url = 'https://www.eventbrite.com/e/' . rawurlencode($eb_id);
        }
        $offers = array();
        foreach ($ticket_data as $ticket) {
            if (!is_array($ticket)) { continue; }
            $price = self::parse_price($ticket);
            if ($price === null) { continue; }
            $currency = self::parse_currency($ticket);
            if (!$currency) { continue; }
            $offer = array(
                '@type' => 'Offer',
                'url' => esc_url_raw($url),
                'price' => $price,
                'priceCurrency' => $currency,
            );
            if (!empty($ticket['name']) && is_string($ticket['name'])) {
                $offer['name'] = sanitize_text_field($ticket['name']);
            }
            // Eventbrite ticket_classes: these flags describe sale/inventory state.
            // Do not infer InStock just because a ticket has a price.
            $availability = self::ticket_availability($ticket);
            if ($availability !== '') { $offer['availability'] = $availability; }

            // Eventbrite sales_start is the start of this ticket-class offer,
            // not the event start or the date WordPress generated the schema.
            $valid_from = self::ticket_valid_from($ticket);
            if ($valid_from !== '') { $offer['validFrom'] = $valid_from; }
            $offers[] = $offer;
        }
        if ($offers) {
            self::status($eb_id, count($offers) . ' priced offer(s); ' . count(array_filter($offers, static function($offer) { return isset($offer['availability']); })) . ' with availability; ' . count(array_filter($offers, static function($offer) { return isset($offer['validFrom']); })) . ' with validFrom.');
            $schema['offers'] = count($offers) === 1 ? $offers[0] : $offers;
        }
        if (!$offers) { self::status($eb_id, 'API returned ticket classes, but none had a usable price/currency.'); }
        return $schema;
    }

    private static function ticket_availability(array $ticket): string {
        // Eventbrite's documented ticket-class field is on_sale_status,
        // NOT on_sale (boolean). It can also return other statuses in practice.
        $status = strtoupper(trim((string)($ticket['on_sale_status'] ?? '')));
        if ($status === 'SOLD_OUT') { return 'https://schema.org/SoldOut'; }
        if ($status === 'AVAILABLE') {
            // Avoid advertising a hidden class or a class outside its sale window.
            if (!empty($ticket['hidden']) || !empty($ticket['hidden_currently'])) { return ''; }
            $now = time();
            foreach (array('sales_start', 'sales_end') as $field) {
                if (!empty($ticket[$field]) && is_string($ticket[$field])) {
                    $time = strtotime($ticket[$field]);
                    if ($time !== false && (($field === 'sales_start' && $time > $now) ||
                        ($field === 'sales_end' && $time <= $now))) { return ''; }
                }
            }
            return 'https://schema.org/InStock';
        }
        // When a provider omits on_sale_status, explicit true flags are useful.
        if (isset($ticket['sold_out']) && $ticket['sold_out'] === true) { return 'https://schema.org/SoldOut'; }
        if (isset($ticket['on_sale']) && $ticket['on_sale'] === true &&
            (!isset($ticket['sold_out']) || $ticket['sold_out'] === false) &&
            empty($ticket['hidden']) && empty($ticket['hidden_currently'])) {
            return 'https://schema.org/InStock';
        }
        // UNAVAILABLE / NOT_YET_ON_SALE and unknown statuses are not synonyms
        // for SoldOut or PreOrder; omit instead of asserting an incorrect value.
        return '';
    }

    private static function ticket_valid_from(array $ticket): string {
        // Eventbrite's sales_start is an ISO-8601 datetime when configured.
        $raw = $ticket['sales_start'] ?? null;
        if (!is_string($raw)) { return ''; }
        $raw = trim($raw);
        // Require an explicit UTC/offset timezone; an ambiguous local datetime
        // must not be passed through as an asserted absolute timestamp.
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/i', $raw)) {
            return '';
        }
        try {
            $date = new DateTimeImmutable($raw);
            return $date->format('Y-m-d\TH:i:sP');
        } catch (Exception $e) {
            return '';
        }
    }

    private static function parse_price(array $ticket) {
        if (!empty($ticket['free'])) { return '0.00'; }
        // Eventbrite monetary objects report major_value as a decimal in currency units.
        // Avoid using amount/minor units: doing so risks a 100x pricing error.
        foreach (array('cost', 'actual_cost') as $field) {
            if (isset($ticket[$field]['major_value']) && is_numeric($ticket[$field]['major_value'])) {
                $value = (float)$ticket[$field]['major_value'];
                if (is_finite($value) && $value >= 0) { return number_format($value, 2, '.', ''); }
            }
        }
        return null;
    }

    private static function parse_currency(array $ticket): string {
        foreach (array('cost', 'actual_cost') as $field) {
            $code = strtoupper((string)($ticket[$field]['currency'] ?? ''));
            if (preg_match('/^[A-Z]{3}$/', $code)) { return $code; }
        }
        if (!empty($ticket['free'])) {
            $fallback = class_exists('Pubcrawls_Admin') ? (string)Pubcrawls_Admin::get_setting('schema_currency', 'USD') : 'USD';
            $fallback = strtoupper($fallback);
            return preg_match('/^[A-Z]{3}$/', $fallback) ? $fallback : '';
        }
        return '';
    }

    private static function ticket_classes(string $event_id, string $token): array {
        $key = 'pc_price_eb_' . $event_id . '_' . substr(hash('sha256', $token), 0, 12);
        $cached = get_transient($key);
        if (is_array($cached)) { return $cached; }
        $all = array();
        $continuation = '';
        for ($page = 0; $page < 5; $page++) {
            $url = 'https://www.eventbriteapi.com/v3/events/' . rawurlencode($event_id) . '/ticket_classes/';
            if ($continuation !== '') { $url = add_query_arg('continuation', $continuation, $url); }
            $response = wp_remote_get($url, array(
                'timeout' => 8,
                'redirection' => 0,
                'reject_unsafe_urls' => true,
                'sslverify' => true,
                'headers' => array('Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'),
            ));
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                $reason = is_wp_error($response) ? 'Network error: ' . $response->get_error_code() : 'Eventbrite API HTTP ' . wp_remote_retrieve_response_code($response);
                self::status($event_id, $reason . '. Confirm the token can access this event.');
                set_transient($key, array(), self::ERROR_SECONDS);
                return array();
            }
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!is_array($body) || !isset($body['ticket_classes']) || !is_array($body['ticket_classes'])) {
                self::status($event_id, 'The Eventbrite response did not contain ticket_classes.');
                set_transient($key, array(), self::ERROR_SECONDS);
                return array();
            }
            $all = array_merge($all, $body['ticket_classes']);
            $pagination = isset($body['pagination']) && is_array($body['pagination']) ? $body['pagination'] : array();
            $continuation = !empty($pagination['has_more_items']) && !empty($pagination['continuation']) && is_string($pagination['continuation']) ? $pagination['continuation'] : '';
            if ($continuation === '') { break; }
        }
        if (!$all) { self::status($event_id, 'Eventbrite returned no ticket classes.'); }
        set_transient($key, $all, $all ? self::CACHE_SECONDS : self::ERROR_SECONDS);
        return $all;
    }

    private static function status(string $id, string $message): void {
        set_transient('pc_price_status_' . $id, sanitize_text_field($message), DAY_IN_SECONDS);
    }

    public static function add_diagnostics_page(): void {
        add_management_page('PubCrawls Ticket Prices', 'PubCrawls Ticket Prices', 'manage_options', 'pubcrawls-ticket-prices', array(__CLASS__, 'diagnostics_page'));
    }

    public static function refresh_prices(): void {
        if (!current_user_can('manage_options')) { wp_die('Unauthorized'); }
        check_admin_referer('pubcrawls_price_refresh');
        $post_id = isset($_POST['event_post_id']) ? absint($_POST['event_post_id']) : 0;
        if ($post_id && get_post($post_id) && function_exists('get_field')) {
            $rows = get_field('event_dates', $post_id);
            if (is_array($rows)) {
                $token = class_exists('Pubcrawls_Eventbrite_API') ? Pubcrawls_Eventbrite_API::get_api_token() : '';
                foreach ($rows as $row) {
                    if (!is_array($row)) { continue; }
                    $id = trim((string)($row['eventbrite_id'] ?? ''));
                    if (!preg_match('/^[0-9]{1,30}$/', $id)) { continue; }
                    delete_transient('pc_price_eb_' . $id . '_' . substr(hash('sha256', $token), 0, 12));
                    delete_transient('pc_price_status_' . $id);
                    if ($token !== '') { self::ticket_classes($id, $token); }
                    else { self::status($id, 'No Eventbrite API token is configured.'); }
                }
            }
        }
        wp_safe_redirect(add_query_arg(array('page' => 'pubcrawls-ticket-prices', 'event_post_id' => $post_id, 'refreshed' => 1), admin_url('tools.php')));
        exit;
    }

    public static function diagnostics_page(): void {
        if (!current_user_can('manage_options')) { return; }
        $post_id = isset($_GET['event_post_id']) ? absint($_GET['event_post_id']) : 0;
        $post = $post_id ? get_post($post_id) : null;
        $token = class_exists('Pubcrawls_Eventbrite_API') ? Pubcrawls_Eventbrite_API::get_api_token() : '';
        echo '<div class="wrap"><h1>PubCrawls Ticket Price Diagnostics</h1>';
        echo '<p>API token: <strong>' . ($token !== '' ? 'Configured' : 'Missing') . '</strong>. Checkout embeds do not confirm API permissions. This page never displays the token.</p>';
        echo '<form method="get" action="' . esc_url(admin_url('tools.php')) . '"><input type="hidden" name="page" value="pubcrawls-ticket-prices"/><label for="event_post_id">WordPress event post ID: </label> <input id="event_post_id" type="number" min="1" name="event_post_id" value="' . esc_attr($post_id) . '" /> <button type="submit" class="button">Inspect</button></form>';
        if ($post) {
            echo '<h2>' . esc_html(get_the_title($post_id)) . '</h2>';
            $rows = function_exists('get_field') ? get_field('event_dates', $post_id) : array();
            echo '<table class="widefat striped"><thead><tr><th>Date</th><th>Provider</th><th>Eventbrite ID</th><th>Last price sync status</th></tr></thead><tbody>';
            if (is_array($rows)) { foreach ($rows as $row) {
                if (!is_array($row)) { continue; }
                $id = trim((string)($row['eventbrite_id'] ?? ''));
                $msg = $id ? get_transient('pc_price_status_' . $id) : false;
                echo '<tr><td>' . esc_html((string)($row['eventdate'] ?? '')) . '</td><td>' . esc_html((string)($row['ticket_provider'] ?? 'Inherited/default')) . '</td><td>' . esc_html($id) . '</td><td>' . esc_html($msg ?: 'Not checked, or status expired. Use Refresh Prices.') . '</td></tr>';
            }}
            echo '</tbody></table>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="pubcrawls_price_refresh"/><input type="hidden" name="event_post_id" value="' . esc_attr($post_id) . '"/>';
            wp_nonce_field('pubcrawls_price_refresh');
            echo '<p><button class="button button-primary">Refresh Prices Now</button></p></form>';
        }
        echo '<p>Pricing, availability and validFrom are only included when supported by Eventbrite ticket-class data. A missing field is omitted rather than guessed. SquadUP prices are not automatically fetched in this version.</p></div>';
    }

}
Pubcrawls_Ticket_Price_Schema::init();
