# Design System: LENTERA Management System

Versi 2.0 · 10 Oktober 2026 · Bahasa antarmuka: Indonesia

Redesain berdasarkan evaluasi versi pertama dan penerapan **UI UX Pro Max** dari `nextlevelbuilder/ui-ux-pro-max-skill`, commit sumber `50d8a7de0900119855614541f15a1a616691eb33`. Dokumen ini menggantikan arahan visual V1 untuk paket V2. Ketentuan bisnis, sumber, dan definisi data tetap mengikuti lampiran klien.

**Status:** spesifikasi dan 24 mockup raster untuk peninjauan; belum merupakan aplikasi interaktif atau proyek Google Stitch yang telah diperbarui. Font, warna, ilustrasi, dan susunan komponen adalah usulan desain, bukan pedoman merek resmi Bank Indonesia. PNG memperlihatkan arah visual; nilai token dan perilaku dalam dokumen menjadi acuan implementasi.

## 1. Visual Theme & Atmosphere

### 1.1 Arah baru

LENTERA tampil sebagai ruang belajar institusional yang jelas, lapang, dan berkarakter. Gunakan judul geometris tebal, permukaan putih, aksen biru laut, serta ilustrasi editorial mercusuar, buku, dan arsitektur. Kontras visual mengarahkan perhatian ke tindakan berikutnya atau satu angka utama. Hindari menjadikan setiap label sebuah kartu.

Peserta memakai navigasi atas yang sederhana; pengelola BI memakai sidebar berkelompok. Keduanya berbagi token, bentuk tombol, ikon, dan bahasa visual. Tampilan mitra memakai struktur pengelola yang lebih sederhana dengan penanda akses lihat.

| Area | Kepadatan / 10 | Variasi / 10 | Gerak / 10 | Komposisi |
| --- | --- | --- | --- | --- |
| Landing dan masuk | 3 | 6 | 2 | Split asimetris, ilustrasi editorial sebagai identitas |
| Beranda peserta | 4 | 6 | 2 | Materi unggulan + urutan belajar vertikal |
| Form dan tes | 4 | 3 | 1 | Fokus isi, instruksi ringkas, tindakan jelas |
| Dashboard BI | 6 | 5 | 2 | Angka utama dominan + tabel + kondisi data |
| Pengelolaan | 6 | 4 | 1 | Tabel/form utama + panel bantuan kontekstual |

### 1.2 Penerapan UI UX Pro Max

Pencarian awal `education learning platform institutional` menghasilkan gaya untuk pembelajaran anak; hasil tersebut ditolak karena tidak sesuai audiens. Pencarian terarah `corporate training LMS professional` menghasilkan **Accessible & Ethical**, pola **Trust & Authority**, dan palet navy/Slate. Bagian yang relevan diterapkan: keterbacaan, hierarki, navigasi, formulir, fokus, dan reduced motion. CTA penjualan, testimoni, harga, serta carousel dari rekomendasi generik tidak dibawa ke produk ini.

Pencarian tipografi `modern geometric sans professional` menghasilkan **Geometric Modern: Outfit + Work Sans**. Pasangan ini dipilih menggantikan Geist. Rekomendasi font serif dari pencarian sistem tidak digunakan untuk dashboard. Tidak ada framework implementasi yang diasumsikan karena keluaran tahap ini berupa gambar.

### 1.3 Ilustrasi dan hero

S01 memakai judul “Kenali peran BI. Perluas wawasan bersama LENTERA.” dan CTA “Masuk ke LENTERA”. Ilustrasi mercusuar biru-putih melambangkan arah belajar; cahaya emas kecil terbatas pada objek ilustrasi. S02 menggunakan ilustrasi yang sama sebagai kesinambungan. S04 memakai arsitektur bank dan buku sebagai ilustrasi materi. Ilustrasi bangunan adalah konsep editorial, bukan representasi resmi gedung BI atau kampus tertentu.

Teks dan ilustrasi memiliki zona baca terpisah. Pada mobile ilustrasi materi berada di atas judul/CTA. Form evaluasi dan tes tidak memakai ilustrasi besar yang menggeser pertanyaan. Ornamen lingkaran pada panel angka bersifat dekoratif, bukan grafik data.

## 2. Color Palette & Roles

| Token | Nilai | Peran |
| --- | --- | --- |
| canvas | `#F8FAFC` | Latar aplikasi terang |
| surface | `#FFFFFF` | Form, tabel, header, logo |
| surface-muted | `#F1F5F9` | Header tabel dan konteks sekunder |
| ink | `#0F172A` | Judul dan isi utama |
| text-secondary | `#475569` | Deskripsi dan metadata |
| text-muted | `#64748B` | Metadata ringkas; gunakan pada canvas/putih |
| border-subtle | `#E2E8F0` | Pemisah dekoratif |
| border-control | `#64748B` | Batas kontrol yang harus dikenali |
| accent | `#245D83` | Satu keluarga aksen interaktif |
| accent-hover | `#1D4C6B` | Hover/pressed |
| accent-soft | `#EAF1F6` | Pilihan aktif dan bantuan |
| feature-surface | `#0F172A` | Panel angka utama dan navigasi aktif |
| on-feature | `#FFFFFF` | Angka/judul pada panel gelap |
| feature-muted | `#CBD5E1` | Metadata pada panel gelap |

Putih pada accent sekitar 7,07:1; ink pada putih 17,85:1; secondary pada putih 7,58:1; muted pada canvas 4,55:1. Gunakan opacity penuh pada teks. Batas kontrol dan fokus memerlukan kontras sekurangnya 3:1; garis dekoratif tidak menggantikan batas kontrol. Implementasi perlu menguji hasil gabungan warna, bukan hanya token terpisah.

Logo mempertahankan warna aslinya. Emas tidak digunakan untuk CTA, nilai metrik, atau seri data. Kilau hangat pada ilustrasi dan motif panel hanyalah dekorasi; hilangkan bila mengurangi kontras. Status memakai teks/ikon dengan Slate atau biru, bukan makna warna saja. Untuk kondisi error, tulis sebab dan langkah perbaikannya secara eksplisit.

## 3. Typography Rules

**Outfit** 600–700 untuk judul dan angka utama; **Work Sans** 400–600 untuk isi, label, dan tabel. Gunakan angka tabular untuk kolom nilai. Font dalam PNG merupakan pendekatan visual; implementasi harus memuat berkas font yang benar. Fallback `Arial, sans-serif`.

