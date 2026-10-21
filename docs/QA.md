# Verifikasi impor respons L1 historis, ekspor CSV, dan filter periode L1 — 21 Oktober 2026

- **121 tes PHPUnit, 1.236 assertion lulus**, menggunakan SQLite in-memory yang terpisah dari database demo.
- Filter periode L1 pada Dashboard (`LearningFlowTest::test_dashboard_period_filter_is_inclusive_validated_and_applies_to_csv`):
  - Penyaringan tanggal awal (`from`) dan tanggal akhir (`to`) secara inklusif menurut zona waktu aplikasi (awal hari `00:00:00` s.d. akhir hari `23:59:59`).
  - Validasi ketat format `Y-m-d` dan aturan `after_or_equal:from` untuk mencegah rentang terbalik.
  - Nilai filter tanggal dipertahankan pada tautan paginasi (`withQueryString()`) dan nama berkas serta isi ekspor CSV.
- Impor respons L1 historis (`HistoricalImportTest`):
  - Alur staging pada tabel `import_rows`: pembacaan berkas CSV (pemisah `;` atau `,`, maks 2 MB / 2.000 baris) dengan pratinjau status per baris (siap, institusi belum dipetakan, duplikat, tidak valid, baris ringkasan).
  - Pemetaan alias institusi manual yang disimpan di `institution_aliases` untuk dipakai pada impor selanjutnya tanpa pencocokan otomatis yang berisiko.
  - Penerapan idempoten dalam satu transaksi: membuat kepesertaan dan respons final L1 dengan aman; penerapan ulang tidak menggandakan respons.
  - Deteksi dan pencegahan duplikasi: penolakan berkas identik berdasarkan hash SHA-256 dan deteksi duplikat pasangan NIM + email.
  - Pencatatan riwayat audit lengkap pada setiap tahapan impor (`import.staged`, `import.alias`, `import.applied`).
- Fitur ekspor CSV ditambahkan melalui `Controller::csv()` menggunakan response streaming `streamDownload()`:
  - Ringkasan L1: `GET /beranda?format=csv` mengekspor judul kegiatan, ID sesi, ID instrumen, isian institusi peserta, jumlah respons, nilai rata-rata, waktu respons pertama, dan waktu respons terakhir.
  - Analisis L2: `GET /analisis/l2?format=csv` mengekspor nama kegiatan, sesi, isian institusi peserta, nama master institusi (atau penanda belum dipetakan), nama instrumen, versi, jumlah pretest final, post-test final, pasangan sah, rata-rata pretest pasangan, rata-rata post-test pasangan, dan selisih poin persentase post-pre.
- Format lokal Indonesia & kompatibilitas Microsoft Excel:
  - Pemisah kolom titik koma (`;`) dan format desimal menggunakan tanda koma (`,`).
  - Dilengkapi awalan UTF-8 Byte Order Mark (`\xEF\xBB\xBF`) agar Excel di lingkungan Windows/Office langsung mengenali karakter UTF-8 tanpa teks berantakan.
- Pencegahan serangan injeksi formula spreadsheet (CSV formula injection):
  - Isian teks dari peserta yang diawali karakter formula (`=`, `+`, `-`, `@`, `\t`, `\r`) secara otomatis dinetralkan dengan prefiks kutip tunggal (`'`).
- Penegakan izin dan isolasi cakupan akun:
  - Mitra hanya mengekspor data yang menjadi hak institusinya dan dari sesi yang publikasinya telah dibuka oleh pengelola BI.
  - Sesi yang belum dipublikasikan, data institusi lain, nama peserta, nomor identitas, dan email tidak dibocorkan ke dalam berkas CSV.
  - Filter format yang tidak didukung (misalnya `format=xlsx`) divalidasi dan ditolak dengan pesan kesalahan.
- Tombol **Cetak / simpan PDF** pada tampilan analisis L2 memanfaatkan kapabilitas cetak bawaan browser (`window.print()`) dengan layout cetak yang rapi.
- Pint `--test` lulus, kompilasi Blade `php artisan view:cache` lulus tanpa error.

