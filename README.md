# Morano USA — web corporativa

Rediseño de [moranousa.com](https://moranousa.com) con la misma información (en inglés, mercado de EE. UU.): Morano USA, LLC, la plataforma comercial de Morano Gruppo en Estados Unidos. Aceites de palma, grasas vegetales, marca propia (private label), cadena de suministro Colombia → EE. UU., industrias, formatos, sostenibilidad y solicitud de cotización. *From the Palm to the U.S. Market.*

Web **estática** (Astro): no necesita servidor ni base de datos. Se publica en Vercel o en cualquier hosting subiendo la carpeta `dist/`. Misma familia visual que [moranogruppo.com](https://moranogruppo.com) y [moranoeurope.com](https://moranoeurope.com).

## Cómo se edita

| Qué | Archivo |
| --- | --- |
| Todos los textos (portada, productos, industrias, formatos, formulario, contacto, menú) | [src/lib/site.ts](src/lib/site.ts) |
| Portada (orden y estructura de secciones) | [src/pages/index.astro](src/pages/index.astro) |
| Términos y condiciones comerciales (PDF) | `public/docs/` (lo copia `npm run images` desde el material) |
| Fotos | originales en `Desktop\MATERIAL-CLIENTES\morano-usa\web-actual` → registrar en `scripts/build-images.mjs` → `npm run images` |
| Logotipo (fondo claro, fondo oscuro, marca sola, iconos) | `_material/logo-morano-usa.png` → `npm run brand` |

## En local

```bash
npm install
npm run dev        # http://localhost:4321
npm run build      # genera dist/
npm run check      # revisa tipos
```

## Decisiones

- Colores del logotipo: azul marino `#002a54` y rojo `#c61e24` (solo como acento); esquinas rectas; marcos desplazados como motivo, igual que en las otras webs del grupo.
- Los textos son los de la web actual del cliente; no se inventan cifras, clientes ni certificados. El sello ISCC se muestra como en la web actual, con su aclaración: la documentación aplica solo a la entidad, operación y producto correspondientes.
- Fotos: la del puerto y las de producto son las de la web actual; el resto son fotos reales de Morano Gruppo en Colombia.
- El formulario de cotización tiene los mismos campos que la web actual y abre el correo con la solicitud redactada para sales@moranousa.com (la web no guarda datos). WhatsApp: el mismo número de la web actual.
- Dirección: 7901 4th St N, STE 300, St. Petersburg, FL 33702 (la que dio el cliente para Estados Unidos). La página vieja `/contact/` decía Doral, FL: confirmar con el cliente.
- Las páginas viejas (marcas de techos, cerámica y diseño, blog de prueba, política de privacidad de ejemplo) redirigen a la portada (`vercel.json`). `/commercial-sales-terms-and-conditions/` se mantiene.
- Google Analytics: la web actual no tiene; si el cliente da un ID, va en `SITE.gaId`.
