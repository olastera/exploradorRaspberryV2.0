# Task 7 — Card CSS Class Migration

**Status:** ✅ Complete

## Changes Made

**File:** `views/user-cards.php`

### Folder cards (lines 22-30)
- `<a>` wrapper: `class="card h-100 border-0 text-decoration-none"` + inline styles → `class="movie-card text-decoration-none"`
- Poster div: added `class="movie-card-poster"`, removed redundant inline styles (`position:relative;overflow:hidden;border-radius...;background...`), kept `padding-top:133%`
- Body div: `class="card-body text-center py-3 d-flex flex-column align-items-center"` + inline styles → `class="movie-card-body text-center py-3 d-flex flex-column align-items-center"` (no inline styles)
- Title `<p>`: `class="card-text small text-truncate w-100 mb-0 mt-2 text-light"` → `class="title mb-0"`

### Video cards (lines 42-54)
- Card wrapper: `class="card h-100 border-0"` + inline styles → `class="movie-card"`
- Poster div: added `class="movie-card-poster"`, removed redundant inline styles, kept `padding-top:133%`
- Body div: `class="card-body py-3"` + inline styles → `class="movie-card-body py-3"`
- Title `<p>`: `class="card-text small text-truncate mb-2 text-light"` → `class="title mb-2"`

### JS fix (line 92)
- Updated search filter selector from `.card-text` to `.title` to match the new class name

## Verification
- `php -l views/user-cards.php` — No syntax errors
