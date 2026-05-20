<?php

namespace SocialMediaPosts;

defined( 'ABSPATH' ) || exit;

class Admin {

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
	}

	public function register_menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . SMP_POST_TYPE,
			__( 'Shortcodes Info', 'social-media-posts' ),
			__( 'Shortcodes Info', 'social-media-posts' ),
			'manage_options',
			'smp-shortcodes-info',
			[ $this, 'display_page' ]
		);
	}

	public function enqueue_styles( string $hook ): void {
		if ( strpos( $hook, 'smp-shortcodes-info' ) === false ) {
			return;
		}
		wp_enqueue_style(
			'smp-admin',
			SMP_URL . 'assets/admin.css',
			[],
			SMP_VERSION
		);
	}

	public function display_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'social-media-posts' ) );
		}

		$platforms = Plugin::platforms();
		?>
		<div class="wrap smp-admin-wrap">
			<h1><?php esc_html_e( 'Social Media Posts - Shortcodes', 'social-media-posts' ); ?></h1>

			<div class="smp-info-box">
				<h2><?php esc_html_e( 'How to Use Shortcodes', 'social-media-posts' ); ?></h2>
				<p><?php esc_html_e( 'Copy and paste the shortcodes below into your pages or posts to display your curated social media posts.', 'social-media-posts' ); ?></p>
			</div>

			<!-- SMP Grid Shortcode -->
			<div class="smp-shortcode-section">
				<h3><?php esc_html_e( '1. Grid Display', 'social-media-posts' ); ?></h3>
				<p><?php esc_html_e( 'Display social media posts in a responsive grid layout.', 'social-media-posts' ); ?></p>

				<div class="smp-shortcode-block">
					<div class="smp-shortcode-code">
						<code>[smp_grid]</code>
						<button class="smp-copy-btn" onclick="smpCopyToClipboard('[smp_grid]')" aria-label="<?php esc_attr_e( 'Copy shortcode', 'social-media-posts' ); ?>">
							<?php esc_html_e( 'Copy', 'social-media-posts' ); ?>
						</button>
					</div>
				</div>

				<h4><?php esc_html_e( 'Available Attributes:', 'social-media-posts' ); ?></h4>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Attribute', 'social-media-posts' ); ?></th>
							<th><?php esc_html_e( 'Default', 'social-media-posts' ); ?></th>
							<th><?php esc_html_e( 'Description', 'social-media-posts' ); ?></th>
							<th><?php esc_html_e( 'Example', 'social-media-posts' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>limit</code></td>
							<td><code>-1</code> (all)</td>
							<td><?php esc_html_e( 'Number of posts to display', 'social-media-posts' ); ?></td>
							<td><code>limit="12"</code></td>
						</tr>
						<tr>
							<td><code>columns</code></td>
							<td><code>3</code></td>
							<td><?php esc_html_e( 'Number of columns (1-4)', 'social-media-posts' ); ?></td>
							<td><code>columns="4"</code></td>
						</tr>
						<tr>
							<td><code>platform</code></td>
							<td><code>''</code> (all)</td>
							<td><?php esc_html_e( 'Filter by platform', 'social-media-posts' ); ?></td>
							<td><code>platform="instagram"</code></td>
						</tr>
						<tr>
							<td><code>category</code></td>
							<td><code>''</code> (all)</td>
							<td><?php esc_html_e( 'Filter by category slug', 'social-media-posts' ); ?></td>
							<td><code>category="lifestyle"</code></td>
						</tr>
						<tr>
							<td><code>orderby</code></td>
							<td><code>date</code></td>
							<td><?php esc_html_e( 'Sort posts by (date, title)', 'social-media-posts' ); ?></td>
							<td><code>orderby="title"</code></td>
						</tr>
						<tr>
							<td><code>order</code></td>
							<td><code>DESC</code></td>
							<td><?php esc_html_e( 'Sort direction (ASC or DESC)', 'social-media-posts' ); ?></td>
							<td><code>order="ASC"</code></td>
						</tr>
					</tbody>
				</table>

				<h4><?php esc_html_e( 'Examples:', 'social-media-posts' ); ?></h4>
				<div class="smp-examples">
					<div class="smp-example-item">
						<strong><?php esc_html_e( 'Show 12 Instagram posts in 4 columns:', 'social-media-posts' ); ?></strong>
						<div class="smp-shortcode-block">
							<code>[smp_grid limit="12" columns="4" platform="instagram"]</code>
							<button class="smp-copy-btn" onclick="smpCopyToClipboard('[smp_grid limit=&quot;12&quot; columns=&quot;4&quot; platform=&quot;instagram&quot;]')" aria-label="<?php esc_attr_e( 'Copy shortcode', 'social-media-posts' ); ?>">
								<?php esc_html_e( 'Copy', 'social-media-posts' ); ?>
							</button>
						</div>
					</div>
					<div class="smp-example-item">
						<strong><?php esc_html_e( 'Show posts from "Featured" category, sorted alphabetically:', 'social-media-posts' ); ?></strong>
						<div class="smp-shortcode-block">
							<code>[smp_grid category="featured" orderby="title"]</code>
							<button class="smp-copy-btn" onclick="smpCopyToClipboard('[smp_grid category=&quot;featured&quot; orderby=&quot;title&quot;]')" aria-label="<?php esc_attr_e( 'Copy shortcode', 'social-media-posts' ); ?>">
								<?php esc_html_e( 'Copy', 'social-media-posts' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>

			<!-- SMP Carousel Shortcode -->
			<div class="smp-shortcode-section">
				<h3><?php esc_html_e( '2. Carousel Display', 'social-media-posts' ); ?></h3>
				<p><?php esc_html_e( 'Display social media posts in an interactive carousel/slider.', 'social-media-posts' ); ?></p>

				<div class="smp-shortcode-block">
					<div class="smp-shortcode-code">
						<code>[smp_carousel]</code>
						<button class="smp-copy-btn" onclick="smpCopyToClipboard('[smp_carousel]')" aria-label="<?php esc_attr_e( 'Copy shortcode', 'social-media-posts' ); ?>">
							<?php esc_html_e( 'Copy', 'social-media-posts' ); ?>
						</button>
					</div>
				</div>

				<h4><?php esc_html_e( 'Available Attributes:', 'social-media-posts' ); ?></h4>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Attribute', 'social-media-posts' ); ?></th>
							<th><?php esc_html_e( 'Default', 'social-media-posts' ); ?></th>
							<th><?php esc_html_e( 'Description', 'social-media-posts' ); ?></th>
							<th><?php esc_html_e( 'Example', 'social-media-posts' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>limit</code></td>
							<td><code>-1</code> (all)</td>
							<td><?php esc_html_e( 'Number of posts to display', 'social-media-posts' ); ?></td>
							<td><code>limit="20"</code></td>
						</tr>
						<tr>
							<td><code>visible</code></td>
							<td><code>5</code></td>
							<td><?php esc_html_e( 'Visible items at once (3-5)', 'social-media-posts' ); ?></td>
							<td><code>visible="3"</code></td>
						</tr>
						<tr>
							<td><code>loop</code></td>
							<td><code>true</code></td>
							<td><?php esc_html_e( 'Loop carousel (true or false)', 'social-media-posts' ); ?></td>
							<td><code>loop="false"</code></td>
						</tr>
						<tr>
							<td><code>platform</code></td>
							<td><code>''</code> (all)</td>
							<td><?php esc_html_e( 'Filter by platform', 'social-media-posts' ); ?></td>
							<td><code>platform="tiktok"</code></td>
						</tr>
						<tr>
							<td><code>category</code></td>
							<td><code>''</code> (all)</td>
							<td><?php esc_html_e( 'Filter by category slug', 'social-media-posts' ); ?></td>
							<td><code>category="lifestyle"</code></td>
						</tr>
						<tr>
							<td><code>orderby</code></td>
							<td><code>date</code></td>
							<td><?php esc_html_e( 'Sort posts by (date, title)', 'social-media-posts' ); ?></td>
							<td><code>orderby="title"</code></td>
						</tr>
						<tr>
							<td><code>order</code></td>
							<td><code>DESC</code></td>
							<td><?php esc_html_e( 'Sort direction (ASC or DESC)', 'social-media-posts' ); ?></td>
							<td><code>order="ASC"</code></td>
						</tr>
					</tbody>
				</table>

				<h4><?php esc_html_e( 'Examples:', 'social-media-posts' ); ?></h4>
				<div class="smp-examples">
					<div class="smp-example-item">
						<strong><?php esc_html_e( 'Show TikTok videos, 4 visible at a time:', 'social-media-posts' ); ?></strong>
						<div class="smp-shortcode-block">
							<code>[smp_carousel visible="4" platform="tiktok"]</code>
							<button class="smp-copy-btn" onclick="smpCopyToClipboard('[smp_carousel visible=&quot;4&quot; platform=&quot;tiktok&quot;]')" aria-label="<?php esc_attr_e( 'Copy shortcode', 'social-media-posts' ); ?>">
								<?php esc_html_e( 'Copy', 'social-media-posts' ); ?>
							</button>
						</div>
					</div>
					<div class="smp-example-item">
						<strong><?php esc_html_e( 'Show recent posts, no looping:', 'social-media-posts' ); ?></strong>
						<div class="smp-shortcode-block">
							<code>[smp_carousel limit="10" loop="false"]</code>
							<button class="smp-copy-btn" onclick="smpCopyToClipboard('[smp_carousel limit=&quot;10&quot; loop=&quot;false&quot;]')" aria-label="<?php esc_attr_e( 'Copy shortcode', 'social-media-posts' ); ?>">
								<?php esc_html_e( 'Copy', 'social-media-posts' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>

			<!-- Platforms Reference -->
			<div class="smp-shortcode-section">
				<h3><?php esc_html_e( 'Supported Platforms', 'social-media-posts' ); ?></h3>
				<p><?php esc_html_e( 'Use these platform values with the "platform" attribute:', 'social-media-posts' ); ?></p>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Platform Value', 'social-media-posts' ); ?></th>
							<th><?php esc_html_e( 'Display Name', 'social-media-posts' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $platforms as $key => $label ) : ?>
							<tr>
								<td><code><?php echo esc_html( $key ); ?></code></td>
								<td><?php echo esc_html( $label ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<!-- Quick Tips -->
			<div class="smp-shortcode-section smp-tips-section">
				<h3><?php esc_html_e( 'Quick Tips', 'social-media-posts' ); ?></h3>
				<ul class="smp-tips-list">
					<li><?php esc_html_e( 'Combine attributes using spaces: [smp_grid columns="2" limit="8" platform="instagram"]', 'social-media-posts' ); ?></li>
					<li><?php esc_html_e( 'Use category slugs from Social Media Collections (not display names)', 'social-media-posts' ); ?></li>
					<li><?php esc_html_e( 'Set limit="-1" to show all posts (default behavior)', 'social-media-posts' ); ?></li>
					<li><?php esc_html_e( 'Carousel visible attribute only accepts values between 3-5', 'social-media-posts' ); ?></li>
					<li><?php esc_html_e( 'Grid columns only accepts values between 1-4', 'social-media-posts' ); ?></li>
				</ul>
			</div>
		</div>

		<script>
			function smpCopyToClipboard( text ) {
				const textarea = document.createElement( 'textarea' );
				textarea.value = text;
				document.body.appendChild( textarea );
				textarea.select();
				document.execCommand( 'copy' );
				document.body.removeChild( textarea );

				alert( '<?php esc_html_e( 'Shortcode copied to clipboard!', 'social-media-posts' ); ?>' );
			}
		</script>
		<?php
	}
}