---

# Verifikasi pengelolaan akun dan pemulihan kata sandi — 17 Oktober 2026

- **117 tes PHPUnit, 1.192 assertion lulus**, menggunakan SQLite in-memory, termasuk suite pengujian `AccountManagementTest`.
- Pengujian mencakup pembuatan akun staf BI dan mitra oleh pengelola, pengikatan peran dan institusi aktif dalam kantor perwakilan (KPwBI), pengiriman tautan token penetapan kata sandi via notifikasi email, serta pencatatan audit lengkap (`account.created`, `account.updated`, `account.reset_sent`, `account.password_reset`).
- Penegakan izin dan cakupan wilayah: mitra dan peserta ditolak 403 saat mencoba mengakses rute manajemen akun. Pengelola kantor perwakilan tidak dapat melihat atau mengubah akun milik kantor lain (404).
- Keamanan penonaktifan akun: penonaktifan akun memblokir autentikasi login, menghapus sesi aktif di tabel `sessions` (pada driver database), serta mencegah pengelola menonaktifkan akunnya sendiri agar tidak terjadi penguncian akses.
- Pemulihan mandiri kata sandi: permohonan tautan reset menerapkan anti-enumerasi (mengembalikan respons sukses seragam untuk email terdaftar, email asing, maupun email peserta), pembatasan frekuensi (throttling), verifikasi token, penolakan token peserta/tidak valid, pemutusan sesi aktif lama, serta validasi standar kata sandi minimal 12 karakter memuat huruf dan angka.
- Pemeriksaan Pint, kompilasi template Blade (`php artisan view:cache`), dan sintaks PHP lulus bersih.

---

# Verifikasi antrean akses — 17 Oktober 2026

- **112 tes PHPUnit, 1.158 assertion lulus**, termasuk sembilan tes gerbang dan tiga tes formulir asinkron baru. Database fitur tetap SQLite in-memory, terpisah dari demo.
- Tes mencakup slot aktif, FIFO reservasi, tiket/slot ditinggalkan, kedaluwarsa, cancel, batas tiket, tiket salah cookie/metode/tujuan, replay, token bucket, penurunan kapasitas dengan request lama, kunci nonblocking, state rusak yang menolak akses, serta pembatasan alamat tujuan lokal.
- Tes formulir membuktikan pendaftaran/L1 asinkron tetap menyimpan satu hasil, pesan berhasil/session login tetap tersedia, dan validasi/izin tetap ditegakkan server.
- `php tests/admission-concurrency.php` lulus: 48 admissions pada 12 proses PHP terpisah, puncak tiga request aktif untuk batas tiga. Proses yang dihentikan paksa melepas slot langsung tanpa menunggu TTL. Ini uji mekanisme kunci, bukan uji kapasitas aplikasi/hosting.
- Chromium `tests/admission-browser.cjs` lulus pada gerbang aktif: slot ditahan oleh proses CLI sintetis → formulir belum dikirim → posisi antrean/permainan → cancel dengan isian utuh → lanjut setelah slot dilepas → pendaftaran hanya satu POST → L1 antre/cancel/kirim → konfirmasi dan reload. Handler Alpine tidak mengunci tombol pada cancel. Antrean GET mempertahankan tiket dan tujuan setelah reload.
- Permainan diperiksa pada lebar 375/768/1.440 px tanpa overflow; screenshot mobile ditinjau. Tidak ada error JavaScript. Endpoint ringan menolak content type sederhana (415) dan Origin lain (403).
- Regresi `tests/l2-browser.cjs` lulus dengan antrean aktif: authoring/penerbitan/assignment → QR → draf pretest → final pre/post → publikasi → skor 66,67/100 dan 100,00/100, selisih 33,33 poin persentase → isolasi V2. `formaction` Kirim final, retry, revisi lama, CSRF dan kunci yang tidak dirender tetap terjaga.
- Pint --test, kompilasi Blade, sintaks PHP/JavaScript dan git diff --check lulus. Tidak mengubah dependency atau bundle Vite; CSS/JS ruang tunggu berada langsung di `public`. CI ditambah uji konkurensi terisolasi; CI GitHub belum dijalankan pada tahap lokal ini.
- Satu percobaan browser awal timeout menunggu posisi L1 saat pemeriksaan lain berjalan; pengulangan penuh dengan diagnostik lulus. Tidak dihitung sebagai bukti uji beban. Artefak sintetis ada di `tests/outputs/admission`, diabaikan Git; tes menambah sesi, kepesertaan, respons dan audit demo.

