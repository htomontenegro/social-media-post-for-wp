<?php
/**
 * Plugin Name:       Social Media Posts
 * Description:       Custom post type to curate social media posts (Instagram, Facebook, X, TikTok, YouTube, LinkedIn, Other) with Elementor Dynamic Tag integration, plus a Social Links manager and [smp_social_links] icon shortcode.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Fairgo
 * Text Domain:       social-media-posts
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'SMP_VERSION', '1.1.0' );
define( 'SMP_FILE', __FILE__ );
define( 'SMP_PATH', plugin_dir_path( __FILE__ ) );
define( 'SMP_URL', plugin_dir_url( __FILE__ ) );
define( 'SMP_POST_TYPE', 'social_media_post' );

$smp_composer_autoload = SMP_PATH . 'vendor/autoload.php';
if ( file_exists( $smp_composer_autoload ) ) {
	require_once $smp_composer_autoload;
} else {
	spl_autoload_register( function ( $class ) {
		$prefix = 'SocialMediaPosts\\';
		if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file     = SMP_PATH . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	} );
}

register_activation_hook( __FILE__, function () {
	( new \SocialMediaPosts\PostType() )->register();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );

add_action( 'plugins_loaded', function () {
	\SocialMediaPosts\Plugin::instance()->boot();
} );
