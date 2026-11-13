-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 10 Nov 2026 pada 07.10
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_keuangan_riski`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `jenis` enum('pemasukan','pengeluaran') NOT NULL,
  `deskripsi` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`, `jenis`, `deskripsi`, `created_at`) VALUES
(1, 'Penjualan Produk', 'pemasukan', 'Pendapatan langsung dari penjualan produk UMKM', '2026-11-10 05:43:33'),
(2, 'Pendapatan Jasa / Layanan', 'pemasukan', 'Pendapatan dari jasa layanan bisnis', '2026-11-10 05:43:33'),
(3, 'Pendapatan Lain-lain', 'pemasukan', 'Pendapatan non-operasional atau insidental', '2026-11-10 05:43:33'),
(4, 'Modal Usaha Tambahan', 'pemasukan', 'Injeksi kas modal dari pemilik/investor', '2026-11-10 05:43:33'),
(5, 'Bahan Baku & Persediaan', 'pengeluaran', 'Biaya belanja stok barang / bahan produksi', '2026-11-10 05:43:33'),
(6, 'Gaji Karyawan', 'pengeluaran', 'Upah dan honor tenaga kerja', '2026-11-10 05:43:33'),
(7, 'Listrik, Air & Internet', 'pengeluaran', 'Tagihan utilitas operasional bulanan', '2026-11-10 05:43:33'),
(9, 'Promosi & Pemasaran', 'pengeluaran', 'Biaya iklan online, brosur, banner', '2026-11-10 05:43:33'),
(10, 'Transportasi & Logistik', 'pengeluaran', 'Ongkir bahan, bensin operasional dan kurir', '2026-11-10 05:43:33'),
(11, 'Perawatan & Operasional Toko', 'pengeluaran', 'Biaya ATK, kemasan, perbaikan perlengkapan', '2026-11-10 05:43:33'),
(12, 'Pengeluaran Lainnya', 'pengeluaran', 'Biaya tak terduga / beban lain-lain', '2026-11-10 05:43:33');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL,
  `nama_bisnis` varchar(150) NOT NULL,
  `pemilik` varchar(100) NOT NULL,
  `telepon` varchar(30) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `mata_uang` varchar(10) DEFAULT 'Rp',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `nama_bisnis`, `pemilik`, `telepon`, `alamat`, `mata_uang`, `updated_at`) VALUES
(1, 'TOKO ASMARUDIN', 'ASMARUDIN', '0858-1400-0634', 'Jl. Lingkar Selatan Kp. Sengkol RT05 RW02 Kec. Setu Kel. Muncul Kota Tangerang Selatan Banten', 'Rp', '2026-11-10 05:50:38');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `kode_transaksi` varchar(50) NOT NULL,
  `tanggal` date NOT NULL,
  `jenis` enum('pemasukan','pengeluaran') NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `nominal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `metode_pembayaran` varchar(50) DEFAULT 'Tunai',
  `keterangan` text DEFAULT NULL,
  `bukti_transaksi` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi`
--

INSERT INTO `transaksi` (`id`, `kode_transaksi`, `tanggal`, `jenis`, `kategori_id`, `nominal`, `metode_pembayaran`, `keterangan`, `bukti_transaksi`, `created_at`, `updated_at`) VALUES
(1, 'TRX-20261110-001', '2026-11-05', 'pemasukan', 1, 4500000.00, 'Transfer Bank', 'Penjualan paket produk grosir ke pelanggan A', NULL, '2026-11-10 05:43:33', '2026-11-10 05:43:33'),
(2, 'TRX-20261110-002', '2026-11-05', 'pengeluaran', 5, 1800000.00, 'Tunai', 'Belanja bahan baku stok mingguan di supplier', NULL, '2026-11-10 05:43:33', '2026-11-10 05:43:33'),
(3, 'TRX-20261110-003', '2026-11-07', 'pemasukan', 2, 850000.00, 'Tunai', 'Jasa instalasi & pendampingan teknis', NULL, '2026-11-10 05:43:33', '2026-11-10 05:43:33'),
(4, 'TRX-20261110-004', '2026-11-07', 'pengeluaran', 7, 350000.00, 'Transfer Bank', 'Pembayaran token listrik & internet kantor', NULL, '2026-11-10 05:43:33', '2026-11-10 05:43:33'),
(6, 'TRX-20261110-006', '2026-11-10', 'pemasukan', 1, 2350000.00, 'Tunai', 'Penjualan eceran di toko fisik', NULL, '2026-11-10 05:43:33', '2026-11-10 05:43:33');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_transaksi` (`kode_transaksi`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
