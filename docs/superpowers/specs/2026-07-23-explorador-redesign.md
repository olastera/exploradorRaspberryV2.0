# Explorador UI Redesign — Petify-Inspired

## Overview

Redesign the `/mnt/disco/explorador/` media explorer with a Petify-inspired admin layout: sidebar navigation, light/dark mode toggle, card-based content views, and an enhanced dashboard. Pure PHP/JS — no build tools.

## Architecture

### Layout System

A shared layout partial replaces the current per-page standalone HTML. Each page includes `views/layouts/main-header.php` (head + sidebar open) and `views/layouts/main-footer.php` (sidebar close + scripts).

**`views/layouts/main-header.php`:**
- `<!DOCTYPE html>` through `<head>` (meta, CDN links, CSS) + sidebar open + `<main>` open
- Accepts `$pageTitle` variable for `<title>`
- CSS variables for theming
- Sidebar HTML (fixed left, 260px, collapsible to 64px)

**`views/layouts/main-footer.php`:**
- `</main>` + sidebar close + modal partials + `<script>` + `</body></html>`

### Sidebar (260px → 64px)

```
+------------------------------------------+
| [icon] Explorador           [collapse btn]|
|------------------------------------------|
| [film]    Películas                      |
| [music]   Música                         |
| [file]    Documentos                     |
| [graph]   Dashboard        (admin only)  |
| [repeat]  Conversiones     (admin only)  |
|------------------------------------------|
| [sun/moon] Modo claro/oscuro             |
| [logout]  Cerrar sesión                  |
+------------------------------------------+
```

Collapsed state: 64px wide, icons only, labels hidden. Toggle via JS `sidebar-collapsed` class on `<body>`.

### Color System

CSS custom properties on `:root` and `[data-theme="light"]`:

```css
:root {
  --bg: #0F172A;
  --sidebar-bg: #0B1120;
  --card-bg: #1E293B;
  --text: #F1F5F9;
  --text-muted: #94A3B8;
  --accent: #3B82F6;
  --accent-hover: #2563EB;
  --border: rgba(255,255,255,0.08);
  --hover: rgba(59,130,246,0.08);
  --shadow: 0 4px 20px rgba(0,0,0,0.3);
}

[data-theme="light"] {
  --bg: #F8FAFC;
  --sidebar-bg: #F1F5F9;
  --card-bg: #FFFFFF;
  --text: #0F172A;
  --text-muted: #64748B;
  --accent: #2563EB;
  --accent-hover: #1D4ED8;
  --border: rgba(0,0,0,0.08);
  --hover: rgba(37,99,235,0.06);
  --shadow: 0 4px 20px rgba(0,0,0,0.08);
}
```

### Light/Dark Toggle

Button in sidebar. Toggles `data-theme` attribute on `<html>`. Persisted in `localStorage`. JS sets it on page load.

---

## Views Redesign

### Movies — Admin (`views/admin-movies.php`)

Card grid (not table) with poster focus:
- 4-5 cols desktop, 3 cols tablet, 2 cols mobile
- Poster image (height 280px), hover overlay with play/rename/delete/move icons
- Title truncate below poster
- Multi-select with checkbox + floating action bar for batch ops
- Folder items as cards too (folder icon + name)
- Current path shown in breadcrumb above grid

### Movies — User (`views/user-cards.php`)

Refined existing card grid:
- Same grid as admin but simpler overlay (play + copy only)
- Search bar + per-page selector top-right
- Pagination bottom

### Music (`views/music/album-grid.php`)

Keep current album grid but apply new card design:
- Square album covers with equal aspect ratio
- Play on hover overlay
- Track listing on click (modal stays)

### Documents (`views/docs/file-list.php`)

Table view stays (best for files) but restyled:
- Styled table with icon per file type
- Row hover highlight
- Inline rename, download, delete buttons
- Preview modal

---

## Dashboard (`dashboard.php`)

Two tabs: "Biblioteca" (default) and "Sistema"

**Biblioteca tab:**
- Row 1: 4 stat cards — Películas (count), Música (count), Documentos (count), Espacio usado
- Row 2: Donut chart (library distribution) + bar chart (recent activity, last 7 days)
- Row 3: Recent files table (last 10 modified across all libraries)

**Sistema tab:**
- Same as current: CPU, RAM, Disk, Temp, Uptime, Load
- Cards more compact, stats side by side

All stats fetched via AJAX every 10s (library tab) / 5s (system tab). Charts rendered with inline SVG (no chart library dependency).

---

## File Changes

| File | Action |
|---|---|
| `views/layouts/main-header.php` | CREATE — head + sidebar open |
| `views/layouts/main-footer.php` | CREATE — sidebar close + scripts |
| `index.php` | REWRITE — strip inline CSS/HTML, use layout partials, sidebar, card grid |
| `views/admin-movies.php` | REWRITE — card grid, hover overlays, multi-select bar |
| `views/user-cards.php` | REFINE — card styling, same grid |
| `views/music/album-grid.php` | REFINE — card styling |
| `views/docs/file-list.php` | REFINE — table styling |
| `views/partials/*.php` | KEEP — modals unchanged |
| `dashboard.php` | REWRITE — library tab + system tab, SVG charts |
| `conversiones.php` | REFINe — sidebar integration, styling |
| `src/Auth/Auth.php` | REFINE — apply new theme CSS (no layout partials for login) |
| `assets/css/app.css` | CREATE — extracted CSS (moved from inline) |
| `assets/js/app.js` | CREATE — sidebar toggle, theme toggle, shared utils |

---

## Implementation Order

1. Create `assets/css/app.css` and `assets/js/app.js` with theme system
2. Create `views/layouts/main-header.php` and `views/layouts/main-footer.php`
3. Refactor `index.php` to use layout partials + sidebar
4. Rewrite `views/admin-movies.php` (card grid)
5. Refine `views/user-cards.php` (card styling)
6. Refine `views/music/album-grid.php`
7. Refine `views/docs/file-list.php`
8. Rewrite `dashboard.php` (library + system tabs, SVG charts)
9. Refine `conversiones.php` (sidebar)
10. Refine `src/Auth/Auth.php` (theme CSS)
11. Verify `php -l` on all files + reload Apache
