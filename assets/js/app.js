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
  if (!confirm('Vols eliminar aquesta conversió?')) return;
  var form = document.createElement('form');
  form.method = 'POST'; form.action = 'convert.php';
  form.innerHTML = '<input name="action" value="delete"><input name="id" value="' + id + '"><input name="_token" value="' + (window.csrfToken || '') + '">';
  document.body.appendChild(form); form.submit();
}

function playConverted(path) {
  window.open('serve.php?file=' + encodeURIComponent(path), '_blank');
}
