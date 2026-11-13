<?php
$pageTitle = 'Profil & Pengaturan Usaha';
require_once __DIR__ . '/includes/header.php';

// Aksi Update Profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profil') {
    $nama_bisnis = sanitize($_POST['nama_bisnis'] ?? '');
    $pemilik = sanitize($_POST['pemilik'] ?? '');
    $telepon = sanitize($_POST['telepon'] ?? '');
    $alamat = sanitize($_POST['alamat'] ?? '');

    if (!empty($nama_bisnis) && !empty($pemilik)) {
        try {
            $stmt = $pdo->prepare("UPDATE pengaturan SET nama_bisnis = ?, pemilik = ?, telepon = ?, alamat = ? WHERE id = ?");
            $stmt->execute([$nama_bisnis, $pemilik, $telepon, $alamat, $profil['id']]);
            set_flash('success', 'Profil bisnis UMKM berhasil diperbarui!');
        } catch (PDOException $e) {
            set_flash('danger', 'Gagal memperbarui profil: ' . $e->getMessage());
        }
    } else {
        set_flash('danger', 'Nama Bisnis dan Nama Pemilik tidak boleh kosong.');
    }
    echo "<script>window.location.href = 'pengaturan.php';</script>";
    exit;
}

// Aksi Reset Data Dummy (Opsional untuk presentasi tugas)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_dummy') {
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("TRUNCATE TABLE transaksi;");
        $pdo->exec("TRUNCATE TABLE kategori;");
        $pdo->exec("TRUNCATE TABLE pengaturan;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        // Panggil kembali fungsi inisialisasi default dari database.php
        initDatabaseTables($pdo);

        set_flash('success', 'Data berhasil di-reset kembali ke data simulasi awal (demo).');
    } catch (PDOException $e) {
        set_flash('danger', 'Gagal reset data: ' . $e->getMessage());
    }
    echo "<script>window.location.href = 'pengaturan.php';</script>";
    exit;
}

// Ambil info versi MySQL
$mysqlVersion = $pdo->query('select version()')->fetchColumn();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Pengaturan & Profil Bisnis</h4>
        <p class="text-muted mb-0 small">Identitas UMKM yang dicetak pada faktur, kuitansi, dan laporan keuangan.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Form Profil Bisnis -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0"><i class="bi bi-shop me-2 text-primary"></i>Informasi Usaha</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="pengaturan.php">
                    <input type="hidden" name="action" value="update_profil">

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Nama Bisnis / Perusahaan UMKM <span class="text-danger">*</span></label>
                            <input type="text" name="nama_bisnis" class="form-control" value="<?= htmlspecialchars($profil['nama_bisnis']) ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Nama Pemilik / Manajer <span class="text-danger">*</span></label>
                            <input type="text" name="pemilik" class="form-control" value="<?= htmlspecialchars($profil['pemilik']) ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Nomor Telepon / WhatsApp</label>
                            <input type="text" name="telepon" class="form-control" value="<?= htmlspecialchars($profil['telepon']) ?>" placeholder="0812-xxxx-xxxx">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Mata Uang Default</label>
                            <input type="text" class="form-control" value="Rupiah (Rp)" readonly style="background-color: #f1f5f9;">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Alamat Tempat Usaha</label>
                            <textarea name="alamat" rows="3" class="form-control" placeholder="Alamat lengkap lokasi usaha..."><?= htmlspecialchars($profil['alamat']) ?></textarea>
                        </div>

                        <div class="col-12 pt-2 text-end">
                            <button type="submit" class="btn btn-primary px-4 fw-bold">
                                <i class="bi bi-check-lg me-1"></i> Simpan Perubahan Profil
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Informasi Lingkungan Sistem & Reset -->
    <div class="col-12 col-lg-4">
        <!-- Informasi Sistem -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0"><i class="bi bi-cpu me-2 text-primary"></i>Informasi Sistem Komputer Bisnis</h6>
            </div>
            <div class="card-body p-3">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Aplikasi:</span>
                        <span class="fw-semibold">SIKEU-UMKM v1.0</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">PHP Engine:</span>
                        <span class="fw-semibold text-primary"><?= phpversion() ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Database Server:</span>
                        <span class="fw-semibold text-success">MySQL <?= htmlspecialchars($mysqlVersion) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Web Server:</span>
                        <span class="fw-semibold">Apache (XAMPP)</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Lokasi Folder:</span>
                        <span class="fw-semibold text-truncate" style="max-width: 170px;" title="c:\xampp\htdocs\web_riski">c:\xampp\htdocs\web_riski</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Tombol Reset Data Demo -->
        <div class="card shadow-sm border-0 border-warning border-opacity-50">
            <div class="card-body p-3">
                <h6 class="fw-bold text-dark"><i class="bi bi-arrow-repeat text-warning me-2"></i>Reset Data Simulasi (Demo)</h6>
                <p class="small text-muted mb-3">
                    Mengembalikan data transaksi dan pos kategori ke contoh default awal. Sangat berguna untuk simulasi presentasi tugas.
                </p>
                <form method="POST" action="pengaturan.php" onsubmit="return confirm('Apakah Anda yakin ingin me-reset seluruh data transaksi ke contoh awal?');">
                    <input type="hidden" name="action" value="reset_dummy">
                    <button type="submit" class="btn btn-outline-warning text-dark w-100 btn-sm fw-semibold">
                        <i class="bi bi-arrow-clockwise me-1"></i> Reset ke Data Contoh
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

