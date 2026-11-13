<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helper.php';

$pdo = getDBConnection();
$profil = get_profil_bisnis($pdo);

$tipe = sanitize($_GET['tipe'] ?? 'transaksi');
$timestamp = date('Ymd_His');

if ($tipe === 'arus_kas') {
    $bulanPilih = sanitize($_GET['bulan'] ?? date('m'));
    $tahunPilih = sanitize($_GET['tahun'] ?? date('Y'));
    $filename = "Laporan_Arus_Kas_{$tahunPilih}_{$bulanPilih}_{$timestamp}.csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    // BOM UTF-8 for Excel Indonesian character support
    fputs($output, "\xEF\xBB\xBF");

    fputcsv($output, [$profil['nama_bisnis']]);
    fputcsv($output, ['LAPORAN ARUS KAS / BUKU KAS UMUM']);
    fputcsv($output, ["Periode: Bulan $bulanPilih Tahun $tahunPilih"]);
    fputcsv($output, []);

    // Header Kolom
    fputcsv($output, ['No', 'Tanggal', 'Kode Transaksi', 'Kategori', 'Keterangan', 'Metode', 'Masuk (Rp)', 'Keluar (Rp)']);

    $tanggalAwal = "$tahunPilih-$bulanPilih-01";
    $tanggalAkhir = date('Y-m-t', strtotime($tanggalAwal));

    $stmtTrx = $pdo->prepare("
        SELECT t.*, k.nama_kategori 
        FROM transaksi t 
        JOIN kategori k ON t.kategori_id = k.id 
        WHERE t.tanggal >= ? AND t.tanggal <= ? 
        ORDER BY t.tanggal ASC, t.id ASC
    ");
    $stmtTrx->execute([$tanggalAwal, $tanggalAkhir]);
    $rows = $stmtTrx->fetchAll();

    $no = 1;
    $totalMasuk = 0;
    $totalKeluar = 0;
    foreach ($rows as $r) {
        $masuk = $r['jenis'] === 'pemasukan' ? $r['nominal'] : 0;
        $keluar = $r['jenis'] === 'pengeluaran' ? $r['nominal'] : 0;
        $totalMasuk += $masuk;
        $totalKeluar += $keluar;

        fputcsv($output, [
            $no++,
            $r['tanggal'],
            $r['kode_transaksi'],
            $r['nama_kategori'],
            $r['keterangan'],
            $r['metode_pembayaran'],
            $masuk,
            $keluar
        ]);
    }

    fputcsv($output, []);
    fputcsv($output, ['', '', '', '', '', 'TOTAL MASUK', $totalMasuk]);
    fputcsv($output, ['', '', '', '', '', 'TOTAL KELUAR', $totalKeluar]);
    fputcsv($output, ['', '', '', '', '', 'SURPLUS/DEFISIT', $totalMasuk - $totalKeluar]);
    fclose($output);
    exit;

} elseif ($tipe === 'laba_rugi') {
    $bulanPilih = sanitize($_GET['bulan'] ?? date('m'));
    $tahunPilih = sanitize($_GET['tahun'] ?? date('Y'));
    $mode = sanitize($_GET['mode'] ?? 'bulanan');

    $filename = "Laporan_Laba_Rugi_{$tahunPilih}_{$timestamp}.csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF");

    fputcsv($output, [$profil['nama_bisnis']]);
    fputcsv($output, ['LAPORAN LABA / RUGI USAHA']);
    fputcsv($output, ["Periode: " . ($mode === 'tahunan' ? "Tahun $tahunPilih" : "Bulan $bulanPilih $tahunPilih")]);
    fputcsv($output, []);

    if ($mode === 'tahunan') {
        $whereCondition = "YEAR(t.tanggal) = ?";
        $whereParams = [$tahunPilih];
    } else {
        $whereCondition = "MONTH(t.tanggal) = ? AND YEAR(t.tanggal) = ?";
        $whereParams = [$bulanPilih, $tahunPilih];
    }

    // Pendapatan
    fputcsv($output, ['I. PENDAPATAN USAHA', 'Jumlah (Rp)']);
    $stmtIn = $pdo->prepare("
        SELECT k.nama_kategori, COALESCE(SUM(t.nominal), 0) as total
        FROM kategori k
        LEFT JOIN transaksi t ON k.id = t.kategori_id AND $whereCondition
        WHERE k.jenis = 'pemasukan'
        GROUP BY k.id HAVING total > 0 ORDER BY total DESC
    ");
    $stmtIn->execute($whereParams);
    $inRows = $stmtIn->fetchAll();
    $totIn = 0;
    foreach ($inRows as $r) {
        $totIn += $r['total'];
        fputcsv($output, [$r['nama_kategori'], $r['total']]);
    }
    fputcsv($output, ['TOTAL PENDAPATAN (A)', $totIn]);
    fputcsv($output, []);

    // Beban
    fputcsv($output, ['II. BEBAN OPERASIONAL', 'Jumlah (Rp)']);
    $stmtOut = $pdo->prepare("
        SELECT k.nama_kategori, COALESCE(SUM(t.nominal), 0) as total
        FROM kategori k
        LEFT JOIN transaksi t ON k.id = t.kategori_id AND $whereCondition
        WHERE k.jenis = 'pengeluaran'
        GROUP BY k.id HAVING total > 0 ORDER BY total DESC
    ");
    $stmtOut->execute($whereParams);
    $outRows = $stmtOut->fetchAll();
    $totOut = 0;
    foreach ($outRows as $r) {
        $totOut += $r['total'];
        fputcsv($output, [$r['nama_kategori'], $r['total']]);
    }
    fputcsv($output, ['TOTAL BEBAN (B)', $totOut]);
    fputcsv($output, []);

    fputcsv($output, ['LABA / (RUGI) BERSIH (A - B)', $totIn - $totOut]);
    fclose($output);
    exit;

} else {
    // Export Semua Data Transaksi sesuai filter dari transaksi.php
    $filterJenis = sanitize($_GET['jenis'] ?? '');
    $filterKategori = sanitize($_GET['kategori_id'] ?? '');
    $filterStartDate = sanitize($_GET['start_date'] ?? '');
    $filterEndDate = sanitize($_GET['end_date'] ?? '');
    $filterKeyword = sanitize($_GET['q'] ?? '');

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
        $params[] = "%$filterKeyword%";
        $params[] = "%$filterKeyword%";
        $params[] = "%$filterKeyword%";
    }
    $sql .= " ORDER BY t.tanggal DESC, t.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $filename = "Rekap_Transaksi_{$timestamp}.csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF");

    fputcsv($output, ['No', 'Kode Transaksi', 'Tanggal', 'Jenis', 'Kategori', 'Metode Pembayaran', 'Nominal (Rp)', 'Keterangan']);

    $no = 1;
    foreach ($rows as $r) {
        fputcsv($output, [
            $no++,
            $r['kode_transaksi'],
            $r['tanggal'],
            ucfirst($r['jenis']),
            $r['nama_kategori'],
            $r['metode_pembayaran'],
            $r['nominal'],
            $r['keterangan']
        ]);
    }
    fclose($output);
    exit;
}

