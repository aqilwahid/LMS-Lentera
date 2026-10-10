/* DOM/function tests only. Does not claim browser layout verification. */
const fs = require("fs"),
  path = require("path"),
  assert = require("assert/strict");
const { JSDOM, VirtualConsole } = require(
  process.env.LENTERA_JSDOM_PATH || "jsdom",
);
const root = path.resolve(__dirname, "..");
const staticRoot = fs.existsSync(path.join(root, "dist/index.html")) ? path.join(root, "dist") : root;
const html = fs.readFileSync(path.join(staticRoot, "index.html"), "utf8");
const timeout = setTimeout(() => {
  console.error("Timed out while checking export / DOM behavior.");
  process.exit(1);
}, 20000);
let downloads = [],
  failures = [];
const virtualConsole = new VirtualConsole();
virtualConsole.on("jsdomError", (err) => {
  if (!/navigation \(except hash changes\)/.test(err.message))
    failures.push(err.message);
});
const dom = new JSDOM(html, {
  url: "https://lentera.test/",
  runScripts: "outside-only",
  pretendToBeVisual: true,
  virtualConsole,
});
const w = dom.window,
  d = w.document;
w.scrollTo = () => {};
w.URL.createObjectURL = (blob) => {
  downloads.push(blob);
  return "blob:test-" + downloads.length;
};
w.URL.revokeObjectURL = () => {};
w.HTMLDialogElement.prototype.showModal = function () {
  this.open = true;
};
w.HTMLDialogElement.prototype.close = function () {
  this.open = false;
  this.dispatchEvent(new w.Event("close"));
};
w.TextEncoder = TextEncoder;
w.TextDecoder = TextDecoder;
w.setImmediate = (fn, ...args) => setTimeout(() => fn(...args), 0);
w.clearImmediate = clearTimeout;
for (const name of [
  "assets/lucide.min.js",
  "assets/pdf-lib.min.js",
  "assets/fontkit.min.js",
  "assets/pdf-fonts.js",
  "assets/jszip.min.js",
  "data.js",
  "pages-participant.js",
  "pages-admin.js",
  "app.js",
])
  w.eval(fs.readFileSync(path.join(staticRoot, name), "utf8"));
const tick = () => new Promise((r) => setTimeout(r, 25));
const route = async (slug) => {
  w.location.hash = "/" + slug;
  await tick();
};
const click = (selector) => {
  const el = d.querySelector(selector);
  assert(el, "Missing " + selector);
  el.click();
};
const fill = (id, val) => {
  const el = d.getElementById(id);
  assert(el, id);
  el.value = val;
};
const change = (selector) => {
  const el = d.querySelector(selector);
  el.dispatchEvent(new w.Event("change", { bubbles: true }));
};
const submit = (id) => {
  d.getElementById(id).dispatchEvent(
    new w.Event("submit", { bubbles: true, cancelable: true }),
  );
};
const blobBytes = (blob) =>
  new Promise((resolve, reject) => {
    const reader = new w.FileReader();
    reader.onload = () => resolve(Buffer.from(reader.result));
    reader.onerror = reject;
    reader.readAsArrayBuffer(blob);
  });
