<?php

namespace SocialMediaPosts\Tags;

use Elementor\Core\DynamicTags\Data_Tag;
use Elementor\Modules\DynamicTags\Module;
use SocialMediaPosts\ElementorTags;

defined( 'ABSPATH' ) || exit;

class UrlTag extends Data_Tag {

	public function get_name() {
		return 'smp-url';
	}

	public function get_title() {
		return esc_html__( 'SMP Post URL', 'social-media-posts' );
	}

	public function get_group() {
		return ElementorTags::GROUP;
	}

	public function get_categories() {
		return [ Module::URL_CATEGORY ];
	}

	protected function get_value( array $options = [] ) {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}
		return esc_url( (string) get_post_meta( $post_id, '_smp_url', true ) );
	}
}