| Peran | Desktop | Mobile | Aturan |
| --- | --- | --- | --- |
| Hero publik | 52–64 px / 1,05 | 32–40 px / 1,1 | Maksimal 3–4 baris, pembungkusan alami |
| Judul halaman | 32–40 px / 1,15 | 28–32 px / 1,15 | Tidak mendominasi form |
| Judul bagian | 22–26 px / 1,25 | 20–24 px / 1,25 | Semibold |
| Isi dan kontrol | 16 px / 1,5 | 16 px / 1,5 | Label nyata, bukan placeholder saja |
| Metadata | 13–14 px / 1,45 | 14 px / 1,45 | Jangan memuat instruksi penting sebagai teks kecil |
| Angka utama | 64–88 px / 1 | 56–72 px / 1 | Selalu dengan nama metrik, unit, dan periode |
| Tabel | 14–16 px / 1,5 | 16 px / 1,5 | Angka rata kanan dan digit tabular |

Skor ditulis `5,30 / 6`; angka 141 disebut respons, bukan otomatis peserta unik. Batasi paragraf sekitar 65 karakter per baris. Tidak memakai serif, teks gradien, kapital panjang, atau letter-spacing rapat pada isi.

## 4. Component Stylings

### 4.1 Navigasi

Header peserta setinggi 80 px desktop, logo LENTERA lebar sekitar 180–220 px, menu Beranda / Kegiatan saya / Materi / Evaluasi / Panduan, logo BI sebagai endorsement. Avatar peserta berupa ikon orang; huruf BI hanya untuk area pengelola. Logo ditampilkan memakai aset asli dalam implementasi, tanpa digambar ulang.

Sidebar BI lebar 232–256 px, putih, kelompok RUANG KERJA dan EVALUASI. Item aktif berupa bidang navy dengan teks putih, radius 12 px; target 48 px. Sidebar mitra hanya Ringkasan, Kegiatan, Evaluasi agregat, Panduan. Pada mobile gunakan menu untuk BI/mitra; peserta memakai empat tujuan bawah Beranda, Kegiatan, Evaluasi, Panduan. Materi dapat diakses dari sesi/menu sekunder. Jangan menempatkan bottom nav di atas isi formulir tanpa ruang cadangan.

### 4.2 Panel dan tombol

Panel memakai radius 24 px, hero 28–32 px, kontrol 12 px. Shadow lembut `0 8px 30px rgba(15,23,42,.04)` hanya untuk pemisahan lapisan. Form/tabel menggunakan garis pembagi dan ruang kosong; jangan menambahkan kartu dalam setiap sel.

Satu CTA utama per layar. Tombol kerja tinggi 48 px, tombol hero 52–56 px; action sekunder outline/teks. Hover mempergelap aksen; pressed memakai transform scale 0,98 tanpa memindahkan tata letak. Fokus 3 px terlihat jelas. Disabled memiliki label/alasan bila konteks memerlukan; tidak aktif secara semantik. Unduh hanya aktif bila berkas sudah tersedia.

### 4.3 Form, umpan balik, dan akses

Label di atas kontrol; jarak label-kontrol 8 px dan antarfield 20–24 px. Form lebar dapat memakai dua kolom bila tidak menyulitkan urutan baca. NIM/NIP bertipe teks; institusi memakai master dengan pencarian dan alias. Pertahankan data saat validasi gagal. Kesalahan dekat field, ringkasan error yang dapat difokuskan setelah pengiriman gagal, dan tautan ke setiap field bermasalah.

Password manager dan paste diperbolehkan. Jangan memaksa email @bi.go.id untuk pengguna eksternal. Metode autentikasi masih menunggu keputusan klien. Peserta tidak memilih sendiri peran pengelola.

Skeleton mengikuti dimensi komponen. Empty state menyatakan apa yang belum tersedia dan bagaimana melengkapinya. Gunakan ilustrasi kecil yang konsisten; jangan mengganti data kosong dengan nol. Konfirmasi berhasil hanya setelah respons server. Nilai belum dinilai tetap “Belum tersedia”.

## 5. Layout Principles & Responsive

Grid 12 kolom desktop; komposisi utama 8/4 atau 7/5. Lebar konten peserta maksimal 1.280 px, keseluruhan aplikasi maksimal sekitar 1.600 px. Skala jarak 4, 8, 12, 16, 24, 32, 48, 64, 96 px. Padding halaman 32–48 px desktop, 24 px tablet, 20–24 px mobile.

Di bawah 768 px, area konten utama menjadi satu kolom. Kontrol filter kecil boleh membungkus bila setiap target tetap 44 px atau lebih; susun vertikal jika teks tidak muat. Tidak ada scroll horizontal halaman. Nama institusi dibungkus penuh. Tabel berubah menjadi daftar label/nilai atau detail yang bisa dibuka. L1 memakai pilihan vertikal pada mobile; tidak mengecilkan enam opsi menjadi target sempit.

Gambar mobile dalam paket adalah cuplikan halaman yang dapat digulir, bukan janji semua isi muat pada satu layar fisik. S08M memperlihatkan satu pertanyaan lengkap beserta keenam opsi dan awal pertanyaan berikutnya. Sediakan inset bawah untuk bottom nav/CTA dan safe area. Gunakan min-height 100dvh, dukung zoom, pembesaran teks, keyboard, dan orientasi landscape.

## 6. Motion & Interaction

Gerak membantu memahami perubahan: feedback tekan 100–150 ms, menu/dialog 180–220 ms, transisi konten 200–300 ms. Animasi hanya transform/opacity. Maksimal 1–2 elemen bergerak dalam satu tampilan. Ilustrasi, logo, angka KPI, dan soal tetap statis. Jangan menjalankan pulse, shimmer, penghitung angka, atau carousel dekoratif terus-menerus.

Reduced motion menonaktifkan gerak nonesensial; status tetap terlihat sebagai teks. Loading hanya bergerak ketika proses benar-benar berlangsung. Navigasi kembali memulihkan posisi dan draf. Setelah pindah halaman, fokus diarahkan ke konten utama; sticky header tidak boleh menutupi fokus.

Target implementasi harus diuji pada 375, 768, 1.024, dan 1.440 px, termasuk keyboard dan pembesaran teks. Paket PNG belum menguji perilaku ini. Kapasitas 200–1.500 pengguna bersamaan adalah kebutuhan implementasi, bukan hasil uji mockup.

