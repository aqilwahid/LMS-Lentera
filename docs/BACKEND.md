# Backend tahap 1 — 15 Oktober 2026

Root Laravel berada di repository utama, berdampingan dengan prototype. PHP 8.4 + Laravel 13, Blade, Alpine dan Vite telah dipasang dengan lockfile. Runtime lokal memakai SQLite agar dapat diperiksa tanpa server database; konfigurasi produksi memakai MariaDB melalui koneksi mysql Laravel. Migrasi MariaDB, konkurensi database dan hosting aktual belum diuji pada sesi ini.

## Menjalankan checkout baru

Diperlukan PHP 8.4 beserta PDO SQLite/MySQL, mbstring, OpenSSL, fileinfo, DOM/XML dan Composer 2; Node 22.12+ untuk Vite. Ekstensi laporan tambahan diperiksa saat paket laporan dipasang.

Dari root project:

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

Untuk data sintetis, ubah LENTERA_DEMO_ENABLED=true di .env, kemudian:

```powershell
php artisan db:seed --class=DemoSeeder
php artisan serve --host=127.0.0.1 --port=8000 --no-reload
```

Buka http://127.0.0.1:8000/. Login /masuk hanya untuk pengelola@example.test dan mitra@example.test, kata sandi Lentera-Demo-2026!. Akun participant contoh lama tetap menjadi fixture regresi, tetapi tidak dapat login. Seeder hanya berjalan pada APP_ENV=local/testing dengan flag demo aktif, tidak menimpa akun/respons yang sudah ada. DatabaseSeeder default kosong. Data contoh tidak memuat identitas responden Excel atau snapshot 141 respons April.

Alur peserta: pindai QR/buka tautan kelas → isi formulir identitas → Gabung kelas → Buka evaluasi L1 → pilih lima nilai → Kirim evaluasi → konfirmasi waktu server. Peserta tidak login atau membuat akun. Untuk memperoleh QR contoh, login pengelola lalu buka QR dan tautan pada ringkasan. Masuk dengan mitra/pengelola untuk melihat agregat sesuai cakupan. Baca [ACCESS_FLOW.md](ACCESS_FLOW.md) untuk keputusan akses terbaru.

## Lingkungan Windows yang disiapkan pada sesi ini

PHP 8.4.26, Composer dan Node 22.22.0 portabel berada di C:/Users/LEGION/.codex/lentera-tools, di luar repository. PATH mesin tidak diubah. .env, APP_KEY, database SQLite, vendor dan build lokal tidak masuk Git.

Pada mesin ini, cukup jalankan:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/serve-local.ps1
```

Jika npm gagal mengekstrak dependency pada Google Drive, gunakan:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-local.ps1
```

Script server Windows memakai --no-reload untuk mempertahankan TEMP/TMP bagi unggahan; restart manual setelah mengubah .env.

Script membangun di LOCALAPPDATA, lalu menyalin hasilnya ke public/build. Untuk perubahan PHP tidak perlu build ulang. Untuk perubahan styles.css/aset, jalankan assets:sync atau build-local.ps1. CSS V2 asli menjadi sumber public/styles-v2.css; hanya logo, font dan ilustrasi yang diizinkan disalin, bukan PDF atau pustaka ekspor prototype.

## Antrean akses — 17 Oktober 2026

Gerbang ringan di `public/index.php` membatasi request sebelum Composer, session dan database. Endpoint `public/admission.php` memeriksa tiket tanpa Laravel/database; ruang tunggu dan tic-tac-toe memakai aset statis. Pembatas awal delapan request aktif/delapan pemberian giliran per detik dapat diatur, dan belum membuktikan kapasitas Hostinger.

```powershell
php artisan lentera:admission --enable
php artisan lentera:admission --status
```

Aktivasi lokal tidak mengubah `.env`. Untuk deployment berulang, atur `LENTERA_ADMISSION_ENABLED=true`, perbarui config cache, lalu jalankan `php artisan lentera:admission`. Checkout baru tetap nonaktif sampai konfigurasi diterbitkan. `--disable` mempertahankan state dan melepas pembatas bagi request baru; gunakan ketika lalu lintas sudah terkendali.

Salin seluruh file ruang tunggu di `public` saat deployment. Konfigurasi/state privat di `storage/framework` diabaikan Git dan tidak boleh dihapus saat request aktif. Tidak ada migrasi atau dependency baru. Detail filesystem, HTTPS, polling, isian formulir, validasi jadwal dan keterbatasan ada di [PERFORMANCE.md](PERFORMANCE.md).

