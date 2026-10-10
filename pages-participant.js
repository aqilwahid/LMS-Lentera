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
    institutions,
    questions,
  } = L;
  const pdf = "assets/materi-kebanksentralan.pdf";
  const help = () =>
    `<section class="panel help-card"><div class="row start"><span class="icon-disc">${i("book-open")}</span><div><h3>Butuh panduan?</h3><p>Temukan informasi untuk mengikuti pembelajaran.</p>${a("panduan", "Lihat panduan peserta")}</div></div></section>`;
  L.timeline = (s) =>
    `<ol class="timeline">${[
      [
        "Pretest",
        s.preCompleted ? "Sudah dikirim" : "Belum dikerjakan",
        s.preCompleted ? "complete" : "",
        "pretest",
      ],
      ["Materi", "Sedang dipelajari", "current", "materi"],
      [
        "Evaluasi L1",
        s.l1Submitted ? "Sudah diisi" : "Belum diisi",
        s.l1Submitted ? "complete" : "",
        "evaluasi-l1",
      ],
      [
        "Post-test L2",
        s.postCompleted ? "Sudah dikirim" : "Belum dikerjakan",
        s.postCompleted ? "complete" : "",
        "post-test",
      ],
    ]
      .map(
        ([title, status, cls, slug]) =>
          `<li><span class="timeline-dot ${cls}">${cls === "complete" ? i("check") : ""}</span><div><h4><a href="#/${slug}" style="color:inherit">${title}</a></h4><p>${status}</p></div></li>`,
      )
      .join("")}</ol>`;
  views.s01 = () =>
    `<div class="landing"><section class="landing-hero"><div class="landing-copy"><p class="eyebrow">Literasi kebanksentralan</p><h1>Kenali peran BI.<br>Perluas wawasan bersama LENTERA.</h1><p class="subtitle">Materi, pembelajaran, dan evaluasi kebanksentralan dalam satu ruang.</p>${a("masuk", "Masuk ke LENTERA", "btn large")}</div><img class="landing-art" src="assets/lighthouse.png" width="1254" height="1254" alt="Mercusuar di antara buku, menerangi perjalanan belajar."></section><section class="learning-steps" aria-label="Alur belajar">${[
      [
        "01",
        "book-open",
        "Ikuti sesi",
        "Mulai perjalanan belajar sesuai minat Anda.",
      ],
      [
        "02",
        "file-text",
        "Pelajari materi",
        "Akses materi kebanksentralan yang terkurasi.",
      ],
      [
        "03",
        "clipboard-check",
        "Bagikan evaluasi",
        "Berikan penilaian terhadap pengalaman pembelajaran Anda.",
      ],
    ]
      .map(
        ([n, ic, t, d]) =>
          `<div class="learning-step"><span class="step-no">${n}</span><span class="icon-disc">${i(ic)}</span><div><h3>${t}</h3><p>${d}</p></div></div>`,
      )
      .join(
        "",
      )}</section><section class="intro-section"><div><p class="eyebrow">Tentang LENTERA</p><h2>Belajar dengan arah yang jelas.</h2><p>LENTERA menghubungkan peserta, pengelola BI, dan mitra dalam perjalanan literasi kebanksentralan yang tertata.</p></div>${a("panduan", "Jelajahi panduan", "btn secondary", "book-open")}</section><footer class="public-footer"><span>LENTERA · Pelopor Literasi dan Edukasi Kebanksentralan</span>${b(i("layout-grid") + " Jelajahi 20 layar", "catalog", "demo-launch")}</footer></div>`;
  views.s02 = (s, u) => {
    const admin = u.loginRole === "admin",
      partner = u.loginRole === "partner";
    return `<section class="login-layout"><div class="login-form-side"><div class="login-form"><p class="eyebrow">Selamat datang kembali</p><h1>Masuk ke LENTERA.</h1><p class="muted">Satu ruang untuk melanjutkan pembelajaran Anda.</p><div class="segmented" aria-label="Jenis akun">${b("Peserta & mitra", "login-role", admin ? "" : "active", 'data-value="participant"')}${b("Pegawai BI", "login-role", admin ? "active" : "", 'data-value="admin"')}</div>${!admin ? `<div class="field"><label for="demo-role">Coba sebagai</label><select id="demo-role" data-change="login-role"><option value="participant" ${!partner ? "selected" : ""}>Peserta</option><option value="partner" ${partner ? "selected" : ""}>Mitra perguruan tinggi</option></select></div>` : ""}<div class="field"><label for="demo-email">Alamat email</label><input id="demo-email" type="email" placeholder="nama@institusi.ac.id" disabled></div><div class="field"><label for="demo-password">Kata sandi</label><input id="demo-password" type="password" placeholder="Login produksi belum terhubung" disabled></div><div class="row between mt"><span class="small muted">${i("shield-check")} Pratinjau antarmuka</span>${b("Lupa kata sandi?", "auth-help", "btn ghost")}</div>${b("Masuk demo " + (admin ? "pengelola BI" : partner ? "mitra" : "peserta") + " " + i("arrow-right"), "enter-demo", "btn wide")}<p class="field-hint mt">Demo dapat dicoba tanpa email atau kata sandi. Tidak ada autentikasi atau pengiriman data ke server.</p><hr><p class="small muted">Sudah menerima undangan?</p>${b("Lihat alur aktivasi akun", "activation-help", "text-link btn ghost")}</div></div><div class="login-art"><img src="assets/lighthouse.png" alt="Mercusuar LENTERA di antara buku" width="1254" height="1254"><div class="login-art-caption"><h2>Wawasan yang menerangi.</h2><p class="muted">Pelajari peran bank sentral, pahami materinya, lalu bagikan pengalaman belajar Anda.</p></div></div></section>`;
  };
  views.s03 = (s) =>
    `${heading("Ruang peserta", "Lengkapi profil Anda.", "Profil membantu menghubungkan pembelajaran dengan institusi Anda.")}<form id="profile-form" class="panel profile-card" novalidate><div class="profile-header"><span class="avatar">${i("user-round")}</span><div><h2>Identitas peserta</h2><p class="muted">Gunakan data contoh untuk mencoba formulir ini.</p></div>${pill("Demo lokal")}</div><div class="error-summary" tabindex="-1" role="alert"></div><div class="form-grid">${field("Nama lengkap", "profile-name", s.profile.name, { required: true, placeholder: "Masukkan nama contoh" })}${field("Institusi", "profile-institution", s.profile.institution, { options: institutions, required: true })}${field("Kategori peserta", "profile-category", s.profile.category, { options: ["Mahasiswa", "GenBI", "Akademisi", "Umum"] })}${field("Jenis identitas", "profile-idType", s.profile.idType, { options: ["NIM", "NIP", "Identitas lainnya"] })}${field("Nomor identitas", "profile-identity", s.profile.identity, { required: true, placeholder: "Nomor contoh, bukan identitas asli" })}${field("Jurusan / unit kerja", "profile-department", s.profile.department, { required: true, placeholder: "Contoh: Manajemen" })}</div><div class="mt">${info("Data demo tersimpan hanya di browser ini. Anda dapat menghapusnya melalui menu “Demo · 20 layar”.")}</div><div class="form-actions">${a("beranda", "Lewati dulu", "btn ghost", "")}<button class="btn" type="submit">Simpan & lanjutkan ${i("arrow-right")}</button></div></form>`;
  views.s04 = (s) =>
    `${heading("Ruang peserta", '<span class="desktop-title">Selamat datang di ruang belajar.</span><span class="mobile-title">Ruang belajar Anda.</span>', "Lanjutkan pembelajaran dari sesi terakhir.", pill("Contoh alur peserta"))}<div class="home-layout"><section class="lesson-hero"><div class="lesson-copy"><p class="eyebrow">Materi pilihan</p><h2>Peran dan Tugas Bank Indonesia</h2><p class="speaker">Triani Susanti</p>${a("materi", "Lanjutkan sesi", "btn")}</div><img src="assets/learning.png" alt="Miniatur bank dan buku terbuka sebagai simbol pembelajaran" width="1448" height="1086"></section><aside class="stack"><section class="panel timeline-panel"><h3>Langkah belajar Anda</h3>${L.timeline(s)}</section>${help()}</aside><section class="course-section"><h2 class="home-section-heading">Kegiatan saya</h2><div class="course-row"><span class="icon-disc">${i("notebook-text")}</span><div class="grow"><h3>Literasi Kebanksentralan</h3><p>Pemahaman dasar, peran, dan kontribusi Bank Indonesia.</p></div><span class="date">${i("calendar-days")} 7 April 2026</span>${a("kegiatan", "Lihat kegiatan")}</div>${s.activities.length ? `<p class="table-note">${s.activities.length} kegiatan demo tersimpan di ruang pengelola BI.</p>` : ""}</section></div>`;
  views.s05 = (s) =>
    `${a(s.role === "partner" ? "mitra" : "beranda", s.role === "partner" ? "Kembali ke ringkasan" : "Kembali ke beranda", "back-link", "arrow-left")}${heading("Kegiatan pembelajaran", "Literasi Kebanksentralan.", "Materi, sesi, dan evaluasi dalam satu perjalanan.")}<div class="two-col wide-left"><div class="stack"><section class="event-banner"><p class="eyebrow">KPwBI Provinsi Banten</p><h2>Memahami peran bank sentral.</h2><div class="event-meta"><span>${i("calendar-days")} 7 April 2026</span><span>${i("user-round")} Triani Susanti</span></div></section><section class="panel"><div class="panel-header"><h2>Sesi pembelajaran</h2>${pill("01 sesi")}</div><div class="list-row"><span class="icon-disc">${i("book-open")}</span><div class="grow"><h3>Peran dan Tugas Bank Indonesia</h3><p class="muted">Triani Susanti · Materi PDF</p></div>${a("materi", "Buka sesi")}</div></section><section class="panel"><h2>Alur sesi</h2>${[
      [
        "clipboard-list",
        "Pretest",
        "Kenali pemahaman awal sebelum belajar.",
        "pretest",
        s.preCompleted ? "Sudah dikirim" : "Belum dikerjakan",
      ],
      [
        "file-text",
        "Materi pembelajaran",
        "Pelajari materi kebanksentralan, 11 halaman.",
        "materi",
        "Sedang dipelajari",
      ],
      [
        "message-square-text",
        "Evaluasi L1",
        "Berikan penilaian terhadap materi dan narasumber.",
        "evaluasi-l1",
        s.l1Submitted ? "Sudah diisi" : "Belum diisi",
      ],
      [
        "clipboard-check",
        "Post-test L2",
        "Lihat kembali pemahaman setelah belajar.",
        "post-test",
        s.postCompleted ? "Sudah dikirim" : "Belum dikerjakan",
      ],
    ]
      .filter((row) => s.role !== "partner" || row[3] === "materi")
      .map(
        ([ic, t, d, slug, status]) =>
          `<div class="list-row"><span class="icon-disc">${i(ic)}</span><div class="grow"><h4>${t}</h4><p class="muted">${d}</p></div><span class="task-status quiet">${status}</span>${a(slug, '<span class="sr-only">Buka ' + t + "</span>", "icon-btn", "chevron-right")}</div>`,
      )
      .join(
        "",
      )}</section></div><aside class="stack"><section class="panel"><h3>Informasi kepesertaan</h3><dl class="key-values"><div><dt>Institusi peserta</dt><dd>${e(s.role === "partner" ? institutions[0] : s.profile.institution)}</dd></div><div><dt>Status</dt><dd>${pill(s.role === "partner" ? "Akses lihat" : "Terdaftar · demo")}</dd></div><div><dt>Wilayah</dt><dd>Banten</dd></div></dl></section>${help()}</aside></div>`;
  views.s06 = (s, u) =>
    `${a(s.role === "admin" ? "kelola-materi" : "kegiatan", "Kembali ke " + (s.role === "admin" ? "pengelolaan materi" : "kegiatan"), "back-link", "arrow-left")}${heading("Sumber pembelajaran", "Peran dan Tugas Bank Indonesia.", "Triani Susanti · Literasi Kebanksentralan")}<div class="pdf-layout"><aside class="panel pdf-menu"><h3>Materi sesi</h3><div class="pdf-selected">${i("file-text")}<strong>Edukasi dan Literasi Kebanksentralan.pdf</strong><span class="small muted">PDF · 11 halaman</span></div>${s.role === "participant" ? `<div class="list-row">${i("clipboard-list")}${a("pretest", "Pretest")}</div><div class="list-row">${i("message-square-text")}${a("evaluasi-l1", "Evaluasi L1")}</div><div class="list-row">${i("clipboard-check")}${a("post-test", "Post-test L2")}</div>` : info("Materi asli tersedia untuk dibaca atau diunduh.")}</aside><section><div class="pdf-toolbar"><div class="row"><label for="pdf-page">Halaman</label><input id="pdf-page" type="number" min="1" max="11" value="${u.pdfPage}" data-change="pdf-page"><span>/ 11</span></div><div class="row"><label class="sr-only" for="pdf-zoom">Perbesaran PDF</label><select id="pdf-zoom" data-change="pdf-zoom">${[75, 100, 125, 150].map((v) => `<option ${v === u.pdfZoom ? "selected" : ""}>${v}%</option>`).join("")}</select><a class="btn secondary" href="${pdf}" download="Edukasi-dan-Literasi-Kebanksentralan.pdf">${i("download")} Unduh</a></div></div><iframe class="pdf-frame" id="pdf-viewer" title="Materi PDF: Edukasi dan Literasi Kebanksentralan" src="${pdf}#page=${u.pdfPage}&zoom=${u.pdfZoom}&toolbar=0"></iframe><p class="pdf-fallback">Jika pembaca PDF perangkat tidak mendukung navigasi di sini, <a href="${pdf}" target="_blank" rel="noopener">buka PDF di tab baru ${i("external-link")}</a>.</p><div class="form-actions">${s.role === "participant" ? a("evaluasi-l1", "Lanjutkan evaluasi", "btn") : a(s.role === "admin" ? "kelola-materi" : "mitra", "Kembali ke ruang " + (s.role === "admin" ? "pengelola" : "mitra"), "btn")}</div></section></div>`;
  L.quiz = (s, u, kind) => {
    const n = u.question,
      answers = s[kind + "Answers"],
      post = kind === "post",
      q = questions[n];
    return `${a("kegiatan", "Kembali ke sesi", "back-link", "arrow-left")}${heading("Evaluasi pembelajaran", post ? "Post-test L2." : "Pretest.", "Peran dan Tugas Bank Indonesia", pill("Contoh soal demo"))}<div class="two-col quiz-layout"><form id="quiz-form" class="panel" data-kind="${kind}" novalidate><div class="row between"><span class="small muted">Pertanyaan ${n + 1} dari ${questions.length}</span>${pill("Pilihan tunggal", "neutral")}</div><div class="error-summary" tabindex="-1" role="alert"></div><fieldset><legend class="quiz-question">${q[0]}</legend>${q[1].map((option, j) => `<label class="answer-option" for="answer-${j}"><input type="radio" id="answer-${j}" name="quiz-answer" value="${j}" ${answers[n] === j ? "checked" : ""} data-change="quiz-answer" data-kind="${kind}"><span>${e(option)}</span></label>`).join("")}</fieldset><div class="quiz-actions">${b(i("arrow-left") + " Sebelumnya", "quiz-prev", "btn secondary", n === 0 ? "disabled" : "")}<button class="btn" type="submit">${n === 4 ? "Kirim jawaban" : "Berikutnya"} ${i(n === 4 ? "check" : "arrow-right")}</button></div><p class="field-hint mt" id="quiz-draft-status">Draf jawaban tersimpan di browser ini.</p></form><aside class="panel quiz-sidebar"><h3>Navigasi soal</h3><div class="question-grid">${questions.map((_, j) => `<button type="button" class="${j === n ? "active" : ""} ${answers[j] !== undefined ? "answered" : ""}" data-action="quiz-jump" data-index="${j}" aria-label="Pertanyaan ${j + 1}${answers[j] !== undefined ? ", terisi" : ""}" ${j === n ? 'aria-current="step"' : ""}>${j + 1}</button>`).join("")}</div><p class="small muted">${answers.filter((x) => Number.isInteger(x)).length} dari 5 pertanyaan terisi</p><div class="mt">${info("Pertanyaan contoh untuk mencoba alur. Nilai resmi dan bank soal BI belum dihubungkan.")}</div></aside></div>`;
  };
  views.s07 = (s, u) => L.quiz(s, u, "pre");
  views.s08 = (s) =>
    `${a("kegiatan", "Kembali ke sesi", "back-link", "arrow-left")}${heading("Evaluasi pembelajaran", "Bagikan pengalaman belajar Anda.", "Peran dan Tugas Bank Indonesia · Triani Susanti")}<div class="two-col wide-left"><form id="l1-form" class="panel l1-panel" novalidate><div class="l1-header"><div><h2>Evaluasi L1</h2><p class="small muted mt">Pilih satu nilai untuk setiap pernyataan.</p></div>${pill(`<span id="l1-count">${s.l1.filter(Boolean).length}</span> / 5 terisi`)}</div>${s.l1Submitted ? `<div class="mt">${info("Evaluasi demo sudah tersimpan. Anda dapat menyesuaikan jawaban di bawah ini.", "success")}</div>` : ""}<div class="error-summary" tabindex="-1" role="alert"></div>${L.aspects.map((q, j) => `<fieldset class="rating-question" id="l1-question-${j}" tabindex="-1"><legend><span class="question-label"><span>0${j + 1}</span><span>${q.question}</span></span></legend><div class="rating-options">${[1, 2, 3, 4, 5, 6].map((n) => `<label class="rating-option" for="rating-${j}-${n}"><input id="rating-${j}-${n}" type="radio" name="l1-${j}" value="${n}" data-change="l1-rating" data-index="${j}" ${s.l1[j] === n ? "checked" : ""}><span>${n}</span></label>`).join("")}</div></fieldset>`).join("")}<div class="mt">${field("Masukan untuk pembelajaran berikutnya (opsional)", "l1-feedback", s.feedback, { textarea: true, placeholder: "Tulis saran Anda", help: "Gunakan masukan contoh, tanpa data pribadi." })}</div><div class="form-actions">${b("Simpan draf", "save-l1", "btn secondary")}<button class="btn" type="submit">${s.l1Submitted ? "Perbarui evaluasi" : "Kirim evaluasi"} ${i("arrow-right")}</button></div></form><aside class="stack l1-aside"><section class="panel soft"><span class="icon-disc">${i("message-square-text")}</span><h3 class="mt">Setiap masukan berarti.</h3><p class="small muted mt">Penilaian Anda membantu memahami pengalaman belajar dari lima aspek.</p><hr><p class="small"><strong>Skala penilaian 1–6</strong></p><p class="small muted">Pilih angka yang paling sesuai dengan pengalaman Anda. Seluruh pertanyaan perlu diisi.</p></section>${help()}</aside></div>`;
  views.s09 = (s, u) =>
    u.postMode === "quiz"
      ? L.quiz(s, u, "post")
      : `${heading("Evaluasi pembelajaran", "Post-test L2.", "Peran dan Tugas Bank Indonesia", pill(s.postCompleted ? "Kiriman demo" : "Pratinjau hasil"))}<div class="two-col"><section class="panel result-panel"><div class="success-mark">${i("check")}</div><h2>Jawaban sudah dikirim.</h2><p class="muted">${s.postCompleted ? "Jawaban demo Anda sudah tersimpan di browser ini." : "Contoh tampilan konfirmasi setelah peserta mengirim jawaban."}</p><div class="result-status">${i("clock-3")} Menunggu penilaian</div><dl class="key-values"><div><dt>Jenis evaluasi</dt><dd>Post-test L2</dd></div><div><dt>Status pengiriman</dt><dd>${s.postCompleted ? "Tersimpan lokal" : "Terkonfirmasi · contoh"}</dd></div><div><dt>Nilai</dt><dd>Belum tersedia</dd></div></dl>${a("kegiatan", "Kembali ke sesi", "btn", "arrow-left")}<div class="mt">${b(s.postCompleted ? "Tinjau / coba ulang jawaban" : "Coba alur post-test", "start-post", "btn ghost")}</div></section><aside class="stack"><section class="panel"><h3>Langkah lainnya</h3>${L.timeline(s)}</section>${info("Hasil penilaian resmi tampil setelah proses penilaian terhubung. Demo ini tidak membuat nilai.")}</aside></div>`;
  const guides = {
    start: {
      title: "Mulai belajar di LENTERA",
      intro:
        "Gunakan ruang peserta untuk mengikuti sesi, membuka materi, dan mengisi evaluasi.",
      steps: [
        "Buka menu Masuk, lalu pilih demo peserta.",
        "Lengkapi profil dengan data contoh.",
        "Buka kegiatan Literasi Kebanksentralan.",
        "Ikuti alur pretest, materi, evaluasi L1, dan post-test.",
      ],
      slug: "beranda",
      cta: "Buka ruang peserta",
    },
    material: {
      title: "Membaca materi pembelajaran",
      intro:
        "Materi tersedia sebagai PDF agar dapat dipelajari kembali sesuai kebutuhan.",
      steps: [
        "Buka kegiatan, lalu pilih Buka sesi.",
        "Gunakan halaman dan perbesaran pada pembaca PDF.",
        "Jika PDF tidak tampil, buka di tab baru atau unduh.",
        "Pilih Lanjutkan evaluasi setelah selesai membaca.",
      ],
      slug: "materi",
      cta: "Buka materi",
    },
    l1: {
      title: "Mengisi evaluasi L1",
      intro:
        "Evaluasi L1 mengumpulkan pengalaman Anda terhadap materi dan narasumber melalui lima aspek pembelajaran.",
      steps: [
        "Buka Evaluasi L1 dari sesi yang diikuti.",
        "Baca setiap pernyataan, lalu pilih satu nilai pada skala 1–6.",
        "Isi seluruh lima pernyataan. Tambahkan masukan bila diperlukan.",
        "Periksa jawaban, kemudian pilih Kirim evaluasi.",
      ],
      slug: "evaluasi-l1",
      cta: "Buka evaluasi L1",
    },
    l2: {
      title: "Mengikuti pretest dan post-test",
      intro:
        "Pretest dan post-test digunakan dalam alur evaluasi pemahaman. Versi demo menyediakan lima soal contoh.",
      steps: [
        "Pilih jawaban untuk setiap pertanyaan.",
        "Gunakan navigasi soal untuk meninjau jawaban.",
        "Lengkapi pertanyaan yang belum dijawab.",
        "Kirim jawaban dan buka halaman konfirmasi. Nilai belum tersedia di demo.",
      ],
      slug: "pretest",
      cta: "Coba pretest",
    },
    partner: {
      title: "Memahami akses mitra",
      intro:
        "Ruang mitra dirancang untuk meninjau informasi institusi sendiri dan evaluasi agregat.",
      steps: [
        "Buka demo Mitra perguruan tinggi.",
        "Periksa institusi yang tertera pada ringkasan.",
        "Pilih periode yang ingin dilihat.",
        "Ringkasan agregat akan tersedia setelah pemetaan dan izin akses terhubung. Identitas peserta tidak ditampilkan.",
      ],
      slug: "mitra",
      cta: "Buka ruang mitra",
    },
  };
  L.guideTopics = guides;
  views.s19 = (s, u) => {
    const g = guides[u.guide] || guides.l1,
      menu = [
        ["start", "Mulai menggunakan", "compass"],
        ["material", "Materi pembelajaran", "book-open"],
        ["l1", "Evaluasi L1", "message-square-text"],
        ["l2", "Pretest & post-test", "clipboard-check"],
        ["partner", "Akses mitra", "users-round"],
      ];
    return `${heading("Pusat bantuan", "Panduan penggunaan.", "Temukan langkah yang Anda perlukan.")}<div class="search-field mb">${i("search")}<label class="sr-only" for="guide-search">Cari panduan</label><input id="guide-search" type="search" placeholder="Cari materi, evaluasi, atau akses…" value="${e(u.guideSearch)}" data-input="guide-search"></div><div class="guide-layout"><nav class="panel guide-nav" aria-label="Topik panduan">${menu.map(([id, title, ic]) => `<button type="button" class="${u.guide === id ? "active" : ""}" data-action="guide" data-value="${id}" data-guide-title="${e(title.toLowerCase())}">${i(ic)} ${title}</button>`).join("")}</nav><article class="panel guide-article"><p class="eyebrow">Panduan peserta & mitra</p><h2>${g.title}</h2><p>${g.intro}</p><h3>Langkah-langkah</h3><ol>${g.steps.map((t) => `<li>${t}</li>`).join("")}</ol>${u.guide === "l1" ? `<h3>Contoh pilihan nilai</h3><div class="example-ratings" aria-label="Contoh tampilan enam pilihan, tanpa jawaban terpilih">${[1, 2, 3, 4, 5, 6].map((n) => `<span>${n}</span>`).join("")}</div><p>Setiap pernyataan memiliki enam pilihan. Tidak ada jawaban yang dipilih secara otomatis.</p>` : ""}<div class="mt">${a(g.slug, g.cta, "btn")}</div><hr>${info("Panduan ini menjelaskan prototipe LENTERA. Fitur undangan, login resmi, dan penilaian server belum dihubungkan.")}</article></div>`;
  };
})();
