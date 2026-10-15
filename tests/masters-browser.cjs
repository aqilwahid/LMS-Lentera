/* Requires the local demo server; creates synthetic master/session/participant data. */
const assert=require('node:assert/strict');const fs=require('node:fs');const path=require('node:path');
const {chromium}=require(process.env.LENTERA_PLAYWRIGHT_PATH||'playwright');
(async()=>{
 const browser=await chromium.launch();
 try{
  const base=process.env.LENTERA_BASE_URL||'http://127.0.0.1:8000';const stamp=Date.now();
  const page=await browser.newPage({viewport:{width:1440,height:1000}});const errors=[];
  page.on('pageerror',e=>errors.push(e.message));await page.goto(base+'/masuk');
  assert(await page.getByText('Lingkungan contoh').count(),'Refusing outside demo');
  await page.getByLabel('Email',{exact:true}).fill('pengelola@example.test');await page.getByLabel('Kata sandi',{exact:true}).fill('Lentera-Demo-2026!');
  await page.getByRole('button',{name:'Masuk',exact:true}).click();await page.waitForURL('**/beranda');
  await page.getByRole('link',{name:'Institusi',exact:true}).click();await page.getByRole('link',{name:'Tambah institusi',exact:true}).click();
  await page.getByLabel('Nama institusi',{exact:true}).fill('Institusi QA '+stamp);await page.getByRole('button',{name:'Simpan master',exact:true}).click();
  await page.getByText('Master tersimpan.',{exact:true}).waitFor();
  await page.getByRole('link',{name:'Narasumber',exact:true}).click();await page.getByRole('link',{name:'Tambah narasumber',exact:true}).click();
  const speaker='Narasumber Master QA '+stamp;await page.getByLabel('Nama narasumber',{exact:true}).fill(speaker);
  await page.getByLabel('Asal instansi (opsional)').fill('Instansi QA');await page.getByRole('button',{name:'Simpan master',exact:true}).click();
  await page.getByText('Master tersimpan.',{exact:true}).waitFor();
  await page.getByRole('link',{name:'Kelola kegiatan',exact:true}).click();await page.getByRole('link',{name:/^Kelola kegiatan:/}).first().click();
  await page.getByRole('link',{name:'Tambah sesi',exact:true}).click();await page.getByLabel('Nama sesi',{exact:true}).fill('Sesi Master QA '+stamp);
  await page.getByLabel('Pilih dari master narasumber (opsional)').selectOption({label:speaker});await page.getByLabel('Versi instrumen L1',{exact:true}).selectOption({index:1});
  const date=d=>new Date(Date.now()+d*86400000+7*3600000).toISOString().slice(0,16);
  for(const [n,v]of Object.entries({starts_at:date(0),join_opens_at:date(-1),join_closes_at:date(1),l1_opens_at:date(-1),l1_closes_at:date(1)}))await page.locator('[name='+n+']').fill(v);
  await page.getByRole('button',{name:'Simpan sesi',exact:true}).click();await page.getByRole('button',{name:'Aktifkan akses kelas',exact:true}).click();
  await page.getByRole('link',{name:'Lihat QR dan tautan',exact:true}).click();const join=await page.getByLabel('Tautan peserta').inputValue();
  const context=await browser.newContext();const guest=await context.newPage();guest.on('pageerror',e=>errors.push(e.message));await guest.goto(join);
  assert.equal(await guest.getByLabel('Nama Narasumber',{exact:true}).inputValue(),speaker);
  const participant='Peserta Master QA '+stamp;
  for(const[n,v]of Object.entries({full_name:participant,institution_name:'Alias Kampus QA '+stamp,identity_number:'00000123',department:'Ekonomi',faculty:'Ekonomi',phone:'081200000000',participant_category:'Mahasiswa',email:'peserta-master@example.test'}))await guest.locator('[name='+n+']').fill(v);
  await guest.getByRole('button',{name:'Gabung kelas',exact:true}).click();await guest.getByRole('link',{name:'Buka evaluasi L1',exact:true}).click();
  const l1=guest.url();for(let i=0;i<5;i++)await guest.locator('#q'+i+'-5').check();
  await guest.getByRole('button',{name:'Kirim evaluasi',exact:true}).click();await guest.getByRole('heading',{name:'Evaluasi sudah dikirim.'}).waitFor();
  await page.getByRole('link',{name:'Kepesertaan',exact:true}).click();await page.getByLabel('Cari nama, nomor identitas atau instansi').fill(participant);
  await page.getByRole('button',{name:'Terapkan filter',exact:true}).click();await page.getByRole('link',{name:/^Lihat kepesertaan #/}).first().click();
  const detail=page.url();const outputs=path.join(__dirname,'outputs','masters');fs.mkdirSync(outputs,{recursive:true});
  for(const width of[375,768,1440]){await page.setViewportSize({width,height:1000});assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'Overflow at '+width);await page.screenshot({path:path.join(outputs,'participant-'+width+'.png'),fullPage:true});}
  await page.getByLabel('Pemetaan ke master institusi').selectOption({label:'Universitas Sultan Ageng Tirtayasa'});
  await page.getByLabel('Status kepesertaan',{exact:true}).selectOption('revoked');await page.getByRole('button',{name:'Simpan kepesertaan',exact:true}).click();
  await page.getByText('Status dan pemetaan tersimpan.',{exact:false}).waitFor();await guest.goto(l1);assert.equal(guest.url(),join);
  assert.equal(await guest.getByRole('heading',{name:'Evaluasi sudah dikirim.'}).count(),0);
  await page.goto(detail);await page.getByLabel('Status kepesertaan',{exact:true}).selectOption('active');await page.getByRole('button',{name:'Simpan kepesertaan',exact:true}).click();
  await page.getByText('Status dan pemetaan tersimpan.',{exact:false}).waitFor();await guest.goto(l1);assert(await guest.getByText('5,00 / 6').count());
  await page.getByRole('link',{name:'Riwayat perubahan',exact:true}).click();assert(await page.getByRole('heading',{name:'Kepesertaan diubah',exact:true}).count());
  assert.equal(await page.getByText('peserta-master@example.test',{exact:true}).count(),0);assert.equal(errors.length,0,errors.join('\n'));
  console.log(JSON.stringify({passed:true,flow:'master → speaker session → guest L1 → mapping/revoke/reactivate → retained response/audit',widths:[375,768,1440],errors}));await context.close();
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
