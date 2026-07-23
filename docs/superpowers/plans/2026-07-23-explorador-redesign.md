# Explorador UI Redesign Implementation Plan

> **For agentic workers:** Use superpowers:subagent-driven-development (recommended) to implement this plan task-by-task. Steps use checkbox syntax.

**Goal:** Redesign the Explorador UI with Petify-inspired sidebar navigation, light/dark mode toggle, card-based movie grid, and enhanced dashboard.

**Architecture:** Shared layout partials (`main-header.php`/`main-footer.php`) replace per-page HTML. CSS variables handle theming. JS manages sidebar collapse + theme toggle + localStorage persistence. PHP logic stays in `index.php`.

**Tech Stack:** PHP 8.x (no curl), Bootstrap 5.3, Bootstrap Icons, vanilla JS, inline SVG for charts.

## Global Constraints

- No build tools, no npm, no composer — pure vanilla PHP/JS/CSS
- No curl extension — use `file_get_contents` for HTTP
- All `<i>` icons must have `aria-hidden="true"`
- All `<html>` must have `color-scheme: dark` and `theme-color`
- All pages must pass `php -l` syntax check
- Sidebar must work without JS (nav links are `<a>` elements)

---

### Task 1: Create Theme CSS (`assets/css/app.css`)

**Files:**
- Create: `assets/css/app.css`

**Interfaces:**
- Produces: CSS custom properties consumed by all pages. Class names: `.sidebar`, `.sidebar-collapsed`, `.sidebar-link`, `.sidebar-link.active`, `.theme-toggle`, `.stat-card`, `.movie-card`, `.movie-card-overlay`, `.dashboard-chart-container`, `.sidebar-logo`

- [ ] **Create `assets/css/app.css` with theme system and all component styles**

Write to `assets/css/app.css`:

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

/* Sidebar */
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

/* Main Content */
.main-content {
  margin-left: var(--sidebar-width);
  flex: 1;
  padding: 1.25rem;
  transition: margin-left var(--transition);
  min-width: 0;
}
.sidebar-collapsed .main-content { margin-left: var(--sidebar-collapsed-width); }

/* Cards */
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

/* Stats cards */
.stat-card {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 1.25rem;
}
.stat-card .stat-icon { font-size: 1.8rem; }
.stat-card .stat-value { font-size: 1.8rem; font-weight: 700; }
.stat-card .stat-label { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }

/* Breadcrumb */
.breadcrumb { background: transparent; padding: 0; margin: 0; }
.breadcrumb-item + .breadcrumb-item::before { color: var(--text-muted); }
.breadcrumb-item a, .breadcrumb-item.active { color: var(--text-muted); }

/* Album grid */
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

/* Docs table */
.docs-table { color: var(--text); }
.docs-table > :not(caption) > * > * { border-bottom-color: var(--border); }
.docs-table tbody tr:hover { background: var(--hover); }

/* Dashboard */
.dashboard-tabs .nav-link { color: var(--text-muted); border: none; padding: 0.6rem 1.2rem; }
.dashboard-tabs .nav-link.active { color: var(--accent); background: transparent; border-bottom: 2px solid var(--accent); }

/* Toolbar */
.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

/* Selection bar */
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

/* Scrollbar */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

/* Misc */
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

---

### Task 2: Create JS Utilities (`assets/js/app.js`)

**Files:**
- Create: `assets/js/app.js`

**Interfaces:**
- Produces: `window.toggleSidebar()`, `window.toggleTheme()`, `window.fullUrl()`, `window.copyToClipboard()`, `window.playVideo()`, `window.showRenameModal()`, `window.showUploadModal()`, `window.showNewFolderModal()`, `window.searchTrailer()`, `window.showPosterModal()`

- [ ] **Create `assets/js/app.js`**

Write to `assets/js/app.js`:

