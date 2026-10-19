# Sistem Informasi Manajemen Parkir (ParkirApp)

Aplikasi web manajemen parkir terpadu berbasis **PHP 8 & MySQL** yang dibangun sesuai struktur database **drawSQL**.

---

## 🗄️ Struktur Database Sesuai Diagram drawSQL

| Nama Tabel | Deskripsi | Kolom Utama |
| :--- | :--- | :--- |
| **`tb_user`** | Pengguna sistem (Admin, Petugas, Owner) | `id_user`, `nama_lengkap`, `username`, `password`, `role`, `status_aktif` |
| **`tb_tarif`** | Pengaturan tarif per jam berdasarkan jenis | `id_tarif`, `jenis_kendaraan`, `tarif_per_jam` |
| **`tb_area_parkir`** | Monitoring kapasitas dan ketersediaan slot | `id_area`, `nama_area`, `kapasitas`, `terisi` |
| **`tb_kendaraan`** | Data master kendaraan & plat nomor | `id_kendaraan`, `plat_nomor`, `jenis_kendaraan`, `warna`, `pemilik`, `id_user` |
| **`tb_transaksi`** | Transaksi parkir masuk, keluar, dan durasi | `id_parkir`, `id_kendaraan`, `waktu_masuk`, `waktu_keluar`, `id_tarif`, `durasi_jam`, `biaya_total`, `status`, `id_user`, `id_area` |
| **`tb_log_aktivitas`** | Riwayat audit trail aktivitas pengguna | `id_log`, `id_user`, `aktivitas`, `waktu_aktivitas` |

---

## 🔑 Akun Demo & Hak Akses

| Peran (Role) | Username | Password | Hak Akses |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin1` | `admin123` | Akses penuh (Dashboard, Transaksi, Master Kendaraan, Area, Tarif, Pengguna, Laporan, Log) |
| **Petugas** | `petugas1` | `petugas123` | Operasional Parkir (Check-In Masuk, Check-Out Keluar, Cetak Karcis/Struk, Data Kendaraan) |
| **Owner** | `owner1` | `owner123` | Monitoring & Finansial (Dashboard Tren, Ketersediaan Area, Laporan Rekap, Log Audit) |

---

## 🚀 Cara Menjalankan Aplikasi

1. Pastikan **Apache** dan **MySQL** aktif pada XAMPP.
2. Buka browser dan akses alamat:
   ```
   http://localhost/app-parkir/
   ```
3. Masuk menggunakan salah satu akun di atas (tersedia tombol cepat isi akun di halaman login).
4. File schema & database cadangan tersedia di: `c:/xamppp/htdocs/app-parkir/database.sql`.

---

## ✨ Fitur Unggulan

- **Check-In Cepat (Kendaraan Masuk)**: Autocomplete nomor plat, deteksi otomatis kendaraan terdaftar, validasi anti double check-in, dan otomatis menambah slot terisi area.
- **Cetak Karcis Masuk**: Format struk thermal dengan barcode Code 128 dan nomor tiket unik.
- **Check-Out & Pembayaran (Kendaraan Keluar)**: Pencarian cepat tiket/plat, kalkulasi durasi parkir otomatis (pembulatan jam ke atas), hitung biaya tarif, input uang bayar & hitung kembalian instan.
- **Cetak Struk Pembayaran**: Kwitansi rincian biaya resmi siap cetak thermal 58mm/80mm maupun printer standar.
- **Monitoring Slot Real-Time**: Status bar ketersediaan kapasitas area parkir (hijau, kuning, merah) dilengkapi fitur sinkronisasi slot.
- **Manajemen Lengkap**: CRUD Kendaraan, Area Parkir, Tarif Parkir, dan Pengguna Sistem.
- **Laporan & Export**: Filter tanggal, status, jenis kendaraan, print view format laporan resmi, dan ekspor data ke Excel (.csv).
- **Audit Trail (Log Aktivitas)**: Mencatat setiap aktivitas penting pengguna secara kronologis.

