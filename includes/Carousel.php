<?php

namespace SocialMediaPosts;

defined( 'ABSPATH' ) || exit;

class Carousel {

	public function register(): void {
		add_shortcode( 'smp_carousel', [ $this, 'render' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
	}

	public function register_assets(): void {
		wp_register_script(
			'gsap',
			'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js',
			[],
			'3.12.5',
			true
		);
		wp_register_script(
			'gsap-observer',
			'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/Observer.min.js',
			[ 'gsap' ],
			'3.12.5',
			true
		);
		wp_register_script(
			'smp-carousel',
			SMP_URL . 'assets/carousel.js',
			[ 'gsap', 'gsap-observer' ],
			SMP_VERSION,
			true
		);
		wp_register_style(
			'smp-carousel',
			SMP_URL . 'assets/carousel.css',
			[],
			SMP_VERSION
		);
	}

	public function render( array $atts ): string {
		$atts = shortcode_atts( [
			'limit'      => -1,
			'platform'   => '',
			'category'   => '',
			'orderby'    => 'date',
			'order'      => 'DESC',
			'loop'       => 'true',
			'visible'    => 5,
		], $atts, 'smp_carousel' );

		$visible = max( 3, min( 5, (int) $atts['visible'] ) );

		wp_enqueue_script( 'smp-carousel' );
		wp_enqueue_style( 'smp-carousel' );

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
			class="smp-carousel is-loading"
			role="region"
			aria-label="<?php esc_attr_e( 'Social Media Posts', 'social-media-posts' ); ?>"
			aria-busy="true"
			data-loop="<?php echo esc_attr( filter_var( $atts['loop'], FILTER_VALIDATE_BOOLEAN ) ? '1' : '0' ); ?>"
			data-visible="<?php echo esc_attr( (string) $visible ); ?>"
		>
			<div class="smp-carousel__track">
				<?php foreach ( $posts as $post ) : ?>
					<?php $this->render_card( $post ); ?>
				<?php endforeach; ?>
			</div>
			<div class="smp-carousel__loader" aria-hidden="true">
				<span class="smp-carousel__spinner"></span>
			</div>
			<button class="smp-carousel__arrow smp-carousel__arrow--prev" aria-label="<?php esc_attr_e( 'Previous post', 'social-media-posts' ); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
			</button>
			<button class="smp-carousel__arrow smp-carousel__arrow--next" aria-label="<?php esc_attr_e( 'Next post', 'social-media-posts' ); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
			</button>
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
			$img_data = wp_get_attachment_image_src( $attach_id, 'large' );
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
				$img_data = wp_get_attachment_image_src( $thumb_id, 'large' );
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
		<<?php echo $card_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag name is safe (literal 'a' or 'div') ?><?php echo $card_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped above ?> class="smp-carousel__card">
			<div class="smp-carousel__media">
				<?php if ( $img_src ) : ?>
					<img
						src="<?php echo esc_url( $img_src ); ?>"
						alt="<?php echo esc_attr( $handle ? '@' . $handle : $post->post_title ); ?>"
						class="smp-carousel__image"
						loading="lazy"
						decoding="async"
					>
				<?php else : ?>
					<div class="smp-carousel__image-placeholder" aria-hidden="true">
						<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
					</div>
				<?php endif; ?>
				<?php if ( $platform_label ) : ?>
					<span class="smp-carousel__platform smp-carousel__platform--<?php echo esc_attr( $platform ); ?>"><?php echo esc_html( $platform_label ); ?></span>
				<?php endif; ?>
			</div>
			<div class="smp-carousel__body">
				<?php if ( $handle ) : ?>
					<p class="smp-carousel__handle">@<?php echo esc_html( $handle ); ?></p>
				<?php endif; ?>
				<?php if ( $desc_short ) : ?>
					<p class="smp-carousel__description"><?php echo esc_html( $desc_short ); ?></p>
				<?php endif; ?>
			</div>
		</<?php echo $card_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- safe ?>>
		<?php
	}
}
