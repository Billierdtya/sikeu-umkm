<?php
$pageTitle = 'Laporan Laba Rugi Usaha';
require_once __DIR__ . '/includes/header.php';

$bulanPilih = sanitize($_GET['bulan'] ?? date('m'));
$tahunPilih = sanitize($_GET['tahun'] ?? date('Y'));
$mode = sanitize($_GET['mode'] ?? 'bulanan'); // bulanan atau tahunan

$bulanList = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

if ($mode === 'tahunan') {
    $periodeLabel = "Tahun $tahunPilih";
    $whereCondition = "YEAR(t.tanggal) = ?";
    $whereParams = [$tahunPilih];
} else {
    $periodeLabel = "Bulan " . $bulanList[$bulanPilih] . " " . $tahunPilih;
    $whereCondition = "MONTH(t.tanggal) = ? AND YEAR(t.tanggal) = ?";
    $whereParams = [$bulanPilih, $tahunPilih];
}

// 1. Ambil Breakdown Rincian Pendapatan (Pemasukan) per Kategori
$sqlIn = "
    SELECT k.nama_kategori, COALESCE(SUM(t.nominal), 0) as total
    FROM kategori k
    LEFT JOIN transaksi t ON k.id = t.kategori_id AND $whereCondition
    WHERE k.jenis = 'pemasukan'
    GROUP BY k.id
    HAVING total > 0
    ORDER BY total DESC
";
$stmtIn = $pdo->prepare($sqlIn);
$stmtIn->execute($whereParams);
$pendapatanRows = $stmtIn->fetchAll();

$totalPendapatan = 0;
foreach ($pendapatanRows as $row) {
    $totalPendapatan += (float)$row['total'];
}

// 2. Ambil Breakdown Rincian Beban (Pengeluaran) per Kategori
$sqlOut = "
    SELECT k.nama_kategori, COALESCE(SUM(t.nominal), 0) as total
    FROM kategori k
    LEFT JOIN transaksi t ON k.id = t.kategori_id AND $whereCondition
    WHERE k.jenis = 'pengeluaran'
    GROUP BY k.id
    HAVING total > 0
    ORDER BY total DESC
";
$stmtOut = $pdo->prepare($sqlOut);
$stmtOut->execute($whereParams);
$bebanRows = $stmtOut->fetchAll();

$totalBeban = 0;
foreach ($bebanRows as $row) {
    $totalBeban += (float)$row['total'];
}

// 3. Laba / Rugi Bersih
$labaRugiBersih = $totalPendapatan - $totalBeban;
$profitMargin = $totalPendapatan > 0 ? ($labaRugiBersih / $totalPendapatan) * 100 : 0;
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Laporan Laba / Rugi (Income Statement)</h4>
        <p class="text-muted mb-0 small">Kinerja profitabilitas bisnis UMKM berdasarkan perbandingan total pendapatan dan beban operasional.</p>
    </div>
    <div class="d-flex gap-2 no-print">
        <a href="cetak_laporan.php?tipe=laba_rugi&bulan=<?= $bulanPilih ?>&tahun=<?= $tahunPilih ?>&mode=<?= $mode ?>" target="_blank" class="btn btn-outline-secondary d-flex align-items-center">
            <i class="bi bi-printer me-1"></i> Cetak / PDF
        </a>
        <a href="export_excel.php?tipe=laba_rugi&bulan=<?= $bulanPilih ?>&tahun=<?= $tahunPilih ?>&mode=<?= $mode ?>" target="_blank" class="btn btn-success d-flex align-items-center">
            <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
        </a>
    </div>
</div>

<!-- Card Filter Periode -->
<div class="card mb-4 no-print shadow-sm border-0">
    <div class="card-body p-3">
        <form method="GET" action="laporan_laba_rugi.php" class="row g-2 align-items-center">
            <div class="col-auto">
                <span class="fw-bold small text-muted"><i class="bi bi-funnel me-1"></i>Filter Laporan:</span>
            </div>
            <div class="col-auto">
                <select name="mode" class="form-select form-select-sm" id="selectMode" onchange="toggleBulanInput()">
                    <option value="bulanan" <?= $mode == 'bulanan' ? 'selected' : '' ?>>Laporan Bulanan</option>
                    <option value="tahunan" <?= $mode == 'tahunan' ? 'selected' : '' ?>>Laporan Tahunan Penuh</option>
                </select>
            </div>
            <div class="col-auto" id="colBulan">
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

