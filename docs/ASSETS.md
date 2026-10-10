# Aset dan asal

| Aset lokal | Asal / penggunaan |
|---|---|
| `assets/lentera-logo.png` | Logo LENTERA asli yang diberikan klien |
| `assets/bank-indonesia-logo.png` | Logo Bank Indonesia asli yang diberikan klien |
| `assets/materi-kebanksentralan.pdf` | “Edukasi dan Literasi Kebanksentralan.pdf”, 11 halaman; salinan web dikompresi dengan Ghostscript |
| `assets/lighthouse.png` | Ilustrasi hero, dibuat dengan built-in imagegen dari acuan S01 V2 |
| `assets/learning.png` | Ilustrasi ruang belajar, dibuat dengan built-in imagegen dari acuan S04 V2 |
| `assets/campus.png` | Ilustrasi kondisi kosong mitra, dibuat dengan built-in imagegen dari acuan S17 V2 |
| `assets/fonts/*` | Outfit dan Work Sans dari Google Fonts; lisensi OFL disertakan |
| `assets/lucide.min.js` | Lucide, lisensi ISC disertakan |
| `assets/pdf-lib.min.js` | pdf-lib, lisensi MIT disertakan |
| `assets/fontkit.min.js` | @pdf-lib/fontkit, memasang font lokal di PDF; lisensi MIT dalam README disertakan |
| `assets/pdf-fonts.js` | Salinan base64 font OFL untuk ekspor PDF tanpa koneksi jaringan |
| `assets/jszip.min.js` | JSZip, lisensi MIT/GPL sesuai file lisensi disertakan |

Font diperoleh melalui Google Fonts CSS API dan URL font resmi `fonts.gstatic.com`. Lisensi diperoleh dari repositori `google/fonts` pada direktori `ofl/outfit` dan `ofl/worksans`.

## Prompt ilustrasi final

Mode: built-in imagegen, dengan gambar V2 sebagai referensi. Hasil disalin ke `dist/assets/` dalam source repository dan `assets/` dalam ZIP.

**Lighthouse** — Use case: background-extraction. Asset type: website hero illustration. Reference image is approved LENTERA webpage mockup; extract/recreate ONLY the right-side editorial 3D lighthouse and books island artwork as a standalone image, not the page. Match exact white stone lighthouse with navy cupola, warm golden light, blue books, small green plants, pale calm blue sea and wisps, high-end realistic miniature. Full subject centered, square canvas, pale icy #EAF4FA backdrop, comfortable 6% margin. No text, no letters, no logo, no UI, no border, no headings, no buttons. Maintain reference polish and palette.

**Learning** — Use case: background-extraction. Asset type: responsive learning card art. Use approved webpage only as art reference; recreate ONLY white-and-blue classical bank building on open book with upright navy books, foliage, calm water ribbons, blue sky. Refined photoreal 3D miniature same as reference's lesson card. Entire subject centered in landscape 4:3 frame with 8% breathing room, pale icy blue #EAF4FA backdrop. Do NOT include any text, branding, logos, BI monogram, numbers, buttons, page UI, or borders.

**Campus** — Use case: background-extraction. Asset type: website empty state artwork. From approved UI reference, recreate ONLY central pale blue and white campus-and-document miniature illustration. Elegant soft dimensional paper-cut illustration of classical university building, tiny flag, simplified trees and rounded document sheet in front, pale sky-blue round halo, generous white background #FFFFFF. Landscape 4:3, centered subject, soft muted palette, no text, no logos, no page UI, no text-like pseudoletters. Visual matches the central partner dashboard empty state.
