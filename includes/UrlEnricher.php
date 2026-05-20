<?php

namespace SocialMediaPosts;

defined( 'ABSPATH' ) || exit;

class UrlEnricher {

	/**
	 * Extract title / description / image / video / platform for a URL.
	 *
	 * Always returns an array. Fields that could not be extracted come back as empty strings.
	 * Platform is detected from the hostname; the caller is responsible for deciding whether
	 * the result is "good enough" to mark the import as success vs partial.
	 *
	 * @return array{title:string,description:string,image_url:string,video_url:string,platform:string}
	 */
	public function enrich( string $url ): array {
		$result = [
			'title'       => '',
			'description' => '',
			'image_url'   => '',
			'video_url'   => '',
			'platform'    => $this->detect_platform( $url ),
		];

		$oembed = ( new OEmbedFetcher() )->fetch( $url );
		if ( ! is_wp_error( $oembed ) ) {
			if ( ! empty( $oembed['title'] ) ) {
				$result['title'] = $oembed['title'];
			}
			if ( ! empty( $oembed['thumbnail_url'] ) ) {
				$result['image_url'] = $oembed['thumbnail_url'];
			}
			if ( ( $oembed['type'] ?? '' ) === 'video' && ! empty( $oembed['thumbnail_url'] ) ) {
				$result['video_url'] = $oembed['thumbnail_url'];
			}
		}

		if ( $result['title'] && $result['image_url'] && $result['description'] ) {
			return $result;
		}

		$og = $this->fetch_og_tags( $url );
		if ( $og ) {
			if ( ! $result['title'] && ! empty( $og['title'] ) ) {
				$result['title'] = $og['title'];
			}
			if ( ! $result['description'] && ! empty( $og['description'] ) ) {
				$result['description'] = $og['description'];
			}
			if ( ! $result['image_url'] && ! empty( $og['image'] ) ) {
				$result['image_url'] = $og['image'];
			}
			if ( ! $result['video_url'] && ! empty( $og['video'] ) ) {
				$result['video_url'] = $og['video'];
			}
		} else {
			// Log what failed
			error_log( 'SMP: fetch_og_tags returned null for ' . $url );
		}

		return $result;
	}

	private function is_x_url( string $url ): bool {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( ! $host ) {
			return false;
		}
		$host = preg_replace( '/^www\./', '', $host );
		return in_array( $host, [ 'x.com', 'twitter.com', 't.co' ], true );
	}

	private function is_tiktok_url( string $url ): bool {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( ! $host ) {
			return false;
		}
		$host = preg_replace( '/^www\./', '', $host );
		return in_array( $host, [ 'tiktok.com', 'vm.tiktok.com' ], true );
	}

	private function fetch_og_tags_via_tiktok( string $url ): ?array {
		// TikTok renders post content client-side and serves an empty shell to
		// generic clients (botType "others"). It only emits Open Graph tags to
		// recognised social crawlers, so we identify as facebookexternalhit.
		$response = wp_safe_remote_get( $url, [
			'timeout'     => 10,
			'redirection' => 5,
			'user-agent'  => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
			'headers'     => [
				'Accept'          => 'text/html,application/xhtml+xml',
				'Accept-Language' => 'en;q=0.9',
			],
		] );

		if ( is_wp_error( $response ) ) {
			error_log( 'SMP: TikTok fetch failed: ' . $response->get_error_message() );
			return null;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 400 ) {
			error_log( 'SMP: TikTok HTTP ' . $code . ' for ' . $url );
			return null;
		}

		$body = (string) wp_remote_retrieve_body( $response );
		if ( ! $body ) {
			error_log( 'SMP: TikTok returned empty body for ' . $url );
			return null;
		}

		$head_end = stripos( $body, '</head>' );
		if ( $head_end !== false ) {
			$body = substr( $body, 0, $head_end );
		}

		$result = [
			'title'       => $this->extract_og( $body, 'title' ),
			'description' => $this->extract_og( $body, 'description' ),
			'image'       => $this->extract_og( $body, 'image' ),
			'video'       => $this->extract_og( $body, 'video' ),
		];

		error_log( 'SMP: TikTok extraction result for ' . $url . ': ' . json_encode( $result ) );
		return $result;
	}

	private function fetch_og_tags_via_nitter( string $url ): ?array {
		// Convert X/Twitter URL to nitter.net equivalent
		$nitter_url = $this->convert_to_nitter_url( $url );
		if ( ! $nitter_url ) {
			error_log( 'SMP: Could not convert X URL to nitter format: ' . $url );
			return null;
		}

		$response = wp_safe_remote_get( $nitter_url, [
			'timeout'     => 10,
			'redirection' => 5,
			'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
			'headers'     => [
				'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
				'Accept-Language' => 'en-US,en;q=0.9',
			],
		] );

		if ( is_wp_error( $response ) ) {
			error_log( 'SMP: Nitter fetch failed for ' . $nitter_url . ': ' . $response->get_error_message() );
			return null;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 400 ) {
			error_log( 'SMP: Nitter HTTP ' . $code . ' for ' . $nitter_url );
			return null;
		}

		$body = (string) wp_remote_retrieve_body( $response );
		if ( ! $body ) {
			error_log( 'SMP: Nitter returned empty body for ' . $nitter_url );
			return null;
		}

		// Parse OG tags from nitter response
		$result = [
			'title'       => $this->extract_og( $body, 'title' ),
			'description' => $this->extract_og( $body, 'description' ),
			'image'       => $this->nitter_image_to_twitter_cdn( $this->extract_og( $body, 'image' ) ),
			'video'       => $this->nitter_image_to_twitter_cdn( $this->extract_og( $body, 'video' ) ),
		];

		error_log( 'SMP: Nitter extraction result for ' . $url . ': ' . json_encode( $result ) );
		return $result;
	}

