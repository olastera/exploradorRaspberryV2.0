<?php
require_once __DIR__ . '/config/bootstrap.php';
use App\Auth\Auth;
use App\System\Dashboard;

$currentUser = Auth::requireAuth();
if ($currentUser['role'] !== 'admin') { header('Location: index.php'); exit; }
$isAdmin = true;

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
$totalCount = $moviesCount + $musicCount + $docsCount;
$libraryPercent = [
    'movies' => $totalCount > 0 ? round($moviesCount / $totalCount * 100) : 33,
    'music' => $totalCount > 0 ? round($musicCount / $totalCount * 100) : 33,
    'docs' => $totalCount > 0 ? round($docsCount / $totalCount * 100) : 33,
];

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
// AJAX endpoint for auto-refresh
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode(Dashboard::getStats());
    exit;
}

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
        getRecentFiles(MEDIA_ROOT, 'Películas', 5),
        getRecentFiles(MEDIA_ROOT . '/musica', 'Música', 3),
        getRecentFiles(MEDIA_ROOT . '/documentos', 'Documentos', 3)
    );
    usort($all, fn($a, $b) => $b['mtime'] - $a['mtime']);
    echo json_encode(array_slice($all, 0, 10));
    exit;
}