## 7. Anti-Patterns & Handoff Rules

- Jangan memakai emoji sebagai ikon, neon, ungu dekoratif, serif untuk dashboard, atau hitam murni.
- Jangan membuat tiga kartu identik sebagai pengganti hierarki informasi.
- Jangan menampilkan nilai tanpa unit, jumlah respons, periode, atau konteks.
- Jangan menyamakan L1 dengan skor tes L2; jangan membuat widget lima bintang untuk enam pilihan nilai.
- Jangan menampilkan tren tahunan, skor L2, persentase penyelesaian, atau peringkat kampus yang tidak didukung data.
- Jangan mengisi contoh dengan identitas responden asli; jangan menjadikan logo BI sebagai avatar peserta.
- Jangan menambahkan nama narasumber bawaan dari asumsi atau mengklaim panduan eksternal sudah final.
- Jangan menganggap PNG sebagai komponen UI siap produksi. Ganti logo dengan aset asli, gunakan font/token di atas, dan implementasikan state interaktif secara nyata.


### 7.1 Evaluasi L1

L1 adalah umpan balik terhadap penyampaian pengajar/materi, bukan nilai ujian pengetahuan. Instrumen Excel berisi **lima pertanyaan, masing-masing skala 1–6**, ditambah masukan bebas. Angka lima adalah jumlah pertanyaan, bukan jumlah pilihan rating. `[M1][X1]`

Lima aspek untuk label ringkas:

1. Pemahaman atau kejelasan isi materi.
2. Kejelasan penjelasan tanpa ambiguitas.
3. Kemudahan memahami istilah.
4. Kemudahan menarik kesimpulan.
5. Tambahan pemahaman atau pengetahuan yang dirasakan.

Teks pertanyaan lengkap harus mengambil versi instrumen yang disetujui. Label ringkas di atas tidak menggantikan redaksi sumber saat evaluasi diterbitkan. Untuk prototipe yang hanya memakai dokumen ini, gunakan salinan kerja berikut dari header Excel; spasi dan tanda penanda formulir telah dirapikan, serta salah ketik “ambibuitas” diperbaiki menjadi “ambiguitas”. Perbaikan redaksi tetap ditinjau pengelola sebelum penerbitan.

| Butir | Redaksi untuk prototipe |
| --- | --- |
| 1 | Kejelasan isi: Menurut Bapak/Ibu, bagaimana pemahaman Bapak/Ibu terhadap materi/informasi Literasi Kebanksentralan pada Civitas Akademika Tahun 2026? |
| 2 | Kejelasan isi: Penjelasan materi/informasi Literasi Kebanksentralan pada Civitas Akademika Tahun 2026 tidak menimbulkan ambiguitas. |
| 3 | Keterbacaan: Menurut Bapak/Ibu, seberapa mudah istilah pada materi/informasi Literasi Kebanksentralan pada Civitas Akademika Tahun 2026 yang disampaikan untuk bisa dipahami? |
| 4 | Interpretasi: Menurut Bapak/Ibu, apakah materi/informasi Literasi Kebanksentralan pada Civitas Akademika Tahun 2026 yang disajikan dapat untuk ditarik kesimpulan secara tepat? |
| 5 | Peningkatan Pengetahuan: Saya mendapatkan pemahaman/pengetahuan baru setelah membaca materi/informasi Literasi Kebanksentralan pada Civitas Akademika Tahun 2026. |

Gunakan radio angka **1, 2, 3, 4, 5, 6**, tanpa nilai terpilih otomatis. Jangan memakai widget lima bintang. Angka dan deskripsi opsi resmi berasal dari konfigurasi instrumen; arti verbal tiap angka belum tersedia dalam lampiran dan tidak boleh dikarang.

Pada desktop, keenam pilihan berada dalam satu baris dengan target minimal 44 px. Pada mobile di bawah 768 px, tampilkan pilihan satu kolom bernomor dengan target minimal 44 px. Lima pertanyaan tetap dapat dibaca dalam satu halaman vertikal. Tampilkan indikator **“Pertanyaan terisi”** tanpa membuat kelima pertanyaan menjadi lima layar wajib.

Header memuat kegiatan, sesi, materi, dan narasumber yang sedang dinilai. Jika sesi memiliki beberapa narasumber, gunakan pemilih narasumber dan status penyelesaian masing-masing, bukan satu jawaban yang disalin otomatis ke semua pengajar. Aturan siapa yang harus dinilai ditetapkan pengelola.

Urutan layar: konteks sesi, petunjuk, pertanyaan, masukan opsional, tinjauan singkat, **“Kirim evaluasi”**. Setelah server mengonfirmasi, tampilkan **“Evaluasi sudah dikirim”**, waktu pengiriman, dan tautan kembali ke sesi. Jangan menampilkan status berhasil hanya karena tombol ditekan.

### 7.2 Pretest dan post-test L2

Gunakan layar fokus dengan satu pertanyaan utama dan navigasi soal yang jelas. Desktop dapat memakai kolom pertanyaan dan panel ringkasan; pada mobile panel menjadi bagian ringkas di atas atau bawah, tetap satu kolom.

Template resmi dapat berbeda per pertemuan. Admin memilih template, membuat versi untuk sesi, mengatur jadwal, instruksi, jenis soal, serta aturan penilaian yang memang tersedia. Perubahan template berikutnya tidak mengubah jawaban atau skor historis.

Pretest dan post-test tampil sebagai tahap berbeda dengan label konsisten. Pilihan ganda menjadi pola awal; esai dapat memakai textarea apabila instrumen memerlukannya. Panduan LMS juga memuat tipe lain, sehingga tidak semua tipe lama otomatis menjadi persyaratan rilis awal. `[P2]`

Timer, nilai kelulusan, pengulangan tes, pembukaan kunci jawaban, dan publikasi skor hanya muncul bila sudah dikonfigurasi. Jangan mengarang batas waktu atau ambang lulus. Jika penilaian belum selesai, tampilkan **“Menunggu penilaian”**. Umpan balik L1 tidak menjadi komponen skor L2.

### 7.3 Materi dan panduan PDF

Item materi menampilkan judul, jenis file, jumlah halaman bila tersedia, narasumber/sesi, serta versi atau tanggal pembaruan. Sediakan **“Baca materi”** dan aksi sekunder **“Unduh PDF”**.

