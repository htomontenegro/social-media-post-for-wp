# Instagram User-Agent Rule

## Issue
Instagram blocks HTTP requests with aggressive browser user-agents (like Chrome 120.0.0.0), preventing the plugin from fetching Open Graph metadata for Instagram posts.

## Solution
Use a non-aggressive, identifier-based user-agent string:

```php
'user-agent' => 'Mozilla/5.0 (compatible; Fairgo-SocialMediaPosts/1.0; +https://wordpress.org/)',
```

Keep HTTP headers minimal:
```php
'headers' => [
    'Accept'          => 'text/html,application/xhtml+xml',
    'Accept-Language' => 'en;q=0.9',
],
```

## Why This Works
- **v1 approach** (works): Honest, lightweight identifier → Instagram allows the request
- **v2 approach** (broken): Aggressive Chrome browser mimicry → Instagram detects and blocks as bot

## Where This Applies
File: `includes/UrlEnricher.php`
Function: `fetch_og_tags()`

Instagram OG tags are publicly available but behind Instagram's bot detection. The honest approach bypasses this better than pretending to be a real browser.

## What Gets Imported
When this works correctly, Instagram posts import with:
- ✅ Post description/caption
- ✅ Thumbnail image
- ✅ Author name & handle
- ✅ Like/comment counts
- ✅ Post date

## Do Not Change
- ❌ Don't use realistic browser user-agents (Chrome, Firefox, Safari versions)
- ❌ Don't add extra HTTP headers like `DNT`, `Connection`, `Upgrade-Insecure-Requests`
- ❌ Don't add `Accept-Encoding: gzip, deflate`

These appear to Instagram's bot detection as a suspicious scraper.
