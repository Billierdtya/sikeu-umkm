<?php
$pageTitle = 'Edit Transaksi Kas';
require_once __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM transaksi WHERE id = ?");
$stmt->execute([$id]);
$trx = $stmt->fetch();

if (!$trx) {
    set_flash('danger', 'Data transaksi tidak ditemukan.');
    echo "<script>window.location.href = 'transaksi.php';</script>";
    exit;
}

$kategoriList = $pdo->query("SELECT * FROM kategori ORDER BY jenis ASC, nama_kategori ASC")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = sanitize($_POST['tanggal'] ?? date('Y-m-d'));
    $jenis = sanitize($_POST['jenis'] ?? '');
    $kategori_id = (int)($_POST['kategori_id'] ?? 0);
    $rawNominal = str_replace(['.', ',', 'Rp', ' '], '', $_POST['nominal'] ?? '0');
    $nominal = (float)$rawNominal;
    $metode_pembayaran = sanitize($_POST['metode_pembayaran'] ?? 'Tunai');
    $keterangan = sanitize($_POST['keterangan'] ?? '');

    if (empty($tanggal) || empty($jenis) || empty($kategori_id) || $nominal <= 0) {
        $error = 'Harap lengkapi semua bidang wajib bertanda bintang (*) dan pastikan nominal lebih dari 0.';
    } else {
        $namaFileBukti = $trx['bukti_transaksi'];

        // Cek jika ada file bukti baru yang diupload
        if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['bukti']['tmp_name'];
            $fileName = $_FILES['bukti']['name'];
            $fileSize = $_FILES['bukti']['size'];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];

            if (!in_array($ext, $allowedExts)) {
                $error = 'Format file bukti hanya diperbolehkan JPG, PNG, atau PDF.';
            } elseif ($fileSize > 3 * 1024 * 1024) {
                $error = 'Ukuran file bukti maksimal 3MB.';
            } else {
                $uploadDir = __DIR__ . '/uploads/';
                // Hapus bukti lama jika ada
                if (!empty($trx['bukti_transaksi']) && file_exists($uploadDir . $trx['bukti_transaksi'])) {
                    @unlink($uploadDir . $trx['bukti_transaksi']);
                }
                $namaFileBukti = 'BUKTI_' . time() . '_' . uniqid() . '.' . $ext;
                move_uploaded_file($fileTmp, $uploadDir . $namaFileBukti);
            }
        }

        if (empty($error)) {
            try {
                $stmtUpdate = $pdo->prepare("
                    UPDATE transaksi 
                    SET tanggal = ?, jenis = ?, kategori_id = ?, nominal = ?, metode_pembayaran = ?, keterangan = ?, bukti_transaksi = ?
                    WHERE id = ?
                ");
                $stmtUpdate->execute([
                    $tanggal,
                    $jenis,
                    $kategori_id,
                    $nominal,
                    $metode_pembayaran,
                    $keterangan,
                    $namaFileBukti,
                    $id
                ]);

                set_flash('success', "Transaksi <strong>{$trx['kode_transaksi']}</strong> berhasil diperbarui!");
                echo "<script>window.location.href = 'transaksi.php';</script>";
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal memperbarui transaksi: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold mb-1">Edit Transaksi Kas</h4>
                <p class="text-muted small mb-0">Ubah rincian transaksi kode <strong><?= htmlspecialchars($trx['kode_transaksi']) ?></strong>.</p>
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
                <form method="POST" action="transaksi_edit.php?id=<?= $id ?>" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Kode Transaksi</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($trx['kode_transaksi']) ?>" readonly style="background-color: #f1f5f9; font-family: monospace; font-weight: 600;">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Tanggal Transaksi <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($trx['tanggal']) ?>" required>
                        </div>

                        <!-- Jenis Transaksi -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Jenis Transaksi <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="jenis" id="jenis_pemasukan" value="pemasukan" <?= $trx['jenis'] == 'pemasukan' ? 'checked' : '' ?> onchange="filterKategoriByJenis()">
                                    <label class="btn btn-outline-success w-100 py-2 d-flex align-items-center justify-content-center fw-bold" for="jenis_pemasukan">
                                        <i class="bi bi-arrow-down-left-circle-fill me-2 fs-5"></i> Kas Masuk (Pemasukan)
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="jenis" id="jenis_pengeluaran" value="pengeluaran" <?= $trx['jenis'] == 'pengeluaran' ? 'checked' : '' ?> onchange="filterKategoriByJenis()">
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
                                    <option value="<?= $kat['id'] ?>" data-jenis="<?= $kat['jenis'] ?>" <?= $trx['kategori_id'] == $kat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kat['nama_kategori']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Nominal Transaksi -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Nominal (Rupiah) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold bg-light">Rp</span>
                                <input type="text" name="nominal" id="nominal" class="form-control format-rupiah" value="<?= number_format($trx['nominal'], 0, ',', '.') ?>" required style="font-size: 1.1rem; font-weight: 700;">
                            </div>
                        </div>

                        <!-- Metode Pembayaran -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Metode Pembayaran</label>
                            <select name="metode_pembayaran" class="form-select">
                                <option value="Tunai" <?= $trx['metode_pembayaran'] == 'Tunai' ? 'selected' : '' ?>>Tunai (Cash)</option>
                                <option value="Transfer Bank" <?= $trx['metode_pembayaran'] == 'Transfer Bank' ? 'selected' : '' ?>>Transfer Bank (BCA / Mandiri / BRI / BNI)</option>
                                <option value="QRIS" <?= $trx['metode_pembayaran'] == 'QRIS' ? 'selected' : '' ?>>QRIS (Gopay / OVO / Dana / ShopeePay)</option>
                                <option value="Kartu Debit / Kredit" <?= $trx['metode_pembayaran'] == 'Kartu Debit / Kredit' ? 'selected' : '' ?>>Kartu Debit / Kredit</option>
                            </select>
                        </div>

                        <!-- Upload Bukti Baru -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold small">Ganti Bukti / Nota (Opsional)</label>
                            <input type="file" name="bukti" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                            <?php if (!empty($trx['bukti_transaksi'])): ?>
                                <div class="form-text small mt-1">
                                    File saat ini: <a href="uploads/<?= htmlspecialchars($trx['bukti_transaksi']) ?>" target="_blank" class="text-primary"><i class="bi bi-paperclip"></i> Lihat Berkas Lampiran</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Keterangan Transaksi -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Keterangan / Rincian</label>
                            <textarea name="keterangan" rows="3" class="form-control"><?= htmlspecialchars($trx['keterangan'] ?? '') ?></textarea>
                        </div>

                        <div class="col-12 mt-4 pt-2 border-top d-flex gap-2 justify-content-end">
                            <a href="transaksi.php" class="btn btn-light px-4">Batal</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">
                                <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
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

    options.forEach(opt => {
        if (opt.value === '') return;
        const optJenis = opt.getAttribute('data-jenis');
        if (optJenis === jenis) {
            opt.hidden = false;
            opt.disabled = false;
        } else {
            opt.hidden = true;
            opt.disabled = true;
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    filterKategoriByJenis();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

