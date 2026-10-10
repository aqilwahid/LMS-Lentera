/* LENTERA: data agregat contoh, tanpa identitas peserta. */
window.L = (() => {
  const pages = [
    ["s01", "pengenalan", "Pengenalan LENTERA", "public"],
    ["s02", "masuk", "Masuk ke LENTERA", "public"],
    ["s03", "profil", "Profil peserta", "participant"],
    ["s04", "beranda", "Beranda peserta", "participant"],
    ["s05", "kegiatan", "Detail kegiatan", "shared"],
    ["s06", "materi", "Materi pembelajaran", "shared"],
    ["s07", "pretest", "Pretest", "participant"],
    ["s08", "evaluasi-l1", "Evaluasi L1", "participant"],
    ["s09", "post-test", "Post-test L2", "participant"],
    ["s10", "ringkasan", "Ringkasan BI", "admin"],
    ["s11", "buat-kegiatan", "Buat kegiatan", "admin"],
    ["s12", "kelola-materi", "Materi & narasumber", "admin"],
    ["s13", "instrumen", "Instrumen & bank soal", "admin"],
    ["s14", "analisis-l1", "Analisis L1", "admin"],
    ["s15", "analisis-l2", "Analisis L2", "admin"],
    ["s16", "peserta-mitra", "Peserta & mitra", "admin"],
    ["s17", "mitra", "Ringkasan institusi", "partner"],
    ["s18", "laporan", "Laporan", "admin"],
    ["s19", "panduan", "Panduan penggunaan", "shared"],
    ["s20", "pengaturan", "Peran & hak akses", "admin"],
  ].map(([id, slug, title, role]) => ({ id, slug, title, role }));
  const institutions = [
    "Universitas Sultan Ageng Tirtayasa",
    "UIN Sultan Maulana Hasanuddin Banten",
    "UIN Syarif Hidayatullah Jakarta",
  ];
  const aspects = [
    { short: "Kejelasan isi", question: "Kejelasan isi materi", value: "5,28" },
    {
      short: "Penjelasan materi",
      question: "Penjelasan tidak menimbulkan ambiguitas",
      value: "5,30",
    },
    {
      short: "Kemudahan istilah",
      question: "Kemudahan memahami istilah",
      value: "5,23",
    },
    {
      short: "Penarikan kesimpulan",
      question: "Kemudahan menarik kesimpulan",
      value: "5,23",
    },
    {
      short: "Tambahan pengetahuan",
      question: "Tambahan pengetahuan yang dirasakan",
      value: "5,46",
    },
  ];
  // Pertanyaan contoh untuk menguji antarmuka; bukan bank soal resmi atau penilaian BI.
  const questions = [
    [
      "Manakah topik yang termasuk dalam pembelajaran kebanksentralan?",
      [
        "Kebijakan moneter",
        "Desain interior",
        "Teknik konstruksi",
        "Tata boga",
      ],
    ],
    [
      "Materi yang sedang dipelajari membahas lembaga apa?",
      [
        "Bank Indonesia",
        "Perpustakaan Nasional",
        "Badan Meteorologi",
        "Museum Nasional",
      ],
    ],
    [
      "Apa langkah yang dapat dilakukan untuk memahami materi lebih lanjut?",
      [
        "Membaca materi dan mengajukan pertanyaan",
        "Melewati seluruh materi",
        "Mengisi jawaban secara acak",
        "Mengabaikan panduan",
      ],
    ],
    [
      "Apa tujuan evaluasi pengalaman belajar L1 pada demo ini?",
      [
        "Memberi umpan balik tentang pembelajaran",
        "Mengubah identitas institusi",
        "Mendaftarkan rekening",
        "Melakukan pembayaran",
      ],
    ],
    [
      "Di mana peserta dapat membuka sumber pembelajaran pada LENTERA?",
      [
        "Menu Materi",
        "Menu Pengaturan akses",
        "Menu Impor data",
        "Menu Peran pengelola",
      ],
    ],
  ];
  const defaults = () => ({
    role: "participant",
    profile: {
      name: "",
      institution: institutions[0],
      category: "Mahasiswa",
      idType: "NIM",
      identity: "",
      department: "",
    },
    preAnswers: [],
    postAnswers: [],
    preCompleted: true,
    postCompleted: false,
    l1: [],
    feedback: "",
    l1Submitted: false,
    speakers: [],
    activities: [],
    draft: {
      step: 0,
      name: "",
      office: "KPwBI Provinsi Banten",
      institution: institutions[0],
      date: "",
      description: "",
      session: "Peran dan Tugas Bank Indonesia",
      speaker: "Triani Susanti",
      material: true,
    },
    instrumentVersions: [],
    reports: [],
    access: {
      office: "KPwBI Provinsi Banten",
      institution: institutions[0],
      aggregate: true,
    },
    settings: { largeText: false },
    guide: "l1",
  });
  const escape = (v) =>
    String(v ?? "").replace(
      /[&<>"']/g,
      (s) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        })[s],
    );
  const icon = (name, cls = "") =>
    `<i data-lucide="${name}" class="icon ${cls}" aria-hidden="true"></i>`;
  const link = (slug, label, cls = "text-link", iconName = "arrow-right") =>
    `<a class="${cls}" href="#/${slug}">${label}${iconName ? icon(iconName) : ""}</a>`;
  const button = (label, action, cls = "btn", extra = "") =>
    `<button type="button" class="${cls}" data-action="${action}" ${extra}>${label}</button>`;
  const pill = (text, cls = "") => `<span class="pill ${cls}">${text}</span>`;
  const info = (text, cls = "") =>
    `<div class="notice ${cls}">${icon("info")}<div>${text}</div></div>`;
  const heading = (eyebrow, title, subtitle = "", right = "") =>
    `<div class="page-heading"><div>${eyebrow ? `<p class="eyebrow">${eyebrow}</p>` : ""}<h1 tabindex="-1">${title}</h1>${subtitle ? `<p class="subtitle">${subtitle}</p>` : ""}</div>${right ? `<div class="heading-action">${right}</div>` : ""}</div>`;
  const field = (label, name, value = "", opts = {}) =>
    `<div class="field ${opts.wide ? "span-2" : ""}"><label for="${name}">${label}${opts.required ? ' <span aria-hidden="true">*</span>' : ""}</label>${opts.options ? `<select id="${name}" name="${name}" ${opts.required ? "required" : ""}>${opts.options.map((o) => `<option value="${escape(o)}" ${o === value ? "selected" : ""}>${escape(o)}</option>`).join("")}</select>` : opts.textarea ? `<textarea id="${name}" name="${name}" rows="${opts.rows || 3}" ${opts.required ? "required" : ""}>${escape(value)}</textarea>` : `<input id="${name}" name="${name}" type="${opts.type || "text"}" value="${escape(value)}" placeholder="${escape(opts.placeholder || "")}" ${opts.required ? "required" : ""} ${opts.extra || ""}>`}${opts.help ? `<p class="field-hint">${opts.help}</p>` : ""}</div>`;
  const tabs = (items, selected, group) =>
    `<div class="tabs" role="tablist" aria-label="${group}">${items.map(([id, label]) => `<button type="button" role="tab" aria-selected="${selected === id}" class="tab ${selected === id ? "active" : ""}" data-action="tab" data-group="${group}" data-value="${id}">${label}</button>`).join("")}</div>`;
  return {
    pages,
    institutions,
    aspects,
    questions,
    defaults,
    escape,
    icon,
    link,
    button,
    pill,
    info,
    heading,
    field,
    tabs,
    views: {},
  };
})();
