# Agent Instructions — LMS-Lentera

## 1. Environment & Tooling (Windows Local)
- **PHP CLI**: Berada di folder portable. Tambahkan ke `$env:PATH` sebelum memanggil PHP:
  ```powershell
  $env:PATH = "$env:USERPROFILE\.codex\lentera-tools\php;$env:PATH"
  ```
- **Targeted Test Runner** (jauh lebih cepat daripada menjalankan seluruh suite):
  ```powershell
  php vendor/phpunit/phpunit/phpunit tests/Feature/<TargetTest>.php
  ```
- **Code Style & Formatting**:
  ```powershell
  php vendor/bin/pint --test
  # Autofix: php vendor/bin/pint
  ```
- **Blade Template Validation**:
  ```powershell
  php artisan view:cache
  ```

## 2. Aturan Domain Utama (Non-Negotiables)
- **Peserta**: TIDAK memiliki akun dan TIDAK melalui alur login `/masuk`. Akses kelas murni melalui token QR (`/kelas/{token}`) yang disimpan di session browser (`user_id = null`).
- **Pengelola BI (`bi_admin`)**: Dibatasi oleh kantor perwakilan akun (`office_id`). Seluruh mutasi kegiatan, sesi, instrumen, dan akun pengelola/mitra wajib terisolasi per KPwBI.
- **Mitra (`partner`)**: Dibatasi oleh institusinya (`institution_id`). Hanya memiliki akses baca agregat tanpa identitas individu, dan hanya untuk sesi yang status publikasinya telah dibuka oleh BI.
- **Integritas Historis (Snapshot)**:
  - `Enrollment` membekukan `institution_name` saat pendaftaran. Perubahan master institusi tidak boleh menimpa isian historis peserta.
  - Sesi membekukan `speaker_name`. Sesi mengunci konteks (nama sesi, narasumber, instrumen) segera setelah ada peserta bergabung atau respons masuk.
- **Audit Trail**: Seluruh mutasi administratif (buat/ubah kegiatan, sesi, QR, master, materi, instrumen, akun, impor) wajib mencatat rekaman ke tabel `audit_entries` dalam transaksi yang sama.
- **Database Testing**: Pengujian unit/feature berjalan pada SQLite in-memory (`:memory:`).

## 3. Konvensi Commit
- Gunakan Conventional Commits (mis. `feat: ...`, `fix: ...`, `docs: ...`).
- Sertakan trailer: `Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>`.
