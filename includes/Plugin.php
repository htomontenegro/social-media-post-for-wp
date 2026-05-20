<?php

namespace SocialMediaPosts;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	private static ?Plugin $instance = null;

	public static function instance(): Plugin {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		load_plugin_textdomain( 'social-media-posts', false, dirname( plugin_basename( SMP_FILE ) ) . '/languages' );

		( new PostType() )->register();
		( new MetaBox() )->register();
		( new Ajax() )->register();
		( new Importer() )->register();
		( new BulkActions() )->register();
		( new Carousel() )->register();
		( new Grid() )->register();
		( new Admin() )->register();

		if ( did_action( 'elementor/loaded' ) ) {
			( new ElementorTags() )->register();
		} else {
			add_action( 'elementor/loaded', function () {
				( new ElementorTags() )->register();
			} );
		}
	}

	public static function platforms(): array {
		return [
			'instagram' => __( 'Instagram', 'social-media-posts' ),
			'facebook'  => __( 'Facebook', 'social-media-posts' ),
			'x'         => __( 'X (Twitter)', 'social-media-posts' ),
			'tiktok'    => __( 'TikTok', 'social-media-posts' ),
			'youtube'   => __( 'YouTube', 'social-media-posts' ),
			'linkedin'  => __( 'LinkedIn', 'social-media-posts' ),
			'other'     => __( 'Other', 'social-media-posts' ),
		];
	}
}