(async () => {
  let checked = 0;
  for (const page of w.L.pages) {
    await route(page.slug);
    assert(d.querySelector("h1"), page.id + " h1");
    assert(d.title.includes(page.title), page.id + " title");
    const ids = [...d.querySelectorAll("[id]")].map((el) => el.id);
    assert.equal(new Set(ids).size, ids.length, page.id + " duplicate IDs");
    for (const img of d.querySelectorAll("img")) {
      assert(img.hasAttribute("alt"), page.id + " missing alt");
      assert(
        fs.existsSync(path.join(staticRoot, img.getAttribute("src"))),
        page.id + " missing image",
      );
    }
    assert.equal(
      d.querySelectorAll("i[data-lucide]").length,
      0,
      page.id + " unresolved icons",
    );
    for (const a of d.querySelectorAll('a[href^="#/"]'))
      assert(
        w.L.pages.some((p) => "#/" + p.slug === a.getAttribute("href")),
        page.id + " invalid route " + a.href,
      );
    for (const input of d.querySelectorAll("input,select,textarea"))
      assert(
        input.closest("label") ||
          input.getAttribute("aria-label") ||
          d.querySelector(`label[for="${input.id}"]`),
        page.id + " unlabelled field " + input.id,
      );
    checked++;
  }
  await route("profil");
  submit("profile-form");
  assert(
    d.querySelector(".error-summary").textContent.includes("Nama lengkap"),
  );
  fill("profile-name", "Peserta Demo");
  fill("profile-identity", "CONTOH-001");
  fill("profile-department", "Manajemen");
  submit("profile-form");
  await tick();
  assert.equal(w.location.hash, "#/beranda");
  assert.equal(w.L.demo.getState().profile.name, "Peserta Demo");
  await route("evaluasi-l1");
  assert.equal(d.querySelectorAll(".rating-options").length, 5);
  assert.equal(d.querySelectorAll(".rating-option").length, 30);
  assert.equal(d.querySelectorAll(".rating-option input:checked").length, 0);
  submit("l1-form");
  assert.equal(d.querySelectorAll(".error-summary li").length, 5);
  for (let n = 0; n < 5; n++) {
    const el = d.querySelector(`#rating-${n}-6`);
    el.checked = true;
    change(`#rating-${n}-6`);
  }
  fill("l1-feedback", "Contoh masukan");
  submit("l1-form");
  assert(w.L.demo.getState().l1Submitted);
  assert(d.getElementById("app-dialog").open);
  click('[data-action="close-dialog"]');
  await route("pretest");
  submit("quiz-form");
  assert(
    d
      .querySelector(".error-summary")
      .textContent.includes("Pilih satu jawaban"),
  );
  for (let n = 0; n < 5; n++) {
    d.getElementById("answer-0").checked = true;
    change("#answer-0");
    submit("quiz-form");
    await tick();
  }
  assert.equal(w.location.hash, "#/materi");
  assert.equal(w.L.demo.getState().preAnswers.length, 5);
  fill("pdf-page", "99");
  change("#pdf-page");
  assert.equal(d.getElementById("pdf-page").value, "11");
  assert(d.getElementById("pdf-viewer").src.includes("page=11"));
  await route("post-test");
  click('[data-action="start-post"]');
  for (let n = 0; n < 5; n++) {
    d.getElementById("answer-0").checked = true;
    change("#answer-0");
    submit("quiz-form");
    await tick();
  }
  assert(w.L.demo.getState().postCompleted);
  assert(d.querySelector(".result-panel"));
  await route("ringkasan");
  fill("filter-year", "2025");
  change("#filter-year");
  assert(d.querySelector("h3").textContent.includes("Belum ada data"));
  click('[data-action="reset-filters"]');
  assert(d.querySelector(".metric").textContent.includes("5,30"));
  await route("buat-kegiatan");
  submit("activity-form");
  assert(d.querySelector(".error-summary li"));
  fill("activity-name", "Kegiatan Demo");
  fill("activity-date", "2026-10-10");
  submit("activity-form");
  assert.equal(w.L.demo.getState().draft.step, 1);
  submit("activity-form");
  assert.equal(w.L.demo.getState().draft.step, 2);
  submit("activity-form");
  assert.equal(w.L.demo.getState().draft.step, 3);
  submit("activity-form");
  assert.equal(w.L.demo.getState().activities.length, 1);
  await route("kelola-materi");
  fill("speaker-name", "Narasumber Contoh");
  fill("speaker-institution", "Institusi Contoh");
  fill("speaker-subject", "Materi Contoh");
  submit("speaker-form");
  assert.equal(w.L.demo.getState().speakers.length, 1);
  assert(d.body.textContent.includes("Narasumber Contoh"));
  await route("instrumen");
  submit("instrument-form");
  assert.equal(d.querySelectorAll(".error-summary li").length, 2);
  fill("instrument-activity", "Literasi Kebanksentralan");
  fill("instrument-session", "Peran dan Tugas Bank Indonesia");
  submit("instrument-form");
  assert.equal(w.L.demo.getState().instrumentVersions.length, 1);
  await route("peserta-mitra");
  click('[data-action="open-import"]');
  assert(d.getElementById("import-file"));
  assert.equal(w.L.parseCSV('a,b\n"x,y",z\n').length, 2);
  assert.equal(w.L.parseCSV('a;b\n"x;y";z')[1][0], "x;y");
  await route("mitra");
  assert(!d.querySelector(".sidebar").textContent.includes("Impor"));
  assert(!d.querySelector(".sidebar").textContent.includes("Pengaturan"));
  assert(!d.querySelector(".metric"));
  await route("panduan");
  click('[data-action="guide"][data-value="material"]');
  assert(
    d.querySelector(".guide-article h2").textContent.includes("Membaca materi"),
  );
  fill("guide-search", "evaluasi");
  d.getElementById("guide-search").dispatchEvent(
    new w.Event("input", { bubbles: true }),
  );
  assert(d.querySelector('[data-value="material"]').hidden);
  await route("pengaturan");
  submit("access-form");
  assert.equal(w.L.demo.getState().access.office, "KPwBI Provinsi Banten");
  console.log(
    "Validated 20 routes and form/navigation scenarios. Checking file exports.",
  );
  const outputs = path.join(root, "tests", "outputs");
  fs.mkdirSync(outputs, { recursive: true });
  const rows = w.L.reportRows(true, true);
  fs.writeFileSync(
    path.join(outputs, "report.xlsx"),
    await blobBytes(await w.L.exportXlsx(rows)),
  );
  fs.writeFileSync(
    path.join(outputs, "report.pdf"),
    await blobBytes(await w.L.exportPdf(rows)),
  );
  await route("laporan");
  d.querySelector('[name="report-l1"]').checked = false;
  d.querySelector('[name="report-aspects"]').checked = false;
  submit("report-form");
  assert(
    d.querySelector(".error-summary").textContent.includes("Pilih setidaknya"),
  );
  d.querySelector('[name="report-l1"]').checked = true;
  submit("report-form");
  await new Promise((r) => setTimeout(r, 100));
  assert(downloads.length === 1);
  assert.equal(w.L.demo.getState().reports.length, 1);
  click('[data-action="catalog"]');
  assert.equal(d.querySelectorAll(".screen-catalog a").length, 20);
  click('[data-action="reset-confirm"]');
  click('[data-action="reset-demo"]');
  assert.equal(w.L.demo.getState().profile.name, "");
  assert.equal(w.L.demo.getState().activities.length, 0);
  await route("does-not-exist");
  assert(d.body.textContent.includes("Halaman tidak ditemukan"));
  assert.equal(failures.length, 0, failures.join("\n"));
  console.log(
    JSON.stringify(
      {
        passed: true,
        routes: checked,
        scenarios: [
          "profile validation",
          "30 L1 radio options",
          "L1 submission",
          "five pretest questions",
          "PDF controls",
          "post-test confirmation",
          "filter empty states",
          "four-step activity",
          "speaker creation",
          "instrument versioning",
          "CSV parsing",
          "partner scope",
          "guide search",
          "demo settings",
          "PDF export",
          "Excel export",
          "report validation",
          "20-screen catalog",
          "reset",
          "404",
        ],
        browserLayoutVerified: false,
      },
      null,
      2,
    ),
  );
  clearTimeout(timeout);
  dom.window.close();
})().catch((err) => {
  console.error(err);
  clearTimeout(timeout);
  dom.window.close();
  process.exitCode = 1;
});
