/* Buildless, browser-only prototype. No authentication, API calls, or server writes. */
(() => {
  "use strict";
  const { escape: e, icon: i, link: a, button: b, pill, info } = L,
    STORAGE = "lentera-demo-v2.1";
  const app = document.getElementById("app"),
    dialog = document.getElementById("app-dialog");
  let state = L.defaults(),
    storageOK = true,
    toastTimer,
    dialogOpener,
    currentPage;
  try {
    const saved = JSON.parse(localStorage.getItem(STORAGE) || "null");
    if (saved) {
      state = {
        ...state,
        ...saved,
        profile: { ...state.profile, ...saved.profile },
        draft: { ...state.draft, ...saved.draft },
        settings: { ...state.settings, ...saved.settings },
        access: { ...state.access, ...saved.access },
      };
    }
  } catch {
    storageOK = false;
  }
  let ui = {
    loginRole: "participant",
    question: 0,
    postMode: "result",
    materialTab: "speakers",
    instrumentTab: "l1",
    peopleTab: "institutions",
    guide: "l1",
    guideSearch: "",
    pdfPage: 1,
    pdfZoom: 100,
    filters: {
      year: "2026",
      date: "7 April 2026",
      institution: "Semua institusi",
    },
    partnerDate: "Semua periode",
    importPreview: "",
  };
  const save = () => {
    try {
      localStorage.setItem(STORAGE, JSON.stringify(state));
      storageOK = true;
    } catch {
      storageOK = false;
    }
  };
  const savedMessage = (label) =>
    storageOK
      ? label + " Tersimpan di browser ini."
      : label +
        " Tersimpan sementara selama tab ini terbuka; penyimpanan browser tidak tersedia.";
  const toast = (text) => {
    const el = document.getElementById("toast");
    el.textContent = text;
    el.classList.add("visible");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove("visible"), 4500);
  };
  const icons = () =>
    window.lucide?.createIcons({ attrs: { "aria-hidden": "true" } });
  const pageForHash = () => {
    const key =
      location.hash.replace(/^#\/?/, "").split("?")[0].toLowerCase() ||
      "pengenalan";
    return L.pages.find(
      (p) =>
        p.slug === key ||
        p.id === key ||
        (["s04m", "s08m", "s10m", "s17m"].includes(key) &&
          p.id === key.slice(0, -1)),
    );
  };
  const participantNav = [
    ["beranda", "Beranda", "house"],
    ["kegiatan", "Kegiatan saya", "calendar-days"],
    ["materi", "Materi", "book-open"],
    ["evaluasi-l1", "Evaluasi", "clipboard-check"],
    ["panduan", "Panduan", "circle-help"],
  ];
  const adminNav = [
    ["ringkasan", "Ringkasan", "house"],
    ["buat-kegiatan", "Kegiatan", "calendar-days"],
    ["kelola-materi", "Materi", "book-open"],
    ["instrumen", "Instrumen", "notebook-tabs"],
    ["analisis-l1", "Analisis L1", "chart-no-axes-combined"],
    ["analisis-l2", "Analisis L2", "chart-column"],
    ["peserta-mitra", "Peserta & Mitra", "users-round"],
    ["laporan", "Laporan", "file-chart-column"],
  ];
  const partnerNav = [
    ["mitra", "Ringkasan", "house"],
    ["kegiatan", "Kegiatan", "calendar-days"],
    ["mitra", "Evaluasi agregat", "chart-column"],
    ["panduan", "Panduan", "book-open"],
  ];
  const isActive = (slug, index = 0) =>
    (currentPage.slug === slug && (slug !== "mitra" || index === 0)) ||
    (["pretest", "post-test"].includes(currentPage.slug) &&
      slug === "evaluasi-l1");
  const navLinks = (items, side = false) =>
    items
      .map(
        ([slug, label, ic], j) =>
          `<a class="${side ? "side-link" : ""} ${isActive(slug, j) ? "active" : ""}" href="#/${slug}" ${isActive(slug, j) ? 'aria-current="page"' : ""}>${side ? i(ic) : ""}<span>${label}</span></a>`,
      )
      .join("");
  const profileButton = (role) =>
    b(
      `<span class="avatar">${role === "admin" ? "BI" : i("user-round")}</span>`,
      "profile-menu",
      "icon-btn avatar-btn",
      'aria-label="Menu profil dan demo"',
    );
  const endorsement = () =>
    `<div class="mobile-endorsement"><img src="assets/bank-indonesia-logo.png" alt="Bank Indonesia" width="2048" height="362"></div>`;
  const logo = () =>
    `<a href="#/pengenalan" aria-label="LENTERA, halaman pengenalan"><img class="brand-logo" src="assets/lentera-logo.png" alt="LENTERA" width="2048" height="684"></a>`;
  function publicHeader() {
    return `<header class="top-header">${logo()}<nav class="main-nav" aria-label="Navigasi utama">${a("pengenalan", "Tentang LENTERA", "", "")}${a("kegiatan", "Alur belajar", "", "")}${a("panduan", "Panduan", "", "")}</nav><div class="header-right"><img class="bi-logo" src="assets/bank-indonesia-logo.png" alt="Bank Indonesia" width="2048" height="362">${b(i("menu"), "mobile-menu", "icon-btn mobile-menu", 'aria-label="Buka menu"')}</div></header>${endorsement()}`;
  }
  function participantHeader() {
    return `<header class="top-header">${logo()}<nav class="main-nav" aria-label="Navigasi peserta">${navLinks(participantNav)}</nav><div class="header-right"><img class="bi-logo" src="assets/bank-indonesia-logo.png" alt="Bank Indonesia" width="2048" height="362">${profileButton("participant")}${b(i("menu"), "mobile-menu", "icon-btn mobile-menu", 'aria-label="Buka menu"')}</div></header>${endorsement()}`;
  }
  function sidebar(role) {
    const partner = role === "partner";
    return `<aside class="sidebar">${logo()}<nav aria-label="Navigasi ${partner ? "mitra" : "pengelola BI"}"><p class="nav-group-title">Ruang kerja</p>${partner ? navLinks(partnerNav, true) : navLinks(adminNav.slice(0, 4), true) + `<p class="nav-group-title">Evaluasi</p>` + navLinks(adminNav.slice(4), true)}</nav><div class="sidebar-bottom">${
      !partner
        ? navLinks(
            [
              ["panduan", "Panduan", "circle-help"],
              ["pengaturan", "Pengaturan", "settings-2"],
            ],
            true,
          )
        : ""
    }<div class="sidebar-footer">${i(partner ? "users-round" : "building-2")} ${partner ? "Ruang mitra" : "KPwBI Provinsi Banten"}</div></div></aside>`;
  }
  function workspaceHeader(role) {
    return `<header class="workspace-topbar"><a href="#/${role === "partner" ? "mitra" : "ringkasan"}" class="mobile-brand"><img src="assets/lentera-logo.png" alt="LENTERA" width="2048" height="684"></a><div class="breadcrumb">Ruang kerja <strong>/ ${e(currentPage.title)}</strong></div><div class="header-right"><img class="bi-logo" src="assets/bank-indonesia-logo.png" alt="Bank Indonesia" width="2048" height="362"><span class="role-name">${role === "partner" ? "Pemantau PT" : "Pengelola BI"}</span>${profileButton(role)}${b(i("menu"), "mobile-menu", "icon-btn mobile-menu", 'aria-label="Buka navigasi"')}</div></header>${endorsement()}`;
  }
  function bottomNav(role) {
    let nav =
      role === "participant"
        ? [
            participantNav[0],
            ["kegiatan", "Kegiatan", "calendar-days"],
            participantNav[3],
            participantNav[4],
          ]
        : role === "partner"
          ? partnerNav
          : [adminNav[0], adminNav[1], adminNav[4], adminNav[7]];
    return `<nav class="bottom-nav" aria-label="Navigasi ${role === "admin" ? "pengelola" : role === "partner" ? "mitra" : "peserta"} seluler">${nav.map(([slug, label, ic], j) => `<a href="#/${slug}" class="${isActive(slug, j) ? "active" : ""}" ${isActive(slug, j) ? 'aria-current="page"' : ""}>${i(ic)}<span>${label === "Evaluasi agregat" ? "Evaluasi" : label}</span></a>`).join("")}</nav>`;
  }
  function footer() {
    return `<footer class="page-footer"><span>LENTERA · Prototipe interaktif<br>Data demo tersimpan lokal, tanpa backend.</span>${b(i("layout-grid") + " Demo · 20 layar", "catalog", "demo-launch")}<span class="no-mobile">${currentPage.id.toUpperCase()} · Desain V2</span></footer>`;
  }
  function render(reset = false) {
    let page = pageForHash();
    if (!page) {
      currentPage = {
        slug: "404",
        title: "Halaman tidak ditemukan",
        id: "404",
      };
      app.innerHTML =
        publicHeader() +
        `<main id="main-content" class="container"><section class="panel">${L.empty("Halaman tidak ditemukan.", "Pilih salah satu dari 20 layar LENTERA melalui menu demo.", "search-x", a("pengenalan", "Kembali ke pengenalan", "btn"))}</section></main>`;
      icons();
      return;
    }
    currentPage = page;
    if (!["public", "shared"].includes(page.role)) state.role = page.role;
    const role = page.role === "public" ? "public" : state.role;
    const isPublic = role === "public";
    document.title = page.title + " — LENTERA";
    document.body.classList.toggle("large-text", !!state.settings.largeText);
    const body = L.views[page.id](state, ui);
    app.innerHTML = isPublic
      ? `<div class="app-shell public">${publicHeader()}<main id="main-content">${body}</main></div>`
      : `<div class="app-shell ${role}">${role === "participant" ? participantHeader() : sidebar(role) + workspaceHeader(role)}<main id="main-content" class="container">${body}${footer()}</main>${bottomNav(role)}</div>`;
    icons();
    save();
    if (reset) {
      window.scrollTo({ top: 0, left: 0, behavior: "instant" });
      requestAnimationFrame(() =>
        document.querySelector("h1")?.focus({ preventScroll: true }),
      );
    }
  }
  const go = (slug) => {
    closeDialog();
    if (location.hash === "#/" + slug) render(true);
    else location.hash = "/" + slug;
  };
  function modal(title, body) {
    if (!dialog.open) dialogOpener = document.activeElement;
    dialog.innerHTML = `<div class="dialog-header"><h2 id="dialog-title">${title}</h2>${b(i("x"), "close-dialog", "icon-btn", 'aria-label="Tutup dialog"')}</div><div class="dialog-body">${body}</div>`;
    icons();
    if (!dialog.open) dialog.showModal();
  }
  function closeDialog() {
    if (dialog.open) dialog.close();
  }
  dialog.addEventListener("close", () => {
    if (dialogOpener?.isConnected) dialogOpener.focus({ preventScroll: true });
  });
  dialog.addEventListener("click", (event) => {
    if (event.target === dialog) {
      const rect = dialog.getBoundingClientRect();
      if (
        event.clientX < rect.left ||
        event.clientX > rect.right ||
        event.clientY < rect.top ||
        event.clientY > rect.bottom
      )
        closeDialog();
    }
  });
  function catalog() {
    modal(
      "Jelajahi LENTERA",
      `<p class="muted">20 layar desktop dalam satu website responsif. Beranda peserta, evaluasi L1, ringkasan BI, dan ringkasan mitra mengikuti empat rancangan mobile.</p><div class="row wrap mt">${b("Peserta", "switch-role", "btn secondary", 'data-value="participant"')}${b("Pengelola BI", "switch-role", "btn secondary", 'data-value="admin"')}${b("Mitra PT", "switch-role", "btn secondary", 'data-value="partner"')}</div><div class="screen-catalog">${L.pages.map((p) => `<a href="#/${p.slug}" data-action="catalog-link"><span class="code">${p.id.toUpperCase()}</span>${p.title}${["s04", "s08", "s10", "s17"].includes(p.id) ? i("smartphone") : ""}</a>`).join("")}</div>${info("Pilihan peran adalah pratinjau demo. Tidak mengubah hak akses atau akun nyata.")}<label class="checkbox-row mt"><input type="checkbox" data-change="large-text" ${state.settings.largeText ? "checked" : ""}>Perbesar teks</label><div class="row between wrap mt"><span class="small quiet">${storageOK ? "Penyimpanan demo: browser ini" : "Penyimpanan sementara: tab ini"}</span>${b("Reset data demo", "reset-confirm", "btn danger")}</div>`,
    );
  }
  function menu() {
    const role = currentPage.role === "public" ? "public" : state.role;
    const nav =
      role === "admin"
        ? [
            ...adminNav,
            ["panduan", "Panduan", "circle-help"],
            ["pengaturan", "Pengaturan", "settings-2"],
          ]
        : role === "partner"
          ? partnerNav
          : participantNav;
    modal(
      "Menu LENTERA",
      `<div class="mobile-menu-endorsement" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--line);"><span style="font-size:12px;color:var(--quiet);font-weight:500;">Didukung oleh</span><img src="assets/bank-indonesia-logo.png" alt="Bank Indonesia" style="height:20px;width:auto;max-height:22px;object-fit:contain;"></div><nav class="stack sm" aria-label="Menu lengkap">${navLinks(nav, true)}</nav><hr><div class="row wrap">${b("Jelajahi 20 layar", "catalog", "btn")}${a("profil", "Profil peserta", "btn secondary", "user-round")}${a("pengenalan", "Halaman pengenalan", "btn ghost", "")}</div>`,
    );
  }
  function clearErrors(form) {
    form.querySelectorAll("[aria-invalid]").forEach((el) => {
      el.removeAttribute("aria-invalid");
      el.removeAttribute("aria-describedby");
    });
    form.querySelectorAll(".field-error").forEach((el) => el.remove());
    const summary = form.querySelector(".error-summary");
    if (summary) summary.innerHTML = "";
  }
  function errors(form, items) {
    clearErrors(form);
    const summary = form.querySelector(".error-summary");
    summary.innerHTML = `<strong>Periksa kembali isian berikut.</strong><ul>${items.map(({ id, message }) => `<li><a href="#${id}" data-action="focus-field" data-target="${id}">${e(message)}</a></li>`).join("")}</ul>`;
    items.forEach(({ id, message }) => {
      const el = document.getElementById(id);
      if (el) {
        el.setAttribute("aria-invalid", "true");
        el.setAttribute("aria-describedby", id + "-error");
        const target = el.closest(".field") || el.closest("label") || el;
        const p = document.createElement("p");
        p.id = id + "-error";
        p.className = "field-error";
        p.textContent = message;
        target.appendChild(p);
      }
    });
    summary.focus({ preventScroll: true });
    window.scrollTo({
      top: Math.max(
        0,
        window.scrollY + summary.getBoundingClientRect().top - 100,
      ),
      behavior: "smooth",
    });
  }
  function required(form) {
    const items = [...form.querySelectorAll("[required]")]
      .filter((el) => !el.value.trim())
      .map((el) => ({
        id: el.id,
        message:
          (form.querySelector(`label[for="${el.id}"]`)?.textContent || "Isian")
            .replace("*", "")
            .trim() + " perlu diisi.",
      }));
    if (items.length) {
      errors(form, items);
      return false;
    }
    clearErrors(form);
    return true;
  }
  const collectActivity = () => {
    const f = document.getElementById("activity-form");
    if (!f) return;
    const d = new FormData(f);
    [
      "name",
      "office",
      "institution",
      "date",
      "description",
      "session",
      "speaker",
    ].forEach((k) => {
      if (d.has("activity-" + k))
        state.draft[k] = String(d.get("activity-" + k)).trim();
    });
    if (state.draft.step === 2)
      state.draft.material = d.has("activity-material");
    save();
  };
  const saveL1 = () => {
    const input = document.getElementById("l1-feedback");
    if (input) state.feedback = input.value;
    save();
  };
  const resetFilters = () => {
    ui.filters = {
      year: "2026",
      date: "7 April 2026",
      institution: "Semua institusi",
    };
    render();
  };
  function download(blob, name) {
    const url = URL.createObjectURL(blob);
    const el = document.createElement("a");
    el.href = url;
    el.download = name;
    document.body.append(el);
    el.click();
    el.remove();
    setTimeout(() => URL.revokeObjectURL(url), 15000);
  }
  function reportRows(includeL1, includeAspects) {
    const rows = [
      ["LENTERA — Ringkasan evaluasi L1"],
      ["Jenis data", "Contoh data agregat"],
      ["Tanggal kegiatan", "7 April 2026"],
      ["KPwBI", "Provinsi Banten"],
      ["Institusi", "Semua institusi"],
      [],
    ];
    if (includeL1)
      rows.push(["Rata-rata L1 (skala 1–6)", 5.3], ["Respons valid", 141], []);
    if (includeAspects)
      rows.push(
        ["Aspek", "Rata-rata / 6", "n"],
        ...L.aspects.map((x) => [
          x.short,
          Number(x.value.replace(",", ".")),
          141,
        ]),
        [],
      );
    rows.push(
      [
        "Catatan",
        "141 respons bertanggal valid; satu baris ringkasan dikecualikan.",
      ],
      ["Batasan", "Tidak mewakili seluruh tahun. Data L2 belum tersedia."],
    );
    return rows;
  }
  async function exportXlsx(rows) {
    const zip = new JSZip(),
      xml = (s) =>
        String(s).replace(
          /[<>&"']/g,
          (ch) =>
            ({
              "<": "&lt;",
              ">": "&gt;",
              "&": "&amp;",
              '"': "&quot;",
              "'": "&apos;",
            })[ch],
        );
    zip.file(
      "[Content_Types].xml",
      '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
    );
    zip.file(
      "_rels/.rels",
      '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
    );
    zip.file(
      "xl/workbook.xml",
      '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Evaluasi L1" sheetId="1" r:id="rId1"/></sheets></workbook>',
    );
    zip.file(
      "xl/_rels/workbook.xml.rels",
      '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
    );
    zip.file(
      "xl/styles.xml",
      '<?xml version="1.0"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="12"/><color rgb="FF245D83"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="3"><xf fontId="0" fillId="0" borderId="0" xfId="0"/><xf fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="2" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>',
    );
    const sheetRows = rows
      .map(
        (row, n) =>
          `<row r="${n + 1}">${row
            .map((v, j) => {
              const cell = String.fromCharCode(65 + j) + (n + 1);
              return typeof v === "number"
                ? `<c r="${cell}" s="${Number.isInteger(v) ? 0 : 2}"><v>${v}</v></c>`
                : `<c r="${cell}" t="inlineStr" s="${n === 0 || v === "Aspek" ? 1 : 0}"><is><t xml:space="preserve">${xml(v)}</t></is></c>`;
            })
            .join("")}</row>`,
      )
      .join("");
    zip.file(
      "xl/worksheets/sheet1.xml",
      `<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols><col min="1" max="1" width="42" customWidth="1"/><col min="2" max="2" width="80" customWidth="1"/><col min="3" max="3" width="12" customWidth="1"/></cols><sheetData>${sheetRows}</sheetData></worksheet>`,
    );
    return new Blob(
      [await zip.generateAsync({ type: "uint8array", compression: "DEFLATE" })],
      {
        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
      },
    );
  }
  async function exportPdf(rows) {
    const { PDFDocument, rgb } = PDFLib;
    const doc = await PDFDocument.create();
    doc.registerFontkit(window.fontkit);
    const page = doc.addPage([595.28, 841.89]),
      regular = await doc.embedFont(window.LENTERA_PDF_FONTS.body, {
        subset: true,
      }),
      bold = await doc.embedFont(window.LENTERA_PDF_FONTS.heading, {
        subset: true,
      }),
      blue = rgb(0.14, 0.36, 0.51),
      ink = rgb(0.06, 0.09, 0.16),
      quiet = rgb(0.29, 0.33, 0.41);
    doc.setTitle("LENTERA — Laporan evaluasi L1");
    doc.setSubject("Contoh data agregat 7 April 2026");
    doc.setAuthor("LENTERA — Prototipe");
    page.drawRectangle({
      x: 0,
      y: 740,
      width: 595.28,
      height: 102,
      color: ink,
    });
    page.drawText("LENTERA", {
      x: 44,
      y: 796,
      size: 15,
      font: bold,
      color: rgb(1, 1, 1),
    });
    page.drawText("Laporan evaluasi pembelajaran", {
      x: 44,
      y: 764,
      size: 23,
      font: bold,
      color: rgb(1, 1, 1),
    });
    let y = 704;
    page.drawText("CONTOH DATA AGREGAT  /  7 APRIL 2026", {
      x: 44,
      y,
      size: 10,
      font: bold,
      color: blue,
    });
    y -= 34;
    for (const row of rows.slice(1)) {
      if (!row.length) {
        y -= 13;
        continue;
      }
      const line = row.map((v) =>
        typeof v === "number"
          ? v.toLocaleString("id-ID", {
              minimumFractionDigits: Number.isInteger(v) ? 0 : 2,
            })
          : String(v),
      );
      const isAspect = line[0] === "Aspek";
      if (isAspect) {
        page.drawRectangle({
          x: 40,
          y: y - 8,
          width: 514,
          height: 28,
          color: rgb(0.92, 0.95, 0.97),
        });
      }
      const isLong = line[1] && regular.widthOfTextAtSize(line[1], 10) > 248;
      page.drawText(line[0].replace(/—/g, "-").replace(/–/g, "-"), {
        x: 44,
        y,
        size: 10,
        font: isAspect ? bold : regular,
        color: ink,
      });
      if (line[1]) {
        page.drawText(line[1].replace(/—/g, "-").replace(/–/g, "-"), {
          x: isLong ? 44 : 292,
          y: isLong ? y - 17 : y,
          size: 10,
          font: regular,
          color: quiet,
        });
      }
      if (line[2])
        page.drawText(line[2], {
          x: 500,
          y,
          size: 10,
          font: regular,
          color: quiet,
        });
      page.drawLine({
        start: { x: 44, y: y - (isLong ? 26 : 10) },
        end: { x: 550, y: y - (isLong ? 26 : 10) },
        thickness: 0.4,
        color: rgb(0.88, 0.91, 0.94),
      });
      y -= isLong ? 48 : 32;
    }
    page.drawText("Prototipe LENTERA | Tanpa identitas peserta | 1 / 1", {
      x: 44,
      y: 40,
      size: 9,
      font: regular,
      color: quiet,
    });
    return new Blob([await doc.save()], { type: "application/pdf" });
  }
  L.exportXlsx = exportXlsx;
  L.exportPdf = exportPdf;
  L.reportRows = reportRows;
  document.addEventListener("click", (event) => {
    const target = event.target.closest("[data-action]");
    if (!target) {
      if (dialog.open && event.target.closest('a[href^="#/"]')) closeDialog();
      return;
    }
    const action = target.dataset.action;
    if (action === "catalog-link") {
      closeDialog();
      return;
    }
    if (target.disabled) return;
    event.preventDefault();
    const actions = {
      catalog: catalog,
      "mobile-menu": menu,
      "profile-menu": () =>
        modal(
          "Ruang Anda",
          `<div class="row mb"><span class="avatar">${i("user-round")}</span><div><h3>${e(state.profile.name || "Pengguna demo")}</h3><p class="small muted">${state.role === "admin" ? "Pengelola BI" : state.role === "partner" ? "Mitra perguruan tinggi" : "Peserta"}</p></div></div><div class="stack sm">${a("profil", "Profil peserta", "btn secondary", "user-round")}${b("Ganti peran / jelajahi layar", "catalog", "btn")}${a("pengenalan", "Kembali ke pengenalan", "btn ghost", "arrow-left")}</div>`,
        ),
      "close-dialog": closeDialog,
      "login-role": () => {
        ui.loginRole = target.dataset.value;
        render();
      },
      "enter-demo": () => {
        state.role = ui.loginRole;
        save();
        go(
          ui.loginRole === "admin"
            ? "ringkasan"
            : ui.loginRole === "partner"
              ? "mitra"
              : "profil",
        );
      },
      "switch-role": () => {
        state.role = target.dataset.value;
        save();
        go(
          state.role === "admin"
            ? "ringkasan"
            : state.role === "partner"
              ? "mitra"
              : "beranda",
        );
      },
      "auth-help": () =>
        modal(
          "Pemulihan kata sandi",
          `<p class="muted">Pada versi produksi, tautan pemulihan akan dikirim ke alamat email akun terdaftar.</p><div class="mt">${info("Demo ini belum terhubung ke layanan akun. Tidak perlu memasukkan atau membagikan kata sandi.")}</div><div class="dialog-actions">${b("Mengerti", "close-dialog", "btn")}</div>`,
        ),
      "activation-help": () =>
        modal(
          "Aktivasi melalui undangan",
          `<ol><li>Buka tautan undangan dari pengelola kegiatan.</li><li>Verifikasi email dan buat kata sandi pada layanan resmi.</li><li>Lengkapi profil, lalu buka kegiatan.</li></ol>${info("Pengiriman undangan dan verifikasi email belum dihubungkan pada demo.")}<div class="dialog-actions">${b("Coba demo peserta", "switch-role", "btn", 'data-value="participant"')}</div>`,
        ),
      tab: () => {
        ({
          material: () => (ui.materialTab = target.dataset.value),
          instrument: () => (ui.instrumentTab = target.dataset.value),
          people: () => (ui.peopleTab = target.dataset.value),
        })[target.dataset.group]?.();
        render();
      },
      "quiz-jump": () => {
        ui.question = Number(target.dataset.index);
        render();
      },
      "quiz-prev": () => {
        ui.question = Math.max(0, ui.question - 1);
        render();
      },
      "start-post": () => {
        ui.postMode = "quiz";
        ui.question = 0;
        render(true);
      },
      "save-l1": () => {
        saveL1();
        toast(savedMessage("Draf evaluasi."));
      },
      "activity-prev": () => {
        collectActivity();
        state.draft.step = Math.max(0, state.draft.step - 1);
        save();
        render(true);
      },
      "activity-save": () => {
        collectActivity();
        toast(savedMessage("Draf kegiatan."));
      },
      "instrument-preview": () => {
        const n = Number(target.dataset.index),
          l1 = ui.instrumentTab === "l1";
        modal(
          "Pratinjau pertanyaan",
          `<h3>${e(l1 ? L.aspects[n].question : L.questions[n][0])}</h3>${l1 ? `<div class="example-ratings">${[1, 2, 3, 4, 5, 6].map((n) => `<span>${n}</span>`).join("")}</div><p class="small muted">Enam pilihan nilai, tanpa pilihan otomatis.</p>` : `<ol type="A">${L.questions[n][1].map((t) => `<li>${e(t)}</li>`).join("")}</ol>${info("Soal contoh untuk demonstrasi, bukan bank soal resmi.")}`}`,
        );
      },
      "reset-filters": resetFilters,
      feedback: () =>
        modal(
          "Masukan peserta",
          `${info("Masukan dari berkas sumber tidak disertakan dalam prototipe ini.")}<h3 class="mt">Masukan yang Anda coba di demo</h3><p class="muted mt">${state.feedback ? e(state.feedback) : "Belum ada masukan lokal."}</p><p class="field-hint mt">Terpisah dari ringkasan 141 respons pada dashboard.</p>`,
        ),
      "manage-l2": () => {
        ui.instrumentTab = "post";
        go("instrumen");
      },
      "open-import": () => {
        ui.peopleTab = "import";
        render();
      },
      "institution-detail": () => {
        const n = Number(target.dataset.index);
        modal(
          "Detail institusi",
          `<h3>${L.institutions[n]}</h3><dl class="key-values"><div><dt>Alias</dt><dd>${["UNTIRTA", "UIN SMH Banten", "UIN Jakarta"][n]}</dd></div><div><dt>KPwBI</dt><dd>Provinsi Banten</dd></div><div><dt>Agregat institusi</dt><dd>Belum tersedia</dd></div></dl>`,
        );
      },
      mapping: () =>
        modal(
          "Pemetaan data contoh",
          `<p class="muted">141 baris respons bertanggal 7 April 2026 digunakan untuk ringkasan. Satu baris ringkasan dikecualikan.</p><div class="table-wrap mt"><table><thead><tr><th>Kolom sumber</th><th>Tujuan</th></tr></thead><tbody><tr><td>Timestamp</td><td>Tanggal respons</td></tr><tr><td>Institusi</td><td>Master institusi</td></tr><tr><td>Lima aspek L1</td><td>Nilai skala 1–6</td></tr></tbody></table></div><div class="mt">${info("Nama, nomor identitas, dan jawaban individual tidak disalin ke website ini.")}</div>`,
        ),
      guide: () => {
        ui.guide = target.dataset.value;
        render();
      },
      "focus-field": () => {
        const el = document.getElementById(target.dataset.target);
        el?.focus({ preventScroll: true });
        if (el)
          window.scrollTo({
            top: window.scrollY + el.getBoundingClientRect().top - 110,
            behavior: "smooth",
          });
      },
      "reset-confirm": () =>
        modal(
          "Reset data demo?",
          `<p class="muted">Profil contoh, jawaban, kegiatan, narasumber tambahan, dan riwayat ekspor lokal akan dihapus dari browser ini. Berkas unduhan tidak terpengaruh.</p><div class="dialog-actions">${b("Batal", "close-dialog", "btn secondary")}${b("Reset data demo", "reset-demo", "btn danger")}</div>`,
        ),
      "reset-demo": () => {
        const role = state.role;
        state = L.defaults();
        state.role = role;
        save();
        closeDialog();
        render();
        toast("Data demo dikembalikan ke kondisi awal.");
      },
    };
    actions[action]?.();
  });
  document.addEventListener("change", async (event) => {
    const el = event.target,
      action = el.dataset.change;
    if (el.id.startsWith("filter-")) {
      if (el.id === "filter-date" && state.role === "partner")
        ui.partnerDate = el.value;
      else ui.filters[el.id.replace("filter-", "")] = el.value;
      render();
      return;
    }
    if (action === "login-role") {
      ui.loginRole = el.value;
      render();
    }
    if (action === "quiz-answer") {
      state[el.dataset.kind + "Answers"][ui.question] = Number(el.value);
      save();
      document
        .querySelectorAll(".question-grid button")
        [ui.question]?.classList.add("answered");
      const status = document.getElementById("quiz-draft-status");
      if (status) status.textContent = savedMessage("Draf jawaban.");
    }
    if (action === "l1-rating") {
      state.l1[Number(el.dataset.index)] = Number(el.value);
      saveL1();
      document.getElementById("l1-count").textContent =
        state.l1.filter(Boolean).length;
    }
    if (action === "large-text") {
      state.settings.largeText = el.checked;
      save();
      document.body.classList.toggle("large-text", el.checked);
    }
    if (action === "pdf-page" || action === "pdf-zoom") {
      if (action === "pdf-page") {
        ui.pdfPage = Math.max(1, Math.min(11, parseInt(el.value, 10) || 1));
        el.value = ui.pdfPage;
      } else ui.pdfZoom = parseInt(el.value, 10);
      document.getElementById("pdf-viewer").src =
        "assets/materi-kebanksentralan.pdf#page=" +
        ui.pdfPage +
        "&zoom=" +
        ui.pdfZoom +
        "&toolbar=0";
    }
    if (action === "import-file") {
      const file = el.files?.[0];
      if (!file) return;
      const out = document.getElementById("import-preview");
      if (
        file.size > 2 * 1024 * 1024 ||
        !file.name.toLowerCase().endsWith(".csv")
      ) {
        out.innerHTML = info("Pilih berkas .csv maksimal 2 MB.", "error");
        icons();
        return;
      }
      try {
        const text = await file.text(),
          rows = parseCSV(text);
        if (rows.length < 1) throw new Error("Berkas kosong");
        ui.importPreview = `<div class="notice success">${i("circle-check")}<div><strong>${e(file.name)}</strong><br>${Math.max(0, rows.length - 1)} baris data · ${rows[0].length} kolom</div></div><h3 class="mt">Kolom terdeteksi</h3><div class="row wrap mt">${rows[0].map((t) => pill(e(t), "neutral")).join("")}</div><p class="field-hint mt">Pratinjau saja. Data tidak diimpor, dikirim, atau disimpan.</p>`;
        out.innerHTML = ui.importPreview;
        icons();
      } catch {
        out.innerHTML = info(
          "Berkas tidak dapat dibaca. Periksa format CSV dan coba lagi.",
          "error",
        );
        icons();
      }
    }
  });
  function parseCSV(text) {
    const delimiter =
      (text.split(/\r?\n/)[0].match(/;/g) || []).length >
      (text.split(/\r?\n/)[0].match(/,/g) || []).length
        ? ";"
        : ",";
    const rows = [];
    let row = [],
      value = "",
      quoted = false;
    for (let n = 0; n < text.length; n++) {
      const c = text[n];
      if (c === '"') {
        if (quoted && text[n + 1] === '"') {
          value += '"';
          n++;
        } else quoted = !quoted;
      } else if (c === delimiter && !quoted) {
        row.push(value);
        value = "";
      } else if ((c === "\n" || c === "\r") && !quoted) {
        if (c === "\r" && text[n + 1] === "\n") n++;
        row.push(value);
        if (row.some((v) => v.trim())) rows.push(row);
        row = [];
        value = "";
      } else value += c;
    }
    row.push(value);
    if (row.some((v) => v.trim())) rows.push(row);
    if (quoted) throw new Error("Unclosed CSV quote");
    return rows;
  }
  L.parseCSV = parseCSV;
  document.addEventListener("input", (event) => {
    const el = event.target;
    if (el.id === "l1-feedback") {
      state.feedback = el.value;
      save();
    }
    if (el.dataset.input === "guide-search") {
      ui.guideSearch = el.value;
      const query = el.value.toLowerCase().trim();
      document.querySelectorAll("[data-guide-title]").forEach((b) => {
        b.hidden = query && !b.dataset.guideTitle.includes(query);
      });
    }
  });
  document.addEventListener("keydown", (event) => {
    const tab = event.target.closest("[role=tab]");
    if (tab && ["ArrowLeft", "ArrowRight", "Home", "End"].includes(event.key)) {
      event.preventDefault();
      const tabs = [...tab.parentElement.querySelectorAll("[role=tab]")],
        n = tabs.indexOf(tab);
      let next =
        event.key === "Home"
          ? 0
          : event.key === "End"
            ? tabs.length - 1
            : (n + (event.key === "ArrowRight" ? 1 : -1) + tabs.length) %
              tabs.length;
      const value = tabs[next].dataset.value,
        group = tab.dataset.group;
      tabs[next].click();
      document
        .querySelector(`[data-group="${group}"][data-value="${value}"]`)
        ?.focus();
    }
  });
  document.addEventListener("submit", async (event) => {
    const form = event.target;
    if (
      ![
        "profile-form",
        "quiz-form",
        "l1-form",
        "activity-form",
        "speaker-form",
        "instrument-form",
        "report-form",
        "access-form",
      ].includes(form.id)
    )
      return;
    event.preventDefault();
    if (form.id === "profile-form") {
      if (!required(form)) return;
      const data = new FormData(form);
      for (const k of [
        "name",
        "institution",
        "category",
        "idType",
        "identity",
        "department",
      ])
        state.profile[k] = String(data.get("profile-" + k) || "").trim();
      save();
      go("beranda");
      toast(savedMessage("Profil demo."));
    }
    if (form.id === "quiz-form") {
      const kind = form.dataset.kind,
        answers = state[kind + "Answers"];
      if (!Number.isInteger(answers[ui.question])) {
        errors(form, [
          {
            id: "answer-0",
            message: "Pilih satu jawaban sebelum melanjutkan.",
          },
        ]);
        return;
      }
      if (ui.question < 4) {
        ui.question++;
        render(true);
      } else {
        const missing = L.questions.findIndex(
          (_, j) => !Number.isInteger(answers[j]),
        );
        if (missing >= 0) {
          ui.question = missing;
          render(true);
          errors(document.getElementById("quiz-form"), [
            {
              id: "answer-0",
              message: "Lengkapi pertanyaan ini sebelum mengirim.",
            },
          ]);
          return;
        }
        state[kind + "Completed"] = true;
        save();
        if (kind === "post") {
          ui.postMode = "result";
          render(true);
        } else go("materi");
        toast(savedMessage("Jawaban demo."));
      }
    }
    if (form.id === "l1-form") {
      saveL1();
      const missing = L.aspects.map((_, j) => j).filter((j) => !state.l1[j]);
      if (missing.length) {
        errors(
          form,
          missing.map((j) => ({
            id: "l1-question-" + j,
            message: "Pertanyaan " + (j + 1) + " perlu diisi.",
          })),
        );
        return;
      }
      state.l1Submitted = true;
      save();
      render();
      modal(
        "Evaluasi demo tersimpan",
        `<div class="success-mark">${i("check")}</div><p class="muted">Terima kasih. Lima penilaian dan masukan contoh Anda tersimpan di browser ini.</p><p class="field-hint mt">Jawaban ini tidak digabungkan ke snapshot 141 respons pada dashboard.</p><div class="dialog-actions">${a("kegiatan", "Kembali ke sesi", "btn secondary", "")}${a("post-test", "Lanjutkan post-test", "btn")}</div>`,
      );
    }
    if (form.id === "activity-form") {
      if (!required(form)) return;
      collectActivity();
      if (state.draft.step < 3) {
        state.draft.step++;
        save();
        render(true);
      } else {
        state.activities.push({ ...state.draft });
        state.draft = L.defaults().draft;
        save();
        render(true);
        toast(savedMessage("Kegiatan demo diterbitkan."));
      }
    }
    if (form.id === "speaker-form") {
      if (!required(form)) return;
      const data = new FormData(form);
      state.speakers.push({
        name: String(data.get("speaker-name")).trim(),
        institution: String(data.get("speaker-institution")).trim(),
        subject: String(data.get("speaker-subject")).trim(),
      });
      save();
      render();
      toast(savedMessage("Narasumber demo."));
    }
    if (form.id === "instrument-form") {
      const data = new FormData(form),
        activity = data.get("instrument-activity"),
        session = data.get("instrument-session");
      const missing = [];
      if (activity === "Pilih kegiatan")
        missing.push({
          id: "instrument-activity",
          message: "Pilih kegiatan untuk versi ini.",
        });
      if (session === "Pilih sesi")
        missing.push({
          id: "instrument-session",
          message: "Pilih sesi untuk versi ini.",
        });
      if (missing.length) {
        errors(form, missing);
        return;
      }
      state.instrumentVersions.push({
        type: { l1: "Evaluasi L1", pre: "Pretest", post: "Post-test L2" }[
          ui.instrumentTab
        ],
        activity,
        session,
      });
      save();
      render();
      toast(savedMessage("Versi instrumen demo."));
    }
    if (form.id === "access-form") {
      const data = new FormData(form);
      state.access = {
        office: data.get("access-office"),
        institution: data.get("access-institution"),
        aggregate: data.has("access-aggregate"),
      };
      save();
      toast(savedMessage("Konfigurasi contoh."));
    }
    if (form.id === "report-form") {
      if (!L.hasSnapshot(ui)) return;
      const data = new FormData(form),
        l1 = data.has("report-l1"),
        aspects = data.has("report-aspects");
      if (!l1 && !aspects) {
        errors(form, [
          {
            id: "report-l1",
            message: "Pilih setidaknya satu komponen laporan.",
          },
        ]);
        return;
      }
      const format = data.get("report-format"),
        submit = form.querySelector("[type=submit]");
      submit.disabled = true;
      submit.textContent = "Menyiapkan laporan…";
      try {
        const rows = reportRows(l1, aspects),
          blob =
            format === "pdf" ? await exportPdf(rows) : await exportXlsx(rows);
        download(
          blob,
          "LENTERA-Laporan-L1-2026-04-07." +
            (format === "pdf" ? "pdf" : "xlsx"),
        );
        state.reports.push({
          label: "Evaluasi L1 · " + (format === "pdf" ? "PDF" : "Excel"),
          date: new Date().toLocaleDateString("id-ID"),
          status: "Unduhan disiapkan",
        });
        save();
        render();
        toast("Berkas laporan telah disiapkan untuk diunduh.");
      } catch (err) {
        submit.disabled = false;
        submit.innerHTML = i("download") + " Siapkan laporan";
        modal(
          "Laporan belum berhasil dibuat",
          `<p class="muted">Muat ulang halaman dan coba kembali. Pastikan semua berkas aset tersedia ketika menjalankan website secara lokal.</p>`,
        );
      }
    }
  });
  window.addEventListener("hashchange", () => {
    closeDialog();
    ui.question = 0;
    ui.postMode = "result";
    render(true);
  });
  L.demo = { getState: () => state, getUI: () => ui, render, go };
  render();
})();
