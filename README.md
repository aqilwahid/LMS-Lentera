# LENTERA Management System

LENTERA mengelola kegiatan literasi kebanksentralan bagi peserta eksternal Bank Indonesia, pengelola BI, dan mitra perguruan tinggi.

**Status per 17 Oktober 2026:** fondasi backend, pengelolaan kegiatan/sesi, master institusi/narasumber, kepesertaan, materi PDF privat, instrumen L1/L2 berversi, pretest/post-test, analisis L2 BI/mitra, serta pengelolaan akun pengelola/mitra dan pemulihan kata sandi mandiri sudah dapat dijalankan dan diperiksa secara lokal. Alur peserta membuka QR/tautan kelas → mengisi formulir tanpa akun → membuka kelas → mengisi L1 → menerima konfirmasi telah terhubung ke database. Login hanya untuk pengelola BI dan mitra. Prototype 20 layar tetap tersedia sebagai referensi visual V2; alur login peserta pada prototype lama belum diselaraskan dengan alur QR backend. Aplikasi belum dinyatakan siap produksi.

## Sudah dikerjakan

| Area | Hasil implementasi |
| --- | --- |
| Fondasi | Laravel 13, target PHP 8.4, Blade, Alpine.js, Vite, serta lockfile Composer/npm. |
| Tampilan V2 | Layout, navigasi, komponen validasi, login, beranda peserta, detail kegiatan/sesi, form L1, konfirmasi, dan ringkasan agregat BI/mitra memakai CSS, font, logo, serta aset V2 yang sudah ada. |
| Autentikasi pengelola/mitra | Login email/kata sandi, password hashing, session server, rotasi session ID, logout, CSRF, pembatasan percobaan login, dan penolakan akun nonaktif. |
| Akses peserta dan pengelola | Peserta tanpa akun hanya mengakses kelas dan respons miliknya melalui sesi browser. Pendaftaran tidak membuat akun pengguna. BI dibatasi KPwBI akun. Mitra hanya menerima agregat institusinya tanpa identitas, jawaban, atau masukan individu. Izin diperiksa server. |
| Database | Migrasi untuk KPwBI, institusi, narasumber, pengguna/profil dasar, kegiatan, sesi, kepesertaan/pemetaan, versi instrumen L1/L2, respons L1, assignment/attempt L2, dan audit; tabel session, cache, dan antrean database juga tersedia. |
| Data contoh | Seeder sintetis untuk tiga institusi, tiga peran, satu kegiatan/sesi, satu kepesertaan, dan instrumen L1. Seeder hanya diizinkan pada lingkungan local/testing dengan flag demo aktif. |
| Pendaftaran melalui QR | Tautan acak per sesi, jadwal pendaftaran, formulir identitas tanpa login, serta tampilan/unduh QR SVG bagi BI sesuai KPwBI. |
| Evaluasi L1 | Lima butir skala 1–6, masukan opsional, validasi/jadwal/versi diperiksa server, dan rata-rata dihitung dari lima jawaban yang sah. |
| Integritas respons | Transaksi, penguncian, dan constraint unik kepesertaan+sesi mencegah respons ganda. Retry final mempertahankan respons pertama dan mengembalikan konfirmasi. |
| Riwayat | Kepesertaan menyimpan konteks institusi/kategori historis. Respons menyimpan snapshot instrumen, versi, skala, dan narasumber. Model membatasi perubahan konteks historis dan respons final. |
| Ringkasan | Agregat L1 dari database sesuai cakupan BI/mitra, dipisahkan per sesi, instrumen, dan institusi historis, dengan jumlah respons serta periode. |
| Deployment/CI | Workflow Pages dibatasi ke allowlist prototype dan dijalankan manual. Workflow pemeriksaan backend tersedia untuk build, tes, dan format kode; belum melakukan deployment Laravel. |
| Pengelolaan BI | Buat/ubah kegiatan dan sesi, pilih instrumen L1 terbit, atur jadwal, serta aktifkan/nonaktifkan/ganti QR. Perubahan dibatasi KPwBI, memakai pemeriksaan versi formulir, dan dicatat pada riwayat audit. |
| Master dan kepesertaan BI | Master institusi/narasumber sesuai KPwBI; pilihan narasumber pada sesi; daftar/detail dan pencarian peserta; pemetaan instansi terpisah dari sumber; penonaktifan/pengaktifan kepesertaan; audit. |
| Materi PDF privat | Unggah per sesi, riwayat versi, terbit/sembunyikan, baca/unduh sesuai kepesertaan aktif, retry tanpa versi ganda, serta audit. File berada di luar public. |
| Instrumen L1 | Seri per KPwBI, draf lima butir, pratinjau/penerbitan, versi terbit terkunci, versi baru, retry dan audit. Pemilihan versi pada sesi diperiksa server. |
| Evaluasi L2 | Soal pilihan ganda dan kunci berversi per KPwBI, assignment/jadwal per sesi, pre/post tanpa login, draf manual server, pengiriman final, skor server, publikasi skor dan ringkasan pasangan lengkap. |
| Analisis L2 | Filter tanggal/kegiatan/sesi/versi/institusi, pasangan sah dan rata-rata berbobot jumlah pasangan. BI dibatasi KPwBI; mitra hanya agregat institusinya dari hasil yang sudah dibuka. |
| Antrean akses | Gerbang sebelum Laravel/database membatasi request aktif dan laju masuk. Ruang tunggu menampilkan posisi, pesan bergantian dan tic-tac-toe lokal. Isian formulir tetap di tab; pembatas dapat diaktifkan dan diatur melalui Artisan. |
| Impor historis | Pengelola BI mengunggah CSV respons L1 lama ke sebuah sesi: staging dan pratinjau, baris ringkasan dikecualikan, pemetaan alias institusi manual, deteksi duplikat (NIM + email), penolakan berkas identik, serta penerapan satu transaksi dan idempoten. Lihat [IMPORT.md](docs/IMPORT.md). |
| Pengelolaan akun | BI membuat/mengubah/menonaktifkan akun pengelola BI dan mitra dalam cakupan KPwBI; kata sandi ditetapkan pemilik lewat tautan email (60 menit). Lupa kata sandi untuk pengelola/mitra aktif tanpa membocorkan keberadaan email; sesi lama dihapus setelah reset atau penonaktifan; semua dicatat pada riwayat. Pengiriman email memerlukan konfigurasi MAIL_* produksi. |
| Ekspor dan laporan awal | Ekspor CSV kompatibel Excel (titik koma, desimal koma, UTF-8 BOM, netralisasi formula spreadsheet) untuk ringkasan L1 dan analisis L2 sesuai cakupan akun; tombol cetak/simpan PDF browser. |
| Dokumentasi | Keputusan stack, cara menjalankan, asumsi bisnis, pemetaan layar backend, hasil QA, dan batas implementasi telah ditulis. |

