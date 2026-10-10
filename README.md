# LENTERA — website HTML/CSS responsif

Implementasi desain V2 yang disetujui: **20 layar desktop** dengan adaptasi responsif untuk semua halaman. Empat acuan mobile utama adalah beranda peserta, evaluasi L1, dashboard BI, dan dashboard mitra.

## Membuka website

Website yang diterbitkan: https://lentera-learning.ardentchick33.chatgpt.site

Dalam source repository, buka `dist/index.html`. Dalam ZIP unduhan, buka `index.html` pada folder utama. HTML, CSS, JavaScript, font, ikon, ilustrasi, serta materi PDF disertakan lokal; tidak perlu proses build atau CDN.

Untuk membuka melalui server lokal milik Anda (opsional), jalankan `python3 -m http.server 8000` di direktori yang berisi `index.html`, lalu buka `http://localhost:8000`. Pembaca PDF dan perilaku penyimpanan dapat berbeda ketika memakai alamat `file://`.

Gunakan **Demo · 20 layar** di footer untuk membuka katalog halaman, berpindah peran, memperbesar teks, atau menghapus data demo. Menu seluler juga menyediakan akses ke katalog. Tidak diperlukan kredensial.

## File yang dapat diedit

| File | Tanggung jawab |
|---|---|
| `index.html` | Kerangka HTML dan pemuatan aset lokal |
| `styles.css` | Design token, layout desktop, breakpoint, mobile, cetak |
| `data.js` | Pemetaan 20 layar, data agregat contoh, komponen kecil |
| `pages-participant.js` | Pengenalan, login, profil, sesi, PDF, tes, L1, panduan |
| `pages-admin.js` | Pengelolaan BI, analisis, mitra, laporan, konfigurasi |
| `app.js` | Router hash, formulir, state lokal, dialog, ekspor PDF/XLSX |
| `assets/` | Logo, tiga ilustrasi, font, ikon, pustaka ekspor, materi PDF |
| `docs/DESIGN.md` | Spesifikasi desain V2 yang menjadi acuan |
| `docs/SCREEN_MAP.md` | Pemetaan 20 desktop + 4 mobile dan interaksinya |
| `docs/brand-spec.md` | Token, aset, dan aturan identitas visual |
| `docs/QA.md` | Pemeriksaan yang dilakukan dan batasannya |

## Fitur yang bisa dicoba

- Navigasi seluruh halaman, pergantian peran demo, menu seluler, dialog, serta pencarian panduan.
- Profil dengan validasi, penyimpanan lokal, dan reset data.
- Pembaca PDF 11 halaman, pemilihan halaman/perbesaran sesuai dukungan browser, buka tab baru, dan unduh materi.
- Lima soal contoh pretest dan post-test: pilihan jawaban, navigasi soal, draf, validasi, serta konfirmasi.
- Evaluasi L1: lima pertanyaan × enam pilihan, tanpa pilihan otomatis; masukan opsional; hitungan isian; ringkasan error; simpan draf dan kirim demo.
- Dashboard BI dan analisis L1: data contoh terverifikasi, filter, serta kondisi kosong ketika tidak ada data.
- Wizard kegiatan empat tahap, simpan draf, penerbitan demo, penambahan narasumber, versi instrumen, dan konfigurasi contoh.
- Master institusi, pratinjau pemetaan, dan pembacaan header/jumlah baris CSV lokal hingga 2 MB.
- Ekspor **PDF asli** dan **Excel `.xlsx` asli**, dengan pilihan komponen laporan dan riwayat lokal.
- Dashboard mitra dengan cakupan baca dan kondisi agregat belum tersedia.

## Status implementasi

Ini adalah front-end interaktif. Akun, undangan email, SSO, database, unggah materi server, penilaian L2, dan otorisasi server belum dihubungkan. Pilihan peran di katalog merupakan pratinjau, bukan mekanisme keamanan.

Login produksi sengaja tidak menerima email atau kata sandi. Tombol **Masuk demo** langsung membuka alur contoh. Gunakan data contoh ketika mengisi formulir. `localStorage` hanya berlaku pada browser yang sama; jika tidak tersedia, state bertahan selama tab terbuka. Perubahan lokal tidak memengaruhi snapshot data sumber.

Pretest berstatus selesai pada beranda awal mengikuti rancangan contoh yang disetujui. Halaman post-test awal memperlihatkan pratinjau konfirmasi; **Coba alur post-test** membuka tes interaktif. Tidak ada nilai L2 yang dibuat-buat.

## Data dan sumber

Snapshot L1: 7 April 2026, 141 respons bertanggal valid; satu baris ringkasan dikecualikan. Rata-rata 5,30/6. Lima aspek: 5,28; 5,30; 5,23; 5,23; 5,46. Narasumber: Triani Susanti. Data tahunan lengkap, pasangan pre/post, dan agregat per institusi tidak tersedia pada prototipe.

Nama, NIM, email, dan jawaban individual dari sumber Excel tidak disertakan. Materi PDF disertakan sebagai salinan terkompresi untuk web; sumber asli tidak diubah.

## Pengujian pengembang

`tests/smoke.cjs` menggunakan jsdom 26.1.0 untuk memeriksa 20 rute dan interaksi DOM. Pasang jsdom di lingkungan pengujian, lalu jalankan `node tests/smoke.cjs`; atau arahkan `LENTERA_JSDOM_PATH` ke paket yang sudah terpasang. Pengujian menghasilkan PDF/XLSX sementara di `tests/outputs/`.

Pengujian DOM bukan pengujian render browser. Pemeriksaan visual di Chrome/Safari/Firefox dan perangkat mobile masih perlu dilakukan sebelum produksi.
