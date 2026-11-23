// Datos generales y textos de moranousa.com. Los textos son los de la web actual
// del cliente (en inglés, mercado de EE. UU.); no se inventan cifras ni certificados.
export const SITE = {
  url: 'https://moranousa.com',
  nombre: 'Morano USA',
  razonSocial: 'Morano USA, LLC',
  lema: 'From the Palm to the World.',
  descripcion: 'The U.S. commercial gateway to Morano Gruppo for palm oils, vegetable fats and industrial ingredients.',
  email: 'sales@moranousa.com',
  // WhatsApp de la web actual (Joinchat).
  whatsapp: '573185162999',
  whatsappTexto: 'Hello Morano USA, I would like information about your products.',
  // Dirección de EE. UU. enviada por el cliente (2026-11-12) para Morano Gruppo.
  direccion: ['7901 4th St N, STE 300', 'St. Petersburg, FL 33702'],
  mapsUrl: 'https://www.google.com/maps/search/?api=1&query=7901+4th+St+N+STE+300+St.+Petersburg+FL+33702',
  /** Google Analytics: la web actual no tiene; si el cliente da un ID, va aquí. */
  gaId: '',
  gaHost: 'moranousa.com',
  terminos: '/commercial-sales-terms-and-conditions/',
  terminosPdf: '/docs/morano-usa-commercial-sales-terms-and-conditions.pdf',
  gruppo: [
    { nombre: 'Morano Gruppo', url: 'https://moranogruppo.com/' },
    { nombre: 'Morano Europe', url: 'https://moranoeurope.com/' },
  ],
};

export const NAV = [
  { href: '/#about', label: 'About' },
  { href: '/#products', label: 'Products' },
  { href: '/#supply-chain', label: 'Supply Chain' },
  { href: '/#private-label', label: 'Private Label' },
  { href: '/#industries', label: 'Industries' },
  { href: '/#sustainability', label: 'Sustainability' },
];

export const HOME = {
  heroLabel: 'Your U.S. Commercial Gateway to Morano Gruppo',
  heroTitulo: ['From the Palm', 'to the U.S. Market.'],
  heroTexto: 'Palm oils, vegetable fats and industrial ingredients backed by an integrated agro-industrial supply chain from Colombia to the United States.',

  about:
    'Morano USA, LLC is the U.S. commercial platform of Morano Gruppo, connecting the Group’s palm oil, vegetable fats and derivative production capabilities in Colombia with customers throughout the United States and international markets.',
  produced: 'Produced through our affiliated industrial operations in Colombia.',
  commercialized: 'Commercialized through Morano USA in the United States.',

  chain: [
    { titulo: 'Palm', texto: 'Integrated agricultural origin in Colombia.', foto: 'cultivo', alt: 'Oil palm plantation in Colombia with harvested fruit bunches' },
    { titulo: 'Extraction & Refining', texto: 'Oils and fractions produced through affiliated operations.', foto: 'refineria', alt: 'Refining equipment at an affiliated plant in Colombia' },
    { titulo: 'Packaging', texto: 'Retail, foodservice, industrial and private-label formats.', foto: 'envasado', alt: 'Edible oil filling line' },
    { titulo: 'International Trade', texto: 'Commercial coordination from Colombia to the U.S. market.', foto: 'exportacion', alt: 'Packaged product loaded for export' },
  ],

  productsIntro: 'A modular portfolio designed for manufacturers, distributors, wholesalers, foodservice operators and industrial buyers.',
  products: [
    {
      titulo: 'Crude & Refined Palm Oils',
      texto: 'CPO, CPKO, RBD Palm Oil and RBD Palm Olein for industrial applications and further processing.',
      foto: 'p-aceites',
      alt: 'Refined palm oil in a laboratory flask with palm fruit',
      interes: 'RBD Palm Oil',
    },
    {
      titulo: 'Vegetable Fats',
      texto: 'Palm stearin, shortening, bakery fats and margarine solutions for food manufacturing.',
      foto: 'p-grasas',
      alt: 'Blocks of vegetable shortening',
      interes: 'Vegetable Shortening',
    },
    {
      titulo: 'Packaged & Private Label',
      texto: 'Flexible edible-oil packaging programs for distributors, foodservice and retail markets.',
      foto: 'p-marca-propia',
      alt: 'Edible oil bottles on a filling line',
      interes: 'Private Label',
    },
  ],

  privateLabel: 'From product selection and packaging configuration to label development, export logistics and U.S. commercial distribution.',
  privateSteps: ['Product selection', 'Packaging configuration', 'Production & packaging', 'Export logistics'],

  industries: [
    'Food Manufacturing',
    'Bakery & Confectionery',
    'Frying & Foodservice',
    'Retail & Wholesale',
    'Oleochemical',
    'Personal Care & Cosmetics',
    'Soap & Detergents',
    'Industrial Formulations',
  ],

  formats: [
    { titulo: 'Packaged Products', texto: 'Bottles and commercial presentations.' },
    { titulo: 'Drums', texto: 'Flexible industrial customer supply.' },
    { titulo: 'Flexitank & Isotank', texto: 'Efficient international liquid-bulk logistics.' },
    { titulo: 'Bulk', texto: 'Large-volume industrial supply.' },
  ],

  sustainability:
    'Our approach centers on responsible sourcing, traceability, circular-economy principles, biomass utilization and resource efficiency.',
  sustainabilityNote: 'Certification and regulatory documentation is provided only for the applicable entity, operation and product scope.',
  sustainabilityAxes: ['Responsible sourcing', 'Traceability', 'Circular economy', 'Biomass utilization', 'Resource efficiency'],

  quoteIntro: 'Tell our U.S. commercial team what you need. We’ll follow up regarding product information, packaging and commercial terms.',
  productOptions: ['Crude Palm Oil (CPO)', 'Crude Palm Kernel Oil (CPKO)', 'RBD Palm Oil', 'RBD Palm Olein', 'RBD Palm Stearin', 'Vegetable Shortening', 'Packaged Edible Oil', 'Private Label', 'Industrial Ingredients', 'Other'],
  packagingOptions: ['Bottles', 'Jugs', 'Drums', 'Flexitank', 'Isotank', 'Bulk', 'Other'],
};
