<?php

namespace SocialMediaPosts\Tags;

use Elementor\Core\DynamicTags\Data_Tag;
use Elementor\Modules\DynamicTags\Module;
use SocialMediaPosts\ElementorTags;

defined( 'ABSPATH' ) || exit;

class ImageTag extends Data_Tag {

	public function get_name() {
		return 'smp-image';
	}

	public function get_title() {
		return esc_html__( 'SMP Image', 'social-media-posts' );
	}

	public function get_group() {
		return ElementorTags::GROUP;
	}

	public function get_categories() {
		return [ Module::IMAGE_CATEGORY, Module::MEDIA_CATEGORY ];
	}

	public function get_value( array $options = [] ) {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return [ 'id' => '', 'url' => '' ];
		}

		$attachment_id = (int) get_post_meta( $post_id, '_smp_media_attachment_id', true );
		$source        = (string) get_post_meta( $post_id, '_smp_media_source', true );

		if ( $source === 'library' && $attachment_id ) {
			$src = wp_get_attachment_image_src( $attachment_id, 'full' );
			if ( $src && ! empty( $src[0] ) ) {
				return [ 'id' => $attachment_id, 'url' => $src[0] ];
			}
		}

		$url = (string) get_post_meta( $post_id, '_smp_media_url', true );
		if ( $url ) {
			return [ 'id' => '', 'url' => esc_url( $url ) ];
		}

		$thumb_id = get_post_thumbnail_id( $post_id );
		if ( $thumb_id ) {
			$src = wp_get_attachment_image_src( $thumb_id, 'full' );
			if ( $src && ! empty( $src[0] ) ) {
				return [ 'id' => $thumb_id, 'url' => $src[0] ];
			}
		}

		return [ 'id' => '', 'url' => '' ];
	}
}