```javascript
// Sidebar toggle
function toggleSidebar() {
  document.body.classList.toggle('sidebar-collapsed');
  var collapsed = document.body.classList.contains('sidebar-collapsed');
  localStorage.setItem('sidebarCollapsed', collapsed);
}

// Theme toggle
function toggleTheme() {
  var html = document.documentElement;
  var theme = html.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
  html.setAttribute('data-theme', theme);
  localStorage.setItem('theme', theme);
}

// Init theme from localStorage
(function() {
  var theme = localStorage.getItem('theme');
  if (theme) document.documentElement.setAttribute('data-theme', theme);
  var sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
  if (sidebarCollapsed) document.body.classList.add('sidebar-collapsed');
})();

// Build full URL for copied links
function fullUrl(path) {
  var base = window.location.origin + window.location.pathname.replace(/\/+$/, '');
  var sep = base.indexOf('?') >= 0 ? '&' : '?';
  return base + sep + 'file=' + encodeURIComponent(path);
}

function playVideo(url, title) {
  var w = window.open('', '_blank');
  w.document.write('<html><head><title>' + (title || 'Reproducir') + '</title>');
  w.document.write('<meta name="viewport" content="width=device-width,initial-scale=1">');
  w.document.write('<style>body{margin:0;background:#000;display:flex;align-items:center;justify-content:center;height:100vh;}');
  w.document.write('video{max-width:100%;max-height:100vh;}</style></head><body>');
  w.document.write('<video src="' + url + '" controls autoplay style="width:100%;height:auto;max-height:100vh;"></video>');
  w.document.write('</body></html>');
  w.document.close();
}

function copyToClipboard(url) {
  navigator.clipboard.writeText(url).then(function() {
    var toast = document.createElement('div');
    toast.setAttribute('aria-live', 'polite');
    toast.className = 'position-fixed bottom-0 end-0 p-3';
    toast.style.zIndex = '9999';
    toast.innerHTML = '<div class="toast show align-items-center text-bg-success border-0"><div class="d-flex"><div class="toast-body">URL copiada al portapapeles</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>';
    document.body.appendChild(toast);
    setTimeout(function() { toast.remove(); }, 3000);
  });
}

function searchTrailer(title) {
  var query = encodeURIComponent(title.replace(/[._]/g, ' ') + ' trailer');
  window.open('https://www.youtube.com/results?search_query=' + query, '_blank');
}

function showRenameModal(currentName) {
  document.getElementById('renameOldInput').value = currentName;
  document.getElementById('renameNewInput').value = currentName;
  document.getElementById('renameNewInput').focus();
  new bootstrap.Modal(document.getElementById('renameModal')).show();
}

function showUploadModal() {
  new bootstrap.Modal(document.getElementById('uploadModal')).show();
}

function showNewFolderModal() {
  new bootstrap.Modal(document.getElementById('newFolderModal')).show();
}

function showPosterModal(data, query) {
  var modalImg = document.getElementById('posterModalImage');
  modalImg.src = '';
  modalImg.classList.add('d-none');
  document.getElementById('posterModalTitle').textContent = data.title + (data.year ? ' (' + data.year + ')' : '');
  document.getElementById('posterModalLink').href = data.imdb_url || '#';
  document.getElementById('posterModalRating').textContent = data.rating ? '\u2605 ' + data.rating : '';
  document.getElementById('posterModalGenre').textContent = data.genre || '';
  document.getElementById('posterModalRuntime').textContent = data.runtime || '';
  document.getElementById('posterModalPlot').textContent = data.plot || '';
  document.getElementById('posterModalLoader').classList.remove('d-none');
  var modal = new bootstrap.Modal(document.getElementById('posterModal'));
  modal.show();
  modalImg.src = data.poster;
  modalImg.onload = function() {
    document.getElementById('posterModalLoader').classList.add('d-none');
    modalImg.classList.remove('d-none');
  };
  modalImg.onerror = function() {
    document.getElementById('posterModalLoader').classList.add('d-none');
    modalImg.alt = 'No disponible';
  };
}

// Ajax batch poster loading
function loadPosterBatch(posterImages, startIndex, batchSize) {
  batchSize = batchSize || 5;
  for (var i = startIndex; i < startIndex + batchSize && i < posterImages.length; i++) {
    (function(img) {
      if (img.dataset.loaded) return;
      img.dataset.loaded = '1';
      var query = img.dataset.query;
      fetch('imdb_search.php?q=' + encodeURIComponent(query))
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.found && data.poster) {
            img.src = data.poster;
            img.onclick = function() { showPosterModal(data, query); };
            img.style.cursor = 'pointer';
          } else {
            img.style.display = 'none';
          }
          img.classList.remove('shimmer');
        })
        .catch(function() {
          img.classList.remove('shimmer');
          img.style.display = 'none';
        });
    })(posterImages[i]);
  }
}

// Convert functions
function convertToMp4(file) {
  var form = document.createElement('form');
  form.method = 'POST';
  form.action = 'convert.php';
  form.innerHTML = '<input name="action" value="start"><input name="file" value="' + encodeURIComponent(file) + '"><input name="_token" value="' + (window.csrfToken || '') + '">';
  document.body.appendChild(form);
  form.submit();
}

function cancelConversion(id) {
  var form = document.createElement('form');
  form.method = 'POST'; form.action = 'convert.php';
  form.innerHTML = '<input name="action" value="cancel"><input name="id" value="' + id + '"><input name="_token" value="' + (window.csrfToken || '') + '">';
  document.body.appendChild(form); form.submit();
}

function deleteConversion(id) {
  if (!confirm('Eliminar esta conversión?')) return;
  var form = document.createElement('form');
  form.method = 'POST'; form.action = 'convert.php';
  form.innerHTML = '<input name="action" value="delete"><input name="id" value="' + id + '"><input name="_token" value="' + (window.csrfToken || '') + '">';
  document.body.appendChild(form); form.submit();
}

function playConverted(path) {
  window.open('serve.php?file=' + encodeURIComponent(path), '_blank');
}
```

