/* Local synthetic demo only. Run l2-browser.cjs first to provide a published L2 fixture. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require(process.env.LENTERA_PLAYWRIGHT_PATH || 'playwright');

(async () => {
  const browser = await chromium.launch({ channel: 'chromium' });
  try {
    const base = process.env.LENTERA_BASE_URL || 'http://127.0.0.1:8000';
    const stamp = Date.now();
    const sessionName = 'Analisis L2 QA ' + stamp;
    const privateName = 'PRIVATE_ANALYTICS_PARTICIPANT_' + stamp;
    const errors = [];
    const staff = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    staff.on('pageerror', e => errors.push(e.message));
    async function login(page, email) {
      await page.goto(base + '/masuk');
      assert(await page.getByText('Lingkungan contoh').count(), 'Refusing outside demo');
      await page.getByLabel('Email', { exact: true }).fill(email);
      await page.getByLabel('Kata sandi', { exact: true }).fill('Lentera-Demo-2026!');
      await page.getByRole('button', { name: 'Masuk', exact: true }).click();
      await page.waitForURL('**/beranda');
    }
    await login(staff, 'pengelola@example.test');
    await staff.getByRole('link', { name: 'Kelola kegiatan', exact: true }).click();
    await staff.getByRole('link', { name: /^Kelola kegiatan:/ }).first().click();
    await staff.getByRole('link', { name: 'Tambah sesi', exact: true }).click();
    await staff.getByLabel('Nama sesi', { exact: true }).fill(sessionName);
    await staff.getByLabel('Nama narasumber', { exact: true }).fill('Narasumber analisis QA');
    await staff.getByLabel('Versi instrumen L1', { exact: true }).selectOption({ index: 1 });
    const date = d => new Date(Date.now() + d * 86400000 + 7 * 3600000).toISOString().slice(0, 16);
    for (const [field, value] of Object.entries({ starts_at: date(0), join_opens_at: date(-1), join_closes_at: date(1), l1_opens_at: date(-1), l1_closes_at: date(1) })) await staff.locator('[name=' + field + ']').fill(value);
    await staff.getByRole('button', { name: 'Simpan sesi', exact: true }).click();
    await staff.getByRole('button', { name: 'Aktifkan akses kelas', exact: true }).click();
    await staff.getByRole('link', { name: 'Lihat QR dan tautan', exact: true }).waitFor();
    const sessionEdit = staff.url();
    const sessionID = sessionEdit.split('/').at(-2);
    await staff.getByRole('link', { name: 'Atur evaluasi L2', exact: true }).click();
    const settingsURL = staff.url();
    assert(await staff.locator('select[name=l2_instrument_id] option').count() > 1, 'Run tests/l2-browser.cjs first to create a published instrument');
    await staff.getByLabel('Versi instrumen L2', { exact: true }).selectOption({ index: 1 });
    for (const [field, value] of Object.entries({ pre_opens_at: date(-1), pre_closes_at: date(1), post_opens_at: date(-1), post_closes_at: date(1) })) await staff.locator('[name=' + field + ']').fill(value);
    await staff.getByLabel('Status akses L2', { exact: true }).selectOption('1');
    await staff.getByRole('button', { name: 'Simpan pengaturan L2', exact: true }).click();
    await staff.getByText('Pengaturan L2 tersimpan. Peserta tetap mengikuti jadwal yang ditetapkan.', { exact: true }).waitFor();
    await staff.goto(sessionEdit);
    await staff.getByRole('link', { name: 'Lihat QR dan tautan', exact: true }).click();
    const joinURL = await staff.getByLabel('Tautan peserta').inputValue();
    const guestContext = await browser.newContext();
    const guest = await guestContext.newPage();
    await guest.goto(joinURL);
    for (const [field, value] of Object.entries({ full_name: privateName, institution_name: 'Universitas Sultan Ageng Tirtayasa', identity_number: 'PRIVATE_NIM', department: 'Ekonomi', faculty: 'Ekonomi', phone: '08123456789', participant_category: 'Mahasiswa', email: 'private-analytics@example.test' })) await guest.locator('[name=' + field + ']').fill(value);
    await guest.getByRole('button', { name: 'Gabung kelas', exact: true }).click();
    for (const stage of ['pre', 'post']) {
      await guest.goto(joinURL + '/l2/' + stage);
      await guest.getByRole('button', { name: stage === 'pre' ? 'Mulai pretest' : 'Mulai post-test', exact: true }).click();
      const count = await guest.locator('fieldset.rating-question').count();
      for (let i = 0; i < count; i++) await guest.locator('#l2-' + i + '-0').check();
      await guest.getByRole('button', { name: 'Kirim final', exact: true }).click();
      await guest.getByRole('heading', { name: stage === 'pre' ? 'Pretest sudah dikirim.' : 'Post-test sudah dikirim.', exact: true }).waitFor();
    }
    await staff.getByRole('link', { name: 'Analisis L2', exact: true }).click();
    await staff.getByLabel('Tanggal awal', { exact: true }).fill(date(0).slice(0, 10));
    await staff.getByLabel('Tanggal akhir', { exact: true }).fill(date(0).slice(0, 10));
    await staff.getByLabel('Sesi', { exact: true }).selectOption(sessionID);
    await staff.getByRole('button', { name: 'Terapkan filter L2', exact: true }).click();
    await staff.getByRole('heading', { name: 'Hasil pada periode terpilih', exact: true }).waitFor();
    assert(await staff.getByText('Pasangan sah dengan post-test pada periode: 1.', { exact: true }).count());
    assert(!(await staff.content()).includes(privateName));
    const partnerContext = await browser.newContext();
    const partner = await partnerContext.newPage();
    partner.on('pageerror', e => errors.push(e.message));
    await login(partner, 'mitra@example.test');
    await partner.getByRole('link', { name: 'Analisis L2', exact: true }).click();
    assert(!(await partner.content()).includes(sessionName), 'Unreleased session metadata leaked');
    const forged = await partner.request.get(base + '/analisis/l2?institution_id=999999', { headers: { Accept: 'application/json' } });
    assert.equal(forged.status(), 422);
    await staff.goto(settingsURL);
    await staff.getByLabel('Buka skor peserta dan agregat mitra', { exact: true }).selectOption('1');
    await staff.getByRole('button', { name: 'Simpan pengaturan L2', exact: true }).click();
    await staff.getByText('Pengaturan L2 tersimpan. Peserta tetap mengikuti jadwal yang ditetapkan.', { exact: true }).waitFor();
    await partner.reload();
    await partner.getByLabel('Tanggal awal', { exact: true }).fill(date(0).slice(0, 10));
    await partner.getByLabel('Tanggal akhir', { exact: true }).fill(date(0).slice(0, 10));
    await partner.getByLabel('Sesi', { exact: true }).selectOption(sessionID);
    await partner.getByRole('button', { name: 'Terapkan filter L2', exact: true }).click();
    await partner.getByRole('heading', { name: 'Hasil pada periode terpilih', exact: true }).waitFor();
    assert(await partner.getByText('Pasangan sah dengan post-test pada periode: 1.', { exact: true }).count());
    assert(await partner.getByText('Selisih rata-rata post minus pre: 0,00 poin persentase.', { exact: true }).count());
    assert.equal(await partner.locator('select[name=institution_id]').count(), 0);
    for (const secret of [privateName, 'PRIVATE_NIM', 'private-analytics@example.test', 'instrument_snapshot', 'guest_access_hash', 'Kunci:']) assert(!(await partner.content()).includes(secret), 'Private data leaked: ' + secret);
    const outputs = path.join(__dirname, 'outputs', 'l2-analytics');
    fs.mkdirSync(outputs, { recursive: true });
    for (const width of [375, 768, 1440]) {
      await partner.setViewportSize({ width, height: 1000 });
      assert(await partner.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Overflow at ' + width);
      await partner.screenshot({ path: path.join(outputs, 'partner-' + width + '.png'), fullPage: true });
    }
    assert.equal(errors.length, 0, errors.join('\n'));
    console.log(JSON.stringify({ passed: true, flow: 'BI → final pair → filtered analysis → unreleased hidden from partner → release → scoped partner aggregate', privateDataExcluded: true, forgedFilterRejected: true, widths: [375, 768, 1440], errors }));
    await guestContext.close();
    await partnerContext.close();
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error); process.exit(1); });
