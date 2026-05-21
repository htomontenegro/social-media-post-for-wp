<?php

namespace SocialMediaPosts;

defined( 'ABSPATH' ) || exit;

/**
 * Social Links manager: an admin page to curate social URLs and a
 * [smp_social_links] shortcode that renders them as branded or minimalist icons.
 */
class SocialLinks {

	private const OPTION       = 'smp_social_links';
	private const NONCE_ACTION = 'smp_save_social_links';
	private const NONCE_NAME   = 'smp_social_links_nonce';
	private const MENU_SLUG    = 'smp-social-links';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_post_' . self::NONCE_ACTION, [ $this, 'handle_save' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_frontend_assets' ] );
		add_shortcode( 'smp_social_links', [ $this, 'render_shortcode' ] );
		add_shortcode( 'smp_social_icons', [ $this, 'render_shortcode' ] );
	}

	/* ---------------------------------------------------------------------
	 * Icon library
	 * ------------------------------------------------------------------- */

	/**
	 * The known social platforms, in display order, each with a brand colour
	 * and two icon variants: "branded" (solid brand glyph) and "minimal"
	 * (monochrome line glyph).
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function icons(): array {
		$min_attr = 'aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
		$fa_attr  = 'aria-hidden="true" focusable="false" fill="currentColor" xmlns="http://www.w3.org/2000/svg"';

		$globe = "<svg {$min_attr}><circle cx=\"12\" cy=\"12\" r=\"9\"/><line x1=\"3\" y1=\"12\" x2=\"21\" y2=\"12\"/><path d=\"M12 3a14 14 0 0 1 0 18 14 14 0 0 1 0-18z\"/></svg>";

		return [
			'instagram' => [
				'label'   => __( 'Instagram', 'social-media-posts' ),
				'brand'   => '#E4405F',
				'branded' => "<svg {$fa_attr} viewBox=\"0 0 448 512\"><path d=\"M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z\"/></svg>",
				'minimal' => "<svg {$min_attr}><rect x=\"2\" y=\"2\" width=\"20\" height=\"20\" rx=\"5\" ry=\"5\"/><path d=\"M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z\"/><line x1=\"17.5\" y1=\"6.5\" x2=\"17.51\" y2=\"6.5\"/></svg>",
			],
			'facebook'  => [
				'label'   => __( 'Facebook', 'social-media-posts' ),
				'brand'   => '#1877F2',
				'branded' => "<svg {$fa_attr} viewBox=\"0 0 320 512\"><path d=\"M279.14 288l14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S260.43 0 225.36 0c-73.22 0-121.08 44.38-121.08 124.72v70.62H22.89V288h81.39v224h100.17V288z\"/></svg>",
				'minimal' => "<svg {$min_attr}><path d=\"M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z\"/></svg>",
			],
			'x'         => [
				'label'   => __( 'X (Twitter)', 'social-media-posts' ),
				'brand'   => '#000000',
				'branded' => "<svg {$fa_attr} viewBox=\"0 0 512 512\"><path d=\"M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z\"/></svg>",
				'minimal' => "<svg {$min_attr}><line x1=\"4\" y1=\"4\" x2=\"20\" y2=\"20\"/><line x1=\"20\" y1=\"4\" x2=\"4\" y2=\"20\"/></svg>",
			],
			'tiktok'    => [
				'label'   => __( 'TikTok', 'social-media-posts' ),
				'brand'   => '#010101',
				'branded' => "<svg {$fa_attr} viewBox=\"0 0 448 512\"><path d=\"M448 209.91a210.06 210.06 0 0 1-122.77-39.25V349.38A162.55 162.55 0 1 1 185 188.31V278.2a74.62 74.62 0 1 0 52.23 71.18V0l88 0a121.18 121.18 0 0 0 1.86 22.17h0A122.18 122.18 0 0 0 381 102.39a121.43 121.43 0 0 0 67 20.14Z\"/></svg>",
				'minimal' => "<svg {$min_attr}><circle cx=\"9\" cy=\"16\" r=\"4\"/><path d=\"M13 16V4c.5 2.7 2.7 4.5 5.5 4.5\"/></svg>",
			],
			'linkedin'  => [
				'label'   => __( 'LinkedIn', 'social-media-posts' ),
				'brand'   => '#0A66C2',
				'branded' => "<svg {$fa_attr} viewBox=\"0 0 448 512\"><path d=\"M100.28 448H7.4V148.9h92.88zM53.79 108.1C24.09 108.1 0 83.5 0 53.8a53.79 53.79 0 0 1 107.58 0c0 29.7-24.1 54.3-53.79 54.3zM447.9 448h-92.68V302.4c0-34.7-.7-79.2-48.29-79.2-48.29 0-55.69 37.7-55.69 76.7V448h-92.78V148.9h89.08v40.8h1.3c12.4-23.5 42.69-48.3 87.88-48.3 94 0 111.28 61.9 111.28 142.3V448z\"/></svg>",
				'minimal' => "<svg {$min_attr}><path d=\"M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z\"/><rect x=\"2\" y=\"9\" width=\"4\" height=\"12\"/><circle cx=\"4\" cy=\"4\" r=\"2\"/></svg>",
			],
			'youtube'   => [
				'label'   => __( 'YouTube', 'social-media-posts' ),
				'brand'   => '#FF0000',
				'branded' => "<svg {$fa_attr} viewBox=\"0 0 576 512\"><path d=\"M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.5 48.284 47.821C117.22 448 288 448 288 448s170.78 0 213.371-11.486c23.497-6.321 42.003-24.171 48.284-47.821 11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305zm-317.51 213.508V175.185l142.739 81.205-142.739 81.201z\"/></svg>",
				'minimal' => "<svg {$min_attr}><rect x=\"2\" y=\"5\" width=\"20\" height=\"14\" rx=\"4\"/><path d=\"M10 9l5 3-5 3z\"/></svg>",
			],
			'website'   => [
				'label'   => __( 'Website / Other', 'social-media-posts' ),
				'brand'   => '#444444',
				'branded' => $globe,
				'minimal' => $globe,
			],
		];
	}

	public static function icon_svg( string $key, string $style ): string {
		$icons = self::icons();
		if ( ! isset( $icons[ $key ] ) ) {
			$key = 'website';
		}
		$variant = $style === 'minimalist' ? 'minimal' : 'branded';
		return $icons[ $key ][ $variant ];
	}

	/** Platform keys that ship as fixed rows in the manager. */
	public static function core_platforms(): array {
		return [ 'instagram', 'facebook', 'x', 'tiktok', 'linkedin' ];
	}

	/* ---------------------------------------------------------------------
	 * Settings
	 * ------------------------------------------------------------------- */

	public static function defaults(): array {
		$links = [];
		foreach ( self::core_platforms() as $key ) {
			$links[ $key ] = [
				'url'     => '',
				'target'  => '',
				'enabled' => true,
			];
		}

		return [
			'style'        => 'branded',
			'shape'        => 'circle',
			'size'         => 20,
			'padding'      => 12,
			'gap'          => 12,
			'color'        => '',
			'bg'           => '',
			'border_width' => 0,
			'border_color' => '',
			'align'        => 'left',
			'target'       => '_blank',
			'links'        => $links,
			'extras'       => [],
		];
	}

	public static function get_settings(): array {
		$saved = get_option( self::OPTION, [] );
		if ( ! is_array( $saved ) ) {
			$saved = [];
		}
		$settings = array_merge( self::defaults(), $saved );

		// Ensure every core platform has a row even if the option predates it.
		$defaults_links = self::defaults()['links'];
		$settings['links'] = is_array( $settings['links'] ?? null ) ? $settings['links'] : [];
		foreach ( $defaults_links as $key => $row ) {
			$settings['links'][ $key ] = array_merge( $row, $settings['links'][ $key ] ?? [] );
		}

		$settings['extras'] = is_array( $settings['extras'] ?? null ) ? array_values( $settings['extras'] ) : [];

		return $settings;
	}

	/* ---------------------------------------------------------------------
	 * Admin menu + page
	 * ------------------------------------------------------------------- */

	public function register_menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . SMP_POST_TYPE,
			__( 'Social Links', 'social-media-posts' ),
			__( 'Social Links', 'social-media-posts' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ]
		);
	}

	public function enqueue_admin_assets( string $hook ): void {
		if ( strpos( $hook, self::MENU_SLUG ) === false ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'smp-admin', SMP_URL . 'assets/admin.css', [], SMP_VERSION );
		wp_enqueue_style( 'smp-social-links', SMP_URL . 'assets/social-links.css', [], SMP_VERSION );

		wp_enqueue_script(
			'smp-social-links-admin',
			SMP_URL . 'assets/social-links-admin.js',
			[ 'jquery', 'wp-color-picker' ],
			SMP_VERSION,
			true
		);

		$icon_options = [];
		$icon_svgs    = [];
		foreach ( self::icons() as $key => $data ) {
			$icon_options[ $key ] = $data['label'];
			$icon_svgs[ $key ]    = [
				'branded' => $data['branded'],
				'minimal' => $data['minimal'],
			];
		}

		wp_localize_script( 'smp-social-links-admin', 'SMP_SocialLinks', [
			'iconOptions' => $icon_options,
			'icons'       => $icon_svgs,
			'i18n'        => [
				'remove'      => __( 'Remove', 'social-media-posts' ),
				'copied'      => __( 'Shortcode copied to clipboard!', 'social-media-posts' ),
				'labelPh'     => __( 'Label (e.g. Threads)', 'social-media-posts' ),
				'inheritTab'  => __( 'Use default', 'social-media-posts' ),
				'newTab'      => __( 'New tab', 'social-media-posts' ),
				'sameTab'     => __( 'Same tab', 'social-media-posts' ),
			],
		] );
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'social-media-posts' ) );
		}

		$settings   = self::get_settings();
		$icons      = self::icons();
		$shortcode  = $this->build_shortcode_string( $settings );
		$updated    = isset( $_GET['updated'] ) && $_GET['updated'] === '1'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$target_opt = [
			'_blank' => __( 'New tab', 'social-media-posts' ),
			'_self'  => __( 'Same tab', 'social-media-posts' ),
		];
		?>
		<div class="wrap smp-admin-wrap smp-social-admin">
			<h1><?php esc_html_e( 'Social Links', 'social-media-posts' ); ?></h1>

			<?php if ( $updated ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Social links saved.', 'social-media-posts' ); ?></p></div>
			<?php endif; ?>

			<div class="smp-info-box">
				<h2><?php esc_html_e( 'Social Icons Shortcode', 'social-media-posts' ); ?></h2>
				<p><?php esc_html_e( 'Add your social profile URLs, pick a look, and drop the shortcode anywhere. Every option below can also be overridden per-shortcode.', 'social-media-posts' ); ?></p>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="smp-social-form">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::NONCE_ACTION ); ?>" />
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

				<div class="smp-shortcode-section">
					<h3><?php esc_html_e( 'Your Links', 'social-media-posts' ); ?></h3>
					<p><?php esc_html_e( 'Leave a URL blank to hide that platform. The "Target" controls whether the link opens in a new tab.', 'social-media-posts' ); ?></p>

					<table class="widefat striped smp-links-table">
						<thead>
							<tr>
								<th class="smp-col-icon"><?php esc_html_e( 'Icon', 'social-media-posts' ); ?></th>
								<th><?php esc_html_e( 'Platform', 'social-media-posts' ); ?></th>
								<th><?php esc_html_e( 'URL', 'social-media-posts' ); ?></th>
								<th class="smp-col-target"><?php esc_html_e( 'Target', 'social-media-posts' ); ?></th>
								<th class="smp-col-enable"><?php esc_html_e( 'Show', 'social-media-posts' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( self::core_platforms() as $key ) :
								$row = $settings['links'][ $key ]; ?>
								<tr>
									<td class="smp-col-icon">
										<span class="smp-icon-preview" data-icon="<?php echo esc_attr( $key ); ?>">
											<?php echo self::icon_svg( $key, $settings['style'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG ?>
										</span>
									</td>
									<td><strong><?php echo esc_html( $icons[ $key ]['label'] ); ?></strong></td>
									<td>
										<input type="url" class="large-text" name="links[<?php echo esc_attr( $key ); ?>][url]" value="<?php echo esc_attr( $row['url'] ); ?>" placeholder="https://" />
									</td>
									<td class="smp-col-target">
										<select name="links[<?php echo esc_attr( $key ); ?>][target]">
											<option value=""><?php esc_html_e( 'Use default', 'social-media-posts' ); ?></option>
											<?php foreach ( $target_opt as $tval => $tlabel ) : ?>
												<option value="<?php echo esc_attr( $tval ); ?>" <?php selected( $row['target'], $tval ); ?>><?php echo esc_html( $tlabel ); ?></option>
											<?php endforeach; ?>
										</select>
									</td>
									<td class="smp-col-enable">
										<input type="hidden" name="links[<?php echo esc_attr( $key ); ?>][enabled]" value="0" />
										<input type="checkbox" name="links[<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( ! empty( $row['enabled'] ) ); ?> />
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<h4><?php esc_html_e( 'Extra Links', 'social-media-posts' ); ?></h4>
					<p><?php esc_html_e( 'Add any other profiles. Choose the closest icon, or use the generic Website icon.', 'social-media-posts' ); ?></p>

					<table class="widefat smp-extras-table">
						<thead>
							<tr>
								<th class="smp-col-icon"><?php esc_html_e( 'Icon', 'social-media-posts' ); ?></th>
								<th><?php esc_html_e( 'Label', 'social-media-posts' ); ?></th>
								<th><?php esc_html_e( 'URL', 'social-media-posts' ); ?></th>
								<th class="smp-col-target"><?php esc_html_e( 'Target', 'social-media-posts' ); ?></th>
								<th class="smp-col-remove"></th>
							</tr>
						</thead>
						<tbody class="smp-extras-body">
							<?php foreach ( $settings['extras'] as $i => $extra ) : ?>
								<tr class="smp-extra-row">
									<td class="smp-col-icon">
										<select name="extras[<?php echo (int) $i; ?>][icon]">
											<?php foreach ( $icons as $ik => $idata ) : ?>
												<option value="<?php echo esc_attr( $ik ); ?>" <?php selected( $extra['icon'] ?? 'website', $ik ); ?>><?php echo esc_html( $idata['label'] ); ?></option>
											<?php endforeach; ?>
										</select>
									</td>
									<td><input type="text" class="regular-text" name="extras[<?php echo (int) $i; ?>][label]" value="<?php echo esc_attr( $extra['label'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Label (e.g. Threads)', 'social-media-posts' ); ?>" /></td>
									<td><input type="url" class="large-text" name="extras[<?php echo (int) $i; ?>][url]" value="<?php echo esc_attr( $extra['url'] ?? '' ); ?>" placeholder="https://" /></td>
									<td class="smp-col-target">
										<select name="extras[<?php echo (int) $i; ?>][target]">
											<option value=""><?php esc_html_e( 'Use default', 'social-media-posts' ); ?></option>
											<?php foreach ( $target_opt as $tval => $tlabel ) : ?>
												<option value="<?php echo esc_attr( $tval ); ?>" <?php selected( $extra['target'] ?? '', $tval ); ?>><?php echo esc_html( $tlabel ); ?></option>
											<?php endforeach; ?>
										</select>
									</td>
									<td class="smp-col-remove"><button type="button" class="button-link smp-remove-extra"><?php esc_html_e( 'Remove', 'social-media-posts' ); ?></button></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<p><button type="button" class="button smp-add-extra"><?php esc_html_e( '+ Add Extra Link', 'social-media-posts' ); ?></button></p>
				</div>

				<div class="smp-appearance-layout">
				<div class="smp-shortcode-section smp-appearance-main">
					<h3><?php esc_html_e( 'Appearance', 'social-media-posts' ); ?></h3>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Icon style', 'social-media-posts' ); ?></th>
							<td>
								<label class="smp-radio"><input type="radio" name="style" value="branded" <?php checked( $settings['style'], 'branded' ); ?> /> <?php esc_html_e( 'Branded (brand colours)', 'social-media-posts' ); ?></label>
								<label class="smp-radio"><input type="radio" name="style" value="minimalist" <?php checked( $settings['style'], 'minimalist' ); ?> /> <?php esc_html_e( 'Minimalist (line icons)', 'social-media-posts' ); ?></label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="smp-shape"><?php esc_html_e( 'Shape', 'social-media-posts' ); ?></label></th>
							<td>
								<select id="smp-shape" name="shape">
									<?php foreach ( [ 'circle' => __( 'Circle', 'social-media-posts' ), 'rounded' => __( 'Rounded square', 'social-media-posts' ), 'square' => __( 'Square', 'social-media-posts' ), 'none' => __( 'No background', 'social-media-posts' ) ] as $sval => $slabel ) : ?>
										<option value="<?php echo esc_attr( $sval ); ?>" <?php selected( $settings['shape'], $sval ); ?>><?php echo esc_html( $slabel ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="smp-size"><?php esc_html_e( 'Icon size (px)', 'social-media-posts' ); ?></label></th>
							<td><input type="number" id="smp-size" name="size" min="8" max="128" value="<?php echo esc_attr( (string) $settings['size'] ); ?>" class="small-text" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="smp-padding"><?php esc_html_e( 'Padding (px)', 'social-media-posts' ); ?></label></th>
							<td><input type="number" id="smp-padding" name="padding" min="0" max="80" value="<?php echo esc_attr( (string) $settings['padding'] ); ?>" class="small-text" /> <span class="description"><?php esc_html_e( 'Space inside the shape, around the icon.', 'social-media-posts' ); ?></span></td>
						</tr>
						<tr>
							<th scope="row"><label for="smp-gap"><?php esc_html_e( 'Gap (px)', 'social-media-posts' ); ?></label></th>
							<td><input type="number" id="smp-gap" name="gap" min="0" max="80" value="<?php echo esc_attr( (string) $settings['gap'] ); ?>" class="small-text" /> <span class="description"><?php esc_html_e( 'Space between icons.', 'social-media-posts' ); ?></span></td>
						</tr>
						<tr>
							<th scope="row"><label for="smp-color"><?php esc_html_e( 'Icon colour', 'social-media-posts' ); ?></label></th>
							<td>
								<input type="text" id="smp-color" name="color" value="<?php echo esc_attr( $settings['color'] ); ?>" class="smp-color-field" data-default-color="" />
								<p class="description"><?php esc_html_e( 'Branded: leave blank to keep brand colours. Minimalist: the line colour (defaults to dark).', 'social-media-posts' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="smp-bg"><?php esc_html_e( 'Background colour', 'social-media-posts' ); ?></label></th>
							<td>
								<input type="text" id="smp-bg" name="bg" value="<?php echo esc_attr( $settings['bg'] ); ?>" class="smp-color-field" data-default-color="" />
								<p class="description"><?php esc_html_e( 'Optional. Branded: leave blank to use each brand colour.', 'social-media-posts' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="smp-border"><?php esc_html_e( 'Border width (px)', 'social-media-posts' ); ?></label></th>
							<td><input type="number" id="smp-border" name="border_width" min="0" max="20" value="<?php echo esc_attr( (string) $settings['border_width'] ); ?>" class="small-text" /> <span class="description"><?php esc_html_e( 'Set 0 for no border.', 'social-media-posts' ); ?></span></td>
						</tr>
						<tr>
							<th scope="row"><label for="smp-border-color"><?php esc_html_e( 'Border colour', 'social-media-posts' ); ?></label></th>
							<td><input type="text" id="smp-border-color" name="border_color" value="<?php echo esc_attr( $settings['border_color'] ); ?>" class="smp-color-field" data-default-color="" /></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Default target', 'social-media-posts' ); ?></th>
							<td>
								<label class="smp-radio"><input type="radio" name="target" value="_blank" <?php checked( $settings['target'], '_blank' ); ?> /> <?php esc_html_e( 'New tab', 'social-media-posts' ); ?></label>
								<label class="smp-radio"><input type="radio" name="target" value="_self" <?php checked( $settings['target'], '_self' ); ?> /> <?php esc_html_e( 'Same tab', 'social-media-posts' ); ?></label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="smp-align"><?php esc_html_e( 'Alignment', 'social-media-posts' ); ?></label></th>
							<td>
								<select id="smp-align" name="align">
									<?php foreach ( [ 'left' => __( 'Left', 'social-media-posts' ), 'center' => __( 'Center', 'social-media-posts' ), 'right' => __( 'Right', 'social-media-posts' ) ] as $aval => $alabel ) : ?>
										<option value="<?php echo esc_attr( $aval ); ?>" <?php selected( $settings['align'], $aval ); ?>><?php echo esc_html( $alabel ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					</table>
				</div>

				<div class="smp-shortcode-section smp-appearance-preview">
					<h3><?php esc_html_e( 'Live Preview', 'social-media-posts' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Updates live as you change appearance options. Save to apply link changes.', 'social-media-posts' ); ?></p>
					<div class="smp-social-preview">
						<?php echo $this->render_links_html( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within renderer ?>
					</div>
				</div>
				</div>

				<p class="submit"><button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save Social Links', 'social-media-posts' ); ?></button></p>
			</form>

			<div class="smp-shortcode-section">
				<h3><?php esc_html_e( 'Shortcode', 'social-media-posts' ); ?></h3>
				<p><?php esc_html_e( 'Use the bare shortcode to inherit the settings above, or copy the one matching your current settings:', 'social-media-posts' ); ?></p>
				<div class="smp-shortcode-block">
					<div class="smp-shortcode-code">
						<code id="smp-shortcode-basic">[smp_social_links]</code>
						<button type="button" class="smp-copy-btn" data-clipboard-target="smp-shortcode-basic"><?php esc_html_e( 'Copy', 'social-media-posts' ); ?></button>
					</div>
				</div>
				<div class="smp-shortcode-block">
					<div class="smp-shortcode-code">
						<code id="smp-shortcode-full"><?php echo esc_html( $shortcode ); ?></code>
						<button type="button" class="smp-copy-btn" data-clipboard-target="smp-shortcode-full"><?php esc_html_e( 'Copy', 'social-media-posts' ); ?></button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Save
	 * ------------------------------------------------------------------- */

	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'social-media-posts' ) );
		}
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security check failed.', 'social-media-posts' ) );
		}

		$icon_keys = array_keys( self::icons() );
		$clean     = self::defaults();

		$style          = isset( $_POST['style'] ) ? sanitize_key( wp_unslash( $_POST['style'] ) ) : 'branded';
		$clean['style'] = in_array( $style, [ 'branded', 'minimalist' ], true ) ? $style : 'branded';

		$shape          = isset( $_POST['shape'] ) ? sanitize_key( wp_unslash( $_POST['shape'] ) ) : 'circle';
		$clean['shape'] = in_array( $shape, [ 'circle', 'rounded', 'square', 'none' ], true ) ? $shape : 'circle';

		$clean['size']    = isset( $_POST['size'] ) ? max( 8, min( 128, absint( $_POST['size'] ) ) ) : 20;
		$clean['padding'] = isset( $_POST['padding'] ) ? max( 0, min( 80, absint( $_POST['padding'] ) ) ) : 12;
		$clean['gap']     = isset( $_POST['gap'] ) ? max( 0, min( 80, absint( $_POST['gap'] ) ) ) : 12;

		$clean['color'] = isset( $_POST['color'] ) ? (string) sanitize_hex_color( wp_unslash( $_POST['color'] ) ) : '';
		$clean['bg']    = isset( $_POST['bg'] ) ? (string) sanitize_hex_color( wp_unslash( $_POST['bg'] ) ) : '';

		$clean['border_width'] = isset( $_POST['border_width'] ) ? max( 0, min( 20, absint( $_POST['border_width'] ) ) ) : 0;
		$clean['border_color'] = isset( $_POST['border_color'] ) ? (string) sanitize_hex_color( wp_unslash( $_POST['border_color'] ) ) : '';

		$align          = isset( $_POST['align'] ) ? sanitize_key( wp_unslash( $_POST['align'] ) ) : 'left';
		$clean['align'] = in_array( $align, [ 'left', 'center', 'right' ], true ) ? $align : 'left';

		$target          = isset( $_POST['target'] ) ? sanitize_key( wp_unslash( $_POST['target'] ) ) : '_blank';
		$clean['target'] = in_array( $target, [ '_blank', '_self' ], true ) ? $target : '_blank';

		$posted_links  = isset( $_POST['links'] ) && is_array( $_POST['links'] ) ? wp_unslash( $_POST['links'] ) : [];
		$clean['links'] = [];
		foreach ( self::core_platforms() as $key ) {
			$row    = is_array( $posted_links[ $key ] ?? null ) ? $posted_links[ $key ] : [];
			$rtgt   = isset( $row['target'] ) ? sanitize_key( $row['target'] ) : '';
			$clean['links'][ $key ] = [
				'url'     => isset( $row['url'] ) ? esc_url_raw( $row['url'] ) : '',
				'target'  => in_array( $rtgt, [ '_blank', '_self' ], true ) ? $rtgt : '',
				'enabled' => ! empty( $row['enabled'] ),
			];
		}

		$posted_extras  = isset( $_POST['extras'] ) && is_array( $_POST['extras'] ) ? wp_unslash( $_POST['extras'] ) : [];
		$clean['extras'] = [];
		foreach ( $posted_extras as $extra ) {
			if ( ! is_array( $extra ) ) {
				continue;
			}
			$url = isset( $extra['url'] ) ? esc_url_raw( $extra['url'] ) : '';
			if ( $url === '' ) {
				continue; // Skip empty rows.
			}
			$icon = isset( $extra['icon'] ) ? sanitize_key( $extra['icon'] ) : 'website';
			$tgt  = isset( $extra['target'] ) ? sanitize_key( $extra['target'] ) : '';
			$clean['extras'][] = [
				'icon'   => in_array( $icon, $icon_keys, true ) ? $icon : 'website',
				'label'  => isset( $extra['label'] ) ? sanitize_text_field( $extra['label'] ) : '',
				'url'    => $url,
				'target' => in_array( $tgt, [ '_blank', '_self' ], true ) ? $tgt : '',
			];
		}

		update_option( self::OPTION, $clean );

		wp_safe_redirect( add_query_arg( [ 'post_type' => SMP_POST_TYPE, 'page' => self::MENU_SLUG, 'updated' => '1' ], admin_url( 'edit.php' ) ) );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Frontend
	 * ------------------------------------------------------------------- */

	public function register_frontend_assets(): void {
		wp_register_style( 'smp-social-links', SMP_URL . 'assets/social-links.css', [], SMP_VERSION );
	}

	public function render_shortcode( $atts ): string {
		$atts     = is_array( $atts ) ? $atts : [];
		$settings = self::get_settings();

		// Shortcode attributes override saved settings when present.
		$map = [ 'style', 'shape', 'size', 'padding', 'gap', 'color', 'bg', 'align', 'target', 'border_color' ];
		foreach ( $map as $field ) {
			if ( isset( $atts[ $field ] ) && $atts[ $field ] !== '' ) {
				$settings[ $field ] = $atts[ $field ];
			}
		}
		// "border" is the friendly attribute name for border width.
		if ( isset( $atts['border'] ) && $atts['border'] !== '' ) {
			$settings['border_width'] = $atts['border'];
		}

		// Normalise overrides.
		$settings['style']   = in_array( $settings['style'], [ 'branded', 'minimalist' ], true ) ? $settings['style'] : 'branded';
		$settings['shape']   = in_array( $settings['shape'], [ 'circle', 'rounded', 'square', 'none' ], true ) ? $settings['shape'] : 'circle';
		$settings['align']   = in_array( $settings['align'], [ 'left', 'center', 'right' ], true ) ? $settings['align'] : 'left';
		$settings['target']  = in_array( $settings['target'], [ '_blank', '_self' ], true ) ? $settings['target'] : '_blank';
		$settings['size']    = max( 8, min( 128, absint( $settings['size'] ) ) );
		$settings['padding'] = max( 0, min( 80, absint( $settings['padding'] ) ) );
		$settings['gap']     = max( 0, min( 80, absint( $settings['gap'] ) ) );
		$settings['color']   = $settings['color'] ? (string) sanitize_hex_color( $settings['color'] ) : '';
		$settings['bg']      = $settings['bg'] ? (string) sanitize_hex_color( $settings['bg'] ) : '';
		$settings['border_width'] = max( 0, min( 20, absint( $settings['border_width'] ) ) );
		$settings['border_color'] = $settings['border_color'] ? (string) sanitize_hex_color( $settings['border_color'] ) : '';

		// Optional "only" / "platforms" attribute to filter & order.
		$only = '';
		if ( isset( $atts['only'] ) ) {
			$only = (string) $atts['only'];
		} elseif ( isset( $atts['platforms'] ) ) {
			$only = (string) $atts['platforms'];
		}
		if ( $only !== '' ) {
			$settings['only'] = array_filter( array_map( 'sanitize_key', array_map( 'trim', explode( ',', $only ) ) ) );
		}

		$html = $this->render_links_html( $settings );
		if ( $html === '' ) {
			return '';
		}

		wp_enqueue_style( 'smp-social-links' );
		return $html;
	}

	/**
	 * Build the ordered list of links to render from settings.
	 *
	 * @return array<int,array<string,string>>
	 */
	private function collect_links( array $settings ): array {
		$default_target = $settings['target'];
		$out            = [];

		foreach ( self::core_platforms() as $key ) {
			$row = $settings['links'][ $key ] ?? [];
			if ( empty( $row['enabled'] ) || empty( $row['url'] ) ) {
				continue;
			}
			$out[] = [
				'icon'   => $key,
				'label'  => self::icons()[ $key ]['label'],
				'url'    => $row['url'],
				'target' => ! empty( $row['target'] ) ? $row['target'] : $default_target,
			];
		}

		foreach ( $settings['extras'] as $extra ) {
			if ( empty( $extra['url'] ) ) {
				continue;
			}
			$icon  = $extra['icon'] ?? 'website';
			$label = ! empty( $extra['label'] ) ? $extra['label'] : ( self::icons()[ $icon ]['label'] ?? __( 'Link', 'social-media-posts' ) );
			$out[] = [
				'icon'   => $icon,
				'label'  => $label,
				'url'    => $extra['url'],
				'target' => ! empty( $extra['target'] ) ? $extra['target'] : $default_target,
			];
		}

		// Optional filter/order by the shortcode "only" attribute.
		if ( ! empty( $settings['only'] ) ) {
			$ordered = [];
			foreach ( $settings['only'] as $wanted ) {
				foreach ( $out as $item ) {
					if ( $item['icon'] === $wanted ) {
						$ordered[] = $item;
					}
				}
			}
			$out = $ordered;
		}

		return $out;
	}

	public function render_links_html( array $settings ): string {
		$links = $this->collect_links( $settings );
		if ( ! $links ) {
			return '';
		}

		$icons   = self::icons();
		$wrap_cls = [
			'smp-social',
			'smp-social--' . $settings['style'],
			'smp-social--' . $settings['shape'],
			'smp-social--align-' . $settings['align'],
		];
		if ( $settings['bg'] !== '' ) {
			$wrap_cls[] = 'smp-social--has-bg';
		}

		$styles = [
			'--smp-sl-size: ' . (int) $settings['size'] . 'px',
			'--smp-sl-pad: ' . (int) $settings['padding'] . 'px',
			'--smp-sl-gap: ' . (int) $settings['gap'] . 'px',
		];
		if ( $settings['color'] !== '' ) {
			$styles[] = '--smp-sl-color: ' . $settings['color'];
		}
		if ( $settings['bg'] !== '' ) {
			$styles[] = '--smp-sl-bg: ' . $settings['bg'];
		}
		if ( (int) $settings['border_width'] > 0 ) {
			$styles[] = '--smp-sl-border-w: ' . (int) $settings['border_width'] . 'px';
			if ( $settings['border_color'] !== '' ) {
				$styles[] = '--smp-sl-border-c: ' . $settings['border_color'];
			}
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $wrap_cls ) ); ?>" style="<?php echo esc_attr( implode( '; ', $styles ) ); ?>">
			<?php foreach ( $links as $item ) :
				$brand    = $icons[ $item['icon'] ]['brand'] ?? '#444444';
				$is_blank = $item['target'] === '_blank';
				?>
				<a
					class="smp-social__link smp-social__link--<?php echo esc_attr( $item['icon'] ); ?>"
					data-icon="<?php echo esc_attr( $item['icon'] ); ?>"
					href="<?php echo esc_url( $item['url'] ); ?>"
					target="<?php echo esc_attr( $item['target'] ); ?>"
					<?php echo $is_blank ? 'rel="noopener noreferrer"' : ''; ?>
					style="--smp-sl-brand: <?php echo esc_attr( $brand ); ?>;"
					aria-label="<?php echo esc_attr( $item['label'] ); ?>"
				>
					<span class="smp-social__icon"><?php echo self::icon_svg( $item['icon'], $settings['style'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG ?></span>
					<span class="screen-reader-text"><?php echo esc_html( $item['label'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/** Build a shortcode string reflecting the saved settings, for the copy box. */
	private function build_shortcode_string( array $settings ): string {
		$parts = [ 'smp_social_links' ];
		$parts[] = 'style="' . $settings['style'] . '"';
		$parts[] = 'shape="' . $settings['shape'] . '"';
		$parts[] = 'size="' . (int) $settings['size'] . '"';
		$parts[] = 'padding="' . (int) $settings['padding'] . '"';
		$parts[] = 'gap="' . (int) $settings['gap'] . '"';
		if ( $settings['color'] !== '' ) {
			$parts[] = 'color="' . $settings['color'] . '"';
		}
		if ( $settings['bg'] !== '' ) {
			$parts[] = 'bg="' . $settings['bg'] . '"';
		}
		if ( (int) $settings['border_width'] > 0 ) {
			$parts[] = 'border="' . (int) $settings['border_width'] . '"';
			if ( $settings['border_color'] !== '' ) {
				$parts[] = 'border_color="' . $settings['border_color'] . '"';
			}
		}
		$parts[] = 'align="' . $settings['align'] . '"';
		$parts[] = 'target="' . $settings['target'] . '"';

		return '[' . implode( ' ', $parts ) . ']';
	}
}
