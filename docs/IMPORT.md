# Impor respons L1 historis

Menu **Impor historis** (pengelola BI saja, cakupan KPwBI sendiri) memasukkan respons L1 lama dari CSV ke sebuah sesi.

## Alur
1. Unggah CSV UTF-8 (pemisah `;` atau `,`, maks. 2 MB / 2.000 baris) dan pilih sesi tujuan. Sesi harus memiliki instrumen L1 terbit yang valid.
2. Berkas dibaca ke tabel staging (`import_rows`); belum ada respons yang dibuat. Pratinjau menampilkan jumlah per status: siap, institusi belum dipetakan, duplikat, tidak valid, baris ringkasan.
3. Isian institusi yang belum dikenal dipetakan manual ke master institusi; pemetaan disimpan sebagai alias (`institution_aliases`) dan dipakai impor berikutnya. Nama mirip tidak pernah dicocokkan otomatis.
4. **Terapkan** membuat kepesertaan dan respons final dalam satu transaksi. Menerapkan ulang tidak menggandakan data.

## Kolom
`timestamp, nama, instansi, kategori, nim, email, nilai_1 … nilai_5, masukan` (masukan opsional). Waktu: `2026-04-07 10:15:00` atau `07/04/2026 10:15:00`, dibaca menurut zona waktu aplikasi. NIM dibaca sebagai teks. Baris kosong atau tanpa timestamp/email/NIM dianggap ringkasan dan dikecualikan.

## Aturan
- Berkas identik (hash SHA-256) pada sesi yang sama ditolak.
- Duplikat = pasangan NIM + email yang sama dalam berkas atau pada sesi yang sudah punya respons.
- Respons yang diimpor tidak dapat diubah atau dihapus (sama seperti respons biasa). Setiap tahap tercatat pada riwayat perubahan.
- Kategori `Mahasiswa / GenBI` disimpan apa adanya.

Belum tersedia: `.xlsx` langsung (simpan sebagai CSV dari Excel), impor L2, pembatalan batch, dan rekonsiliasi terhadap total sumber. Tambahkan PhpSpreadsheet bila `.xlsx` langsung dibutuhkan.
