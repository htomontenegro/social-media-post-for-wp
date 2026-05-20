<?php

namespace SocialMediaPosts\Tags;

use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module;
use SocialMediaPosts\ElementorTags;
use SocialMediaPosts\Plugin;

defined( 'ABSPATH' ) || exit;

class PlatformTag extends Tag {

	public function get_name() {
		return 'smp-platform';
	}

	public function get_title() {
		return esc_html__( 'SMP Platform', 'social-media-posts' );
	}

	public function get_group() {
		return ElementorTags::GROUP;
	}

	public function get_categories() {
		return [ Module::TEXT_CATEGORY ];
	}

	public function render() {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return;
		}
		$platform  = (string) get_post_meta( $post_id, '_smp_platform', true );
		$platforms = Plugin::platforms();
		if ( ! $platform || ! isset( $platforms[ $platform ] ) ) {
			return;
		}
		echo esc_html( $platforms[ $platform ] );
	}
}