- [ ] **Verify file created**: `ls -la assets/js/app.js`

---

### Task 3: Create Layout Header (`views/layouts/main-header.php`)

**Files:**
- Create: `views/layouts/main-header.php`

**Interfaces:**
- Consumes: `$pageTitle` (string), `$library` (string) for active sidebar link
- Produces: HTML `<html>` open through `<main>` open

- [ ] **Create `views/layouts/main-header.php`**

Write to `views/layouts/main-header.php`:

```php
<?php if (!isset($pageTitle)) $pageTitle = 'Explorador de Medios'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#0F172A">
    <title><?php echo $pageTitle; ?></title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/app.css?v=2">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" role="navigation" aria-label="Navegación principal">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-logo text-decoration-none">
                <i aria-hidden="true" class="bi bi-film"></i>
                <span>Explorador</span>
            </a>
            <button class="sidebar-collapse-btn" onclick="toggleSidebar()" aria-label="Colapsar menú">
                <i aria-hidden="true" class="bi bi-chevron-left"></i>
            </button>
        </div>
        <div class="sidebar-links">
            <a href="?lib=movies" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'index.php' && (!isset($_GET['lib']) || $_GET['lib'] === 'movies')) ? 'active' : ''; ?>">
                <i aria-hidden="true" class="bi bi-film"></i>
                <span>Películas</span>
            </a>
            <a href="?lib=music" class="sidebar-link <?php echo (isset($_GET['lib']) && $_GET['lib'] === 'music') ? 'active' : ''; ?>">
                <i aria-hidden="true" class="bi bi-music-note-beamed"></i>
                <span>Música</span>
            </a>
            <a href="?lib=docs" class="sidebar-link <?php echo (isset($_GET['lib']) && $_GET['lib'] === 'docs') ? 'active' : ''; ?>">
                <i aria-hidden="true" class="bi bi-file-earmark-text"></i>
                <span>Documentos</span>
            </a>
            <?php if (isset($isAdmin) && $isAdmin): ?>
            <hr style="border-color:var(--border);margin:0.5rem 0;">
            <a href="dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                <i aria-hidden="true" class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a href="conversiones.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'conversiones.php' ? 'active' : ''; ?>">
                <i aria-hidden="true" class="bi bi-arrow-repeat"></i>
                <span>Conversiones</span>
            </a>
            <?php endif; ?>
        </div>
        <div class="sidebar-bottom">
            <button class="sidebar-link" onclick="toggleTheme()" style="background:none;border:none;cursor:pointer;width:100%;" aria-label="Cambiar tema">
                <i aria-hidden="true" class="bi bi-sun-fill"></i>
                <span>Modo claro</span>
            </button>
            <a href="?logout=1" class="sidebar-link" style="color:var(--text-muted);">
                <i aria-hidden="true" class="bi bi-box-arrow-right"></i>
                <span>Cerrar sesión</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
```

- [ ] **Verify `php -l views/layouts/main-header.php`** — syntax OK

---

### Task 4: Create Layout Footer (`views/layouts/main-footer.php`)

**Files:**
- Create: `views/layouts/main-footer.php`

**Interfaces:**
- Consumes: `$library` (string), `$isAdmin` (bool), `$csrfToken` (string) for JS globals
- Produces: Modals + `<script>` + close `</main></body></html>`

- [ ] **Create `views/layouts/main-footer.php`**

Write to `views/layouts/main-footer.php`:

```php
    </main>

    <!-- Modals -->
    <?php include __DIR__ . '/../partials/poster-modal.php'; ?>
    <?php include __DIR__ . '/../partials/rename-modal.php'; ?>
    <?php if (isset($isAdmin) && $isAdmin): ?>
        <?php include __DIR__ . '/../partials/upload-modal.php'; ?>
        <?php include __DIR__ . '/../partials/new-folder-modal.php'; ?>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js?v=2"></script>
    <script>
    var library = '<?php echo isset($library) ? $library : ''; ?>';
    var csrfToken = '<?php echo isset($csrfToken) ? $csrfToken : ''; ?>';
    var isAdmin = <?php echo (isset($isAdmin) && $isAdmin) ? 'true' : 'false'; ?>;
    </script>
    <?php if (isset($pageScript)): ?>
    <script><?php echo $pageScript; ?></script>
    <?php endif; ?>
</body>
</html>
```

- [ ] **Verify `php -l views/layouts/main-footer.php`**

---

### Task 5: Refactor `index.php` to Use Layout Partials + Sidebar

**Files:**
- Modify: `index.php` (full rewrite — strip all HTML, inline CSS, and JS; keep only PHP logic)

**Interfaces:**
- Consumes: layout partials from Tasks 3-4
- Produces: page that includes header, sidebar, content area, footer

- [ ] **Rewrite `index.php`**

