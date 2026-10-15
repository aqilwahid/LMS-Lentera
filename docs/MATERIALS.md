# Materi PDF privat dan riwayat versi

Dokumentasi diperbarui 16 Oktober 2026; implementasi dan pengujian tahap ini dilakukan 15 Oktober 2026. Peserta tetap bergabung melalui QR tanpa akun. Materi terikat pada satu sesi.

## Fitur yang tersedia

Pengelola BI sesuai KPwBI dapat membuka **Kelola kegiatan → Atur sesi → Kelola materi PDF**, menambahkan judul/keterangan dan PDF, mengunggah versi baru, menerbitkan versi yang dipilih, menyembunyikan materi, serta mengunduh versi lama untuk pemeriksaan.

PDF baru disimpan sebagai versi belum terbit. Penerbitan dilakukan terpisah dan eksplisit. Mengunggah versi berikutnya tidak mengganti versi yang sedang dilihat peserta. Versi lama tetap tersimpan; pengelola dapat menerbitkan kembali versi lama jika diperlukan.

Peserta yang sudah mendaftar pada browser yang sama, dengan kepesertaan dan QR aktif, mendapat daftar materi terbit di halaman kelas. Tersedia **Baca materi**, pratinjau menggunakan pembaca PDF browser, **Buka PDF di tab baru**, serta **Unduh PDF**. Membuka materi tidak otomatis menyelesaikan tugas atau menjadi syarat L1.

Mitra belum mendapat akses PDF; pembagian materi ke institusi mitra memerlukan aturan tersendiri.

## Penyimpanan dan model

- materials menyimpan sesi, judul/keterangan, status terlihat, current_version_id dan revision.
- material_versions menyimpan nomor versi, lokasi privat acak, nama berkas asli yang dibersihkan, ukuran, SHA-256, petugas, waktu dan kunci retry.
- Disk materials memakai storage/app/private/materials secara eksplisit, bukan disk public dan bukan lokasi yang dipilih dari request. serve=false. Tidak memakai storage:link atau URL berkas statis.
- File fisik menggunakan UUID.pdf. Nama asli tidak digunakan sebagai path atau nama header unduhan. Header unduhan dibuat dari judul yang dibersihkan dan nomor versi.
- Setiap versi mempunyai file terpisah. Model menolak penimpaan/penghapusan metadata versi; nomor versi unik per materi. Editor judul/keterangan setelah pembuatan, penghapusan, retensi dan pemindahan materi antar-sesi belum disediakan.
- File privat, database lokal dan screenshot QA diabaikan Git; workflow Pages tetap hanya membawa prototype/aset yang diizinkan.

## Validasi dan batas unggahan

LENTERA_MATERIAL_MAX_KB menetapkan batas aplikasi; default 10240 KiB. Batas efektif adalah nilai terendah antara konfigurasi tersebut, upload_max_filesize PHP dan post_max_size PHP dengan ruang untuk field formulir. Nilai efektif ditampilkan pada formulir. Pada runtime lokal bawaan saat tahap ini, upload_max_filesize masih 2M sehingga batas efektif sekitar 2 MB; mengubah flag aplikasi saja tidak menaikkan batas PHP.

Server memeriksa upload yang sah, ekstensi PDF, MIME PDF, ukuran dan header %PDF-. Validasi ini bukan parser penuh PDF, pemeriksaan antivirus, atau penghapusan JavaScript/lampiran di dalam PDF. Unggahan hanya diperbolehkan bagi BI berwenang; pemeriksaan konten dan pemindai tambahan belum diimplementasikan.

Jika PHP/hosting menolak body sebelum aplikasi berjalan, respons dapat berasal dari PHP/web server. Versi PHP, folder sementara yang writable, batas upload/post, ruang disk dan perilaku error perlu diperiksa pada akun hosting aktual.

## Otorisasi baca/unduh

