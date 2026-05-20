# Why Twitter & Instagram Had Different Data Extraction

## The Problem

**Before the fix:**
- ✅ Instagram imports captured: description, author name, handle, likes, comments, date
- ❌ Twitter imports captured: only description

**Why the difference?**

## How It Works

### Data Flow
```
URL → UrlEnricher → fetch_og_tags → OG tag extraction
  ↓
PostParser → Platform-specific parsing → Structured data
```

### OG Tag Formats

**Instagram OG tags** (predictable structured format):
```html
<meta property="og:title" content="Author Name - Bio on Instagram: \"Caption\"">
<meta property="og:description" content="123 likes, 456 comments - @handle on date: \"Caption\"">
<meta property="og:image" content="...">
```

**X/Twitter via Nitter OG tags** (simple format):
```html
<meta property="og:title" content="Author Name (@handle)">
<meta property="og:description" content="Tweet text">
<meta property="og:image" content="...">
```

### PostParser.php Handling

**Old behavior:**
- Instagram → `parse_meta_style()` with regex to extract all fields
- X/Twitter → `default` case, only extracted caption

**New behavior (fixed):**
- Instagram → `parse_meta_style()` - extracts author, handle, likes, comments, date from structured OG format
- X/Twitter → `parse_x_post()` - extracts author name and handle from "Name (@handle)" format, uses description as caption
- Facebook → `parse_meta_style()` - same pattern as Instagram

## Why the Formats Differ

**Instagram's format:** Legacy OG tag structure they've maintained for years. Very structured with metadata in the title and description.

**Nitter's format:** Simple, clean OG tags. Makes sense for a lightweight Twitter frontend. Author info comes from HTML structure, not OG tags themselves.

## What Gets Extracted Now

### Instagram Post
```
Author Name: extracted from "Name - Bio on Instagram"
Handle:      extracted from "123 likes, 456 comments - @handle on date"
Bio:         extracted from "Name - Bio on Instagram"
Caption:     extracted from quoted text
Likes:       extracted from numeric count
Comments:    extracted from numeric count
Date:        extracted from "on date"
Image:       from og:image (direct CDN URL)
```

### X/Twitter Post
```
Author Name: extracted from "Name (@handle)"
Handle:      extracted from "Name (@handle)"
Caption:     full description (tweet text)
Image:       from og:image, converted from nitter.net/pic/... → pbs.twimg.com/...
```

## Why Image Conversion Matters

Nitter proxies Twitter media through its own domain:
```
og:image = https://nitter.net/pic/media%2FHIo7-W7bgAExv5s.jpg
```

Storing this URL means the image breaks whenever Nitter is down or rate-limits the request ("Upstream Error").

The `nitter_image_to_twitter_cdn()` method in `UrlEnricher` converts it at import time to the stable direct CDN URL:
```
https://pbs.twimg.com/media/HIo7-W7bgAExv5s.jpg
```

## JS Import Bypass (Removed)

`import.js` previously short-circuited all X/Twitter URLs to a manual entry form before even attempting a server fetch. This was the original workaround before Nitter + PostParser worked correctly.

```js
// OLD (removed) — blocked auto-import entirely for X
if (isXUrl(url)) {
    showManualEntry(url, $row, deferred);
    return deferred.promise();
}
```

This block was removed. X/Twitter URLs now flow through the same automatic AJAX path as Instagram.
