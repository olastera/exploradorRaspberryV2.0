// Sidebar toggle
function toggleSidebar() {
  if (window.matchMedia('(max-width: 767.98px)').matches) {
    document.body.classList.toggle('sidebar-open');
    return;
  }
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

window.addEventListener('resize', function() {
  if (!window.matchMedia('(max-width: 767.98px)').matches) {
    document.body.classList.remove('sidebar-open');
  }
});

// Build full URL for copied links
function fullUrl(path) {
  var base = window.location.origin + window.location.pathname.replace(/\/+$/, '');
  var sep = base.indexOf('?') >= 0 ? '&' : '?';
  return base + sep + 'file=' + encodeURIComponent(path);
}

function playVideo(url, title) {
  var w = window.open('', '_blank');
  w.document.write('<html><head><title>' + (title || 'Reprodueix') + '</title>');
  w.document.write('<meta name="viewport" content="width=device-width,initial-scale=1">');
  w.document.write('<style>body{margin:0;background:#000;display:flex;align-items:center;justify-content:center;height:100vh;}');
  w.document.write('video{max-width:100%;max-height:100vh;}</style></head><body>');
  w.document.write('<video src="' + url + '" controls autoplay style="width:100%;height:auto;max-height:100vh;"></video>');
  w.document.write('</body></html>');
  w.document.close();
}

function copyToClipboard(url) {
  var absoluteUrl = new URL(url, window.location.href).href;
  function showCopied() {
    var toast = document.createElement('div');
    toast.setAttribute('aria-live', 'polite');
    toast.className = 'position-fixed bottom-0 end-0 p-3';
    toast.style.zIndex = '9999';
    toast.innerHTML = '<div class="toast show align-items-center text-bg-success border-0"><div class="d-flex"><div class="toast-body">URL copiada al porta-retalls</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>';
    document.body.appendChild(toast);
    setTimeout(function() { toast.remove(); }, 3000);
  }
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(absoluteUrl).then(showCopied);
  } else {
    var input = document.createElement('textarea');
    input.value = absoluteUrl;
    input.style.position = 'fixed';
    input.style.opacity = '0';
    document.body.appendChild(input);
    input.select();
    document.execCommand('copy');
    input.remove();
    showCopied();
  }
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

function openImageLightbox(url, title) {
  document.getElementById('imageLightboxTitle').textContent = title || '';
  document.getElementById('imageLightboxImg').src = url;
  document.getElementById('imageLightboxDownload').href = url;
  new bootstrap.Modal(document.getElementById('imageLightboxModal')).show();
}

function showMovieInfo(query) {
  fetch('imdb_search.php?q=' + encodeURIComponent(query))
    .then(function(response) {
      if (!response.ok) throw new Error('No s\'ha pogut carregar la informació');
      return response.json();
    })
    .then(function(data) {
      if (!data.found) {
        window.alert('No s\'ha trobat informació per a «' + query + '».');
        return;
      }
      showPosterModal(data, query);
    })
    .catch(function() {
      window.alert('No s\'ha pogut carregar la informació de la pel·lícula.');
    });
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
            var fallback = img.parentElement.querySelector('.poster-fallback');
            if (fallback) fallback.style.display = 'none';
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
  if (!confirm('Vols convertir «' + file + '» a MP4? Podràs seguir el progrés a Conversions.')) return;
  var form = new FormData();
  form.append('action', 'start');
  form.append('file', file);
  form.append('csrf_token', window.csrfToken || '');
  fetch('convert.php', { method: 'POST', body: form })
    .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
    .then(function(result) {
      if (!result.ok) {
        alert(result.data.error || 'No s\'ha pogut iniciar la conversió.');
        return;
      }
      window.location.href = 'conversiones.php';
    })
    .catch(function() {
      alert('No s\'ha pogut iniciar la conversió.');
    });
}

function submitHiddenForm(action, values) {
  var form = document.createElement('form');
  form.method = 'POST';
  form.action = action;
  form.style.display = 'none';
  Object.keys(values).forEach(function(name) {
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = values[name];
    form.appendChild(input);
  });
  document.body.appendChild(form);
  form.submit();
}

function deleteLibraryItem(name) {
  if (!confirm('Vols eliminar «' + name + '»? Aquesta acció no es pot desfer.')) return;
  submitHiddenForm(window.location.href, { csrf_token: window.csrfToken || '', delete: name });
}

function moveLibraryItem(name, target) {
  var labels = { movies: 'Pel·lícules', music: 'Música', docs: 'Documents' };
  if (!confirm('Vols moure «' + name + '» a ' + (labels[target] || target) + '?')) return;
  submitHiddenForm(window.location.href, {
    csrf_token: window.csrfToken || '',
    move_item: name,
    move_target: target
  });
}

function selectedLibraryItems() {
  return Array.from(document.querySelectorAll('.item-checkbox:checked')).map(function(input) {
    return input.value;
  });
}

function selectedFolderItems() {
  return Array.from(document.querySelectorAll('.item-checkbox:checked')).filter(function(input) {
    var item = input.closest('.explorer-item');
    return item && item.dataset.type === 'folder';
  }).map(function(input) {
    return input.value;
  });
}

// Clipboard (copiar/cortar y pegar dentro de la misma biblioteca)
function currentExplorerPath() {
  return new URLSearchParams(window.location.search).get('path') || '';
}

function getClipboard() {
  try {
    var raw = localStorage.getItem('fileClipboard');
    return raw ? JSON.parse(raw) : null;
  } catch (e) {
    return null;
  }
}

function setClipboard(action) {
  var items = selectedLibraryItems();
  if (!items.length) return;
  var base = currentExplorerPath();
  var fullPaths = items.map(function(name) { return base ? base + '/' + name : name; });
  localStorage.setItem('fileClipboard', JSON.stringify({ library: window.library || '', action: action, items: fullPaths }));
  updatePasteButton();
}

function clipboardCopySelected() { setClipboard('copy'); }
function clipboardCutSelected() { setClipboard('cut'); }

function clearClipboard() {
  localStorage.removeItem('fileClipboard');
  updatePasteButton();
}

function updatePasteButton() {
  var pasteBtn = document.getElementById('pasteClipboardBtn');
  var clearBtn = document.getElementById('clearClipboardBtn');
  if (!pasteBtn || !clearBtn) return;
  var data = getClipboard();
  var visible = !!(data && data.library === (window.library || '') && data.items && data.items.length);
  pasteBtn.classList.toggle('d-none', !visible);
  clearBtn.classList.toggle('d-none', !visible);
  if (visible) {
    var label = document.getElementById('pasteClipboardLabel');
    var verb = data.action === 'cut' ? 'Mou aquí' : 'Enganxa';
    if (label) label.textContent = verb + ' (' + data.items.length + ')';
  }
}

function pasteClipboard() {
  var data = getClipboard();
  if (!data || !data.items || !data.items.length) return;
  submitHiddenForm(window.location.href, {
    csrf_token: window.csrfToken || '',
    clipboard_action: data.action,
    clipboard_items: JSON.stringify(data.items),
    clipboard_source: '1'
  });
  if (data.action === 'cut') clearClipboard();
}

function openMergeVideoFolders() {
  var folders = selectedFolderItems();
  if (folders.length < 2) {
    alert('Selecciona com a mínim dues carpetes.');
    return;
  }
  var hidden = document.getElementById('mergeVideoFoldersInput');
  var list = document.getElementById('mergeVideoFoldersList');
  var destination = document.getElementById('mergeDestinationInput');
  hidden.value = JSON.stringify(folders);
  list.textContent = folders.join(' · ');
  destination.value = '';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('mergeVideoFoldersModal')).show();
  setTimeout(function() { destination.focus(); }, 200);
}

