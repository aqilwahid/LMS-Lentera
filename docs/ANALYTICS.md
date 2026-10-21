# Analisis L2 per institusi dan periode

Pembaruan 16 Oktober 2026. Halaman **Analisis L2** tersedia di GET /analisis/l2 bagi pengelola BI dan mitra. Hasil berasal dari database, bukan localStorage. Tidak mengubah instrumen, jawaban, atau skor yang sudah tersimpan.

## Cara menggunakan

1. Masuk sebagai pengelola BI atau mitra, lalu pilih **Analisis L2** pada menu atas atau **Buka analisis L2** di beranda.
2. Tentukan **Tanggal awal** dan **Tanggal akhir**. Tampilan awal memakai 1 Januari–31 Desember tahun berjalan menurut zona waktu aplikasi.
3. Bila perlu, pilih kegiatan, sesi dan versi instrumen. BI juga dapat memilih institusi atau **Belum dikenali atau dipetakan**.
4. Tekan **Terapkan filter L2**, lalu periksa jumlah respons final, pasangan sah dan rincian kelompok.
5. Gunakan **Kembali ke tahun berjalan** untuk menghapus filter dan kembali ke periode awal.
6. Tekan **Unduh CSV (Excel)** untuk mengunduh data rincian dalam format CSV yang kompatibel langsung dengan Microsoft Excel (pemisah titik koma `;`, desimal koma `,`, UTF-8 BOM, dan pengamanan formula). Tekan **Cetak / simpan PDF** untuk mencetak tampilan atau menyimpannya sebagai berkas PDF melalui peramban.

Pilihan kegiatan/sesi/instrumen berasal dari hasil final yang memenuhi cakupan akun pada seluruh periode, sebelum filter tanggal. Jika belum ada hasil final, pilihan belum tersedia. Tanggal awal/akhir wajib valid; akhir tidak boleh sebelum awal. Kombinasi sesi dan kegiatan yang tidak cocok ditolak. Pagination mempertahankan filter.

Pengelola BI melihat hasil KPwBI akunnya, termasuk yang skornya belum dibuka. Mitra hanya melihat institusinya, dalam KPwBI institusi tersebut, dan hanya dari sesi dengan publikasi hasil aktif. Pengelola mengatur **Buka skor peserta dan agregat mitra** pada **Atur evaluasi L2**. Ini satu flag publikasi untuk skor peserta dan agregat mitra pada tahap awal; pemisahan persetujuan/publikasi mitra perlu kebijakan tambahan.

## Periode dan denominator

| Angka | Definisi |
| --- | --- |
| Pretest final pada periode | Jumlah pretest final dengan waktu pengiriman di dalam tanggal awal–akhir. |
| Post-test final pada periode | Jumlah post-test final dengan waktu pengiriman di dalam tanggal awal–akhir. |
| Pasangan sah | Post-test final pada periode yang memiliki pretest sah, meskipun pretest berasal dari sebelum periode. |
| Rata-rata pre/post pasangan | Rata-rata skor masing-masing tahap hanya dari pasangan sah. |
| Selisih | Rata-rata skor post dikurangi pre pada setiap pasangan, dalam poin persentase. |

Rentang mencakup satu hari penuh dari 00:00:00 sampai 23:59:59 menurut zona waktu aplikasi. Query membandingkan kolom timestamp secara langsung; tidak membungkusnya dalam fungsi DATE. Waktu post-test menentukan periode penyelesaian pasangan. Karena pretest pasangannya dapat berada sebelum periode, jumlah pretest pada periode dapat lebih kecil daripada jumlah pasangan. Jangan mengartikan angka ini sebagai kehilangan data.

Tanpa pasangan, rata-rata/selisih ditampilkan **belum tersedia**, bukan nol. Nilai nol yang memang berasal dari tes tetap ditampilkan 0,00. Respons tanpa pasangan tidak ditambahkan ke denominator sebagai nilai nol.

Rata-rata keseluruhan dihitung dari seluruh pasangan yang memenuhi filter, bukan rata-rata dari rata-rata kelompok atau halaman yang sedang dibuka. Jadi setiap pasangan mendapat bobot sama. Rincian dipisahkan menurut ID kegiatan/sesi, assessment, versi instrumen, ID institusi efektif dan nama institusi sumber. Judul kegiatan yang sama tidak menggabungkan sesi yang berbeda.

Ringkasan lintas versi bersifat deskriptif. Kesetaraan versi atau validitas psikometrik belum dibuktikan. Gunakan filter versi dan rincian kelompok saat membandingkan; selisih bukan bukti dampak kausal dan tidak digabungkan dengan L1.

