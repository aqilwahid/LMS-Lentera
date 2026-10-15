/* Local demo only. Generates two small valid PDFs; no client documents are uploaded. */
const assert=require('node:assert/strict');const fs=require('node:fs');const path=require('node:path');const crypto=require('node:crypto');
const {chromium}=require(process.env.LENTERA_PLAYWRIGHT_PATH||'playwright');
function pdf(text){
 const stream='BT /F1 18 Tf 40 140 Td ('+text+') Tj ET';
 const objects=['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 320 200] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>','<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>','<< /Length '+Buffer.byteLength(stream)+' >>\nstream\n'+stream+'\nendstream'];
 let out='%PDF-1.4\n';const offsets=[0];
 for(let i=0;i<objects.length;i++){offsets.push(Buffer.byteLength(out));out+=(i+1)+' 0 obj\n'+objects[i]+'\nendobj\n';}
 const xref=Buffer.byteLength(out);out+='xref\n0 6\n0000000000 65535 f \n';for(const offset of offsets.slice(1))out+=String(offset).padStart(10,'0')+' 00000 n \n';
 return Buffer.from(out+'trailer\n<< /Root 1 0 R /Size 6 >>\nstartxref\n'+xref+'\n%%EOF\n');
}
const digest=b=>crypto.createHash('sha256').update(b).digest('hex');
(async()=>{
 const browser=await chromium.launch({channel:'chromium'});
 try{
  const base=process.env.LENTERA_BASE_URL||'http://127.0.0.1:8000';const stamp=Date.now();const v1=pdf('LENTERA PRIVATE QA V1');const v2=pdf('LENTERA PRIVATE QA V2');
  const page=await browser.newPage({viewport:{width:1440,height:1000}});const errors=[];page.on('pageerror',e=>errors.push(e.message));await page.goto(base+'/masuk');
  assert(await page.getByText('Lingkungan contoh').count(),'Refusing outside demo');
  await page.getByLabel('Email',{exact:true}).fill('pengelola@example.test');await page.getByLabel('Kata sandi',{exact:true}).fill('Lentera-Demo-2026!');
  await page.getByRole('button',{name:'Masuk',exact:true}).click();await page.waitForURL('**/beranda');
  await page.getByRole('link',{name:'Kelola kegiatan',exact:true}).click();await page.getByRole('link',{name:/^Kelola kegiatan:/}).first().click();await page.getByRole('link',{name:'Tambah sesi',exact:true}).click();
  await page.getByLabel('Nama sesi',{exact:true}).fill('Sesi PDF QA '+stamp);await page.getByLabel('Nama narasumber',{exact:true}).fill('Narasumber PDF QA');
  await page.getByLabel('Versi instrumen L1',{exact:true}).selectOption({index:1});
  const date=d=>new Date(Date.now()+d*86400000+7*3600000).toISOString().slice(0,16);
  for(const[n,v]of Object.entries({starts_at:date(0),join_opens_at:date(-1),join_closes_at:date(1),l1_opens_at:date(-1),l1_closes_at:date(1)}))await page.locator('[name='+n+']').fill(v);
  await page.getByRole('button',{name:'Simpan sesi',exact:true}).click();await page.getByRole('button',{name:'Aktifkan akses kelas',exact:true}).click();await page.getByRole('link',{name:'Lihat QR dan tautan',exact:true}).waitFor();
  const sessionEdit=page.url();await page.getByRole('link',{name:'Kelola materi PDF',exact:true}).click();
  await page.getByLabel('Judul materi',{exact:true}).fill('Materi PDF QA '+stamp);await page.getByLabel('Berkas PDF',{exact:true}).setInputFiles({name:'qa-v1.pdf',mimeType:'application/pdf',buffer:v1});
  const uploadAction=await page.locator('form[enctype]').getAttribute('action');
  const csrfInvalid=await page.request.post(uploadAction,{headers:{Accept:'application/json','Sec-Fetch-Site':'cross-site'},multipart:{_token:'invalid',title:'Invalid',upload_nonce:crypto.randomUUID(),pdf:{name:'qa.pdf',mimeType:'application/pdf',buffer:v1}},maxRedirects:0});assert.equal(csrfInvalid.status(),419);
  await page.getByRole('button',{name:'Unggah PDF',exact:true}).click();await page.getByRole('button',{name:'Terbitkan versi 1',exact:true}).waitFor();
  const detail=page.url();const materialId=detail.split('/').pop();await page.goto(sessionEdit);await page.getByRole('link',{name:'Lihat QR dan tautan',exact:true}).click();const join=await page.getByLabel('Tautan peserta').inputValue();
  const context=await browser.newContext();const guest=await context.newPage();guest.on('pageerror',e=>errors.push(e.message));
  const downloadURL=join+'/materi/'+materialId+'/unduh';assert.equal((await context.request.get(downloadURL)).status(),403);
  await guest.goto(join);for(const[n,v]of Object.entries({full_name:'Peserta PDF QA '+stamp,institution_name:'Universitas Sultan Ageng Tirtayasa',identity_number:'0000456',department:'Ekonomi',faculty:'Ekonomi',phone:'081200000000',participant_category:'Mahasiswa',email:'pdf-qa@example.test'}))await guest.locator('[name='+n+']').fill(v);
  await guest.getByRole('button',{name:'Gabung kelas',exact:true}).click();await guest.waitForURL('**/kegiatan');const classURL=guest.url();
  assert.equal((await context.request.get(downloadURL)).status(),404);assert.equal(await guest.getByRole('link',{name:/^Baca materi:/}).count(),0);
  await page.goto(detail);await page.getByRole('button',{name:'Terbitkan versi 1',exact:true}).click();await page.getByText('Versi materi diterbitkan untuk peserta kelas.',{exact:true}).waitFor();await guest.reload();await guest.getByRole('link',{name:/^Baca materi:/}).click();
  await guest.waitForTimeout(1500); // Allow the native Chromium PDF viewer to initialize.
  const outputs=path.join(__dirname,'outputs','materials');fs.mkdirSync(outputs,{recursive:true});
  for(const width of[375,768,1440]){await guest.setViewportSize({width,height:1000});assert(await guest.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'Overflow at '+width);await guest.screenshot({path:path.join(outputs,'preview-'+width+'.png'),fullPage:true});}
  const fileURL=await guest.getByRole('link',{name:'Buka PDF di tab baru',exact:true}).getAttribute('href');const inline=await context.request.get(fileURL);assert.equal(inline.status(),200);assert(inline.headers()['content-disposition'].startsWith('inline;'));assert.equal(digest(await inline.body()),digest(v1));
  await page.goto(detail);const form=page.locator('form[enctype]');const nonce=await form.locator('[name=upload_nonce]').inputValue();const revision=await form.locator('[name=revision]').inputValue();const csrf=await form.locator('[name=_token]').inputValue();const action=await form.getAttribute('action');
  await page.getByLabel('Berkas PDF versi baru',{exact:true}).setInputFiles({name:'qa-v2.pdf',mimeType:'application/pdf',buffer:v2});await page.getByRole('button',{name:'Unggah versi baru',exact:true}).click();await page.getByRole('button',{name:'Terbitkan versi 2',exact:true}).waitFor();
  const retry=await page.request.post(action,{multipart:{_token:csrf,upload_nonce:nonce,revision,pdf:{name:'qa-v2.pdf',mimeType:'application/pdf',buffer:v2}},maxRedirects:0});assert.equal(retry.status(),302);await page.reload();assert.equal(await page.getByRole('button',{name:'Terbitkan versi 3',exact:true}).count(),0);
  assert.equal(digest(await(await context.request.get(downloadURL)).body()),digest(v1));await page.getByRole('button',{name:'Terbitkan versi 2',exact:true}).click();await page.getByText('Versi materi diterbitkan untuk peserta kelas.',{exact:true}).waitFor();
  const second=await context.request.get(downloadURL);assert.equal(second.status(),200);assert.equal(digest(await second.body()),digest(v2));assert(second.headers()['cache-control'].includes('no-store'));
  await guest.reload();const event=guest.waitForEvent('download');await guest.getByRole('link',{name:'Unduh PDF',exact:true}).click();const download=await event;assert.equal(digest(fs.readFileSync(await download.path())),digest(v2));
  await page.getByRole('button',{name:'Sembunyikan materi',exact:true}).click();await page.getByText('Materi disembunyikan dari peserta. Riwayat versi tetap tersimpan.',{exact:true}).waitFor();assert.equal((await context.request.get(downloadURL)).status(),404);
  await guest.goto(classURL);assert.equal(await guest.getByRole('link',{name:/^Baca materi:/}).count(),0);
  await page.getByRole('button',{name:'Terbitkan versi 2',exact:true}).click();await page.getByText('Versi materi diterbitkan untuk peserta kelas.',{exact:true}).waitFor();assert.equal((await context.request.get(downloadURL)).status(),200);
  await page.getByRole('link',{name:'Riwayat perubahan',exact:true}).click();assert(await page.getByRole('heading',{name:'Materi disembunyikan',exact:true}).count());assert.equal(errors.length,0,errors.join('\n'));
  console.log(JSON.stringify({passed:true,flow:'private upload → draft → publish → guest preview/download → version/retry → publish V2 → hide → restore',widths:[375,768,1440],byteHashesVerified:true,csrfChecked:true,errors}));await context.close();
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1);});