## Data dan aturan sementara

- Login hanya untuk bi_admin dan partner. Peserta memakai kepesertaan tamu dengan user_id=null dan akses sesi browser. Data/endpoint participant dari fondasi awal dipertahankan untuk kompatibilitas dan regresi; bukan jalur pendaftaran baru. Pengelola BI dibatasi office_id; mitra pada institution_id. Akun nonaktif ditolak pada setiap request terautentikasi.
- Master offices/institutions, users, activities, learning_sessions, enrollments, l1_instruments/l1_responses, serta l2_instruments/l2_assessments/l2_attempts. Nomor identitas berupa teks.
- Institusi ID/nama dan kategori pada enrollment adalah snapshot saat pendaftaran; perubahan profil tidak mengubahnya. Model menolak perubahan konteks enrollment, tetapi status dapat dicabut.
- Satu respons final per enrollment+sesi. Sesi tahap ini mempunyai satu narasumber, satu versi L1 dan jendela pengisian. Multi-narasumber memerlukan assignment terpisah sebelum dipakai dalam kegiatan nyata.
- Redaksi kerja lima butir dari DESIGN V2 ada di config/lentera.php dan disalin ke versi instrumen saat pembuatan. Label verbal angka 1–6 tidak dikarang. Redaksi resmi masih menunggu persetujuan.
- Versi instrumen terbit tidak dapat diedit/dihapus lewat model. Respons menyimpan snapshot pertanyaan, versi, skala dan narasumber. Konteks sesi dengan respons final tidak dapat diubah lewat model. Perubahan dengan query builder/SQL langsung melewati guard model; perubahan administratif harus memakai layanan domain terkontrol pada tahap berikutnya.
- Server memeriksa kepesertaan, peran, jadwal, versi instrumen, tepat lima nilai integer 1–6 dan panjang feedback. score_sum dihitung server; rata-rata = score_sum/5.
- Transaksi mengunci enrollment dan sesi; constraint unik enrollment+sesi menjadi pengaman akhir. Retry setelah final mengembalikan konfirmasi yang sama, tanpa menimpa jawaban, meski jadwal sudah ditutup. Retry dengan payload berbeda juga mempertahankan respons pertama. Request tetap harus berasal dari peserta yang masih berhak.
- Respons final tidak dapat diedit/dihapus lewat model. Koreksi administratif dan audit koreksi belum disediakan.
- Login memakai session Laravel, rotasi session ID, CSRF, password hashing dan pembatasan percobaan per email/IP. Logout menghapus session. Pendaftaran kelas terbuka bagi pemegang tautan QR selama jadwalnya aktif, tanpa membuat akun. Reset email otomatis, SSO dan HRIS belum tersedia.
- Mitra hanya menerima agregat institusi historisnya, tanpa identitas/jawaban/feedback individu; tidak dapat membuka detail kegiatan peserta atau mengekspor. BI melihat agregat KPwBI akun. Agregat dipisahkan per sesi, instrumen dan konteks institusi, disertai jumlah respons/periode.
- L1 tidak mensyaratkan L2/membaca PDF. Pengelolaan kegiatan/sesi, jadwal dan QR beserta audit sudah tersedia; lihat [MANAGEMENT.md](MANAGEMENT.md). Materi PDF privat beserta versi/izin kini tersedia; lihat [MATERIALS.md](MATERIALS.md). Draf L2 manual di server, skor dan publikasi sudah tersedia; lihat [L2.md](L2.md). Draf/autosave L1 dan laporan belum dihubungkan. Master institusi/narasumber dan pemetaan/status kepesertaan sudah tersedia; lihat [MASTERS_PARTICIPANTS.md](MASTERS_PARTICIPANTS.md).