Read the current file first to preserve all PHP logic ($library, $files, $msg, etc.). Then replace everything after the initial bootstrap include with:

```php
<?php
require_once __DIR__ . '/config/bootstrap.php';
use App\Auth\Auth;
use App\Auth\Csrf;
use App\Media\FileExplorer;
use App\Security\PathValidator;

$currentUser = Auth::requireAuth();
$isAdmin = ($currentUser['role'] === 'admin');
$csrfToken = Csrf::token();

$libraryNames = ['movies' => 'Películas', 'music' => 'Música', 'docs' => 'Documentos'];

// [KEEP ALL EXISTING PHP LOGIC — library detection, POST handling for delete/rename/upload/move,
//  file listing, breadcrumb building, msg/uploadResult etc. The PHP logic stays exactly as-is,
//  from the `$library = $_GET['lib'] ?? 'movies'` line down through the view includes.
//  Only strip the HTML head, navbar, tabs, and footer. The inline CSS and JS are removed.]

$pageTitle = ($libraryNames[$library] ?? 'Explorador') . ' - Explorador de Medios';
$pageTitle = ($libraryNames[$library] ?? 'Explorador') . ' - Explorador de Medios';

include __DIR__ . '/views/layouts/main-header.php';
?>

<!-- Alert messages -->
<?php if ($msg): ?>
    <div class="alert alert-<?php echo $msgType; ?> alert-dismissible fade show py-2">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($uploadResult): ?>
    <div class="alert alert-<?php echo $uploadResult['type']; ?> alert-dismissible fade show py-2">
        <?php echo $uploadResult['msg']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Breadcrumbs + Actions -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="?lib=<?php echo $library; ?>"><i aria-hidden="true" class="bi bi-house-fill"></i></a></li>
            <?php if (!empty($relativePath)): ?>
                <?php foreach ($breadcrumbs as $b): ?>
                    <li class="breadcrumb-item <?php echo ($b['path'] === $relativePath) ? 'active' : '' ?>">
                        <?php if ($b['path'] !== $relativePath): ?>
                            <a href="?lib=<?php echo $library; ?>&path=<?php echo urlencode($b['path']); ?>"><?php echo htmlspecialchars($b['name']); ?></a>
                        <?php else: ?>
                            <?php echo htmlspecialchars($b['name']); ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ol>
    </nav>
    <div class="d-flex gap-2">
        <?php if ($isAdmin && ($library === 'docs' || $library === 'music')): ?>
            <button class="btn btn-sm btn-outline-primary" onclick="showUploadModal()"><i aria-hidden="true" class="bi bi-upload"></i> Subir</button>
            <button class="btn btn-sm btn-outline-secondary" onclick="showNewFolderModal()"><i aria-hidden="true" class="bi bi-folder-plus"></i> Carpeta</button>
        <?php endif; ?>
    </div>
</div>

<!-- Main content -->
<?php if ($library === 'movies' && !$isAdmin): ?>
    <?php include __DIR__ . '/views/user-cards.php'; ?>
<?php elseif ($library === 'movies'): ?>
    <?php include __DIR__ . '/views/admin-movies.php'; ?>
<?php elseif ($library === 'music'): ?>
    <?php include __DIR__ . '/views/music/album-grid.php'; ?>
<?php elseif ($library === 'docs'): ?>
    <?php include __DIR__ . '/views/docs/file-list.php'; ?>
<?php endif; ?>

<?php
// Page-specific JS for poster loading
ob_start();
if ($library === 'movies'):
?>
document.addEventListener('DOMContentLoaded', function() {
    var posterImages = document.querySelectorAll('.poster-thumb[data-query]');
    if (posterImages.length > 0) loadPosterBatch(posterImages, 0, 5);
});
<?php
endif;
$pageScript = ob_get_clean();

include __DIR__ . '/views/layouts/main-footer.php';
?>
```

Note: The PHP logic before the HTML (library detection, POST handling, file listing, breadcrumbs, $msg, $uploadResult) must be kept **exactly as is** from the current `index.php`. Only the HTML template, inline CSS, and JS are replaced.

- [ ] **Verify `php -l index.php`**

---

### Task 6: Rewrite `views/admin-movies.php` as Card Grid

**Files:**
- Modify: `views/admin-movies.php`

- [ ] **Rewrite `views/admin-movies.php`**

Replace the entire current file with a card-based grid view:

