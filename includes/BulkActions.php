<?php

namespace SocialMediaPosts;

defined( 'ABSPATH' ) || exit;

class BulkActions {

	private const BULK_KEY      = 'smp_publish';
	private const ADMIN_ACTION  = 'smp_publish_all_drafts';
	private const NOTICE_QUERY  = 'smp_bulk_published';

	public function register(): void {
		$post_type = SMP_POST_TYPE;
		add_filter( 'bulk_actions-edit-' . $post_type, [ $this, 'add_bulk_action' ] );
		add_filter( 'handle_bulk_actions-edit-' . $post_type, [ $this, 'handle_bulk_action' ], 10, 3 );
		add_filter( 'views_edit-' . $post_type, [ $this, 'add_publish_all_link' ] );
		add_action( 'admin_post_' . self::ADMIN_ACTION, [ $this, 'handle_publish_all' ] );
		add_action( 'admin_notices', [ $this, 'maybe_render_notice' ] );
	}

	public function add_bulk_action( array $actions ): array {
		$actions[ self::BULK_KEY ] = __( 'Publish', 'social-media-posts' );
		return $actions;
	}

	public function handle_bulk_action( string $redirect_to, string $action, array $post_ids ): string {
		if ( $action !== self::BULK_KEY ) {
			return $redirect_to;
		}
		if ( ! current_user_can( 'publish_posts' ) ) {
			return $redirect_to;
		}

		$count = 0;
		foreach ( $post_ids as $post_id ) {
			$post_id = (int) $post_id;
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			$post = get_post( $post_id );
			if ( ! $post || $post->post_type !== SMP_POST_TYPE ) {
				continue;
			}
			if ( $post->post_status === 'publish' ) {
				continue;
			}
			$updated = wp_update_post( [
				'ID'          => $post_id,
				'post_status' => 'publish',
			], true );
			if ( ! is_wp_error( $updated ) ) {
				$count++;
			}
		}

		return add_query_arg( self::NOTICE_QUERY, $count, $redirect_to );
	}

	public function add_publish_all_link( array $views ): array {
		if ( ! current_user_can( 'publish_posts' ) ) {
			return $views;
		}

		$drafts = wp_count_posts( SMP_POST_TYPE )->draft ?? 0;
		if ( ! $drafts ) {
			return $views;
		}

		$url = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::ADMIN_ACTION ),
			self::ADMIN_ACTION
		);

		$label = sprintf(
			/* translators: %d: number of draft posts */
			_n( 'Publish %d draft', 'Publish all %d drafts', (int) $drafts, 'social-media-posts' ),
			(int) $drafts
		);

		$confirm = esc_js( sprintf(
			/* translators: %d: number of draft posts */
			__( 'Publish all %d draft Social Media Posts now?', 'social-media-posts' ),
			(int) $drafts
		) );

		$views['smp_publish_all'] = sprintf(
			'<a href="%1$s" class="button button-secondary" style="margin-left:6px;" onclick="return confirm(\'%2$s\');">%3$s</a>',
			esc_url( $url ),
			$confirm,
			esc_html( $label )
		);

		return $views;
	}

	public function handle_publish_all(): void {
		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'social-media-posts' ), '', [ 'response' => 403 ] );
		}
		check_admin_referer( self::ADMIN_ACTION );

		$draft_ids = get_posts( [
			'post_type'      => SMP_POST_TYPE,
			'post_status'    => 'draft',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		] );

		$count = 0;
		foreach ( $draft_ids as $post_id ) {
			$post_id = (int) $post_id;
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}
			$updated = wp_update_post( [
				'ID'          => $post_id,
				'post_status' => 'publish',
			], true );
			if ( ! is_wp_error( $updated ) ) {
				$count++;
			}
		}

		$redirect = add_query_arg(
			[
				'post_type'         => SMP_POST_TYPE,
				self::NOTICE_QUERY  => $count,
			],
			admin_url( 'edit.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	public function maybe_render_notice(): void {
		if ( ! isset( $_GET[ self::NOTICE_QUERY ] ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== SMP_POST_TYPE ) {
			return;
		}

		$count = (int) $_GET[ self::NOTICE_QUERY ];
		if ( $count < 1 ) {
			$message = __( 'No drafts were published.', 'social-media-posts' );
			$class   = 'notice notice-warning is-dismissible';
		} else {
			$message = sprintf(
				/* translators: %d: number of published posts */
				_n( 'Published %d Social Media Post.', 'Published %d Social Media Posts.', $count, 'social-media-posts' ),
				$count
			);
			$class = 'notice notice-success is-dismissible';
		}

		printf(
			'<div class="%1$s"><p>%2$s</p></div>',
			esc_attr( $class ),
			esc_html( $message )
		);
	}
}
