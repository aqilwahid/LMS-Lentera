# Master institusi/narasumber dan kepesertaan

Pembaruan 15 Oktober 2026. Fitur ini melanjutkan [pengelolaan kegiatan/sesi](MANAGEMENT.md). Peserta tetap bergabung melalui QR tanpa akun.

## Yang tersedia

Pengelola BI pada KPwBI sendiri dapat:

- Membuat, mencari, mengubah nama, dan mengaktifkan/menonaktifkan master institusi.
- Membuat, mencari, mengubah, dan mengaktifkan/menonaktifkan master narasumber, dengan asal instansi serta jenis internal/eksternal.
- Memilih narasumber dari master ketika membuat/mengubah sesi yang konteksnya belum terkunci. Nama boleh diisi langsung jika belum memakai master.
- Membuka daftar kepesertaan, mencari nama/nomor identitas/instansi, memfilter kegiatan atau instansi belum dipetakan, serta melihat detail identitas.
- Memetakan instansi yang belum dikenali ke master institusi pada KPwBI yang sama.
- Menonaktifkan/mengaktifkan kembali kepesertaan tanpa menghapus respons.
- Membaca riwayat perubahan master, status kepesertaan, dan pemetaan.

Mitra tidak mempunyai akses ke master, daftar/detail peserta atau tindakan pengelolaan. Mitra tetap menerima agregat institusinya saja. Halaman daftar/detail peserta menggunakan Cache-Control private, no-store.

## Migrasi dan pemeriksaan lokal

```powershell
php artisan migrate
php artisan test
php vendor/bin/pint --test
php artisan view:cache
node tests/masters-browser.cjs
```

Browser test memerlukan server demo lokal dan Playwright/Chromium; dependency dapat diarahkan dengan LENTERA_PLAYWRIGHT_PATH. Tes membuat data master/sesi/pendaftaran/respons/audit sintetis, bukan akun peserta. Artefak berada di tests/outputs/masters dan diabaikan Git.

Migrasi menambah speakers, speaker_id pada sesi, status/revision master, mapped_institution_id/revision kepesertaan, serta indeks. Nama master unik dalam KPwBI yang sama. Sebelum menerapkan pada database produksi yang sudah berisi data, periksa duplikat nama per KPwBI sesuai collation MariaDB aktual; migrasi tidak menggabungkan atau menghapus duplikat secara otomatis.

## Riwayat narasumber dan institusi

speaker_id menghubungkan master ke sesi, sedangkan speaker_name tetap merupakan snapshot nama pada sesi. Jika master dipilih, server mengambil nama dari master; nama bebas dari request tidak dipercaya. Perubahan nama master tidak mengganti nama pada sesi lama. Jika sesi tetap memilih master yang sama, penyimpanan jadwal mempertahankan snapshot lama.

Narasumber nonaktif tidak dapat dipilih untuk sesi baru, tetapi referensi yang sudah digunakan boleh dipertahankan ketika mengubah jadwal. Penggantian master/nama sesi, narasumber atau instrumen tetap ditolak setelah peserta bergabung atau L1 terkirim.

Institusi nonaktif tidak ditawarkan sebagai saran dan tidak dipetakan otomatis saat pendaftaran baru. Nama yang diketik peserta tetap diterima sebagai isian asli yang belum dipetakan. Perubahan nama master tidak mengubah institution_name atau institution_id historis pada kepesertaan yang sudah ada.

Tidak ada penghapusan master atau pemindahan master antar-KPwBI melalui UI. Penonaktifan menjaga referensi historis. Penonaktifan master institusi tidak otomatis menonaktifkan akun mitra atau menghapus agregat lama.

## Pemetaan tanpa menulis ulang sumber

- institution_name dan institution_id tetap menyimpan konteks saat pendaftaran.
- Untuk institution_id yang masih null, BI dapat menetapkan mapped_institution_id setelah memeriksa isian asli.
- Pemetaan baru hanya ke institusi aktif dalam KPwBI kegiatan. Pemetaan lama ke master yang kemudian nonaktif boleh dipertahankan saat mengubah status peserta; tidak diam-diam dihapus.
- Institusi yang sudah dikenali saat pendaftaran tidak dapat dipetakan ulang melalui formulir ini.
- Pemetaan dapat diganti/dikosongkan dengan audit. Isian asli tidak diubah.
- Agregat mitra memakai institution_id jika tersedia, atau mapped_institution_id untuk instansi yang semula belum dikenali. Mengubah pemetaan dapat memindahkan respons antar-cakupan agregat mitra. Tidak ada pencocokan alias otomatis atau pemeringkatan.
- Kelompok agregat tetap mempertahankan nama institusi asli sebagai label sumber dan dipisahkan per sesi/instrumen. Varian ejaan tidak otomatis digabung menjadi satu kelompok.
- Audit kepesertaan hanya memuat status dan ID pemetaan sebelum/sesudah; nama peserta, email, telepon, nomor identitas dan rahasia akses browser tidak disalin ke payload audit itu.

## Status dan izin

Status active mengizinkan akses sesuai QR/jadwal; revoked menghentikan akses kelas dan submit. Mengaktifkan kembali memungkinkan browser peserta yang sama melanjutkan, selama QR/jadwal masih mengizinkan. Respons historis tetap tersimpan dan tetap dihitung dalam agregat historis, termasuk saat kepesertaan dinonaktifkan.

Setiap pembaruan master/kepesertaan memakai revision dan transaksi dengan row lock. Formulir lama ditolak. Perubahan dan audit disimpan dalam transaksi yang sama; kegagalan audit membatalkan perubahan. Cakupan KPwBI serta institusi tujuan diperiksa server. Payload tidak dapat mengubah office_id atau identitas historis peserta.

Pengelola BI dapat mengoreksi nama, NIM/NIP, email, HP, jurusan dan fakultas (alasan wajib, kunci revisi); instansi asli dan kategori tidak dapat diubah. Nilai sebelum/sesudah dan alasan koreksi disimpan pada riwayat perubahan. Email/NIP/HP belum diverifikasi. Pemetaan bukan verifikasi identitas dan bukan pemberian akun. Pemulihan browser/perangkat baru, koreksi jawaban final L1, retensi/penghapusan, hubungan banyak KPwBI, serta penyatuan identitas peserta lintas kegiatan belum disediakan.

## Hasil dan tahap berikutnya

Suite lokal: **47 tes, 424 assertion lulus**, termasuk pembatasan peran/KPwBI, duplikasi master, konflik versi, snapshot, narasumber server, institusi nonaktif, pemetaan/cakupan mitra, dan transaksi audit. Browser memeriksa master → sesi dengan narasumber master → peserta tanpa akun → L1 → pemetaan/status → respons tetap tersimpan, pada lebar 375/768/1440 px.

SQLite dan Chromium lokal telah diuji. MariaDB/Hostinger, konkurensi aktual dan beban 1.500 pengguna belum diuji. Hasil rinci ada di [QA.md](QA.md).

Materi PDF privat kini tersedia; lihat [MATERIALS.md](MATERIALS.md). Instrumen L1 kini tersedia; lihat [INSTRUMENTS.md](INSTRUMENTS.md). L2 kini tersedia; lihat [L2.md](L2.md). Analisis L2 kini tersedia; lihat [ANALYTICS.md](ANALYTICS.md). Berikutnya impor historis, filter L1 dan laporan server. Panduan awam tetap berupa sketsa Markdown; finalisasi PDF dilaksanakan belakangan.
