<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <input type="text" id="userSearchInput" class="form-control form-control-sm" placeholder="Cerca…" style="max-width:280px;">
    <select id="userPerPageSelect" class="form-select form-select-sm" style="width:auto;">
        <option value="10" selected>10</option>
        <option value="15">15</option>
        <option value="25">25</option>
        <option value="30">30</option>
    </select>
    <span class="text-muted small ms-auto" id="userItemCount"><?php echo count($directories) + count($files); ?> elementos</span>
</div>

<div class="row g-3" id="userCardGrid">
    <?php $userCardIndex = 0; ?>
    <?php foreach ($directories as $dir):
        $cleanName = preg_replace('/\[.*?\]/', '', $dir);
        $cleanName = preg_replace('/\(.*?\)|\)[^)]*\)/', '', $cleanName);
        $cleanName = trim(preg_replace('/\s+/', ' ', $cleanName));
        $link = '?lib=movies&path=' . urlencode(($relativePath ? $relativePath . '/' : '') . $dir);
        $userCardIndex++;
    ?>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2 usercard-wrapper" data-index="<?php echo $userCardIndex; ?>">
            <a href="<?php echo $link; ?>" class="movie-card text-decoration-none">
                <div class="movie-card-poster" style="padding-top:133%;">
                    <img class="poster-thumb shimmer" data-query="<?php echo htmlspecialchars($cleanName); ?>" alt="<?php echo htmlspecialchars($dir); ?>" width="300" height="450" loading="lazy" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;">
                </div>
                <div class="movie-card-body text-center py-3 d-flex flex-column align-items-center">
                    <i aria-hidden="true" class="bi bi-folder-fill" style="font-size:1.5rem;color:#fbbf24;"></i>
                    <p class="title mb-0"><?php echo htmlspecialchars($dir); ?></p>
                </div>
            </a>
        </div>
    <?php endforeach; ?>

    <?php foreach ($files as $file):
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $videoExtensions)) continue;
        $relPath = ($relativePath ? $relativePath . '/' : '') . $file;
        $fileUrl = 'serve.php?file=' . urlencode($relPath);
        $userCardIndex++;
    ?>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2 usercard-wrapper" data-index="<?php echo $userCardIndex; ?>">
            <div class="movie-card">
                <div class="movie-card-poster" style="padding-top:133%;">
                    <img class="poster-thumb shimmer" data-query="<?php echo htmlspecialchars(pathinfo($file, PATHINFO_FILENAME)); ?>" alt="<?php echo htmlspecialchars($file); ?>" width="300" height="450" loading="lazy" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;">
                </div>
                <div class="movie-card-body py-3">
                    <p class="title mb-2"><?php echo htmlspecialchars($file); ?></p>
                    <div class="d-flex gap-1 justify-content-center flex-wrap">
                        <button onclick='event.stopPropagation();playVideo(<?php echo htmlspecialchars(json_encode($fileUrl), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>)' class="btn btn-sm" style="background:#3B82F6;color:#fff;border-radius:6px;" title="Reproducir"><i aria-hidden="true" class="bi bi-play-fill"></i></button>
                        <button onclick='event.stopPropagation();copyToClipboard(<?php echo htmlspecialchars(json_encode($fileUrl), ENT_QUOTES); ?>)' class="btn btn-sm btn-outline-light" style="border-radius:6px;" title="Copiar enlace"><i aria-hidden="true" class="bi bi-clipboard"></i></button>
                        <button onclick='event.stopPropagation();searchTrailer(<?php echo htmlspecialchars(json_encode($file), ENT_QUOTES); ?>)' class="btn btn-sm btn-outline-danger" style="border-radius:6px;" title="Ver Trailer"><i aria-hidden="true" class="bi bi-youtube"></i></button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($directories) && empty(array_filter($files, fn($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), $videoExtensions)))): ?>
        <div class="col-12">
            <div class="text-center py-5 text-muted">
                <i aria-hidden="true" class="bi bi-folder-x display-4 d-block mb-2"></i>
                No hi ha fitxers
            </div>
        </div>
    <?php endif; ?>
