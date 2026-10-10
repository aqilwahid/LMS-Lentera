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
