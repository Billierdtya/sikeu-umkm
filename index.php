<?php
$pageTitle = 'Dashboard Keuangan';
require_once __DIR__ . '/includes/header.php';

// Periode Bulan & Tahun Berjalan
$currentMonth = date('m');
$currentYear = date('Y');

// 1. Total Saldo Kas Keseluruhan
$totalPemasukanAll = (float)$pdo->query("SELECT COALESCE(SUM(nominal), 0) FROM transaksi WHERE jenis = 'pemasukan'")->fetchColumn();
$totalPengeluaranAll = (float)$pdo->query("SELECT COALESCE(SUM(nominal), 0) FROM transaksi WHERE jenis = 'pengeluaran'")->fetchColumn();
$totalSaldoKas = $totalPemasukanAll - $totalPengeluaranAll;

// 2. Data Bulan Ini
$stmtMonthIn = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM transaksi WHERE jenis = 'pemasukan' AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
$stmtMonthIn->execute([$currentMonth, $currentYear]);
$pemasukanBulanIni = (float)$stmtMonthIn->fetchColumn();

$stmtMonthOut = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM transaksi WHERE jenis = 'pengeluaran' AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
$stmtMonthOut->execute([$currentMonth, $currentYear]);
$pengeluaranBulanIni = (float)$stmtMonthOut->fetchColumn();

$labaRugiBulanIni = $pemasukanBulanIni - $pengeluaranBulanIni;

