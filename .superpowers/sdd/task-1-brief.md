# Task 1: Create Theme CSS (`assets/css/app.css`)

**Files:**
- Create: `assets/css/app.css`

**Interfaces:**
- Produces: CSS custom properties consumed by all pages. Class names: `.sidebar`, `.sidebar-collapsed`, `.sidebar-link`, `.sidebar-link.active`, `.theme-toggle`, `.stat-card`, `.movie-card`, `.movie-card-overlay`, `.dashboard-chart-container`, `.sidebar-logo`

- [ ] **Create `assets/css/app.css` with theme system and all component styles**

Write the full CSS to `assets/css/app.css`. It must contain ALL of the following sections:

## Theme Variables

```css
:root {
  --bg: #0F172A;
  --sidebar-bg: #0B1120;
  --card-bg: #1E293B;
  --text: #F1F5F9;
  --text-muted: #94A3B8;
  --accent: #3B82F6;
  --accent-hover: #2563EB;
  --accent-rgb: 59, 130, 246;
  --border: rgba(255,255,255,0.08);
  --hover: rgba(59,130,246,0.08);
  --shadow: 0 4px 20px rgba(0,0,0,0.3);
  --sidebar-width: 260px;
  --sidebar-collapsed-width: 64px;
  --transition: 0.25s ease;
}
[data-theme="light"] {
  --bg: #F8FAFC;
  --sidebar-bg: #F1F5F9;
  --card-bg: #FFFFFF;
  --text: #0F172A;
  --text-muted: #64748B;
  --accent: #2563EB;
  --accent-hover: #1D4ED8;
  --accent-rgb: 37, 99, 235;
  --border: rgba(0,0,0,0.08);
  --hover: rgba(37,99,235,0.06);
  --shadow: 0 4px 20px rgba(0,0,0,0.08);
}
```

## Reset & Body

```css
* { box-sizing: border-box; }
body {
  margin: 0;
  background: var(--bg);
  color: var(--text);
  font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
  display: flex;
  min-height: 100vh;
}
a { color: var(--accent); text-decoration: none; }
a:hover { color: var(--accent-hover); }
```

## Sidebar

```css
.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: var(--sidebar-width);
  height: 100vh;
  background: var(--sidebar-bg);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  transition: width var(--transition);
  z-index: 1040;
  overflow-x: hidden;
}
.sidebar-collapsed .sidebar { width: var(--sidebar-collapsed-width); }
.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  border-bottom: 1px solid var(--border);
  min-height: 60px;
}
.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text);
  white-space: nowrap;
  overflow: hidden;
}
.sidebar-collapsed .sidebar-logo span { display: none; }
.sidebar-logo i { font-size: 1.4rem; color: var(--accent); }
.sidebar-links {
  flex: 1;
  padding: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.sidebar-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.7rem 0.85rem;
  border-radius: 8px;
  color: var(--text-muted);
  white-space: nowrap;
  overflow: hidden;
  transition: background var(--transition), color var(--transition);
}
.sidebar-link:hover { background: var(--hover); color: var(--text); }
.sidebar-link.active { background: rgba(var(--accent-rgb), 0.12); color: var(--accent); }
.sidebar-link i { font-size: 1.2rem; min-width: 20px; text-align: center; }
.sidebar-collapsed .sidebar-link span { display: none; }
.sidebar-collapsed .sidebar-link { justify-content: center; padding: 0.7rem; }
.sidebar-bottom {
  padding: 0.75rem;
  border-top: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.sidebar-collapse-btn {
  background: none;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  padding: 0.4rem;
  border-radius: 6px;
  transition: background var(--transition);
}
.sidebar-collapse-btn:hover { background: var(--hover); color: var(--text); }
```

## Main Content

```css
.main-content {
  margin-left: var(--sidebar-width);
  flex: 1;
  padding: 1.25rem;
  transition: margin-left var(--transition);
  min-width: 0;
}
.sidebar-collapsed .main-content { margin-left: var(--sidebar-collapsed-width); }
```

## Movie Grid & Cards

