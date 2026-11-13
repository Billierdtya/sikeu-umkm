<?php
$pageTitle = 'Laporan Buku Arus Kas';
require_once __DIR__ . '/includes/header.php';

// Ambil filter periode (default bulan berjalan)
$bulanPilih = sanitize($_GET['bulan'] ?? date('m'));
$tahunPilih = sanitize($_GET['tahun'] ?? date('Y'));

$tanggalAwalPeriode = "$tahunPilih-$bulanPilih-01";
$tanggalAkhirPeriode = date('Y-m-t', strtotime($tanggalAwalPeriode));

// 1. Hitung Saldo Awal (Total Masuk - Total Keluar sebelum tanggal $tanggalAwalPeriode)
$stmtSaldoAwalIn = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM transaksi WHERE jenis = 'pemasukan' AND tanggal < ?");
$stmtSaldoAwalIn->execute([$tanggalAwalPeriode]);
$saldoAwalIn = (float)$stmtSaldoAwalIn->fetchColumn();

$stmtSaldoAwalOut = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM transaksi WHERE jenis = 'pengeluaran' AND tanggal < ?");
$stmtSaldoAwalOut->execute([$tanggalAwalPeriode]);
$saldoAwalOut = (float)$stmtSaldoAwalOut->fetchColumn();

$saldoAwal = $saldoAwalIn - $saldoAwalOut;

