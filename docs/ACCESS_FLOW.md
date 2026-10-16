# Akses peserta melalui QR kelas

Keputusan pengguna, 15 Oktober 2026: **peserta tidak memerlukan login atau akun untuk mengikuti kelas**. Ketentuan ini menggantikan asumsi login peserta pada fondasi backend awal dan prototype.

## Alur dan formulir

Pengelola BI login → buka QR dan tautan sesi → bagikan QR/tautan → peserta membuka formulir → peserta mengisi identitas → masuk ke halaman kelas → mengisi evaluasi L1 → menerima konfirmasi server.

Formulir memuat Nama Lengkap; Instansi/Perguruan Tinggi; NIP/NIM/NPM; Jurusan/Program Studi; Fakultas; No. HP; Kategori; Nama Narasumber; dan Email. Delapan isian identitas wajib; nama narasumber diambil dari sesi yang dibagikan, tampil hanya baca dan selalu ditetapkan server. Satu sesi masih mempunyai satu narasumber. Nomor identitas dan HP tetap teks, sehingga angka nol di depan tidak hilang.

Instansi menyediakan saran dari master KPwBI kelas, tetapi tetap menerima nama instansi lain. Pemetaan hanya dilakukan ke nama master yang cocok pada KPwBI itu; nama yang belum dikenali disimpan apa adanya dengan institution_id=null. Pengelola kini dapat mengisi mapped_institution_id secara terpisah, tanpa menulis ulang isian asli; lihat [MASTERS_PARTICIPANTS.md](MASTERS_PARTICIPANTS.md). Tidak ada tebakan alias. Kategori menerima teks dengan saran yang dapat diubah di config/lentera.php. Peserta nonkampus dapat mengisi nama unit kerja pada jurusan/fakultas.

## Penyimpanan dan akses

- Pendaftaran membuat enrollment tanpa user/account, dengan snapshot seluruh identitas, institusi, kategori dan sesi kelas. Data lama tidak dihapus oleh migrasi.
- Tautan kelas memakai token acak per sesi, status aktif dan waktu buka/tutup pendaftaran. Token bukan nomor ID berurutan.
- Token QR hanya mengizinkan membuka pendaftaran, bukan membaca semua peserta. Respons pribadi diakses melalui rahasia acak pada session browser; database menyimpan hash HMAC rahasia itu. Email/NIP atau enrollment_id dari request tidak dapat dipakai untuk mengambil alih respons.
- Retry pendaftaran dalam browser yang sama memakai kunci unik yang sama. Retry L1 mempertahankan respons final pertama. Data peserta dan respons final tidak ditimpa oleh retry.
- Pembatasan pengiriman formulir memakai sesi browser, agar peserta berbeda yang memakai IP internet kampus yang sama tidak berbagi kuota 30 request/menit.
- BI melihat agregat KPwBI akun dan dapat membuka/unduh QR SVG untuk sesi dalam cakupannya. Mitra mendapat agregat berdasarkan institusi saat pendaftaran, atau pemetaan yang ditetapkan BI untuk instansi yang semula belum dikenali; mitra tetap hanya mendapat cakupan institusinya dan tidak dapat mengelola QR. QR dibuat lokal oleh endroid/qr-code, tanpa mengirim URL atau data peserta ke layanan pembuat QR eksternal.
- Login email/kata sandi tetap tersedia hanya untuk BI/mitra. Akun participant dari implementasi awal tetap menjadi data/fixture regresi tetapi login participant ditolak. Endpoint peserta lama tidak ditautkan pada alur QR baru.

## Batas dan pekerjaan lanjutan

Email, nomor identitas dan nomor HP merupakan deklarasi peserta, belum diverifikasi. Membuka tautan di browser/perangkat baru menghasilkan pendaftaran baru; deduplikasi orang lintas browser dan pemulihan akses belum disediakan. Jangan membuka jawaban lama hanya berdasarkan kecocokan email/NIP tanpa verifikasi kepemilikan.

Akses mengikuti session browser Laravel (SESSION_LIFETIME, default 120 menit tidak aktif). Sesi yang kedaluwarsa atau cookie dihapus tidak dapat memulihkan respons lama otomatis. Penggunaan perangkat bersama dan pemulihan sesi memerlukan alur lanjutan. Tidak ada klaim bahwa orang yang berbeda tidak dapat mengisi identitas yang sama; jaminan anti-duplikasi saat ini berlaku untuk retry pada kepesertaan/sesi browser yang sama.

Pendaftaran dan evaluasi L1 mempunyai jadwal terpisah. Peserta yang sudah mendaftar dapat mengakses kelas setelah pendaftaran ditutup selama tautan tetap aktif, tetapi pengiriman L1 mengikuti jadwal evaluasi. Menonaktifkan atau merotasi token kelas memutus akses melalui tautan lama.

Seeder contoh menerbitkan token untuk sesi demo yang sudah ada; menjalankan seeder ulang tidak merotasi token atau mengubah jadwal. UI membuat/mengubah kegiatan dan sesi, mengatur masa berlaku, serta mengganti/menonaktifkan QR sudah tersedia bagi BI sesuai KPwBI dan dicatat dalam riwayat perubahan; lihat [MANAGEMENT.md](MANAGEMENT.md). Koreksi identitas, audit operasi lain, kebijakan privasi final, dan pemulihan akses masih pekerjaan berikutnya. Materi PDF privat terbit dapat dibaca/diunduh oleh peserta yang berhak; lihat [MATERIALS.md](MATERIALS.md). Pretest/post-test L2 juga memakai akses QR dan kepesertaan browser ini; lihat [L2.md](L2.md).

QR memakai URL aplikasi yang sedang dibuka. Alamat localhost hanya berlaku pada komputer server; sebelum membagikan QR kepada peserta, deploy aplikasi pada alamat HTTPS yang dapat dijangkau peserta dan pastikan APP_URL benar. Kapasitas MariaDB/Hostinger serta 1.500 pengguna belum diuji.

Prototype hash 20 layar dipertahankan sebagai referensi visual. Alur login peserta pada prototype tersebut bukan aturan akses backend terbaru.

Penjelasan untuk peserta awam disiapkan dalam [sketsa panduan peserta](PANDUAN_LMS_DRAFT.md). Panduan pengelola/mitra terpisah di [PANDUAN_PENGELOLA_MITRA_DRAFT.md](PANDUAN_PENGELOLA_MITRA_DRAFT.md). Keduanya masih Markdown; gambar, PDF final, dan pemasangan pada aplikasi mengikuti [rencana finalisasi](PANDUAN_LMS_RENCANA.md).
