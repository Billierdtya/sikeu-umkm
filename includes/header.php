<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';

$pdo = getDBConnection();
$profil = get_profil_bisnis($pdo);

if (!isset($pageTitle)) {
    $pageTitle = 'Aplikasi Komputer Bisnis - Keuangan UMKM';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars($profil['nama_bisnis']) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Page Content Wrapper -->
    <div id="page-content-wrapper">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg top-navbar no-print">
            <div class="container-fluid px-0">
                <button class="btn btn-light d-lg-none me-2" id="menu-toggle">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="d-flex align-items-center">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-2 px-2 py-1">
                        <i class="bi bi-briefcase-fill me-1"></i> Sistem Komputer Bisnis
                    </span>
                    <span class="text-muted d-none d-md-inline" style="font-size: 0.85rem;">
                        <?= tanggal_indo(date('Y-m-d'), true) ?>
                    </span>
                </div>

                <div class="ms-auto d-flex align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center" type="button" data-bs-dismiss="dropdown" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1 text-primary"></i>
                            <span class="fw-semibold"><?= htmlspecialchars($profil['pemilik']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li class="dropdown-header"><?= htmlspecialchars($profil['nama_bisnis']) ?></li>
                            <li><a class="dropdown-item" href="pengaturan.php"><i class="bi bi-gear me-2"></i>Pengaturan Bisnis</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-muted" href="#"><i class="bi bi-info-circle me-2"></i>Versi 1.0 (XAMPP)</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content Area -->
        <div class="content-container">
            <?php render_flash(); ?>