```php
<div class="toolbar">
    <div class="d-flex align-items-center gap-2">
        <span class="text-muted small"><?php echo $folderCount + $fileCount; ?> elemento(s)</span>
    </div>
</div>

<?php if (empty($folders) && empty($files)): ?>
    <div class="text-center py-5 text-muted">
        <i aria-hidden="true" class="bi bi-folder2-open" style="font-size:3rem;display:block;margin-bottom:1rem"></i>
        Carpeta vacía
    </div>
<?php else: ?>
    <!-- Multi-select bar -->
    <div class="selection-bar" id="selectionBar">
        <span id="selectedCount" class="text-muted small">0 seleccionados</span>
        <button class="btn btn-sm btn-outline-danger" onclick="deleteSelected()"><i aria-hidden="true" class="bi bi-trash"></i> Eliminar</button>
        <button class="btn btn-sm btn-outline-info" onclick="extractSelected()"><i aria-hidden="true" class="bi bi-box-arrow-up-right"></i> Extraer</button>
        <button class="btn btn-sm btn-outline-success" onclick="moveSelected('musica')"><i aria-hidden="true" class="bi bi-music-note-beamed"></i> Música</button>
        <button class="btn btn-sm btn-outline-primary" onclick="moveSelected('documentos')"><i aria-hidden="true" class="bi bi-file-earmark-text"></i> Documentos</button>
    </div>

    <div class="movie-grid">
        <?php foreach ($folders as $dir): ?>
        <div class="movie-card">
            <a href="?lib=movies&path=<?php echo urlencode($relativePath ? $relativePath . '/' . $dir : $dir); ?>" class="text-decoration-none">
                <div class="movie-card-folder">
                    <i aria-hidden="true" class="bi bi-folder text-warning"></i>
                    <div class="title" style="color:var(--text)"><?php echo htmlspecialchars($dir); ?></div>
                    <div class="subtitle">Carpeta</div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>

        <?php foreach ($files as $file):
            $fileUrl = 'serve.php?file=' . urlencode(($relativePath ? $relativePath . '/' : '') . $file);
            $isVideo = preg_match('/\.(mp4|mkv|avi|mov|webm|ogg|wmv|flv|m4v|ts|vob)$/i', $file);
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $icon = $isVideo ? 'bi-film text-primary' : (in_array($ext, ['mp3','flac','wav','aac','ogg','wma']) ? 'bi-music-note text-success' : (in_array($ext, ['jpg','jpeg','png','gif','webp']) ? 'bi-image text-info' : 'bi-file-earmark text-muted'));
            $cleanName = pathinfo($file, PATHINFO_FILENAME);
        ?>
        <div class="movie-card">
            <div class="movie-card-poster">
                <img class="poster-thumb shimmer" data-query="<?php echo htmlspecialchars($cleanName); ?>" alt="<?php echo htmlspecialchars($file); ?>" width="300" height="450" loading="lazy">
                <div class="movie-card-overlay">
                    <?php if ($isVideo): ?>
                    <button class="btn btn-light" onclick="playVideo('<?php echo $fileUrl; ?>', '<?php echo htmlspecialchars($file); ?>')" title="Reproducir">
                        <i aria-hidden="true" class="bi bi-play-fill"></i>
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-light" onclick="copyToClipboard('<?php echo $fileUrl; ?>')" title="Copiar URL">
                        <i aria-hidden="true" class="bi bi-link"></i>
                    </button>
                    <button class="btn btn-light" onclick="searchTrailer('<?php echo htmlspecialchars($cleanName); ?>')" title="Trailer">
                        <i aria-hidden="true" class="bi bi-youtube" style="color:#dc2626"></i>
                    </button>
                    <button class="btn btn-light" onclick="showRenameModal('<?php echo htmlspecialchars($file); ?>')" title="Renombrar">
                        <i aria-hidden="true" class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-light" onclick="if(confirm('Eliminar <?php echo htmlspecialchars(addslashes($file)); ?>?')){var f=document.createElement('form');f.method='POST';f.innerHTML='<input name=action value=delete><input name=file value=\"<?php echo htmlspecialchars($file, ENT_QUOTES); ?>\"><input name=_token value=\"'+csrfToken+'\">';document.body.appendChild(f);f.submit();}" title="Eliminar">
                        <i aria-hidden="true" class="bi bi-trash" style="color:#dc2626"></i>
                    </button>
                    <?php if ($isVideo): ?>
                    <button class="btn btn-light" onclick="convertToMp4('<?php echo htmlspecialchars($file); ?>')" title="Convertir">
                        <i aria-hidden="true" class="bi bi-arrow-repeat"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="movie-card-body">
                <div class="title"><?php echo htmlspecialchars($file); ?></div>
                <div class="subtitle"><?php echo $isVideo ? 'Vídeo' : strtoupper($ext); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
```

- [ ] **Verify `php -l views/admin-movies.php`**

---

### Task 7: Refine `views/user-cards.php`

**Files:**
- Modify: `views/user-cards.php`

- [ ] **Update `views/user-cards.php`** — apply new card class names, overlay, search/pagination

Read current file, then update:
- Card `div` gets class `movie-card` and inline style `background:var(--card-bg);border:1px solid var(--border);border-radius:12px;overflow:hidden;`
- Poster section gets `movie-card-poster` class
- Overlay div with `movie-card-overlay` class containing play + copy buttons
- Body gets `movie-card-body`
- Search input stays, pagination stays

