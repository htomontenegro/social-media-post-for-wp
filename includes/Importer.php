<?php

namespace SocialMediaPosts;

defined( 'ABSPATH' ) || exit;

class Importer {

	private const MENU_SLUG    = 'smp-import';
	private const NONCE_ACTION = 'smp_import_url';

	private string $hook_suffix = '';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'wp_ajax_smp_import_url', [ $this, 'ajax_import_url' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function add_menu_page(): void {
		$this->hook_suffix = (string) add_submenu_page(
			'edit.php?post_type=' . SMP_POST_TYPE,
			__( 'Import URLs', 'social-media-posts' ),
			__( 'Import URLs', 'social-media-posts' ),
			'edit_posts',
			self::MENU_SLUG,
			[ $this, 'render_page' ]
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		?>
		<div class="wrap smp-importer">
			<h1><?php esc_html_e( 'Import Social Media Posts from URLs', 'social-media-posts' ); ?></h1>

			<p class="description">
				<?php esc_html_e( 'Paste one URL per line. The importer creates a Draft post for each URL with title, description, and thumbnail auto-filled when the platform exposes them.', 'social-media-posts' ); ?>
			</p>
			<p class="description">
				<strong><?php esc_html_e( 'Heads up:', 'social-media-posts' ); ?></strong>
				<?php esc_html_e( 'YouTube, TikTok, Vimeo, Instagram, and X posts populate automatically. Facebook URLs may require manual completion as the platform restricts public post data — the importer will still create the draft with URL + platform set so you can fill in the rest.', 'social-media-posts' ); ?>
			</p>

			<form id="smp-import-form" onsubmit="return false;">
				<p>
					<label for="smp-import-collections"><strong><?php esc_html_e( 'Add to Collections (optional)', 'social-media-posts' ); ?></strong></label>
					<select id="smp-import-collections" multiple class="regular-text smp-import-collections">
						<?php
						$terms = get_terms( [
							'taxonomy'   => 'social_media_collection',
							'hide_empty' => false,
						] );
						if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
							foreach ( $terms as $term ) {
								printf(
									'<option value="%s">%s</option>',
									esc_attr( $term->slug ),
									esc_html( $term->name )
								);
							}
						}
						?>
					</select>
					<p class="description"><?php esc_html_e( 'Select one or more collections to assign to the imported posts. Leave empty to create posts without a collection.', 'social-media-posts' ); ?></p>
				</p>

				<p>
					<label for="smp-import-urls"><strong><?php esc_html_e( 'URLs (one per line)', 'social-media-posts' ); ?></strong></label>
				</p>
				<textarea id="smp-import-urls" rows="10" class="large-text code" placeholder="https://www.instagram.com/p/...&#10;https://www.tiktok.com/@user/video/...&#10;https://www.youtube.com/watch?v=..."></textarea>

				<p class="submit">
					<button type="submit" class="button button-primary" id="smp-import-start"><?php esc_html_e( 'Start Import', 'social-media-posts' ); ?></button>
					<span class="smp-import-summary" aria-live="polite"></span>
				</p>
			</form>

			<table class="widefat striped smp-import-results" hidden>
				<thead>
					<tr>
						<th class="smp-col-status"><?php esc_html_e( 'Status', 'social-media-posts' ); ?></th>
						<th class="smp-col-url"><?php esc_html_e( 'URL', 'social-media-posts' ); ?></th>
						<th class="smp-col-platform"><?php esc_html_e( 'Platform', 'social-media-posts' ); ?></th>
						<th class="smp-col-title"><?php esc_html_e( 'Title', 'social-media-posts' ); ?></th>
						<th class="smp-col-actions"><?php esc_html_e( 'Action', 'social-media-posts' ); ?></th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<?php
	}

	public function enqueue_assets( string $hook ): void {
		if ( ! $this->hook_suffix || $hook !== $this->hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'smp-import',
			SMP_URL . 'assets/import.css',
			[],
			SMP_VERSION
		);

		wp_enqueue_script(
			'smp-import',
			SMP_URL . 'assets/import.js',
			[ 'jquery' ],
			SMP_VERSION,
			true
		);

		wp_localize_script( 'smp-import', 'SMP_Import', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
			'i18n'    => [
				'processing'   => __( 'Processing…', 'social-media-posts' ),
				'queued'       => __( 'Queued', 'social-media-posts' ),
				'created'      => __( 'Created', 'social-media-posts' ),
				'partial'      => __( 'Partial', 'social-media-posts' ),
				'duplicate'    => __( 'Duplicate', 'social-media-posts' ),
				'failed'       => __( 'Failed', 'social-media-posts' ),
				'edit'         => __( 'Edit post', 'social-media-posts' ),
				'noUrls'       => __( 'Paste at least one URL.', 'social-media-posts' ),
				'summaryDone'  => __( 'Done. Created: %1$d · Partial: %2$d · Duplicate: %3$d · Failed: %4$d', 'social-media-posts' ),
				'networkError' => __( 'Network error — try again.', 'social-media-posts' ),
			],
			'collectionsSelector' => '#smp-import-collections',
		] );
	}

