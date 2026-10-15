# LENTERA Management System

LENTERA mengelola kegiatan literasi kebanksentralan bagi peserta eksternal Bank Indonesia, pengelola BI, dan mitra perguruan tinggi.

**Status per 15 Oktober 2026:** backend tahap pertama sudah dapat dijalankan dan diperiksa secara lokal. Alur peserta membuka QR/tautan kelas → mengisi formulir tanpa akun → membuka kelas → mengisi L1 → menerima konfirmasi telah terhubung ke database. Login hanya untuk pengelola BI dan mitra. Prototype 20 layar tetap tersedia sebagai referensi visual V2; alur login peserta pada prototype lama belum diselaraskan dengan alur QR backend. Aplikasi belum dinyatakan siap produksi.

## Sudah dikerjakan

| Area | Hasil implementasi |
| --- | --- |
| Fondasi | Laravel 13, target PHP 8.4, Blade, Alpine.js, Vite, serta lockfile Composer/npm. |
| Tampilan V2 | Layout, navigasi, komponen validasi, login, beranda peserta, detail kegiatan/sesi, form L1, konfirmasi, dan ringkasan agregat BI/mitra memakai CSS, font, logo, serta aset V2 yang sudah ada. |
| Autentikasi pengelola/mitra | Login email/kata sandi, password hashing, session server, rotasi session ID, logout, CSRF, pembatasan percobaan login, dan penolakan akun nonaktif. |
| Akses peserta dan pengelola | Peserta tanpa akun hanya mengakses kelas dan respons miliknya melalui sesi browser. Pendaftaran tidak membuat akun pengguna. BI dibatasi KPwBI akun. Mitra hanya menerima agregat institusinya tanpa identitas, jawaban, atau masukan individu. Izin diperiksa server. |
| Database | Migrasi untuk KPwBI, institusi, pengguna/profil dasar, kegiatan, sesi, kepesertaan, versi instrumen L1, dan respons L1; tabel session, cache, dan antrean database juga tersedia. |
| Data contoh | Seeder sintetis untuk tiga institusi, tiga peran, satu kegiatan/sesi, satu kepesertaan, dan instrumen L1. Seeder hanya diizinkan pada lingkungan local/testing dengan flag demo aktif. |
| Pendaftaran melalui QR | Tautan acak per sesi, jadwal pendaftaran, formulir identitas tanpa login, serta tampilan/unduh QR SVG bagi BI sesuai KPwBI. |
| Evaluasi L1 | Lima butir skala 1–6, masukan opsional, validasi/jadwal/versi diperiksa server, dan rata-rata dihitung dari lima jawaban yang sah. |
| Integritas respons | Transaksi, penguncian, dan constraint unik kepesertaan+sesi mencegah respons ganda. Retry final mempertahankan respons pertama dan mengembalikan konfirmasi. |
| Riwayat | Kepesertaan menyimpan konteks institusi/kategori historis. Respons menyimpan snapshot instrumen, versi, skala, dan narasumber. Model membatasi perubahan konteks historis dan respons final. |
| Ringkasan | Agregat L1 dari database sesuai cakupan BI/mitra, dipisahkan per sesi, instrumen, dan institusi historis, dengan jumlah respons serta periode. |
| Deployment/CI | Workflow Pages dibatasi ke allowlist prototype dan dijalankan manual. Workflow pemeriksaan backend tersedia untuk build, tes, dan format kode; belum melakukan deployment Laravel. |
| Dokumentasi | Keputusan stack, cara menjalankan, asumsi bisnis, pemetaan layar backend, hasil QA, dan batas implementasi telah ditulis. |

**Alur peserta yang ditetapkan:** peserta tidak login atau membuat akun. Pengelola membagikan QR kelas, lalu peserta mengisi Nama Lengkap; Instansi/Perguruan Tinggi; NIP/NIM/NPM; Jurusan/Program Studi; Fakultas; No. HP; Kategori; Nama Narasumber; dan Email. Nama narasumber berasal dari sesi kelas dan tidak dapat diubah peserta.

