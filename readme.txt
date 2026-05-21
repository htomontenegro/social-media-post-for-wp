=== Social Media Posts ===
Contributors: fairgo
Tags: social media, custom post type, elementor, dynamic tags
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Curate social media posts (Instagram, Facebook, X, TikTok, YouTube, LinkedIn, Other) and surface them in Elementor through dedicated Dynamic Tags.

== Description ==

Registers a public custom post type "Social Media Posts" with custom fields for Description, URL, Platform, and Media (image or video, either from the WordPress Media Library or via an external URL). Provides:

* Admin meta box with WordPress Media Library picker.
* "Fetch from URL" button that pulls a thumbnail via WordPress oEmbed when the platform supports it.
* Custom Elementor Dynamic Tags grouped under "Social Media Posts" so the fields show up natively in the Elementor editor — perfect for Loop Grid templates.

== Installation ==

1. Upload the `social-media-posts` directory to `/wp-content/plugins/`.
2. Activate the plugin via the Plugins screen.
3. Browse to `wp-admin → Social Media Posts → Add New`.

== Changelog ==

= 1.1.0 =
* New "Social Links" admin page to manage social profile URLs (Facebook, Instagram, X, TikTok, LinkedIn, plus unlimited extras), each with a per-link new-tab/same-tab target.
* New [smp_social_links] shortcode (alias [smp_social_icons]) rendering branded or minimalist icon sets with controls for size, padding, gap, colour, background, shape, alignment, and target.

= 1.0.0 =
* Initial release.
