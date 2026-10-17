/* Local synthetic demo only. Start server and enable admission before running. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawn } = require('node:child_process');
const { chromium } = require(process.env.LENTERA_PLAYWRIGHT_PATH || 'playwright');

(async () => {
  const base = process.env.LENTERA_BASE_URL || 'http://127.0.0.1:8000';
  assert(['127.0.0.1', 'localhost'].includes(new URL(base).hostname), 'Local demo only');
  const browser = await chromium.launch({ channel: 'chromium' });
  const holders = [];
  const errors = [];
  let diagnosticPage;
  const output = path.join(__dirname, 'outputs', 'admission');
  fs.mkdirSync(output, { recursive: true });
  const php = process.env.LENTERA_PHP || 'php';
  async function hold() {
    const child = spawn(php, [path.join(__dirname, 'admission-hold.php')], { cwd: path.join(__dirname, '..'), windowsHide: true });
    holders.push(child);
    await new Promise((resolve, reject) => {
      let log = '';
      const timeout = setTimeout(() => reject(new Error('Holder did not start: ' + log)), 18000);
      child.stdout.on('data', data => { log += data; if (log.includes('READY')) { clearTimeout(timeout); resolve(); } });
      child.stderr.on('data', data => { log += data; });
      child.on('error', reject);
      child.on('exit', code => { clearTimeout(timeout); if (!log.includes('READY')) reject(new Error('Holder exited ' + code + ': ' + log)); });
    });
    return async () => {
      if (child.exitCode !== null) return;
      await new Promise(resolve => { child.once('exit', resolve); child.stdin.end('release\n'); });
    };
  }
  try {
    const staff = await browser.newPage();
    staff.on('pageerror', error => errors.push(error.message));
    await staff.goto(base + '/masuk');
    assert(await staff.getByText('Lingkungan contoh').count(), 'Refusing outside demo');
    await staff.getByLabel('Email', { exact: true }).fill('pengelola@example.test');
    await staff.getByLabel('Kata sandi', { exact: true }).fill('Lentera-Demo-2026!');
    await staff.getByRole('button', { name: 'Masuk', exact: true }).click();
    await staff.waitForURL('**/beranda');
    await staff.getByRole('link', { name: 'Kelola kegiatan', exact: true }).click();
    await staff.getByRole('link', { name: /^Kelola kegiatan:/ }).first().click();
    await staff.getByRole('link', { name: 'Tambah sesi', exact: true }).click();
    await staff.getByLabel('Nama sesi', { exact: true }).fill('Antrean QA ' + Date.now());
    await staff.getByLabel('Nama narasumber', { exact: true }).fill('Narasumber antrean sintetis');
    await staff.getByLabel('Versi instrumen L1', { exact: true }).selectOption({ index: 1 });
    const date = d => new Date(Date.now() + d * 86400000 + 7 * 3600000).toISOString().slice(0, 16);
    for (const [field, value] of Object.entries({ starts_at: date(0), join_opens_at: date(-1), join_closes_at: date(1), l1_opens_at: date(-1), l1_closes_at: date(1) })) await staff.locator('[name=' + field + ']').fill(value);
    await staff.getByRole('button', { name: 'Simpan sesi', exact: true }).click();
    await staff.getByRole('button', { name: 'Aktifkan akses kelas', exact: true }).click();
    await staff.getByRole('link', { name: 'Lihat QR dan tautan', exact: true }).click();
    const joinURL = await staff.getByLabel('Tautan peserta').inputValue();
    const guestContext = await browser.newContext();
    const guest = await guestContext.newPage();
    diagnosticPage = guest;
    guest.on('pageerror', error => errors.push(error.message));
    await guest.goto(joinURL);
    const payload = { full_name: 'Antrean Sintetis', institution_name: 'Universitas Sultan Ageng Tirtayasa', identity_number: '0000123', department: 'Ekonomi', faculty: 'Ekonomi', phone: '08123456789', participant_category: 'Mahasiswa', email: 'antrean@example.test' };
    for (const [field, value] of Object.entries(payload)) await guest.locator('[name=' + field + ']').fill(value);
    let submissions = 0;
    guest.on('request', request => { if (request.method() === 'POST' && request.url() === joinURL) submissions++; });
    const release = await hold();
    await guest.getByRole('button', { name: 'Gabung kelas', exact: true }).click();
    await guest.locator('#lr-position:not([hidden])').waitFor();
    assert.equal(submissions, 0, 'Form sent while all slots occupied');
    await guest.getByRole('button', { name: 'Main tic-tac-toe', exact: true }).click();
    await guest.getByRole('button', { name: 'Baris 1, kolom 1: kosong', exact: true }).click();
    await guest.locator('.lr-cell[data-mark="O"]').waitFor();
    assert.equal(await guest.locator('.lr-cell[data-mark="X"]').count(), 1);
    for (const width of [375, 768, 1440]) {
      await guest.setViewportSize({ width, height: 900 });
      assert(await guest.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Overflow at ' + width);
      await guest.screenshot({ path: path.join(output, 'game-' + width + '.png') });
    }
    await guest.keyboard.press('Escape');
    await guest.getByRole('button', { name: 'Batalkan antrean', exact: true }).click();
    for (const [field, value] of Object.entries(payload)) assert.equal(await guest.locator('[name=' + field + ']').inputValue(), value);
    await guest.getByRole('button', { name: 'Gabung kelas', exact: true }).click();
    await guest.locator('#lr-position:not([hidden])').waitFor();
    await guest.screenshot({ path: path.join(output, 'queue.png') });
    const other = await browser.newPage();
    await other.goto(joinURL);
    await other.waitForURL('**/waiting-room.html#*');
    await other.locator('#lr-position:not([hidden])').waitFor();
    const queueURL = other.url();
    await other.reload();
    await other.locator('#lr-position:not([hidden])').waitFor();
    assert.equal(other.url(), queueURL, 'Refreshing should retain ticket and destination');
    await other.getByRole('button', { name: 'Batalkan antrean', exact: true }).click();
    await other.close();
    await release();
    await guest.waitForURL('**/kegiatan', { timeout: 35000 });
    assert.equal(submissions, 1, 'Registration was posted multiple times');
    await guest.getByText('Data peserta tersimpan.', { exact: false }).waitFor();
    await guest.getByRole('link', { name: 'Buka evaluasi L1', exact: true }).click();
    for (let i = 0; i < 5; i++) await guest.locator('#q' + i + '-5').check();
    await guest.getByLabel('Masukan (opsional, maksimal 2.000 karakter)').fill('Isian antrean sintetis tetap utuh.');
    const releaseL1 = await hold();
    await guest.getByRole('button', { name: 'Kirim evaluasi', exact: true }).click();
    await guest.locator('#lr-position:not([hidden])').waitFor();
    await guest.getByRole('button', { name: 'Batalkan antrean', exact: true }).click();
    assert(await guest.getByRole('button', { name: 'Kirim evaluasi', exact: true }).isEnabled(), 'Alpine disabled cancelled form');
    for (let i = 0; i < 5; i++) assert(await guest.locator('#q' + i + '-5').isChecked());
    await guest.getByRole('button', { name: 'Kirim evaluasi', exact: true }).click();
    await guest.locator('#lr-position:not([hidden])').waitFor();
    await releaseL1();
    await guest.getByRole('heading', { name: 'Evaluasi sudah dikirim.', exact: true }).waitFor({ timeout: 35000 });
    await guest.getByText('5,00 / 6', { exact: false }).waitFor();
    await guest.reload();
    await guest.getByRole('heading', { name: 'Evaluasi sudah dikirim.', exact: true }).waitFor();
    // Lightweight endpoint rejects cross-origin/simple POSTs without reaching Laravel.
    assert.equal((await guest.request.post(base + '/admission.php', { data: '{}', headers: { 'Content-Type': 'text/plain' } })).status(), 415);
    assert.equal((await guest.request.post(base + '/admission.php', { data: { path: '/', method: 'GET' }, headers: { Origin: 'https://sibling.invalid' } })).status(), 403);
    assert.equal(errors.length, 0, errors.join('\n'));
    console.log(JSON.stringify({ passed: true, queuePreservesForm: true, cancelPreservesAlpine: true, registrationPosts: submissions, staticReloadRetainsTicket: true, l1Confirmed: true, game: true, widths: [375, 768, 1440], errors }));
  } catch (error) {
    if (diagnosticPage && !diagnosticPage.isClosed()) {
      await diagnosticPage.screenshot({ path: path.join(output, 'failure.png'), fullPage: true }).catch(() => {});
      console.error(JSON.stringify({ url: diagnosticPage.url(), errors, diagnostic: await diagnosticPage.evaluate(() => ({
        dialog: document.querySelector('.lr-dialog')?.textContent,
        error: document.querySelector('[data-lentera-form-error]')?.textContent,
        invalid: [...document.querySelectorAll('form :invalid')].map(input => ({ name: input.name, message: input.validationMessage })),
      })).catch(() => null) }));
    }
    throw error;
  } finally {
    for (const holder of holders) if (holder.exitCode === null) holder.kill();
    await browser.close();
  }
})().catch(error => { console.error(error); process.exit(1); });
