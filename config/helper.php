<?php
// Pastikan session sudah start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Format angka menjadi format Rupiah Indonesia
 */
function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

/**
 * Format tanggal standar (YYYY-MM-DD) ke format Indonesia (contoh: 10 November 2026)
 */
function tanggal_indo($tanggal, $cetak_hari = false) {
    if (empty($tanggal) || $tanggal == '0000-00-00') {
        return '-';
    }

    $hari = [
        1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'
    ];

    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $pecahkan = explode('-', date('Y-m-d', strtotime($tanggal)));

    $tgl_indo = $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];

    if ($cetak_hari) {
        $num_hari = date('N', strtotime($tanggal));
        return $hari[$num_hari] . ', ' . $tgl_indo;
    }

    return $tgl_indo;
}

/**
 * Sanitasi string input dari form XSS
 */
function sanitize($input) {
    return htmlspecialchars(trim($input ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Set pesan flash notifikasi
 */
function set_flash($type, $pesan) {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $pesan
    ];
}

/**
 * Ambil dan bersihkan notifikasi flash
 */
function get_flash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Tampilkan alert notifikasi bootstrap jika ada pesan flash
 */
function render_flash() {
    $flash = get_flash();
    if ($flash) {
        $icon = match($flash['type']) {
            'success' => 'bi-check-circle-fill',
            'danger' => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-circle-fill',
            default => 'bi-info-circle-fill',
        };
        echo '
        <div class="alert alert-' . $flash['type'] . ' alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
            <i class="bi ' . $icon . ' me-2 fs-5"></i>
            <div>' . $flash['message'] . '</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
}

/**
 * Generate Kode Transaksi Otomatis (Contoh: TRX-20261110-XXXX)
 */
function generate_kode_transaksi($pdo) {
    $prefix = 'TRX-' . date('Ymd') . '-';
    $stmt = $pdo->prepare("SELECT kode_transaksi FROM transaksi WHERE kode_transaksi LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $lastTrx = $stmt->fetch();

    if ($lastTrx) {
        $lastNumber = (int)substr($lastTrx['kode_transaksi'], -3);
        $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $nextNumber = '001';
    }

    return $prefix . $nextNumber;
}

/**
 * Ambil data pengaturan profil bisnis
 */
function get_profil_bisnis($pdo) {
    $stmt = $pdo->query("SELECT * FROM pengaturan ORDER BY id ASC LIMIT 1");
    $data = $stmt->fetch();
    if (!$data) {
        return [
            'nama_bisnis' => 'UMKM Bisnis Riski',
            'pemilik' => 'Riski',
            'telepon' => '-',
            'alamat' => '-',
            'mata_uang' => 'Rp'
        ];
    }
    return $data;
}

