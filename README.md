# SIKEU-UMKM - Sistem Pencatatan Keuangan & Akuntansi Bisnis UMKM
**Mata Kuliah / Praktikum: Aplikasi Komputer Bisnis**  
**Proyek:** `c:\xampp\htdocs\web_riski`  
**Pengembang:** Riski  

---

## 📌 Tentang Aplikasi
**SIKEU-UMKM** adalah aplikasi komputer bisnis berbasis web yang dirancang khusus untuk mempermudah pelaku Usaha Mikro, Kecil, dan Menengah (UMKM) dalam mencatat transaksi arus kas secara real-time, mengontrol beban operasional, dan menghasilkan laporan keuangan standar bisnis (Laporan Arus Kas & Laporan Laba Rugi) secara akurat dan otomatis.

---

## ✨ Fitur-Fitur Utama

1. **Dashboard Ringkasan Eksekutif (Financial Overview)**:
   - Kartu statistik Total Saldo Kas Berjalan, Pemasukan Bulan Berjalan, Pengeluaran Bulan Berjalan, dan Laba/Rugi Bersih.
   - Grafik interaktif tren arus kas 6 bulan terakhir menggunakan **Chart.js**.
   - Diagram donat distribusi pos beban operasional terbesar bulan ini.
   - Tabel ringkasan 6 transaksi kas terkini.

2. **Pencatatan Transaksi Kas (Cash Flow Management)**:
   - Input transaksi arus kas masuk (pemasukan) dan arus kas keluar (pengeluaran).
   - Penomoran kode transaksi otomatis (`TRX-YYYYMMDD-XXX`).
   - Pemilihan metode pembayaran (Tunai, Transfer Bank, QRIS, Kartu Debit).
   - Fitur unggah bukti struk / kuitansi nota transaksi (.jpg, .png, .pdf).
   - Filter transaksi berdasarkan rentang tanggal, jenis, kategori, serta pencarian kata kunci.
   - Edit dan Hapus transaksi dengan konfirmasi proteksi data.

3. **Manajemen Kategori Keuangan (Chart of Accounts)**:
   - Klasifikasi pos pemasukan (Penjualan, Pendapatan Jasa, Modal, dll.).
   - Klasifikasi pos pengeluaran (Bahan Baku, Gaji Karyawan, Listrik/Air/Internet, Sewa, Iklan/Promosi, dll.).
   - Tambah, edit, dan hapus kategori yang dilengkapi proteksi integritas data transaksi.

4. **Laporan Arus Kas (Buku Kas Umum)**:
   - Perhitungan otomatis Saldo Awal, Total Penerimaan, Total Pengeluaran, dan Saldo Akhir.
   - Tabel mutasi kronologis yang menampilkan saldo kas berjalan per setiap baris transaksi.

5. **Laporan Laba / Rugi (Income Statement)**:
   - Format standar akuntansi bisnis:
     - **I. Pendapatan Usaha (Revenue)**
     - **II. Beban Operasional Usaha (Expenses)**
     - **III. Laba / Rugi Bersih Usaha (Net Profit/Loss)**
   - Perhitungan rasio margin keuntungan (*profit margin percentage*).
   - Pilihan filter periode bulanan maupun tahunan penuh.

6. **Pencetakan & Ekspor Data**:
   - **Cetak Laporan / Simpan ke PDF**: Tampilan print-ready formal dengan kop bisnis dan kolom tanda tangan pembuat laporan serta pemilik usaha.
   - **Export ke Excel / CSV**: Kompatibel langsung dibuka di Microsoft Excel dengan format UTF-8 BOM.

7. **Profil Usaha & Fitur Reset Demo**:
   - Pengaturan nama usaha, pemilik, alamat, dan nomor telepon.
   - Tombol satu-klik untuk reset data simulasi (sangat berguna untuk demo presentasi tugas kuliah).

8. **Otomatisasi Database (Zero Configuration)**:
   - Database `db_keuangan_riski` dan seluruh tabelnya otomatis dibuatkan saat aplikasi pertama kali diakses lewat browser.

---

## 🛠️ Teknologi & Stack
- **Bahasa Pemrograman**: PHP Native (PHP 8.x) dengan ekstensi PDO (PHP Data Objects)
- **Database**: MySQL / MariaDB (XAMPP)
- **Frontend Framework**: Bootstrap 5.3.3 & Bootstrap Icons
- **Visualisasi Data**: Chart.js
- **Arsitektur**: Modular (Pemisahan `config`, `includes`, `assets`, dan modul proses)

---

## 🚀 Cara Menjalankan Aplikasi di XAMPP

1. **Jalankan XAMPP**:
   - Buka **XAMPP Control Panel**.
   - Klik tombol **Start** pada modul **Apache** dan **MySQL**.

2. **Buka Melalui Browser**:
   - Buka Google Chrome, Microsoft Edge, atau browser favorit Anda.
   - Ketikkan alamat:
     ```
     http://localhost/web_riski
     ```
   - Aplikasi akan otomatis menginisialisasi database `db_keuangan_riski`, tabel-tabel, pos kategori standar, dan data contoh transaksi awal.

3. **(Opsional) Import Database Manual Melalui phpMyAdmin**:
   - Jika ingin melakukan import manual:
     1. Buka `http://localhost/phpmyadmin/`
     2. Buat database baru bernama `db_keuangan_riski`
     3. Klik tab **Import**, pilih berkas `c:\xampp\htdocs\web_riski\database\schema.sql`
     4. Klik **Go / Kirim**.

---

## 📁 Struktur Direktori Proyek

```
c:\xampp\htdocs\web_riski\
├── assets/
│   ├── css/
│   │   └── style.css            # Desain UI modern, responsif & print style
│   └── js/
│       └── app.js              # Script format mata uang & konfirmasi hapus
├── config/
│   ├── database.php            # Koneksi PDO & auto-installer tabel
│   └── helper.php              # Fungsi rupiah, tanggal Indonesia, sanitasi
├── database/
│   └── schema.sql              # Skrip SQL struktur tabel & data seeder
├── includes/
│   ├── header.php              # Header & navigasi atas
│   ├── sidebar.php             # Menu samping
│   └── footer.php              # Penutup & script library
├── uploads/
│   └── .gitkeep                # Direktori berkas bukti/nota transaksi
├── index.php                   # Dashboard statistik & grafik keuangan
├── transaksi.php               # Data transaksi kas + filter & pencarian
├── transaksi_tambah.php        # Form input pencatatan transaksi kas
├── transaksi_edit.php          # Form ubah data transaksi
├── transaksi_hapus.php         # Proses hapus data & berkas lampiran
├── kategori.php                # Manajemen pos kategori akun keuangan
├── laporan_arus_kas.php        # Laporan Buku Kas Umum & saldo berjalan
├── laporan_laba_rugi.php       # Laporan Laba Rugi Operasional UMKM
├── cetak_laporan.php           # Template cetak formal laporan & PDF
├── export_excel.php            # Export data transaksi & laporan ke CSV/Excel
├── pengaturan.php              # Pengaturan profil UMKM & reset demo
└── README.md                   # Dokumentasi lengkap aplikasi
```

