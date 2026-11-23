// Piezas de marca a partir del logotipo oficial de Morano USA LLC
// (_material/logo-morano-usa.png: marcos + «Morano USA LLC» sobre blanco).
// No se redibuja nada: se quita el fondo blanco separando cada píxel en
// «color del logo + transparencia» y, para fondos oscuros, el azul marino pasa
// a blanco (el rojo y los grises se quedan).
//
//   public/brand/usa.png / usa-reverse.png             logotipo completo (cabecera, pie)
//   public/brand/usa-mark.png / usa-mark-reverse.png   solo los marcos (pantalla de carga)
//   public/brand/usa-word-reverse.png                  solo el nombre (pantalla de carga)
//   public/favicon-32.png, apple-touch-icon.png, icon-192.png, icon-512.png
//   src/data/brand.json                                medidas de cada pieza
//
// Uso: npm run brand
import sharp from 'sharp';
import { mkdirSync, writeFileSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const SRC = join(root, '_material', 'logo-morano-usa.png');
const OUT = join(root, 'public', 'brand');
mkdirSync(OUT, { recursive: true });

// Colores del logotipo (medidos en el archivo original).
const RED = [198, 30, 36];
const NAVY = [0, 42, 84];
const GRAY = [114, 114, 114];
const LIGHT = [212, 213, 217];
const PALETTE = [RED, NAVY, GRAY, LIGHT];
const WHITE = [255, 255, 255];
// Sobre fondo oscuro: el azul marino pasa a blanco.
const REVERSE = new Map([[NAVY, WHITE]]);

const { data, info } = await sharp(SRC).removeAlpha().raw().toBuffer({ resolveWithObject: true });
const W = info.width;
const H = info.height;

/** Para cada píxel: qué color del logo es y con cuánta opacidad sobre blanco. */
function unmix(reverse) {
  const out = Buffer.alloc(W * H * 4);
  for (let p = 0; p < W * H; p++) {
    const px = [data[p * 3], data[p * 3 + 1], data[p * 3 + 2]];
    if (Math.min(...px) >= 250) continue; // blanco: transparente
    let best = null;
    for (const col of PALETTE) {
      const d = WHITE.map((w, k) => w - col[k]);
      const v = WHITE.map((w, k) => w - px[k]);
      const dd = d.reduce((s, x) => s + x * x, 0);
      const a = Math.max(0, Math.min(1, d.reduce((s, x, k) => s + x * v[k], 0) / dd));
      const err = px.reduce((s, x, k) => s + (x - (a * col[k] + (1 - a) * 255)) ** 2, 0);
      // Los dos grises están en la misma recta que el blanco: un gris claro sólido
      // encaja igual que el gris oscuro medio transparente. Ante un empate gana
      // el color más sólido (si no, el marco gris claro sale manchado en oscuro).
      if (!best || err < best.err - 40 || (Math.abs(err - best.err) <= 40 && a > best.a)) best = { col, a, err };
    }
    const col = reverse ? (REVERSE.get(best.col) ?? best.col) : best.col;
    out[p * 4] = col[0];
    out[p * 4 + 1] = col[1];
    out[p * 4 + 2] = col[2];
    out[p * 4 + 3] = Math.round(best.a * 255);
  }
  return sharp(out, { raw: { width: W, height: H, channels: 4 } });
}

// Marca y nombre: el primer hueco vertical ancho (> 20 px) tras la marca.
const inkCol = (x) => {
  for (let y = 0; y < H; y++) {
    const i = (y * W + x) * 3;
    if (Math.min(data[i], data[i + 1], data[i + 2]) < 235) return true;
  }
  return false;
};
let markEnd = 0;
let wordStart = 0;
for (let x = 0, seen = false, gap = 0; x < W; x++) {
  if (inkCol(x)) {
    if (seen && gap > 20) {
      markEnd = x - gap;
      wordStart = x;
      break;
    }
    seen = true;
    gap = 0;
  } else if (seen) gap++;
}
if (!wordStart) throw new Error('No se encontró el hueco entre la marca y el nombre');

const brand = {};
async function save(name, img, region) {
  let buf = await img.clone().png().toBuffer();
  if (region) buf = await sharp(buf).extract(region).png().toBuffer();
  buf = await sharp(buf).trim({ threshold: 1 }).png({ compressionLevel: 9 }).toBuffer();
  const m = await sharp(buf).metadata();
  writeFileSync(join(OUT, `${name}.png`), buf);
  brand[name] = { width: m.width, height: m.height };
  console.log(name, `${m.width}×${m.height}`);
  return buf;
}

const light = unmix(false);
const dark = unmix(true);
await save('usa', light);
await save('usa-reverse', dark);
const markBox = { left: 0, top: 0, width: markEnd, height: H };
const wordBox = { left: wordStart, top: 0, width: W - wordStart, height: H };
const mark = await save('usa-mark', light, markBox);
await save('usa-mark-reverse', dark, markBox);
await save('usa-word-reverse', dark, wordBox);

// Iconos: la marca centrada sobre blanco.
async function icon(size, file, pad = 0.14) {
  const inner = Math.round(size * (1 - pad * 2));
  const m = await sharp(mark).resize({ width: inner, height: inner, fit: 'inside' }).toBuffer();
  await sharp({ create: { width: size, height: size, channels: 4, background: '#ffffff' } })
    .composite([{ input: m, gravity: 'center' }])
    .png({ compressionLevel: 9 })
    .toFile(join(root, 'public', file));
}
await icon(32, 'favicon-32.png', 0.06);
await icon(180, 'apple-touch-icon.png');
await icon(192, 'icon-192.png');
await icon(512, 'icon-512.png');

// Logotipo de Morano Gruppo (apilado, el mismo de moranogruppo.com) para el pie.
for (const n of ['gruppo-v', 'gruppo-v-reverse']) {
  const m = await sharp(join(OUT, `${n}.png`)).metadata();
  brand[n] = { width: m.width, height: m.height };
}

writeFileSync(join(root, 'src', 'data', 'brand.json'), JSON.stringify(brand, null, 2) + '\n');
console.log('→ public/brand, iconos y src/data/brand.json');
