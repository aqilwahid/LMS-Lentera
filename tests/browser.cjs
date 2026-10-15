/* Runs only against an explicitly enabled synthetic demo environment. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require(process.env.LENTERA_PLAYWRIGHT_PATH || 'playwright');
(async () => {
  const baseURL = process.env.LENTERA_BASE_URL || 'http://127.0.0.1:8000';
  const browser = await chromium.launch();
  try {
    const staff = await browser.newPage({ viewport:{width:1440,height:1000} });
    const errors = [];
    staff.on('pageerror',e => errors.push(e.message));
    await staff.goto(baseURL+'/masuk');
    assert(await staff.getByText('Lingkungan contoh').count(),'Refusing to submit outside demo environment');
    await staff.getByLabel('Email',{exact:true}).fill('pengelola@example.test');
    await staff.getByLabel('Kata sandi',{exact:true}).fill('Lentera-Demo-2026!');
    await staff.getByRole('button',{name:'Masuk',exact:true}).click();
    await staff.waitForURL('**/beranda');
    await staff.getByRole('link',{name:/QR dan tautan:/}).first().click();
    const joinURL = await staff.getByLabel('Tautan peserta').inputValue();
    const qrResponse = await staff.request.get(await staff.locator('img[alt^="QR untuk"]').getAttribute('src'));
    assert.equal(qrResponse.status(),200);
    assert((await qrResponse.text()).includes('<svg'));
    const outputs = path.join(__dirname,'outputs','backend');
    fs.mkdirSync(outputs,{recursive:true});
    await staff.screenshot({path:path.join(outputs,'class-qr.png'),fullPage:true});
    const guestContext = await browser.newContext({viewport:{width:375,height:1000}});
    const page = await guestContext.newPage();
    page.on('pageerror',e=>errors.push(e.message));
    await page.goto(joinURL);
    assert.equal(await page.locator('input[type=password]').count(),0);
    assert(await page.getByRole('heading',{name:'Gabung kelas.'}).count());
    for(const width of [375,768,1440]) {
      await page.setViewportSize({width,height:1000});
      assert(await page.evaluate(()=>document.documentElement.scrollWidth <= innerWidth),'Join overflow at '+width);
      await page.screenshot({path:path.join(outputs,'join-'+width+'.png'),fullPage:true});
    }
    const csrfJoin = await page.request.post(joinURL,{form:{_token:'invalid'},maxRedirects:0});
    assert.equal(csrfJoin.status(),419);
    const fields = { full_name:'Peserta QR Browser Contoh',institution_name:'Universitas Sultan Ageng Tirtayasa',identity_number:'00123456',department:'Ilmu Ekonomi',faculty:'Ekonomi dan Bisnis',phone:'+62 812-3456-7890',participant_category:'Mahasiswa',email:'browser-qr@example.test' };
    for(const [name,value] of Object.entries(fields)) { await page.locator('[name='+name+']').fill(value); }
    await page.getByRole('button',{name:'Gabung kelas',exact:true}).click();
    await page.waitForURL('**/kegiatan');
    assert(await page.getByText('Selamat datang, Peserta QR Browser Contoh.').count());
    await page.getByRole('link',{name:'Buka evaluasi L1'}).click();
    const l1URL=page.url();
    assert.equal(await page.locator('input[type=radio]').count(),30);
    assert.equal(await page.locator('input[type=radio]:checked').count(),0);
    await page.setViewportSize({width:375,height:1000});
    assert((await page.locator('.rating-option').first().boundingBox()).height>=44);
    await page.screenshot({path:path.join(outputs,'guest-l1-375.png'),fullPage:true});
    const token=await page.locator('form.panel input[name=_token]').inputValue();
    const instrument=await page.locator('[name=instrument_id]').inputValue();
    const invalid=await page.request.post(l1URL,{headers:{Accept:'application/json'},form:{_token:token,instrument_id:instrument,'answers[0]':'7'}});
    assert.equal(invalid.status(),422);
    for(let i=0;i<5;i++) { await page.locator('#q'+i+'-5').check(); }
    await page.getByLabel('Masukan (opsional, maksimal 2.000 karakter)').fill('Respons sintetis QR dari browser QA.');
    await page.getByRole('button',{name:'Kirim evaluasi',exact:true}).click();
    await page.getByRole('heading',{name:'Evaluasi sudah dikirim.'}).waitFor();
    assert(await page.getByText('5,00 / 6').count());
    await page.reload();
    assert(await page.getByRole('heading',{name:'Evaluasi sudah dikirim.'}).count());
    const retry=await page.request.post(l1URL,{form:{_token:token,instrument_id:instrument,'answers[0]':'1'},maxRedirects:0});
    assert.equal(retry.status(),302);
    await page.goto(l1URL);
    assert(await page.getByText('5,00 / 6').count());
    await page.screenshot({path:path.join(outputs,'guest-confirmation.png'),fullPage:true});
    const other=await browser.newContext();
    const otherPage=await other.newPage();
    await otherPage.goto(l1URL);
    assert.equal(otherPage.url(),joinURL);
    assert.equal(await otherPage.getByText('Peserta QR Browser Contoh').count(),0);
    await other.close();
    await staff.goto(baseURL+'/beranda');
    assert(await staff.getByText('5,00 / 6',{exact:false}).count());
    assert.equal(await staff.getByText('Respons sintetis QR dari browser QA.').count(),0);
    assert.equal(errors.length,0,errors.join('\n'));
    console.log(JSON.stringify({passed:true,browser:'Chromium',flow:'QR → form → class → L1 without account',widths:[375,768,1440],csrfChecked:true,privateBrowserAccess:true,errors}));
    await guestContext.close();
  } finally { await browser.close(); }
})().catch(error=>{console.error(error);process.exit(1);});