<!-- Rangkuman Hasil Laba Rugi -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card p-3 bg-white h-100">
            <span class="text-muted small fw-bold">TOTAL PENDAPATAN</span>
            <div class="fs-4 fw-bold text-success mt-1"><?= rupiah($totalPendapatan) ?></div>
            <small class="text-muted">Akumulasi seluruh pos pemasukan</small>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card p-3 bg-white h-100">
            <span class="text-muted small fw-bold">TOTAL BEBAN OPERASIONAL</span>
            <div class="fs-4 fw-bold text-danger mt-1"><?= rupiah($totalBeban) ?></div>
            <small class="text-muted">Akumulasi seluruh pos biaya & belanja</small>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card p-3 bg-white h-100 border-<?= $labaRugiBersih >= 0 ? 'success' : 'danger' ?>">
            <span class="<?= $labaRugiBersih >= 0 ? 'text-success' : 'text-danger' ?> small fw-bold">
                <?= $labaRugiBersih >= 0 ? 'LABA BERSIH (SURPLUS)' : 'RUGI BERSIH (DEFISIT)' ?>
            </span>
            <div class="fs-4 fw-bold <?= $labaRugiBersih >= 0 ? 'text-success' : 'text-danger' ?> mt-1">
                <?= rupiah($labaRugiBersih) ?>
            </div>
            <small class="text-muted">Margin: <strong><?= number_format($profitMargin, 1, ',', '.') ?>%</strong> dari pendapatan</small>
        </div>
    </div>
</div>

<!-- Format Format Standar Laporan Laba Rugi Akuntansi Bisnis -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom text-center">
        <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($profil['nama_bisnis']) ?></h5>
        <div class="text-secondary fw-semibold">LAPORAN LABA / RUGI USAHA</div>
        <div class="small text-muted">Periode: <?= $periodeLabel ?></div>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0">
                <tbody>
                    <!-- BAGIAN I: PENDAPATAN -->
                    <tr class="table-light">
                        <td colspan="2" class="fw-bold text-primary fs-6 py-2">
                            <i class="bi bi-chevron-right me-1"></i> I. PENDAPATAN / PENJUALAN (REVENUE)
                        </td>
                        <td></td>
                    </tr>
                    <?php if (empty($pendapatanRows)): ?>
                        <tr>
                            <td style="width: 40px;"></td>
                            <td class="text-muted fst-italic">Tidak ada pendapatan pada periode ini</td>
                            <td class="text-end text-muted">Rp 0</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pendapatanRows as $row): ?>
                            <tr>
                                <td style="width: 40px;"></td>
                                <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                                <td class="text-end fw-semibold text-secondary"><?= rupiah($row['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr class="border-top border-bottom fw-bold" style="background-color: #f0fdf4;">
                        <td colspan="2" class="text-success ps-4">TOTAL PENDAPATAN USAHA (A)</td>
                        <td class="text-end text-success fs-6"><?= rupiah($totalPendapatan) ?></td>
                    </tr>

                    <tr><td colspan="3" class="py-2"></td></tr>

                    <!-- BAGIAN II: BEBAN OPERASIONAL -->
                    <tr class="table-light">
                        <td colspan="2" class="fw-bold text-danger fs-6 py-2">
                            <i class="bi bi-chevron-right me-1"></i> II. BEBAN OPERASIONAL USAHA (EXPENSES)
                        </td>
                        <td></td>
                    </tr>
                    <?php if (empty($bebanRows)): ?>
                        <tr>
                            <td style="width: 40px;"></td>
                            <td class="text-muted fst-italic">Tidak ada beban pengeluaran pada periode ini</td>
                            <td class="text-end text-muted">Rp 0</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bebanRows as $row): ?>
                            <tr>
                                <td style="width: 40px;"></td>
                                <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                                <td class="text-end fw-semibold text-secondary"><?= rupiah($row['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr class="border-top border-bottom fw-bold" style="background-color: #fef2f2;">
                        <td colspan="2" class="text-danger ps-4">TOTAL BEBAN OPERASIONAL (B)</td>
                        <td class="text-end text-danger fs-6"><?= rupiah($totalBeban) ?></td>
                    </tr>

                    <tr><td colspan="3" class="py-3"></td></tr>

                    <!-- BAGIAN III: LABA / RUGI BERSIH -->
                    <tr class="table-active border-top border-2 border-dark fw-bold" style="font-size: 1.1rem;">
                        <td colspan="2" class="py-3 ps-3 <?= $labaRugiBersih >= 0 ? 'text-success' : 'text-danger' ?>">
                            <i class="bi <?= $labaRugiBersih >= 0 ? 'bi-check2-circle' : 'bi-x-circle' ?> me-2"></i>
                            LABA / (RUGI) BERSIH OPERASIONAL (A - B)
                        </td>
                        <td class="text-end py-3 pe-3 <?= $labaRugiBersih >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= rupiah($labaRugiBersih) ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleBulanInput() {
    const mode = document.getElementById('selectMode').value;
    const colBulan = document.getElementById('colBulan');
    if (mode === 'tahunan') {
        colBulan.style.display = 'none';
    } else {
        colBulan.style.display = 'block';
    }
}
document.addEventListener('DOMContentLoaded', toggleBulanInput);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

