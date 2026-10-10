(() => {
  const {
    views,
    escape: e,
    icon: i,
    link: a,
    button: b,
    pill,
    info,
    heading,
    field,
    tabs,
    institutions,
    aspects,
  } = L;
  L.hasSnapshot = (u) =>
    u.filters.year === "2026" &&
    u.filters.institution === "Semua institusi" &&
    u.filters.date === "7 April 2026";
  L.filters = (u, partner = false) =>
    `<div class="filter-bar">${field("Tahun", "filter-year", u.filters.year, { options: ["2026", "2025"] })}${field("Periode", "filter-date", partner ? u.partnerDate : u.filters.date, { options: partner ? ["Semua periode", "7 April 2026"] : ["7 April 2026", "Periode lainnya"] })}${partner ? `<div class="locked-field">${i("building-2")}<span class="grow">${institutions[0]}</span>${i("lock-keyhole")}</div>` : field("Institusi", "filter-institution", u.filters.institution, { options: ["Semua institusi", ...institutions] })}</div>`;
  L.feature = () =>
    `<section class="feature-panel"><div><p class="eyebrow">Rata-rata evaluasi L1</p><div class="metric">5,30 <small>/ 6</small></div><p class="metric-sub">Lima aspek pengalaman belajar</p></div><div class="secondary-metric"><div class="metric">141</div><p class="metric-sub">respons valid</p>${a("analisis-l1", "Lihat rincian")}</div></section>`;
  L.aspectTable = (n = false) =>
    `<div class="table-wrap"><table class="aspect-table"><caption class="sr-only">Rata-rata lima aspek evaluasi L1 pada 7 April 2026</caption><thead><tr><th scope="col">Aspek</th><th scope="col" class="num">Rata-rata / 6</th>${n ? '<th scope="col" class="num">n</th>' : ""}</tr></thead><tbody>${aspects.map((r) => `<tr><td>${r.short}</td><td class="num">${r.value}</td>${n ? '<td class="num n">141</td>' : ""}</tr>`).join("")}</tbody></table></div>`;
  L.empty = (title, copy, ic = "files", cta = "") =>
    `<div class="empty-state"><span class="empty-icon">${i(ic)}</span><h3>${title}</h3><p>${copy}</p>${cta}</div>`;
  views.s10 = (s, u) =>
    `${heading("KPwBI Provinsi Banten", "Gambaran pembelajaran.", "Ringkasan evaluasi dan aktivitas literasi kebanksentralan.", pill("Contoh data · 7 April 2026"))}${L.filters(u)}${L.hasSnapshot(u) ? `<div class="two-col"><div class="stack">${L.feature()}<section class="panel"><div class="panel-header"><h3>Rincian lima aspek</h3>${a("analisis-l1", "Lihat semua")}</div>${L.aspectTable()}<p class="table-note">Sumber: respons 7 April 2026 · Skala 1–6</p></section></div><aside class="stack"><section class="panel"><h3>Evaluasi L2</h3>${L.empty("Hasil belum tersedia.", "Pretest dan post-test berpasangan belum tersedia.", "files", a("analisis-l2", "Lihat kebutuhan data", "btn secondary"))}</section><section class="panel soft"><h3>Ringkasan yang siap dibagikan.</h3><p class="small muted mt">Susun laporan sesuai periode dan cakupan data yang tersedia.</p><div class="mt">${a("laporan", "Siapkan laporan", "btn", "file-down")}</div></section></aside></div>` : `<section class="panel">${L.empty("Belum ada data untuk filter ini.", "Data contoh yang tersedia mencakup semua institusi pada 7 April 2026.", "search-x", b("Tampilkan data contoh", "reset-filters", "btn"))}</section>`}`;
  views.s11 = (s, u) => {
    const d = s.draft,
      step = d.step;
    const content = [
      `<h2 class="mb">Informasi kegiatan</h2><div class="form-grid">${field("Nama kegiatan", "activity-name", d.name, { required: true, wide: true, placeholder: "Contoh: Literasi Kebanksentralan" })}${field("Kantor perwakilan", "activity-office", d.office, { options: ["KPwBI Provinsi Banten"] })}${field("Institusi mitra", "activity-institution", d.institution, { options: institutions })}${field("Tanggal kegiatan", "activity-date", d.date, { type: "date", required: true })}${field("Deskripsi kegiatan", "activity-description", d.description, { textarea: true, wide: true })}</div>`,
      `<h2 class="mb">Sesi & narasumber</h2><div class="form-grid">${field("Judul sesi", "activity-session", d.session, { required: true, wide: true })}${field("Narasumber", "activity-speaker", d.speaker, { options: ["Triani Susanti", ...s.speakers.map((x) => x.name)] })}</div><div class="mt">${info("Satu sesi contoh. Narasumber tambahan dapat dibuat pada menu Materi & narasumber.")}</div>`,
      `<h2 class="mb">Materi pembelajaran</h2><label class="answer-option"><input type="checkbox" name="activity-material" ${d.material ? "checked" : ""}><span><strong>Edukasi dan Literasi Kebanksentralan.pdf</strong><br><span class="small muted">PDF · 11 halaman</span></span></label>${a("materi", "Lihat materi", "text-link", "external-link")}`,
      `<h2 class="mb">Tinjau kegiatan</h2><dl class="key-values"><div><dt>Nama kegiatan</dt><dd>${e(d.name)}</dd></div><div><dt>Kantor</dt><dd>${e(d.office)}</dd></div><div><dt>Institusi</dt><dd>${e(d.institution)}</dd></div><div><dt>Tanggal</dt><dd>${e(d.date)}</dd></div><div><dt>Sesi</dt><dd>${e(d.session)}</dd></div><div><dt>Narasumber</dt><dd>${e(d.speaker)}</dd></div><div><dt>Materi</dt><dd>${d.material ? "1 PDF" : "Belum dipilih"}</dd></div></dl><div class="mt">${info("Terbitkan demo menyimpan kegiatan hanya di browser ini. Tidak ada undangan yang dikirim.")}</div>`,
    ][step];
    return `${heading("Pengelolaan kegiatan", "Buat kegiatan baru.", "Susun informasi, sesi, dan materi secara bertahap.")}<ol class="stepper" aria-label="Tahapan kegiatan">${["Informasi", "Sesi", "Materi", "Tinjauan"].map((t, n) => `<li class="${n === step ? "active" : n < step ? "done" : ""}" ${n === step ? 'aria-current="step"' : ""}><span>${n < step ? i("check") : n + 1}</span>${t}</li>`).join("")}</ol><div class="two-col"><form id="activity-form" class="panel" novalidate><div class="error-summary" tabindex="-1" role="alert"></div>${content}<div class="form-actions">${step ? b(i("arrow-left") + " Kembali", "activity-prev", "btn secondary") : ""}${b("Simpan draf", "activity-save", "btn ghost")}<button type="submit" class="btn">${step === 3 ? "Terbitkan demo" : "Lanjutkan"} ${i("arrow-right")}</button></div></form><aside class="stack"><section class="panel soft"><span class="icon-disc">${i("calendar-plus")}</span><h3 class="mt">Satu kegiatan, alur yang tertata.</h3><p class="small muted mt">Hubungkan peserta, materi, dan evaluasi ke sesi yang sesuai.</p><hr><p class="small muted">Draf dapat disimpan dan dilanjutkan di browser yang sama.</p></section>${s.activities.length ? `<section class="panel"><h3>Kegiatan demo tersimpan</h3>${s.activities.map((t) => `<div class="list-row"><div><strong>${e(t.name)}</strong><p class="muted">${e(t.date)} · ${e(t.session)}</p></div></div>`).join("")}</section>` : ""}</aside></div>`;
  };
  views.s12 = (s, u) =>
    `${heading("Sumber pembelajaran", "Materi & narasumber.", "Sumber belajar dan pengajar, tertata.")}${tabs(
      [
        ["speakers", "Narasumber"],
        ["materials", "Materi"],
      ],
      u.materialTab,
      "material",
    )}<div class="two-col"><div class="stack"><section class="panel">${u.materialTab === "speakers" ? `<div class="table-wrap"><table class="speaker-table"><caption class="sr-only">Daftar narasumber</caption><thead><tr><th>Nama</th><th>Kategori</th><th>Materi terkait</th></tr></thead><tbody><tr><td><strong>Triani Susanti</strong></td><td>Belum ditetapkan</td><td>Peran dan Tugas Bank Indonesia</td></tr>${s.speakers.map((t) => `<tr><td><strong>${e(t.name)}</strong><br><small>${e(t.institution)}</small></td><td>Eksternal</td><td>${e(t.subject)}</td></tr>`).join("")}</tbody></table></div><p class="table-note">Tambahkan narasumber sesuai daftar yang disepakati.</p>` : `<h2>Materi tersedia</h2><div class="list-row"><span class="icon-disc">${i("file-text")}</span><div class="grow"><h4>Edukasi dan Literasi Kebanksentralan.pdf</h4><p class="muted">PDF · 11 halaman · Sesi kebanksentralan</p></div>${a("materi", "Lihat")}</div><div class="mt">${info("Unggah materi ke server akan dihubungkan saat backend tersedia. PDF sumber pada demo dapat dibaca dan diunduh.")}</div>`}</section><section class="panel soft"><div class="row start"><span class="icon-disc">${i("file-text")}</span><div class="grow"><h3>Edukasi dan Literasi Kebanksentralan.pdf</h3><p class="small muted mt">11 halaman</p>${a("materi", "Lihat materi")}</div></div></section></div><aside class="panel"><div class="panel-header"><h3>Tambah narasumber</h3>${pill("Eksternal")}</div><form id="speaker-form" novalidate><div class="error-summary" tabindex="-1" role="alert"></div><div class="stack sm">${field("Nama lengkap", "speaker-name", "", { required: true })}${field("Instansi", "speaker-institution", "", { required: true })}${field("Bidang materi", "speaker-subject", "", { required: true })}</div><button type="submit" class="btn wide mt">Simpan narasumber ${i("plus")}</button></form><p class="field-hint mt">Data contoh tersimpan lokal. Tiga narasumber bawaan dapat dikonfigurasi setelah daftar ditetapkan.</p></aside></div>`;
  views.s13 = (s, u) => {
    const isL1 = u.instrumentTab === "l1";
    return `${heading("Pengelolaan evaluasi", "Instrumen & bank soal.", "Versi yang tepat untuk setiap sesi.")}${tabs(
      [
        ["l1", "Evaluasi L1"],
        ["pre", "Pretest"],
        ["post", "Post-test L2"],
      ],
      u.instrumentTab,
      "instrument",
    )}<div class="two-col"><section class="panel"><div class="panel-header"><div><h2>${isL1 ? "Lima aspek pembelajaran" : "Bank soal contoh"}</h2><p class="small muted mt">5 pertanyaan · ${isL1 ? "Skala 1–6" : "Pilihan tunggal"}</p></div>${pill("Draf contoh")}</div>${(isL1 ? aspects : L.questions.map((x) => ({ short: x[0] }))).map((q, n) => `<div class="list-row"><span class="number">0${n + 1}</span><div class="grow"><strong>${q.short}</strong><p class="muted">${isL1 ? "Skala 1–6" : "Soal demonstrasi antarmuka"}</p></div>${b(i("eye"), "instrument-preview", "icon-btn", `data-index="${n}" aria-label="Lihat pertanyaan ${n + 1}"`)}</div>`).join("")}<p class="table-note">${isL1 ? "Pilihan nilai tidak dipilih otomatis." : "Bukan bank soal resmi. Belum digunakan untuk penilaian."}</p></section><aside class="stack"><form id="instrument-form" class="panel" novalidate><h3 class="mb">Penggunaan instrumen</h3><div class="error-summary" tabindex="-1" role="alert"></div><div class="stack">${field("Kegiatan", "instrument-activity", "Pilih kegiatan", { options: ["Pilih kegiatan", "Literasi Kebanksentralan", ...s.activities.map((t) => t.name)] })}${field("Sesi", "instrument-session", "Pilih sesi", { options: ["Pilih sesi", "Peran dan Tugas Bank Indonesia"] })}</div><p class="small muted mt">Versi yang sudah digunakan tetap tersimpan untuk menjaga riwayat.</p><button type="submit" class="btn wide mt">Simpan versi</button><div class="mt">${a(isL1 ? "evaluasi-l1" : u.instrumentTab === "pre" ? "pretest" : "post-test", "Pratinjau peserta", "text-link", "eye")}</div></form>${s.instrumentVersions.length ? `<section class="panel"><h3>Riwayat versi demo</h3>${s.instrumentVersions.map((t, n) => `<div class="list-row"><div><strong>Versi ${n + 1} · ${e(t.type)}</strong><p class="muted">${e(t.activity)}</p></div></div>`).join("")}</section>` : ""}</aside></div>`;
  };
  views.s14 = (s, u) =>
    `${heading("Evaluasi pembelajaran", "Analisis evaluasi L1.", "Peran dan Tugas Bank Indonesia · Triani Susanti", pill("Contoh data · 7 April 2026"))}${L.filters(u)}${L.hasSnapshot(u) ? `${L.feature()}<div class="two-col section-gap"><section class="panel"><div class="panel-header"><h2>Rincian lima aspek</h2></div>${L.aspectTable(true)}<p class="table-note">141 respons bertanggal valid. Satu baris ringkasan dikecualikan.</p></section><aside class="panel">${L.empty("Riwayat tahunan belum tersedia.", "Data contoh mencakup satu tanggal.", "calendar-range")}</aside></div><section class="panel section-gap"><div class="row between wrap"><div><h3>Masukan peserta</h3><p class="small muted mt">Identitas disembunyikan</p></div>${b("Tinjau masukan " + i("arrow-right"), "feedback", "btn")}</div></section>` : `<section class="panel">${L.empty("Belum ada data untuk filter ini.", "Pilih kembali periode contoh untuk melihat rincian lima aspek.", "search-x", b("Tampilkan data contoh", "reset-filters", "btn"))}</section>`}`;
  views.s15 = () =>
    `${heading("Evaluasi pembelajaran", "Analisis evaluasi L2.", "Perbandingan pretest dan post-test berpasangan.")}<section class="panel"><div class="empty-state horizontal"><span class="empty-icon">${i("files")}</span><div><h2>Hasil L2 belum tersedia.</h2><p class="mt">Analisis tampil setelah data pretest dan post-test tersedia.</p><div class="mt">${b("Kelola instrumen L2 " + i("arrow-right"), "manage-l2", "btn")}</div></div></div></section><div class="two-col equal section-gap"><section class="panel"><h3>Ringkasan penilaian</h3><dl class="key-values">${["Pasangan pre/post", "Rata-rata pretest", "Rata-rata post-test"].map((t) => `<div><dt>${t}</dt><dd class="quiet">Belum tersedia</dd></div>`).join("")}</dl></section><section class="panel soft"><h3 class="mb">Data yang diperlukan</h3><ul class="check-list">${["Peserta yang sama", "Sesi yang sesuai", "Versi tes yang sebanding", "Hasil penilaian"].map((t) => `<li>${i("circle-check")} ${t}</li>`).join("")}</ul></section></div>`;
  views.s16 = (s, u) =>
    `${heading("Master data", "Peserta & mitra.", "Satukan data kepesertaan dan nama institusi.", b(i("upload") + " Impor data", "open-import", "btn"))}${tabs(
      [
        ["institutions", "Institusi"],
        ["participants", "Peserta"],
        ["import", "Impor data"],
      ],
      u.peopleTab,
      "people",
    )}${u.peopleTab === "institutions" ? `<section class="panel"><div class="table-wrap"><table class="institutions-table"><caption class="sr-only">Institusi dalam sumber data</caption><thead><tr><th>Institusi resmi</th><th>Alias</th><th>KPwBI</th><th>Tindakan</th></tr></thead><tbody>${institutions.map((n, j) => `<tr><td><strong>${n}</strong></td><td>${["UNTIRTA", "UIN SMH Banten", "UIN Jakarta"][j]}</td><td>Banten</td><td>${b("Lihat", "institution-detail", "btn ghost", `data-index="${j}"`)}</td></tr>`).join("")}</tbody></table></div></section><section class="import-card section-gap"><span class="icon-disc">${i("sheet")}</span><div class="grow"><h3>Pratinjau impor Excel</h3><p><span class="import-count">141</span> baris respons · 1 baris ringkasan dikecualikan</p><p>Periksa variasi nama institusi sebelum mengimpor.</p></div>${b("Tinjau pemetaan", "mapping", "btn secondary")}</section>` : u.peopleTab === "participants" ? `<section class="panel">${L.empty("Identitas peserta tidak disertakan.", "Demo menggunakan ringkasan 141 respons tanpa nama, nomor mahasiswa, atau data pribadi lainnya.", "users-round")}</section>` : `<section class="panel"><h2>Pratinjau berkas lokal</h2><p class="muted mt">Baca header dan jumlah baris berkas contoh. Berkas tidak dikirim ke server dan tidak disimpan.</p><div class="mt">${field("Pilih berkas CSV", "import-file", "", { type: "file", extra: 'accept=".csv,text/csv" data-change="import-file"', help: "Gunakan CSV maksimal 2 MB, dengan baris pertama sebagai nama kolom." })}</div><div id="import-preview" class="mt">${u.importPreview || info("Untuk sumber Excel, simpan lembar kerja sebagai CSV terlebih dahulu. Impor ke database belum tersedia.")}</div></section>`}`;
  views.s17 = (s, u) =>
    `${heading("Ruang mitra", "Ringkasan institusi.", institutions[0], pill(i("lock-keyhole") + " Akses lihat"))}${L.filters(u, true)}<div class="two-col wide-left"><section class="panel partner-empty empty-state"><img src="assets/campus.png" alt="Ilustrasi kampus dan dokumen" width="1448" height="1086"><h2>Data agregat belum ditampilkan.</h2><p>Ringkasan tersedia setelah data dipetakan dan akses diberikan.</p></section><aside class="stack"><section class="panel soft scope-panel"><h3>Cakupan akses</h3>${[
      ["check", "Institusi sendiri", "Dapat melihat ringkasan institusi ini."],
      ["check", "Ringkasan evaluasi", "Dapat melihat hasil evaluasi agregat."],
      [
        "lock-keyhole",
        "Tanpa identitas peserta",
        "Data pribadi peserta tidak ditampilkan demi menjaga privasi.",
      ],
    ]
      .map(
        ([ic, t, d]) =>
          `<div class="scope-row"><span class="icon-disc">${i(ic)}</span><div><h4>${t}</h4><p>${d}</p></div></div>`,
      )
      .join(
        "",
      )}</section><section class="panel"><h3>Materi pembelajaran</h3><div class="row start mt"><span class="icon-disc">${i("book-open")}</span><div><h4>Literasi Kebanksentralan</h4><p class="small muted mt">Jelajahi materi pembelajaran untuk memperkuat pemahaman.</p>${a("materi", "Lihat materi")}</div></div></section></aside></div>`;
  views.s18 = (s, u) =>
    `${heading("Ringkasan & ekspor", "Laporan pembelajaran.", "Pilih cakupan dan komponen laporan yang diperlukan.")}${L.filters(u)}<div class="two-col"><form id="report-form" class="panel export-control" novalidate><h2>Isi laporan</h2><div class="error-summary" tabindex="-1" role="alert"></div><label class="checkbox-row"><input type="checkbox" id="report-l1" name="report-l1" checked><span><strong>Ringkasan evaluasi L1</strong><br><small class="muted">Rata-rata 5,30 / 6 · 141 respons valid</small></span></label><label class="checkbox-row"><input type="checkbox" id="report-aspects" name="report-aspects" checked><span><strong>Rincian lima aspek</strong><br><small class="muted">Nilai rata-rata dan jumlah respons</small></span></label><label class="checkbox-row"><input type="checkbox" disabled><span class="quiet">Analisis L2<br><small>Belum tersedia</small></span></label><hr><h3>Format laporan</h3><div class="export-options"><label class="format-option"><input type="radio" name="report-format" value="pdf" checked>${i("file-text")}<span><strong>PDF</strong><small>Dokumen siap unduh</small></span></label><label class="format-option"><input type="radio" name="report-format" value="xlsx">${i("sheet")}<span><strong>Excel</strong><small>Workbook .xlsx</small></span></label></div>${!L.hasSnapshot(u) ? info("Filter saat ini tidak memiliki data. Pilih semua institusi, 2026, dan 7 April 2026.", "warning") : info("Laporan memuat data agregat contoh. Identitas peserta tidak disertakan.")}<button class="btn wide mt" type="submit" ${!L.hasSnapshot(u) ? "disabled" : ""}>${i("download")} Siapkan laporan</button></form><aside class="stack"><section class="panel soft"><h3>Pratinjau cakupan</h3><dl class="key-values"><div><dt>Periode</dt><dd>${e(u.filters.date)}</dd></div><div><dt>Institusi</dt><dd>${e(u.filters.institution)}</dd></div><div><dt>Respons valid</dt><dd>${L.hasSnapshot(u) ? "141" : "Belum tersedia"}</dd></div><div><dt>Skala</dt><dd>1–6</dd></div></dl></section><section class="panel"><h3>Riwayat ekspor lokal</h3>${
      s.reports.length
        ? s.reports
            .slice(-5)
            .reverse()
            .map(
              (t) =>
                `<div class="list-row"><span class="icon-disc">${i("file-down")}</span><div><strong>${e(t.label)}</strong><p class="muted">${e(t.date)} · ${e(t.status)}</p></div></div>`,
            )
            .join("")
        : '<p class="small quiet mt">Belum ada laporan yang disiapkan.</p>'
    }</section></aside></div>`;
  views.s20 = (s) =>
    `${heading("Pengaturan ruang kerja", "Peran & hak akses.", "Cakupan yang jelas untuk setiap pengguna.", pill("Konfigurasi demo"))}<div class="two-col wide-left"><section class="panel"><h2 class="mb">Matriks akses</h2><div class="table-wrap"><table class="permissions-table"><thead><tr><th>Kemampuan</th><th>Pengelola BI</th><th>Peserta</th><th>Mitra PT</th></tr></thead><tbody>${[
      ["Kelola kegiatan", true, false, false],
      ["Baca materi", true, true, true],
      ["Isi evaluasi", false, true, false],
      ["Lihat evaluasi agregat", true, false, true],
    ]
      .map(
        ([t, ...vals]) =>
          `<tr><td>${t}</td>${vals.map((v) => `<td>${v ? i("check") + '<span class="sr-only">Diizinkan</span>' : '<span class="quiet" aria-label="Tidak diizinkan">—</span>'}</td>`).join("")}</tr>`,
      )
      .join(
        "",
      )}</tbody></table></div><div class="mt">${info("Pilihan peran pada demo hanya mengubah pratinjau tampilan. Otorisasi pengguna yang sebenarnya memerlukan pemeriksaan di server.")}</div></section><aside class="panel"><h3 class="mb">Cakupan mitra</h3><form id="access-form"><div class="stack">${field("Kantor perwakilan", "access-office", s.access.office, { options: ["KPwBI Provinsi Banten"] })}${field("Institusi", "access-institution", s.access.institution, { options: institutions })}<label class="checkbox-row"><input type="checkbox" name="access-aggregate" ${s.access.aggregate ? "checked" : ""}><span>Izinkan tampilan agregat<br><small class="quiet">Tanpa identitas peserta</small></span></label></div><button class="btn wide mt" type="submit">Simpan pengaturan demo</button><p class="field-hint mt">Pengaturan disimpan sebagai contoh, tidak memberikan hak akses nyata.</p></form></aside></div>`;
})();
