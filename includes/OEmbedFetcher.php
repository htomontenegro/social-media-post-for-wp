<?php

namespace SocialMediaPosts;

defined( 'ABSPATH' ) || exit;

class OEmbedFetcher {

	/**
	 * Fetch oEmbed data for a URL and return a normalised payload.
	 *
	 * Returns an error only when the provider returned no oEmbed data at all.
	 * Individual fields (thumbnail_url, title, etc.) may be empty strings; callers
	 * decide what counts as "good enough".
	 *
	 * @return array{thumbnail_url:string,type:string,title:string,provider_name:string}|\WP_Error
	 */
	public function fetch( string $url ) {
		$url = esc_url_raw( $url );
		if ( ! $url ) {
			return new \WP_Error( 'smp_invalid_url', __( 'Invalid URL.', 'social-media-posts' ) );
		}

		$oembed = _wp_oembed_get_object();
		$data   = $oembed->get_data( $url );

		if ( ! $data || ! is_object( $data ) ) {
			return new \WP_Error( 'smp_no_oembed', __( 'No oEmbed data available for this URL.', 'social-media-posts' ) );
		}

		$thumbnail     = isset( $data->thumbnail_url ) ? (string) $data->thumbnail_url : '';
		$type          = isset( $data->type ) ? (string) $data->type : '';
		$title         = isset( $data->title ) ? (string) $data->title : '';
		$provider_name = isset( $data->provider_name ) ? (string) $data->provider_name : '';

		if ( ! $thumbnail ) {
			return new \WP_Error( 'smp_no_thumbnail', __( 'The provider did not return a thumbnail.', 'social-media-posts' ) );
		}

		return [
			'thumbnail_url' => esc_url_raw( $thumbnail ),
			'type'          => sanitize_text_field( $type ),
			'title'         => sanitize_text_field( $title ),
			'provider_name' => sanitize_text_field( $provider_name ),
		];
	}
}