function updateSelectionBar() {
  var selected = selectedLibraryItems();
  var bar = document.getElementById('selectionBar');
  if (bar) bar.classList.toggle('show', selected.length > 0);
  var count = document.getElementById('selectedCount');
  if (count) count.textContent = selected.length + ' seleccionats';
}

function deleteSelected() {
  var items = selectedLibraryItems();
  if (!items.length || !confirm('Vols eliminar ' + items.length + ' elements?')) return;
  submitHiddenForm(window.location.href, {
    csrf_token: window.csrfToken || '',
    delete_selected: '1',
    selected_items: JSON.stringify(items)
  });
}

function moveSelected(target) {
  var items = selectedLibraryItems();
  if (!items.length) return;
  if (!confirm('Vols moure ' + items.length + ' elements?')) return;
  submitHiddenForm(window.location.href, {
    csrf_token: window.csrfToken || '',
    move_selected: '1',
    move_items: JSON.stringify(items),
    move_target: target
  });
}

document.addEventListener('change', function(event) {
  if (event.target.classList.contains('item-checkbox')) updateSelectionBar();
});

function setExplorerView(view) {
  var content = document.getElementById('explorerContent');
  if (!content) return;
  content.classList.toggle('explorer-list-view', view === 'list');
  var gridButton = document.getElementById('gridViewBtn');
  var listButton = document.getElementById('listViewBtn');
  if (gridButton) gridButton.classList.toggle('active', view === 'grid');
  if (listButton) listButton.classList.toggle('active', view === 'list');
  localStorage.setItem('explorerView', view);
}