**Alur peserta yang ditetapkan:** peserta tidak login atau membuat akun. Pengelola membagikan QR kelas, lalu peserta mengisi Nama Lengkap; Instansi/Perguruan Tinggi; NIP/NIM/NPM; Jurusan/Program Studi; Fakultas; No. HP; Kategori; Nama Narasumber; dan Email. Nama narasumber berasal dari sesi kelas dan tidak dapat diubah peserta.

**Aturan sementara:** satu peran per akun pengelola/mitra, satu narasumber dan satu respons L1 final per kepesertaan per sesi. Sesi browser menyimpan akses peserta; email/NIP belum diverifikasi dan tidak digunakan untuk membuka respons peserta lain. Redaksi L1 memakai salinan kerja DESIGN V2; redaksi resmi dan aturan multi-narasumber belum final. Detail ada di [docs/BACKEND.md](docs/BACKEND.md).

## Belum dikerjakan

| Area | Pekerjaan berikutnya |
| --- | --- |
| Akun dan profil | koreksi identitas peserta yang diaudit, serta pemulihan akses peserta lintas perangkat. |
| Pengelolaan | Pengaturan akun/KPwBI, persetujuan/distribusi instrumen lintas kantor, koreksi identitas/jawaban final, penghapusan/arsip dan aturan retensi. Instrumen L1/L2 per KPwBI sudah tersedia. |
| Materi dan panduan | Finalisasi panduan awam menjadi PDF/halaman LMS, pemindai konten PDF, retensi/penghapusan dan pembagian materi mitra. Materi PDF kelas privat beserta versi dan izin sudah tersedia; aset PDF prototype tidak otomatis diimpor. |
| L1 lanjutan | Multi-narasumber, aturan kewajiban pengisian resmi, draf server/autosave, dan mekanisme koreksi administratif yang dapat diaudit. |
| L2 lanjutan | Autosave, navigasi satu soal, jenis soal/bobot lain, retake/timer/nilai lulus, pembahasan, koreksi final dan kebijakan resmi. Pilihan ganda, draf manual, skor server dan publikasi sudah tersedia. |
| Impor historis lanjutan | Pembacaan `.xlsx` langsung (PhpSpreadsheet), impor L2, pembatalan batch, dan rekonsiliasi terhadap total sumber. Impor CSV L1 sudah tersedia. |
| Analisis/laporan | Filter periode L1, dashboard/grafik tahunan, analisis butir L2, ekspor XLSX/PDF berformat resmi dari server, dan arsip laporan. Filter/agregat L2 BI/mitra sudah tersedia, beserta ekspor CSV (dibuka di Excel) untuk ringkasan L1 dan analisis L2 sesuai cakupan, serta cetak/simpan PDF dari browser. |
| Pekerjaan latar/email | Job bertahap, penjadwalan cron yang terverifikasi, anti-overlap, SMTP/API transaksional, dan pemantauan kegagalan. Tabel antrean sudah ada, job aplikasi belum tersedia. |
| Integrasi | SSO BI dan ERP-HRIS menunggu spesifikasi serta akses nyata. |
| Produksi | Verifikasi MariaDB/PHP/ekstensi/cron akun Hostinger, deployment dengan document root public, backup/pemulihan, dan konfigurasi produksi. |
| Kapasitas/QA lanjutan | Pengujian konkurensi pada MariaDB, uji beban 200–1.500 pengguna, Safari/Firefox, pembaca layar, dan audit visual seluruh layar backend. |

