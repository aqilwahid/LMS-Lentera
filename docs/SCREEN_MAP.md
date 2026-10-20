# Pemetaan backend tahap 1

Panduan S19 belum dihubungkan sebagai halaman backend. Sketsa isi awam tersedia di [PANDUAN_LMS_DRAFT.md](PANDUAN_LMS_DRAFT.md) dan [PANDUAN_PENGELOLA_MITRA_DRAFT.md](PANDUAN_PENGELOLA_MITRA_DRAFT.md); finalisasi PDF/halaman LMS dikerjakan belakangan.

| Acuan V2 | Rute server | Status |
| --- | --- | --- |
| S02 (BI/mitra) | GET/POST /masuk; POST /keluar | Login session hanya untuk pengelola/mitra |
| S02 (pemulihan kata sandi) | GET/POST /lupa-kata-sandi; GET /atur-kata-sandi/{token}; POST /atur-kata-sandi | Permohonan pemulihan mandiri via email, anti-enumerasi, pembatasan laju, token reset dan verifikasi standar kata sandi |
| Pendaftaran peserta | GET/POST /kelas/{token} | Form identitas tanpa akun melalui QR |
| Ruang tunggu | /waiting-room.html dan POST /admission.php | Antrean sebelum Laravel; posisi/pesan, tic-tac-toe lokal, lanjut saat tersedia. Dialog pada halaman menjaga isian POST selama menunggu. |
| Kelas peserta | GET /kelas/{token}/kegiatan | Kelas sesuai pendaftaran dan sesi browser |
| S08 (peserta QR) | GET/POST /kelas/{token}/l1 | L1 tanpa akun, hanya respons browser peserta sendiri |
| QR pengelola | GET /pengelola/sesi/{session}/akses dan /qr | Tampilan tautan dan QR SVG sesuai cakupan KPwBI |
| S04 | GET /beranda (peserta) | Kepesertaan aktif dari database |
| S05 | GET /kegiatan/{activity} | Detail kegiatan/sesi dengan policy |
| S08 | GET/POST /sesi/{session}/l1 | Form L1, validasi, simpan final dan konfirmasi |
| S11 (pengelola) | /pengelola/kegiatan dan /pengelola/sesi/{session}/ubah | Daftar, buat/ubah kegiatan dan sesi, jadwal, versi instrumen terbit |
| Akses QR (pengelola) | POST /pengelola/sesi/{session}/akses | Aktifkan, nonaktifkan atau ganti QR dengan pemeriksaan versi formulir |
| Audit (pengelola) | GET /pengelola/riwayat | Riwayat kegiatan, sesi dan QR sesuai KPwBI |
| S12/S16 (master BI) | /pengelola/master/institusi dan /pengelola/master/narasumber | Daftar, tambah/ubah serta status master sesuai KPwBI |
| S16 (kepesertaan BI) | /pengelola/kepesertaan dan /{enrollment} | Filter/detail, pemetaan instansi dan status kepesertaan; hanya BI berizin |
| S06 (peserta QR) | /kelas/{token}/materi/{material}; /pdf; /unduh | Pratinjau dan unduhan versi terbit dengan akses sesi browser |
| S12 (materi BI) | /pengelola/sesi/{session}/materi dan /pengelola/materi/{material} | Unggah/riwayat versi, terbit/sembunyikan, unduh versi lama sesuai KPwBI |
| S13 (instrumen BI) | /pengelola/instrumen; /buat; /{instrument}; /terbit; /versi | Draf lima butir L1 per KPwBI, terbit terkunci, versi baru, retry dan audit. |
| S13 (soal L2 BI) | /pengelola/instrumen-l2; /buat; /{instrument}; /terbit; /versi | Draf soal/opsi/kunci pilihan ganda per KPwBI, versi terbit terkunci, retry dan audit. |
| S07/S09 (peserta QR) | /kelas/{token}/l2/{pre atau post}; POST /{start atau save atau submit} | Mulai, draf manual server, final tahan retry, skor sesuai publikasi; kunci tidak dikirim. |
| S11/S15 (L2 sesi BI) | GET/PUT /pengelola/sesi/{session}/l2 | Pilih versi, jadwal pre/post, prasyarat opsional, publikasi dan ringkasan pasangan lengkap. |
| S15/S17 (analisis L2 BI/mitra) | GET /analisis/l2 | Filter tanggal/kegiatan/sesi/versi/institusi, jumlah final/pasangan dan rata-rata. Mitra hanya institusi sendiri dan hasil terbit, tanpa data individu. |
| S20 (kelola akun BI) | /pengelola/akun; /buat; /{id}/ubah; /{id}/pemulihan | Daftar, tambah, ubah status keaktifan, dan kirim ulang reset kata sandi sesuai cakupan KPwBI |
| S10/S17 | GET /beranda (BI/mitra) | Agregat database sesuai KPwBI/institusi |

