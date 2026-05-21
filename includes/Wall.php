<?php

namespace SocialMediaPosts;

defined( 'ABSPATH' ) || exit;

class Wall {

	public function register(): void {
		add_shortcode( 'smp_wall', [ $this, 'render' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
	}

	public function register_assets(): void {
		wp_register_style(
			'smp-wall',
			SMP_URL . 'assets/wall.css',
			[],
			$this->asset_version( 'assets/wall.css' )
		);
		wp_register_script(
			'smp-wall',
			SMP_URL . 'assets/wall.js',
			[],
			$this->asset_version( 'assets/wall.js' ),
			true
		);
	}

	private function asset_version( string $relative ): string {
		$path = SMP_PATH . $relative;
		return file_exists( $path ) ? (string) filemtime( $path ) : SMP_VERSION;
	}

	public function render( $atts, string $content = '' ): string {
		$atts = shortcode_atts( [
			'columns'    => 8,
			'rows'       => 6,
			'gap'        => 4,
			'interval'   => 7800,
			'swaps'      => 3,
			'opacity'    => 1,
			'background' => '#0b0b0b',
			'blur'       => 0,
			'limit'      => -1,
			'platform'   => '',
			'category'   => '',
		], $atts, 'smp_wall' );

		$columns    = max( 2, min( 16, (int) $atts['columns'] ) );
		$rows       = max( 2, min( 16, (int) $atts['rows'] ) );
		$gap        = max( 0, min( 40, (int) $atts['gap'] ) );
		$interval   = max( 600, min( 60000, (int) $atts['interval'] ) );
		$swaps      = max( 1, min( 30, (int) $atts['swaps'] ) );
		$opacity    = number_format( max( 0, min( 1, (float) $atts['opacity'] ) ), 2, '.', '' );
		$blur       = max( 0, min( 50, (int) $atts['blur'] ) );
		$background = $this->sanitize_color( (string) $atts['background'], '#0b0b0b' );

		wp_enqueue_style( 'smp-wall' );
		wp_enqueue_script( 'smp-wall' );

		$query_args = [
			'post_type'      => SMP_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => (int) $atts['limit'],
			'orderby'        => 'date',
			'order'          => 'DESC',
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

		// Randomise the pool, then repeat it as needed so every cell in the
		// grid is filled even when there are fewer posts than cells. A final
		// shuffle scatters any repeats so they do not line up in a pattern.
		shuffle( $posts );
		$needed = $columns * $rows;
		$count  = count( $posts );
		$tiles  = [];
		for ( $i = 0; $i < $needed; $i++ ) {
			$tiles[] = $posts[ $i % $count ];
		}
		shuffle( $tiles );

		// Responsive column counts. Emitted as literal integers in a scoped
		// <style> block because CSS repeat() rejects calc()/min() as a count,
		// and never increase a column count the author set below the cap.
		$uid      = wp_unique_id( 'smp-wall-' );
		$cols_lg  = $columns;
		$cols_md  = min( $columns, 8 );
		$cols_sm  = min( $columns, 6 );
		$cols_xs  = min( $columns, 4 );

		ob_start();
		?>
		<style>
			#<?php echo esc_html( $uid ); ?> { --smp-wall-cols: <?php echo esc_html( (string) $cols_lg ); ?>; --smp-wall-rows: <?php echo esc_html( (string) $rows ); ?>; --smp-wall-gap: <?php echo esc_html( (string) $gap ); ?>px; --smp-wall-bg: <?php echo esc_html( $background ); ?>; --smp-wall-opacity: <?php echo esc_html( $opacity ); ?>; --smp-wall-blur: <?php echo esc_html( (string) $blur ); ?>px; }
			@media (max-width: 1200px) { #<?php echo esc_html( $uid ); ?> { --smp-wall-cols: <?php echo esc_html( (string) $cols_md ); ?>; } }
			@media (max-width: 900px)  { #<?php echo esc_html( $uid ); ?> { --smp-wall-cols: <?php echo esc_html( (string) $cols_sm ); ?>; } }
			@media (max-width: 600px)  { #<?php echo esc_html( $uid ); ?> { --smp-wall-cols: <?php echo esc_html( (string) $cols_xs ); ?>; } }
		</style>
		<div
			id="<?php echo esc_attr( $uid ); ?>"
			class="smp-wall"
			data-interval="<?php echo esc_attr( (string) $interval ); ?>"
			data-swaps="<?php echo esc_attr( (string) $swaps ); ?>"
		>
			<div class="smp-wall__grid" role="presentation" aria-hidden="true">
				<?php foreach ( $tiles as $tile_post ) : ?>
					<?php $this->render_tile( $tile_post ); ?>
				<?php endforeach; ?>
			</div>
			<?php if ( '' !== trim( (string) $content ) ) : ?>
				<div class="smp-wall__scrim" aria-hidden="true"></div>
				<div class="smp-wall__overlay"><?php echo do_shortcode( $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- enclosed shortcode content authored in the editor ?></div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	private function render_tile( \WP_Post $post ): void {
		$img_src = $this->image_src( $post );
		?>
		<div class="smp-wall__tile">
			<?php if ( $img_src ) : ?>
				<img
					src="<?php echo esc_url( $img_src ); ?>"
					alt=""
					class="smp-wall__img"
					loading="lazy"
					decoding="async"
				>
			<?php else : ?>
				<div class="smp-wall__img smp-wall__placeholder" aria-hidden="true">
					<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	private function sanitize_color( string $value, string $default ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return $default;
		}
		// Restrictive whitelist because the value is printed into a <style>
		// block; none of these branches allow ; { } < > to break out.
		if ( preg_match( '/^#[0-9a-fA-F]{3,8}$/', $value ) ) {
			return $value;
		}
		if ( preg_match( '/^[a-zA-Z]+$/', $value ) ) {
			return $value;
		}
		if ( preg_match( '/^(?:rgb|rgba|hsl|hsla)\(\s*[0-9.,%\/\s]+\)$/i', $value ) ) {
			return $value;
		}
		return $default;
	}

	private function image_src( \WP_Post $post ): string {
		$attach_id = (int) get_post_meta( $post->ID, '_smp_media_attachment_id', true );
		if ( $attach_id ) {
			$img_data = wp_get_attachment_image_src( $attach_id, 'medium' );
			if ( $img_data ) {
				return $img_data[0];
			}
		}

		$media_url = (string) get_post_meta( $post->ID, '_smp_media_url', true );
		if ( $media_url ) {
			return $media_url;
		}

		$thumb_id = (int) get_post_thumbnail_id( $post->ID );
		if ( $thumb_id ) {
			$img_data = wp_get_attachment_image_src( $thumb_id, 'medium' );
			if ( $img_data ) {
				return $img_data[0];
			}
		}

		return '';
	}
}