Desktop dapat memakai daftar materi di kiri dan viewer di kanan. Pada mobile, viewer berada setelah metadata dengan pilihan buka dokumen yang jelas. PDF tidak dipaksa masuk iframe yang terlalu sempit. Bila pratinjau gagal, unduhan tetap tersedia jika pengguna berhak mengaksesnya.

Panduan LMS yang dilampirkan adalah referensi alur internal BI. Panduan pengguna LENTERA eksternal harus ditulis menyesuaikan layar hasil implementasi; jangan mengganti judul panduan lama lalu menyatakan isinya sudah sesuai. `[P2]`

### 7.4 Tabel, filter, dan grafik

- Filter utama: tahun/periode, KPwBI bila diizinkan, perguruan tinggi, kegiatan/sesi, narasumber, kategori peserta, dan versi instrumen bila diperlukan. Tahun selalu terlihat.
- Form filter memakai label nyata. Pada mobile, tombol **“Filter”** membuka bagian filter satu kolom; pilihan yang sedang aktif tetap terlihat setelah ditutup.
- Tabel desktop memakai header lengket, tinggi baris minimum 48 px, angka rata kanan, pengurutan yang jelas, pencarian, dan pagination. Jangan memuat 1.500 peserta sekaligus ke satu tabel browser.
- Tabel mobile berubah menjadi daftar ringkasan satu kolom dengan **“Lihat detail”**. Jangan menyembunyikan kolom penting tanpa akses detail dan jangan membuat halaman bergeser horizontal.
- Setiap grafik memiliki judul, unit, periode, lingkup, jumlah respons, label sumbu, dan alternatif tabel. Tooltip dapat dibuka melalui fokus/tap.
- Tren tahunan L1 memakai garis dan marker; nilai 1–6 dengan domain sumbu yang terlihat. Bulan tanpa observasi ditampilkan sebagai celah **“Belum ada data”**, bukan nilai nol atau garis interpolasi.
- Perbandingan kampus/narasumber menggunakan dot plot atau batang horizontal dengan label penuh dan jumlah respons. Untuk batang skor, gunakan baseline 0 dan batas atas 6 agar panjang tidak menyesatkan; skor valid tetap 1–6.
- Perbandingan pre/post memakai dua marker atau garis berpasangan dengan denominator peserta berpasangan. Jangan memakai dual axis, grafik 3D, donut dekoratif, atau animasi penghitung angka.

### 7.5 Loading, kosong, gagal, dan akses

| Keadaan | Tampilan dan tindakan |
| --- | --- |
| Memuat data | Skeleton mengikuti ukuran panel/baris sebenarnya; bukan spinner generik |
| Belum ada kegiatan | Penjelasan singkat; peserta diarahkan memeriksa undangan, admin mendapat aksi membuat kegiatan |
| Filter tanpa hasil | “Tidak ada data untuk filter ini” dan “Reset filter” |
| L2 belum tersedia | “Data pretest dan post-test belum tersedia untuk periode ini” |
| Koneksi terputus | Pesan dekat area kerja, status penyimpanan yang sebenarnya, dan “Coba lagi” |
| Pengiriman belum terkonfirmasi | Tetap “Menunggu konfirmasi pengiriman”; jangan menganggap sukses atau otomatis membuat respons baru |
| Kegiatan ditutup | Tampilkan hasil/status yang diizinkan dan alasan formulir tidak dapat diisi |
| Hak akses tidak cukup | Jelaskan cakupan akun dan cara menghubungi pengelola; jangan merender data tersembunyi di balik layar |
| Laporan sedang disiapkan | Status antrean, waktu permintaan, dan tautan unduh hanya setelah berkas selesai |


## 8. Product Structure, Roles & Flows

### 8.1 Model informasi

Relasi utama: **KPwBI membina institusi; program memiliki kegiatan; kegiatan memiliki pertemuan/sesi; sesi memiliki materi, narasumber, serta versi instrumen; peserta memiliki kepesertaan dan respons yang terikat pada konteks tersebut.**

L1 perlu membedakan peserta, kegiatan/sesi, narasumber yang dinilai, dan versi instrumen. Percobaan L2 menyimpan tahap pre/post, versi tes, status, dan hasil. Satu master peserta dapat dipakai untuk banyak kegiatan tanpa menyalin profil secara manual.

Ini adalah model kerja yang diusulkan untuk menjaga konsistensi antarlayar, bukan skema database final.

### 8.2 Matriks akses

| Kemampuan | Peserta eksternal | Pegawai BI pengelola | Pemantau PT |
| --- | --- | --- | --- |
| Melihat profil dan hasil sendiri | Ya | Sesuai tugas dan izin | Hanya bila juga memiliki peran peserta |
| Mengakses materi kegiatan | Sesuai kepesertaan | Sesuai cakupan KPwBI | Materi yang dibagikan untuk institusinya |
| Mengisi pretest, L1, L2 | Untuk dirinya sendiri | Bila terdaftar sebagai peserta | Bila terdaftar sebagai peserta |
| Membuat atau mengubah kegiatan | Tidak | Ya, sesuai cakupan | Tidak |
| Mengelola narasumber dan instrumen | Tidak | Ya, sesuai cakupan | Tidak |
| Melihat dashboard agregat | Progres dirinya sendiri | Kegiatan/KPwBI yang diizinkan | Agregat institusinya yang diizinkan |
| Melihat identitas/jawaban individu | Miliknya sendiri | Sesuai kebutuhan tugas dan izin | Tidak secara default |
| Ekspor laporan agregat | Hasil sendiri bila tersedia | Ya, sesuai izin | Hanya jika diaktifkan BI |
| Mengatur akun dan hak akses | Tidak | Hanya petugas BI berwenang | Tidak |

Klien menyebut dashboard dikelola pegawai BI dan stakeholder PT dapat melihat. Pemisahan detail izin dan pembatasan ke institusi sendiri di atas merupakan usulan awal yang harus dikonfirmasi. “Mitra dapat menilai” tidak otomatis berarti mereka boleh mengubah jawaban peserta atau mengakses semua kampus. `[M1]`

### 8.3 Alur peserta