Layout, navigasi dan formulir menggunakan Blade serta CSS/aset V2. Layar backend belum mencakup seluruh 20 layar. Rute /beranda, /kegiatan/{activity} dan /sesi/{session}/l1 untuk akun peserta lama hanya menjadi kompatibilitas internal; alur peserta terbaru memakai QR. Semua rute hash di bawah tetap merupakan **prototype terpisah** dengan localStorage; bukan rute atau izin server. Pemetaan lebih lanjut dilakukan bertahap sesuai [BACKEND.md](BACKEND.md).

---

# Pemetaan layar

Rute berbasis hash bekerja tanpa konfigurasi rewrite server. Misalnya `index.html#/beranda`. Alias `#/s01` sampai `#/s20` juga tersedia.

| Desain | Rute | Implementasi dan interaksi |
|---|---|---|
| S01 | `#/pengenalan` | Landing mercusuar, alur tiga langkah, navigasi masuk/panduan |
| S02 | `#/masuk` | Dua kategori akun, tiga peran demo, informasi aktivasi/pemulihan |
| S03 | `#/profil` | Profil peserta, validasi isian, simpan lokal |
| S04 | `#/beranda` | Hero materi, timeline belajar, kegiatan, bantuan |
| S05 | `#/kegiatan` | Detail kegiatan, sesi, status tugas, informasi institusi |
| S06 | `#/materi` | Pembaca PDF 11 halaman, perbesaran, unduh, lanjut evaluasi |
| S07 | `#/pretest` | Lima soal, navigasi, validasi jawaban, draf lokal |
| S08 | `#/evaluasi-l1` | Lima aspek × enam nilai, draf, feedback, konfirmasi |
| S09 | `#/post-test` | Pratinjau konfirmasi dan alur lima soal post-test |
| S10 | `#/ringkasan` | Dashboard BI, KPI 5,30/6 dan 141, tabel aspek, filter |
| S11 | `#/buat-kegiatan` | Wizard Informasi → Sesi → Materi → Tinjauan |
| S12 | `#/kelola-materi` | Tab materi/narasumber, tambah narasumber lokal |
| S13 | `#/instrumen` | Tab L1/pre/post, pratinjau pertanyaan, versi lokal |
| S14 | `#/analisis-l1` | Rincian aspek, n=141, riwayat kosong, feedback demo |
| S15 | `#/analisis-l2` | Kondisi data belum tersedia, kebutuhan pasangan data |
| S16 | `#/peserta-mitra` | Tiga institusi, pemetaan, pratinjau CSV lokal |
| S17 | `#/mitra` | Institusi sendiri, agregat belum tersedia, cakupan lihat |
| S18 | `#/laporan` | Filter, komponen laporan, ekspor PDF/XLSX, riwayat lokal |
| S19 | `#/panduan` | Lima topik, pencarian topik, artikel dan tautan alur |
| S20 | `#/pengaturan` | Matriks akses dan penyimpanan konfigurasi contoh |

## Empat acuan mobile

| Acuan | Implementasi responsif |
|---|---|
| S04M | Ilustrasi di atas teks hero, timeline di bawah, empat menu bawah |
| S08M | Enam pilihan nilai disusun vertikal, target sentuh minimal 48 px |
| S10M | KPI navy, tabel aspek, kondisi L2 kosong, CTA laporan satu kolom |
| S17M | Kampus dan kondisi kosong satu kolom, cakupan akses bertumpuk |

Alias `#/s04m`, `#/s08m`, `#/s10m`, dan `#/s17m` membuka halaman yang sama. Tampilan mobile mengikuti lebar viewport, bukan gambar yang dipasang sebagai halaman.

Breakpoint utama: 1.250 px, 1.020 px, dan 760 px. Navigasi samping berubah menjadi menu seluler dan navigasi bawah di bawah 760 px. Tabel master yang lebar memiliki area gulir lokal; teks dan formulir tetap mengalir dalam viewport.
