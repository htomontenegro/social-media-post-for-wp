<?php

namespace SocialMediaPosts\Tags;

use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module;
use SocialMediaPosts\ElementorTags;

defined( 'ABSPATH' ) || exit;

class DescriptionTag extends Tag {

	public function get_name() {
		return 'smp-description';
	}

	public function get_title() {
		return esc_html__( 'SMP Description', 'social-media-posts' );
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
		$value = (string) get_post_meta( $post_id, '_smp_description', true );
		echo wp_kses_post( $value );
	}
}
