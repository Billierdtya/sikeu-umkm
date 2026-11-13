<?php
$pageTitle = 'Catat Transaksi Baru';
require_once __DIR__ . '/includes/header.php';

// Ambil semua kategori untuk form
$kategoriList = $pdo->query("SELECT * FROM kategori ORDER BY jenis ASC, nama_kategori ASC")->fetchAll();

$kodeOtomatis = generate_kode_transaksi($pdo);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_transaksi = sanitize($_POST['kode_transaksi'] ?? '');
    $tanggal = sanitize($_POST['tanggal'] ?? date('Y-m-d'));
    $jenis = sanitize($_POST['jenis'] ?? '');
    $kategori_id = (int)($_POST['kategori_id'] ?? 0);
    // Hapus titik / format rupiah dari input nominal
    $rawNominal = str_replace(['.', ',', 'Rp', ' '], '', $_POST['nominal'] ?? '0');
    $nominal = (float)$rawNominal;
    $metode_pembayaran = sanitize($_POST['metode_pembayaran'] ?? 'Tunai');
    $keterangan = sanitize($_POST['keterangan'] ?? '');

    // Validasi form
    if (empty($kode_transaksi) || empty($tanggal) || empty($jenis) || empty($kategori_id) || $nominal <= 0) {
        $error = 'Harap lengkapi semua bidang wajib bertanda bintang (*) dan pastikan nominal lebih dari 0.';
    } else {
        // Upload bukti transaksi jika ada
        $namaFileBukti = null;
        if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['bukti']['tmp_name'];
            $fileName = $_FILES['bukti']['name'];
            $fileSize = $_FILES['bukti']['size'];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];

            if (!in_array($ext, $allowedExts)) {
                $error = 'Format file bukti hanya diperbolehkan JPG, PNG, atau PDF.';
            } elseif ($fileSize > 3 * 1024 * 1024) { // max 3MB
                $error = 'Ukuran file bukti maksimal 3MB.';
            } else {
                $uploadDir = __DIR__ . '/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $namaFileBukti = 'BUKTI_' . time() . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($fileTmp, $uploadDir . $namaFileBukti);
            }
        }

        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO transaksi (kode_transaksi, tanggal, jenis, kategori_id, nominal, metode_pembayaran, keterangan, bukti_transaksi)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $kode_transaksi,
                    $tanggal,
                    $jenis,
                    $kategori_id,
                    $nominal,
                    $metode_pembayaran,
                    $keterangan,
                    $namaFileBukti
                ]);

                set_flash('success', "Transaksi <strong>$kode_transaksi</strong> berhasil disimpan!");
                echo "<script>window.location.href = 'transaksi.php';</script>";
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal menyimpan transaksi: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold mb-1">Tambah Transaksi Kas</h4>
                <p class="text-muted small mb-0">Catat setiap transaksi arus kas masuk (pemasukan) atau kas keluar (beban/pengeluaran).</p>
            </div>
            <a href="transaksi.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="POST" action="transaksi_tambah.php" enctype="multipart/form-data" id="formTransaksi">
                    <div class="row g-3">
                        <!-- Kode Transaksi & Tanggal -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Kode Transaksi <span class="text-danger">*</span></label>
                            <input type="text" name="kode_transaksi" class="form-control" value="<?= htmlspecialchars($_POST['kode_transaksi'] ?? $kodeOtomatis) ?>" required readonly style="background-color: #f1f5f9; font-family: monospace; font-weight: 600;">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Tanggal Transaksi <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($_POST['tanggal'] ?? date('Y-m-d')) ?>" required>
                        </div>

                        <!-- Jenis Transaksi (Radio Button Card) -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Jenis Transaksi <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="jenis" id="jenis_pemasukan" value="pemasukan" <?= (!isset($_POST['jenis']) || $_POST['jenis'] == 'pemasukan') ? 'checked' : '' ?> onchange="filterKategoriByJenis()">
                                    <label class="btn btn-outline-success w-100 py-2 d-flex align-items-center justify-content-center fw-bold" for="jenis_pemasukan">
                                        <i class="bi bi-arrow-down-left-circle-fill me-2 fs-5"></i> Kas Masuk (Pemasukan)
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="jenis" id="jenis_pengeluaran" value="pengeluaran" <?= (isset($_POST['jenis']) && $_POST['jenis'] == 'pengeluaran') ? 'checked' : '' ?> onchange="filterKategoriByJenis()">
                                    <label class="btn btn-outline-danger w-100 py-2 d-flex align-items-center justify-content-center fw-bold" for="jenis_pengeluaran">
                                        <i class="bi bi-arrow-up-right-circle-fill me-2 fs-5"></i> Kas Keluar (Pengeluaran)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Kategori Keuangan -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Kategori <span class="text-danger">*</span></label>
                            <select name="kategori_id" id="kategori_id" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($kategoriList as $kat): ?>
                                    <option value="<?= $kat['id'] ?>" data-jenis="<?= $kat['jenis'] ?>" <?= (isset($_POST['kategori_id']) && $_POST['kategori_id'] == $kat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kat['nama_kategori']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small">Kategori otomatis disesuaikan dengan jenis transaksi di atas.</div>
                        </div>

                        <!-- Nominal Transaksi -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Nominal (Rupiah) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold bg-light">Rp</span>
                                <input type="text" name="nominal" id="nominal" class="form-control format-rupiah" placeholder="0" value="<?= htmlspecialchars($_POST['nominal'] ?? '') ?>" required style="font-size: 1.1rem; font-weight: 700;">
                            </div>
                        </div>

                        <!-- Metode Pembayaran -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Metode Pembayaran</label>
                            <select name="metode_pembayaran" class="form-select">
                                <option value="Tunai" <?= (isset($_POST['metode_pembayaran']) && $_POST['metode_pembayaran'] == 'Tunai') ? 'selected' : '' ?>>Tunai (Cash)</option>
                                <option value="Transfer Bank" <?= (isset($_POST['metode_pembayaran']) && $_POST['metode_pembayaran'] == 'Transfer Bank') ? 'selected' : '' ?>>Transfer Bank (BCA / Mandiri / BRI / BNI)</option>
                                <option value="QRIS" <?= (isset($_POST['metode_pembayaran']) && $_POST['metode_pembayaran'] == 'QRIS') ? 'selected' : '' ?>>QRIS (Gopay / OVO / Dana / ShopeePay)</option>
                                <option value="Kartu Debit / Kredit" <?= (isset($_POST['metode_pembayaran']) && $_POST['metode_pembayaran'] == 'Kartu Debit / Kredit') ? 'selected' : '' ?>>Kartu Debit / Kredit</option>
                            </select>
                        </div>

                        <!-- Upload Bukti / Struk / Kwitansi -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Upload Bukti / Nota (Opsional)</label>
                            <input type="file" name="bukti" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                            <div class="form-text small">Format: JPG, PNG, atau PDF (Maks. 3 MB)</div>
                        </div>

                        <!-- Keterangan Transaksi -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Keterangan / Rincian</label>
                            <textarea name="keterangan" rows="3" class="form-control" placeholder="Contoh: Pembelian 10 box bahan baku A dari supplier PT Jaya..."><?= htmlspecialchars($_POST['keterangan'] ?? '') ?></textarea>
                        </div>

                        <!-- Tombol Submit -->
                        <div class="col-12 mt-4 pt-2 border-top d-flex gap-2 justify-content-end">
                            <a href="transaksi.php" class="btn btn-light px-4">Batal</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">
                                <i class="bi bi-save me-1"></i> Simpan Transaksi
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function filterKategoriByJenis() {
    const jenis = document.querySelector('input[name="jenis"]:checked').value;
    const select = document.getElementById('kategori_id');
    const options = select.querySelectorAll('option');

    let firstMatchSelected = false;

    options.forEach(opt => {
        if (opt.value === '') {
            opt.hidden = false;
            return;
        }

        const optJenis = opt.getAttribute('data-jenis');
        if (optJenis === jenis) {
            opt.hidden = false;
            opt.disabled = false;
            if (!firstMatchSelected && select.value === '') {
                opt.selected = true;
                firstMatchSelected = true;
            }
        } else {
            opt.hidden = true;
            opt.disabled = true;
            if (opt.selected) {
                opt.selected = false;
            }
        }
    });
}

// Jalankan filter saat halaman dimuat
document.addEventListener('DOMContentLoaded', function () {
    filterKategoriByJenis();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

