# Social Media Import Procedures

**Last Updated:** 2026-05-20  
**Scope:** Import procedures and platform-specific handling for Facebook, X/Twitter, Instagram, TikTok, and LinkedIn

---

## Table of Contents
1. [General Architecture](#general-architecture)
2. [Platform Rules](#platform-rules)
3. [Adding New Platforms](#adding-new-platforms)
4. [Testing & Debugging](#testing--debugging)
5. [Troubleshooting](#troubleshooting)

---

## General Architecture

### Data Flow
```
URL → UrlEnricher → fetch_og_tags → OG tag extraction
  ↓
PostParser → Platform-specific parsing → Structured data
```

### Flow Diagram
```
enrich(url)
  ↓
OEmbedFetcher (WordPress native oEmbed)
  ↓
fetch_og_tags(url)
  ├─ is_x_url(url)? 
  │  └─ YES → fetch_og_tags_via_nitter()
  │              └─ nitter_image_to_twitter_cdn()  [converts nitter URLs → Twitter CDN]
  │  └─ NO  → wp_safe_remote_get()        [All other platforms]
  │
  └─ extract_og() on response
  ↓
PostParser::parse(platform, og_title, og_description)
  ├─ 'instagram' / 'facebook' → parse_meta_style()  [structured OG format]
  ├─ 'x'                      → parse_x_post()       [simple "Name (@handle)" format]
  └─ default                  → raw description as caption
```

### Design Principle
**Isolation:** Each platform gets its own detection and handler method. This prevents one platform's requirements from breaking another's.

---

## Platform Rules

### 📱 Instagram (instagram.com, instagr.am)

**Status:** ✅ Working  
**Handler File:** `includes/UrlEnricher.php` → `fetch_og_tags()` with standard `wp_safe_remote_get()`  
**Parser:** `PostParser::parse_meta_style()`

#### User-Agent (CRITICAL)
```php
'user-agent' => 'Mozilla/5.0 (compatible; Fairgo-SocialMediaPosts/1.0; +https://wordpress.org/)',
```

#### HTTP Headers (MINIMAL - DO NOT ADD EXTRA)
```php
'headers' => [
    'Accept'          => 'text/html,application/xhtml+xml',
    'Accept-Language' => 'en;q=0.9',
],
```

#### ⚠️ DO NOT
- ❌ Use realistic browser user-agents (Chrome, Firefox, Safari versions)
- ❌ Add `DNT`, `Connection`, or `Upgrade-Insecure-Requests` headers
- ❌ Add `Accept-Encoding: gzip, deflate`

These appear to Instagram's bot detection as a suspicious scraper.

#### OG Tag Format
```html
<meta property="og:title" content="Author Name - Bio on Instagram: \"Caption\"">
<meta property="og:description" content="123 likes, 456 comments - @handle on date: \"Caption\"">
<meta property="og:image" content="...">
```

#### Data Extracted
- ✅ Author name
- ✅ Handle (@username)
- ✅ Bio/description
- ✅ Caption
- ✅ Likes count
- ✅ Comments count
- ✅ Post date
- ✅ Image/thumbnail

---

### 🐦 X / Twitter (x.com, twitter.com, t.co)

**Status:** ✅ Working well  
**Handler File:** `includes/UrlEnricher.php` → `fetch_og_tags_via_nitter()`  
**Parser:** `PostParser::parse_x_post()`

#### Why Nitter?
Twitter requires authentication to see OG tags. Nitter is a public Twitter frontend that exposes them without login.

#### URL Conversion
```
https://x.com/user/status/123456 → https://nitter.net/user/status/123456
```

#### User-Agent
Chrome-like user-agent is fine (we're hitting Nitter, not Twitter directly):
```php
'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36...'
```

#### Image Conversion (IMPORTANT)
Nitter proxies images through its own domain. These links break when Nitter is down.

**Convert at import time:**
```
https://nitter.net/pic/media%2FHIo7-W7bgAExv5s.jpg 
  → https://pbs.twimg.com/media/HIo7-W7bgAExv5s.jpg
```

Handled by `nitter_image_to_twitter_cdn()` in `UrlEnricher`.

#### OG Tag Format
```html
<meta property="og:title" content="Author Name (@handle)">
<meta property="og:description" content="Tweet text">
<meta property="og:image" content="...">
```

#### Data Extracted
- ✅ Author name
- ✅ Handle (@username)
- ✅ Tweet text/caption
- ✅ Image/media
- ✅ Date

#### Potential Improvements (Future - DO NOT IMPLEMENT YET)
1. **Fallback chain** if Nitter fails (alternative Nitter instances: nitter.1d4.us, nitter.kavin.rocks, etc.)
2. **Better video detection** (parse `og:video:url` separately)
3. **Reply context** (extract quoted/referenced tweets)
4. **URL parameter cleanup** (remove tracking params like `?s=20&t=abc`)
5. **Rate limiting** for bulk imports (add 1s delay between requests)

**Current advice:** Leave X/Twitter handling alone. It's working well and isolated from other platforms.

---

### 👥 Facebook (facebook.com)

**Status:** ✅ Working  
**Handler File:** `includes/UrlEnricher.php` → `fetch_og_tags()` with standard `wp_safe_remote_get()`  
**Parser:** `PostParser::parse_meta_style()`

#### User-Agent
Same as Instagram — honest identifier required:
```php
'user-agent' => 'Mozilla/5.0 (compatible; Fairgo-SocialMediaPosts/1.0; +https://wordpress.org/)',
```

#### HTTP Headers
Minimal set (same as Instagram):
```php
'headers' => [
    'Accept'          => 'text/html,application/xhtml+xml',
    'Accept-Language' => 'en;q=0.9',
],
```

#### OG Tag Format
Structured format similar to Instagram:
```html
<meta property="og:title" content="Author Name">
<meta property="og:description" content="Post content with metadata">
<meta property="og:image" content="...">
```

#### Data Extracted
- ✅ Author name
- ✅ Post content
- ✅ Image/thumbnail
- ✅ Engagement metrics (if available in OG tags)

---

### 🎵 TikTok (tiktok.com, vm.tiktok.com)

**Status:** ✅ Works via standard handler  
**Handler File:** `includes/UrlEnricher.php` → `fetch_og_tags()` with standard `wp_safe_remote_get()`  
**Parser:** Default (raw OG description extraction)

#### User-Agent
Honest identifier (same as Instagram/Facebook):
```php
'user-agent' => 'Mozilla/5.0 (compatible; Fairgo-SocialMediaPosts/1.0; +https://wordpress.org/)',
```

#### OG Tag Format
```html
<meta property="og:title" content="Creator Name - TikTok">
<meta property="og:description" content="Video description">
<meta property="og:image" content="...">
```

#### Data Extracted
- ✅ Creator name
- ✅ Video description
- ✅ Thumbnail image
- ✅ Video metadata (basic)

#### Notes
- TikTok serves OG tags publicly
- No special parsing needed beyond standard extraction
- Video extraction relies on OG tags (true video URL extraction may require additional API calls)

---

### 💼 LinkedIn (linkedin.com)

**Status:** ✅ Works via standard handler  
**Handler File:** `includes/UrlEnricher.php` → `fetch_og_tags()` with standard `wp_safe_remote_get()`  
**Parser:** Default (raw OG description extraction)

#### User-Agent
Honest identifier (same as Instagram/Facebook):
```php
'user-agent' => 'Mozilla/5.0 (compatible; Fairgo-SocialMediaPosts/1.0; +https://wordpress.org/)',
```

#### OG Tag Format
```html
<meta property="og:title" content="Post title or author name">
<meta property="og:description" content="Post content">
<meta property="og:image" content="...">
```

#### Data Extracted
- ✅ Author/post title
- ✅ Post content
- ✅ Image/thumbnail
- ✅ Engagement info (if in OG tags)

#### Notes
- LinkedIn may have authentication requirements for some content
- Falls back gracefully if OG tags aren't accessible
- No special parsing needed

---

## Adding New Platforms

### Safe Pattern

1. **Create a URL detection method:**
```php
private function is_platform_url( string $url ): bool {
    $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
    $host = preg_replace( '/^www\./', '', $host );
    return in_array( $host, [ 'platform.com', 'alt.platform.com' ], true );
}
```

2. **Create a fetch handler (if needed):**
```php
private function fetch_og_tags_via_platform( string $url ): ?array {
    // Custom logic here
    // Return array of OG tags or null
}
```

3. **Route in `fetch_og_tags()`:**
```php
if ( $this->is_platform_url( $url ) ) {
    return $this->fetch_og_tags_via_platform( $url );
}
if ( $this->is_x_url( $url ) ) {
    return $this->fetch_og_tags_via_nitter( $url );
}

// Fall back to standard handler for all others
return $this->standard_fetch_via_wp_safe_remote_get( $url );
```

4. **Add a parser method in `PostParser` (if needed):**
```php
private static function parse_platform_post( string $title, string $description ): array {
    // Extract structured data from OG tags
    return [
        'author'   => '...',
        'handle'   => '...',
        'caption'  => '...',
        'date'     => '...',
    ];
}
```

5. **Route in `PostParser::parse()`:**
```php
if ( 'platform' === $platform ) {
    return self::parse_platform_post( $title, $description );
}
```

### Key Principles
- **Isolation:** New platform handlers never touch other platforms' code
- **Detection first:** Always check with `is_<platform>_url()` before routing
- **Fail gracefully:** Default case should handle unknown platforms without error
- **Document why:** Add comments explaining why special handling is needed (auth requirements, OG format, etc.)

---

## Testing & Debugging

### Test Each Platform With
1. **Public post URL** (ensure you have permission to import)
2. **Verify OG tag extraction:**
   - Check browser DevTools → Network tab → Response headers
   - Look for `og:title`, `og:description`, `og:image`
3. **Verify parser output:**
   - Add debug logging in `PostParser::parse()`
   - Verify all expected fields are extracted

### Debug Logging
Add to `UrlEnricher.php`:
```php
error_log( 'Platform: ' . $platform );
error_log( 'OG Tags: ' . json_encode( $og_tags ) );
error_log( 'Parsed: ' . json_encode( $parsed ) );
```

### Common Issues

| Issue | Platform | Solution |
|-------|----------|----------|
| "Bot detected" | Instagram, Facebook | Use honest user-agent only, minimal headers |
| 404 / not found | X/Twitter | Nitter might be down; check alternative instances |
| Images broken | X/Twitter | Verify `nitter_image_to_twitter_cdn()` conversion |
| No metadata | Any platform | Check OG tags in browser; platform may not expose them |
| Auth required | LinkedIn | May need to implement proxy fetcher (low priority) |

---

## Troubleshooting

### Instagram Imports Fail
**Symptom:** 403 Forbidden or bot detection  
**Check:**
1. User-agent is exactly: `Mozilla/5.0 (compatible; Fairgo-SocialMediaPosts/1.0; +https://wordpress.org/)`
2. No extra headers (no `DNT`, `Connection`, `Upgrade-Insecure-Requests`)
3. URL is valid and public
4. Try URL directly in browser (private window) to confirm it's accessible

### X/Twitter Imports Fail
**Symptom:** 404 or empty OG tags  
**Check:**
1. URL is in format: `https://x.com/user/status/ID`
2. Nitter (nitter.net) is accessible and not rate-limited
3. Tweet is public (not deleted or private)
4. Check error logs for Nitter response

### Generic Platform Imports Fail
**Symptom:** No data extracted  
**Check:**
1. OG tags exist in HTML (inspect with browser)
2. Platform is publicly accessible (no login wall)
3. Check if URL needs special encoding (spaces, special chars)
4. Verify `PostParser::parse()` default case works as fallback

---

## Change Log

**2026-05-20** — Created main import procedures guide consolidating all platform rules and handling procedures.