function initExplorerBrowser() {
  var items = Array.from(document.querySelectorAll('.explorer-item'));
  var search = document.getElementById('explorerSearch');
  var previous = document.getElementById('explorerPrev');
  var next = document.getElementById('explorerNext');
  var pageInfo = document.getElementById('explorerPageInfo');
  var count = document.getElementById('explorerItemCount');
  var page = 1;
  var perPage = 20;

  function filteredItems() {
    var term = search ? search.value.toLocaleLowerCase('ca').trim() : '';
    return items.filter(function(item) {
      return !term || (item.dataset.name || '').toLocaleLowerCase('ca').includes(term);
    });
  }

  function render() {
    var filtered = filteredItems();
    var totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
    page = Math.min(page, totalPages);
    items.forEach(function(item) { item.classList.add('d-none'); });
    filtered.slice((page - 1) * perPage, page * perPage).forEach(function(item) {
      item.classList.remove('d-none');
    });
    if (pageInfo) pageInfo.textContent = 'Pàgina ' + page + ' de ' + totalPages;
    if (count) count.textContent = filtered.length + ' elements';
    if (previous) previous.disabled = page <= 1;
    if (next) next.disabled = page >= totalPages;
  }

  if (search) search.addEventListener('input', function() { page = 1; render(); });
  if (previous) previous.addEventListener('click', function() { if (page > 1) { page--; render(); } });
  if (next) next.addEventListener('click', function() {
    if (page < Math.ceil(filteredItems().length / perPage)) { page++; render(); }
  });

  if (document.getElementById('gridViewBtn')) {
    setExplorerView(localStorage.getItem('explorerView') === 'list' ? 'list' : 'grid');
  }
  render();
}

function cancelConversion(id) {
  var form = document.createElement('form');
  form.method = 'POST'; form.action = 'convert.php';
  form.innerHTML = '<input name="action" value="cancel"><input name="id" value="' + id + '"><input name="_token" value="' + (window.csrfToken || '') + '">';
  document.body.appendChild(form); form.submit();
}

function deleteConversion(id) {
  if (!confirm('Vols eliminar aquesta conversió?')) return;
  var form = document.createElement('form');
  form.method = 'POST'; form.action = 'convert.php';
  form.innerHTML = '<input name="action" value="delete"><input name="id" value="' + id + '"><input name="_token" value="' + (window.csrfToken || '') + '">';
  document.body.appendChild(form); form.submit();
}

function playConverted(path) {
  window.open('serve.php?file=' + encodeURIComponent(path), '_blank');
}