	public function ajax_import_url(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'You are not allowed to do that.', 'social-media-posts' ) ], 403 );
		}

		$raw_url = isset( $_POST['url'] ) ? wp_unslash( $_POST['url'] ) : '';
		$url     = esc_url_raw( trim( (string) $raw_url ) );
		if ( ! $url ) {
			wp_send_json_error( [
				'status'  => 'failed',
				'message' => __( 'Invalid URL.', 'social-media-posts' ),
			], 400 );
		}

		$collections = isset( $_POST['collections'] ) ? wp_unslash( (array) $_POST['collections'] ) : [];
		$collections = array_map( 'sanitize_key', $collections );

		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
			wp_send_json_error( [
				'status'  => 'failed',
				'message' => __( 'Only http and https URLs are supported.', 'social-media-posts' ),
			], 400 );
		}

		$existing = $this->find_existing_post( $url );
		if ( $existing ) {
			if ( ! empty( $collections ) ) {
				wp_set_post_terms( $existing, $collections, 'social_media_collection', true );
			}
			wp_send_json_success( [
				'status'    => 'duplicate',
				'post_id'   => $existing,
				'edit_link' => get_edit_post_link( $existing, 'raw' ),
				'title'     => get_the_title( $existing ),
				'platform'  => (string) get_post_meta( $existing, '_smp_platform', true ),
				'message'   => empty( $collections )
					? __( 'Already imported.', 'social-media-posts' )
					: __( 'Already imported — added to selected collection(s).', 'social-media-posts' ),
			] );
		}

		$enricher = new UrlEnricher();
		$fields   = $enricher->enrich( $url );

		$parsed = ( new PostParser() )->parse(
			$fields['platform'],
			$fields['title'],
			$fields['description']
		);

		$caption        = $parsed['caption'] ?: $fields['description'];
		$post_title     = PostParser::truncate_for_title( $caption ) ?: ( $fields['title'] ?: $url );

		$post_id = wp_insert_post( [
			'post_type'    => SMP_POST_TYPE,
			'post_status'  => 'draft',
			'post_title'   => $post_title,
			'post_content' => '',
		], true );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( [
				'status'  => 'failed',
				'message' => $post_id->get_error_message(),
			], 500 );
		}

		update_post_meta( $post_id, '_smp_url', $url );
		update_post_meta( $post_id, '_smp_platform', $fields['platform'] );
		update_post_meta( $post_id, '_smp_description', $caption );
		update_post_meta( $post_id, '_smp_author_name', $parsed['author_name'] );
		update_post_meta( $post_id, '_smp_author_bio', $parsed['author_bio'] );
		update_post_meta( $post_id, '_smp_author_handle', $parsed['author_handle'] );

		$media_type    = $fields['video_url'] ? 'video' : 'image';
		$media_url     = $fields['video_url'] ?: $fields['image_url'];
		$attachment_id = 0;
		$media_source  = 'url';

		if ( $fields['image_url'] ) {
			$attachment_id = $this->sideload_image( $fields['image_url'], $post_id );
			if ( $attachment_id ) {
				$media_source = 'attachment';
			}
		}

		update_post_meta( $post_id, '_smp_media_type', $media_type );
		update_post_meta( $post_id, '_smp_media_source', $media_source );
		update_post_meta( $post_id, '_smp_media_url', $media_url );
		update_post_meta( $post_id, '_smp_media_attachment_id', $attachment_id );

		if ( ! empty( $collections ) ) {
			wp_set_post_terms( $post_id, $collections, 'social_media_collection', false );
		}

		$has_caption = (bool) $caption;
		$has_media   = (bool) $media_url;
		$status      = ( $has_caption && $has_media ) ? 'created' : 'partial';

		wp_send_json_success( [
			'status'    => $status,
			'post_id'   => $post_id,
			'edit_link' => get_edit_post_link( $post_id, 'raw' ),
			'title'     => $post_title,
			'platform'  => $fields['platform'],
			'author'    => $parsed['author_handle'] ? '@' . $parsed['author_handle'] : $parsed['author_name'],
			'message'   => $status === 'created'
				? __( 'Created with caption and media.', 'social-media-posts' )
				: __( 'Created — needs manual completion.', 'social-media-posts' ),
		] );
	}

	private function sideload_image( string $url, int $post_id ): int {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_sideload_image( $url, $post_id, '', 'id' );
		if ( is_wp_error( $attachment_id ) ) {
			error_log( 'SMP: sideload_image failed for ' . $url . ': ' . $attachment_id->get_error_message() );
			return 0;
		}
		return (int) $attachment_id;
	}

	private function find_existing_post( string $url ): int {
		$query = new \WP_Query( [
			'post_type'      => SMP_POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => [
				[
					'key'   => '_smp_url',
					'value' => $url,
				],
			],
		] );
		$ids = $query->posts;
		return ! empty( $ids ) ? (int) $ids[0] : 0;
	}
}