// 2. Ambil Transaksi pada periode ini terurut kronologis tanggal ASC
$stmtTrx = $pdo->prepare("
    SELECT t.*, k.nama_kategori 
    FROM transaksi t
    JOIN kategori k ON t.kategori_id = k.id
    WHERE t.tanggal >= ? AND t.tanggal <= ?
    ORDER BY t.tanggal ASC, t.id ASC
");
$stmtTrx->execute([$tanggalAwalPeriode, $tanggalAkhirPeriode]);
$transaksiPeriode = $stmtTrx->fetchAll();

// 3. Hitung Total Mutasi Periode
$totalPemasukanPeriode = 0;
$totalPengeluaranPeriode = 0;
foreach ($transaksiPeriode as $trx) {
    if ($trx['jenis'] == 'pemasukan') {
        $totalPemasukanPeriode += (float)$trx['nominal'];
    } else {
        $totalPengeluaranPeriode += (float)$trx['nominal'];
    }
}
$saldoAkhir = $saldoAwal + $totalPemasukanPeriode - $totalPengeluaranPeriode;

$bulanList = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Laporan Arus Kas (Cash Flow)</h4>
        <p class="text-muted mb-0 small">Buku kas umum mutasi kas masuk, keluar, dan saldo berjalan per periode.</p>
    </div>
    <div class="d-flex gap-2 no-print">
        <a href="cetak_laporan.php?tipe=arus_kas&bulan=<?= $bulanPilih ?>&tahun=<?= $tahunPilih ?>" target="_blank" class="btn btn-outline-secondary d-flex align-items-center">
            <i class="bi bi-printer me-1"></i> Cetak / PDF
        </a>
        <a href="export_excel.php?tipe=arus_kas&bulan=<?= $bulanPilih ?>&tahun=<?= $tahunPilih ?>" target="_blank" class="btn btn-success d-flex align-items-center">
            <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
        </a>
    </div>
</div>

<!-- Card Filter Periode -->
<div class="card mb-4 no-print shadow-sm border-0">
    <div class="card-body p-3">
        <form method="GET" action="laporan_arus_kas.php" class="row g-2 align-items-center">
            <div class="col-auto">
                <span class="fw-bold small text-muted"><i class="bi bi-calendar3 me-1"></i>Pilih Periode:</span>
            </div>
            <div class="col-auto">
                <select name="bulan" class="form-select form-select-sm">
                    <?php foreach ($bulanList as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $bulanPilih == $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select name="tahun" class="form-select form-select-sm">
                    <?php 
                    $yearNow = (int)date('Y');
                    for ($y = $yearNow - 2; $y <= $yearNow + 1; $y++): ?>
                        <option value="<?= $y ?>" <?= $tahunPilih == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-search me-1"></i> Tampilkan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Ringkasan Posisi Kas Periode Ini -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-3">
        <div class="card p-3 h-100 bg-white">
            <div class="text-muted small fw-semibold">SALDO AWAL KAS</div>
            <div class="fs-5 fw-bold text-secondary mt-1"><?= rupiah($saldoAwal) ?></div>
            <small class="text-muted" style="font-size: 0.75rem;">Posisi sebelum 01 <?= $bulanList[$bulanPilih] ?> <?= $tahunPilih ?></small>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card p-3 h-100 bg-white">
            <div class="text-muted small fw-semibold">TOTAL KAS MASUK (+)</div>
            <div class="fs-5 fw-bold text-success mt-1"><?= rupiah($totalPemasukanPeriode) ?></div>
            <small class="text-muted" style="font-size: 0.75rem;">Akumulasi penerimaan periode ini</small>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card p-3 h-100 bg-white">
            <div class="text-muted small fw-semibold">TOTAL KAS KELUAR (-)</div>
            <div class="fs-5 fw-bold text-danger mt-1"><?= rupiah($totalPengeluaranPeriode) ?></div>
            <small class="text-muted" style="font-size: 0.75rem;">Akumulasi pengeluaran periode ini</small>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card p-3 h-100 bg-white border-primary">
            <div class="text-primary small fw-semibold">SALDO AKHIR KAS</div>
            <div class="fs-5 fw-bold text-primary mt-1"><?= rupiah($saldoAkhir) ?></div>
            <small class="text-muted" style="font-size: 0.75rem;">Posisi per <?= tanggal_indo($tanggalAkhirPeriode) ?></small>
        </div>
    </div>
</div>

<!-- Tabel Mutasi Kas Detail -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">
            <i class="bi bi-table me-2 text-primary"></i>Mutasi Kas Periode <?= $bulanList[$bulanPilih] ?> <?= $tahunPilih ?>
        </h6>
        <span class="badge bg-light text-dark border">Basis Kas (Cash Basis)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-custom align-middle">
            <thead class="text-center">
                <tr>
                    <th style="width: 45px;">No</th>
                    <th style="width: 110px;">Tanggal</th>
                    <th>Kode & Kategori</th>
                    <th>Keterangan / Uraian</th>
                    <th class="text-end" style="width: 150px;">Penerimaan (Masuk)</th>
                    <th class="text-end" style="width: 150px;">Pengeluaran (Keluar)</th>
                    <th class="text-end" style="width: 160px;">Saldo Berjalan</th>
                </tr>
            </thead>
            <tbody>
                <!-- Baris Saldo Awal -->
                <tr class="table-light fw-semibold">
                    <td class="text-center">-</td>
                    <td class="text-center"><?= date('d/m/Y', strtotime($tanggalAwalPeriode)) ?></td>
                    <td colspan="2"><em>Saldo Kas Awal Periode</em></td>
                    <td class="text-end">-</td>
                    <td class="text-end">-</td>
                    <td class="text-end text-primary fw-bold"><?= rupiah($saldoAwal) ?></td>
                </tr>

                <?php if (empty($transaksiPeriode)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            Tidak ada aktivitas transaksi pada bulan <?= $bulanList[$bulanPilih] ?> <?= $tahunPilih ?>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $runningBalance = $saldoAwal;
                    $no = 1;
                    foreach ($transaksiPeriode as $row): 
                        if ($row['jenis'] == 'pemasukan') {
                            $runningBalance += (float)$row['nominal'];
                        } else {
                            $runningBalance -= (float)$row['nominal'];
                        }
                    ?>
                        <tr>
                            <td class="text-center text-muted"><?= $no++ ?></td>
                            <td class="text-center"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                            <td>
                                <div class="fw-semibold small" style="font-family: monospace;"><?= htmlspecialchars($row['kode_transaksi']) ?></div>
                                <span class="badge <?= $row['jenis'] == 'pemasukan' ? 'badge-pemasukan' : 'badge-pengeluaran' ?>" style="font-size: 0.72rem;">
                                    <?= htmlspecialchars($row['nama_kategori']) ?>
                                </span>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($row['keterangan'] ?: '-') ?></div>
                                <small class="text-muted">(Metode: <?= htmlspecialchars($row['metode_pembayaran']) ?>)</small>
                            </td>
                            <td class="text-end text-success fw-semibold">
                                <?= $row['jenis'] == 'pemasukan' ? rupiah($row['nominal']) : '-' ?>
                            </td>
                            <td class="text-end text-danger fw-semibold">
                                <?= $row['jenis'] == 'pengeluaran' ? rupiah($row['nominal']) : '-' ?>
                            </td>
                            <td class="text-end fw-bold <?= $runningBalance >= 0 ? 'text-primary' : 'text-danger' ?>">
                                <?= rupiah($runningBalance) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="4" class="text-end">TOTAL MUTASI PERIODE INI:</td>
                    <td class="text-end text-success"><?= rupiah($totalPemasukanPeriode) ?></td>
                    <td class="text-end text-danger"><?= rupiah($totalPengeluaranPeriode) ?></td>
                    <td class="text-end text-primary"><?= rupiah($saldoAkhir) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

