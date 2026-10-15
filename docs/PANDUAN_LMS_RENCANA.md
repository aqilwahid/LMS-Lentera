# Rencana finalisasi panduan LMS

Status: sketsa Markdown untuk peninjauan; belum membuat PDF, gambar baru, atau halaman panduan aplikasi. Tanggal: 15 Oktober 2026.

## Berkas dan sasaran pembaca

| Berkas | Sasaran | Isi |
| --- | --- | --- |
| [PANDUAN_LMS_DRAFT.md](PANDUAN_LMS_DRAFT.md) | Peserta awam, terutama pengguna HP | QR/tautan, seluruh isian identitas, kelas, L1, konfirmasi, dan bantuan. |
| [PANDUAN_PENGELOLA_MITRA_DRAFT.md](PANDUAN_PENGELOLA_MITRA_DRAFT.md) | Pegawai BI dan mitra yang belum terbiasa dengan LMS | Login, kegiatan/sesi, pengaturan QR, membaca ringkasan/riwayat, keluar, dan bantuan. |
| Dokumen ini | Penyusun panduan dan tim implementasi | Rencana halaman, gambar, status fitur, dan pemeriksaan sebelum terbit. |

Panduan peserta menjadi dokumen utama. Panduan pengelola/mitra dipisahkan agar peserta tidak mengira harus memiliki akun.

## Urutan halaman usulan

Jumlah halaman dapat berubah saat tata letak final dibuat.

| Halaman peserta | Fokus |
| --- | --- |
| Sampul | Judul, identitas LENTERA/BI, versi, dan tanggal. |
| 1 | Ringkasan satu halaman: QR → isi data → kelas → L1 → konfirmasi. |
| 2 | Membuka QR atau tautan di HP/komputer. |
| 3–4 | Penjelasan seluruh isian dan pemeriksaan sebelum bergabung. |
| 5 | Halaman kelas dan cara membuka L1. |
| 6–7 | Lima butir, enam pilihan per butir, masukan, dan kirim. |
| 8 | Konfirmasi dan bantuan saat koneksi bermasalah. |
| 9 | Pertanyaan umum dan kontak bantuan. |

Panduan pengelola/mitra dapat disusun terpisah dalam sekitar 6–8 halaman: masuk; membuat kegiatan/sesi; jadwal; siklus QR/tautan; membaca ringkasan/riwayat; keluar dan bantuan. Bab L2 serta pengelolaan lanjutan ditambahkan ketika sudah berfungsi, bukan dipasang sebagai langkah aktif pada edisi awal.

## Daftar gambar yang disiapkan nanti

| ID | Gambar | Penanda yang diperlukan |
| --- | --- | --- |
| P01 | QR contoh dan halaman Gabung kelas | Nama kegiatan/sesi; tidak perlu akun. |
| P02 | Formulir peserta pada HP | Seluruh label identitas, nama narasumber hanya baca, tombol Gabung kelas. |
| P03 | Halaman kelas | Nama peserta, jadwal, narasumber, Buka evaluasi L1. |
| P04 | Satu pertanyaan L1 lengkap | Keenam pilihan angka, satu jawaban per pertanyaan. |
| P05 | Bagian akhir form L1 | Masukan opsional, tombol Kirim evaluasi. |
| P06 | Konfirmasi L1 | Evaluasi sudah dikirim dan waktu penyimpanan. |
| P07/P08 | Materi peserta | Daftar versi terbit, pratinjau dan fallback buka/unduh. |
| A01 | Login pengelola/mitra | Email, kata sandi tersamarkan, Masuk. |
| A02 | Ringkasan pengelola | Bagikan QR kelas dan tautan sesi. |
| A03 | Halaman Bagikan kelas | QR, Tautan peserta, jadwal, Unduh QR SVG. |
| A04 | Ringkasan L1 BI/mitra | Cakupan, periode, jumlah respons, rata-rata, kondisi kosong. |
| A05 | Formulir kegiatan | Nama/deskripsi, Simpan kegiatan, Tambah sesi. |
| A06 | Formulir sesi | Narasumber, instrumen, jadwal dan keterangan konteks terkunci. |
| A07 | Akses QR kelas | Aktifkan/nonaktifkan dan Ganti QR dan tautan beserta dampaknya. |
| A08 | Riwayat perubahan | Pelaku, waktu, jenis perubahan dan nilai sebelum/sesudah. |
| A09/A10 | Master dan pilihan narasumber | Nama, status dan pemilihan pada sesi. |
| A11/A12 | Daftar/detail kepesertaan | Filter, isian asli, pemetaan institusi dan status. |
| A13/A14 | Materi BI | Unggah PDF, batas ukuran, versi, terbit/sembunyikan. |

