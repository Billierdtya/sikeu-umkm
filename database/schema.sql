-- ==========================================================
-- SKEMA DATABASE APLIKASI KOMPUTER BISNIS (SIKEU-UMKM)
-- Proyek: c:\xampp\htdocs\web_riski
-- Pengembang: Riski
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `db_keuangan_riski` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_keuangan_riski`;

-- 1. TABEL KATEGORI KEUANGAN
CREATE TABLE IF NOT EXISTS `kategori` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kategori` VARCHAR(100) NOT NULL,
    `jenis` ENUM('pemasukan', 'pengeluaran') NOT NULL,
    `deskripsi` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. TABEL TRANSAKSI KAS
CREATE TABLE IF NOT EXISTS `transaksi` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. TABEL PENGATURAN BISNIS / UMKM
CREATE TABLE IF NOT EXISTS `pengaturan` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_bisnis` VARCHAR(150) NOT NULL,
    `pemilik` VARCHAR(100) NOT NULL,
    `telepon` VARCHAR(30) NULL,
    `alamat` TEXT NULL,
    `mata_uang` VARCHAR(10) DEFAULT 'Rp',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- DATA AWAL (SEEDER)
-- ==========================================================

-- Data Kategori Default
INSERT INTO `kategori` (`id`, `nama_kategori`, `jenis`, `deskripsi`) VALUES
(1, 'Penjualan Produk', 'pemasukan', 'Pendapatan langsung dari penjualan produk UMKM'),
(2, 'Pendapatan Jasa / Layanan', 'pemasukan', 'Pendapatan dari jasa layanan bisnis'),
(3, 'Pendapatan Lain-lain', 'pemasukan', 'Pendapatan non-operasional atau insidental'),
(4, 'Modal Usaha Tambahan', 'pemasukan', 'Injeksi kas modal dari pemilik/investor'),
(5, 'Bahan Baku & Persediaan', 'pengeluaran', 'Biaya belanja stok barang / bahan produksi'),
(6, 'Gaji Karyawan', 'pengeluaran', 'Upah dan honor tenaga kerja'),
(7, 'Listrik, Air & Internet', 'pengeluaran', 'Tagihan utilitas operasional bulanan'),
(8, 'Sewa Tempat Usaha', 'pengeluaran', 'Biaya sewa ruko / lapak usaha'),
(9, 'Promosi & Pemasaran', 'pengeluaran', 'Biaya iklan online, brosur, banner'),
(10, 'Transportasi & Logistik', 'pengeluaran', 'Ongkir bahan, bensin operasional dan kurir'),
(11, 'Perawatan & Operasional Toko', 'pengeluaran', 'Biaya ATK, kemasan, perbaikan perlengkapan'),
(12, 'Pengeluaran Lainnya', 'pengeluaran', 'Biaya tak terduga / beban lain-lain');

-- Data Profil Bisnis Default
INSERT INTO `pengaturan` (`id`, `nama_bisnis`, `pemilik`, `telepon`, `alamat`, `mata_uang`) VALUES
(1, 'UMKM Riski Jaya Abadi', 'Riski', '0812-3456-7890', 'Jl. Bisnis Raya No. 12, Jakarta', 'Rp');

-- Contoh Transaksi Awal
INSERT INTO `transaksi` (`kode_transaksi`, `tanggal`, `jenis`, `kategori_id`, `nominal`, `metode_pembayaran`, `keterangan`) VALUES
('TRX-20261105-001', '2026-11-05', 'pemasukan', 1, 4500000.00, 'Transfer Bank', 'Penjualan paket produk grosir ke pelanggan A'),
('TRX-20261105-002', '2026-11-05', 'pengeluaran', 5, 1800000.00, 'Tunai', 'Belanja bahan baku stok mingguan di supplier'),
('TRX-20261107-003', '2026-11-07', 'pemasukan', 2, 850000.00, 'Tunai', 'Jasa instalasi & pendampingan teknis'),
('TRX-20261107-004', '2026-11-07', 'pengeluaran', 7, 350000.00, 'Transfer Bank', 'Pembayaran token listrik & internet kantor'),
('TRX-20261109-005', '2026-11-09', 'pengeluaran', 9, 250000.00, 'Transfer Bank', 'Pasang iklan promosi Instagram Ads'),
('TRX-20261110-006', '2026-11-10', 'pemasukan', 1, 2350000.00, 'Tunai', 'Penjualan eceran di toko fisik');

