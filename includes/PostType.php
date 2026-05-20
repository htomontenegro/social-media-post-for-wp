<?php

namespace SocialMediaPosts;

defined( 'ABSPATH' ) || exit;

class PostType {

	public function register(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_meta' ] );
		add_action( 'init', [ $this, 'register_taxonomy' ] );
		add_filter( 'manage_' . SMP_POST_TYPE . '_posts_columns', [ $this, 'columns' ] );
		add_action( 'manage_' . SMP_POST_TYPE . '_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
	}

	public function register_post_type(): void {
		$labels = [
			'name'                  => _x( 'Social Media Posts', 'Post Type General Name', 'social-media-posts' ),
			'singular_name'         => _x( 'Social Media Post', 'Post Type Singular Name', 'social-media-posts' ),
			'menu_name'             => __( 'Social Media Posts', 'social-media-posts' ),
			'name_admin_bar'        => __( 'Social Media Post', 'social-media-posts' ),
			'add_new'               => __( 'Add New', 'social-media-posts' ),
			'add_new_item'          => __( 'Add New Social Media Post', 'social-media-posts' ),
			'new_item'              => __( 'New Social Media Post', 'social-media-posts' ),
			'edit_item'             => __( 'Edit Social Media Post', 'social-media-posts' ),
			'view_item'             => __( 'View Social Media Post', 'social-media-posts' ),
			'all_items'             => __( 'All Social Media Posts', 'social-media-posts' ),
			'search_items'          => __( 'Search Social Media Posts', 'social-media-posts' ),
			'not_found'             => __( 'No social media posts found.', 'social-media-posts' ),
			'not_found_in_trash'    => __( 'No social media posts found in Trash.', 'social-media-posts' ),
			'featured_image'        => __( 'Featured Image', 'social-media-posts' ),
			'archives'              => __( 'Social Media Post Archives', 'social-media-posts' ),
		];

		register_post_type( SMP_POST_TYPE, [
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'show_in_admin_bar'   => true,
			'has_archive'         => true,
			'hierarchical'        => false,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-share',
			'capability_type'     => 'post',
			'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
			'rewrite'             => [ 'slug' => 'social-media-posts' ],
		] );
	}

	public function register_taxonomy(): void {
		$labels = [
			'name'                       => _x( 'Collections', 'Taxonomy General Name', 'social-media-posts' ),
			'singular_name'              => _x( 'Collection', 'Taxonomy Singular Name', 'social-media-posts' ),
			'menu_name'                  => __( 'Collections', 'social-media-posts' ),
			'all_items'                  => __( 'All Collections', 'social-media-posts' ),
			'parent_item'                => __( 'Parent Collection', 'social-media-posts' ),
			'parent_item_colon'          => __( 'Parent Collection:', 'social-media-posts' ),
			'new_item_name'              => __( 'New Collection Name', 'social-media-posts' ),
			'add_new_item'               => __( 'Add New Collection', 'social-media-posts' ),
			'edit_item'                  => __( 'Edit Collection', 'social-media-posts' ),
			'update_item'                => __( 'Update Collection', 'social-media-posts' ),
			'view_item'                  => __( 'View Collection', 'social-media-posts' ),
			'separate_items_with_commas' => __( 'Separate collections with commas', 'social-media-posts' ),
			'add_or_remove_items'        => __( 'Add or remove collections', 'social-media-posts' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'social-media-posts' ),
			'popular_items'              => __( 'Popular Collections', 'social-media-posts' ),
			'search_items'               => __( 'Search Collections', 'social-media-posts' ),
			'not_found'                  => __( 'Not Found', 'social-media-posts' ),
			'no_terms'                   => __( 'No collections', 'social-media-posts' ),
			'items_list'                 => __( 'Collections list', 'social-media-posts' ),
			'items_list_navigation'      => __( 'Collections list navigation', 'social-media-posts' ),
		];

		register_taxonomy( 'social_media_collection', SMP_POST_TYPE, [
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'show_in_nav_menus' => false,
			'show_in_rest'      => true,
			'query_var'         => 'social_media_collection',
			'rewrite'           => [ 'slug' => 'social-collection' ],
			'show_admin_column' => true,
		] );
	}

	public function register_meta(): void {
		$auth = function () {
			return current_user_can( 'edit_posts' );
		};

		$strings = [
			'_smp_description'         => 'string',
			'_smp_url'                 => 'string',
			'_smp_platform'            => 'string',
			'_smp_media_type'          => 'string',
			'_smp_media_source'        => 'string',
			'_smp_media_url'           => 'string',
			'_smp_author_name'         => 'string',
			'_smp_author_bio'          => 'string',
			'_smp_author_handle'       => 'string',
		];
		foreach ( $strings as $key => $type ) {
			register_post_meta( SMP_POST_TYPE, $key, [
				'type'              => $type,
				'single'            => true,
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
				'sanitize_callback' => 'sanitize_text_field',
			] );
		}

		register_post_meta( SMP_POST_TYPE, '_smp_media_attachment_id', [
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'auth_callback'     => $auth,
			'sanitize_callback' => 'absint',
		] );
	}

	public function columns( array $columns ): array {
		$reordered = [];
		foreach ( $columns as $key => $label ) {
			if ( $key === 'title' ) {
				$reordered['smp_thumb'] = __( 'Thumb', 'social-media-posts' );
			}
			$reordered[ $key ] = $label;
			if ( $key === 'title' ) {
				$reordered['smp_platform'] = __( 'Platform', 'social-media-posts' );
				$reordered['smp_url']      = __( 'URL', 'social-media-posts' );
			}
		}
		return $reordered;
	}

	public function render_column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'smp_thumb':
				$attachment_id = (int) get_post_meta( $post_id, '_smp_media_attachment_id', true );
				$url           = (string) get_post_meta( $post_id, '_smp_media_url', true );
				if ( $attachment_id ) {
					echo wp_get_attachment_image( $attachment_id, [ 60, 60 ] );
				} elseif ( $url ) {
					printf(
						'<img src="%s" alt="" style="max-width:60px;max-height:60px;object-fit:cover;" />',
						esc_url( $url )
					);
				} elseif ( has_post_thumbnail( $post_id ) ) {
					echo get_the_post_thumbnail( $post_id, [ 60, 60 ] );
				} else {
					echo '&mdash;';
				}
				break;

			case 'smp_platform':
				$platform  = (string) get_post_meta( $post_id, '_smp_platform', true );
				$platforms = Plugin::platforms();
				echo esc_html( $platforms[ $platform ] ?? '—' );
				break;

			case 'smp_url':
				$url = (string) get_post_meta( $post_id, '_smp_url', true );
				if ( $url ) {
					printf(
						'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
						esc_url( $url ),
						esc_html( $this->shorten_url( $url ) )
					);
				} else {
					echo '&mdash;';
				}
				break;
		}
	}

	private function shorten_url( string $url, int $max = 40 ): string {
		$display = preg_replace( '#^https?://#i', '', $url );
		if ( strlen( $display ) <= $max ) {
			return $display;
		}
		return substr( $display, 0, $max - 1 ) . '…';
	}
}
