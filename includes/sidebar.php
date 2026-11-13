<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div id="sidebar-wrapper" class="no-print">
    <div class="sidebar-brand d-flex align-items-center">
        <div class="me-2 rounded-circle bg-primary bg-opacity-25 p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
            <i class="bi bi-wallet2 text-white fs-5"></i>
        </div>
        <div>
            <div class="lh-1 text-white fw-bold" style="font-size: 1.05rem;">SIKEU-UMKM</div>
            <small class="text-secondary" style="font-size: 0.72rem; letter-spacing: 0.05em;">RISKI BUSINESS APP</small>
        </div>
    </div>

    <div class="list-group list-group-flush my-3">
        <div class="sidebar-heading">Menu Utama</div>
        <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="transaksi.php" class="nav-link <?= in_array($current_page, ['transaksi.php', 'transaksi_edit.php']) ? 'active' : '' ?>">
            <i class="bi bi-arrow-left-right"></i> Data Transaksi
        </a>
        <a href="transaksi_tambah.php" class="nav-link <?= $current_page == 'transaksi_tambah.php' ? 'active' : '' ?>">
            <i class="bi bi-plus-circle"></i> Catat Transaksi
        </a>
        <a href="kategori.php" class="nav-link <?= $current_page == 'kategori.php' ? 'active' : '' ?>">
            <i class="bi bi-tags"></i> Kategori Keuangan
        </a>

        <div class="sidebar-heading mt-3">Laporan Bisnis</div>
        <a href="laporan_arus_kas.php" class="nav-link <?= $current_page == 'laporan_arus_kas.php' ? 'active' : '' ?>">
            <i class="bi bi-journal-text"></i> Buku Arus Kas
        </a>
        <a href="laporan_laba_rugi.php" class="nav-link <?= $current_page == 'laporan_laba_rugi.php' ? 'active' : '' ?>">
            <i class="bi bi-graph-up-arrow"></i> Laporan Laba Rugi
        </a>

        <div class="sidebar-heading mt-3">Konfigurasi</div>
        <a href="pengaturan.php" class="nav-link <?= $current_page == 'pengaturan.php' ? 'active' : '' ?>">
            <i class="bi bi-sliders"></i> Profil & Pengaturan
        </a>
    </div>

    <div class="px-3 mt-auto mb-3 text-center">
        <div class="p-3 rounded-3" style="background: rgba(255, 255, 255, 0.05);">
            <div class="text-white-50 small mb-1" style="font-size: 0.75rem;">Aplikasi Komputer Bisnis</div>
            <div class="text-white fw-bold small">v1.0 • PHP & MySQL</div>
        </div>
    </div>
</div>