1. Masuk melalui metode eksternal yang disepakati, atau mengikuti undangan kegiatan yang sah.
2. Lengkapi profil dan periksa kepesertaan.
3. Buka kegiatan, lalu pilih sesi yang relevan.
4. Kerjakan pretest jika kegiatan mensyaratkannya.
5. Akses materi dan ikuti pembelajaran.
6. Isi L1 serta post-test L2 sesuai jadwal dan ketentuan sesi.
7. Lihat konfirmasi pengiriman, tugas yang masih tersisa, dan hasil yang sudah boleh dibuka.

Urutan membuka L1 dan L2 dikonfigurasi per kegiatan. Jangan mengunci L2 hanya karena L1 belum diisi kecuali aturan klien secara eksplisit memerlukannya. Status kegiatan selesai mempertimbangkan semua tugas wajib, bukan sekadar membuka file materi.

### 8.4 Alur pengelola BI

Perencanaan kegiatan dan mitra; penetapan sesi, narasumber, materi serta instrumen; pelaksanaan dan pemantauan partisipasi; evaluasi L1/L2; pelaporan serta tindak lanjut. Lima kelompok ini mengikuti arah proses LENTERA pada laporan klien. `[P1, hlm. 5]`

Tiga narasumber bawaan disiapkan sebagai konfigurasi master. Identitas ketiganya **belum dipastikan oleh lampiran meeting**. Excel hanya menunjukkan Triani Susanti pada data yang dibaca; daftar tim pelaksana bukan otomatis daftar tiga narasumber bawaan. Sediakan **“Tambah narasumber eksternal”** tanpa mengubah struktur kegiatan. `[M1][X1][P1]`

### 8.5 Cakupan awal dan pengembangan lanjutan

**Cakupan awal:** profil/kepesertaan, kegiatan dan sesi, materi PDF, narasumber internal/eksternal, pretest dan L2 per pertemuan, L1 skala 1–6, dashboard tahunan, master mitra, akses lihat PT, laporan, dan panduan. Impor data historis beserta pemeriksaan mapping adalah usulan pendukung untuk transisi dari Google Form/Excel.

**Pengembangan lanjutan yang memerlukan spesifikasi:** integrasi LMS BI/ERP-HRIS, SSO, sertifikat otomatis, penilaian jenis khusus, notifikasi terjadwal, dan perluasan lintas KPwBI. Jangan menampilkan fitur ini sebagai sudah aktif pada mockup awal. Sistem tidak memerlukan chatbot AI, marketplace kursus, leaderboard, atau gamifikasi untuk memenuhi brief saat ini.

## 9. Screen Inventory & Stitch Directions

Gunakan ID layar berikut saat meminta Stitch membuat atau merevisi layar. Semua layar mewarisi token dan komponen di bagian 1–7.

| ID | Layar dan pengguna | Isi utama | Aksi utama | State yang harus didesain |
| --- | --- | --- | --- | --- |
| S01 | Pengenalan, semua pengguna | Logo, tujuan LENTERA, penjelasan akses, panduan | Masuk ke LENTERA | Aset tersedia / fallback teks |
| S02 | Masuk dan aktivasi akun | Jalur eksternal dan pegawai sesuai konfigurasi; bantuan akses | Masuk / lanjutkan aktivasi | Validasi, gagal masuk, pemulihan, akun belum aktif |
| S03 | Profil peserta | Nama, institusi, kategori, jenis identitas, NIM/NIP, prodi/unit | Simpan profil | Lengkap, field wajib belum terisi, instansi belum ditemukan |
| S04 | Beranda peserta | Sesi berikutnya, tugas wajib, daftar kegiatan, riwayat singkat | Lanjutkan sesi | Baru bergabung, ada tugas, seluruh tugas wajib selesai |
| S05 | Kegiatan saya dan detail kegiatan | Jadwal, sesi, narasumber, status kepesertaan, daftar tugas | Buka sesi | Belum dibuka, berjalan, ditutup |
| S06 | Sesi dan materi | Daftar materi, viewer PDF, konteks sesi, akses evaluasi | Baca materi / lanjutkan tugas | Memuat, materi tidak tersedia, preview gagal |
| S07 | Pretest | Petunjuk, versi tes, soal, status jawaban | Mulai / kirim pretest | Draf, koneksi terganggu, belum dibuka, terkirim |
| S08 | Evaluasi L1 | Lima pertanyaan skala 1–6, materi, narasumber, masukan | Kirim evaluasi | Belum lengkap, mengirim, sudah terkirim, ditutup |
| S09 | Post-test L2 dan hasil | Soal, ringkasan, hasil sesuai kebijakan publikasi | Kirim post-test | Menunggu penilaian, hasil tersedia, hasil belum dibuka |
| S10 | Ringkasan BI | Filter periode, metrik yang tersedia, tren L1, kegiatan perlu tindak lanjut | Lihat rincian / siapkan laporan | Ada data, data parsial, kosong, gagal memuat |
| S11 | Kelola kegiatan BI | Daftar kegiatan, form kegiatan/sesi, tanggal, mitra, peserta | Buat / terbitkan kegiatan | Draf, belum lengkap, terbit, ditutup |
| S12 | Materi dan narasumber BI | Master narasumber, tambah eksternal, file materi, hubungan ke sesi | Simpan materi/narasumber | Unggah berjalan, gagal unggah, versi baru |
| S13 | Instrumen dan bank soal BI | Template resmi, versi per sesi, L1 atau pre/post L2, pratinjau | Simpan versi / gunakan pada sesi | Draf, sudah digunakan, hanya lihat versi lama |
| S14 | Analisis L1 BI | Skor, lima aspek, distribusi respons, PT/narasumber/materi, umpan balik | Lihat rincian evaluasi | Sampel terbatas, kosong, filter aktif |
| S15 | Analisis L2 BI | Kelengkapan pasangan pre/post, hasil dan perubahan berpasangan | Lihat rincian hasil | Tidak ada L2, pasangan tidak lengkap, menunggu penilaian |
| S16 | Peserta dan mitra BI | Master institusi, kepesertaan, kategori, impor historis dan pemetaan | Simpan / impor data | Alias belum dipetakan, baris ringkasan, duplikat potensial |
| S17 | Dashboard mitra PT | Label “Akses lihat”, lingkup institusi, agregat yang diizinkan | Lihat rincian agregat | Belum ada data, akses terbatas, periode aktif |
| S18 | Laporan BI dan akses lihat berizin | Periode, isi laporan, waktu data, status pembuatan, arsip | Siapkan laporan | Diproses, selesai, gagal, belum tersedia |
| S19 | Panduan | Panduan sesuai peran, indeks topik, PDF, bantuan | Baca / unduh panduan | PDF tersedia, preview gagal |
| S20 | Pengaturan akses BI berwenang | Akun, peran, hubungan KPwBI–PT, izin laporan | Simpan akses | Konflik cakupan, perubahan tersimpan, akses ditolak |

