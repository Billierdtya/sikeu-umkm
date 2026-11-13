<?php
// Konfigurasi Database XAMPP
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_keuangan_riski');

function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        // Coba koneksi ke database yang sudah ada
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Jika database belum dibuat (error 1049 Unknown database)
        if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
            try {
                // Koneksi ke MySQL server tanpa memilih database
                $rootPdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // Sambungkan kembali dengan database yang baru dibuat
                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                
                // Inisialisasi tabel dan data awal
                initDatabaseTables($pdo);
                return $pdo;
            } catch (PDOException $ex) {
                die("Gagal inisialisasi database: " . $ex->getMessage());
            }
        } else {
            die("Gagal terhubung ke MySQL di XAMPP: " . $e->getMessage() . "<br><br><strong>Pastikan modul MySQL di XAMPP Control Panel sudah dalam status START / Running.</strong>");
        }
    }
}

function initDatabaseTables($pdo) {
    // Tabel Kategori
    $pdo->exec("CREATE TABLE IF NOT EXISTS `kategori` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nama_kategori` VARCHAR(100) NOT NULL,
        `jenis` ENUM('pemasukan', 'pengeluaran') NOT NULL,
        `deskripsi` VARCHAR(255) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Tabel Transaksi
    $pdo->exec("CREATE TABLE IF NOT EXISTS `transaksi` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `kode_transaksi` VARCHAR(50) NOT NULL UNIQUE,
        `tanggal` DATE NOT NULL,
        `jenis` ENUM('pemasukan', 'pengeluaran') NOT NULL,
        `kategori_id` INT NOT NULL,
        `nominal` DECIMAL(15,2) NOT NULL DEFAULT 0,
        `metode_pembayaran` VARCHAR(50) DEFAULT 'Tunai',
        `keterangan` TEXT NULL,
        `bukti_transaksi` VARCHAR(255) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`kategori_id`) REFERENCES `kategori`(`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Tabel Pengaturan Profil Bisnis / UMKM
    $pdo->exec("CREATE TABLE IF NOT EXISTS `pengaturan` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nama_bisnis` VARCHAR(150) NOT NULL,
        `pemilik` VARCHAR(100) NOT NULL,
        `telepon` VARCHAR(30) NULL,
        `alamat` TEXT NULL,
        `mata_uang` VARCHAR(10) DEFAULT 'Rp',
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Isi data default jika masih kosong
    $checkKat = $pdo->query("SELECT COUNT(*) FROM `kategori`")->fetchColumn();
    if ($checkKat == 0) {
        $kategoriDefault = [
            // Pemasukan
            ['Penjualan Produk', 'pemasukan', 'Pendapatan langsung dari penjualan produk UMKM'],
            ['Pendapatan Jasa / Layanan', 'pemasukan', 'Pendapatan dari jasa layanan bisnis'],
            ['Pendapatan Lain-lain', 'pemasukan', 'Pendapatan non-operasional atau insidental'],
            ['Modal Usaha Tambahan', 'pemasukan', 'Injeksi kas modal dari pemilik/investor'],
            // Pengeluaran
            ['Bahan Baku & Persediaan', 'pengeluaran', 'Biaya belanja stok barang / bahan produksi'],
            ['Gaji Karyawan', 'pengeluaran', 'Upah dan honor tenaga kerja'],
            ['Listrik, Air & Internet', 'pengeluaran', 'Tagihan utilitas operasional bulanan'],
            ['Sewa Tempat Usaha', 'pengeluaran', 'Biaya sewa ruko / lapak usaha'],
            ['Promosi & Pemasaran', 'pengeluaran', 'Biaya iklan online, brosur, banner'],
            ['Transportasi & Logistik', 'pengeluaran', 'Ongkir bahan, bensin operasional dan kurir'],
            ['Perawatan & Operasional Toko', 'pengeluaran', 'Biaya ATK, kemasan, perbaikan perlengkapan'],
            ['Pengeluaran Lainnya', 'pengeluaran', 'Biaya tak terduga / beban lain-lain']
        ];

        $stmt = $pdo->prepare("INSERT INTO `kategori` (`nama_kategori`, `jenis`, `deskripsi`) VALUES (?, ?, ?)");
        foreach ($kategoriDefault as $kat) {
            $stmt->execute($kat);
        }
    }

    // Isi Pengaturan default jika kosong
    $checkPengaturan = $pdo->query("SELECT COUNT(*) FROM `pengaturan`")->fetchColumn();
    if ($checkPengaturan == 0) {
        $pdo->exec("INSERT INTO `pengaturan` (`nama_bisnis`, `pemilik`, `telepon`, `alamat`, `mata_uang`) 
            VALUES ('UMKM Riski Jaya Abadi', 'Riski', '0812-3456-7890', 'Jl. Bisnis Raya No. 12, Jakarta', 'Rp')");
    }

    // Isi Sample Transaksi awal jika masih kosong
    $checkTrx = $pdo->query("SELECT COUNT(*) FROM `transaksi`")->fetchColumn();
    if ($checkTrx == 0) {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 days'));
        $twoDaysAgo = date('Y-m-d', strtotime('-3 days'));
        $fiveDaysAgo = date('Y-m-d', strtotime('-5 days'));

        $sampleTrx = [
            ['TRX-' . date('Ymd') . '-001', $fiveDaysAgo, 'pemasukan', 1, 4500000, 'Transfer Bank', 'Penjualan paket produk grosir ke pelanggan A', null],
            ['TRX-' . date('Ymd') . '-002', $fiveDaysAgo, 'pengeluaran', 5, 1800000, 'Tunai', 'Belanja bahan baku stok mingguan di supplier', null],
            ['TRX-' . date('Ymd') . '-003', $twoDaysAgo, 'pemasukan', 2, 850000, 'Tunai', 'Jasa instalasi & pendampingan teknis', null],
            ['TRX-' . date('Ymd') . '-004', $twoDaysAgo, 'pengeluaran', 7, 350000, 'Transfer Bank', 'Pembayaran token listrik & internet kantor', null],
            ['TRX-' . date('Ymd') . '-005', $yesterday, 'pengeluaran', 9, 250000, 'Transfer Bank', 'Pasang iklan promosi Instagram Ads', null],
            ['TRX-' . date('Ymd') . '-006', $today, 'pemasukan', 1, 2350000, 'Tunai', 'Penjualan eceran di toko fisik', null]
        ];

        $stmtTrx = $pdo->prepare("INSERT INTO `transaksi` (`kode_transaksi`, `tanggal`, `jenis`, `kategori_id`, `nominal`, `metode_pembayaran`, `keterangan`, `bukti_transaksi`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($sampleTrx as $trx) {
            $stmtTrx->execute($trx);
        }
    }
}

