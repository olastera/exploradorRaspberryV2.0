<?php
require_once __DIR__ . '/config/bootstrap.php';
use App\Auth\Auth;
use App\Auth\Csrf;
$currentUser = Auth::requireAuth();
if ($currentUser['role'] !== 'admin') { header('Location: index.php'); exit; }
$isAdmin = true;
$csrfToken = Csrf::token();
$pageTitle = 'Conversiones - Explorador de Medios';
include __DIR__ . '/views/layouts/main-header.php';
?>
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center py-3" style="color:#F1F5F9;">
            <h3 class="card-title mb-0"><i aria-hidden="true" class="bi bi-arrow-repeat me-2 text-primary"></i> Conversiones de Vídeo</h3>
            <div class="d-flex align-items-center gap-2">
                <a href="index.php" class="btn btn-outline-light btn-sm"><i aria-hidden="true" class="bi bi-arrow-left"></i> Volver</a>
                <a href="?logout=1" class="btn btn-outline-light btn-sm" aria-label="Cerrar sesión"><i aria-hidden="true" class="bi bi-box-arrow-right"></i></a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="p-3 border-bottom d-flex align-items-center gap-2" style="border-color:rgba(255,255,255,0.08) !important;">
                <span class="text-muted small" id="convCount">Cargando…</span>
                <button class="btn btn-outline-secondary btn-sm ms-auto" id="clearCompletedBtn" onclick="clearCompleted()">
                    <i aria-hidden="true" class="bi bi-trash"></i> Limpiar completados
                </button>
                <button class="btn btn-outline-primary btn-sm" onclick="refreshList()">
                    <i aria-hidden="true" class="bi bi-arrow-clockwise"></i> Refrescar
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle" id="convTable">
                    <thead class="table-light" style="background:#0F172A;">
                        <tr>
                            <th>Archivo</th>
                            <th style="width:15%">Estado</th>
                            <th style="width:30%">Progreso</th>
                            <th style="width:15%">Iniciado</th>
                            <th style="width:20%" class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="convBody" aria-live="polite">
                        <tr><td colspan="5" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Cargando…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted small" id="autoRefreshStatus" style="border-top-color:rgba(255,255,255,0.08);">
            <i aria-hidden="true" class="bi bi-arrow-repeat me-1"></i> Actualización automática cada 3 segundos
        </div>
    </div>
</div>

<?php
$pageScript = '
var refreshInterval = null;

