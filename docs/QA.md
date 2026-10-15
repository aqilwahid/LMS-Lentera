# Verifikasi master dan kepesertaan — 15 Oktober 2026

- Migrasi master narasumber, status/revision institusi, speaker_id sesi, serta pemetaan/revision kepesertaan berhasil pada SQLite lokal tanpa menghapus data sebelumnya.
- **47 tes PHPUnit, 424 assertion lulus.** Tambahan tes mencakup master sesuai KPwBI, nama duplikat, formulir lama, narasumber yang ditentukan server, snapshot setelah master diubah, master nonaktif, pemetaan yang memengaruhi agregat mitra, data asli yang tetap utuh, status, privasi halaman, pembatasan peran, dan rollback audit.
- Chromium berhasil menguji master → sesi dengan narasumber dari master → pendaftaran dan L1 tanpa akun → pencarian/detail peserta → pemetaan dan penonaktifan → akses peserta ditolak → pengaktifan → L1 sebelumnya tetap tampil.
- Detail peserta diperiksa pada 375, 768 dan 1440 px tanpa overflow horizontal. Screenshot mobile diperiksa. Tidak ada error JavaScript pada alur tersebut.
- Halaman daftar/detail peserta memakai Cache-Control private, no-store; mitra tidak mendapat data individual. Audit perubahan kepesertaan tidak menyalin identitas peserta.
- Pint --test, Composer validate --strict dan kompilasi Blade lulus.
- Browser test: tests/masters-browser.cjs. Artefak di tests/outputs/masters diabaikan Git; database demo bertambah data sintetis pada setiap run.

Batas: MariaDB/Hostinger, collation dan konkurensi nyata, kapasitas 1.500 pengguna, verifikasi identitas, pemulihan akses lintas browser dan koreksi identitas/final L1 belum diuji/disediakan. Detail aturan ada di [MASTERS_PARTICIPANTS.md](MASTERS_PARTICIPANTS.md). Catatan tahap sebelumnya di bawah adalah riwayat verifikasi.

---

# Verifikasi pengelolaan kegiatan/sesi dan QR — 15 Oktober 2026

- Migrasi revision kegiatan/sesi dan tabel audit_entries berhasil pada database SQLite lokal, tanpa menghapus data awal.
- **36 tes PHPUnit, 313 assertion lulus.** Tambahan tes mencakup pembuatan kegiatan/sesi, pembatasan role/KPwBI, penolakan payload yang mengganti cakupan, jadwal tidak sah, instrumen draft/tidak lengkap, konteks terkunci setelah pendaftaran, konflik versi formulir, siklus QR, serta rollback jika audit gagal.
- Chromium berhasil menjalankan: pengelola membuat kegiatan/sesi → mengaktifkan QR → peserta mendaftar dan mengirim L1 tanpa akun → pengelola mengganti QR → tautan lama ditolak → peserta pada browser yang sama tetap melihat respons melalui tautan baru → nonaktifkan/aktifkan → respons tetap ada → audit terbaca.
- Form sesi pada 375, 768 dan 1440 px tidak memiliki overflow horizontal. Screenshot mobile diperiksa. Tidak ada error JavaScript pada alur browser ini.
- Kompilasi Blade, Pint --test dan Composer validate --strict lulus.
- Browser test baru: tests/management-browser.cjs; hasil screenshot di tests/outputs/management. Tes menambah data dan audit sintetis pada database demo lokal, bukan akun peserta.

Batas: belum menguji MariaDB/Hostinger, race condition nyata pada database produksi, browser selain Chromium, retensi/arsip, dan beban 1.500 pengguna. Audit tahap ini mencakup perubahan kegiatan, sesi dan QR, belum semua operasi aplikasi. Panduan ada di [MANAGEMENT.md](MANAGEMENT.md). Hasil tahap sebelumnya di bawah merupakan riwayat verifikasi.

---

# Verifikasi alur QR tanpa akun — 15 Oktober 2026

Alur ini mengikuti koreksi kebutuhan pengguna: peserta mengisi formulir melalui QR kelas tanpa login; autentikasi hanya untuk BI/mitra.

- Migrasi tambahan berhasil pada database SQLite lokal, mempertahankan data awal dan menambah identitas/akses peserta tamu serta token/jadwal kelas.
- **27 tes PHPUnit, 213 assertion lulus**: pendaftaran tanpa akun, delapan field identitas, narasumber server, nomor identitas berawalan nol, instansi belum dipetakan, retry pendaftaran/L1, browser dan kelas lain, kepesertaan dicabut, jadwal/tautan ditutup, login participant ditolak, QR sesuai peran/KPwBI, serta regresi L1 fondasi awal.
- Pembatasan request formulir diuji per sesi browser, termasuk peserta berbeda pada IP yang sama.
- Chromium: pengelola membuka QR → browser peserta terpisah mengisi formulir → membuka kelas → mengisi 30 radio L1 → konfirmasi tersimpan → reload/retry → browser lain tetap harus mengisi formulir dan tidak melihat respons pertama.
- Form pendaftaran diperiksa pada 375, 768 dan 1440 px tanpa overflow horizontal. Target radio L1 mobile minimal 44 px. Screenshot diperiksa.
- Browser menguji CSRF pendaftaran (419), validasi L1 tidak sah (422), dan keluaran QR SVG (200).
- Pint, Composer validate --strict, check-platform-reqs, kompilasi Blade dan Vite production build lulus.

Browser test kini membuat kepesertaan dan respons sintetis baru pada setiap run tanpa membuat akun peserta. Screenshot berada di tests/outputs/backend, diabaikan Git. Batas: belum menguji pemindaian QR memakai kamera perangkat fisik, MariaDB/Hostinger, konkurensi atau kapasitas 1.500 pengguna. Email/identitas belum diverifikasi dan pemulihan akses lintas perangkat belum tersedia; lihat [ACCESS_FLOW.md](ACCESS_FLOW.md).

Hasil tahap pertama di bawah merupakan catatan historis sebelum perubahan metode masuk peserta.

---

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
