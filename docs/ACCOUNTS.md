# Pengelolaan akun dan pemulihan kata sandi

Pembaruan 17 Oktober 2026. Fitur ini melengkapi autentikasi pengelola/mitra, kontrol akses per kantor, dan riwayat audit perubahan. Peserta kegiatan tetap mengikuti alur QR tanpa login dan tanpa akun.

## Yang tersedia

Pengelola BI pada KPwBI akun dapat:

- Membuka daftar akun melalui menu **Akun** (`/pengelola/akun`), mencari akun berdasarkan nama atau email, dan melihat peran serta status keaktifannya.
- Membuat akun baru (`/pengelola/akun/buat`) untuk peran **Pengelola BI** (`bi_admin`) atau **Mitra** (`partner`):
  - Untuk akun pengelola BI, kantor (`office_id`) otomatis ditetapkan ke KPwBI pengelola yang membuat.
  - Untuk akun mitra, pengelola wajib memilih institusi mitra (`institution_id`) yang aktif dalam cakupan KPwBI tersebut.
  - Akun baru dibuat dengan kata sandi acak awal dan sistem otomatis mengirimkan tautan pembuatan/pengaturan kata sandi ke email pengguna.
- Mengubah nama dan status keaktifan akun (`/pengelola/akun/{id}/ubah`).
- Menonaktifkan akun untuk menghentikan akses login. Penonaktifan akun secara otomatis menghapus seluruh sesi aktif akun tersebut pada tabel `sessions` jika aplikasi menggunakan driver sesi database.
- Sistem mencegah pengelola menonaktifkan akunnya sendiri agar tidak terjadi penguncian akses administratif.
- Mengirim ulang tautan pemulihan/pengaturan kata sandi (`/pengelola/akun/{id}/pemulihan`) untuk akun yang berstatus aktif.
- Membaca riwayat perubahan akun pada halaman **Riwayat perubahan** (`/pengelola/riwayat`).

Pengguna dengan peran Pengelola BI atau Mitra yang lupa kata sandi dapat:

- Membuka halaman **Lupa kata sandi** (`/lupa-kata-sandi`) dari tautan di bawah formulir login.
- Memasukkan email akun aktif untuk menerima tautan pemulihan kata sandi (tautan berlaku selama 60 menit).
- Membuka tautan dari email ke halaman **Atur kata sandi** (`/atur-kata-sandi/{token}`), lalu memasukkan kata sandi baru sesuai standar keamanan.

Mitra dan peserta tidak memiliki hak akses untuk mengelola akun. Peserta tidak memiliki akun pengguna dan tidak dapat menggunakan alur pemulihan kata sandi ini.

## Aturan keamanan dan server

- **Isolasi KPwBI**: Pengelola BI dibatasi oleh `office_id` akunnya. Pengelola hanya dapat melihat, membuat, mengubah, dan memicu reset kata sandi bagi akun pengelola BI di kantor yang sama atau akun mitra yang terikat pada institusi aktif di kantor tersebut. Upaya mengakses atau memanipulasi ID akun di luar cakupan kantor ditolak dengan HTTP 404/403.
- **Anti-enumerasi email**: Endpoint permintaan reset kata sandi (`POST /lupa-kata-sandi`) selalu mengembalikan pesan konfirmasi sukses yang identik, baik email tersebut terdaftar, tidak terdaftar, nonaktif, maupun milik peserta. Hal ini mencegah pihak luar menebak keberadaan email pengelola atau mitra di sistem.
- **Batasan peran pemulihan**: Hanya akun aktif dengan peran `bi_admin` atau `partner` yang diproses oleh broker pemulihan kata sandi. Email milik peserta diabaikan dan tidak dikirimi email pemulihan.
- **Pembatasan laju (rate limiting / throttling)**:
  - Form permohonan tautan pemulihan: maksimal 5 permintaan per menit per IP (`throttle:5,1`).
  - Form eksekusi reset kata sandi: maksimal 10 permintaan per menit per IP (`throttle:10,1`).
  - Form login: maksimal 30 permintaan per menit (`throttle:30,1`).
- **Standar kata sandi**: Kata sandi baru wajib memenuhi aturan minimal 12 karakter, memuat kombinasi huruf dan angka (`regex:/^(?=.*[A-Za-z])(?=.*\d).+$/`), serta konfirmasi kata sandi wajib cocok.
- **Pencabutan sesi aktif (session revocation)**: Saat kata sandi berhasil direset atau saat akun dinonaktifkan oleh pengelola, seluruh rekaman sesi browser aktif untuk akun tersebut pada tabel `sessions` dihapus (pada driver sesi database), dan `remember_token` dirotasi secara acak untuk memutus sesi "remember me".
- **Riwayat audit**: Seluruh aksi administratif dan pemulihan dicatat ke tabel `audit_entries` dalam cakupan KPwBI yang bersangkutan:
  - `account.created`: Akun pengelola atau mitra baru dibuat.
  - `account.updated`: Perubahan nama atau status keaktifan akun.
  - `account.reset_sent`: Pengelola mengirim ulang tautan reset kata sandi.
  - `account.password_reset`: Pengguna berhasil mengatur ulang kata sandi melalui tautan token.

## Cara memeriksa lokal

Jalankan pengujian otomatis:

```powershell
php vendor/phpunit/phpunit/phpunit tests/Feature/AccountManagementTest.php
php vendor/bin/pint --test
php artisan view:cache
```

Untuk pengujian interaktif lokal:

```powershell
php artisan serve --host=127.0.0.1 --port=8000 --no-reload
```

1. Masuk sebagai `pengelola@example.test` (kata sandi `Lentera-Demo-2026!`).
2. Pilih menu **Akun** di navigasi atas.
3. Pilih **Tambah akun**, pilih peran **Mitra**, pilih institusi, lalu simpan.
4. Periksa log email (`storage/logs/laravel.log` jika mailer lokal diset `log`) untuk melihat tautan reset token yang dikirim.
5. Keluar, lalu buka tautan pemulihan tersebut atau navigasikan ke `/lupa-kata-sandi`.
6. Atur kata sandi baru (minimal 12 karakter + huruf & angka) dan coba masuk kembali dengan kata sandi baru.
7. Masuk kembali sebagai pengelola dan periksa menu **Riwayat perubahan** untuk memastikan seluruh catatan audit tersimpan.

## Hasil dan tahap berikutnya

- **117 tes PHPUnit, 1.192 assertion lulus**, mencakup pembuatan akun, pengiriman tautan via notifikasi, penegakan audit, isolasi kantor/KPwBI, larangan penonaktifan mandiri, pencegahan enumerasi email, penolakan token peserta/tidak sah, serta validasi kebijakan kata sandi.
- Pengiriman email pada lingkungan lokal/testing menggunakan driver `array` atau `log`. Lingkungan produksi memerlukan konfigurasi `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, dan `MAIL_FROM_ADDRESS` yang terverifikasi.
- Tahap berikutnya mencakup koreksi identitas peserta yang diaudit, pemulihan akses peserta lintas perangkat, impor historis, dan laporan server.