function refreshList() {
    fetch(\'convert.php?action=list\')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var tbody = document.getElementById(\'convBody\');
            if (data.length === 0) {
                tbody.innerHTML = \'<tr><td colspan="5" class="text-center py-4 text-muted"><i aria-hidden="true" class="bi bi-check-circle display-4 d-block mb-2"></i>No hay conversiones</td></tr>\';
                document.getElementById(\'convCount\').textContent = \'0 conversiones\';
                stopAutoRefresh();
                return;
            }
            var html = \'\';
            var hasRunning = false;
            var hasPending = false;
            var queueOrder = data.filter(function(c) { return c.status === \'pending\'; })
                .sort(function(a, b) { return (a.startedAt || 0) - (b.startedAt || 0); });
            data.forEach(function(c) {
                var statusBadge = \'\', progressBar = \'\', actionBtns = \'\', iconHtml = \'\';
                if (c.status === \'running\') {
                    statusBadge = \'<span class="badge bg-primary"><i aria-hidden="true" class="bi bi-arrow-repeat me-1"></i>Convirtiendo</span>\';
                    progressBar = \'<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:\' + c.progress + \'%">\' + c.progress + \'%</div></div>\';
                    actionBtns = \'<button class="btn btn-sm btn-outline-danger me-1" data-id="\' + escapeHtml(c.id) + \'" onclick="cancelConversion(this.dataset.id)"><i aria-hidden="true" class="bi bi-stop-fill"></i></button>\';
                    actionBtns += \'<button class="btn btn-sm btn-outline-secondary" onclick="window.location.href=&#39;index.php&#39;"><i aria-hidden="true" class="bi bi-folder"></i> Ir</button>\';
                    iconHtml = \'<i aria-hidden="true" class="bi bi-arrow-repeat text-primary conv-icon"></i>\';
                    hasRunning = true;
                } else if (c.status === \'completed\') {
                    statusBadge = \'<span class="badge bg-success"><i aria-hidden="true" class="bi bi-check-circle me-1"></i>Completado</span>\';
                    progressBar = \'<div class="progress"><div class="progress-bar bg-success" style="width:100%">100%</div></div>\';
                    actionBtns = \'<button class="btn btn-sm btn-outline-success me-1" data-path="\' + escapeHtml(encodeURIComponent(c.outputRelative)) + \'" onclick="playConverted(this.dataset.path)"><i aria-hidden="true" class="bi bi-play-circle-fill"></i></button>\';
                    actionBtns += \'<button class="btn btn-sm btn-outline-danger" data-id="\' + escapeHtml(c.id) + \'" onclick="deleteConversion(this.dataset.id)"><i aria-hidden="true" class="bi bi-x"></i></button>\';
                    iconHtml = \'<i aria-hidden="true" class="bi bi-check-circle-fill text-success conv-icon"></i>\';
                } else if (c.status === \'pending\') {
                    var pos = queueOrder.indexOf(c) + 1;
                    statusBadge = \'<span class="badge bg-secondary"><i aria-hidden="true" class="bi bi-clock me-1"></i>En cola (#\' + pos + \')</span>\';
                    progressBar = \'<div class="progress"><div class="progress-bar bg-secondary" style="width:0%">Esperando…</div></div>\';
                    actionBtns = \'<button class="btn btn-sm btn-outline-danger" data-id="\' + escapeHtml(c.id) + \'" onclick="cancelConversion(this.dataset.id)"><i aria-hidden="true" class="bi bi-stop-fill"></i></button>\';
                    iconHtml = \'<i aria-hidden="true" class="bi bi-clock text-secondary conv-icon"></i>\';
                    hasPending = true;
                } else {
                    statusBadge = \'<span class="badge bg-danger"><i aria-hidden="true" class="bi bi-exclamation-triangle me-1"></i>Error</span>\';
                    progressBar = \'<div class="progress"><div class="progress-bar bg-danger" style="width:\' + (c.progress || 0) + \'%">\' + (c.error || \'Error\') + \'</div></div>\';
                    actionBtns = \'<button class="btn btn-sm btn-outline-danger" data-id="\' + escapeHtml(c.id) + \'" onclick="deleteConversion(this.dataset.id)"><i aria-hidden="true" class="bi bi-x"></i></button>\';
                    iconHtml = \'<i aria-hidden="true" class="bi bi-exclamation-triangle-fill text-danger conv-icon"></i>\';
                }
                var startedAt = c.startedAt ? new Date(c.startedAt * 1000).toLocaleString() : \'-\';
                html += \'<tr><td><div class="d-flex align-items-center gap-2">\' + iconHtml + \'<div><div class="small">\' + escapeHtml(c.input) + \'</div><small class="text-muted">→ \' + escapeHtml(c.output) + \'</small></div></div></td><td>\' + statusBadge + \'</td><td>\' + progressBar + \'</td><td class="small text-muted">\' + startedAt + \'</td><td class="text-end text-nowrap">\' + actionBtns + \'</td></tr>\';
            });
            tbody.innerHTML = html;
            document.getElementById(\'convCount\').textContent = data.length + \' conversione(s)\';
            if (hasRunning || hasPending) startAutoRefresh();
            else stopAutoRefresh();
        });
}

function startAutoRefresh() {
    if (refreshInterval) return;
    refreshInterval = setInterval(refreshList, 3000);
    document.getElementById(\'autoRefreshStatus\').innerHTML = \'<i aria-hidden="true" class="bi bi-arrow-repeat me-1"></i> Actualización automática <span class="badge bg-primary ms-1">ACTIVA</span>\';
}
function stopAutoRefresh() {
    if (refreshInterval) { clearInterval(refreshInterval); refreshInterval = null; }
    document.getElementById(\'autoRefreshStatus\').innerHTML = \'<i aria-hidden="true" class="bi bi-check-circle me-1"></i> Todas las conversiones han finalizado\';
}
function cancelConversion(id) {
    if (!confirm(\'¿Cancelar esta conversión?\')) return;
    var form = new FormData(); form.append(\'id\', id); form.append(\'csrf_token\', csrfToken);
    fetch(\'convert.php?action=cancel\', { method: \'POST\', body: form }).then(function(r) { return r.json(); }).then(function(d) { if (d.ok) refreshList(); });
}
function deleteConversion(id) {
    if (!confirm(\'¿Borrar esta conversión del listado?\')) return;
    var form = new FormData(); form.append(\'id\', id); form.append(\'csrf_token\', csrfToken);
    fetch(\'convert.php?action=delete\', { method: \'POST\', body: form }).then(function(r) { return r.json(); }).then(function(d) { if (d.ok) refreshList(); });
}
function clearCompleted() {
    if (!confirm(\'¿Borrar todas las completadas?\')) return;
    fetch(\'convert.php?action=list\').then(function(r) { return r.json(); }).then(function(data) {
        Promise.all(data.filter(function(c) { return c.status !== \'running\'; }).map(function(c) {
            var f = new FormData(); f.append(\'id\', c.id); f.append(\'csrf_token\', csrfToken);
            return fetch(\'convert.php?action=delete\', { method: \'POST\', body: f });
        })).then(function() { refreshList(); });
    });
}
function playConverted(relativePath) {
    var path = decodeURIComponent(relativePath);
    fetch(\'convert.php?action=gentoken&file=\' + encodeURIComponent(path))
        .then(function(r) { return r.json(); })
        .then(function(d) { if (d.token) window.open(\'serve.php?file=\' + encodeURIComponent(path) + \'&token=\' + d.token, \'_blank\'); })
        .catch(function() { window.open(\'serve.php?file=\' + encodeURIComponent(path), \'_blank\'); });
}
function escapeHtml(str) {
    var div = document.createElement(\'div\');
    div.textContent = str;
    return div.innerHTML;
}
document.addEventListener(\'DOMContentLoaded\', refreshList);
';
include __DIR__ . '/views/layouts/main-footer.php';
?>
