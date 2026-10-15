# Verifikasi backend tahap 1 — 15 Oktober 2026

Hasil lokal pada PHP 8.4.26 / Laravel 13.36.0:

- Migrasi SQLite dan DemoSeeder berhasil; seeder ulang tidak menggandakan akun/kepesertaan.
- PHPUnit: **17 tes, 99 assertion lulus**. Termasuk constraint unik database, retry final, perhitungan server, izin peran dan KPwBI/institusi, kepesertaan dicabut, snapshot historis, instrumen terbit, jadwal, rate limit dan logout.
- Composer validate --strict, check-platform-reqs dan pemeriksaan advisory saat pembaruan lockfile berhasil.
- Pint --test lulus; Blade view:cache berhasil.
- Vite production build berhasil, dengan npm dependency dibangun pada C: karena drive Google menolak beberapa operasi ekstraksi.
- Chromium browser: login → kegiatan → 30 radio L1 tanpa pilihan awal → kirim lima nilai → konfirmasi → reload → logout; agregat BI/mitra terbaca tanpa feedback individu.
- Browser HTTP menolak CSRF invalid (419) dan payload nilai tidak sah (422).
- Form L1 pada 375, 768 dan 1440 px tidak memiliki overflow horizontal; target radio mobile minimal 44 px. Screenshot desktop/mobile diperiksa. Ini hanya cakupan layar/alur tahap 1, bukan audit visual semua layar.
- Smoke test prototype 20 rute dan ekspor PDF/XLSX lulus. Tes ekspor menunggu hasil asynchronous dengan batas waktu, menggantikan jeda tetap 100 ms.

Artefak screenshot berada di tests/outputs/backend; hasil ekspor prototype di tests/outputs. Semua artefak dan database lokal diabaikan Git. Browser QA meninggalkan satu respons **sintetis** lima nilai 5, rata-rata 5,00/6, untuk akun peserta contoh.

Batas: belum menjalankan tes pada MariaDB aktual atau Hostinger; locking/concurrent retries pada MariaDB, beban 200–1.500 pengguna, Safari/Firefox, pembaca layar dan backup belum diverifikasi. Workflow CI tersedia tetapi belum dijalankan di GitHub dalam sesi ini. L2, impor, laporan server, CRUD dan email masih tahap berikutnya. Cara mengulang dan asumsi ada di [BACKEND.md](BACKEND.md).

---

# Verifikasi

## Dilakukan

- Syntax JavaScript untuk empat file aplikasi.
- Render DOM seluruh 20 rute: heading, title, ID unik, label input, aset gambar, ikon terpasang, serta tautan internal valid.
- Validasi profil dan penyimpanan contoh.
- L1: 30 radio, lima kelompok dengan enam opsi, tidak ada pilihan otomatis, ringkasan error, pengiriman lokal.
- Lima pertanyaan pretest dan post-test, draf, validasi isian kosong, konfirmasi.
- Pembatasan halaman PDF ke rentang 1–11.
- Filter dashboard dan kondisi tanpa data.
- Empat langkah pembuatan kegiatan, narasumber baru, versi instrumen.
- Parser CSV termasuk pemisah di dalam tanda kutip.
- Cakupan navigasi mitra, pencarian panduan, konfigurasi contoh.
- Katalog 20 layar, reset lokal, dan halaman tidak ditemukan.
- Ekspor XLSX dibuka kembali dengan openpyxl, nilai rata-rata dan jumlah respons diperiksa.
- Ekspor PDF dibuka kembali dan dirender untuk pemeriksaan teks/tata letak.
- Materi PDF terkompresi tetap 11 halaman.

## Batas verifikasi

Pemeriksaan DOM menggunakan jsdom, bukan browser dengan layout engine. Sarana browser QA yang diizinkan tidak tersedia pada sesi ini; tidak ada klaim pixel-perfect, screenshot hasil implementasi, pengukuran overflow pada perangkat nyata, atau uji pembaca layar aktual.

CSS memuat aturan desktop, tablet, dan mobile, tetapi sebelum produksi lakukan pemeriksaan visual pada lebar 390, 768, 1440, dan 1920 px serta uji Chrome, Safari, dan Firefox. Perilaku pembaca PDF bawaan tergantung browser; tautan buka/unduh selalu disediakan.

## Batas fungsional

Seluruh alur akun, role, dan data perubahan merupakan demo front-end lokal. Tidak ada backend, login asli, otorisasi server, sinkronisasi perangkat, email undangan, atau penilaian L2. Data pribadi dari workbook sumber tidak disertakan.
