<?php

namespace SocialMediaPosts;

defined( 'ABSPATH' ) || exit;

class Ajax {

	public function register(): void {
		add_action( 'wp_ajax_smp_fetch_oembed', [ $this, 'fetch_oembed' ] );
		add_action( 'wp_ajax_smp_fetch_post_metadata', [ $this, 'fetch_post_metadata' ] );
		add_action( 'wp_ajax_smp_copy_to_library', [ $this, 'copy_to_library' ] );
	}

	public function fetch_post_metadata(): void {
		check_ajax_referer( 'smp_fetch_oembed', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'You are not allowed to do that.', 'social-media-posts' ) ], 403 );
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		if ( ! $url ) {
			wp_send_json_error( [ 'message' => __( 'No URL supplied.', 'social-media-posts' ) ], 400 );
		}

		$enricher = new UrlEnricher();
		$fields   = $enricher->enrich( $url );

		$parsed = ( new PostParser() )->parse(
			$fields['platform'],
			$fields['title'],
			$fields['description']
		);

		wp_send_json_success( [
			'platform'       => $fields['platform'],
			'author_name'    => $parsed['author_name'],
			'author_handle'  => $parsed['author_handle'],
			'author_bio'     => $parsed['author_bio'],
			'description'    => $parsed['caption'] ?: $fields['description'],
			'image_url'      => $fields['image_url'],
			'video_url'      => $fields['video_url'],
		] );
	}

	public function copy_to_library(): void {
		check_ajax_referer( 'smp_copy_to_library', 'nonce' );

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( [ 'message' => __( 'You are not allowed to do that.', 'social-media-posts' ) ], 403 );
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		if ( ! $url ) {
			wp_send_json_error( [ 'message' => __( 'No URL supplied.', 'social-media-posts' ) ], 400 );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_sideload_image( $url, $post_id, '', 'id' );
		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( [ 'message' => $attachment_id->get_error_message() ], 500 );
		}

		wp_send_json_success( [ 'attachment_id' => (int) $attachment_id ] );
	}

	public function fetch_oembed(): void {
		check_ajax_referer( 'smp_fetch_oembed', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'You are not allowed to do that.', 'social-media-posts' ) ], 403 );
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		if ( ! $url ) {
			wp_send_json_error( [ 'message' => __( 'No URL supplied.', 'social-media-posts' ) ], 400 );
		}

		$result = ( new OEmbedFetcher() )->fetch( $url );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ], 422 );
		}

		wp_send_json_success( $result );
	}
}