**Aturan sementara:** satu peran per akun pengelola/mitra, satu narasumber dan satu respons L1 final per kepesertaan per sesi. Sesi browser menyimpan akses peserta; email/NIP belum diverifikasi dan tidak digunakan untuk membuka respons peserta lain. Redaksi L1 memakai salinan kerja DESIGN V2; redaksi resmi dan aturan multi-narasumber belum final. Detail ada di [docs/BACKEND.md](docs/BACKEND.md).

## Belum dikerjakan

| Area | Pekerjaan berikutnya |
| --- | --- |
| Akun dan profil | UI pengelolaan akun/peran BI/mitra, pemulihan kata sandi pengelola/mitra, koreksi identitas peserta yang diaudit, serta pemulihan akses peserta lintas perangkat. |
| Pengelolaan | CRUD institusi/KPwBI, kegiatan, sesi, kepesertaan, narasumber, instrumen, dan aturan penerbitan melalui UI pengelola. |
| Materi dan panduan | Unggah, versi, penyimpanan privat, pemeriksaan izin baca/unduh PDF, serta finalisasi panduan awam menjadi PDF/halaman LMS. Sketsa Markdown sudah tersedia; PDF prototype belum menjadi modul materi backend. |
| L1 lanjutan | Multi-narasumber, aturan kewajiban pengisian resmi, draf server/autosave, dan mekanisme koreksi administratif yang dapat diaudit. |
| Evaluasi L2 | Instrumen pretest/post-test per pertemuan, versi soal, attempt, draf, penilaian server, pasangan pre/post, dan kebijakan publikasi hasil. |
| Impor historis | Staging Excel, pemetaan alias institusi, validasi, penanganan baris ringkasan, deteksi duplikat, dan rekonsiliasi data sumber. |
| Analisis/laporan | Filter periode lengkap, dashboard tahunan, Chart.js, ekspor PDF/XLSX server menggunakan Dompdf/PhpSpreadsheet, dan arsip laporan. Paket laporan belum dipasang. |
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
php artisan serve --host=127.0.0.1 --port=8000
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

## Pemeriksaan dan hasil

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
```

Hasil lokal terakhir:

- **27 tes PHPUnit, 213 assertion lulus**, menggunakan SQLite in-memory yang terpisah dari database demo.
- Pint, validasi Composer, pemeriksaan kebutuhan platform, kompilasi Blade, dan Vite production build lulus.
- Smoke test seluruh 20 rute prototype dan ekspor PDF/XLSX lulus.
- Chromium berhasil menguji QR pengelola, pendaftaran dan pengisian L1 tanpa akun, konfirmasi setelah reload, retry final, serta isolasi akses pada browser lain.
- Browser menolak CSRF tidak sah (419) dan payload evaluasi tidak sah (422).
- Form pendaftaran QR diperiksa pada lebar 375, 768, dan 1.440 px; form L1 juga diperiksa pada mobile tanpa overflow horizontal; target radio mobile minimal 44 px.

Browser test memerlukan server lokal yang berjalan dan database demo. Setiap run membuat sesi browser peserta baru, satu kepesertaan sintetis dan respons L1 bernilai 5,00/6 tanpa membuat akun. Artefak berada di tests/outputs dan diabaikan Git.

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
- [BACKEND.md](docs/BACKEND.md): konfigurasi, aturan sementara, pengujian, dan deployment.
- [DESIGN.md](docs/DESIGN.md): desain V2.
- [SCREEN_MAP.md](docs/SCREEN_MAP.md): pemetaan layar backend dan prototype.
- [QA.md](docs/QA.md): hasil pemeriksaan serta batas verifikasi.

Prioritas berikutnya: CRUD kegiatan/sesi dan kepesertaan beserta audit, pengelolaan masa berlaku/rotasi QR, materi privat, lalu instrumen/penilaian L2. Impor historis, analisis periode, laporan server, dan uji beban dilanjutkan setelah alur tersebut stabil.

Hosting tujuan tetap Hostinger Unlimited. Deployment Laravel harus mengekspos hanya public; source, .env, vendor, dan storage privat berada di luar root web. Jangan mengasumsikan tersedia root access, Supervisor, Redis, atau daemon permanen. Workflow Pages hanya menerbitkan prototype statis secara manual.
