# Dokumentasi LENTERA

Pembaruan: 22 Oktober 2026. Backend tahap pertama memakai **akses peserta melalui QR tanpa login**. Login hanya untuk pengelola BI dan mitra. Pengelolaan kegiatan/sesi, jadwal, QR, master, kepesertaan, materi PDF privat, instrumen L1/L2 berversi, pretest/post-test, analisis L2 per institusi/periode, pengelolaan akun pengelola/mitra dan pemulihan kata sandi mandiri, dashboard L1 dengan ringkasan KPI dan navigasi aksesibel (keyboard/mobile), serta ekspor CSV (Excel) dan cetak/simpan PDF browser tersedia untuk BI dan mitra sesuai cakupan KPwBI dan institusi. Prototype 20 layar dipertahankan sebagai referensi visual.

## Panduan untuk pengguna awam

Antrean akses sebelum Laravel dan ruang tunggu dengan tic-tac-toe tersedia sebagai pembatas yang dapat dikonfigurasi. Aktivasi dan batas request diterbitkan melalui perintah Artisan; checkout baru belum otomatis mengaktifkannya. Kapasitas 1.000–1.500 pengguna tetap menunggu uji pada hosting tujuan; baca [PERFORMANCE.md](PERFORMANCE.md).

| Dokumen | Status |
| --- | --- |
| [Panduan peserta](PANDUAN_LMS_DRAFT.md) | Sketsa Markdown: QR, identitas, materi PDF, L1, pretest/post-test, simpan draf, konfirmasi, hasil sesuai publikasi, dan bantuan. |
| [Panduan pengelola BI dan mitra](PANDUAN_PENGELOLA_MITRA_DRAFT.md) | Sketsa Markdown: login, membuat/mengubah kegiatan/sesi, mengatur QR, mengunggah/menerbitkan materi PDF, menyiapkan instrumen L1/L2, jadwal/publikasi tes, membaca ringkasan/riwayat dan analisis L2, keluar, dan bantuan. |
| [Rencana finalisasi panduan](PANDUAN_LMS_RENCANA.md) | Rencana halaman/gambar dan daftar yang perlu dilengkapi; PDF dan pemasangan panduan dalam aplikasi belum dikerjakan. |

Sketsa mengikuti fitur backend yang tersedia, termasuk materi PDF privat dan pretest/post-test. Fitur lanjutan yang belum tersedia diberi status rencana. Finalisasi PDF dan halaman panduan tetap dikerjakan belakangan.

## Acuan pengembangan

| Dokumen | Kegunaan |
| --- | --- |
| [README proyek](../README.md) | Status sudah/belum, cara menjalankan, dan hasil pengujian. |
| [ACCESS_FLOW.md](ACCESS_FLOW.md) | Keputusan akses terbaru: peserta tanpa akun, formulir, QR, dan batas akses browser. |
| [MATERIALS.md](MATERIALS.md) | Materi PDF privat, versi, publikasi, izin, batas upload dan QA. |
| [INSTRUMENTS.md](INSTRUMENTS.md) | Draf/publikasi/versi L1 per KPwBI, retry, audit dan pilihan sesi. L2 dijelaskan terpisah di L2.md. |
| [L2.md](L2.md) | Instrumen/assignment L2, draf manual, final, skor server, publikasi dan pasangan pre/post. |
| [ANALYTICS.md](ANALYTICS.md) | Filter/agregat L2 BI dan mitra, periode, pasangan sah, denominator serta privasi. |
| [PERFORMANCE.md](PERFORMANCE.md) | Antrean sebelum Laravel, ruang tunggu/tic-tac-toe, konfigurasi, batas perlindungan dan uji kapasitas. |
| [MASTERS_PARTICIPANTS.md](MASTERS_PARTICIPANTS.md) | Master, snapshot, pemetaan, status kepesertaan dan privasi. |
| [IMPORT.md](IMPORT.md) | Impor CSV respons L1 historis: staging, alias institusi, duplikat, penerapan idempoten. |
| [ACCOUNTS.md](ACCOUNTS.md) | Pengelolaan akun BI/mitra, pemulihan kata sandi, isolasi sesi dan riwayat audit. |
| [MANAGEMENT.md](MANAGEMENT.md) | Pengelolaan kegiatan/sesi, jadwal/QR, pemeriksaan versi dan audit. |
| [BACKEND.md](BACKEND.md) | Konfigurasi, model data, asumsi, pengujian, dan hosting. |
| [SCREEN_MAP.md](SCREEN_MAP.md) | Rute backend yang tersedia serta inventaris layar prototype. |
| [QA.md](QA.md) | Hasil pemeriksaan dan keterbatasan; catatan lama ditandai sebagai riwayat. |
| [TECH_STACK.md](TECH_STACK.md) | Kajian awal kebutuhan, stack dan hosting; keputusan metode masuk peserta kemudian ditetapkan di ACCESS_FLOW.md. |
| [DESIGN.md](DESIGN.md) | Desain visual V2; asumsi login peserta pada rancangan awal mengikuti koreksi akses terbaru. |

Jika ada perbedaan alur akses dengan kajian/desain awal, gunakan keputusan pengguna terbaru yang dicatat di ACCESS_FLOW.md. Dokumen kajian dan hasil QA lama tetap disimpan untuk menelusuri keputusan.
