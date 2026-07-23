# Task 2: Shared JS (`assets/js/app.js`)

**Status:** DONE

**Created:** `assets/js/app.js` (6417 bytes)

**Contains:**
- `toggleSidebar()` — class toggle + localStorage
- `toggleTheme()` — data-theme toggle + localStorage
- IIFE — loads theme + sidebar state from localStorage
- `fullUrl(path)` — absolute URL builder
- `playVideo(url, title)` — video popup
- `copyToClipboard(url)` — clipboard + toast with `aria-live`
- `searchTrailer(title)` — YouTube search
- `showRenameModal(currentName)`, `showUploadModal()`, `showNewFolderModal()` — modal show
- `showPosterModal(data, query)` — IMDb poster modal
- `loadPosterBatch(posterImages, startIndex, batchSize)` — batch poster loading
- `convertToMp4(file)`, `cancelConversion(id)`, `deleteConversion(id)`, `playConverted(path)` — conversion management

**Verification:**
- `ls -la assets/js/app.js` — file exists with correct size
- `node` not available for syntax check, but content matches plan exactly
