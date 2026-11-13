<?php
$pageTitle = 'Data Transaksi Keuangan';
require_once __DIR__ . '/includes/header.php';

// Ambil parameter filter
$filterJenis = sanitize($_GET['jenis'] ?? '');
$filterKategori = sanitize($_GET['kategori_id'] ?? '');
$filterStartDate = sanitize($_GET['start_date'] ?? '');
$filterEndDate = sanitize($_GET['end_date'] ?? '');
$filterKeyword = sanitize($_GET['q'] ?? '');

// Susun Query Dinamis
$sql = "
    SELECT t.*, k.nama_kategori 
    FROM transaksi t 
    JOIN kategori k ON t.kategori_id = k.id 
    WHERE 1=1
";
$params = [];

if (!empty($filterJenis)) {
    $sql .= " AND t.jenis = ?";
    $params[] = $filterJenis;
}

if (!empty($filterKategori)) {
    $sql .= " AND t.kategori_id = ?";
    $params[] = $filterKategori;
}

if (!empty($filterStartDate)) {
    $sql .= " AND t.tanggal >= ?";
    $params[] = $filterStartDate;
}

if (!empty($filterEndDate)) {
    $sql .= " AND t.tanggal <= ?";
    $params[] = $filterEndDate;
}

if (!empty($filterKeyword)) {
    $sql .= " AND (t.keterangan LIKE ? OR t.kode_transaksi LIKE ? OR t.metode_pembayaran LIKE ?)";
    $keywordWildcard = "%$filterKeyword%";
    $params[] = $keywordWildcard;
    $params[] = $keywordWildcard;
    $params[] = $keywordWildcard;
}

$sql .= " ORDER BY t.tanggal DESC, t.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transaksiList = $stmt->fetchAll();

// Hitung total ringkasan dari hasil filter
$totalMasukFilter = 0;
$totalKeluarFilter = 0;
foreach ($transaksiList as $row) {
    if ($row['jenis'] == 'pemasukan') {
        $totalMasukFilter += (float)$row['nominal'];
    } else {
        $totalKeluarFilter += (float)$row['nominal'];
    }
}
$selisihFilter = $totalMasukFilter - $totalKeluarFilter;

// Ambil daftar kategori untuk dropdown filter
$kategoriSemua = $pdo->query("SELECT * FROM kategori ORDER BY jenis ASC, nama_kategori ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Daftar Transaksi Kas</h4>
        <p class="text-muted mb-0 small">Seluruh rekapitulasi pencatatan arus masuk dan keluar keuangan bisnis.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="transaksi_tambah.php" class="btn btn-primary d-flex align-items-center">
            <i class="bi bi-plus-lg me-1"></i> Tambah Transaksi
        </a>
        <a href="export_excel.php?<?= http_build_query($_GET) ?>" class="btn btn-success d-flex align-items-center" target="_blank">
            <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
        </a>
    </div>
</div>

<!-- Card Filter & Pencarian -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form method="GET" action="transaksi.php" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Cari Keterangan / Kode</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Kata kunci..." value="<?= htmlspecialchars($filterKeyword) ?>">
                </div>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Jenis</label>
                <select name="jenis" class="form-select form-select-sm">
                    <option value="">-- Semua Jenis --</option>
                    <option value="pemasukan" <?= $filterJenis == 'pemasukan' ? 'selected' : '' ?>>Pemasukan (+)</option>
                    <option value="pengeluaran" <?= $filterJenis == 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran (-)</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Kategori</label>
                <select name="kategori_id" class="form-select form-select-sm">
                    <option value="">-- Semua Kategori --</option>
                    <?php foreach ($kategoriSemua as $kat): ?>
                        <option value="<?= $kat['id'] ?>" <?= $filterKategori == $kat['id'] ? 'selected' : '' ?>>
                            [<?= ucfirst($kat['jenis']) ?>] <?= htmlspecialchars($kat['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filterStartDate) ?>">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filterEndDate) ?>">
            </div>

            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100" title="Terapkan Filter">
                    <i class="bi bi-filter"></i> Filter
                </button>
                <?php if (!empty($_GET)): ?>
                    <a href="transaksi.php" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Ringkasan Hasil Filter -->
<div class="row g-2 mb-3">
    <div class="col-md-4">
        <div class="p-2 px-3 rounded border bg-white d-flex justify-content-between align-items-center">
            <span class="text-muted small">Total Pemasukan:</span>
            <span class="fw-bold text-success"><?= rupiah($totalMasukFilter) ?></span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-2 px-3 rounded border bg-white d-flex justify-content-between align-items-center">
            <span class="text-muted small">Total Pengeluaran:</span>
            <span class="fw-bold text-danger"><?= rupiah($totalKeluarFilter) ?></span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-2 px-3 rounded border bg-white d-flex justify-content-between align-items-center">
            <span class="text-muted small">Selisih Bersih:</span>
            <span class="fw-bold <?= $selisihFilter >= 0 ? 'text-primary' : 'text-danger' ?>">
                <?= rupiah($selisihFilter) ?>
            </span>
        </div>
    </div>
</div>

<!-- Tabel Data Transaksi -->
<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
        <span class="fw-bold"><i class="bi bi-list-ul me-2 text-primary"></i>Data Transaksi (<?= count($transaksiList) ?> record)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Kode Trx</th>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Keterangan</th>
                    <th>Metode</th>
                    <th class="text-end">Nominal</th>
                    <th class="text-center" style="width: 130px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transaksiList)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                            Tidak ada data transaksi yang sesuai dengan filter atau kata kunci yang dipilih.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($transaksiList as $row): ?>
                        <tr>
                            <td class="text-muted"><?= $no++ ?></td>
                            <td>
                                <span class="fw-semibold text-secondary" style="font-family: monospace; font-size: 0.85rem;">
                                    <?= htmlspecialchars($row['kode_transaksi']) ?>
                                </span>
                            </td>
                            <td><?= tanggal_indo($row['tanggal']) ?></td>
                            <td>
                                <span class="badge <?= $row['jenis'] == 'pemasukan' ? 'badge-pemasukan' : 'badge-pengeluaran' ?>">
                                    <?= htmlspecialchars($row['nama_kategori']) ?>
                                </span>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($row['keterangan'] ?: '-') ?></div>
                                <?php if (!empty($row['bukti_transaksi'])): ?>
                                    <a href="uploads/<?= htmlspecialchars($row['bukti_transaksi']) ?>" target="_blank" class="small text-decoration-none text-primary">
                                        <i class="bi bi-paperclip"></i> Bukti Lampiran
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['metode_pembayaran']) ?></span>
                            </td>
                            <td class="text-end fw-bold <?= $row['jenis'] == 'pemasukan' ? 'text-success' : 'text-danger' ?>">
                                <?= ($row['jenis'] == 'pemasukan' ? '+ ' : '- ') . rupiah($row['nominal']) ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="transaksi_edit.php?id=<?= $row['id'] ?>" class="btn btn-outline-warning" title="Edit Transaksi">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Hapus Transaksi" onclick="konfirmasiHapus('transaksi_hapus.php?id=<?= $row['id'] ?>', 'transaksi <?= htmlspecialchars($row['kode_transaksi']) ?>')">
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>

