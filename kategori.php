<?php
$pageTitle = 'Manajemen Kategori Keuangan';
require_once __DIR__ . '/includes/header.php';

// Aksi Tambah Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama_kategori = sanitize($_POST['nama_kategori'] ?? '');
    $jenis = sanitize($_POST['jenis'] ?? '');
    $deskripsi = sanitize($_POST['deskripsi'] ?? '');

    if (!empty($nama_kategori) && in_array($jenis, ['pemasukan', 'pengeluaran'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori, jenis, deskripsi) VALUES (?, ?, ?)");
            $stmt->execute([$nama_kategori, $jenis, $deskripsi]);
            set_flash('success', "Kategori <strong>$nama_kategori</strong> berhasil ditambahkan!");
        } catch (PDOException $e) {
            set_flash('danger', 'Gagal menambah kategori: ' . $e->getMessage());
        }
    } else {
        set_flash('danger', 'Nama kategori dan jenis wajib diisi.');
    }
    echo "<script>window.location.href = 'kategori.php';</script>";
    exit;
}

// Aksi Edit Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $nama_kategori = sanitize($_POST['nama_kategori'] ?? '');
    $jenis = sanitize($_POST['jenis'] ?? '');
    $deskripsi = sanitize($_POST['deskripsi'] ?? '');

    if ($id > 0 && !empty($nama_kategori) && in_array($jenis, ['pemasukan', 'pengeluaran'])) {
        try {
            $stmt = $pdo->prepare("UPDATE kategori SET nama_kategori = ?, jenis = ?, deskripsi = ? WHERE id = ?");
            $stmt->execute([$nama_kategori, $jenis, $deskripsi, $id]);
            set_flash('success', "Kategori berhasil diperbarui!");
        } catch (PDOException $e) {
            set_flash('danger', 'Gagal memperbarui kategori: ' . $e->getMessage());
        }
    }
    echo "<script>window.location.href = 'kategori.php';</script>";
    exit;
}

// Aksi Hapus Kategori
if (isset($_GET['hapus'])) {
    $idHapus = (int)$_GET['hapus'];
    // Cek apakah kategori digunakan dalam transaksi
    $cekTrx = $pdo->prepare("SELECT COUNT(*) FROM transaksi WHERE kategori_id = ?");
    $cekTrx->execute([$idHapus]);
    $countTrx = $cekTrx->fetchColumn();

    if ($countTrx > 0) {
        set_flash('danger', "Kategori tidak dapat dihapus karena sedang digunakan oleh <strong>$countTrx transaksi</strong>.");
    } else {
        $stmtDel = $pdo->prepare("DELETE FROM kategori WHERE id = ?");
        $stmtDel->execute([$idHapus]);
        set_flash('success', 'Kategori berhasil dihapus.');
    }
    echo "<script>window.location.href = 'kategori.php';</script>";
    exit;
}

// Ambil data kategori beserta jumlah transaksi yang terkait
$sqlKat = "
    SELECT k.*, COUNT(t.id) as total_penggunaan
    FROM kategori k
    LEFT JOIN transaksi t ON k.id = t.kategori_id
    GROUP BY k.id
    ORDER BY k.jenis ASC, k.nama_kategori ASC
";
$kategoriList = $pdo->query($sqlKat)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Kategori Akun Keuangan</h4>
        <p class="text-muted mb-0 small">Kelola pos-pos anggaran pemasukan dan jenis pengeluaran bisnis Anda.</p>
    </div>
    <button type="button" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
        <i class="bi bi-plus-lg me-1"></i> Tambah Kategori Baru
    </button>
</div>

<div class="row g-4">
    <!-- Tabel Kategori -->
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Nama Kategori</th>
                            <th>Klasifikasi Jenis</th>
                            <th>Deskripsi / Catatan</th>
                            <th class="text-center">Dipakai di Trx</th>
                            <th class="text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($kategoriList)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Belum ada kategori yang ditambahkan.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($kategoriList as $row): ?>
                                <tr>
                                    <td class="text-muted"><?= $no++ ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($row['nama_kategori']) ?></td>
                                    <td>
                                        <span class="badge <?= $row['jenis'] == 'pemasukan' ? 'badge-pemasukan' : 'badge-pengeluaran' ?>">
                                            <i class="bi <?= $row['jenis'] == 'pemasukan' ? 'bi-arrow-down-left' : 'bi-arrow-up-right' ?> me-1"></i>
                                            <?= ucfirst($row['jenis']) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small"><?= htmlspecialchars($row['deskripsi'] ?: '-') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary px-2 py-1"><?= $row['total_penggunaan'] ?> transaksi</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-warning" 
                                                onclick="bukaModalEdit(<?= htmlspecialchars(json_encode($row)) ?>)" title="Edit Kategori">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" 
                                                onclick="konfirmasiHapus('kategori.php?hapus=<?= $row['id'] ?>', 'kategori <?= htmlspecialchars($row['nama_kategori']) ?>')" title="Hapus Kategori">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="modalTambahKategori" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="kategori.php">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Kategori Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kategori" class="form-control" placeholder="Contoh: Penjualan Online, Gaji Staff..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Klasifikasi Jenis <span class="text-danger">*</span></label>
                        <select name="jenis" class="form-select" required>
                            <option value="pemasukan">Pemasukan (Kas Masuk)</option>
                            <option value="pengeluaran">Pengeluaran (Biaya / Kas Keluar)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Keterangan / Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="2" placeholder="Catatan singkat tujuan pos kategori ini..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal fade" id="modalEditKategori" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="kategori.php">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kategori" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Klasifikasi Jenis <span class="text-danger">*</span></label>
                        <select name="jenis" id="edit_jenis" class="form-select" required>
                            <option value="pemasukan">Pemasukan (Kas Masuk)</option>
                            <option value="pengeluaran">Pengeluaran (Biaya / Kas Keluar)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Keterangan / Deskripsi</label>
                        <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-bold">Perbarui Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function bukaModalEdit(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_nama').value = data.nama_kategori;
    document.getElementById('edit_jenis').value = data.jenis;
    document.getElementById('edit_deskripsi').value = data.deskripsi || '';
    new bootstrap.Modal(document.getElementById('modalEditKategori')).show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

