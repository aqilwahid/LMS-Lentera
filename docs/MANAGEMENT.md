# Pengelolaan kegiatan, sesi dan QR

Pembaruan 15 Oktober 2026. Fitur ini melanjutkan fondasi backend dan alur peserta melalui QR tanpa login.

## Yang tersedia

Pengelola BI dapat membuka **Kelola kegiatan**, membuat kegiatan, mengubah judul/deskripsi, menambahkan sesi, mengubah jadwal, dan mengaktifkan/menonaktifkan atau mengganti QR. Mitra dan peserta tidak mempunyai izin pengelolaan.

Setiap sesi memuat nama sesi, satu nama narasumber, satu versi L1 terbit, waktu sesi, jadwal pendaftaran, serta jadwal evaluasi L1. Nama narasumber tetap menjadi snapshot sesi; master narasumber kini dapat dipilih secara opsional. Nama dari master ditentukan server dan perubahan master tidak menulis ulang snapshot sesi. Instrumen harus terbit, memuat lima butir valid dan berada dalam cakupan KPwBI atau referensi bersama. UI membuat/menerbitkan instrumen L1 kini tersedia; lihat [INSTRUMENTS.md](INSTRUMENTS.md).

Sesi baru memiliki token acak tetapi QR **nonaktif**. Pengelola memeriksa data, menyimpan, lalu memilih **Aktifkan akses kelas**. Mengaktifkan QR tidak melewati jadwal pendaftaran.

Tidak ada penghapusan kegiatan/sesi pada tahap ini. Gunakan penonaktifan akses untuk menghentikan tautan; catatan peserta dan respons tetap disimpan.

## Cara memeriksa lokal

Jalankan migrasi tambahan pada database yang sudah ada:

```powershell
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000 --no-reload
```

Pada mesin yang memakai runtime portabel, scripts/serve-local.ps1 juga dapat digunakan. Login pengelola contoh seperti dijelaskan di [BACKEND.md](BACKEND.md).

1. Pilih **Kelola kegiatan** → **Buat kegiatan**.
2. Isi nama/deskripsi → **Simpan kegiatan**.
3. Pilih **Tambah sesi**, isi narasumber, instrumen dan seluruh jadwal → **Simpan sesi**.
4. Pilih **Aktifkan akses kelas** → **Lihat QR dan tautan**.
5. Buka tautan pada browser peserta terpisah dan isi formulir tanpa login.
6. Setelah peserta bergabung, periksa pengaturan sesi: konteks terkunci, jadwal masih bisa diubah.
7. Isi L1 dari browser peserta, kemudian periksa efek penonaktifan atau penggantian QR.
8. Buka **Riwayat perubahan** untuk melihat pelaku, waktu, dan perubahan yang tersimpan.

Data pada lingkungan dengan LENTERA_DEMO_ENABLED=true diberi label contoh, termasuk kegiatan baru dari formulir. Jangan memakai instrumen/seeder contoh sebagai instrumen produksi.

## Aturan server

- Pengelola harus aktif, mempunyai peran bi_admin, dan memiliki office_id. Policy update membatasi setiap perubahan kegiatan/sesi ke KPwBI akun. office_id, activity_id, join_token dan status QR tidak diambil dari field bebas pada formulir kegiatan/sesi.
- Seluruh jadwal divalidasi dengan format tanggal/jam yang sama dan timezone aplikasi. Penutupan harus setelah pembukaan pada masing-masing jendela. Tidak ada asumsi bahwa pembukaan L1 wajib setelah waktu sesi, atau bahwa pretest harus selesai terlebih dahulu; aturan tambahan menunggu keputusan bisnis.
- Nama sesi, narasumber dan instrumen dikunci setelah ada pendaftaran tamu atau respons L1. Pemeriksaan dilakukan server dalam transaksi, bukan hanya melalui kontrol readonly. Perubahan konteks memerlukan sesi baru.
- Setiap perubahan memakai revision integer, pemeriksaan versi terbaru dan row lock. Formulir tab lama ditolak; pengguna harus memuat ulang sebelum menyimpan kembali. Retry rotasi dengan revision lama tidak menghasilkan token baru lagi.
- Perubahan kegiatan, sesi dan akses QR dicatat di audit_entries dalam transaksi yang sama. Kegagalan audit membatalkan perubahan. Audit hanya dapat dibaca BI pada KPwBI yang sama, berisi pelaku, waktu, target, serta nilai sebelum/sesudah.
- Token QR, rahasia akses browser dan identitas peserta tidak dimasukkan ke payload audit pengelolaan ini. Guard model menolak edit/hapus audit; query SQL langsung tetap berada di luar guard, sehingga prosedur operasional dan izin database tetap diperlukan.
- Penonaktifan QR menolak akses melalui tautan termasuk peserta yang sudah bergabung, tanpa menghapus enrollment atau respons. Mengaktifkan kembali mempertahankan token saat ini.
- Penggantian QR membuat token lama tidak berlaku. Setelah menerima tautan baru, peserta yang masih memakai sesi browser yang sama dapat mengakses pendaftaran/respons sebelumnya. Perangkat/sesi browser baru belum mempunyai pemulihan akses.
- Semua endpoint perubahan memakai CSRF dan middleware akun aktif. Riwayat audit tahap ini mencakup fitur pengelolaan baru saja; belum merupakan audit lengkap login, koreksi peserta, impor atau semua operasi database.

## Pengujian

```powershell
php artisan test
php vendor/bin/pint --test
php artisan view:cache
node tests/management-browser.cjs
```

Browser test memerlukan server lokal, flag demo aktif, dan dependency Playwright/Chromium. Setiap run menambah kegiatan, sesi, kepesertaan dan respons sintetis, serta audit. Jangan jalankan terhadap database produksi. Artefak berada di tests/outputs/management dan diabaikan Git.

Tes domain mencakup pembatasan peran/KPwBI, payload yang mencoba mengganti cakupan, jadwal tidak valid, instrumen draft/tidak lengkap, penguncian konteks, formulir lama, siklus QR dan transaksi audit. Hasil terbaru dicatat di [QA.md](QA.md).

MariaDB aktual, operasi bersamaan pada hosting dan kapasitas 1.500 pengguna belum diuji. Revision dan penguncian bukan bukti kapasitas atau pembuktian semua race condition; tetap perlu uji integrasi MariaDB dan uji beban.

## Berikutnya

Master institusi/narasumber serta status/pemetaan kepesertaan kini tersedia; lihat [MASTERS_PARTICIPANTS.md](MASTERS_PARTICIPANTS.md). Materi PDF privat kini tersedia; lihat [MATERIALS.md](MATERIALS.md). Instrumen L1 kini tersedia; lihat [INSTRUMENTS.md](INSTRUMENTS.md). L2 kini tersedia; lihat [L2.md](L2.md). Analisis L2 kini tersedia; lihat [ANALYTICS.md](ANALYTICS.md). Pengelolaan akun BI/mitra dan pemulihan kata sandi kini tersedia; lihat [ACCOUNTS.md](ACCOUNTS.md). Berikutnya impor/laporan, filter L1 dan koreksi identitas yang diaudit. Penghapusan/arsip dan retensi data perlu aturan khusus sebelum disediakan. Panduan awam masih Markdown; PDF final tetap dikerjakan belakangan.
