// Optimiza las fotografías de Morano USA (Desktop\MATERIAL-CLIENTES\morano-usa\web-actual:
// las de moranousa.com y fotos reales de Morano Gruppo, prefijo «gruppo_»).
// Solo cambia formato y tamaño: no recorta ni retoca.
// Salida: public/img/<nombre>-<ancho>.{avif,webp,jpg} + src/data/images.json
// (dimensiones, anchos, color de fondo y huella ?v=).
//
// Uso: npm run images  [carpeta de originales]
import sharp from 'sharp';
import { mkdirSync, writeFileSync, existsSync, readFileSync, copyFileSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { createHash } from 'node:crypto';
import { fileURLToPath } from 'node:url';
import { homedir } from 'node:os';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const SRC = process.argv[2] || join(homedir(), 'Desktop', 'MATERIAL-CLIENTES', 'morano-usa', 'web-actual');
const OUT = join(root, 'public', 'img');
mkdirSync(OUT, { recursive: true });

// Versión corta del contenido: va como ?v= en las URL, así un celular que guardó
// una versión anterior con el mismo nombre (caché de 30 días) descarga la nueva.
const hash = (buf) => createHash('md5').update(buf).digest('hex').slice(0, 8);

// nombre → [archivo original, tipo]. Tipos: hero (pantalla completa), foto.
const PHOTOS = {
  // Portada (la misma foto de puerto que usa hoy moranousa.com)
  puerto: ['morano-hero-DPqcMSJm.jpg', 'hero'],
  cosecha: ['gruppo_2025_02_WhatsApp-Image-2025-02-26-at-13.06.41-1-scaled.jpeg', 'foto'],
  // Quiénes somos: planta del grupo en Colombia
  'planta-aerea': ['gruppo_2026_08_WhatsApp-Image-2026-08-06-at-23.03.28.jpeg', 'foto'],
  // Cadena de suministro
  cultivo: ['gruppo_2025_03_WhatsApp-Image-2025-03-03-at-15.26.22-scaled-e1741034070721.jpeg', 'foto'],
  refineria: ['gruppo_2026_08_WhatsApp-Image-2026-07-17-at-22.26.06.jpeg', 'foto'],
  envasado: ['gruppo_2026_08_WhatsApp-Image-2026-07-17-at-22.26.07-2.jpeg', 'foto'],
  exportacion: ['gruppo_2026_08_WhatsApp-Image-2026-08-06-at-22.58.26.jpeg', 'foto'],
  // Productos (las de moranousa.com)
  'p-aceites': ['rbd-palm-oil-CyZyGzC0.jpg', 'foto'],
  'p-grasas': ['vegetable-shortening-DKXcVW-w.jpg', 'foto'],
  'p-marca-propia': ['private-label-line-DNR3vqXv.jpg', 'foto'],
  // Formatos y sostenibilidad
  bodega: ['NQ65HJHPHZETLPUT5V6JJRB6MI.avif', 'foto'],
  racimo: ['gruppo_2025_02_WhatsApp-Image-2025-02-26-at-13.05.59-3-scaled.jpeg', 'foto'],
};
const WIDTHS = { hero: [960, 1440, 1920], foto: [640, 960, 1280] };

const manifest = {};
for (const [name, [file, kind]] of Object.entries(PHOTOS)) {
  const input = join(SRC, file);
  if (!existsSync(input)) throw new Error(`Falta el original: ${file}`);
  const meta = await sharp(input).rotate().metadata();
  const { width, height } = meta.autoOrient ?? meta;
  const widths = [...new Set(WIDTHS[kind].map((w) => Math.min(w, width)))];
  const { dominant } = await sharp(input).stats();
  const color = `#${[dominant.r, dominant.g, dominant.b].map((v) => v.toString(16).padStart(2, '0')).join('')}`;
  for (const w of widths) {
    const pipe = sharp(input).rotate().resize({ width: w, withoutEnlargement: true });
    await pipe.clone().avif({ quality: 50, effort: 6 }).toFile(join(OUT, `${name}-${w}.avif`));
    await pipe.clone().webp({ quality: 74, effort: 6 }).toFile(join(OUT, `${name}-${w}.webp`));
    await pipe.clone().jpeg({ quality: 78, mozjpeg: true, progressive: true }).toFile(join(OUT, `${name}-${w}.jpg`));
  }
  manifest[name] = { width, height, widths, color, v: hash(readFileSync(input)) };
  console.log(name, `${width}×${height}`, widths.join('/'));
}

// Sello ISCC «Certificate Holder» (el mismo que muestra hoy moranousa.com).
await sharp(join(SRC, 'Certification_Holder_Banner_BLACK_positive_RGB.webp'))
  .resize({ width: 520 })
  .webp({ quality: 90, alphaQuality: 100 })
  .toFile(join(OUT, 'iscc-520.webp'));

// Términos y condiciones comerciales (PDF de moranousa.com).
mkdirSync(join(root, 'public', 'docs'), { recursive: true });
copyFileSync(
  join(SRC, 'Morano_USA_Commercial_Sales_Terms_and_Conditions_Letterhead.pdf'),
  join(root, 'public', 'docs', 'morano-usa-commercial-sales-terms-and-conditions.pdf')
);

// Imagen para redes (1200×630): foto del puerto + sombra azul marino + logotipo.
const lock = await sharp(join(root, 'public', 'brand', 'usa-reverse.png')).resize({ width: 620 }).png().toBuffer();
const lockMeta = await sharp(lock).metadata();
const shade = Buffer.from(
  `<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#00152B" stop-opacity=".94"/><stop offset=".65" stop-color="#00152B" stop-opacity=".8"/><stop offset="1" stop-color="#00152B" stop-opacity=".35"/></linearGradient></defs><rect width="1200" height="630" fill="url(#g)"/></svg>`
);
await sharp(join(SRC, PHOTOS.puerto[0]))
  .resize(1200, 630, { fit: 'cover' })
  .composite([
    { input: shade, top: 0, left: 0 },
    { input: lock, top: Math.round((630 - lockMeta.height) / 2), left: 80 },
  ])
  .jpeg({ quality: 84, mozjpeg: true })
  .toFile(join(OUT, 'og-morano-usa.jpg'));

writeFileSync(join(root, 'src', 'data', 'images.json'), JSON.stringify(manifest, null, 2) + '\n');
console.log(`${Object.keys(manifest).length} fotos → src/data/images.json`);