The key change: cards use the same visual style as admin but with simpler overlay (play + copy only, no delete/move).

- [ ] **Verify `php -l views/user-cards.php`**

---

### Task 8: Refine `views/music/album-grid.php`

**Files:**
- Modify: `views/music/album-grid.php`

- [ ] **Update album grid to use new card styles**

Replace grid container class to `album-grid`. Each album card gets `album-card` class. Cover section gets `album-card-cover` with play overlay. Body gets `album-card-body`. 

- [ ] **Verify `php -l views/music/album-grid.php`**

---

### Task 9: Refine `views/docs/file-list.php`

**Files:**
- Modify: `views/docs/file-list.php`

- [ ] **Update docs table styling**

Add class `docs-table table` to `<table>`. Apply `card-custom` class to the container. Add `role="link" tabindex="0"` + keyboard handler to folder rows.

- [ ] **Verify `php -l views/docs/file-list.php`**

---

### Task 10: Rewrite `dashboard.php`

**Files:**
- Modify: `dashboard.php`

- [ ] **Rewrite `dashboard.php` with library stats + system tab**

Read current file first. Replace the full page with:

```php
<?php
require_once __DIR__ . '/config/bootstrap.php';
use App\Auth\Auth;
use App\System\Dashboard;

$currentUser = Auth::requireAuth();
if ($currentUser['role'] !== 'admin') { header('Location: index.php'); exit; }

$stats = Dashboard::getStats();
$pageTitle = 'Dashboard - Explorador de Medios';

// Count files per library
function countFiles($dir) {
    $count = 0;
    if (!is_dir($dir)) return 0;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item[0] === '.') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) $count += countFiles($path);
        elseif (is_file($path)) $count++;
    }
    return $count;
}
$moviesCount = countFiles(MEDIA_ROOT);
$musicCount = countFiles(MEDIA_ROOT . '/musica');
$docsCount = countFiles(MEDIA_ROOT . '/documentos');
$diskFree = disk_free_space(MEDIA_ROOT);
$diskTotal = disk_total_space(MEDIA_ROOT);
$diskUsed = $diskTotal - $diskFree;
$libraryPercent = [
    'movies' => $moviesCount + $musicCount + $docsCount > 0 ? round($moviesCount / ($moviesCount + $musicCount + $docsCount) * 100) : 0,
    'music' => $moviesCount + $musicCount + $docsCount > 0 ? round($musicCount / ($moviesCount + $musicCount + $docsCount) * 100) : 0,
    'docs' => $moviesCount + $musicCount + $docsCount > 0 ? round($docsCount / ($moviesCount + $musicCount + $docsCount) * 100) : 0,
];
$total = $moviesCount + $musicCount + $docsCount;

include __DIR__ . '/views/layouts/main-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0" style="font-weight:600;"><i aria-hidden="true" class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard</h3>
</div>

<ul class="nav nav-tabs dashboard-tabs mb-4">
    <li class="nav-item"><a class="nav-link active" href="#biblioteca" data-bs-toggle="tab">Biblioteca</a></li>
    <li class="nav-item"><a class="nav-link" href="#sistema" data-bs-toggle="tab">Sistema</a></li>
</ul>

<div class="tab-content">
    <!-- Biblioteca tab -->
    <div class="tab-pane fade show active" id="biblioteca">
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <i aria-hidden="true" class="bi bi-film stat-icon text-primary d-block mb-1"></i>
                    <div class="stat-value"><?php echo $moviesCount; ?></div>
                    <div class="stat-label">Películas</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <i aria-hidden="true" class="bi bi-music-note-beamed stat-icon text-success d-block mb-1"></i>
                    <div class="stat-value"><?php echo $musicCount; ?></div>
                    <div class="stat-label">Música</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <i aria-hidden="true" class="bi bi-file-earmark-text stat-icon text-info d-block mb-1"></i>
                    <div class="stat-value"><?php echo $docsCount; ?></div>
                    <div class="stat-label">Documentos</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card text-center">
                    <i aria-hidden="true" class="bi bi-hdd stat-icon text-warning d-block mb-1"></i>
                    <div class="stat-value"><?php echo round($diskUsed / 1073741824, 1); ?> GB</div>
                    <div class="stat-label">Usado de <?php echo round($diskTotal / 1073741824, 0); ?> GB</div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card-custom p-3">
                    <h6 class="mb-3" style="font-weight:600;">Distribución por librería</h6>
                    <svg viewBox="0 0 200 200" style="width:100%;max-width:250px;display:block;margin:0 auto;">
                        <?php
                        $colors = ['#3B82F6', '#10B981', '#F59E0B'];
                        $vals = [$libraryPercent['movies'], $libraryPercent['music'], $libraryPercent['docs']];
                        $totalPct = array_sum($vals) ?: 1;
                        $current = 0;
                        $labels = ['Películas', 'Música', 'Documentos'];
                        for ($i = 0; $i < 3; $i++):
                            $pct = $vals[$i] / $totalPct;
                            $angle = $pct * 360;
                            $start = $current;
                            $end = $current + $angle;
                            $startRad = deg2rad($start - 90);
                            $endRad = deg2rad($end - 90);
                            $x1 = 100 + 80 * cos($startRad);
                            $y1 = 100 + 80 * sin($startRad);
                            $x2 = 100 + 80 * cos($endRad);
                            $y2 = 100 + 80 * sin($endRad);
                            $large = $angle > 180 ? 1 : 0;
                            if ($pct > 0.99):
                        ?>
                        <circle cx="100" cy="100" r="80" fill="none" stroke="<?php echo $colors[$i]; ?>" stroke-width="30"/>
                        <?php else: ?>
                        <path d="M100,100 L<?php echo $x1; ?>,<?php echo $y1; ?> A80,80 0 <?php echo $large; ?>,1 <?php echo $x2; ?>,<?php echo $y2; ?> Z" fill="<?php echo $colors[$i]; ?>"/>
                        <?php endif; $current = $end; endfor; ?>
                    </svg>
                    <div class="d-flex justify-content-center gap-3 mt-2">
                        <?php foreach ($labels as $i => $label): ?>
                        <div class="d-flex align-items-center gap-1 small">
                            <span style="width:10px;height:10px;border-radius:50%;background:<?php echo $colors[$i]; ?>;display:inline-block;"></span>
                            <?php echo $label; ?> (<?php echo $vals[$i]; ?>%)
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card-custom p-3">
                    <h6 class="mb-3" style="font-weight:600;">Archivos recientes</h6>
                    <div id="recentFiles">
                        <div class="text-center text-muted small py-4">
                            <i aria-hidden="true" class="bi bi-clock-history d-block mb-1" style="font-size:1.5rem;"></i>
                            Cargando…
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sistema tab -->
    <div class="tab-pane fade" id="sistema">
        <div class="row g-3" id="systemStats">
            <?php
            $cpuClass = $stats['cpu'] > 80 ? 'bg-danger' : ($stats['cpu'] > 50 ? 'bg-warning' : 'bg-primary');
            $tempClass = $stats['cpuTemp'] !== null ? ($stats['cpuTemp'] > 70 ? 'temp-hot' : ($stats['cpuTemp'] > 55 ? 'temp-warn' : 'temp-ok')) : '';
            $ramClass = $stats['memory']['percent'] > 80 ? 'bg-danger' : ($stats['memory']['percent'] > 60 ? 'bg-warning' : 'bg-info');
            $diskClass = $stats['disk']['percent'] > 85 ? 'bg-danger' : ($stats['disk']['percent'] > 70 ? 'bg-warning' : 'bg-success');
            ?>
            <div class="col-md-6">
                <div class="card-custom p-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small"><i aria-hidden="true" class="bi bi-cpu me-1"></i>CPU</span>
                        <span class="fw-bold"><?php echo $stats['cpu']; ?>%</span>
                    </div>
                    <div class="progress" style="height:8px;background:var(--bg);border-radius:4px;">
                        <div class="progress-bar <?php echo $cpuClass; ?>" style="width:<?php echo $stats['cpu']; ?>%;border-radius:4px;"></div>
                    </div>
                    <?php if ($stats['cpuTemp'] !== null): ?>
                    <div class="mt-1 small <?php echo $tempClass; ?>"><?php echo $stats['cpuTemp']; ?>°C</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card-custom p-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small"><i aria-hidden="true" class="bi bi-memory me-1"></i>RAM</span>
                        <span class="fw-bold"><?php echo $stats['memory']['percent']; ?>%</span>
                    </div>
                    <div class="progress" style="height:8px;background:var(--bg);border-radius:4px;">
                        <div class="progress-bar <?php echo $ramClass; ?>" style="width:<?php echo $stats['memory']['percent']; ?>%;border-radius:4px;"></div>
                    </div>
                    <div class="mt-1 small text-muted"><?php echo $stats['memory']['used']; ?> / <?php echo $stats['memory']['total']; ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card-custom p-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small"><i aria-hidden="true" class="bi bi-hdd me-1"></i>Disco</span>
                        <span class="fw-bold"><?php echo $stats['disk']['percent']; ?>%</span>
                    </div>
                    <div class="progress" style="height:8px;background:var(--bg);border-radius:4px;">
                        <div class="progress-bar <?php echo $diskClass; ?>" style="width:<?php echo $stats['disk']['percent']; ?>%;border-radius:4px;"></div>
                    </div>
                    <div class="mt-1 small text-muted"><?php echo $stats['disk']['used']; ?> / <?php echo $stats['disk']['total']; ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card-custom p-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small"><i aria-hidden="true" class="bi bi-server me-1"></i>Sistema</span>
                        <span class="fw-bold"><?php echo htmlspecialchars($stats['hostname']); ?></span>
                    </div>
                    <div class="small text-muted">
                        <div><i aria-hidden="true" class="bi bi-clock me-1"></i><?php echo htmlspecialchars($stats['uptime']); ?></div>
                        <div><i aria-hidden="true" class="bi bi-activity me-1"></i>Load: <?php echo implode(', ', $stats['load']); ?></div>
                        <div><i aria-hidden="true" class="bi bi-code-slash me-1"></i>PHP <?php echo htmlspecialchars($stats['php']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$pageScript = '
function refreshDashboard() {
    fetch("dashboard.php?ajax=1")
        .then(function(r) { return r.json(); })
        .then(function(stats) {
            var cpuClass = stats.cpu > 80 ? "bg-danger" : (stats.cpu > 50 ? "bg-warning" : "bg-primary");
            var ramClass = stats.memory.percent > 80 ? "bg-danger" : (stats.memory.percent > 60 ? "bg-warning" : "bg-info");
            var diskClass = stats.disk.percent > 85 ? "bg-danger" : (stats.disk.percent > 70 ? "bg-warning" : "bg-success");
            var html = "";
            var cards = [
                { label: "CPU", value: stats.cpu + "%", bar: stats.cpu, cls: cpuClass },
                { label: "RAM", value: stats.memory.percent + "%", bar: stats.memory.percent, cls: ramClass, sub: stats.memory.used + " / " + stats.memory.total },
                { label: "Disco", value: stats.disk.percent + "%", bar: stats.disk.percent, cls: diskClass, sub: stats.disk.used + " / " + stats.disk.total },
                { label: "Sistema", value: stats.hostname, noBar: true, sub: stats.uptime + " | PHP " + stats.php }
            ];
            cards.forEach(function(c) {
                html += "<div class=\"col-md-6\"><div class=\"card-custom p-3\">";
                html += "<div class=\"d-flex justify-content-between mb-1\"><span class=\"text-muted small\">" + c.label + "</span><span class=\"fw-bold\">" + c.value + "</span></div>";
                if (!c.noBar) html += "<div class=\"progress\" style=\"height:8px;background:var(--bg);border-radius:4px;\"><div class=\"progress-bar " + c.cls + "\" style=\"width:" + c.bar + "%;border-radius:4px;\"></div></div>";
                if (c.sub) html += "<div class=\"mt-1 small text-muted\">" + c.sub + "</div>";
                html += "</div></div>";
            });
            document.getElementById("systemStats").innerHTML = html;
        });
}
setInterval(refreshDashboard, 5000);

// Load recent files
fetch("dashboard.php?recent=1")
    .then(function(r) { return r.json(); })
    .then(function(files) {
        if (files.length === 0) {
            document.getElementById("recentFiles").innerHTML = "<div class=\"text-center text-muted small py-3\">Sin actividad reciente</div>";
            return;
        }
        var html = "<div class=\"list-group list-group-flush\">";
        files.forEach(function(f) {
            var icon = f.type === "dir" ? "bi-folder text-warning" : "bi-file-earmark text-muted";
            html += "<div class=\"list-group-item px-0 py-2\" style=\"background:transparent;border-color:var(--border);color:var(--text);\">";
            html += "<div class=\"d-flex align-items-center gap-2\"><i aria-hidden=\"true\" class=\"bi " + icon + "\"></i>";
            html += "<div><div class=\"small\">" + f.name + "</div><small class=\"text-muted\">" + f.lib + " &middot; " + f.time + "</small></div></div></div>";
        });
        html += "</div>";
        document.getElementById("recentFiles").innerHTML = html;
    });
';

include __DIR__ . "/views/layouts/main-footer.php";
```

Also add the AJAX handler at bottom (same as current dashboard.php has). Keep the PHP `if (isset($_GET['ajax']))` and `if (isset($_GET['recent']))` handlers.

- [ ] **Verify `php -l dashboard.php`**

---

### Task 11: Refine `conversiones.php`

**Files:**
- Modify: `conversiones.php`

- [ ] **Update `conversiones.php` to use layout partials**

Replace the current HTML structure to use `main-header.php` / `main-footer.php`. Strip the old `<html>` head, replace with layout includes. Keep all PHP logic and JS.

- [ ] **Verify `php -l conversiones.php`**

---

### Task 12: Refine Auth Login Page

**Files:**
- Modify: `src/Auth/Auth.php`

- [ ] **Update login page to use new theme CSS**

The login page is standalone (no sidebar). Add the `app.css` stylesheet link. Update inline styles to use CSS variables where it helps. Keep the existing layout but add appropriate styling.

- [ ] **Verify `php -l src/Auth/Auth.php`**

---

### Final Verification

- [ ] Run `php -l` on ALL modified files
- [ ] Check no PHP files have syntax errors
- [ ] Announce completion
