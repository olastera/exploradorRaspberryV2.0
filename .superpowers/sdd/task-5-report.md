# Task 5 Report: Restructure index.php

**Status:** DONE
**Date:** 2026-07-23

## Changes Made

**File:** `/mnt/disco/explorador/index.php`

### What was done

Replaced the inline HTML (lines 240–682) with layout partial includes:

1. **Preserved lines 1–239** — All PHP logic (auth, POST handlers, file listing, breadcrumbs, move logic, upload detection) kept exactly as-is.
2. **Added** `<?php $pageTitle = ...; include __DIR__ . '/views/layouts/main-header.php'; ?>` after the closing `?>`.
3. **Kept alert blocks** (identical to original lines 447–460).
4. **Kept breadcrumbs + actions bar** (identical to original lines 462–486).
5. **Kept main content includes** (identical to original lines 488–497).
6. **Added** page-specific JS via `ob_start()`/`$pageScript` for poster loading, then `include __DIR__ . '/views/layouts/main-footer.php';`.
7. **Preserved** both `deleteRecursive()` and `handleUpload()` PHP functions at the bottom of the file.

### Verifications

- `php -l /mnt/disco/explorador/index.php` — **No syntax errors**
- File reduced from 682 lines to 365 lines (inline HTML/CSS/JS removed, now in layout partials)

### What was removed

- Inline DOCTYPE, `<html>`, `<head>`, `<style>` block (all CSS), `<body>` tags
- Library tabs nav (moved to main-header.php)
- Navbar (moved to main-header.php)
- Modals (moved to main-footer.php)
- Bootstrap CSS/JS CDN links (in layout partials)
- Inline JS functions `fullUrl()`, `playVideo()`, `copyToClipboard()`, `searchTrailer()`, `showRenameModal()`, `showUploadModal()`, `showNewFolderModal()`, `showPosterModal()` (moved to app.js)
- Poster loading `DOMContentLoaded` listener (now in `$pageScript`)
- `var library`, `var csrfToken`, `var isAdmin` global variables (now set in main-header.php or app.js context)
