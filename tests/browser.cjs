/* Runs only against an explicitly enabled synthetic demo environment. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require(process.env.LENTERA_PLAYWRIGHT_PATH || 'playwright');
(async () => {
  const baseURL = process.env.LENTERA_BASE_URL || 'http://127.0.0.1:8000';
  const browser = await chromium.launch();
  try {
    const page = await browser.newPage({ viewport: { width:1440, height:1000 } });
    const errors = [];
    page.on('pageerror', e => errors.push(e.message));
    await page.goto(baseURL + '/masuk');
    assert(await page.getByText('Lingkungan contoh').count(), 'Refusing to submit outside demo environment');
    await page.getByLabel('Email', { exact:true }).fill('peserta@example.test');
    await page.getByLabel('Kata sandi', { exact:true }).fill('Lentera-Demo-2026!');
    await page.getByRole('button', { name:'Masuk', exact:true }).click();
    await page.waitForURL('**/beranda');
    await page.getByRole('link', { name:'Lihat kegiatan' }).first().click();
    await page.getByRole('link', { name:'Buka evaluasi L1' }).first().click();
    const l1URL = page.url();
    const outputs = path.join(__dirname, 'outputs', 'backend');
    fs.mkdirSync(outputs, { recursive:true });
    const alreadySubmitted = await page.getByRole('heading', { name:'Evaluasi sudah dikirim.' }).count();
    if (!alreadySubmitted) {
      assert.equal(await page.locator('input[type=radio]').count(),30);
      assert.equal(await page.locator('input[type=radio]:checked').count(),0);
      for (const width of [375,768,1440]) {
        await page.setViewportSize({ width, height:1000 });
        assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Horizontal overflow at '+width);
        await page.screenshot({ path:path.join(outputs,'l1-'+width+'.png'), fullPage:true });
      }
      await page.setViewportSize({ width:375,height:1000 });
      const radio = await page.locator('.rating-option').first().boundingBox();
      assert(radio.height >= 44);
      const token = await page.locator('form.panel input[name=_token]').inputValue();
      const badCSRF = await page.request.post(l1URL, { form: { _token:'invalid' }, maxRedirects:0 });
      assert.equal(badCSRF.status(),419);
      const invalid = await page.request.post(l1URL, { headers:{ Accept:'application/json' }, form:{ _token:token, instrument_id:await page.locator('[name=instrument_id]').inputValue(),'answers[0]':'7' } });
      assert.equal(invalid.status(),422);
      for (let i=0;i<5;i++) { await page.locator('#q'+i+'-5').check(); }
      await page.getByLabel('Masukan (opsional, maksimal 2.000 karakter)').fill('Respons sintetis dari browser QA.');
      await page.getByRole('button', { name:'Kirim evaluasi', exact:true }).click();
      await page.getByRole('heading', { name:'Evaluasi sudah dikirim.' }).waitFor();
      assert(await page.getByText('5,00 / 6').count());
    }
    await page.screenshot({ path:path.join(outputs,'confirmation.png'), fullPage:true });
    await page.reload();
    assert(await page.getByRole('heading', { name:'Evaluasi sudah dikirim.' }).count());
    await page.getByRole('button', { name:'Akun', exact:true }).click();
    await page.locator('#account-menu').getByRole('button', { name:'Keluar' }).click();
    await page.waitForURL('**/masuk');
    assert.equal(errors.length,0,errors.join('\n'));
    for(const account of ['mitra','pengelola']) {
      await page.getByLabel('Email',{exact:true}).fill(account+'@example.test');
      await page.getByLabel('Kata sandi',{exact:true}).fill('Lentera-Demo-2026!');
      await page.getByRole('button',{name:'Masuk',exact:true}).click();
      await page.waitForURL('**/beranda');
      assert(await page.getByText('5,00 / 6').count());
      assert.equal(await page.getByText('Respons sintetis dari browser QA.').count(),0);
      await page.getByRole('button',{name:'Akun',exact:true}).click();
      await page.locator('#account-menu').getByRole('button',{name:'Keluar'}).click();
      await page.waitForURL('**/masuk');
    }
    console.log(JSON.stringify({ passed:true, browser:'Chromium', widths:[375,768,1440], initialSubmission:!alreadySubmitted, csrfChecked:!alreadySubmitted, errors }));
  } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exit(1); });