```css
.movie-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1.25rem;
}
.movie-card {
  background: var(--card-bg);
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid var(--border);
  transition: transform 0.2s, box-shadow 0.2s;
}
.movie-card:hover { transform: translateY(-4px); box-shadow: var(--shadow); }
.movie-card-poster {
  position: relative;
  aspect-ratio: 2/3;
  overflow: hidden;
  background: var(--card-bg);
}
.movie-card-poster img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s;
}
.movie-card:hover .movie-card-poster img { transform: scale(1.05); }
.movie-card-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.6);
  opacity: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: opacity 0.2s;
}
.movie-card:hover .movie-card-overlay { opacity: 1; }
.movie-card-overlay .btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}
.movie-card-body {
  padding: 0.75rem;
}
.movie-card-body .title {
  font-size: 0.875rem;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.movie-card-body .subtitle {
  font-size: 0.75rem;
  color: var(--text-muted);
}
.movie-card-folder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  gap: 0.5rem;
  text-align: center;
}
.movie-card-folder i { font-size: 2.5rem; color: var(--accent); }
```

## Stat Cards

```css
.stat-card {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 1.25rem;
}
.stat-card .stat-icon { font-size: 1.8rem; }
.stat-card .stat-value { font-size: 1.8rem; font-weight: 700; }
.stat-card .stat-label { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
```

## Breadcrumb

```css
.breadcrumb { background: transparent; padding: 0; margin: 0; }
.breadcrumb-item + .breadcrumb-item::before { color: var(--text-muted); }
.breadcrumb-item a, .breadcrumb-item.active { color: var(--text-muted); }
```

## Album Grid

```css
.album-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1rem;
}
.album-card {
  background: var(--card-bg);
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid var(--border);
  transition: transform 0.2s, box-shadow 0.2s;
}
.album-card:hover { transform: translateY(-3px); box-shadow: var(--shadow); }
.album-card-cover {
  aspect-ratio: 1;
  overflow: hidden;
  position: relative;
}
.album-card-cover img { width: 100%; height: 100%; object-fit: cover; }
.album-card-cover .play-overlay {
  position: absolute; inset: 0;
  background: rgba(0,0,0,0.5);
  opacity: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: opacity 0.2s;
}
.album-card:hover .play-overlay { opacity: 1; }
.album-card-body { padding: 0.65rem; }
.album-card-body .title { font-size: 0.85rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
```

## Docs Table

```css
.docs-table { color: var(--text); }
.docs-table > :not(caption) > * > * { border-bottom-color: var(--border); }
.docs-table tbody tr:hover { background: var(--hover); }
```

## Dashboard

```css
.dashboard-tabs .nav-link { color: var(--text-muted); border: none; padding: 0.6rem 1.2rem; }
.dashboard-tabs .nav-link.active { color: var(--accent); background: transparent; border-bottom: 2px solid var(--accent); }
```

## Toolbar

```css
.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}
```

## Selection Bar

```css
.selection-bar {
  position: fixed;
  bottom: 0;
  left: var(--sidebar-width);
  right: 0;
  background: var(--card-bg);
  border-top: 1px solid var(--border);
  padding: 0.75rem 1.25rem;
  display: none;
  align-items: center;
  gap: 0.75rem;
  z-index: 1030;
  transition: left var(--transition);
}
.sidebar-collapsed .selection-bar { left: var(--sidebar-collapsed-width); }
.selection-bar.show { display: flex; }
```

## Scrollbar

```css
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
```

## Misc (form controls, buttons, shimmer, etc.)

```css
.text-muted { color: var(--text-muted) !important; }
.card-custom { background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; }
.alert-dismissible { background: var(--card-bg); border: 1px solid var(--border); color: var(--text); }
.form-control, .form-select {
  background: var(--bg);
  border-color: var(--border);
  color: var(--text);
}
.form-control:focus, .form-select:focus {
  background: var(--bg);
  border-color: var(--accent);
  color: var(--text);
  box-shadow: 0 0 0 0.2rem rgba(var(--accent-rgb), 0.25);
}
.btn-outline-light {
  border-color: var(--border);
  color: var(--text);
}
.btn-outline-light:hover {
  background: var(--hover);
  border-color: var(--border);
  color: var(--text);
}
.shimmer {
  background: linear-gradient(90deg, var(--card-bg) 25%, rgba(var(--accent-rgb),0.06) 50%, var(--card-bg) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}
@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
@media (prefers-reduced-motion: reduce) {
  .shimmer, .movie-card, .album-card { animation: none !important; transition: none !important; }
  .movie-card:hover { transform: none; }
}
```

- [ ] **Verify file created**: `ls -la assets/css/app.css`
