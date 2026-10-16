# Pengelolaan instrumen L1

Pembaruan 16 Oktober 2026. Tahap ini menyediakan pengelolaan instrumen L1; pretest/post-test L2 kini dijelaskan di [L2.md](L2.md).

## Alur pengelola

1. Masuk sebagai pengelola BI, lalu pilih **Instrumen L1**.
2. Pilih **Buat instrumen L1**, isi nama dan tepat lima butir teks, lalu **Simpan draf**.
3. Periksa atau ubah butir. Tekan **Simpan perubahan draf** jika ada perubahan.
4. Periksa pratinjau lima butir yang sudah tersimpan, lalu **Terbitkan versi 1**.
5. Buka **Kelola kegiatan → Tambah sesi/Atur sesi**, lalu pilih nama instrumen dan nomor versi yang sesuai.
6. Jika redaksi perlu diubah, buka versi terbit dan pilih **Buat draf versi baru**. Ubah, simpan, periksa, lalu terbitkan versi tersebut.

Penerbitan mengunci versi. Penerbitan versi baru **tidak otomatis mengganti** pilihan instrumen pada sesi lama. Nama sesi, narasumber dan instrumen sesi tetap dikunci setelah peserta bergabung atau respons L1 masuk. Gunakan sesi baru untuk konteks berbeda.

Peserta tetap membuka QR, mengisi identitas tanpa akun dan mengisi L1. Perubahan tahap ini tidak menambahkan login peserta atau mengubah cara menghitung nilai: jumlah lima jawaban dibagi lima, dengan nilai masing-masing 1–6.

## Model data dan cakupan

- `l1_instrument_sets`: satu seri instrumen milik satu KPwBI, nama tetap dan kode UUID yang dibuat server. Identitas seri tidak dapat diedit atau dihapus melalui model.
- `l1_instruments`: satu versi dengan lima butir, status draf/terbit, revision, dan kunci/hash pembuatan. Nomor versi dialokasikan server dalam transaksi dengan penguncian seri. Constraint unik kode+versi tetap menjadi pengaman database.
- Versi terbit tidak dapat diedit/dihapus melalui model. Draf hanya mengubah butir, bukan kode, nomor versi, seri, atau identitas retry. Menu hapus/arsip belum disediakan.
- Pengelola hanya dapat membaca/mengubah seri KPwBI sendiri. Mitra, akun participant lama, tamu, dan akun nonaktif tidak mendapat akses pengelolaan. Pemilihan instrumen pada sesi divalidasi ulang di server; manipulasi ID milik KPwBI lain ditolak.
- Instrumen dari instalasi sebelumnya memiliki seri kosong dan dipertahankan sebagai referensi bersama **hanya baca**, jika sudah terbit. Tidak ada pemindahan kepemilikan atau penulisan ulang snapshot. Pengelola dapat menyalin butir ke seri baru milik KPwBI sendiri.
- `L1-DEMO` tetap contoh sintetis. Salinan kerja awal berasal dari config/lentera.php. Redaksi dan label verbal skala resmi menunggu persetujuan. Menekan Terbitkan bukan pengesahan redaksi oleh BI.
- Respons final tetap menyimpan ID versi dan snapshot kode, nomor versi, kelima butir serta skala. Menerbitkan versi berikutnya tidak mengubah respons lama atau menyatukan agregat antarversi.

Instrumen referensi bersama merupakan kompatibilitas migrasi awal, bukan fitur pengelolaan instrumen nasional. Distribusi/persetujuan lintas KPwBI perlu ketentuan bisnis tersendiri. Nama seri yang salah dapat diperbaiki dengan membuat seri baru; perubahan nama/arsip yang diaudit belum disediakan.

## Integritas, retry dan audit

Pembuatan seri/draf dan pembuatan versi memakai nonce formulir yang di-HMAC dengan APP_KEY, akun, KPwBI, dan jenis operasi. Nilai HMAC memiliki constraint unik. Retry dari formulir yang sama kembali ke catatan pertama, termasuk jika draf itu kemudian diedit atau diterbitkan. Nonce pembuatan seri dengan isi berbeda ditolak; buka formulir baru untuk operasi baru. Formulir baru pada halaman versi terbit merupakan permintaan versi baru yang disengaja.

Pembuatan seri mengunci baris KPwBI untuk menserialkan retry sebelum seri ada. Pembuatan versi mengunci seri sebelum mengalokasikan nomor. Perubahan draf dan penerbitan mengunci versi dan memeriksa revision; tab lama ditolak agar isi terbaru tidak tertimpa. Retry penerbitan versi yang sudah terbit tidak mengganti waktu terbit atau menggandakan audit.

Validasi mewajibkan lima elemen berindeks 0–4, teks tidak kosong, maksimal 2.000 karakter per butir; nama maksimal 160 karakter. Publikasi memeriksa lagi isi yang sudah tersimpan. Skala dan jumlah butir tetap 1–6 dan lima, sesuai kebutuhan L1; tidak dapat diganti melalui payload.

Audit mencatat pembuatan draf, perubahan butir, penerbitan dan versi baru, dalam transaksi yang sama. Audit gagal membatalkan perubahan. Kunci retry tidak ditampilkan pada audit. Guard model tidak melindungi query builder/SQL langsung; perubahan administratif harus melalui alur aplikasi yang terkontrol.

## Menjalankan dan memeriksa

Pada instalasi lokal yang sudah ada, pertahankan .env, APP_KEY dan database, kemudian jalankan:

```powershell
php artisan migrate
php artisan optimize:clear
php artisan serve --host=127.0.0.1 --port=8000 --no-reload
```

Gunakan scripts/serve-local.ps1 jika memakai runtime Windows portabel yang sudah disiapkan. Perubahan tahap ini tidak menambah dependency atau mengubah aset Vite; build ulang hanya diperlukan bila aset lokal belum tersedia.

```powershell
php artisan test
php vendor/bin/pint --test
php artisan view:cache
node tests/instruments-browser.cjs
```

PHPUnit memakai SQLite in-memory. Browser test memerlukan server lokal dengan data demo aktif; menambah seri/versi, sesi, kepesertaan, respons dan audit sintetis. Test memeriksa draf, terbit, pilihan sesi, L1 peserta tanpa akun, versi baru, retry, CSRF, formulir lama, serta viewport 375/768/1440 px. Screenshot disimpan di tests/outputs/instruments, diabaikan Git. Hasil aktual dicatat di [QA.md](QA.md).

## Batas dan tahap selanjutnya

Belum tersedia: alur persetujuan redaksi, arsip/retensi, instrumen nasional, multi-narasumber, perubahan respons final, draf jawaban L1 server. L2 tersedia terpisah; lihat [L2.md](L2.md). MariaDB/Hostinger, konkurensi nyata dan beban 1.500 pengguna belum diuji.

Instrumen dan penilaian L2 per pertemuan kini tersedia, dengan versi soal/kunci, pretest/post-test, attempt, draf manual server, pengiriman final yang tahan retry, dan publikasi skor; lihat [L2.md](L2.md). Jenis soal/bobot, nilai lulus, timer, percobaan ulang serta pasangan pre/post harus dinyatakan sebagai aturan yang jelas sebelum digunakan pada kegiatan nyata.
