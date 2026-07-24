<?php
require_once __DIR__ . '/config/bootstrap.php';
use App\Auth\Auth;
use App\Auth\Csrf;
use App\Auth\UserManager;
use App\Imdb\FileCache;
use App\Media\FileExplorer;
use App\Media\PosterCache;
use App\Media\Thumbnailer;
use App\System\Dashboard;
use App\System\Settings;

$currentUser = Auth::requireAuth();
if ($currentUser['role'] !== 'admin') { header('Location: index.php'); exit; }
$isAdmin = true;
$csrfToken = Csrf::token();
$adminMessage = '';
$adminMessageType = 'success';

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode(Dashboard::getStats());
    exit;
}
if (isset($_GET['processes'])) {
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode(Dashboard::getProcesses(15));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['csrf_token'] ?? '')) {
        $adminMessage = 'El testimoni de seguretat no és vàlid.';
        $adminMessageType = 'danger';
    } elseif (isset($_POST['save_paths'])) {
        $saved = Settings::savePaths(
            $_POST['movies_path'] ?? '',
            $_POST['music_path'] ?? '',
            $_POST['docs_path'] ?? '',
            $_POST['images_path'] ?? ''
        );
        $adminMessage = $saved
            ? 'Les rutes s’han desat. S’aplicaran a partir de la pròxima petició.'
            : 'No s’han pogut desar: totes les rutes han de ser directoris absoluts, existents i llegibles.';
        $adminMessageType = $saved ? 'success' : 'danger';
    } elseif (isset($_POST['user_create'])) {
        $saved = UserManager::create(
            trim($_POST['user_username'] ?? ''),
            $_POST['user_password'] ?? '',
            $_POST['user_role'] ?? 'user'
        );
        $adminMessage = $saved ? 'Usuari creat correctament.' : 'No s’ha pogut crear l’usuari.';
        $adminMessageType = $saved ? 'success' : 'danger';
    } elseif (isset($_POST['user_update'])) {
        $saved = UserManager::update(
            $_POST['user_oldname'] ?? '',
            trim($_POST['user_username'] ?? ''),
            $_POST['user_password'] ?? '',
            $_POST['user_role'] ?? 'user'
        );
        $adminMessage = $saved ? 'Usuari actualitzat.' : 'No s’ha pogut actualitzar l’usuari.';
        $adminMessageType = $saved ? 'success' : 'danger';
    } elseif (isset($_POST['user_delete_name'])) {
        $saved = UserManager::delete($_POST['user_delete_name']);
        $adminMessage = $saved ? 'Usuari eliminat.' : 'No es pot eliminar l’últim administrador.';
        $adminMessageType = $saved ? 'success' : 'danger';
    } elseif (isset($_POST['clear_poster_cache'])) {
        $cleared = PosterCache::clear();
        $adminMessage = "S'han eliminat {$cleared} carátulas de la memòria cau.";
        $adminMessageType = 'success';
    } elseif (isset($_POST['clear_all_cache'])) {
        $cleared = PosterCache::clear() + FileCache::clear() + Thumbnailer::clear();
        $adminMessage = "S'han eliminat {$cleared} arxius de tota la memòria cau (carátulas, metadades IMDb i miniatures).";
        $adminMessageType = 'success';
    }
}

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
$moviesPath = FileExplorer::getLibraryRoot('movies');
$musicPath = FileExplorer::getLibraryRoot('music');
$docsPath = FileExplorer::getLibraryRoot('docs');
$imagesPath = FileExplorer::getLibraryRoot('images');
$moviesCount = countFiles($moviesPath);
$musicCount = countFiles($musicPath);
$docsCount = countFiles($docsPath);
$imagesCount = countFiles($imagesPath);
$diskFree = disk_free_space(MEDIA_ROOT);
$diskTotal = disk_total_space(MEDIA_ROOT);
$diskUsed = $diskTotal - $diskFree;
$totalCount = $moviesCount + $musicCount + $docsCount + $imagesCount;
$users = UserManager::load();
$libraryPercent = [
    'movies' => $totalCount > 0 ? round($moviesCount / $totalCount * 100) : 25,
    'music' => $totalCount > 0 ? round($musicCount / $totalCount * 100) : 25,
    'docs' => $totalCount > 0 ? round($docsCount / $totalCount * 100) : 25,
    'images' => $totalCount > 0 ? round($imagesCount / $totalCount * 100) : 25,
];