- Pengelola BI harus aktif dan berada pada KPwBI kegiatan. Versi yang diunduh harus benar-benar milik materi yang diminta.
- Peserta harus mempunyai rahasia akses sesi browser dan kepesertaan aktif untuk sesi itu. QR saja, email atau enrollment_id dari request tidak memberi akses.
- Materi harus terbit; endpoint peserta hanya melayani versi saat ini. Versi belum terbit dan versi lama tidak mempunyai URL unduh peserta. Link pada pratinjau menyertakan ID versi yang sedang ditampilkan; jika versi terbit sudah berganti, server menolak dengan 409 agar label dan file tidak diam-diam berbeda.
- QR nonaktif/berganti atau kepesertaan dicabut menghentikan akses melalui tautan lama. Penutupan pendaftaran tidak mencabut peserta yang sudah bergabung; membaca materi mengikuti QR/kepesertaan, bukan jadwal L1.
- Respons file mempunyai Content-Type application/pdf, disposition inline/attachment sesuai tindakan, X-Content-Type-Options nosniff, Referrer-Policy no-referrer dan Cache-Control private, no-store. Data file dikirim melalui stream.
- Halaman daftar/detail pengelola, pratinjau dan halaman kelas juga tidak boleh di-cache publik.
- Menyembunyikan materi menghentikan permintaan berikutnya melalui aplikasi. Salinan yang sudah diunduh atau dibuka pada perangkat peserta tidak dapat ditarik kembali.

## Retry, transaksi dan audit

Formulir unggah mempunyai nonce UUID. Server membentuk kunci HMAC menurut petugas dan target sesi/materi. Retry nonce yang sama dengan SHA-256 yang sama mengembalikan materi/versi sebelumnya tanpa membuat versi ganda. Nonce sama dengan isi PDF berbeda ditolak; muat ulang formulir untuk permintaan baru. Nonce baru dianggap tindakan unggah baru, termasuk jika isinya sama.

Unggah versi baru, penerbitan dan penyembunyian memeriksa revision dan row lock. Konflik formulir lama ditolak. Penomoran versi, metadata dan audit disimpan dalam transaksi yang sama. Kegagalan DB/audit yang tertangkap membersihkan file baru; file sementara dari retry yang tidak digunakan juga dibersihkan.

File system dan database bukan satu transaksi atomik. Proses yang berhenti paksa sebelum commit dapat meninggalkan file tanpa metadata; rekonsiliasi/pembersihan orphan serta retensi otomatis belum tersedia. Backup harus mencakup database dan storage privat, kemudian diuji pemulihannya bersama.

Audit mencatat unggahan, nomor versi/ukuran/hash, penerbitan dan penyembunyian. Lokasi file, kunci retry, isi PDF dan identitas peserta tidak dimasukkan ke payload audit materi.

## Menjalankan dan memeriksa

```powershell
php artisan migrate
powershell -ExecutionPolicy Bypass -File scripts/serve-local.ps1
php artisan test
php vendor/bin/pint --test
php artisan view:cache
node tests/materials-browser.cjs
```

Script Windows sekarang memakai artisan serve --no-reload agar TEMP/TMP tetap diteruskan ke proses PHP. Ini memperbaiki kegagalan upload sementara pada server lokal. Perubahan .env memerlukan restart server manual. Pada hosting gunakan konfigurasi web PHP/cron yang nyata, bukan server development ini.

Browser test memerlukan demo aktif, Playwright dan Chromium. Tes menghasilkan dua PDF sintetis kecil, menambah sesi/pendaftaran/materi/versi/audit, serta menguji publikasi, retry, byte unduhan, sembunyikan dan terbit ulang. Tidak mengunggah dokumen klien. Artefak ada di tests/outputs/materials.

Suite lokal: **58 tes, 525 assertion lulus**; batas validasi, role/KPwBI, sesi browser, materi draft/tersembunyi, versi asing, retry, file hilang dan rollback audit diperiksa. Hasil browser terbaru serta batas pembuktian rendering PDF dicatat di [QA.md](QA.md).

## Berikutnya

Pengelolaan instrumen dan penilaian L2, lalu impor/laporan dan pengujian integrasi MariaDB/hosting. Pemulihan akses peserta, pemindai PDF, rekonsiliasi file, retensi/arsip dan kebijakan berbagi materi mitra masih terpisah. Kapasitas 1.500 pengguna belum diuji. Panduan final/PDF panduan tetap dikerjakan belakangan; materi kelas ini berbeda dari finalisasi panduan.