</div>

<nav class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2" id="userPaginationNav">
    <div class="pagination-info text-muted small">
        <span id="userPaginationInfo">Página 1 de 1</span>
    </div>
    <ul class="pagination pagination-sm mb-0" id="userPaginationControls">
        <li class="page-item" id="userPrevPage"><span class="page-link"><i aria-hidden="true" class="bi bi-chevron-left"></i></span></li>
        <li class="page-item" id="userNextPage"><span class="page-link"><i aria-hidden="true" class="bi bi-chevron-right"></i></span></li>
    </ul>
</nav>

<script>
(function() {
    var wrappers = Array.from(document.querySelectorAll('.usercard-wrapper'));
    var perPageSelect = document.getElementById('userPerPageSelect');
    var paginationInfo = document.getElementById('userPaginationInfo');
    var prevBtn = document.getElementById('userPrevPage');
    var nextBtn = document.getElementById('userNextPage');
    var searchInput = document.getElementById('userSearchInput');
    var currentPage = 1;

    function getFiltered() {
        var term = searchInput.value.toLowerCase().trim();
        return wrappers.filter(function(w) {
            if (!term) return true;
            var cardText = w.querySelector('.title');
            return cardText && cardText.textContent.toLowerCase().includes(term);
        });
    }

    function getPerPage() {
        var v = parseInt(perPageSelect.value, 10);
        return v > 0 ? v : Infinity;
    }

    function render() {
        var filtered = getFiltered();
        var perPage = getPerPage();
        var totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
        if (currentPage > totalPages) currentPage = totalPages;

        var start = (currentPage - 1) * perPage;
        var end = start + perPage;

        wrappers.forEach(function(w) { w.style.display = 'none'; });
        filtered.slice(start, end).forEach(function(w) { w.style.display = ''; });

        paginationInfo.textContent = 'Página ' + currentPage + ' de ' + totalPages + ' (' + filtered.length + ' elementos)';

        prevBtn.closest('.page-item').classList.toggle('disabled', currentPage <= 1);
        nextBtn.closest('.page-item').classList.toggle('disabled', currentPage >= totalPages);

        var existing = document.querySelectorAll('#userPaginationControls .page-number');
        existing.forEach(function(e) { e.remove(); });

        var maxVisible = 7;
        var half = Math.floor(maxVisible / 2);
        var from = Math.max(1, currentPage - half);
        var to = Math.min(totalPages, currentPage + half);
        if (to - from + 1 < maxVisible) {
            if (from === 1) to = Math.min(totalPages, from + maxVisible - 1);
            else from = Math.max(1, to - maxVisible + 1);
        }

        var refNode = nextBtn;
        for (var p = from; p <= to; p++) {
            var li = document.createElement('li');
            li.className = 'page-item page-number' + (p === currentPage ? ' active' : '');
            var a = document.createElement('span');
            a.className = 'page-link';
            a.textContent = p;
            a.addEventListener('click', function(page) { return function() { currentPage = page; render(); }; }(p));
            li.appendChild(a);
            refNode.parentNode.insertBefore(li, refNode);
        }

        var countEl = document.getElementById('userItemCount');
        if (countEl) countEl.textContent = filtered.length + ' elementos';
    }

    searchInput.addEventListener('keyup', function() { currentPage = 1; render(); });
    perPageSelect.addEventListener('change', function() { currentPage = 1; render(); });
    prevBtn.addEventListener('click', function() { if (currentPage > 1) { currentPage--; render(); } });
    nextBtn.addEventListener('click', function() {
        var perPage = getPerPage();
        var totalPages = Math.max(1, Math.ceil(getFiltered().length / perPage));
        if (currentPage < totalPages) { currentPage++; render(); }
    });

    render();
})();
</script>