### 9.1 Komposisi layar kunci

**S04 — Beranda peserta.** Satu panel tugas berikutnya lebih dominan daripada ringkasan progres. Tampilkan urutan tugas dalam daftar, misalnya pretest, materi, L1, post-test. Jangan menampilkan persentase progres sebelum definisi tugas wajib jelas. Pada mobile, konteks kegiatan dan tombol melanjutkan tampil lebih dulu.

**S08 — Evaluasi L1.** Lebar area baca maksimum 760 px. Judul rata kiri, konteks materi/narasumber di bawahnya. Pertanyaan diberi nomor dan dipisahkan garis halus, bukan kartu besar bertumpuk. Setiap kelompok rating memiliki enam nilai. Tindakan kirim muncul setelah masukan bebas. Desain desktop dan mobile wajib memperlihatkan satu pertanyaan lengkap beserta semua opsi.

**S10 — Dashboard BI.** Judul “Gambaran pembelajaran.”, lingkup KPwBI, periode, dan waktu data di bagian atas. Satu panel angka L1 dominan, tabel lima aspek, dan konteks ketersediaan L2. Grafik hanya ditambahkan bila data memadai. Jika memakai lampiran sebagai data contoh, jangan menampilkan tren tahunan fiktif. Gunakan contoh terbatas pada bagian 10.3 dan beri label **“Contoh data lampiran · 7 April 2026”**.

**S11 — Pengaturan kegiatan.** Form bertahap yang ringkas: informasi kegiatan; sesi dan narasumber; materi serta instrumen; tinjauan sebelum terbit. Tampilkan persyaratan yang belum lengkap sebelum pengguna menerbitkan. Pratinjau peserta adalah mode baca, bukan sesi nyata.

**S14 — Analisis L1.** Utamakan skor, jumlah respons, dan perbandingan lima aspek. Tabel kampus dan narasumber menyertakan jumlah respons. Masukan bebas menjadi daftar yang dapat ditinjau oleh BI, dengan identitas disembunyikan secara default. Hindari pemeringkatan pengajar seolah-olah sampel yang sangat berbeda setara.

**S17 — Dashboard mitra.** Gunakan susunan analisis yang sama agar familiar, dengan institusi terkunci sesuai izin dan label **“Akses lihat”**. Hilangkan tombol kelola, impor, ubah instrumen, serta akses identitas individu. Pemantau tetap dapat mengganti periode dan filter yang diizinkan.

## 10. Data Meaning & Dashboard Contract

### 10.1 Definisi metrik

| Metrik | Definisi untuk desain | Batas yang harus terlihat |
| --- | --- | --- |
| Jumlah respons L1 valid | Banyak respons yang memenuhi kelengkapan lima nilai pada konteks instrumen yang sesuai | Bukan otomatis jumlah peserta unik atau peserta terdaftar |
| Skor L1 per respons | Untuk instrumen lampiran: rata-rata lima nilai skala 1–6 | Semua lima nilai wajib valid; kosong tidak diisi nol |
| Rata-rata L1 agregat | Rata-rata skor respons valid pada filter dan versi instrumen yang sesuai | Tampilkan `n`; jangan rata-ratakan rata-rata kampus tanpa bobot respons |
| L1 per aspek | Rata-rata jawaban valid pada aspek yang sama | Tampilkan nama aspek dan denominator; instrumen berbeda tidak langsung digabung |
| Cakupan pengisian L1 | Peserta yang memenuhi kewajiban L1 dibagi peserta yang wajib mengisi pada kegiatan/sesi tersebut | Memerlukan data kepesertaan dan kewajiban yang valid; satu peserta bisa menilai beberapa narasumber |
| Selisih pre/post L2 | Rata-rata selisih skor post-test dikurangi pretest pada peserta yang dapat dipasangkan | Tampilkan jumlah pasangan, skala skor, dan kesetaraan instrumen |
| Waktu penyelesaian evaluasi | Selisih waktu kegiatan berakhir dan waktu rekap dinyatakan final | Memerlukan dua timestamp dan definisi final; bukan waktu isi survei saja |
| Jumlah institusi | Jumlah ID institusi pada master setelah alias dipetakan | Bukan jumlah ejaan unik pada Excel |

Peningkatan pengetahuan pada butir L1 adalah persepsi peserta. Jangan menafsirkannya sebagai peningkatan nilai tes L2. Jangan menyebut rata-rata L1 sebagai “Indeks Literasi Nasional” atau ukuran dampak kausal.

Laporan klien menyebut target L1 `≥5,25 / 6` pada beberapa bagian. Tampilkan sebagai **target dari dokumen klien** yang dapat dikonfigurasi per periode setelah disepakati, bukan ambang universal atau angka realisasi. Target waktu evaluasi juga memerlukan penetapan apakah `<3` atau `≤3` hari karena sumber menggunakan keduanya. `[P1]`

### 10.2 Aturan impor dan keterbandingan

Impor dimulai dengan pratinjau jumlah baris data, baris ringkasan, field wajib, nilai skala, alias instansi, serta duplikat potensial. Baris yang belum dapat dipetakan ditahan untuk ditinjau; jangan dipetakan otomatis ke kampus terdekat hanya karena namanya mirip.

Simpan nilai sumber dan hasil pemetaan secara terpisah. Kategori **“Mahasiswa / GenBI”** pada lampiran tidak dapat dipecah menjadi jumlah mahasiswa non-GenBI dan GenBI tanpa data tambahan. NIM tetap teks dan hanya membantu identifikasi bersama institusi; jangan menganggap nama saja sebagai kunci unik.

Impor ulang file yang sama harus menunjukkan bahwa sumber telah pernah dimasukkan agar tidak menggandakan respons. Pemeriksaan duplikat mempertimbangkan sumber, peserta, sesi, narasumber, instrumen, dan aturan respons yang disepakati. Dokumen ini tidak menetapkan aturan menghapus respons secara otomatis.

Gabungan tahunan harus menyatakan cakupan periode. Jangan menghubungkan data satu hari menjadi tren 12 bulan atau membandingkannya dengan total tahun penuh tanpa label perbedaan cakupan.

