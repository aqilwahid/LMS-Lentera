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
