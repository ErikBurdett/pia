=== Advanced Ads Pro ===
Requires at least: 5.7
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 3.0.8

Advanced Ads Pro is for those who want to perform magic on their ads.

== Distribution ==

The distribution of the software might be limited by copyright and trademark laws.
Copyright and trademark holder: Advanced Ads GmbH.
Please see also https://wpadvancedads.com/terms/.

== Description ==

Advanced Ads Pro extends the free version of Advanced Ads with additional features that help to increase revenue from ads.

Features:

* check delivered ads within the admin bar in the frontend
* Cache Busting
* test placements against each other
* option to limit an ad to be displayed only once per page
* refresh ads without reloading the page
* select ad-related user role for users
* inject ads into any content that uses a filter hook
* Click Fraud Protection
* alternative ads for ad-block users
* Lazy Loading
* place custom code after an ad
* disable all ads by post type
* serve ads on other websites

Placements:

* use display and visitor conditions in placements
* pick any position for the ad in your frontend
* inject ads between posts on posts lists, e.g., home, archive, category
* inject ads based on images, tables, containers, quotes, and any headline level in the content
* ads on random positions in posts (fighting ad blindness)
* ads above the main post headline
* ads in the middle of a post
* background/skin ads
* parallax ads
* set a minimum content length before content injections are happening
* set a minimum amount of words between ads injected into the content
* dedicated placements for bbPress, BuddyPress, and BuddyBoss
* show ads from another blog in a multisite
* repeat content placement injections
* allow Post List placement in any loop on static pages
* ad server to embed ads on other websites

Display and Visitor conditions:

* display ads based on the geolocation
* display ads based on where the user comes from (referrer)
* display ads based on the user agent (browser)
* display ads based on URL parameters (request URI)
* display ads based on user capability
* display ads based on the browser language
* display ads based on browser width
* display ads based on the number of previous page impressions
* display ads based on the number of ad impressions per period
* display ads to new or recurring visitors only
* display ads based on a set cookie
* display ads based on the page template
* display ads based on post metadata
* display ads based on post parent
* display ads based on the day of the week
* display ads based on the language of the page set with WPML
* display ads based on GamiPress points, ranks, and achievements
* display ads based on the BuddyPress profile information
* display ads based on the BuddyBoss profile information and BuddyBoss groups

== Installation ==

Advanced Ads Pro is based on the free Advanced Ads plugin, a simple and powerful ad management solution for WordPress.
You can use Advanced Ads along with any other ad management plugin and don’t need to switch completely.

== Changelog ==

= 3.0.8 (September 23, 2025) =

- Improvement: add undefined translations
- Improvement: adblocker countermeasures class with cleaner structure and validation
- Fix: prevent adblocker countermeasures cookie set on backend
- Fix: adblocker countermeasures cookie headers already sent warning

= 3.0.7 (August 27, 2025) =

- Improvement: add undefined translations
- Fix: fatal error in REST API with tracking addon

= 3.0.6 (July 28, 2025) =

- Improvement: add undefined translations

= 3.0.5 (June 30, 2025) =

- Improvement: add undefined translations
- Improvement: Test plugin compatibility with WordPress 6.8.
- Fix: CORS issue with multiple domains
- Fix: render background placement with group

= 3.0.4 (April 9, 2025) =
- Improvement: improve visitor profile text when updated
- Fix: resolves an issue with ads in XML feeds
- Fix: removed all license-related code from the addons as license management has been moved to the free version.
- Fix: show warning about privacy when an image ad has a custom code attached to it
- Fix: correct order of placement icons
- Fix: fatal error when adblocker item not found

= 3.0.3 (March 19, 2025) =

- Improvement: update Arabic, German, German (Austria), German (Switzerland) and German (formal) translations
- Fix: correct timeout for sticky and popup placements with passive cache busting
- Fix: restore the background placement background color

= 3.0.2 (March 13, 2025) =

- Fix: prevent PHP errors for some groups using passive Cache Busting
- Fix: ensure the correct saving of the group margin value
- Fix: correct an incorrectly called admin notices class
- Fix: resolve issue with shortcode ads not displaying on multisites

= 3.0.1 (March 11, 2025) =

- Fix: resolve PHP warnings during the update process
- Fix: remove a specific incompatibility issue with the basic version
- Fix: correct an error in the loading of the language files
- Fix: make the "Remove the placeholder if unfilled" option deselectable again

= 3.0.0 (March 10, 2025) =

- Feature: add new ad blocker tab with bundled features
- Feature: introduce ad blocker visitor condition
- Feature: introduce ad blocker overlays
- Feature: add ad blocker redirect functionality
- Feature: add Ads By Hours
- Feature: introduce fallbacks for unfilled AdSense ads
- Feature: add duplicate group functionality
- Feature: add IP Address visitor condition
- Feature: pre-check alternative ad blocker images
- Improvement: hide empty content block placeholder
- Improvement: add quick & bulk edit for Pro options
- Improvement: use groups as ad blocker fallback
- Improvement: optimize AJAX cache busting rotation to conserve server resources
- Improvement: disable elements in WP-admin bar

Build: 2025-11-eeb1761b