Fitur pada prototype seperti wizard kegiatan, tes L2, impor CSV, dan ekspor PDF/XLSX masih menggunakan data browser. Keberadaannya di prototype tidak berarti backend fitur tersebut sudah tersedia. Data localStorage tidak otomatis dimigrasikan ke database.

## Menjalankan backend dari checkout baru

Diperlukan PHP 8.4, Composer 2, Node 22.12+ dan ekstensi PHP yang dijelaskan di [panduan backend](docs/BACKEND.md). Jalankan dari root repository, bukan folder assets.

Contoh untuk PowerShell dan database SQLite lokal:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File database/database.sqlite
php artisan migrate
npm ci --ignore-scripts
npm run assets:sync
npm run build
```

Untuk mencoba data sintetis, ubah `LENTERA_DEMO_ENABLED=true` di `.env`, lalu:

```powershell
php artisan db:seed --class=DemoSeeder
php artisan serve --host=127.0.0.1 --port=8000 --no-reload
```

Buka **http://127.0.0.1:8000/**. Untuk memperoleh QR contoh, masuk pengelola di **http://127.0.0.1:8000/masuk**, buka **QR dan tautan** pada ringkasan, lalu buka tautan peserta pada halaman tersebut. Peserta mengisi formulir tanpa login. QR localhost hanya dapat dipakai pada komputer yang menjalankan server; gunakan domain yang dapat diakses peserta saat deployment.

| Peran contoh | Email | Kata sandi |
| --- | --- | --- |
| Pengelola BI | pengelola@example.test | Lentera-Demo-2026! |
| Mitra | mitra@example.test | Lentera-Demo-2026! |

Akun tersebut hanya untuk demo lokal pengelola/mitra. Akun peserta contoh dari implementasi awal hanya dipertahankan sebagai fixture regresi; login peran participant sekarang ditolak. Seeder default produksi kosong; database lokal, .env, dependency terpasang, hasil build, dan artefak QA tidak masuk Git. Checkout baru harus melakukan instalasi/build sendiri.

Pada mesin Windows yang sudah disiapkan dalam sesi pengembangan, runtime portabel dan database contoh tersedia. Jalankan:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/serve-local.ps1
```