Gunakan screenshot aplikasi final dengan data sintetis. Screenshot QA lokal tidak otomatis menjadi aset publik: pilih, periksa, dan samarkan data sebelum digunakan. Jangan menampilkan APP_KEY, kredensial, identitas asli, atau token kelas produksi. QR dalam dokumen contoh harus jelas diberi label contoh; QR kegiatan aktif dibagikan panitia secara terpisah.

## Gaya penulisan dan tampilan

- Satu langkah memuat satu tindakan, dimulai dengan kata seperti Buka, Isi, Periksa, atau Tekan.
- Gunakan sebutan sehari-hari: aplikasi internet, tautan, gambar QR, dan panitia. Hindari istilah backend, session, token, database, policy, atau deployment pada instruksi pembaca.
- Nama tombol harus sama dengan aplikasi, termasuk Gabung kelas, Buka evaluasi L1, Kirim evaluasi, dan Keluar.
- Jelaskan tanda berhasil setelah tindakan, bukan hanya tombol yang harus ditekan.
- Nomori penanda gambar sesuai langkah; jangan mengandalkan warna saja.
- Gunakan desain V2, logo asli dengan rasio terjaga, teks yang terbaca di HP, kontras cukup, serta PDF dengan teks yang dapat dipilih dan tautan yang dapat dibuka.
- Sediakan ringkasan singkat untuk peserta yang tidak ingin membaca seluruh panduan.
- Pemisahan L1 dari tes pengetahuan harus tetap jelas. Jangan mengarang arti verbal angka 1–6.

## Yang perlu dilengkapi sebelum terbit

- Alamat website produksi yang dapat diakses peserta.
- Nama/kanal bantuan dan jam layanan panitia serta petugas akun.
- Redaksi L1 dan penjelasan resmi skala angka 1–6.
- Arahan peserta tanpa NIP/NIM/NPM dan peserta nonkampus; jangan membuat nomor identitas atau aturan pengecualian baru di panduan.
- Penjelasan pemakaian data dan kebijakan privasi yang disetujui.
- Prosedur jika identitas salah, jawaban final salah, sesi berakhir, atau peserta berganti perangkat. Saat ini arahkan ke panitia; jangan menjanjikan tombol pemulihan/koreksi yang belum ada.
- Aturan multi-narasumber jika nanti diterapkan.
- Screenshot dari versi aplikasi yang akan dipakai, berikut pemeriksaan nama tombol dan pesan.
- Kebijakan L2 jika sudah disepakati: jadwal, percobaan, waktu, penilaian, dan kapan hasil boleh terlihat.
- Uji baca dengan pengguna awam: minta mereka membuka kelas dan mengirim L1 hanya dengan panduan, lalu catat bagian yang membingungkan.

## Pemeriksaan sebelum finalisasi

- [ ] Peserta tidak diarahkan ke login atau membuat akun.
- [ ] Seluruh sembilan label formulir tercantum; narasumber dijelaskan sebagai isian otomatis.
- [ ] Semua langkah aktif cocok dengan fitur dan izin yang benar-benar tersedia.
- [ ] Tidak ada klaim jawaban tersimpan sebelum konfirmasi.
- [ ] Kendala koneksi dan permintaan mengisi ulang tidak mendorong pendaftaran ganda.
- [ ] Batas akses pada perangkat/aplikasi internet yang sama dijelaskan dengan bahasa sederhana.
- [ ] Tidak menjanjikan draf otomatis, pemulihan mandiri, koreksi final, sertifikat, atau laporan yang belum tersedia.
- [ ] Data ilustrasi sintetis; tidak menggunakan identitas workbook historis.
- [ ] Placeholder alamat, gambar, dan kontak sudah diganti.
- [ ] Panduan dicoba pada HP dan komputer, kemudian nomor versi/tanggal ditetapkan.

Pelaksanaan final—gambar, tata letak PDF, pemasangan halaman panduan dan unduhan dalam LMS—dikerjakan belakangan sesuai arahan pengguna. Pembuatan sketsa ini tidak mengubah fungsi aplikasi.

Rujukan implementasi: [ACCESS_FLOW.md](ACCESS_FLOW.md), [BACKEND.md](BACKEND.md), [SCREEN_MAP.md](SCREEN_MAP.md), dan [QA.md](QA.md).
