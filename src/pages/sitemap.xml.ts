import type { APIRoute } from 'astro';
import { SITE } from '../lib/site.ts';

export const GET: APIRoute = () => {
  const pages: [string, string][] = [
    ['/', '1.0'],
    [SITE.terminos, '0.4'],
  ];
  const body = `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${pages
    .map(([p, pr]) => `  <url><loc>${SITE.url}${p}</loc><priority>${pr}</priority></url>`)
    .join('\n')}\n</urlset>\n`;
  return new Response(body, { headers: { 'Content-Type': 'application/xml; charset=utf-8' } });
};
