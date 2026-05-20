<?php

namespace SocialMediaPosts;

defined( 'ABSPATH' ) || exit;

class Grid {

	public function register(): void {
		add_shortcode( 'smp_grid', [ $this, 'render' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
	}

	public function register_assets(): void {
		wp_register_style(
			'smp-grid',
			SMP_URL . 'assets/grid.css',
			[],
			SMP_VERSION
		);
	}

	public function render( array $atts ): string {
		$atts = shortcode_atts( [
			'limit'      => -1,
			'platform'   => '',
			'category'   => '',
			'columns'    => 3,
			'orderby'    => 'date',
			'order'      => 'DESC',
		], $atts, 'smp_grid' );

		$columns = max( 1, min( 4, (int) $atts['columns'] ) );

		wp_enqueue_style( 'smp-grid' );

		$query_args = [
			'post_type'      => SMP_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => (int) $atts['limit'],
			'orderby'        => sanitize_key( $atts['orderby'] ),
			'order'          => strtoupper( $atts['order'] ) === 'ASC' ? 'ASC' : 'DESC',
			'no_found_rows'  => true,
		];

		if ( ! empty( $atts['platform'] ) ) {
			$query_args['meta_query'] = [ [
				'key'   => '_smp_platform',
				'value' => sanitize_key( $atts['platform'] ),
			] ];
		}

		if ( ! empty( $atts['category'] ) ) {
			$query_args['tax_query'] = [ [
				'taxonomy' => 'social_media_collection',
				'field'    => 'slug',
				'terms'    => sanitize_key( $atts['category'] ),
			] ];
		}

		$posts = get_posts( $query_args );
		if ( ! $posts ) {
			return '';
		}

		ob_start();
		?>
		<div
			class="smp-grid"
			role="region"
			aria-label="<?php esc_attr_e( 'Social Media Posts Grid', 'social-media-posts' ); ?>"
			style="--smp-columns: <?php echo esc_attr( (string) $columns ); ?>;"
		>
			<?php foreach ( $posts as $post ) : ?>
				<?php $this->render_card( $post ); ?>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	private function render_card( \WP_Post $post ): void {
		$attach_id   = (int) get_post_meta( $post->ID, '_smp_media_attachment_id', true );
		$media_url   = (string) get_post_meta( $post->ID, '_smp_media_url', true );
		$handle      = (string) get_post_meta( $post->ID, '_smp_author_handle', true );
		$description = (string) get_post_meta( $post->ID, '_smp_description', true );
		$platform    = (string) get_post_meta( $post->ID, '_smp_platform', true );
		$post_url    = (string) get_post_meta( $post->ID, '_smp_url', true );

		$img_src = '';
		if ( $attach_id ) {
			$img_data = wp_get_attachment_image_src( $attach_id, 'medium' );
			if ( $img_data ) {
				$img_src = $img_data[0];
			}
		}
		if ( ! $img_src && $media_url ) {
			$img_src = $media_url;
		}
		if ( ! $img_src ) {
			$thumb_id = (int) get_post_thumbnail_id( $post->ID );
			if ( $thumb_id ) {
				$img_data = wp_get_attachment_image_src( $thumb_id, 'medium' );
				if ( $img_data ) {
					$img_src = $img_data[0];
				}
			}
		}

		$desc_short = mb_strlen( $description ) > 100
			? mb_substr( $description, 0, 97 ) . '…'
			: $description;

		$platforms      = Plugin::platforms();
		$platform_label = $platforms[ $platform ] ?? '';

		$card_tag  = $post_url ? 'a' : 'div';
		$card_attr = $post_url
			? sprintf( ' href="%s" target="_blank" rel="noopener noreferrer"', esc_url( $post_url ) )
			: '';
		?>
		<<?php echo $card_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag name is safe ?><?php echo $card_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped ?> class="smp-grid__card">
			<div class="smp-grid__media">
				<?php if ( $img_src ) : ?>
					<img
						src="<?php echo esc_url( $img_src ); ?>"
						alt="<?php echo esc_attr( $handle ? '@' . $handle : $post->post_title ); ?>"
						class="smp-grid__image"
						loading="lazy"
						decoding="async"
					>
				<?php else : ?>
					<div class="smp-grid__image-placeholder" aria-hidden="true">
						<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
					</div>
				<?php endif; ?>
				<?php if ( $platform_label ) : ?>
					<span class="smp-grid__platform smp-grid__platform--<?php echo esc_attr( $platform ); ?>"><?php echo esc_html( $platform_label ); ?></span>
				<?php endif; ?>
			</div>
			<div class="smp-grid__body">
				<?php if ( $handle ) : ?>
					<p class="smp-grid__handle">@<?php echo esc_html( $handle ); ?></p>
				<?php endif; ?>
				<?php if ( $desc_short ) : ?>
					<p class="smp-grid__description"><?php echo esc_html( $desc_short ); ?></p>
				<?php endif; ?>
			</div>
		</<?php echo $card_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- safe ?>>
		<?php
	}
}