// 3. Jumlah Transaksi Bulan Ini
$stmtCountTrx = $pdo->prepare("SELECT COUNT(*) FROM transaksi WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
$stmtCountTrx->execute([$currentMonth, $currentYear]);
$totalTransaksiBulanIni = (int)$stmtCountTrx->fetchColumn();

// 4. Data Transaksi Terbaru (5 data terakhir)
$stmtRecent = $pdo->query("
    SELECT t.*, k.nama_kategori 
    FROM transaksi t 
    JOIN kategori k ON t.kategori_id = k.id 
    ORDER BY t.tanggal DESC, t.id DESC 
    LIMIT 6
");
$recentTransactions = $stmtRecent->fetchAll();

// 5. Data Tren 6 Bulan Terakhir untuk Chart.js
$monthsData = [];
$incomeData = [];
$expenseData = [];

for ($i = 5; $i >= 0; $i--) {
    $targetTime = strtotime("-$i months");
    $m = date('m', $targetTime);
    $y = date('Y', $targetTime);
    $monthLabel = date('M Y', $targetTime);

    $stmtIn = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM transaksi WHERE jenis = 'pemasukan' AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
    $stmtIn->execute([$m, $y]);
    $inVal = (float)$stmtIn->fetchColumn();

    $stmtOut = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM transaksi WHERE jenis = 'pengeluaran' AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
    $stmtOut->execute([$m, $y]);
    $outVal = (float)$stmtOut->fetchColumn();

    $monthsData[] = $monthLabel;
    $incomeData[] = $inVal;
    $expenseData[] = $outVal;
}

// 6. Distribusi Pengeluaran berdasarkan Kategori Bulan Ini
$stmtExpKat = $pdo->prepare("
    SELECT k.nama_kategori, SUM(t.nominal) as total
    FROM transaksi t
    JOIN kategori k ON t.kategori_id = k.id
    WHERE t.jenis = 'pengeluaran' AND MONTH(t.tanggal) = ? AND YEAR(t.tanggal) = ?
    GROUP BY t.kategori_id
    ORDER BY total DESC
    LIMIT 5
");
$stmtExpKat->execute([$currentMonth, $currentYear]);
$expenseByCategory = $stmtExpKat->fetchAll();

$expLabels = [];
$expValues = [];
foreach ($expenseByCategory as $exp) {
    $expLabels[] = $exp['nama_kategori'];
    $expValues[] = (float)$exp['total'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Dashboard Ikhtisar Keuangan</h4>
        <p class="text-muted mb-0 small">Ringkasan kondisi arus kas dan performa keuangan usaha Anda per <?= tanggal_indo(date('Y-m-d')) ?>.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="transaksi_tambah.php" class="btn btn-primary d-flex align-items-center">
            <i class="bi bi-plus-lg me-1"></i> Catat Transaksi
        </a>
        <a href="laporan_arus_kas.php" class="btn btn-outline-secondary d-flex align-items-center">
            <i class="bi bi-printer me-1"></i> Laporan
        </a>
    </div>
</div>

<!-- Kartu Statistik Keuangan -->
<div class="row g-3 mb-4">
    <!-- Total Saldo Kas -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Total Saldo Kas</div>
                    <div class="stat-value <?= $totalSaldoKas >= 0 ? 'text-primary' : 'text-danger' ?>">
                        <?= rupiah($totalSaldoKas) ?>
                    </div>
                </div>
                <div class="stat-icon primary">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
            <div class="mt-3 small text-muted">
                <i class="bi bi-check-circle-fill text-success me-1"></i> Posisi kas berjalan saat ini
            </div>
        </div>
    </div>

    <!-- Pemasukan Bulan Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Pemasukan (<?= date('F') ?>)</div>
                    <div class="stat-value text-success">
                        <?= rupiah($pemasukanBulanIni) ?>
                    </div>
                </div>
                <div class="stat-icon success">
                    <i class="bi bi-arrow-down-left"></i>
                </div>
            </div>
            <div class="mt-3 small text-muted">
                <i class="bi bi-graph-up text-success me-1"></i> Total aliran kas masuk
            </div>
        </div>
    </div>

    <!-- Pengeluaran Bulan Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Pengeluaran (<?= date('F') ?>)</div>
                    <div class="stat-value text-danger">
                        <?= rupiah($pengeluaranBulanIni) ?>
                    </div>
                </div>
                <div class="stat-icon danger">
                    <i class="bi bi-arrow-up-right"></i>
                </div>
            </div>
            <div class="mt-3 small text-muted">
                <i class="bi bi-graph-down text-danger me-1"></i> Biaya operasional & beban
            </div>
        </div>
    </div>

    <!-- Laba/Rugi Bersih Bulan Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card card-stat h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Laba / (Rugi) Bersih</div>
                    <div class="stat-value <?= $labaRugiBulanIni >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= rupiah($labaRugiBulanIni) ?>
                    </div>
                </div>
                <div class="stat-icon <?= $labaRugiBulanIni >= 0 ? 'success' : 'danger' ?>">
                    <i class="bi <?= $labaRugiBulanIni >= 0 ? 'bi-trophy-fill' : 'bi-exclamation-octagon-fill' ?>"></i>
                </div>
            </div>
            <div class="mt-3 small <?= $labaRugiBulanIni >= 0 ? 'text-success' : 'text-danger' ?>">
                <i class="bi <?= $labaRugiBulanIni >= 0 ? 'bi-patch-check-fill' : 'bi-exclamation-triangle-fill' ?> me-1"></i>
                <?= $labaRugiBulanIni >= 0 ? 'Surplus Operasional' : 'Defisit Pengeluaran' ?>
            </div>
        </div>
    </div>
</div>

<!-- Baris Visualisasi Grafik -->
<div class="row g-3 mb-4">
    <!-- Grafik Tren Arus Kas -->
    <div class="col-12 col-lg-8">
        <div class="card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-line-fill text-primary me-2"></i>Tren Arus Kas (6 Bulan Terakhir)</h6>
                <span class="badge bg-light text-secondary border">Dalam Rupiah (Rp)</span>
            </div>
            <div style="position: relative; height: 280px;">
                <canvas id="cashflowChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Grafik Donut Pengeluaran -->
    <div class="col-12 col-lg-4">
        <div class="card p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-pie-chart-fill text-warning me-2"></i>Beban Terbesar Bulan Ini</h6>
            </div>
            <?php if (empty($expValues) || array_sum($expValues) == 0): ?>
                <div class="d-flex flex-column align-items-center justify-content-center h-75 text-center text-muted">
                    <i class="bi bi-inbox fs-1 mb-2"></i>
                    <p class="small mb-0">Belum ada pengeluaran di bulan ini.</p>
                </div>
            <?php else: ?>
                <div style="position: relative; height: 250px;">
                    <canvas id="expensePieChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Baris Transaksi Terkini -->
<div class="card">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
        <div>
            <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Transaksi Terbaru</h6>
            <small class="text-muted">Aktivitas keuangan yang baru saja dicatat</small>
        </div>
        <a href="transaksi.php" class="btn btn-sm btn-outline-primary">Lihat Semua Transaksi <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Keterangan</th>
                    <th>Metode</th>
                    <th class="text-end">Nominal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentTransactions)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-folder-x fs-2 d-block mb-2"></i>
                            Belum ada riwayat transaksi yang dicatat. Silakan tambah transaksi baru.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentTransactions as $row): ?>
                        <tr>
                            <td>
                                <span class="fw-semibold text-secondary" style="font-size: 0.82rem; font-family: monospace;">
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
                                <div class="text-truncate" style="max-width: 280px;" title="<?= htmlspecialchars($row['keterangan']) ?>">
                                    <?= htmlspecialchars($row['keterangan'] ?: '-') ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['metode_pembayaran']) ?></span>
                            </td>
                            <td class="text-end fw-bold <?= $row['jenis'] == 'pemasukan' ? 'text-success' : 'text-danger' ?>">
                                <?= ($row['jenis'] == 'pemasukan' ? '+ ' : '- ') . rupiah($row['nominal']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Inisialisasi Grafik Arus Kas
    const ctxCashflow = document.getElementById('cashflowChart').getContext('2d');
    new Chart(ctxCashflow, {
        type: 'bar',
        data: {
            labels: <?= json_encode($monthsData) ?>,
            datasets: [
                {
                    label: 'Pemasukan (Rp)',
                    data: <?= json_encode($incomeData) ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    borderRadius: 6,
                },
                {
                    label: 'Pengeluaran (Rp)',
                    data: <?= json_encode($expenseData) ?>,
                    backgroundColor: 'rgba(239, 68, 68, 0.85)',
                    borderRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || '';
                            if (label) label += ': ';
                            if (context.parsed.y !== null) {
                                label += 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return 'Rp ' + (value / 1000).toLocaleString('id-ID') + 'k';
                        }
                    }
                }
            }
        }
    });

    // 2. Inisialisasi Donut Pengeluaran jika ada data
    <?php if (!empty($expValues) && array_sum($expValues) > 0): ?>
    const ctxExpense = document.getElementById('expensePieChart').getContext('2d');
    new Chart(ctxExpense, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($expLabels) ?>,
            datasets: [{
                data: <?= json_encode($expValues) ?>,
                backgroundColor: [
                    '#ef4444', '#f59e0b', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12 } },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let val = context.parsed;
                            return ' ' + context.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(val);
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