### 10.3 Data contoh yang sudah diverifikasi

Dataset: `[X1]`, sheet **Form Responses 1**. Baris **2–142** memuat **141 respons bertimestamp**. Baris **143** merupakan ringkasan, bukan respons peserta. Seluruh timestamp bertanggal **7 April 2026**; zona waktu tidak tertulis dalam workbook.

| Elemen contoh | Nilai terverifikasi | Penggunaan yang diperbolehkan |
| --- | --- | --- |
| Respons L1 | 141 | Label “Respons L1”, bukan “Peserta terdaftar” |
| Rata-rata L1 | 5,300709219858156, ditampilkan `5,30 / 6` | Agregat data lampiran pada tanggal tersebut |
| Narasumber yang muncul | Triani Susanti | Konteks data historis; bukan daftar lengkap narasumber bawaan |
| Materi | Peran dan Tugas Bank Indonesia sebagai Bank Sentral | Contoh judul kegiatan/materi sesuai sumber |
| Kategori sumber | 140 “Mahasiswa / GenBI”; 1 “Akademisi” | Jumlah respons per kategori sumber |
| Pemahaman/kejelasan isi | `5,28 / 6` | Rata-rata aspek 1; `n=141` |
| Penjelasan tanpa ambiguitas | `5,30 / 6` | Rata-rata aspek 2; `n=141` |
| Kemudahan istilah | `5,23 / 6` | Rata-rata aspek 3; `n=141` |
| Penarikan kesimpulan | `5,23 / 6` | Rata-rata aspek 4; `n=141` |
| Tambahan pengetahuan yang dirasakan | `5,46 / 6` | Rata-rata aspek 5; `n=141` |

Kolom L1 pada setiap respons sama dengan rata-rata lima butirnya. Angka agregat dihitung ulang dari 141 respons dan tidak memasukkan baris ringkasan. Lima skor aspek di atas dibulatkan hanya untuk tampilan; perhitungan tetap memakai presisi penuh.

Data lampiran tidak menyediakan skor pretest/post-test, jumlah peserta yang wajib mengisi, atau riwayat lengkap tahunan. Pada contoh desain, area tersebut menggunakan empty state yang menjelaskan data apa yang belum tersedia.

### 10.4 Angka laporan yang tidak boleh dicampur

Laporan LENTERA memuat angka L1 **5,8/6** pada sampul, **5,25/6** pada blok hasil tahap 1, dan **5,62/6** pada bagian outcome/capaian di halaman 9. Angka **5,30/6** berasal dari perhitungan workbook tanggal 7 April. Cakupan angka-angka tersebut belum direkonsiliasi; jangan memilih salah satunya untuk KPI “tahun 2026” tanpa verifikasi. `[P1][X1]`

Demikian pula, jumlah peserta kegiatan Agustus pada laporan bukan denominator otomatis bagi respons survei April. Tampilkan asal, periode, dan status data jika angka laporan digunakan sebagai konteks dokumen.

## 11. Assets & Source Register

### 11.1 Aset merek

| Aset asli | Spesifikasi yang diperiksa | Pemakaian |
| --- | --- | --- |
| BI (Bank Indonesia) Landscape Logo (PNG-1080p) - FileVector69.png | PNG transparan, 2.048 × 362 px; rasio sekitar 5,66:1 | Endorsement BI pada header/footer dengan rasio asli |
| LENTERA Lighthouse Logo.png | PNG transparan, 2.048 × 684 px; rasio sekitar 2,99:1 | Identitas utama produk pada permukaan putih |

Untuk implementasi, aset dapat disalin dengan nama operasional `assets/bi-logo.png` dan `assets/lentera-logo.png`; nama ini adalah tujuan penempatan, bukan klaim bahwa file tersebut sudah berada dalam proyek Stitch. Unggah kedua aset asli sebagai referensi saat membuat layar.

Gunakan `object-fit: contain`, tanpa crop atau CSS filter. Clear space usulan minimal 12 px di sekeliling logo. LENTERA sekitar 144–168 px lebar pada header desktop, BI sekitar 180–204 px; sesuaikan susunan baris pada mobile agar logo tidak berdesakan. Uji keterbacaan tagline pada ukuran akhir. Jika tagline pada bitmap terlalu kecil, beri ruang logo lebih besar dan tampilkan nama lengkap program sebagai teks terpisah.

Fallback saat aset tidak termuat: teks **“LENTERA”** dan **“Bank Indonesia”** dalam area identitas yang sama. Jangan menghasilkan logo baru, ikon BI tiruan, atau placeholder broken image.

### 11.2 Sumber kebutuhan

| Kode | Lampiran | Bagian yang dipakai | Implikasi desain |
| --- | --- | --- | --- |
| M1 | Meeting 1 - Pak Ade.txt | Seluruh catatan meeting | LMS eksternal, BI sebagai pengelola, PT akses lihat, 200–1.500 pengguna bersamaan, profil, tiga narasumber bawaan, pre/post, L1/L2, grafik tahunan, panduan PDF |
| X1 | Data Pengukuran Indeks Literasi Kebanksentralan pada Civitas Akademika Tahun 2026 (Jawaban) 7 April 2026.xlsx | Form Responses 1, header A1:O1, respons A2:O142, ringkasan baris 143 | Field peserta, lima butir L1 skala 1–6, masukan, data contoh, kebutuhan pemetaan alias |
| P1 | LENTERA_SR_Laporan Akhir CP Probis.pdf | Hlm. 2–5, 7, 9–11; nomor halaman PDF | Proses terpadu, mitra Banten, evaluasi dan pelaporan, target dari dokumen, konteks pengembangan integrasi |
| P2 | Panduan Penggunaan New LMS Bank Indonesia 2.pdf | Hlm. 2–12, serta 13–18 sebagai konteks enrollment | Login internal, beranda peserta, tugas, sesi, materi, L1/L2; pola yang perlu disesuaikan bagi eksternal |
| P3 | Edukasi dan Literasi Kebanksentralan.pdf | Hlm. 1–9 dan 10; materi bertanggal 9 Oktober 2026 | Contoh materi, konteks kebanksentralan/daerah, identitas visual, ajakan pengisian L1 |
| A1 | BI (Bank Indonesia) Landscape Logo (PNG-1080p) - FileVector69.png | Seluruh aset | Logo BI asli |
| A2 | LENTERA Lighthouse Logo.png | Seluruh aset dan tagline | Identitas LENTERA dan mercusuar |