	private function nitter_image_to_twitter_cdn( string $url ): string {
		if ( ! $url ) {
			return '';
		}
		// Nitter proxies Twitter media as https://nitter.net/pic/<path>
		// The real image is at https://pbs.twimg.com/<path>
		if ( preg_match( '#^https?://[^/]*nitter\.[^/]+/pic/(.+)$#i', $url, $m ) ) {
			return 'https://pbs.twimg.com/' . rawurldecode( $m[1] );
		}
		return $url;
	}

	private function convert_to_nitter_url( string $url ): ?string {
		// Parse the X/Twitter URL to extract user and status ID
		// Supported formats:
		// - https://x.com/user/status/1234567890
		// - https://twitter.com/user/status/1234567890
		// - https://t.co/abc123
		if ( preg_match( '#(?:x\.com|twitter\.com)/([^/]+)/status/(\d+)#i', $url, $m ) ) {
			$user = $m[1];
			$status_id = $m[2];
			return "https://nitter.net/$user/status/$status_id";
		}

		return null;
	}

	public function detect_platform( string $url ): string {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( ! $host ) {
			return '';
		}
		$host = preg_replace( '/^www\./', '', $host );

		$map = [
			'instagram.com'         => 'instagram',
			'instagr.am'            => 'instagram',
			'facebook.com'          => 'facebook',
			'fb.com'                => 'facebook',
			'fb.watch'              => 'facebook',
			'x.com'                 => 'x',
			'twitter.com'           => 'x',
			't.co'                  => 'x',
			'tiktok.com'            => 'tiktok',
			'vm.tiktok.com'         => 'tiktok',
			'youtube.com'           => 'youtube',
			'youtu.be'              => 'youtube',
			'm.youtube.com'         => 'youtube',
			'linkedin.com'          => 'linkedin',
		];

		if ( isset( $map[ $host ] ) ) {
			return $map[ $host ];
		}

		foreach ( $map as $domain => $platform ) {
			if ( substr( $host, -strlen( '.' . $domain ) ) === '.' . $domain ) {
				return $platform;
			}
		}

		return 'other';
	}

	/**
	 * Fetch a URL via wp_safe_remote_get and parse common Open Graph meta tags.
	 *
	 * @return array{title:string,description:string,image:string,video:string}|null
	 */
	private function fetch_og_tags( string $url ): ?array {
		// Special handling for X/Twitter - skip direct fetch as it requires auth
		if ( $this->is_x_url( $url ) ) {
			return $this->fetch_og_tags_via_nitter( $url );
		}

		// Special handling for TikTok - uses client-side rendering, extract from JSON
		if ( $this->is_tiktok_url( $url ) ) {
			return $this->fetch_og_tags_via_tiktok( $url );
		}

		$response = wp_safe_remote_get( $url, [
			'timeout'     => 10,
			'redirection' => 5,
			'user-agent'  => 'Mozilla/5.0 (compatible; Fairgo-SocialMediaPosts/1.0; +https://wordpress.org/)',
			'headers'     => [
				'Accept'          => 'text/html,application/xhtml+xml',
				'Accept-Language' => 'en;q=0.9',
			],
		] );

		if ( is_wp_error( $response ) ) {
			error_log( 'SMP: fetch_og_tags HTTP error for ' . $url . ': ' . $response->get_error_message() );
			return null;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 400 ) {
			error_log( 'SMP: fetch_og_tags HTTP ' . $code . ' for ' . $url );
			return null;
		}

		$body = (string) wp_remote_retrieve_body( $response );
		if ( ! $body ) {
			return null;
		}

		$head_end = stripos( $body, '</head>' );
		if ( $head_end !== false ) {
			$body = substr( $body, 0, $head_end );
		}

		return [
			'title'       => $this->extract_og( $body, 'title' ),
			'description' => $this->extract_og( $body, 'description' ),
			'image'       => $this->extract_og( $body, 'image' ),
			'video'       => $this->extract_og( $body, 'video' ),
		];
	}

	private function extract_og( string $html, string $property ): string {
		$prop = preg_quote( $property, '/' );
		$patterns = [
			'/<meta[^>]+property=["\']og:' . $prop . '["\'][^>]*content=["\']([^"\']+)["\']/i',
			'/<meta[^>]+content=["\']([^"\']+)["\'][^>]*property=["\']og:' . $prop . '["\']/i',
			'/<meta[^>]+name=["\']twitter:' . $prop . '["\'][^>]*content=["\']([^"\']+)["\']/i',
		];

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $html, $m ) ) {
				$value = html_entity_decode( $m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				$value = wp_strip_all_tags( $value );
				if ( in_array( $property, [ 'image', 'video' ], true ) ) {
					return esc_url_raw( $value );
				}
				return sanitize_text_field( $value );
			}
		}

		if ( $property === 'title' ) {
			if ( preg_match( '/<title[^>]*>(.*?)<\/title>/is', $html, $m ) ) {
				$value = html_entity_decode( $m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				return sanitize_text_field( wp_strip_all_tags( $value ) );
			}
		}

		return '';
	}
}
