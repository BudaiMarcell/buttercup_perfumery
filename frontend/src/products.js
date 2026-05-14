

import { getApiBase } from './config.js';

const PRICE_DIVISOR = 400;

const PALETTES = [
    {
        bgColor: 'linear-gradient(135deg,#c5c9b0,#9c9f88)',
        bottleColor: '#7a7d5e',
        liquidColor: '#5a5c3a',
        capColor: '#4a4c2a',
        color: '#9C9F88',
        icon: '🌿',
        notes: ['🌿 Vetiver', '🪵 Cedar', '🌱 Oakmoss'],
    },
    {
        bgColor: 'linear-gradient(135deg,#c8896a,#a4623e)',
        bottleColor: '#8c4a2a',
        liquidColor: '#7a3a1a',
        capColor: '#5a2a0a',
        color: '#A4623E',
        icon: '🌶',
        notes: ['🌶 Cardamom', '🍂 Labdanum', '🪨 Amber'],
    },
    {
        bgColor: 'linear-gradient(135deg,#e8d5b0,#cfb586)',
        bottleColor: '#b89560',
        liquidColor: '#a08040',
        capColor: '#7a6030',
        color: '#CFB586',
        icon: '🌼',
        notes: ['🌼 Mimosa', '🪷 Iris', '✨ Musk'],
    },
    {
        bgColor: 'linear-gradient(135deg,#f5f0e5,#ede5d0)',
        bottleColor: '#d5cdb8',
        liquidColor: '#c0b89a',
        capColor: '#a09878',
        color: '#F2EDDE',
        icon: '🌸',
        notes: ['🌸 Neroli', '🕊 Musk', '🌾 Rice'],
    },
];

const FAMILY_BY_GENDER = {
    male:   "Men's · Woody",
    female: "Women's · Floral",
    unisex: 'Unisex · Modern',
};

function paletteFor(id) {
    const safeId = Number(id) > 0 ? Number(id) : 1;
    return PALETTES[(safeId - 1) % PALETTES.length];
}

function shortNameOf(name) {
    if (!name) return '';
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w.charAt(0).toUpperCase())
        .join('');
}

function formatVolume(volumeMl) {
    if (!volumeMl) return '';
    return `${volumeMl} EDP`;
}

export function mapApiProduct(p) {
    const palette = paletteFor(p.id);
    const stock = Number(p.stock_quantity ?? 0);
    return {
        id:          p.id,
        slug:        p.slug,           // needed by the back-in-stock notify endpoint
        name:        p.name,
        shortName:   shortNameOf(p.name),
        family:      FAMILY_BY_GENDER[p.gender] ?? 'Unisex · Modern',
        desc:        p.description ?? '',
        price:       Math.max(1, Math.round(Number(p.price) / PRICE_DIVISOR)),
        volume:      formatVolume(p.volume_ml),
        stock,                          // raw count, for cart math
        inStock:     stock > 0,         // convenience boolean for the UI
        notes:       palette.notes,
        bgColor:     palette.bgColor,
        bottleColor: palette.bottleColor,
        liquidColor: palette.liquidColor,
        capColor:    palette.capColor,
        color:       palette.color,
        icon:        palette.icon,
    };
}

export async function fetchProducts() {
    try {
        const res = await fetch(`${getApiBase()}/products?per_page=100`, {
            headers: { Accept: 'application/json' },
        });
        if (!res.ok) {
            console.warn(`[products] API returned ${res.status}`);
            return [];
        }
        const json = await res.json();
        const items = Array.isArray(json) ? json : (json.data ?? []);
        return items.map(mapApiProduct);
    } catch (err) {
        console.warn('[products] fetch failed:', err?.message ?? err);
        return [];
    }
}

export default { fetchProducts, mapApiProduct };
