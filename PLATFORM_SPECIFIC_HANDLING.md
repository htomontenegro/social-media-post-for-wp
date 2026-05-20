# Platform-Specific OG Tag Fetching

## Architecture

The `UrlEnricher` class handles different platforms with specialized logic to avoid breaking one while improving another.

### Flow

```
enrich(url)
  ↓
OEmbedFetcher (WordPress native oEmbed)
  ↓
fetch_og_tags(url)
  ├─ is_x_url(url)? 
  │  └─ YES → fetch_og_tags_via_nitter()
  │              └─ nitter_image_to_twitter_cdn()  [converts nitter.net/pic/... → pbs.twimg.com/...]
  │  └─ NO  → wp_safe_remote_get()        [All other platforms]
  │
  └─ extract_og() on response
  ↓
PostParser::parse(platform, og_title, og_description)
  ├─ 'instagram' / 'facebook' → parse_meta_style()  [structured OG format]
  ├─ 'x'                      → parse_x_post()       [simple "Name (@handle)" format]
  └─ default                  → raw description as caption
```

## Per-Platform Rules

### X/Twitter (x.com, twitter.com, t.co)
- **Handler:** `fetch_og_tags_via_nitter()`
- **Why separate?** Twitter requires authentication to see OG tags. Nitter is a public Twitter frontend that exposes them without login.
- **User-agent:** Chrome-like — fine because we're hitting Nitter, not Twitter directly
- **Headers:** Full set (Accept-Language, etc.)
- **PostParser:** `parse_x_post()` — extracts author name + handle from `"Name (@handle)"` title, uses description as caption

**URL conversion:**
```
https://x.com/user/status/123456 → https://nitter.net/user/status/123456
```

**Image conversion (important):** Nitter proxies images through its own domain. These links break when Nitter is down. We convert them to direct Twitter CDN URLs at extraction time:
```
https://nitter.net/pic/media%2FHIo7-W7bgAExv5s.jpg → https://pbs.twimg.com/media/HIo7-W7bgAExv5s.jpg
```
Handled by `nitter_image_to_twitter_cdn()` in `UrlEnricher`.

### Instagram (instagram.com, instagr.am)
- **Handler:** Standard `wp_safe_remote_get()` with v1 user-agent
- **Why it works:** Instagram serves OG tags publicly BUT has bot detection
- **User-agent:** Honest identifier (`Fairgo-SocialMediaPosts/1.0`) - breaks bot detection
- **Headers:** Minimal (Accept, Accept-Language only)
- **⚠️ Critical:** Don't add DNT, Connection, or Upgrade-Insecure-Requests headers

### Facebook
- **Handler:** Standard `wp_safe_remote_get()` with v1 user-agent
- **Status:** Same as Instagram - requires honest user-agent

### YouTube, TikTok, LinkedIn
- **Handler:** Standard `wp_safe_remote_get()` with v1 user-agent + oEmbed first
- **Status:** Work well, no special handling needed

### Other Platforms
- **Handler:** Standard `wp_safe_remote_get()` with v1 user-agent
- **Status:** Best-effort OG extraction

## How to Add Platform-Specific Handling

**Safe pattern:** Add a dedicated `fetch_og_tags_via_<service>()` method and route through `is_<platform>_url()`.

```php
private function is_instagram_url( string $url ): bool {
    $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
    $host = preg_replace( '/^www\./', '', $host );
    return in_array( $host, [ 'instagram.com', 'instagr.am' ], true );
}

private function fetch_og_tags_via_instagram_api( string $url ): ?array {
    // Custom handler for Instagram (if API becomes available)
}
```

Then in `fetch_og_tags()`:
```php
if ( $this->is_x_url( $url ) ) {
    return $this->fetch_og_tags_via_nitter( $url );
}
if ( $this->is_instagram_url( $url ) ) {
    return $this->fetch_og_tags_via_instagram_api( $url );
}

// Fall back to standard handler for all others
```

## Key Principle

**Isolation:** Each platform gets its own detection and handler method. This prevents one platform's requirements from breaking another's.

Current setup is safe - you can improve X/Twitter or add Facebook handling without risking Instagram.
