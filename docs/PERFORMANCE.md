# Antrean akses dan pengujian kapasitas LENTERA

Tanggal pembaruan: 17 Oktober 2026.

LENTERA menyediakan ruang tunggu untuk membatasi banyaknya request yang masuk ke Laravel pada saat yang sama. Peserta yang belum mendapat giliran melihat status antrean dan dapat bermain tic-tac-toe sambil menunggu. Fitur ini merupakan pengendalian beban awal; kapasitas 1.000–1.500 pengguna bersamaan masih harus dibuktikan pada hosting tujuan.

## Batas hosting yang perlu diperiksa

Paket yang disampaikan pengguna adalah Hostinger Unlimited: 2 CPU, RAM 3 GB dan penyimpanan 50 GB. Dokumentasi publik Hostinger juga mencantumkan 60 PHP workers dan 75 koneksi database per pengguna untuk Unlimited. Cocokkan seluruh angka dengan paket aktual di hPanel karena nama paket dan batas akun dapat berbeda atau berubah. Catat juga I/O, inode, batas proses, waktu eksekusi, versi PHP CLI/web dan MariaDB serta pemakaian website lain dalam akun yang sama. [Parameter resmi Hostinger](https://www.hostinger.com/support/6976044-parameters-and-limits-of-hosting-plans-in-hostinger/).

Jumlah pengguna yang sedang membuka halaman berbeda dengan jumlah request PHP yang sedang berjalan. Seribu peserta membaca materi statis dan seribu peserta mengirim jawaban pada detik yang sama menghasilkan beban yang berbeda. Nilai kapasitas antrean di bawah adalah parameter eksperimen, bukan konversi dari RAM atau jumlah CPU.

## Cara kerjanya

1. Semua request yang masuk melalui `public/index.php` melewati pemeriksaan ringan sebelum Composer, Laravel, session dan database dijalankan. Halaman BI, mitra, peserta, PDF privat serta endpoint kesehatan ikut dibatasi.
2. Request memperoleh slot apabila tersedia giliran dan jatah mulai. Jika belum tersedia, server menerbitkan tiket acak yang terikat pada cookie browser, metode HTTP dan tujuan request. Antrean menyimpan hash pengikat tersebut, slot dan masa berlaku; isian identitas, jawaban dan berkas tidak disimpan dalam antrean.
3. Tiket yang menunggu mendapat reservasi sesuai urutan masuk. Pembatas jumlah request aktif bekerja bersama pembatas laju pemberian giliran; pengunjung baru tidak boleh mendahului tiket yang sudah menunggu.
4. Browser memeriksa giliran melalui `public/admission.php`. Endpoint ini memakai PHP ringan tanpa bootstrap Laravel, session database atau query aplikasi. Pemeriksaan berikutnya diberi jeda dan variasi waktu acak agar browser tidak serentak melakukan polling.
5. Setelah mendapat giliran, request melewati validasi, izin, jadwal dan transaksi aplikasi seperti biasa. Slot ditahan sampai request selesai, termasuk respons streaming. Antrean tidak memberikan hak akses kelas atau mengubah aturan evaluasi.

HTML ruang tunggu, CSS, JavaScript, logo dan permainan adalah aset statis yang tetap dapat dilayani tanpa melewati Laravel jika konfigurasi web server benar. Permainan berjalan di browser, tanpa permintaan jaringan tambahan, papan peringkat atau keuntungan posisi antrean. Peserta dapat memilih memainkannya; permainan tidak wajib diselesaikan untuk melanjutkan.

Slot aktif memakai kunci berkas sistem operasi, sedangkan pembaruan urutan antrean memakai kunci singkat tanpa menunggu secara blocking. Permintaan yang tidak memperoleh kunci segera diminta mencoba lagi. Kunci aktif dilepas ketika request berakhir; tiket menunggu dan reservasi yang ditinggalkan dibersihkan ketika antrean diakses berikutnya. Tidak diperlukan root, Redis, Supervisor, cron khusus antrean atau daemon permanen. Dukungan dan batas `flock` bergantung pada runtime serta filesystem; penggunaan bersama harus mematuhi mekanisme kunci yang sama. [Dokumentasi PHP `flock`](https://www.php.net/manual/en/function.flock.php).

## Pengalaman peserta dan keamanan kiriman

- Saat membuka halaman yang ramai, peserta diarahkan ke ruang tunggu, kemudian dilanjutkan ke halaman tujuan setelah memperoleh giliran.
- Saat mengirim formulir dengan JavaScript aktif, isian tetap berada pada halaman yang sama selama menunggu. Status antrean muncul sebagai dialog. Pengiriman menggunakan `FormData`, termasuk tombol tindakan yang dipilih, sehingga tindakan L2 **Simpan draf** dan **Kirim final** tetap berbeda.
- Isian kiriman POST tidak ditambahkan ke `localStorage`, `sessionStorage`, URL ruang tunggu atau berkas antrean. Selama menunggu, data hanya berada dalam halaman browser; menutup atau memuat ulang halaman dapat menghilangkannya. Hindari menyegarkan halaman dan mengirim ulang dari beberapa tab. Ruang tunggu GET mempertahankan alamat tujuan beserta query aslinya dan tiket pada fragmen URL agar dapat dilanjutkan setelah reload; isian pencarian GET tetap mengikuti sifat URL halaman asal.
- Setelah mendapat giliran, browser mengirim formulir ke aplikasi. Pesan berhasil hanya boleh mengikuti konfirmasi aplikasi. Status **menunggu** atau **giliran tersedia** belum berarti jawaban tersimpan.
- Jika koneksi terputus setelah kiriman mungkin diterima aplikasi, browser tidak otomatis mengulang kiriman yang hasilnya belum jelas. Peserta perlu memeriksa status hasil pada kelas sebelum mencoba kembali. Perlindungan submission final ganda tetap bergantung pada transaksi dan constraint domain L1/L2 yang sudah tersedia.
- Tanpa JavaScript, POST yang ditolak antrean menampilkan pemberitahuan bahwa kiriman belum diproses dan petunjuk kembali ke formulir. Pemulihan isian pada navigasi kembali bergantung pada browser; pengiriman otomatis melalui ruang tunggu memerlukan JavaScript.
- Jadwal evaluasi tetap diperiksa berdasarkan waktu pemrosesan oleh server. Menekan tombol sebelum batas waktu lalu menunggu melewati batas waktu tidak otomatis memperpanjang jadwal. Pengelola perlu menyediakan kelonggaran waktu berdasarkan hasil uji antrean dan prosedur kegiatan.

Posisi adalah urutan tiket request yang belum selesai mendapatkan akses, bukan jumlah orang unik dan bukan estimasi menit yang dijamin. Beberapa tab dapat memiliki beberapa tiket. Identitas antrean menggunakan cookie acak, bukan alamat IP, agar peserta yang memakai jaringan kampus bersama tidak diperlakukan sebagai satu orang. Batas tiket per cookie membantu membatasi tab ganda, tetapi bukan perlindungan terhadap penyalahgunaan dengan cookie baru.

## Konfigurasi awal

Sumber pengaturan berada di [`config/admission.php`](../config/admission.php). Gerbang sebelum Laravel membaca salinan pengaturan privat yang diterbitkan melalui perintah Artisan. Mengubah `.env` saja belum mengubah gerbang yang sedang digunakan.

| Pengaturan | Nilai awal | Arti |
| --- | --- | --- |
| `LENTERA_ADMISSION_ENABLED` | `false` | Aktivasi eksplisit setelah kesiapan lingkungan diperiksa |
| `LENTERA_ADMISSION_CAPACITY` | `8` | Batas request aplikasi aktif, dengan reservasi giliran yang ikut memakai kapasitas |
| `LENTERA_ADMISSION_RATE` | `8` | Pengisian kembali jatah pemberian giliran per detik; burst awal sampai kapasitas |
| `LENTERA_ADMISSION_MAX_WAITING` | `2000` | Batas tiket yang tersimpan, termasuk tiket yang sudah mendapat reservasi tetapi belum digunakan |
| `LENTERA_ADMISSION_POLL_SECONDS` | `20` | Jeda dasar maksimum polling normal; tiket dekat depan dapat diperiksa mulai 2 detik, ditambah variasi waktu browser |
| `ticket_ttl` di konfigurasi | `180` detik | Masa berlaku tiket sejak aktivitas terakhir yang sah |
| `reservation_ttl` di konfigurasi | `30` detik | Waktu menggunakan giliran yang sudah dipesan sebelum reservasi ditinggalkan |

Ada batas enam tiket per cookie. Rentang konfigurasi yang diterima implementasi: kapasitas 1–32, laju 1–100 per detik, maksimal tiket 1–5.000, jeda polling dasar 5–60 detik, TTL tiket 60–600 detik, serta TTL reservasi 10–60 detik. Penyesuaian TTL dilakukan pada berkas konfigurasi, bukan variabel lingkungan terpisah.

Tab yang ditidurkan browser atau koneksinya hilang dapat kehilangan tiket/reservasi setelah kedaluwarsa dan harus mengantre kembali. Reservasi yang belum digunakan mengurangi throughput sementara; memilih TTL lebih panjang bukan otomatis lebih baik. Kapasitas juga bukan jaminan jumlah request selesai per detik: lama query, ukuran PDF, CPU dan I/O masih menentukan.

## Mengaktifkan dan memeriksa

Dari root project, setelah pengaturan lingkungan siap:

```powershell
php artisan config:cache
php artisan lentera:admission --enable
php artisan lentera:admission --status
```

Untuk deployment berulang, tetapkan `LENTERA_ADMISSION_ENABLED=true` pada lingkungan tujuan dan terbitkan pengaturan yang sudah di-cache:

```powershell
php artisan config:cache
php artisan lentera:admission
php artisan lentera:admission --status
```

`--enable` dan `--disable` mengubah salinan yang diterbitkan, bukan isi `.env`. `--status` menampilkan pengaturan yang diterapkan, bukan jumlah peserta aktif, kedalaman antrean atau hasil pengukuran kapasitas. Jalankan penerbitan konfigurasi secara berurutan dari satu proses deployment.

Untuk menghentikan pembatasan secara terkontrol:

```powershell
php artisan lentera:admission --disable
php artisan lentera:admission --status
```

Penonaktifan membiarkan request baru langsung menuju Laravel dan dapat meningkatkan beban secara mendadak. Gunakan saat lalu lintas sudah terkendali. Perintah mempertahankan state dan kunci request yang masih hidup. Jangan menghapus, mengganti atau memindahkan direktori antrean, berkas kunci maupun state ketika ada request aktif; tindakan itu dapat memisahkan kelompok kunci dan merusak pembatasan. Jangan menjalankan deployment paralel yang mengubah kapasitas.

Gerbang mempertahankan slot milik request lama ketika kapasitas diturunkan. Jumlah request yang telanjur aktif dapat sementara lebih besar daripada batas baru sampai request tersebut selesai; request baru menunggu kapasitas tersedia.

## Syarat deployment

- Document root hanya `public`. `storage/framework/admission`, `storage/framework/admission-config.php`, `.env`, source dan vendor harus berada di luar akses web. State dan konfigurasi terbit tidak masuk Git.
- Seluruh proses web aplikasi pada satu host harus melihat direktori antrean yang sama dan kunci berkas yang konsisten. CLI penerbit konfigurasi dan proses PHP web memerlukan izin sesuai tugasnya. Kode PHP aplikasi sebaiknya hanya dapat ditulis melalui deployment; batasi hak tulis runtime ke direktori yang diperlukan.
- Penyimpanan lokal satu host adalah asumsi implementasi ini. Dukungan banyak node, filesystem jaringan, NFS dan failover antarhost belum dibuktikan. Uji kunci lintas proses pada filesystem hosting aktual sebelum aktivasi.
- Web server harus melayani `admission.php` sebagai PHP dan aset ruang tunggu sebagai berkas statis. Periksa hasil deployment melalui HTTP; keberadaan berkas saja belum membuktikan aturan rewrite dan PHP handler benar.
- Gunakan HTTPS. Cookie antrean bersifat `HttpOnly` dan `SameSite=Lax`; flag `Secure` mengikuti deteksi HTTPS oleh PHP. Jika ada proxy/CDN, pastikan PHP mengenali HTTPS dari konfigurasi server yang tepercaya dan verifikasi cookie melalui browser. Pengaturan session Laravel tetap perlu `SESSION_SECURE_COOKIE=true`.
- Kecualikan `admission.php`, respons antrean serta halaman/respons peserta dan akun dari cache publik atau aturan CDN yang memaksa cache. Respons gerbang memakai `private, no-store`. Cache aset statis dapat digunakan sesuai aturan deployment aset.
- Gerbang ringan berada di luar routing Laravel dan tidak bergantung pada route cache. Jangan memindahkan endpoint polling ke route Laravel atau mengarahkannya ke bootstrap Laravel melalui rewrite. `route:cache` tidak menggantikan langkah penerbitan konfigurasi antrean.
- Semua jalur ke aplikasi harus melalui gerbang yang sama. Jangan menyediakan front controller alternatif, bypass untuk admin atau endpoint kesehatan tanpa mempertimbangkan kapasitas yang dilewati. Endpoint kesehatan Laravel saat ini dapat ikut mengantre; bedakan pemeriksaan ketersediaan web statis dan kesehatan aplikasi.

## Batas perlindungan

Gerbang membatasi pekerjaan aplikasi sebelum session/query database, tetapi polling tetap memakai PHP worker dan operasi filesystem. Dengan 1.500 browser serta interval dasar 20 detik, perhitungan sederhana menghasilkan sekitar **75 polling per detik** sebelum variasi waktu, tiket dekat depan, retry dan request aplikasi diperhitungkan. Ini perkiraan aritmetika, bukan hasil benchmark. Ukur biaya polling dan persaingan kunci; jangan memperpendek interval hanya agar angka posisi terlihat lebih sering berubah.

Web server dan PHP dapat menerima atau mengurai body unggahan sebelum kode gerbang berjalan. Karena itu, antrean tidak menghapus biaya jaringan, penerimaan unggahan, worker polling, TLS atau batas proses hosting. Unduhan PDF privat yang lama juga dapat menahan slot. Satu query mahal tetap dapat membebani CPU/database meski jumlah request aktif kecil.

Antrean ini tidak memprediksi server akan down berdasarkan CPU/RAM, tidak melindungi seluruh layanan saat web server/hosting sudah tidak dapat menerima request, dan bukan mitigasi DDoS di lapisan jaringan. Jika beban melampaui kapasitas layanan web/PHP sebelum gerbang, ruang tunggu statis dan polling pun bisa tidak tersedia. Penyimpanan antrean gagal akan menolak masuk dengan status sementara tidak tersedia, bukan membebaskan seluruh request tanpa batas.

Kecepatan masuk dapat sengaja lebih rendah daripada kecepatan kedatangan. Akibatnya waktu tunggu bertambah dan antrean akhirnya penuh. Tidak ada janji semua peserta selesai sebelum jadwal tutup. Pisahkan jadwal pekerjaan berat, ukur kebutuhan nyata, lalu sesuaikan batas, pola sesi atau kapasitas hosting berdasarkan hasil tersebut.

## Pemeriksaan lokal dan uji beban

Pemeriksaan otomatis yang disediakan pada tahap ini:

```powershell
php artisan test
php tests/admission-concurrency.php
node tests/admission-browser.cjs
```

Perintah PHP memakai runtime lokal yang dijelaskan dalam [BACKEND.md](BACKEND.md). Tes browser memerlukan aplikasi demo lokal, antrean aktif, PHP CLI, serta Playwright/Chromium. `LENTERA_BASE_URL` memilih server lokal; `LENTERA_PHP` memilih executable PHP jika tidak ada di PATH; `LENTERA_PLAYWRIGHT_PATH` dapat menunjuk paket Playwright portabel. Script menahan slot sementara melalui proses CLI terpisah, lalu melepasnya; jangan jalankan pada produksi. Pengujian harus memakai data sintetis. Rincian hasil eksekusi terakhir dicatat pada [QA.md](QA.md); tersedianya script atau lulusnya tes lokal belum merupakan bukti uji hosting 1.500 pengguna.

Rencana uji pada lingkungan staging dengan MariaDB dan konfigurasi hosting sebenarnya:

| Tahap | Skenario | Bukti yang dikumpulkan |
| --- | --- | --- |
| Dasar | Alur lengkap satu peserta: QR, formulir, materi, L1, pre/post L2 | Waktu aplikasi tanpa antrean, request per alur, jumlah query, ukuran respons dan konsistensi final |
| Bertahap | 200 → 500 → 1.000 → 1.500 browser virtual dengan waktu baca/jawab yang realistis | Laju kedatangan/penyelesaian, kedalaman antrean, waktu tunggu median/p95/p99, latensi aplikasi setelah masuk |
| Lonjakan | Jumlah yang sama membuka QR lalu mengirim pada rentang waktu pendek | Slot maksimum, penolakan sementara, antrean penuh, beban polling, kemampuan antrean menyusut setelah lonjakan |
| Campuran | Peserta mengirim bersamaan dengan BI membuka analisis dan mengunduh PDF privat | Dampak request panjang terhadap slot, CPU, RAM, PHP workers dan query/koneksi MariaDB |
| Gangguan | Putus koneksi, tab tidur/ditutup, proses berhenti, klik ganda, tiket kedaluwarsa, retry setelah respons hilang | Tidak ada slot permanen tertinggal, urutan yang masuk akal, final tidak ganda dan kiriman yang belum pasti tidak diulang diam-diam |
| Batas waktu | Peserta menunggu melewati batas jadwal L1/L2 atau masa session | Penjelasan yang benar, validasi server tetap berlaku dan prosedur pengelola saat peserta tertahan |
| Kondisi storage | Izin storage salah atau state rusak pada lingkungan uji terisolasi | Aplikasi menahan akses dengan pesan gagal sementara, log tanpa data peserta dan pemulihan terkontrol |

Generator beban harus mengikuti cookie, tiket, interval polling dan alur form seperti browser asli. Tes yang hanya mengirim GET berulang ke halaman depan tidak mewakili pengisian kelas. Uji konkurensi PHP lokal memeriksa mekanisme kunci lintas proses; server pengembangan PHP satu proses tidak mewakili pool worker hosting.

Catat **waktu menunggu antrean** terpisah dari **waktu pelayanan setelah mendapat giliran** dan waktu total sampai konfirmasi. Pantau HTTP 5xx/timeout, HTTP 429/202 yang disengaja antrean, panjang antrean, keberhasilan final, latensi query/lock database, koneksi, CPU, RAM, worker, I/O serta ukuran/log state. Rekonsiliasi jumlah peserta sintetis, kepesertaan, respons final dan skor sebelum/sesudah uji; tidak boleh ada respons final hilang atau berlipat karena retry.

Tetapkan batas keberhasilan dan penghentian uji sebelum menjalankan setiap tahap, termasuk toleransi p95 waktu tunggu sesuai durasi kegiatan. Jangan menaikkan tahap ketika database mulai gagal, kapasitas hosting tersaturasi berkelanjutan atau integritas jawaban tidak sesuai. Pilih angka konfigurasi dari hasil pengukuran dan ulangi skenario lonjakan setelah perubahan. Hasil final perlu menyebut tanggal, paket aktual, versi aplikasi, parameter antrean, jumlah pengguna, campuran kegiatan dan durasi uji.

## Pekerjaan berikutnya

Belum tersedia: metrik antrean historis/dashboard operator, penyesuaian otomatis berbasis CPU/latensi, ruang tunggu di lapisan CDN/edge, koordinasi banyak host, pembuktian filesystem Hostinger, uji beban MariaDB/1.500 pengguna dan prosedur kegiatan ketika batas evaluasi terlewati. Impor historis serta ekspor laporan server juga masih memerlukan pemrosesan bertahap dan pengukuran tersendiri. Jalur antrean HTTP ini tidak menggantikan database queue untuk pekerjaan latar.
