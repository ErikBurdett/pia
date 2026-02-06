=== Advanced Ads – Tracking ===
Requires at least: 5.7
Tested up to: 6.8
Stable tag: 3.0.8
Requires PHP: 7.4

Track ad impressions and clicks.

== Distribution ==

The distribution of the software might be limited by copyright and trademark laws.
Copyright and trademark holder: Advanced Ads GmbH.
Please see also https://wpadvancedads.com/terms/.

== Description ==

This add-on for the Advanced Ads plugin provides tracking ad impressions and clicks.

**Tracking:**

* count ad impressions and clicks either locally or in Google Analytics
* enable or disable tracking for all or individual ads
* enable link cloaking to hide the target URL
* open individual links in a new window
* add nofollow or sponsored attribute to links
* works on JavaScript-based ads, iframes and on AMP pages

**Ad Planning**

* limit ad views to a certain amount of impressions or clicks
* spread impressions and clicks over a given period

**Statistics**

* see stats of all or individual ads in your dashboard based on predefined and custom periods, grouped by day, week, or month
* display stats in a table and graph
* compare stats for ads
* compare stats with the previous or next period
* remove stats for all or single ads
* filter stats by ad groups
* public stats for a single ad – e.g. to show clients

**Reports**

* send email reports for all or individual ads to different emails
* create public reports for clients
* combine impressions and clicks with any other metrics in Google Analytics

**Statistic Management**

* export stats as csv
* import stats from csv
* remove old stats

Software included:

* [jqPlot](http://www.jqplot.com), GPL 2

== Installation ==

The Tracking add-on is based on the Advanced Ads plugin, a simple and powerful ad management solution for WordPress.

== Changelog ==

= 3.0.8 (September 23, 2025) =

- Improvement: add undefined translations
- Improvement: show "disabled" in email report when impression or click tracking is turned off
- Fix: prevent impression divide by zero error in email reports

= 3.0.7 (September 1, 2025) =

- Improvement: add undefined translations
- Fix: resolve an error when processing tracking links

= 3.0.6 (August 28, 2025) =

- Improvement: add undefined translations
- Improvement: ajax handler will run with better performance and security
- Fix: remove `mb_convert_encoding` deprecation notice
- Fix: "advancedAdsGAInstances is not defined" JavaScript error
- Fix: Google Analytics tracking ID will correctly record impressions and clicks for ad placements
- Fix: prevent PHP DateRangeError on timestamp handling

= 3.0.5 (July 28, 2025) =

- Improvement: add undefined translations
- Improvement: prevent fatal error when tracking on invalid or non-ad entries
- Fix: resolve incorrect loading of tracking JS files

= 3.0.4 (June 25, 2025) =

- Improvement: add undefined translations
- Improvement: type safety in class-ad-limiter.php
- Improvement: Test plugin compatibility with WordPress 6.8.
- Fix: source URL tracking for cloaked links in debug logs
- Fix: add safety check in data handling
- Fix: the_ad_clicks shortcode render as plain text

= 3.0.3 (April 9, 2025) =

- Improvement: public report url rewriting with backward compatibility
- Fix: removed all license-related code from the addons as license management has been moved to the free version.
- Fix: quick edit for ads ignores tracking options and overwrites content

= 3.0.2 (March 18, 2025) =

- Improvement: update Arabic translations
- Fix: prevent fatal error on ad page

= 3.0.1 (March 12, 2025) =

- Fix: resolve PHP warnings during the update process
- Fix: correct an error in the loading of the language files
- Fix: display ad names in public reports and shareable links again

= 3.0.0 (March 10, 2025) =

- Feature: add best performing ads widget
- Improvement: add quick & bulk edit for tracking options

Build: 2025-11-2c933474