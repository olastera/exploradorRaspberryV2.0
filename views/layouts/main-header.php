<?php if (!isset($pageTitle)) $pageTitle = 'Explorador de Medios'; ?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#0F172A">
    <title><?php echo $pageTitle; ?></title>
    <link href="assets/vendor/bootstrap/bootstrap.min.css?v=5.3.0" rel="stylesheet">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css?v=1.10.0">
    <link rel="stylesheet" href="assets/css/app.css?v=6">
</head>
<body>
    <button class="mobile-menu-btn" type="button" onclick="toggleSidebar()" aria-label="Abrir menú" aria-controls="mainSidebar">
        <i aria-hidden="true" class="bi bi-list"></i>
    </button>
    <!-- Sidebar -->
    <aside class="sidebar" id="mainSidebar" role="navigation" aria-label="Navegación principal">
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
            <a href="index.php?lib=movies" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'index.php' && (!isset($_GET['lib']) || $_GET['lib'] === 'movies')) ? 'active' : ''; ?>">
                <i aria-hidden="true" class="bi bi-film"></i>
                <span>Pel·lícules</span>
            </a>
            <a href="index.php?lib=music" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'index.php' && isset($_GET['lib']) && $_GET['lib'] === 'music') ? 'active' : ''; ?>">
                <i aria-hidden="true" class="bi bi-music-note-beamed"></i>
                <span>Música</span>
            </a>
            <a href="index.php?lib=docs" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'index.php' && isset($_GET['lib']) && $_GET['lib'] === 'docs') ? 'active' : ''; ?>">
                <i aria-hidden="true" class="bi bi-file-earmark-text"></i>
                <span>Documents</span>
            </a>
            <?php if (isset($isAdmin) && $isAdmin): ?>
            <hr style="border-color:var(--border);margin:0.5rem 0;">
            <a href="dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                <i aria-hidden="true" class="bi bi-speedometer2"></i>
                <span>Tauler</span>
            </a>
            <a href="dashboard.php#administracio" class="sidebar-link">
                <i aria-hidden="true" class="bi bi-gear"></i>
                <span>Administració</span>
            </a>
            <a href="conversiones.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'conversiones.php' ? 'active' : ''; ?>">
                <i aria-hidden="true" class="bi bi-arrow-repeat"></i>
                <span>Conversions</span>
            </a>
            <?php endif; ?>
        </div>
        <div class="sidebar-bottom">
            <button class="sidebar-link" onclick="toggleTheme()" style="background:none;border:none;cursor:pointer;width:100%;" aria-label="Cambiar tema">
                <i aria-hidden="true" class="bi bi-sun-fill"></i>
                <span>Mode clar</span>
            </button>
            <a href="?logout=1" class="sidebar-link" style="color:var(--text-muted);">
                <i aria-hidden="true" class="bi bi-box-arrow-right"></i>
                <span>Tanca la sessió</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