**Batas:** belum menguji filesystem/PHP workers/MariaDB Hostinger, beban 200–1.500 pengguna, NFS/banyak node, pemantauan CPU otomatis, mitigasi serangan jaringan, Safari/Firefox atau pembaca layar. Polling tetap memakai PHP, dan gate tidak menjamin hosting tidak down. Nilai awal delapan slot/delapan giliran per detik harus ditentukan ulang dari uji staging. Detail aktivasi, deadline evaluasi, isian belum tersimpan dan rencana uji ada di [PERFORMANCE.md](PERFORMANCE.md).

---

# Verifikasi analisis L2 BI/mitra — 16 Oktober 2026

- **100 tes PHPUnit, 1.090 assertion lulus**, memakai SQLite in-memory. Tidak ada migrasi atau dependency baru pada tahap ini.
- Tambahan tes mencakup rata-rata berbobot tiap pasangan, filter tanggal/hari penuh, pretest sebelum periode, hasil nol/selisih negatif, draf/tanpa pasangan/urutan terbalik/jumlah soal atau versi berbeda, konteks enrollment tidak sesuai sesi, cakupan KPwBI/institusi, publikasi mitra, pemetaan yang diaudit, histori kepesertaan nonaktif, filter yang dimanipulasi, kombinasi sesi/kegiatan, default tahun, kondisi kosong dan pagination kelompok.
- Filter ID institusi dinormalkan menjadi integer sebelum membandingkan COALESCE, sehingga ID dari URL bekerja konsisten di SQLite. Perilaku MariaDB aktual belum diverifikasi.
- Aturan pasangan disamakan pada analisis, ringkasan pengaturan sesi dan selisih peserta: pretest harus final sebelum post-test dimulai, dengan versi/jumlah soal yang sama. Tes urutan terbalik memastikan skor individu tetap ada tanpa menampilkan selisih berpasangan.
- Chromium menguji BI → sesi baru → QR peserta → pre/post final → analisis dengan filter → sesi belum terbit tidak tampak pada mitra → publikasi → agregat mitra institusi sendiri. Filter institusi yang dimanipulasi ditolak 422. Nama/nomor identitas/email peserta, jawaban, snapshot/kunci dan secret tidak dirender pada halaman analisis.
- Halaman mitra pada 375, 768 dan 1440 px tidak memiliki overflow horizontal. Screenshot mobile diperiksa, termasuk hasil 0,00 yang sah. Tidak ada error JavaScript pada alur browser.
- Label publikasi diperjelas menjadi **Buka skor peserta dan agregat mitra**, beserta dampaknya pada kedua peran. Browser test L2 yang lama disesuaikan dengan label ini.
- Pint --test, kompilasi Blade, sintaks test JavaScript, pemeriksaan tautan dokumentasi dan git diff --check lulus. Tidak mengubah aset CSS/JS Vite; memakai komponen/build V2 yang sudah tersedia.
- Browser test baru: tests/l2-analytics-browser.cjs. Memerlukan instrumen sintetis terbit dari tests/l2-browser.cjs. Setiap run menambah sesi, kepesertaan, pre/post dan audit sintetis; artefak di tests/outputs/l2-analytics diabaikan Git.

