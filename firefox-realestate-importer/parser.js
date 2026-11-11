// Executed inside the Zonaprop tab by browser.scripting.executeScript.
// Keep this function self-contained: Firefox serializes it for injection.
function scrapeZonaprop(mode) {
    const clean = value => String(value || '').replace(/\s+/g, ' ').trim();
    const txt = (root, selector) => clean(root?.querySelector(selector)?.textContent);
    const number = value => {
        const found = clean(value).match(/[\d][\d.,]*/);
        if (! found) return null;
        let raw = found[0];
        if (raw.includes('.') && raw.includes(','))
            raw = raw.lastIndexOf(',') > raw.lastIndexOf('.')
                ? raw.replace(/\./g, '').replace(',', '.')
                : raw.replace(/,/g, '');
        else if (/^\d{1,3}(?:[.,]\d{3})+$/.test(raw))
            raw = raw.replace(/[.,]/g, '');
        else raw = raw.replace(',', '.');
        const n = Number(raw);
        return Number.isFinite(n) ? n : null;
    };
    const url = value => {
        try {
            const u = new URL(value, location.href);
            if (u.protocol !== 'https:' || ! u.hostname.endsWith('zonaprop.com.ar')) return null;
            return u.origin + u.pathname.replace(/\/$/, '');
        } catch {
            return null;
        }
    };
    const photoUrl = raw => {
        if (! raw) return null;
        try {
            const u = new URL(raw.replace(/\\\//g, '/'), location.href);
            if (u.protocol !== 'https:' || ! u.hostname.endsWith('.zonapropcdn.com')
                || ! u.pathname.includes('/avisos/') || ! /\.(jpe?g|png|webp)$/i.test(u.pathname)) return null;
            return u.origin + u.pathname;
        } catch {
            return null;
        }
    };
    const images = root => {
        const found = new Map();
        for (const el of root.querySelectorAll('img, source')) {
            const candidates = [
                el.getAttribute('data-flickity-lazyload'),
                el.getAttribute('data-src'),
                el.getAttribute('data-lazy'),
                el.getAttribute('src'),
                el.getAttribute('srcset')?.split(',').map(x => x.trim().split(/\s+/)[0]),
                el.getAttribute('data-srcset')?.split(',').map(x => x.trim().split(/\s+/)[0]),
            ].flat().filter(Boolean);
            for (const candidate of candidates) {
                const normal = photoUrl(candidate);
                if (! normal) continue;
                const key = normal.match(/\/([^/]+)\.(?:jpe?g|png|webp)$/i)?.[1] || normal;
                // Retain the largest available variant of each photo if the DOM has several sizes.
                const old = found.get(key);
                const size = candidate.match(/\/(\d+)x(\d+)\//);
                const oldSize = old?.match(/\/(\d+)x(\d+)\//);
                if (! old || (size && (+size[1] * +size[2]) > (oldSize ? +oldSize[1] * +oldSize[2] : 0)))
                    found.set(key, normal);
            }
        }
        return [...found.values()];
    };
    const kind = value => {
        const t = clean(value).toLowerCase();
        if (/casa|chalet|vivienda/.test(t)) return 'house';
        if (/departamento|apartamento|depto/.test(t)) return 'apartment';
        if (/terreno|lote/.test(t)) return 'land';
        if (/local|oficina|comercial/.test(t)) return 'commercial';
        if (/duplex|dúplex/.test(t)) return 'duplex';
        if (/ph\b/.test(t)) return 'townhouse';
        return null;
    };
    const transaction = value => /alquiler|alquilar|\/alquiler/i.test(value) ? 'rent'
        : /venta|vender|\/venta/i.test(value) ? 'sale' : null;
    const price = root => {
        const value = txt(root, '[data-qa="POSTING_CARD_PRICE"], .postingPrices-module__price, .price-value');
        const currency = /USD|U\$S/i.test(value) ? 'USD' : /EUR|€/i.test(value) ? 'EUR' : /\$/.test(value) ? 'ARS' : null;
        return {price: number(value), currency};
    };
    const metric = (el, name) => {
        const tag = [...el.querySelectorAll('li.icon-feature')]
            .find(node => node.querySelector('i')?.className.includes(name));
        return tag ? number(tag.textContent) : null;
    };
    const addressParts = text => {
        const parts = clean(text).split(/\s*,\s*/).filter(Boolean);
        // Detail page: street, city, region. Listing may only have city and region.
        return {
            address: parts.length >= 3 ? parts.slice(0, -2).join(', ') : null,
            city: parts.length >= 3 ? parts.at(-2) : parts.length === 2 ? parts[0] : parts[0] || null,
            region: parts.length >= 3 ? parts.at(-1) : parts.length === 2 ? parts[1] : null,
        };
    };
    if (mode === 'listing') {
        const cards = [...document.querySelectorAll('[data-qa="posting PROPERTY"][data-to-posting]')];
        const list = [];
        for (const card of cards) {
            const source_url = url(card.getAttribute('data-to-posting'));
            if (! source_url) continue;
            const photo = card.querySelector('.postingGallery-module__gallery-container img');
            const title = clean(photo?.getAttribute('alt')?.split('·').at(-1))
                || clean(source_url.split('/').at(-1).replace(/-\d+\.html$/, '').replace(/-/g, ' '));
            const details = txt(card, '[data-qa="POSTING_CARD_FEATURES"]');
            const locationText = txt(card, '[data-qa="POSTING_CARD_LOCATION"]');
            const parts = addressParts(locationText);
            // A listing may show district + city instead of city + region.
            const segments = clean(locationText).split(/\s*,\s*/).filter(Boolean);
            if (segments.length === 2 && !/buenos aires|costa atl[aá]ntica|provincia/i.test(segments[1])) {
                parts.city = segments[1];
                parts.region = null;
                parts.district = segments[0];
            }
            const priceInfo = price(card);
            list.push({
                source_platform: 'zonaprop',
                source_url,
                title,
                property_type: kind(title),
                operation: transaction(source_url + ' ' + title),
                price: priceInfo.price,
                currency: priceInfo.currency,
                expenses: number(txt(card, '[data-qa="expensas"]')) || null,
                expenses_currency: txt(card, '[data-qa="expensas"]').includes('$') ? 'ARS' : null,
                address: txt(card, '.postingLocations-module__location-address'),
                city: parts.city,
                region: parts.region,
                country: 'Argentina',
                // Do not assume "m² tot." is covered area: on Zonaprop it can mean lot.
                rooms: number(details.match(/[\d.,]+\s*amb/i)?.[0]),
                bedrooms: number(details.match(/[\d.,]+\s*dorm/i)?.[0]),
                bathrooms: number(details.match(/[\d.,]+\s*bañ/i)?.[0]),
                parking_spaces: number(details.match(/[\d.,]+\s*coch/i)?.[0]),
                photos: images(card),
            });
        }
        return {items: [...new Map(list.map(x => [x.source_url, x])).values()], page: location.href};
    }
    if (mode === 'detail') {
        const title = txt(document, 'h1.title-property, h1');
        const intro = txt(document, '.title-type-sup-property');
        const addressText = txt(document, '.section-location-property h4');
        const locationInfo = addressParts(addressText);
        const descNode = document.querySelector('#longDescription, .article-section-description');
        const description = descNode?.innerText?.trim() || descNode?.textContent?.trim() || '';
        const facts = [...document.querySelectorAll('[class*="generalFeaturesProperty-module__description-text"]')]
            .map(x => clean(x.textContent));
        const text = (description + '\n' + facts.join('\n'));
        const grab = pattern => number(text.match(pattern)?.[1]);
        const yes = pattern => pattern.test(text);
        const features = {};
        const featureRules = {
            pool: /\b(pileta|piscina)\b/i,
            heated_pool: /piscina climatizada|pileta climatizada/i,
            garden: /\b(jard[ií]n|parque)\b/i,
            terrace: /\bterraza\b/i,
            balcony: /\bbalc[oó]n\b/i,
            barbecue: /\bparrilla\b/i,
            laundry: /\blavadero\b/i,
            furnished: /\bamoblada|amueblada\b/i,
            air_conditioning: /aire acondicionado/i,
            heating: /calefacci[oó]n/i,
            underfloor_heating: /losa radiante/i,
            natural_gas: /gas natural/i,
            double_glazing: /\b(dvh|doble vidriado)\b/i,
            fireplace: /\bhogar|chimenea\b/i,
            irrigation: /\briego/i,
        };
        for (const [key, regexp] of Object.entries(featureRules)) if (yes(regexp)) features[key] = true;
        const amenities = {};
        if (yes(/(?:\bseguridad\b|\bvigilancia\b)/i)) amenities.security = true;
        if (yes(/co-?work/i)) amenities.coworking = true;
        if (yes(/pileta compartida|piscina com[uú]n/i)) amenities.shared_pool = true;
        if (yes(/agua corriente/i)) amenities.water = true;
        if (yes(/electricidad/i)) amenities.electricity = true;
        if (yes(/\bcloaca\b|\bcloacas\b/i)) amenities.sewer = true;

        const result = {
            source_platform: 'zonaprop',
            source_url: url(location.href),
            title,
            description,
            country: 'Argentina',
            ...locationInfo,
            property_type: kind(intro + ' ' + title),
            operation: transaction(title + ' ' + location.href),
            rooms: metric(document, 'icon-ambiente'),
            bedrooms: metric(document, 'icon-dormitorio'),
            bathrooms: metric(document, 'icon-bano'),
            toilets: metric(document, 'icon-toilete'),
            parking_spaces: metric(document, 'icon-cochera'),
            covered_area: metric(document, 'icon-scubierta')
                ?? grab(/Sup(?:erficie)?\.?\s*Cub(?:ierta)?\.?\s*:\s*([\d.,]+)/i),
            semi_covered_area: grab(/Sup(?:erficie)?\.?\s*Semi(?:cubierta)?\.?\s*:\s*([\d.,]+)/i),
            land_area: grab(/Sup(?:erficie)?\.?\s*(?:Lote|Terreno)\s*:\s*([\d.,]+)/i),
            land_front: grab(/Frente del terreno.*?:\s*([\d.,]+)/i),
            land_depth: grab(/Largo del terreno.*?:\s*([\d.,]+)/i),
            floors: grab(/Cantidad plantas\s*:\s*(\d+)/i),
            parking_type: /Cobertura cochera\s*:\s*Cubierta/i.test(text) ? 'covered'
                : /Cobertura cochera\s*:\s*Semicubierta/i.test(text) ? 'semi_covered' : null,
            condition: /a estrenar/i.test(intro + ' ' + text) ? 'new' : null,
            orientation: (() => {
                const match = intro.match(/\b(N|S|E|O|NE|NO|SE|SO)\b/);
                return {N:'north',S:'south',E:'east',O:'west',NE:'northeast',
                    NO:'northwest',SE:'southeast',SO:'southwest'}[match?.[0]] || null;
            })(),
            features,
            amenities,
            photos: images(document),
            ...price(document),
        };
        // The detail's "m² tot." is interpreted as a lot only for houses and land.
        if (result.land_area == null && ['house','land','townhouse'].includes(result.property_type))
            result.land_area = metric(document, 'icon-stotal');
        if (! result.title) throw Error('The detail page has no object title; possibly blocked');
        return result;
    }
    throw Error('Unsupported parsing mode');
}
