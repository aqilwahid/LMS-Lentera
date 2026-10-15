/* Requires a running local demo server; creates synthetic management data. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require(process.env.LENTERA_PLAYWRIGHT_PATH || 'playwright');
(async () => {
 const baseURL=process.env.LENTERA_BASE_URL || 'http://127.0.0.1:8000';
 const browser=await chromium.launch();
 try {
  const page=await browser.newPage({viewport:{width:1440,height:1000}});
  const errors=[]; page.on('pageerror',e=>errors.push(e.message));
  await page.goto(baseURL+'/masuk');
  assert(await page.getByText('Lingkungan contoh').count(),'Refusing outside demo environment');
  await page.getByLabel('Email',{exact:true}).fill('pengelola@example.test');
  await page.getByLabel('Kata sandi',{exact:true}).fill('Lentera-Demo-2026!');
  await page.getByRole('button',{name:'Masuk',exact:true}).click();
  await page.waitForURL('**/beranda');
  await page.getByRole('link',{name:'Kelola kegiatan',exact:true}).click();
  await page.getByRole('link',{name:'Buat kegiatan',exact:true}).click();
  const title='Kegiatan QA Pengelola '+Date.now();
  await page.getByLabel('Nama kegiatan',{exact:true}).fill(title);
  await page.getByLabel('Deskripsi kegiatan (opsional)').fill('Data sintetis dari browser QA.');
  await page.getByRole('button',{name:'Simpan kegiatan',exact:true}).click();
  await page.getByRole('link',{name:'Tambah sesi',exact:true}).click();
  await page.getByLabel('Nama sesi',{exact:true}).fill('Sesi QA Pengelola');
  await page.getByLabel('Nama narasumber',{exact:true}).fill('Narasumber QA Pengelola');
  await page.getByLabel('Versi instrumen L1',{exact:true}).selectOption({index:1});
  const date=days=>new Date(Date.now()+days*86400000+7*3600000).toISOString().slice(0,16);
  for(const [name,value] of Object.entries({starts_at:date(0),join_opens_at:date(-1),join_closes_at:date(1),l1_opens_at:date(-1),l1_closes_at:date(1)})) {
   await page.locator('[name='+name+']').fill(value);
  }
  const outputs=path.join(__dirname,'outputs','management');
  fs.mkdirSync(outputs,{recursive:true});
  for(const width of [375,768,1440]) {
   await page.setViewportSize({width,height:1000});
   assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'Overflow at '+width);
   await page.screenshot({path:path.join(outputs,'session-form-'+width+'.png'),fullPage:true});
  }
  await page.getByRole('button',{name:'Simpan sesi',exact:true}).click();
  await page.getByRole('button',{name:'Aktifkan akses kelas',exact:true}).waitFor();
  const sessionEditURL=page.url();
  await page.getByRole('button',{name:'Aktifkan akses kelas',exact:true}).click();
  await page.getByRole('link',{name:'Lihat QR dan tautan',exact:true}).click();
  const joinURL=await page.getByLabel('Tautan peserta').inputValue();
  const context=await browser.newContext();
  const guest=await context.newPage(); guest.on('pageerror',e=>errors.push(e.message));
  await guest.goto(joinURL);
  for(const [name,value] of Object.entries({full_name:'Peserta QA Pengelola',institution_name:'Universitas Sultan Ageng Tirtayasa',identity_number:'000012345',department:'Ekonomi',faculty:'Ekonomi dan Bisnis',phone:'081200000000',participant_category:'Mahasiswa',email:'qa-pengelola@example.test'})) {
   await guest.locator('[name='+name+']').fill(value);
  }
  await guest.getByRole('button',{name:'Gabung kelas',exact:true}).click();
  await guest.waitForURL('**/kegiatan');
  await page.goto(sessionEditURL);
  assert(await page.getByText('Nama sesi, narasumber dan versi instrumen dikunci',{exact:false}).count());
  assert(await page.getByLabel('Nama narasumber',{exact:true}).getAttribute('readonly')!==null);
  await guest.getByRole('link',{name:'Buka evaluasi L1',exact:true}).click();
  for(let i=0;i<5;i++) await guest.locator('#q'+i+'-5').check();
  await guest.getByRole('button',{name:'Kirim evaluasi',exact:true}).click();
  await guest.getByRole('heading',{name:'Evaluasi sudah dikirim.'}).waitFor();
  await page.getByRole('button',{name:'Ganti QR dan tautan',exact:true}).click();
  await page.getByText('QR dan tautan diganti. Bagikan tautan baru; tautan lama tidak berlaku.').waitFor();
  assert.equal((await guest.request.get(joinURL)).status(),404);
  await page.getByRole('link',{name:'Lihat QR dan tautan',exact:true}).click();
  const newURL=await page.getByLabel('Tautan peserta').inputValue();
  assert.notEqual(newURL,joinURL);
  await guest.goto(newURL);
  await guest.getByRole('link',{name:'Buka evaluasi L1',exact:true}).click();
  assert(await guest.getByRole('heading',{name:'Evaluasi sudah dikirim.'}).count());
  await page.goto(sessionEditURL);
  await page.getByRole('button',{name:'Nonaktifkan akses kelas',exact:true}).click();
  await page.getByRole('button',{name:'Aktifkan akses kelas',exact:true}).waitFor();
  assert.equal((await guest.request.get(newURL)).status(),404);
  await page.getByRole('button',{name:'Aktifkan akses kelas',exact:true}).click();
  await page.getByRole('link',{name:'Lihat QR dan tautan',exact:true}).waitFor();
  await guest.goto(newURL);
  await guest.getByRole('link',{name:'Buka evaluasi L1',exact:true}).click();
  assert(await guest.getByRole('heading',{name:'Evaluasi sudah dikirim.'}).count());
  await page.getByRole('link',{name:'Riwayat perubahan',exact:true}).click();
  assert(await page.getByRole('heading',{name:'QR diganti',exact:true}).count());
  await page.screenshot({path:path.join(outputs,'audit.png'),fullPage:true});
  assert.equal(errors.length,0,errors.join('\n'));
  console.log(JSON.stringify({passed:true,flow:'create activity/session → activate QR → guest join/L1 → rotate/disable/enable → audit',widths:[375,768,1440],errors}));
  await context.close();
 } finally {await browser.close();}
})().catch(error=>{console.error(error);process.exit(1);});
