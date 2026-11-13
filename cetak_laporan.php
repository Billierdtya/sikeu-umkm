<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helper.php';

$pdo = getDBConnection();
$profil = get_profil_bisnis($pdo);

$tipe = sanitize($_GET['tipe'] ?? 'arus_kas');
$bulanPilih = sanitize($_GET['bulan'] ?? date('m'));
$tahunPilih = sanitize($_GET['tahun'] ?? date('Y'));
$mode = sanitize($_GET['mode'] ?? 'bulanan');

$bulanList = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Keuangan - <?= htmlspecialchars($profil['nama_bisnis']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            font-family: Arial, sans-serif;
            color: #1e293b;
        }
        .paper {
            background: #ffffff;
            max-width: 850px;
            margin: 20px auto;
            padding: 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-radius: 8px;
        }
        .header-kop {
            border-bottom: 3px double #1e293b;
            padding-bottom: 15px;
            margin-bottom: 25px;
            text-align: center;
        }
        .header-kop h3 {
            margin: 0;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .header-kop p {
            margin: 2px 0 0;
            font-size: 0.9rem;
            color: #475569;
        }
        .table-print th {
            background-color: #f1f5f9 !important;
            font-weight: bold;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        .table-print td, .table-print th {
            padding: 8px 12px;
            font-size: 0.9rem;
            border-color: #cbd5e1;
        }
        .signature-box {
            margin-top: 50px;
        }
        .sig-line {
            width: 200px;
            border-bottom: 1px solid #1e293b;
            margin-top: 65px;
            margin-bottom: 5px;
        }
        @media print {
            body { background: #fff !important; }
            .paper { box-shadow: none !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; padding: 0 !important; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container my-3 no-print text-center">
    <button class="btn btn-primary px-4 fw-bold me-2" onclick="window.print()">
        <i class="bi bi-printer me-1"></i> Cetak Dokumen / Simpan PDF
    </button>
    <button class="btn btn-secondary px-3" onclick="window.close()">Tutup Jendela</button>
</div>

<div class="paper">
    <!-- KOP LAPORAN -->
    <div class="header-kop">
        <h3><?= htmlspecialchars($profil['nama_bisnis']) ?></h3>
        <p><?= htmlspecialchars($profil['alamat']) ?> | Telp: <?= htmlspecialchars($profil['telepon']) ?></p>
        <p class="fw-bold mt-2 text-uppercase text-decoration-underline" style="font-size: 1.05rem;">
            <?= $tipe === 'laba_rugi' ? 'LAPORAN LABA / RUGI USAHA' : 'LAPORAN ARUS KAS (BUKU KAS UMUM)' ?>
        </p>
        <div class="small text-muted">
            Periode: <?= $mode === 'tahunan' ? "Tahun $tahunPilih" : $bulanList[$bulanPilih] . " $tahunPilih" ?>
        </div>
    </div>

    <?php if ($tipe === 'laba_rugi'): ?>
        <?php
        if ($mode === 'tahunan') {
            $whereCondition = "YEAR(t.tanggal) = ?";
            $whereParams = [$tahunPilih];
        } else {
            $whereCondition = "MONTH(t.tanggal) = ? AND YEAR(t.tanggal) = ?";
            $whereParams = [$bulanPilih, $tahunPilih];
        }

        $stmtIn = $pdo->prepare("
            SELECT k.nama_kategori, COALESCE(SUM(t.nominal), 0) as total
            FROM kategori k
            LEFT JOIN transaksi t ON k.id = t.kategori_id AND $whereCondition
            WHERE k.jenis = 'pemasukan'
            GROUP BY k.id HAVING total > 0 ORDER BY total DESC
        ");
        $stmtIn->execute($whereParams);
        $pendapatanRows = $stmtIn->fetchAll();
        $totalPendapatan = array_sum(array_column($pendapatanRows, 'total'));

        $stmtOut = $pdo->prepare("
            SELECT k.nama_kategori, COALESCE(SUM(t.nominal), 0) as total
            FROM kategori k
            LEFT JOIN transaksi t ON k.id = t.kategori_id AND $whereCondition
            WHERE k.jenis = 'pengeluaran'
            GROUP BY k.id HAVING total > 0 ORDER BY total DESC
        ");
        $stmtOut->execute($whereParams);
        $bebanRows = $stmtOut->fetchAll();
        $totalBeban = array_sum(array_column($bebanRows, 'total'));

        $labaRugiBersih = $totalPendapatan - $totalBeban;
        ?>

        <table class="table table-bordered table-print align-middle">
            <tbody>
                <tr class="table-light fw-bold">
                    <td colspan="2">I. PENDAPATAN / REVENUE USAHA</td>
                </tr>
                <?php if (empty($pendapatanRows)): ?>
                    <tr><td class="ps-4 fst-italic text-muted">Tidak ada pendapatan</td><td class="text-end">Rp 0</td></tr>
                <?php else: ?>
                    <?php foreach ($pendapatanRows as $r): ?>
                        <tr>
                            <td class="ps-4"><?= htmlspecialchars($r['nama_kategori']) ?></td>
                            <td class="text-end"><?= rupiah($r['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr class="table-success fw-bold">
                    <td>TOTAL PENDAPATAN (A)</td>
                    <td class="text-end"><?= rupiah($totalPendapatan) ?></td>
                </tr>

                <tr class="table-light fw-bold">
                    <td colspan="2">II. BEBAN OPERASIONAL USAHA</td>
                </tr>
                <?php if (empty($bebanRows)): ?>
                    <tr><td class="ps-4 fst-italic text-muted">Tidak ada beban</td><td class="text-end">Rp 0</td></tr>
                <?php else: ?>
                    <?php foreach ($bebanRows as $r): ?>
                        <tr>
                            <td class="ps-4"><?= htmlspecialchars($r['nama_kategori']) ?></td>
                            <td class="text-end"><?= rupiah($r['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr class="table-danger fw-bold">
                    <td>TOTAL BEBAN OPERASIONAL (B)</td>
                    <td class="text-end"><?= rupiah($totalBeban) ?></td>
                </tr>

                <tr class="table-secondary fw-bold" style="font-size: 1.05rem;">
                    <td>LABA / (RUGI) BERSIH OPERASIONAL (A - B)</td>
                    <td class="text-end <?= $labaRugiBersih >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= rupiah($labaRugiBersih) ?>
                    </td>
                </tr>
            </tbody>
        </table>

    <?php else: ?>
        <?php
        $tanggalAwal = "$tahunPilih-$bulanPilih-01";
        $tanggalAkhir = date('Y-m-t', strtotime($tanggalAwal));

        $stmtAwalIn = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM transaksi WHERE jenis = 'pemasukan' AND tanggal < ?");
        $stmtAwalIn->execute([$tanggalAwal]);
        $saldoAwalIn = (float)$stmtAwalIn->fetchColumn();

        $stmtAwalOut = $pdo->prepare("SELECT COALESCE(SUM(nominal), 0) FROM transaksi WHERE jenis = 'pengeluaran' AND tanggal < ?");
        $stmtAwalOut->execute([$tanggalAwal]);
        $saldoAwalOut = (float)$stmtAwalOut->fetchColumn();

        $saldoAwal = $saldoAwalIn - $saldoAwalOut;

        $stmtTrx = $pdo->prepare("
            SELECT t.*, k.nama_kategori 
            FROM transaksi t 
            JOIN kategori k ON t.kategori_id = k.id 
            WHERE t.tanggal >= ? AND t.tanggal <= ? 
            ORDER BY t.tanggal ASC, t.id ASC
        ");
        $stmtTrx->execute([$tanggalAwal, $tanggalAkhir]);
        $rows = $stmtTrx->fetchAll();

        $totIn = 0; $totOut = 0;
        foreach ($rows as $r) {
            if ($r['jenis'] == 'pemasukan') $totIn += (float)$r['nominal'];
            else $totOut += (float)$r['nominal'];
        }
        $saldoAkhir = $saldoAwal + $totIn - $totOut;
        ?>

        <table class="table table-bordered table-print align-middle">
            <thead class="text-center">
                <tr>
                    <th style="width: 35px;">No</th>
                    <th style="width: 90px;">Tanggal</th>
                    <th>Keterangan / Uraian</th>
                    <th class="text-end">Masuk (Debet)</th>
                    <th class="text-end">Keluar (Kredit)</th>
                    <th class="text-end">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <tr class="fw-bold table-light">
                    <td class="text-center">-</td>
                    <td class="text-center"><?= date('d/m/Y', strtotime($tanggalAwal)) ?></td>
                    <td><em>Saldo Kas Awal Periode</em></td>
                    <td class="text-end">-</td>
                    <td class="text-end">-</td>
                    <td class="text-end"><?= rupiah($saldoAwal) ?></td>
                </tr>
                <?php 
                $bal = $saldoAwal; 
                $n = 1;
                foreach ($rows as $r): 
                    if ($r['jenis'] == 'pemasukan') $bal += (float)$r['nominal'];
                    else $bal -= (float)$r['nominal'];
                ?>
                    <tr>
                        <td class="text-center"><?= $n++ ?></td>
                        <td class="text-center"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                        <td>
                            <strong>[<?= htmlspecialchars($r['nama_kategori']) ?>]</strong> <?= htmlspecialchars($r['keterangan'] ?: '-') ?>
                        </td>
                        <td class="text-end"><?= $r['jenis'] == 'pemasukan' ? rupiah($r['nominal']) : '-' ?></td>
                        <td class="text-end"><?= $r['jenis'] == 'pengeluaran' ? rupiah($r['nominal']) : '-' ?></td>
                        <td class="text-end fw-semibold"><?= rupiah($bal) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="3" class="text-end">TOTAL MUTASI & SALDO AKHIR:</td>
                    <td class="text-end text-success"><?= rupiah($totIn) ?></td>
                    <td class="text-end text-danger"><?= rupiah($totOut) ?></td>
                    <td class="text-end text-primary"><?= rupiah($saldoAkhir) ?></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <!-- TANDA TANGAN FORMAL BISNIS -->
    <div class="row signature-box">
        <div class="col-6 text-center">
            <p class="mb-0">Dibuat Oleh,</p>
            <p class="text-muted small">Bagian Administrasi Keuangan</p>
            <div class="sig-line mx-auto"></div>
            <p class="fw-bold mb-0">( Staff Keuangan )</p>
        </div>
        <div class="col-6 text-center">
            <p class="mb-0">Disetujui & Diketahui,</p>
            <p class="text-muted small">Pemilik Usaha / Pimpinan</p>
            <div class="sig-line mx-auto"></div>
            <p class="fw-bold mb-0">( <?= htmlspecialchars($profil['pemilik']) ?> )</p>
        </div>
    </div>
</div>

</body>
</html>