## Pasangan sah dan konsistensi tampilan

Pasangan harus memenuhi seluruh kondisi berikut:

- Kepesertaan dan penetapan L2 sesi sama.
- Kedua tahap final, versi instrumen dan jumlah soal sama, skor tersedia pada skala 0–100.
- Pretest sudah final sebelum atau tepat pada waktu post-test dimulai.

Draf, pre/post tanpa pasangan, urutan terbalik dan versi/jumlah soal yang berbeda tidak masuk rata-rata pasangan. Pre/post final yang tidak berpasangan tetap dapat masuk hitungan final tiap tahap. Data yang tidak cocok dengan konteks sesi/kepesertaan atau versi assignment tidak masuk dataset analisis.

Aturan urutan ini juga digunakan pada ringkasan pengaturan sesi dan selisih hasil peserta. Skor masing-masing tes tetap utuh bila urutannya terbalik; hanya selisih berpasangannya yang tidak ditampilkan. Post-test tetap boleh dimulai tanpa pretest jika prasyarat dimatikan. Aturan kelayakan analisis ini tidak menambahkan prasyarat baru pada alur tes.

Aturan kronologi dan penggunaan publikasi peserta untuk agregat mitra adalah keputusan implementasi awal yang perlu dikonfirmasi sebelum kegiatan nyata. Timestamp di database saat ini berpresisi detik; dua peristiwa pada detik yang sama memenuhi syarat tidak-sesudah. Ini belum merupakan pembuktian urutan request bersamaan di MariaDB.

## Institusi, histori dan privasi

- Institusi efektif memakai institution_id historis; jika kosong, mapped_institution_id hasil pemetaan pengelola. Nama isian asli tetap menjadi label sumber, sehingga alias ejaan tidak diam-diam digabungkan.
- Pemetaan yang diaudit dapat mengubah cakupan agregat mitra tanpa menulis ulang data sumber. Institusi master nonaktif tetap dapat dipakai untuk membaca histori; status master tidak otomatis mencabut akun mitra.
- Hasil final tetap dihitung jika kepesertaan kemudian dinonaktifkan. Tidak ada penghapusan hasil atau koreksi final melalui halaman analisis.
- Cakupan berasal dari akun server, bukan parameter URL. BI tidak dapat memilih master institusi kantor lain; mitra tidak dapat memilih institusi lain atau kelompok tanpa pemetaan. Pilihan di luar cakupan ditolak tanpa membuka data.
- Query hanya mengirim metadata kelompok, jumlah, rata-rata, selisih dan periode. Nama peserta, nomor identitas, email/HP, jawaban, snapshot soal/kunci, token dan secret akses tidak disertakan pada halaman.
- Halaman memakai Cache-Control private, no-store. Tidak ada cache agregat bersama; perubahan pemetaan/publikasi diperiksa ulang pada request berikutnya. Tidak menambah hak pengelolaan BI kepada mitra.

## Menjalankan dan memeriksa

Tidak ada migrasi, dependency atau aset Vite baru pada tahap analisis ini. Checkout yang belum memakai tahap L1/L2 sebelumnya tetap perlu `php artisan migrate`. Jalankan server seperti [BACKEND.md](BACKEND.md), lalu masuk dan buka /analisis/l2.

```powershell
php artisan test
php vendor/bin/pint --test
php artisan view:cache
node tests/l2-browser.cjs
node tests/l2-analytics-browser.cjs
```

Browser test analisis memakai instrumen terbit sintetis dari tests/l2-browser.cjs sebagai prasyarat. Pada database lokal yang sudah memilikinya, cukup jalankan test analisis. Setiap run menambah sesi, kepesertaan, pre/post dan audit sintetis. Tidak memakai data historis klien. Artefak di tests/outputs/l2-analytics diabaikan Git. Lihat hasil di [QA.md](QA.md).

## Batas dan berikutnya

Belum tersedia: ekspor XLSX/PDF berformat resmi cetakan dari server (ekspor CSV kompatibel Excel dan cetak PDF peramban sudah tersedia), grafik/tren tahunan, analisis butir, denominator seluruh peserta wajib tes, filter status kepesertaan, impor historis, serta pemisahan persetujuan publikasi peserta/mitra. Halaman ini tidak menetapkan kelulusan atau peringkat institusi.

MariaDB/Hostinger, query plan pada data besar, uji beban 200–1.500 pengguna, Safari/Firefox dan pembaca layar belum diverifikasi. Berikutnya impor historis melalui staging dan laporan server, kemudian pengujian integrasi/kapasitas sebelum produksi.