Tiga perguruan tinggi mitra yang disebut laporan adalah **Universitas Sultan Ageng Tirtayasa**, **UIN Sultan Maulana Hasanuddin Banten**, dan **UIN Syarif Hidayatullah Jakarta**. Gunakan nama resmi dalam master; ejaan pendek tetap dapat menjadi alias pencarian. `[P1, hlm. 2–3]`

Isi materi PDF diperlakukan sebagai konten klien. Desain ini tidak memverifikasi setiap klaim substantif atau regulasi dalam materi dan tidak menyalinnya menjadi klaim baru pada UI. Tautan evidence dalam laporan bukan bukti bahwa integrasi atau fitur baru sudah berjalan.

## 12. Prompting Google Stitch

Unggah DESIGN.md V2 dan kedua logo asli. Pilih kode layar pada bagian 9. Rujuk gambar V2 dengan kode yang sama sebagai referensi komposisi. Jangan memakai mockup V1 sebagai acuan gaya. Prompt per layar dan koreksinya tersedia pada PROMPTS.md di paket.

Gunakan pola ini untuk setiap layar:

```text
Buat layar [ID dan nama] LENTERA dengan aturan DESIGN.md V2.
Gunakan Outfit + Work Sans, canvas #F8FAFC, surface putih,
ink #0F172A dan aksen #245D83. Sesuaikan navigasi dengan peran.
Terapkan hierarki editorial, jarak 8px, radius panel24px/kontrol12px,
satu tindakan utama, ikon satu keluarga, dan logo sumber asli.
Gunakan konten serta state pada inventaris layar.
Jangan menambahkan data, persentase, atau kebijakan yang belum diberikan.
Untuk mobile: konten satu kolom, teks16px, target44px+,
L1 tetap enam pilihan vertikal, dan tidak ada overflow horizontal.
Implementasikan fokus, error inline, loading, empty state,
konfirmasi server dan reduced motion.
```

Rekomendasi basis UI UX Pro Max telah diseleksi pada bagian 1.2. Dokumen ini adalah hasil sintesis untuk LENTERA; keluaran pencarian generik bukan aturan yang boleh menimpa kebutuhan produk.

## 13. Acceptance Criteria & Decisions to Confirm

### 13.1 Penerimaan hasil desain

- [ ] Logo asli dipakai dengan rasio terjaga; tidak ada glow atau recolor tambahan.
- [ ] Token warna, Outfit/Work Sans, skala spasi, radius, dan perilaku kontrol konsisten antarlayar.
- [ ] S01 memiliki satu CTA utama dan komposisi asimetris; layar kerja mengutamakan tugas/data.
- [ ] Tidak ada tiga kartu identik, overlap konten, teks terpotong, atau overflow horizontal pada 375–1.440 px.
- [ ] Navigasi dan tindakan berbeda sesuai peran; PT tidak memperoleh tombol kelola atau identitas individu secara default.
- [ ] L1 memuat lima pertanyaan dan enam pilihan 1–6; L2 tetap merupakan tes yang terpisah.
- [ ] Form memperlihatkan konteks sesi/narasumber, field wajib, validasi, pengiriman, dan konfirmasi.
- [ ] Template tes dapat berbeda per pertemuan dan versi lama tetap dapat dikenali.
- [ ] Tiga narasumber bawaan bersifat configurable; narasumber eksternal dapat ditambahkan.
- [ ] Dashboard mencantumkan periode, unit/skala, jumlah respons, serta cakupan data.
- [ ] Data contoh L1 menggunakan 141 respons dan 5,30/6; baris ringkasan tidak dihitung.
- [ ] Data L2, denominator, dan tren tahunan yang belum tersedia tidak diisi angka buatan.
- [ ] Materi PDF dan panduan memiliki jalur baca/unduh serta fallback saat preview gagal.
- [ ] State loading, kosong, gagal, retry, akses ditolak, dan data parsial tersedia.
- [ ] Keyboard, fokus, target 44 px, pembungkusan teks, dan reduced motion dirancang dengan jelas.
- [ ] Target pengguna bersamaan dan integrasi ditulis sebagai kebutuhan implementasi, bukan fitur yang telah terbukti.

Daftar di atas dipakai untuk menilai layar yang dihasilkan; belum merupakan hasil pengujian aplikasi karena implementasi belum termasuk pekerjaan ini.

### 13.2 Keputusan yang masih memerlukan klien

| Topik | Yang belum ditetapkan | Default kerja dalam dokumen |
| --- | --- | --- |
| Identitas proyek Stitch | URL/ID proyek dan layar yang perlu diikuti | Dokumen mandiri; belum mengubah proyek aktif |
| Akses eksternal | Registrasi terbuka, undangan, email/OTP, atau federasi akun | Desain menyediakan jalur eksternal; metode autentikasi belum diklaim tersedia |
| Hak akses mitra | Cakupan dashboard, detail, serta izin unduh | Agregat institusi sendiri, akses lihat |
| Narasumber bawaan | Nama tepat tiga orang dan aturan siapa yang dinilai | Master dapat diatur; tidak menebak dari daftar tim |
| Instrumen L1 | Redaksi resmi, arti verbal skala 1–6, kewajiban per narasumber | Struktur lima butir dari Excel; label verbal menunggu instrumen resmi |
| Kebijakan L2 | Template, skoring, timer, percobaan ulang, publikasi hasil | Kontrol hanya muncul bila dikonfigurasi |
| Target dan metrik | Target L1 per periode, ambang waktu <3/≤3 hari, definisi selesai | Target diberi sumber; waktu proses belum dihitung dari data survei |
| Angka laporan | Cakupan 5,8; 5,25; 5,62 dan 5,30 | Jangan digabung menjadi KPI tahunan yang sama |
| Integrasi | API/SSO dan akses LMS BI/ERP-HRIS | Cakupan lanjutan, tanpa badge “terhubung” |
| Panduan dan data historis | Panduan eksternal final, master institusi, denominator, riwayat L2 | Gunakan state yang jujur dan alur pemetaan data |

Keputusan terbuka tidak menghalangi pembuatan layar awal. Stitch tetap dapat menghasilkan struktur, komponen, varian mobile, serta keadaan kosong/gagal menggunakan aturan yang sudah jelas di dokumen ini.
