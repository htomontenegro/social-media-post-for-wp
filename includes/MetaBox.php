<?php

namespace SocialMediaPosts;

defined( 'ABSPATH' ) || exit;

class MetaBox {

	private const NONCE_ACTION = 'smp_save_meta';
	private const NONCE_NAME   = 'smp_meta_nonce';

	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post_' . SMP_POST_TYPE, [ $this, 'save' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	public function add_meta_box(): void {
		add_meta_box(
			'smp_details',
			__( 'Social Media Post Details', 'social-media-posts' ),
			[ $this, 'render' ],
			SMP_POST_TYPE,
			'normal',
			'high'
		);
	}

	public function render( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$description        = (string) get_post_meta( $post->ID, '_smp_description', true );
		$url                = (string) get_post_meta( $post->ID, '_smp_url', true );
		$platform           = (string) get_post_meta( $post->ID, '_smp_platform', true );
		$author_name        = (string) get_post_meta( $post->ID, '_smp_author_name', true );
		$author_bio         = (string) get_post_meta( $post->ID, '_smp_author_bio', true );
		$author_handle      = (string) get_post_meta( $post->ID, '_smp_author_handle', true );
		$media_type         = (string) get_post_meta( $post->ID, '_smp_media_type', true ) ?: 'image';
		$media_source       = (string) get_post_meta( $post->ID, '_smp_media_source', true ) ?: 'library';
		$attachment_id      = (int) get_post_meta( $post->ID, '_smp_media_attachment_id', true );
		$media_url          = (string) get_post_meta( $post->ID, '_smp_media_url', true );
		$preview_url        = '';
		if ( $attachment_id ) {
			$preview_url = (string) wp_get_attachment_image_url( $attachment_id, 'medium' );
			if ( ! $preview_url && $media_type === 'video' ) {
				$preview_url = (string) wp_get_attachment_url( $attachment_id );
			}
		} elseif ( $media_url ) {
			$preview_url = $media_url;
		}

		$platforms = Plugin::platforms();
		?>
		<div class="smp-meta-box">
			<fieldset class="smp-field smp-author">
				<legend><strong><?php esc_html_e( 'Author Info', 'social-media-posts' ); ?></strong></legend>
				<div class="smp-author__row">
					<label class="smp-author__field">
						<span><?php esc_html_e( 'Name', 'social-media-posts' ); ?></span>
						<input type="text" id="smp_author_name" name="smp_author_name" value="<?php echo esc_attr( $author_name ); ?>" class="regular-text" />
					</label>
					<label class="smp-author__field">
						<span><?php esc_html_e( 'Handle', 'social-media-posts' ); ?></span>
						<input type="text" id="smp_author_handle" name="smp_author_handle" value="<?php echo esc_attr( $author_handle ); ?>" class="regular-text" placeholder="username (without @)" />
					</label>
				</div>
				<label class="smp-author__field smp-author__field--full">
					<span><?php esc_html_e( 'Bio / Tagline', 'social-media-posts' ); ?></span>
					<input type="text" id="smp_author_bio" name="smp_author_bio" value="<?php echo esc_attr( $author_bio ); ?>" class="large-text" />
				</label>
			</fieldset>

			<p class="smp-field">
				<label for="smp_description"><strong><?php esc_html_e( 'Description / Caption', 'social-media-posts' ); ?></strong></label>
				<textarea id="smp_description" name="smp_description" rows="4" class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
			</p>

			<p class="smp-field">
				<label for="smp_url"><strong><?php esc_html_e( 'Post URL', 'social-media-posts' ); ?></strong></label>
				<div class="smp-url__row">
					<input type="url" id="smp_url" name="smp_url" value="<?php echo esc_attr( $url ); ?>" class="large-text" placeholder="https://" />
					<button type="button" class="button smp-fetch-post-data"><?php esc_html_e( 'Fetch Post Data', 'social-media-posts' ); ?></button>
				</div>
				<div class="smp-fetch-status" role="status" aria-live="polite"></div>
			</p>

			<p class="smp-field">
				<label for="smp_platform"><strong><?php esc_html_e( 'Platform', 'social-media-posts' ); ?></strong></label>
				<select id="smp_platform" name="smp_platform">
					<option value=""><?php esc_html_e( '— Select —', 'social-media-posts' ); ?></option>
					<?php foreach ( $platforms as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $platform, $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<p class="smp-field">
				<label for="smp_collections"><strong><?php esc_html_e( 'Collections', 'social-media-posts' ); ?></strong></label>
				<div class="smp-collections-list">
					<?php
					$current_terms = wp_get_post_terms( $post->ID, 'social_media_collection', [ 'fields' => 'ids' ] );
					if ( is_wp_error( $current_terms ) ) {
						$current_terms = [];
					}
					$terms = get_terms( [
						'taxonomy'   => 'social_media_collection',
						'hide_empty' => false,
					] );
					if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
						foreach ( $terms as $term ) {
							printf(
								'<label><input type="checkbox" name="smp_collections[]" value="%s" %s /> %s</label>',
								esc_attr( $term->term_id ),
								checked( in_array( $term->term_id, $current_terms, true ), true, false ),
								esc_html( $term->name )
							);
						}
					} else {
						echo '<p class="description">' . esc_html__( 'No collections yet. Create one from the Collections menu.', 'social-media-posts' ) . '</p>';
					}
					?>
				</div>
			</p>

			<fieldset class="smp-field smp-field--inline">
				<legend><strong><?php esc_html_e( 'Media Type', 'social-media-posts' ); ?></strong></legend>
				<label><input type="radio" name="smp_media_type" value="image" <?php checked( $media_type, 'image' ); ?> /> <?php esc_html_e( 'Image', 'social-media-posts' ); ?></label>
				<label><input type="radio" name="smp_media_type" value="video" <?php checked( $media_type, 'video' ); ?> /> <?php esc_html_e( 'Video', 'social-media-posts' ); ?></label>
			</fieldset>

			<fieldset class="smp-field smp-field--inline">
				<legend><strong><?php esc_html_e( 'Media Source', 'social-media-posts' ); ?></strong></legend>
				<label><input type="radio" name="smp_media_source" value="library" <?php checked( $media_source, 'library' ); ?> /> <?php esc_html_e( 'Media Library', 'social-media-posts' ); ?></label>
				<label><input type="radio" name="smp_media_source" value="url" <?php checked( $media_source, 'url' ); ?> /> <?php esc_html_e( 'External URL', 'social-media-posts' ); ?></label>
			</fieldset>

			<div class="smp-field smp-media-library" data-active="<?php echo esc_attr( $media_source === 'library' ? '1' : '0' ); ?>">
				<input type="hidden" id="smp_media_attachment_id" name="smp_media_attachment_id" value="<?php echo esc_attr( (string) $attachment_id ); ?>" />
				<button type="button" class="button smp-pick-media"><?php esc_html_e( 'Select from Media Library', 'social-media-posts' ); ?></button>
				<button type="button" class="button-link smp-clear-media" <?php echo $attachment_id ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', 'social-media-posts' ); ?></button>
			</div>

			<div class="smp-field smp-media-url" data-active="<?php echo esc_attr( $media_source === 'url' ? '1' : '0' ); ?>">
				<label for="smp_media_url"><strong><?php esc_html_e( 'Media URL', 'social-media-posts' ); ?></strong></label>
				<div class="smp-media-url__row">
					<input type="url" id="smp_media_url" name="smp_media_url" value="<?php echo esc_attr( $media_url ); ?>" class="large-text" placeholder="https://" />
					<button type="button" class="button smp-fetch-oembed"><?php esc_html_e( 'Fetch from URL', 'social-media-posts' ); ?></button>
				</div>
				<p class="description"><?php esc_html_e( 'Click "Fetch from URL" to try to auto-capture a thumbnail from the Post URL above (works for YouTube, Vimeo, and other oEmbed-friendly platforms). For Instagram, Facebook, or TikTok you may need to enter the image URL manually.', 'social-media-posts' ); ?></p>
				<div class="smp-fetch-status" role="status" aria-live="polite"></div>
			</div>

			<div class="smp-preview" <?php echo $preview_url ? '' : 'hidden'; ?>>
				<strong><?php esc_html_e( 'Preview', 'social-media-posts' ); ?></strong>
				<div class="smp-preview__inner">
					<?php if ( $preview_url ) : ?>
						<?php if ( $media_type === 'video' && $media_source === 'library' && $attachment_id ) : ?>
							<video src="<?php echo esc_url( $preview_url ); ?>" controls></video>
						<?php else : ?>
							<img src="<?php echo esc_url( $preview_url ); ?>" alt="" />
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	public function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$description = isset( $_POST['smp_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['smp_description'] ) ) : '';
		update_post_meta( $post_id, '_smp_description', $description );

		$author_name = isset( $_POST['smp_author_name'] ) ? sanitize_text_field( wp_unslash( $_POST['smp_author_name'] ) ) : '';
		update_post_meta( $post_id, '_smp_author_name', $author_name );

		$author_bio = isset( $_POST['smp_author_bio'] ) ? sanitize_text_field( wp_unslash( $_POST['smp_author_bio'] ) ) : '';
		update_post_meta( $post_id, '_smp_author_bio', $author_bio );

		$author_handle_raw = isset( $_POST['smp_author_handle'] ) ? sanitize_text_field( wp_unslash( $_POST['smp_author_handle'] ) ) : '';
		$author_handle     = ltrim( $author_handle_raw, '@' );
		update_post_meta( $post_id, '_smp_author_handle', $author_handle );

		$url = isset( $_POST['smp_url'] ) ? esc_url_raw( wp_unslash( $_POST['smp_url'] ) ) : '';
		update_post_meta( $post_id, '_smp_url', $url );

		$platform_raw = isset( $_POST['smp_platform'] ) ? sanitize_key( wp_unslash( $_POST['smp_platform'] ) ) : '';
		$platform     = array_key_exists( $platform_raw, Plugin::platforms() ) ? $platform_raw : '';
		update_post_meta( $post_id, '_smp_platform', $platform );

		$media_type   = isset( $_POST['smp_media_type'] ) ? sanitize_key( wp_unslash( $_POST['smp_media_type'] ) ) : 'image';
		$media_type   = in_array( $media_type, [ 'image', 'video' ], true ) ? $media_type : 'image';
		update_post_meta( $post_id, '_smp_media_type', $media_type );

		$media_source = isset( $_POST['smp_media_source'] ) ? sanitize_key( wp_unslash( $_POST['smp_media_source'] ) ) : 'library';
		$media_source = in_array( $media_source, [ 'library', 'url' ], true ) ? $media_source : 'library';
		update_post_meta( $post_id, '_smp_media_source', $media_source );

		$attachment_id = isset( $_POST['smp_media_attachment_id'] ) ? absint( $_POST['smp_media_attachment_id'] ) : 0;
		update_post_meta( $post_id, '_smp_media_attachment_id', $attachment_id );

		$media_url = isset( $_POST['smp_media_url'] ) ? esc_url_raw( wp_unslash( $_POST['smp_media_url'] ) ) : '';
		update_post_meta( $post_id, '_smp_media_url', $media_url );

		$collection_ids = isset( $_POST['smp_collections'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['smp_collections'] ) ) : [];
		if ( ! empty( $collection_ids ) ) {
			wp_set_post_terms( $post_id, $collection_ids, 'social_media_collection', false );
		} else {
			wp_delete_object_term_relationships( $post_id, 'social_media_collection' );
		}
	}

	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== SMP_POST_TYPE ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'smp-admin',
			SMP_URL . 'assets/admin.css',
			[],
			SMP_VERSION
		);

		wp_enqueue_script(
			'smp-admin',
			SMP_URL . 'assets/admin.js',
			[ 'jquery', 'media-editor' ],
			SMP_VERSION,
			true
		);

		wp_localize_script( 'smp-admin', 'SMP_Admin', [
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'smp_fetch_oembed' ),
			'i18n'      => [
				'pickImage'   => __( 'Select an image', 'social-media-posts' ),
				'pickVideo'   => __( 'Select a video', 'social-media-posts' ),
				'useThis'     => __( 'Use this media', 'social-media-posts' ),
				'fetching'    => __( 'Fetching…', 'social-media-posts' ),
				'fetchFailed' => __( 'Could not fetch a thumbnail from this URL. Enter the media URL manually.', 'social-media-posts' ),
				'fetchOk'     => __( 'Thumbnail captured.', 'social-media-posts' ),
				'enterUrl'    => __( 'Enter the Post URL first.', 'social-media-posts' ),
			],
		] );
	}
}
