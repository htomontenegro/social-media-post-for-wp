<?php

namespace SocialMediaPosts;

defined( 'ABSPATH' ) || exit;

/**
 * Parses the predictable Open Graph patterns that Instagram and Facebook emit
 * into structured author / caption fields.
 *
 * Each parser is best-effort: when a pattern does not match, the corresponding
 * field is returned empty and the caller falls back to the raw OG values.
 */
class PostParser {

	/**
	 * Returns the structured fields extracted from the OG title + description.
	 *
	 * @return array{author_name:string,author_bio:string,author_handle:string,caption:string,likes:int,comments:int,posted_date:string}
	 */
	public function parse( string $platform, string $og_title, string $og_description ): array {
		$result = [
			'author_name'   => '',
			'author_bio'    => '',
			'author_handle' => '',
			'caption'       => '',
			'likes'         => 0,
			'comments'      => 0,
			'posted_date'   => '',
		];

		switch ( $platform ) {
			case 'instagram':
				$this->parse_meta_style( 'Instagram', $og_title, $og_description, $result );
				break;
			case 'facebook':
				$this->parse_meta_style( 'Facebook', $og_title, $og_description, $result );
				break;
			case 'x':
				$this->parse_x_post( $og_title, $og_description, $result );
				break;
			case 'tiktok':
				$this->parse_tiktok_post( $og_title, $og_description, $result );
				break;
			default:
				$result['caption'] = $this->extract_caption_fallback( $og_description ) ?: $og_description;
		}

		return $result;
	}

	private function parse_meta_style( string $platform_label, string $title, string $description, array &$result ): void {
		$label = preg_quote( $platform_label, '/' );

		$title_pattern = '/^(?P<name>.+?)(?:\s+[\-\|]\s+(?P<bio>.+?))?\s+on\s+' . $label . ':\s*[“"\'](?P<caption>.+)[”"\']\.?\s*$/su';
		if ( preg_match( $title_pattern, trim( $title ), $m ) ) {
			$result['author_name'] = isset( $m['name'] ) ? trim( $m['name'] ) : '';
			$result['author_bio']  = isset( $m['bio'] ) ? trim( $m['bio'] ) : '';
			$result['caption']     = isset( $m['caption'] ) ? trim( $m['caption'] ) : '';
		}

		$desc_pattern = '/^(?P<likes>[\d,]+)\s+likes?,\s*(?P<comments>[\d,]+)\s+comments?\s+-\s+(?P<handle>[^\s]+)\s+on\s+(?P<date>[^:]+?):\s*[“"\'](?P<caption>.+)[”"\']\.?\s*$/su';
		if ( preg_match( $desc_pattern, trim( $description ), $m ) ) {
			$result['likes']         = (int) str_replace( ',', '', $m['likes'] );
			$result['comments']      = (int) str_replace( ',', '', $m['comments'] );
			$result['author_handle'] = trim( $m['handle'] );
			$result['posted_date']   = trim( $m['date'] );
			$desc_caption            = trim( $m['caption'] );
			if ( $desc_caption && strlen( $desc_caption ) > strlen( $result['caption'] ) ) {
				$result['caption'] = $desc_caption;
			}
		}

		if ( ! $result['caption'] ) {
			$result['caption'] = $this->extract_caption_fallback( $description );
		}
	}

	private function parse_tiktok_post( string $title, string $description, array &$result ): void {
		// TikTok OG title format: "TikTok · {handle}" (middle-dot separator).
		if ( preg_match( '/^TikTok\s*[·•\-|]\s*(?P<handle>.+?)\s*$/u', trim( $title ), $m ) ) {
			$result['author_handle'] = ltrim( trim( $m['handle'] ), '@' );
			$result['author_name']   = $result['author_handle'];
		}

		// TikTok serves a generic "Check out {handle}'s post." for photo posts;
		// treat that placeholder as no caption so the editor fills in the real one.
		$desc = trim( $description );
		if ( $desc && ! preg_match( '/^Check out .+ post\.?$/iu', $desc ) ) {
			$result['caption'] = $desc;
		}
	}

	private function parse_x_post( string $title, string $description, array &$result ): void {
		// X/Twitter OG format: title="Author (@handle)", description="Tweet text"
		// Extract: @handle and author name from title like "Author Name (@handle)"
		if ( preg_match( '/^(?P<name>.+?)\s*\(\s*@(?P<handle>[a-z0-9_]+)\s*\)$/iu', trim( $title ), $m ) ) {
			$result['author_name']   = trim( $m['name'] );
			$result['author_handle'] = trim( $m['handle'] );
		}

		// Description is the tweet text
		$result['caption'] = trim( $description );
	}

	/**
	 * If no platform-specific pattern matched, try to lift a quoted caption out
	 * of the description; otherwise return the description as-is.
	 */
	private function extract_caption_fallback( string $text ): string {
		if ( preg_match( '/[“"\'](?P<caption>.+)[”"\']\.?\s*$/su', trim( $text ), $m ) ) {
			return trim( $m['caption'] );
		}
		return '';
	}

	public static function truncate_for_title( string $caption, int $max_chars = 120 ): string {
		$caption = trim( $caption );
		if ( $caption === '' ) {
			return '';
		}
		if ( function_exists( 'mb_strlen' ) && mb_strlen( $caption ) > $max_chars ) {
			return rtrim( mb_substr( $caption, 0, $max_chars - 1 ) ) . '…';
		}
		if ( strlen( $caption ) > $max_chars ) {
			return rtrim( substr( $caption, 0, $max_chars - 1 ) ) . '…';
		}
		return $caption;
	}
}