Batas: SQLite/Chromium lokal, bukan bukti konkurensi MariaDB, query plan data besar atau kapasitas 1.500 pengguna. Ekspor server, grafik tahunan, impor historis, filter L1, Safari/Firefox, pembaca layar dan kebijakan publikasi mitra terpisah belum tersedia/diverifikasi. Definisi periode, denominator, pasangan dan cakupan ada di [ANALYTICS.md](ANALYTICS.md). Catatan di bawah merupakan riwayat tahap sebelumnya.

---

# Verifikasi pretest/post-test L2 — 16 Oktober 2026

- Migrasi l2_instruments/l2_assessments/l2_attempts berhasil pada SQLite lokal. Data instrumen/respons L1 yang sudah ada dipertahankan.
- **88 tes PHPUnit, 947 assertion lulus.** Tambahan mencakup soal/opsi/kunci, authoring dan publikasi versi, pemilihan KPwBI, nonce/retry, revisi tab lama, rollback audit, draf parsial/kosong, skor server dan pembulatan, final/retry setelah jadwal ditutup, prasyarat pretest opsional, isolasi browser/kelas, kepesertaan nonaktif, QR berganti, freeze assignment, snapshot/final immutable, serta constraint unik attempt.
- Menurunkan batas jumlah soal untuk authoring tidak membatalkan versi terbit atau mengubah penilaian dari snapshot lama; tercakup tes regresi.
- Chromium berhasil menguji authoring soal/kunci → terbit → penetapan jadwal/prasyarat → QR dan identitas tanpa akun → pretest, simpan draf dan reload → final pre/post → pembukaan skor → pasangan lengkap → versi baru dengan kunci berbeda. Skor sintetis 66,67/100 dan 100,00/100, selisih 33,33 poin persentase; versi kedua tidak mengubah hasil pertama.
- Kunci tidak dirender pada HTML peserta. Skor/selisih tidak dirender sebelum publikasi. CSRF salah ditolak 419; draf dengan revision lama ditolak 422; final retry mempertahankan hasil pertama. Browser lain diarahkan ke pendaftaran, tanpa membuka attempt sebelumnya.
- Form tes pada 375, 768 dan 1440 px tidak memiliki overflow horizontal; tinggi target pilihan minimal 44 px. Screenshot 375 px diperiksa dan tidak ada error JavaScript pada alur ini.
- Pint --test, kompilasi Blade, sintaks JavaScript test, pemeriksaan tautan dokumentasi yang diubah dan git diff --check lulus. Tidak mengubah dependency atau sumber aset CSS/JS Vite; memakai build lokal yang sudah tersedia.
- Browser test: tests/l2-browser.cjs. Setiap run menambah instrumen/versi, sesi, kepesertaan, attempt dan audit sintetis. Artefak di tests/outputs/l2 diabaikan Git.

Batas: tes memakai SQLite dan Chromium lokal, bukan MariaDB/Hostinger atau bukti kapasitas 1.500 pengguna. Draf L2 disimpan manual, belum autosave. Navigasi satu soal, tipe soal lain, timer, nilai lulus, retake, analisis L2 per institusi/periode, Safari/Firefox dan pembaca layar belum tersedia/diverifikasi. Panduan final/PDF tetap ditunda sesuai arahan pengguna. Rincian aturan awal ada di [L2.md](L2.md). Catatan di bawah adalah riwayat QA tahap sebelumnya.

---

# Verifikasi instrumen L1 — 16 Oktober 2026

