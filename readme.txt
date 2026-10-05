=== Pubcrawl ===
Contributors: nexibyllc
Donate link: https://pubcrawls.com/
Tags: elementor, pubcrawls, events, cities, venues
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 3.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Elementor Addons for PubCrawls — provides 17 native Elementor widgets for Events, Cities, Venues, FAQs, Maps, Agendas, Eventbrite & SquadUp Tickets, AI Quick Links, and dynamic content. 

== Description ==

Pubcrawl is the complete, standalone Elementor solution for PubCrawls. It replaces both the legacy `pub-crawls` plugin and the `pubcrawls-eventbrite-tickets` plugin with 17 intuitive, high-performance, and fully customizable Elementor widgets, native custom post types, taxonomies, and shortcodes.

= Included Widgets =
1. **Cities Card Grid** (`pc-cities-card`) — Displays a grid of cities with upcoming event count badges.
2. **Cities List (Popular / All)** (`pc-cities-list`) — Displays a directory of popular and all cities with tabbed view.
3. **City Archive Grid** (`pc-city-archive`) — Grid layout for city archives filtered by category or IDs.
4. **City Events Grid** (`pc-city-events`) — Grid of upcoming events with dates, image, and buy/info buttons.
5. **City Map** (`pc-city-map`) — Embedded Google Map from city ACF map_url.
6. **City Venues** (`pc-city-venues`) — Participating venues list for a city with directions links.
7. **Event Agenda** (`pc-event-agenda`) — Event timeline / agenda steps from ACF repeater.
8. **Event Sticky Ticket Bar** (`pc-event-sticky`) — Sticky bottom/floating bar with date selector and Eventbrite/SquadUp ticket trigger. Replaces `[pubcrawls_event_sticky]`.
9. **Event Tickets Card** (`pc-event-tickets`) — Main event tickets card with date/time, dropdown date selector, Eventbrite modal & SquadUp checkout integration. Replaces `[pubcrawls_event_tickets]`.
10. **Event Venues** (`pc-event-venues`) — Participating venues list for an event.
11. **FAQ Grid** (`pc-faq-grid`) — FAQ grid filtered by category or limit.
12. **Get Directions Button** (`pc-get-directions`) — Google Maps directions link based on ACF address field.
13. **Job Cities** (`pc-job-cities`) — Job cities taxonomy terms display.
14. **Next Event Date** (`pc-next-event-date`) — Formatted next event date calculation for menus or banners.
15. **Page / Post FAQs** (`pc-page-faqs`) — FAQs with dual-mode support: dynamic ACF repeater or custom Elementor repeater with rich text answers. Replaces `[pubcrawl_page_faqs]`.
16. **Social Share Icons** (`pc-social-share`) — Social share buttons for Facebook, Twitter/X, LinkedIn, WhatsApp, etc.
17. **AI Quick Links** (`pc-ai-quick-links`) — Direct query buttons for ChatGPT, Perplexity, Google AI, and Microsoft Copilot with inline SVGs and custom styling.

= Modular Helpers & Bulk Tools =
* **FAQ & SEO Bulk Importer** — Fast batch import of ACF `faqs` repeaters and Yoast / Rank Math / All In One SEO title & meta description across city and events post types using structured JSON. Features dry-run simulation, 25-item chunking to prevent server timeouts, live database counts, and dynamic ACF field key auto-discovery for multi-site portability.
* **Bulk Title & Slug Editor** — Direct bulk editing tools in the WordPress post list tables for `tribe_events`, `city`, `venue`, and custom post types: Append to title (end), Prepend to title (start), Remove from start/end, and Find & Replace. Includes live typing preview, spacing options, and optional URL permalink slug synchronization.
* **Quick Edit Featured Image** — Fast thumbnail preview column directly in WordPress post tables with full image selection, media library modal picker, live thumbnail preview, and removal controls integrated directly into the WordPress Quick Edit inline form.
* **Post URL Column in Admin Lists** — Displays full clickable permalink URLs directly in admin post list tables (Events, Cities, Venues, Pages, Posts, etc.), with granular per-post-type enable/disable checkboxes.
* **Dashboard Toggles** — Easily activate or deactivate helpers individually in the PubCrawls Dashboard under "Helpers & Tools".

== Installation ==

1. Upload the `pubcrawls-addon` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. You can safely deactivate and remove both `pub-crawls` and `pubcrawls-eventbrite-tickets`.
4. Edit any page with Elementor to find the "PubCrawls" widget category in the editor panel.
5. Manage widgets, post types, ticket gateways, and helper tools under the "PubCrawls" dashboard menu.

== Changelog ==

= 2.0.0 =
* Added modular Helpers & Tools directory structure (`includes/helpers/`).
* Added FAQ & SEO Bulk Importer with batching (25/step), dry-run testing, overwrite mode, and direct database queries.
* Added Bulk Title & Slug Editor with Append, Prepend, Remove (start/end), Find & Replace, and URL slug sync.
* Added Quick Edit Featured Image helper with table thumbnail column, media modal picker, and AJAX save/remove.
* Added Post URL Column helper displaying full clickable permalinks with per-post-type enable/disable settings.
* Added dynamic ACF field key auto-discovery (`acf_get_field('faqs')`) with fallback keys for multi-site installation portability.
* Added multi-SEO plugin compatibility (Yoast SEO, Rank Math, All In One SEO, SEOPress).
* Added Admin Dashboard "Helpers & Tools" tab with one-click enable/disable toggles and active status badges.
* Fixed Elementor deletion conflict by eliminating reserved `'id'` controls and prefixing all widget controls with `pubcrawl_`.
* Added dual-mode custom FAQ switcher and WYSIWYG repeater to `pubcrawl-page-faqs`.

= 1.0.0 =
* Initial release with 17 native Elementor widgets.
* Integrated Custom Post Types (Cities, Events, Venues, FAQs, Press, City Maps) and Taxonomies.
* Dual-mode support for Page/Post FAQs (ACF dynamic or custom repeater).
* SquadUp & Eventbrite checkout modal integrations.
* Direct AI quick links for ChatGPT, Perplexity, Google AI, and Microsoft Copilot.