Jika npm mengalami error filesystem pada Google Drive, bangun dependency pada disk lokal dengan:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-local.ps1
```

Script menyalin hasil build ke public/build dan menyinkronkan aset V2. Lokasi runtime portabel, konfigurasi MariaDB, dan langkah Hostinger dijelaskan di [docs/BACKEND.md](docs/BACKEND.md).

## Memperbarui instalasi lokal yang sudah ada

Setelah mengambil perubahan terbaru, jalankan migrasi tambahan. Pertahankan `.env`, APP_KEY, serta database yang sudah ada; tidak perlu membuat database baru atau mengulang seeder untuk memperoleh fitur pengelolaan.

```powershell
git pull --ff-only
composer install
php artisan migrate
php artisan optimize:clear
```

Jika perlu membangun aset pada folder Google Drive, gunakan `scripts/build-local.ps1` seperti langkah di atas. Login pengelola lalu buka **Kelola kegiatan**, **Institusi**, **Narasumber**, **Kepesertaan**, **Instrumen L1**, atau **Instrumen L2**. Untuk produksi, ikuti pemeriksaan dan prosedur deployment di [BACKEND.md](docs/BACKEND.md).

## Pemeriksaan dan hasil

Antrean akses diaktifkan secara eksplisit. Nilai awal delapan request aktif dan delapan pemberian giliran per detik adalah pengaturan percobaan, bukan kapasitas Hostinger yang sudah terbukti:

```powershell
php artisan lentera:admission --enable
php artisan lentera:admission --status
```

Gunakan `--disable` untuk menonaktifkan pembatas ketika lalu lintas sudah terkendali. Pengaturan ada di `.env.example` dan `config/admission.php`; setelah mengubahnya, bersihkan/perbarui config cache lalu terbitkan kembali melalui `php artisan lentera:admission`. Petunjuk deployment dan uji bertahap 200–1.500 pengguna ada di [PERFORMANCE.md](docs/PERFORMANCE.md). Aset ruang tunggu berada langsung di `public`; perubahan ini tidak memerlukan build Vite baru.

Untuk mencoba tahap evaluasi terbaru, pengelola membuka **Instrumen L1** atau **Instrumen L2**, menyimpan draf dan menerbitkan versi. L1 dipilih pada pengaturan sesi; L2 diatur melalui **Kelola kegiatan → Atur sesi → Atur evaluasi L2**. Peserta tetap menggunakan QR tanpa login. Draf L2 disimpan manual dan skor awalnya tersembunyi; rincian ada di [L2.md](docs/L2.md).

```powershell
php artisan test
php vendor/bin/pint --test
composer validate --strict
composer check-platform-reqs
php artisan view:cache
npm run build
node tests/smoke.cjs
npx playwright install chromium
node tests/browser.cjs
node tests/management-browser.cjs
node tests/masters-browser.cjs
node tests/materials-browser.cjs
node tests/instruments-browser.cjs
node tests/l2-browser.cjs
node tests/l2-analytics-browser.cjs
php tests/admission-concurrency.php
node tests/admission-browser.cjs
```

Hasil lokal terakhir:

- **120 tes PHPUnit, 1.228 assertion lulus**, menggunakan SQLite in-memory yang terpisah dari database demo.
- Impor respons L1 historis diverifikasi: alur pembacaan CSV ke staging (`import_rows`), pengecualian baris ringkasan, pemetaan alias institusi manual (`institution_aliases`), pencegahan duplikasi NIM + email, penolakan berkas identik SHA-256, penerapan idempoten dalam satu transaksi, dan pencatatan riwayat audit lengkap.
- Ekspor CSV dan cetak PDF diverifikasi: ekspor ringkasan L1 dan analisis L2 berformat CSV kompatibel Excel (pemisah titik koma, desimal koma, UTF-8 BOM), perlindungan dari injeksi formula spreadsheet (`=`, `+`, `-`, `@`), isolasi cakupan akun BI/mitra tanpa membocorkan data rahasia atau identitas peserta, dan tombol cetak/simpan PDF peramban.
- Pengelolaan akun dan pemulihan kata sandi diverifikasi: pembuatan akun mitra/BI oleh pengelola dengan pembatasan cakupan KPwBI, pengaktifan/penonaktifan, pencegahan penonaktifan akun sendiri, penghapusan sesi database saat akun dinonaktifkan atau kata sandi direset, pemulihan mandiri anti-enumerasi email, pembatasan laju (throttling), penolakan token salah/peserta, dan pencatatan riwayat audit.
- Uji antrean terisolasi menyelesaikan 48 request sintetis pada 12 proses PHP; jumlah aktif tertinggi tepat tiga untuk batas tiga, tanpa melampaui batas. Penghentian paksa proses melepas slot tanpa menunggu TTL. Hasil ini memeriksa mekanisme pembatas, bukan kapasitas hosting.
- Chromium 17 Oktober 2026 menguji antrean aktif, isian tetap utuh, pembatalan, tic-tac-toe, reload ruang tunggu, pendaftaran satu POST dan konfirmasi L1 pada 375/768/1.440 px. Regresi L2 draf/final/skor/retry/versi juga lulus dengan antrean aktif. Detail dan batas hasil ada di [QA.md](docs/QA.md).
- Pint, validasi Composer, pemeriksaan kebutuhan platform, kompilasi Blade, dan Vite production build lulus.
- Chromium menguji draf/terbit/versi instrumen L1, pemilihan pada sesi, L1 tanpa akun, retry, formulir lama, dan riwayat V1 yang tetap utuh setelah V2 terbit. Pemeriksaan terbaru 16 Oktober 2026 tidak mengubah dependency/aset; hasil build/Composer di atas berasal dari tahap sebelumnya.
- Chromium menguji soal/kunci L2 → jadwal sesi → QR peserta → draf/reload → final pre/post → publikasi skor dan pasangan → versi baru yang tidak mengubah nilai lama. Skor sintetis 66,67/100 dan 100,00/100, selisih 33,33 poin persentase; kunci dan skor sebelum publikasi tidak dirender pada halaman peserta.
- Chromium menguji analisis L2 BI dengan filter periode/sesi, sesi belum terbit yang tidak terlihat oleh mitra, pembukaan agregat mitra sesuai institusi, penolakan filter institusi lain, serta halaman 375/768/1.440 px tanpa overflow. Rata-rata keseluruhan, pasangan lintas periode, urutan tes, nilai nol/negatif dan pagination diverifikasi di PHPUnit.
- Smoke test seluruh 20 rute prototype dan ekspor PDF/XLSX lulus.
- Chromium menguji unggah/terbit/sembunyikan dan versi materi; byte PDF serta hasil unduhan diverifikasi dengan SHA-256. Rendering isi PDF native pada screenshot headless belum dianggap terbukti; tersedia fallback tab baru/unduh.
- Chromium juga berhasil menguji master narasumber/institusi, pilihan narasumber server, pemetaan/status kepesertaan serta respons yang tetap tersimpan setelah pengaktifan kembali.
- Chromium berhasil menguji pembuatan kegiatan/sesi, siklus aktif/nonaktif/ganti QR, riwayat perubahan, serta QR pengelola, pendaftaran dan pengisian L1 tanpa akun, konfirmasi setelah reload, retry final, serta isolasi akses pada browser lain.
- Browser menolak CSRF tidak sah (419) dan payload evaluasi tidak sah (422).
- Form pendaftaran QR diperiksa pada lebar 375, 768, dan 1.440 px; form L1 juga diperiksa pada mobile tanpa overflow horizontal; target radio mobile minimal 44 px.

Browser test memerlukan server lokal yang berjalan dan database demo. Tes peserta membuat sesi browser baru, satu kepesertaan sintetis dan respons L1 bernilai 5,00/6 tanpa membuat akun. Tes pengelolaan juga menambah kegiatan, sesi, dan catatan audit sintetis. Artefak berada di tests/outputs dan diabaikan Git.

**Belum diverifikasi:** MariaDB aktual/Hostinger, konkurensi pada MariaDB, kapasitas 1.500 pengguna, serta browser/perangkat di luar cakupan QA di atas. Workflow CI belum dijalankan di GitHub pada saat hasil lokal ini dicatat. Rincian ada di [docs/QA.md](docs/QA.md).

## Prototype 20 layar

Buka index.html di root repository, atau gunakan server statis pada port terpisah:

```powershell
python -m http.server 8080
```

Buka http://localhost:8080/index.html. Gunakan **Demo · 20 layar** di footer untuk katalog dan pergantian peran contoh. Prototype memakai router hash dan localStorage; tidak membutuhkan login backend.

Referensi prototype yang pernah diterbitkan: https://lentera-learning.ardentchick33.chatgpt.site. Versi yang diterbitkan tidak otomatis mencerminkan perubahan repository atau menjadi aplikasi Laravel.

Snapshot contoh historis pada prototype: **7 April 2026, 141 respons L1, rata-rata 5,30/6**. Baris ringkasan tidak dihitung; identitas responden dari workbook tidak disertakan. Snapshot ini terpisah dari respons sintetis database backend.

## Struktur utama

| Lokasi | Fungsi |
| --- | --- |
| app/ | Controller, middleware, model, dan policy backend. |
| routes/web.php | Rute autentikasi, kegiatan, L1, dan ringkasan. |
| database/migrations/ | Skema database. |
| database/seeders/ | Seeder default kosong dan DemoSeeder eksplisit. |
| resources/views/ | Layout, komponen, dan halaman Blade. |
| resources/css/ dan resources/js/ | Tambahan CSS backend dan interaksi Alpine melalui Vite. |
| public/ | Satu-satunya document root untuk deployment Laravel. |
| styles.css dan assets/ | Sumber CSS/token/aset V2; aset backend disinkronkan oleh scripts/sync-assets.mjs. |
| index.html, data.js, app.js, pages-*.js | Prototype 20 layar yang dipertahankan. |
| scripts/ | Sinkronisasi aset, build lokal Windows, dan server lokal. |
| tests/ | PHPUnit, smoke test prototype, dan browser QA. |

## Dokumentasi acuan dan urutan selanjutnya

- [Indeks dokumentasi](docs/README.md): peta panduan pengguna, keputusan terbaru, dan acuan pengembangan.
- [Sketsa panduan peserta awam](docs/PANDUAN_LMS_DRAFT.md): QR, formulir identitas, kelas, evaluasi L1, konfirmasi, dan bantuan.
- [Sketsa panduan pengelola BI/mitra](docs/PANDUAN_PENGELOLA_MITRA_DRAFT.md): login, membagikan QR, membaca ringkasan, dan keluar.
- [Rencana finalisasi panduan](docs/PANDUAN_LMS_RENCANA.md): susunan halaman, daftar gambar, dan pemeriksaan sebelum terbit. PDF serta pemasangan panduan di LMS dikerjakan belakangan.
- [TECH_STACK.md](docs/TECH_STACK.md): kajian kebutuhan, keputusan stack, batas hosting, dan urutan migrasi.
- [ACCESS_FLOW.md](docs/ACCESS_FLOW.md): keputusan akses peserta tanpa login melalui QR kelas.
- [MATERIALS.md](docs/MATERIALS.md): file privat, versi, publikasi, batas upload, hak baca/unduh, dan pengujian.
- [INSTRUMENTS.md](docs/INSTRUMENTS.md): draf/versi instrumen L1, cakupan KPwBI, penerbitan, retry, audit dan riwayat respons.
- [L2.md](docs/L2.md): soal/kunci, pre/post, draf server, skor, publikasi, pasangan dan aturan awal L2.
- [ANALYTICS.md](docs/ANALYTICS.md): analisis L2 BI/mitra, periode berdasarkan post-test, cakupan institusi, pasangan sah, denominator dan privasi.
- [PERFORMANCE.md](docs/PERFORMANCE.md): antrean akses, tic-tac-toe, konfigurasi batas, deployment dan rencana uji kapasitas.
- [MASTERS_PARTICIPANTS.md](docs/MASTERS_PARTICIPANTS.md): master, snapshot, pemetaan instansi, kepesertaan, privasi dan audit.
- [IMPORT.md](docs/IMPORT.md): impor CSV respons L1 historis, alias institusi dan deteksi duplikat.
- [ACCOUNTS.md](docs/ACCOUNTS.md): pengelolaan akun pengelola/mitra, pemulihan mandiri kata sandi, isolasi sesi dan riwayat audit.
- [MANAGEMENT.md](docs/MANAGEMENT.md): pengelolaan kegiatan/sesi, akses QR, audit, dan pemeriksaan versi formulir.
- [BACKEND.md](docs/BACKEND.md): konfigurasi, aturan sementara, pengujian, dan deployment.
- [DESIGN.md](docs/DESIGN.md): desain V2.
- [SCREEN_MAP.md](docs/SCREEN_MAP.md): pemetaan layar backend dan prototype.
- [QA.md](docs/QA.md): hasil pemeriksaan serta batas verifikasi.

Prioritas kesiapan kapasitas: verifikasi batas akun Hostinger dan filesystem, lalu uji antrean pada staging MariaDB bertahap 200, 500, 1.000 dan 1.500 pengguna. Sesuaikan batas request serta jadwal evaluasi dari hasil pengukuran. Pemantauan antrean, penyesuaian otomatis berdasarkan beban dan ruang tunggu pada CDN/edge belum tersedia. Pengembangan fungsi berikutnya tetap mencakup impor historis melalui staging dan laporan server, kebijakan L2 lanjutan serta autosave.

Hosting tujuan tetap Hostinger Unlimited. Deployment Laravel harus mengekspos hanya public; source, .env, vendor, dan storage privat berada di luar root web. Jangan mengasumsikan tersedia root access, Supervisor, Redis, atau daemon permanen. Workflow Pages hanya menerbitkan prototype statis secara manual.
