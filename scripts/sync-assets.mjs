import { mkdir, copyFile, cp } from 'node:fs/promises';
await mkdir('public/assets', { recursive: true });
await copyFile('styles.css', 'public/styles-v2.css');
await cp('assets/fonts', 'public/assets/fonts', { recursive: true });
for (const name of ['lentera-logo.png','bank-indonesia-logo.png','learning.png','lighthouse.png','campus.png','lentera-logo.webp','bank-indonesia-logo.webp','learning.webp','lighthouse.webp','campus.webp']) {
    await copyFile('assets/'+name, 'public/assets/'+name);
}
