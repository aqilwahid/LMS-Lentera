/* Local synthetic demo only; each run creates an instrument, session and L1 response. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require(process.env.LENTERA_PLAYWRIGHT_PATH || 'playwright');

(async () => {
  const base = process.env.LENTERA_BASE_URL || 'http://127.0.0.1:8000';
  const browser = await chromium.launch({ channel: 'chromium' });
  try {
    const stamp = Date.now();
    const name = 'Instrumen QA ' + stamp;
    const questions = Array.from({ length: 5 }, (_, i) => 'Butir sintetis ' + (i + 1) + ' QA ' + stamp);
    const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    const errors = [];
    page.on('pageerror', e => errors.push(e.message));
    await page.goto(base + '/masuk');
    assert(await page.getByText('Lingkungan contoh').count(), 'Refusing outside demo environment');
    await page.getByLabel('Email', { exact: true }).fill('pengelola@example.test');
    await page.getByLabel('Kata sandi', { exact: true }).fill('Lentera-Demo-2026!');
    await page.getByRole('button', { name: 'Masuk', exact: true }).click();
    await page.waitForURL('**/beranda');
    await page.getByRole('link', { name: 'Instrumen L1', exact: true }).click();
    await page.getByRole('link', { name: 'Buat instrumen L1', exact: true }).click();
    const csrf = await page.locator('form.panel [name=_token]').inputValue();
    const nonce = await page.locator('[name=creation_nonce]').inputValue();
    const payload = { _token: csrf, creation_nonce: nonce, name };
    questions.forEach((text, i) => { payload['questions[' + i + ']'] = text; });
    const badCsrf = await page.request.post(base + '/pengelola/instrumen', {
      headers: { Accept: 'application/json', 'Sec-Fetch-Site': 'cross-site' },
      form: { ...payload, _token: 'invalid' }, maxRedirects: 0,
    });
    assert.equal(badCsrf.status(), 419);
    await page.getByLabel('Nama instrumen', { exact: true }).fill(name);
    for (let i = 0; i < 5; i++) await page.getByLabel('Butir ' + (i + 1), { exact: true }).fill(questions[i]);
    await page.getByRole('button', { name: 'Simpan draf', exact: true }).click();
    await page.getByRole('heading', { name: 'Ubah draf', exact: true }).waitFor();
    const v1URL = page.url();
    const v1ID = v1URL.split('/').pop();
    const retryCreate = await page.request.post(base + '/pengelola/instrumen', { form: payload, maxRedirects: 0 });
    assert.equal(retryCreate.status(), 302);
    assert.equal(retryCreate.headers().location, v1URL);
    await page.goto(base + '/pengelola/kegiatan');
    await page.getByRole('link', { name: /^Kelola kegiatan:/ }).first().click();
    await page.getByRole('link', { name: 'Tambah sesi', exact: true }).click();
    const createSessionURL = page.url();
    assert.equal(await page.locator('select[name=l1_instrument_id] option[value="' + v1ID + '"]').count(), 0);
    await page.goto(v1URL);
    await page.getByRole('button', { name: 'Terbitkan versi 1', exact: true }).click();
    await page.getByRole('heading', { name: 'Perlu perubahan?', exact: true }).waitFor();
    await page.goto(createSessionURL);
    await page.getByLabel('Nama sesi', { exact: true }).fill('Sesi instrumen QA ' + stamp);
    await page.getByLabel('Nama narasumber', { exact: true }).fill('Narasumber instrumen QA');
    await page.getByLabel('Versi instrumen L1', { exact: true }).selectOption(v1ID);
    const date = d => new Date(Date.now() + d * 86400000 + 7 * 3600000).toISOString().slice(0, 16);
    for (const [field, value] of Object.entries({ starts_at: date(0), join_opens_at: date(-1), join_closes_at: date(1), l1_opens_at: date(-1), l1_closes_at: date(1) })) {
      await page.locator('[name=' + field + ']').fill(value);
    }
    await page.getByRole('button', { name: 'Simpan sesi', exact: true }).click();
    await page.getByRole('button', { name: 'Aktifkan akses kelas', exact: true }).click();
    await page.getByRole('link', { name: 'Lihat QR dan tautan', exact: true }).waitFor();
    const sessionEditURL = page.url();
    await page.getByRole('link', { name: 'Lihat QR dan tautan', exact: true }).click();
    const joinURL = await page.getByLabel('Tautan peserta').inputValue();
    const guestContext = await browser.newContext();
    const guest = await guestContext.newPage();
    guest.on('pageerror', e => errors.push(e.message));
    await guest.goto(joinURL);
    for (const [field, value] of Object.entries({ full_name: 'Peserta instrumen QA ' + stamp, institution_name: 'Institusi QA', identity_number: '00123', department: 'Ekonomi', faculty: 'Ekonomi', phone: '08123456789', participant_category: 'Mahasiswa', email: 'instrument-qa@example.test' })) {
      await guest.locator('[name=' + field + ']').fill(value);
    }
    await guest.getByRole('button', { name: 'Gabung kelas', exact: true }).click();
    await guest.getByRole('link', { name: 'Buka evaluasi L1', exact: true }).click();
    assert.equal(await guest.locator('[name=instrument_id]').inputValue(), v1ID);
    assert.equal(await guest.locator('legend').first().innerText(), '1. ' + questions[0]);
    for (let i = 0; i < 5; i++) await guest.locator('#q' + i + '-5').check();
    await guest.getByRole('button', { name: 'Kirim evaluasi', exact: true }).click();
    await guest.getByRole('heading', { name: 'Evaluasi sudah dikirim.', exact: true }).waitFor();
    const confirmationURL = guest.url();
    await page.goto(v1URL);
    const versionForm = page.locator('form[action$="/versi"]');
    const versionData = { _token: await versionForm.locator('[name=_token]').inputValue(), creation_nonce: await versionForm.locator('[name=creation_nonce]').inputValue() };
    await page.getByRole('button', { name: 'Buat draf versi baru', exact: true }).click();
    await page.getByRole('heading', { name: 'Ubah draf', exact: true }).waitFor();
    const v2URL = page.url();
    const v2ID = v2URL.split('/').pop();
    await page.getByLabel('Butir 1', { exact: true }).fill('Revisi butir satu QA ' + stamp);
    await page.getByRole('button', { name: 'Simpan perubahan draf', exact: true }).click();
    await page.getByText('Perubahan draf tersimpan.', { exact: true }).waitFor();
    const stale = await page.request.put(v2URL, { headers: { Accept: 'application/json' }, form: { ...payload, revision: '1' } });
    assert.equal(stale.status(), 422);
    await page.getByRole('button', { name: 'Terbitkan versi 2', exact: true }).click();
    await page.getByRole('heading', { name: 'Perlu perubahan?', exact: true }).waitFor();
    const versionRetry = await page.request.post(v1URL + '/versi', { form: versionData, maxRedirects: 0 });
    assert.equal(versionRetry.status(), 302);
    assert.equal(versionRetry.headers().location, v2URL);
    const outputs = path.join(__dirname, 'outputs', 'instruments');
    fs.mkdirSync(outputs, { recursive: true });
    for (const width of [375, 768, 1440]) {
      await page.setViewportSize({ width, height: 1000 });
      assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Overflow at ' + width);
      await page.screenshot({ path: path.join(outputs, 'published-' + width + '.png'), fullPage: true });
    }
    await page.goto(sessionEditURL);
    assert.equal(await page.locator('input[name=l1_instrument_id]').inputValue(), v1ID);
    await page.goto(createSessionURL);
    assert.equal(await page.locator('select[name=l1_instrument_id] option[value="' + v2ID + '"]').count(), 1);
    await guest.goto(confirmationURL);
    assert(await guest.getByText('Rata-rata penilaian Anda: 5,00 / 6.', { exact: true }).count());
    await page.goto(base + '/pengelola/riwayat');
    assert(await page.getByRole('heading', { name: 'Instrumen diterbitkan', exact: true }).count());
    assert.equal(errors.length, 0, errors.join('\n'));
    console.log(JSON.stringify({ passed: true, flow: 'draft → publish → session → guest L1 → V2 → historical V1 preserved', retryChecked: true, staleFormChecked: true, csrfChecked: true, widths: [375, 768, 1440], errors }));
    await guestContext.close();
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error); process.exit(1); });
