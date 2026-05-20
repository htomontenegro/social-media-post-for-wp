<?php

namespace SocialMediaPosts;

use Elementor\Core\DynamicTags\Manager;
use SocialMediaPosts\Tags\AuthorBioTag;
use SocialMediaPosts\Tags\AuthorHandleTag;
use SocialMediaPosts\Tags\AuthorNameTag;
use SocialMediaPosts\Tags\DescriptionTag;
use SocialMediaPosts\Tags\ImageTag;
use SocialMediaPosts\Tags\PlatformTag;
use SocialMediaPosts\Tags\UrlTag;
use SocialMediaPosts\Tags\VideoTag;

defined( 'ABSPATH' ) || exit;

class ElementorTags {

	public const GROUP = 'social_media_posts';

	public function register(): void {
		add_action( 'elementor/dynamic_tags/register', [ $this, 'register_tags' ] );
	}

	public function register_tags( Manager $manager ): void {
		$manager->register_group( self::GROUP, [
			'title' => __( 'Social Media Posts', 'social-media-posts' ),
		] );

		$manager->register( new DescriptionTag() );
		$manager->register( new UrlTag() );
		$manager->register( new ImageTag() );
		$manager->register( new VideoTag() );
		$manager->register( new PlatformTag() );
		$manager->register( new AuthorNameTag() );
		$manager->register( new AuthorBioTag() );
		$manager->register( new AuthorHandleTag() );
	}
}
