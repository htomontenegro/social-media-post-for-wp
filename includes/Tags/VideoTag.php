<?php

namespace SocialMediaPosts\Tags;

use Elementor\Core\DynamicTags\Data_Tag;
use Elementor\Modules\DynamicTags\Module;
use SocialMediaPosts\ElementorTags;

defined( 'ABSPATH' ) || exit;

class VideoTag extends Data_Tag {

	public function get_name() {
		return 'smp-video';
	}

	public function get_title() {
		return esc_html__( 'SMP Video URL', 'social-media-posts' );
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

		$attachment_id = (int) get_post_meta( $post_id, '_smp_media_attachment_id', true );
		$source        = (string) get_post_meta( $post_id, '_smp_media_source', true );

		if ( $source === 'library' && $attachment_id ) {
			$url = wp_get_attachment_url( $attachment_id );
			if ( $url ) {
				return esc_url( $url );
			}
		}

		return esc_url( (string) get_post_meta( $post_id, '_smp_media_url', true ) );
	}
}