include __DIR__ . '/views/layouts/main-header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0" style="font-weight:600;"><i aria-hidden="true" class="bi bi-speedometer2 me-2 text-primary"></i>Tauler</h3>
</div>

<?php if ($adminMessage): ?>
<div class="alert alert-<?php echo $adminMessageType; ?> py-2"><?php echo htmlspecialchars($adminMessage); ?></div>
<?php endif; ?>

<ul class="nav nav-tabs dashboard-tabs mb-4">
    <li class="nav-item"><a class="nav-link active" href="#biblioteca" data-bs-toggle="tab">Biblioteca</a></li>
    <li class="nav-item"><a class="nav-link" href="#sistema" data-bs-toggle="tab">Sistema</a></li>
    <li class="nav-item"><a class="nav-link" href="#administracio" data-bs-toggle="tab">Administració</a></li>
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
                    <i aria-hidden="true" class="bi bi-image stat-icon text-danger d-block mb-1"></i>
                    <div class="stat-value"><?php echo $imagesCount; ?></div>
                    <div class="stat-label">Imatges</div>
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
                        $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444'];
                        $vals = [$libraryPercent['movies'], $libraryPercent['music'], $libraryPercent['docs'], $libraryPercent['images']];
                        $totalPct = array_sum($vals) ?: 1;
                        $current = 0;
                        $labels = ['Películas', 'Música', 'Documentos', 'Imatges'];
                        for ($i = 0; $i < 4; $i++):
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
            <?php foreach ($stats['disks'] as $index => $disk):
                $currentDiskClass = $disk['percent'] > 85 ? 'bg-danger' : ($disk['percent'] > 70 ? 'bg-warning' : 'bg-success');
            ?>
                <div class="col-md-6">
                    <div class="card-custom p-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small"><i aria-hidden="true" class="bi bi-hdd me-1"></i>Disc <?php echo $index + 1; ?> · <?php echo htmlspecialchars(implode(' / ', $disk['libraries'])); ?></span>
                            <span class="fw-bold"><?php echo $disk['percent']; ?>%</span>
                        </div>
                        <div class="progress" style="height:8px;background:var(--bg);border-radius:4px;">
                            <div class="progress-bar <?php echo $currentDiskClass; ?>" style="width:<?php echo $disk['percent']; ?>%;border-radius:4px;"></div>
                        </div>
                        <div class="mt-1 small text-muted"><?php echo $disk['used']; ?> / <?php echo $disk['total']; ?> · <?php echo htmlspecialchars($disk['mount']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
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
        <div class="card-custom mt-3 overflow-hidden">
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom" style="border-color:var(--border)!important">
                <h5 class="mb-0"><i class="bi bi-terminal me-2 text-success"></i>Processos actius</h5>
                <span class="small text-muted"><span class="process-live-dot"></span> Actualització cada 3 segons</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 process-table">
                    <thead>
                        <tr>
                            <th>PID</th>
                            <th>Procés</th>
                            <th class="text-end">CPU</th>
                            <th class="text-end">Memòria</th>
                            <th class="text-end">Temps actiu</th>
                        </tr>
                    </thead>
                    <tbody id="processTableBody">
                        <tr><td colspan="5" class="text-center text-muted py-4">Obre la pestanya Sistema per carregar els processos.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="administracio">
        <div class="row g-4">
            <div class="col-12">
                <div class="card-custom p-4">
                    <h5 class="mb-3"><i class="bi bi-folder2-open me-2 text-primary"></i>Rutes de les biblioteques</h5>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                        <input type="hidden" name="save_paths" value="1">
                        <div class="col-12">
                            <label class="form-label" for="moviesPath">Pel·lícules</label>
                            <input class="form-control" id="moviesPath" name="movies_path" value="<?php echo htmlspecialchars($moviesPath, ENT_QUOTES); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="musicPath">Música</label>
                            <input class="form-control" id="musicPath" name="music_path" value="<?php echo htmlspecialchars($musicPath, ENT_QUOTES); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="docsPath">Documents</label>
                            <input class="form-control" id="docsPath" name="docs_path" value="<?php echo htmlspecialchars($docsPath, ENT_QUOTES); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="imagesPath">Imatges</label>
                            <input class="form-control" id="imagesPath" name="images_path" value="<?php echo htmlspecialchars($imagesPath, ENT_QUOTES); ?>" required>
                        </div>
                        <div class="col-12">
                            <div class="form-text text-muted mb-3">Introdueix rutes absolutes que existeixin i siguin llegibles pel servidor web.</div>
                            <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Desa les rutes</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-12">
                <div class="card-custom p-4">
                    <h5 class="mb-3"><i class="bi bi-hdd-stack me-2 text-primary"></i>Memòria cau</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <form method="post" onsubmit="return confirm('Vols buidar la memòria cau de carátulas?');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                            <button class="btn btn-outline-secondary" type="submit" name="clear_poster_cache" value="1"><i class="bi bi-image me-1"></i>Buida carátulas</button>
                        </form>
                        <form method="post" onsubmit="return confirm('Vols buidar TOTA la memòria cau (carátulas, metadades IMDb i miniatures)?');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                            <button class="btn btn-outline-danger" type="submit" name="clear_all_cache" value="1"><i class="bi bi-trash3 me-1"></i>Buida tota la caché</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card-custom p-4">
                    <h5 class="mb-3"><i class="bi bi-people me-2 text-primary"></i>Usuaris</h5>
                    <form method="post" class="row g-2 align-items-end mb-4 pb-4 border-bottom" style="border-color:var(--border)!important">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                        <div class="col-md-4">
                            <label class="form-label">Nom d’usuari</label>
                            <input class="form-control" name="user_username" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contrasenya</label>
                            <input class="form-control" type="password" name="user_password" required autocomplete="new-password">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Rol</label>
                            <select class="form-select" name="user_role">
                                <option value="user">Usuari</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" name="user_create" value="1"><i class="bi bi-person-plus me-1"></i>Crea</button>
                        </div>
                    </form>

                    <div class="d-flex flex-column gap-3">
                    <?php foreach ($users as $user): ?>
                        <form method="post" class="row g-2 align-items-end p-3 rounded" style="background:var(--bg);border:1px solid var(--border)">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                            <input type="hidden" name="user_oldname" value="<?php echo htmlspecialchars($user['user'], ENT_QUOTES); ?>">
                            <div class="col-md-3">
                                <label class="form-label small">Nom d’usuari</label>
                                <input class="form-control form-control-sm" name="user_username" value="<?php echo htmlspecialchars($user['user'], ENT_QUOTES); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Contrasenya nova</label>
                                <input class="form-control form-control-sm" type="password" name="user_password" placeholder="Sense canvis" autocomplete="new-password">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Rol</label>
                                <select class="form-select form-select-sm" name="user_role">
                                    <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Usuari</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary flex-grow-1" name="user_update" value="1">Actualitza</button>
                                <button class="btn btn-sm btn-outline-danger" name="user_delete_name" value="<?php echo htmlspecialchars($user['user'], ENT_QUOTES); ?>" onclick="return confirm('Vols eliminar aquest usuari?')"><i class="bi bi-trash"></i></button>
                            </div>
                        </form>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$pageScript = '
document.addEventListener("DOMContentLoaded", function() {
    if (window.location.hash === "#administracio") {
        var trigger = document.querySelector("[href=\"#administracio\"]");
        if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
    }
});

function refreshDashboard() {
    fetch("dashboard.php?ajax=1")
        .then(function(r) { return r.json(); })
        .then(function(stats) {
            var cpuClass = stats.cpu > 80 ? "bg-danger" : (stats.cpu > 50 ? "bg-warning" : "bg-primary");
            var ramClass = stats.memory.percent > 80 ? "bg-danger" : (stats.memory.percent > 60 ? "bg-warning" : "bg-info");
            var html = "";
            var cards = [
                { label: "CPU", value: stats.cpu + "%", bar: stats.cpu, cls: cpuClass },
                { label: "RAM", value: stats.memory.percent + "%", bar: stats.memory.percent, cls: ramClass, sub: stats.memory.used + " / " + stats.memory.total }
            ];
            (stats.disks || []).forEach(function(disk, index) {
                var diskClass = disk.percent > 85 ? "bg-danger" : (disk.percent > 70 ? "bg-warning" : "bg-success");
                cards.push({
                    label: "Disc " + (index + 1) + " · " + disk.libraries.join(" / "),
                    value: disk.percent + "%",
                    bar: disk.percent,
                    cls: diskClass,
                    sub: disk.used + " / " + disk.total + " · " + disk.mount
                });
            });
            cards.push({ label: "Sistema", value: stats.hostname, noBar: true, sub: stats.uptime + " | PHP " + stats.php });
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

function refreshProcesses() {
    var systemTab = document.getElementById("sistema");
    if (!systemTab || !systemTab.classList.contains("active")) return;
    fetch("dashboard.php?processes=1", { cache: "no-store" })
        .then(function(response) { return response.json(); })
        .then(function(processes) {
            var body = document.getElementById("processTableBody");
            body.textContent = "";
            if (!processes.length) {
                var emptyRow = body.insertRow();
                var emptyCell = emptyRow.insertCell();
                emptyCell.colSpan = 5;
                emptyCell.className = "text-center text-muted py-4";
                emptyCell.textContent = "No s’han pogut obtenir els processos.";
                return;
            }
            processes.forEach(function(process) {
                var row = body.insertRow();
                var values = [
                    process.pid,
                    process.command,
                    process.cpu.toFixed(1) + "%",
                    process.memory.toFixed(1) + "%",
                    process.elapsed
                ];
                values.forEach(function(value, index) {
                    var cell = row.insertCell();
                    cell.textContent = value;
                    if (index >= 2) cell.className = "text-end";
                    if (index === 1) cell.className = "process-command";
                });
            });
        });
}
document.querySelectorAll("[data-bs-toggle=\"tab\"]").forEach(function(tab) {
    tab.addEventListener("shown.bs.tab", refreshProcesses);
});
setInterval(refreshProcesses, 3000);

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
?>
<?php
// AJAX endpoint for recent files
if (isset($_GET['recent'])) {
    header('Content-Type: application/json');
    function getRecentFiles($dir, $lib, $limit = 10) {
        $recent = [];
        if (!is_dir($dir)) return $recent;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item[0] === '.') continue;
            $path = $dir . '/' . $item;
            if (is_file($path)) {
                $recent[] = [
                    'name' => $item,
                    'lib' => $lib,
                    'time' => date('d/m/Y', filemtime($path)),
                    'type' => 'file',
                    'mtime' => filemtime($path),
                ];
            } elseif (is_dir($path) && $lib === 'movies') {
                $sub = scandir($path);
                foreach ($sub as $s) {
                    if ($s[0] === '.' || !is_file($path . '/' . $s)) continue;
                    $recent[] = [
                        'name' => $item . '/' . $s,
                        'lib' => $lib,
                        'time' => date('d/m/Y', filemtime($path . '/' . $s)),
                        'type' => 'file',
                        'mtime' => filemtime($path . '/' . $s),
                    ];
                }
            }
        }
        usort($recent, fn($a, $b) => $b['mtime'] - $a['mtime']);
        return array_slice($recent, 0, $limit);
    }
    $all = array_merge(
        getRecentFiles($moviesPath, 'Pel·lícules', 5),
        getRecentFiles($musicPath, 'Música', 3),
        getRecentFiles($docsPath, 'Documents', 3),
        getRecentFiles($imagesPath, 'Imatges', 3)
    );
    usort($all, fn($a, $b) => $b['mtime'] - $a['mtime']);
    echo json_encode(array_slice($all, 0, 10));
    exit;
}