## Pemeriksaan

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
```

Tes PHPUnit memakai database SQLite in-memory, terpisah dari database lokal. Browser test masuk sebagai pengelola contoh untuk memperoleh QR, lalu memakai browser context terpisah untuk mendaftar dan mengirim L1 tanpa akun. Setiap run membuat satu kepesertaan dan respons sintetis baru 5/6. Tes menolak lingkungan tanpa banner contoh. Jangan gunakan database berisi data produksi.

Jika dependency Node dibangun di folder lain, arahkan LENTERA_JSDOM_PATH dan LENTERA_PLAYWRIGHT_PATH ke paket di folder node_modules tersebut. Artefak screenshot dan ekspor berada di tests/outputs (diabaikan Git).

PHPUnit mencakup penyimpanan/skor server, retry final, validasi payload, jadwal, instrumen belum terbit, akun nonaktif, rate limit, logout, kepesertaan dicabut, akses peran, batas KPwBI/institusi, snapshot historis dan guard seeder. Ini belum membuktikan race condition pada MariaDB atau kapasitas 1.500 pengguna.

## Hostinger dan deployment

Workflow Pages sekarang hanya manual dan memakai allowlist prototype; tidak lagi mengunggah root repository. Workflow backend.yml menjalankan build/test di CI; belum men-deploy PHP. Situs prototype yang sudah terbit tidak menjadi aplikasi Laravel.

Pada hosting: verifikasi PHP CLI/web 8.4, ekstensi, versi MariaDB, batas akun dan akses cron. DB_CONNECTION=mysql, DB_HOST/PORT/DATABASE/USERNAME/PASSWORD sesuai hPanel. APP_ENV=production, APP_DEBUG=false, APP_URL=https://domain, SESSION_SECURE_COOKIE=true, LENTERA_DEMO_ENABLED=false; buat APP_KEY produksi sendiri. Jangan salin database demo.

Document root harus menunjuk hanya ke public. Source, vendor, .env dan storage privat berada di luar root web. Build dijalankan lokal/CI; composer install --no-dev --optimize-autoloader, php artisan migrate --force, lalu php artisan optimize pada deployment terkontrol. Direktori storage dan bootstrap/cache harus writable. Uji backup dan pemulihan sebelum migrasi data produksi.

SESSION_DRIVER=database, CACHE_STORE=database dan QUEUE_CONNECTION=database sudah menjadi default. Untuk job singkat pada cron, contoh:

```text
* * * * * cd /path/lentera && /path/php artisan queue:work database --stop-when-empty --max-time=50 --tries=3
```

Jangan aktifkan cron sebelum memastikan job bounded dan mekanisme anti-overlap sesuai kemampuan akun. Belum ada job aplikasi tahap ini. Tidak mengasumsikan root, Redis, Supervisor atau daemon. Email lokal memakai log, SMTP/API nyata dikonfigurasi saat akses tersedia.

## Pengelolaan tahap berikutnya yang sudah tersedia

Kegiatan/sesi dapat dibuat dan diubah melalui menu Kelola kegiatan. QR sesi baru nonaktif sampai pengelola mengaktifkannya. Jadwal dan siklus QR dapat diatur, dengan pemeriksaan versi formulir dan riwayat audit. Migrasi tambahan menambah revision dan audit_entries. Panduan operasional, asumsi, batas, dan cara menguji ada di [MANAGEMENT.md](MANAGEMENT.md).

## Tahap selanjutnya

Sketsa panduan untuk pengguna awam tersedia di [PANDUAN_LMS_DRAFT.md](PANDUAN_LMS_DRAFT.md) untuk peserta dan [PANDUAN_PENGELOLA_MITRA_DRAFT.md](PANDUAN_PENGELOLA_MITRA_DRAFT.md) untuk BI/mitra. [PANDUAN_LMS_RENCANA.md](PANDUAN_LMS_RENCANA.md) memuat rencana gambar dan finalisasi. Statusnya naskah Markdown; belum membuat PDF atau menghubungkan halaman panduan pada backend.

Pengelolaan instrumen L1 per KPwBI kini tersedia; lihat [INSTRUMENTS.md](INSTRUMENTS.md). L2 pilihan ganda, draf manual dan penilaian server tersedia; lihat [L2.md](L2.md). Analisis L2 BI/mitra per institusi/periode tersedia; lihat [ANALYTICS.md](ANALYTICS.md). Tahap berikutnya filter L1, impor/laporan dan aturan L2 lanjutan; pemulihan akun pengelola/mitra; multi-narasumber dan aturan L1 resmi; autosave dan jenis soal L2 lanjutan; impor historis; grafik dan laporan menggunakan Chart.js/PhpSpreadsheet/Dompdf. Paket laporan belum dipasang karena fitur itu belum masuk alur tahap 1. Setelah itu uji MariaDB, browser lintas platform, backup, serta k6 bertahap 200–1.500 pengguna sebelum menetapkan kapasitas produksi.