- Migrasi seri/kepemilikan/revision/kunci retry berhasil pada SQLite lokal tanpa mengganti instrumen atau respons sebelumnya.
- **70 tes PHPUnit, 674 assertion lulus.** Tambahan mencakup pembuatan draf lima butir, payload identitas yang diabaikan, validasi teks/indeks/nonce, retry pembuatan, formulir lama, publikasi idempotent, versi baru, referensi bersama hanya baca, role/KPwBI, akun nonaktif, pemilihan sesi, L1 peserta tanpa akun, snapshot versi lama, serta rollback pada kegagalan audit untuk setiap operasi tulis instrumen.
- Chromium berhasil menguji draf → terbit V1 → pilihan instrumen pada sesi → QR dan pendaftaran tanpa akun → L1 → draf/ubah/terbit V2. Sesi dan konfirmasi peserta tetap memakai V1; V2 tersedia untuk sesi baru. Retry pembuatan seri/versi kembali ke catatan yang sama; formulir lama ditolak 422 dan CSRF tidak sah ditolak 419.
- Halaman versi terbit pada 375, 768 dan 1440 px tidak memiliki overflow horizontal. Screenshot 375 px diperiksa; tipografi/aset/komponen V2 tetap digunakan. Tidak ada error JavaScript dalam alur browser ini.
- Pint --test, kompilasi Blade, dan git diff --check lulus. Tidak ada perubahan dependency atau sumber CSS/JS Vite pada tahap ini; memakai build lokal yang sudah tersedia.
- Browser test baru: tests/instruments-browser.cjs. Artefak di tests/outputs/instruments diabaikan Git. Setiap run menambah instrumen/versi, sesi, kepesertaan, respons L1 dan audit sintetis.

Batas: SQLite dan Chromium lokal; belum menguji MariaDB/Hostinger, konkurensi nyata, beban 1.500 pengguna, Safari/Firefox, atau pembaca layar. Pengelolaan instrumen L1 tersedia; instrumen/penilaian L2 dan alur persetujuan redaksi belum tersedia. Panduan final tetap ditunda sesuai arahan pengguna. Rincian ada di [INSTRUMENTS.md](INSTRUMENTS.md). Catatan di bawah merupakan riwayat pemeriksaan tahap sebelumnya.

---

# Verifikasi materi PDF privat — 15 Oktober 2026

- Migrasi materials/material_versions berhasil pada SQLite lokal. File disimpan di disk privat di luar public.
- **58 tes PHPUnit, 525 assertion lulus.** Tambahan tes mencakup PDF draft/terbit/tersembunyi, hash, validasi file/ukuran/nonce, unggahan retry, versi baru yang tidak mengganti versi terbit otomatis, versi yang tidak sesuai, izin role/KPwBI/kelas, kepesertaan dicabut, QR nonaktif, file hilang, metadata immutable, serta pembersihan file dan rollback jika audit gagal.
- Chromium menguji sesi baru → unggah PDF sintetis → draft tidak dapat diakses peserta → terbit → pratinjau/unduh → versi kedua dan retry tanpa versi ketiga → terbit V2 → sembunyikan → terbitkan kembali. SHA-256 byte respons HTTP dan hasil unduhan browser cocok dengan PDF sumber uji.
- Permintaan unggah dengan token CSRF salah dan origin tidak dipercaya ditolak 419. Akses QR saja ditolak 403; materi belum terbit/tersembunyi ditolak 404. Link versi yang telah berganti ditolak 409, bukan diam-diam melayani file dengan versi berbeda.
- Halaman pratinjau pada 375, 768 dan 1440 px tidak memiliki overflow horizontal. Link tab baru/unduh tersedia. Screenshot native PDF pada mode headless tidak membuktikan rendering isi dokumen; uji pembaca PDF manual Chrome/Safari/Firefox dan perangkat fisik tetap diperlukan.
- Script Windows diperbaiki dengan --no-reload agar variabel TEMP/TMP diteruskan. Sebelum perbaikan, multipart gagal pada PHP startup karena temporary file tidak dapat dibuat. Setelah perbaikan, unggahan dan byte PDF berhasil diuji.
- Pint --test, Composer validate --strict dan kompilasi Blade lulus. Browser test baru: tests/materials-browser.cjs; artefak di tests/outputs/materials, diabaikan Git.

Batas: belum menguji MariaDB/Hostinger, beban unduhan 1.500 pengguna, antivirus/parser penuh PDF, rekonsiliasi orphan, retensi otomatis dan pemulihan backup. Batas unggahan mengikuti PHP aktual; runtime lokal masih membatasi sekitar 2 MB. Dokumen dan panduan ada di [MATERIALS.md](MATERIALS.md). Catatan di bawah merupakan riwayat pemeriksaan tahap sebelumnya.

---

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
