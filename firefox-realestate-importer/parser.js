// Executed inside the Zonaprop tab by browser.scripting.executeScript.
// Keep this function self-contained: Firefox serializes it for injection.
function scrapeZonaprop(mode) {
    if (mode === 'auto') {
        const pathname = location.pathname;
        const isDetail = /^\/propiedades\/[^/]+/i.test(pathname)
            || (document.querySelector('h1.title-property')
                && document.querySelector('#longDescription, .section-location-property'));
        const isListing = document.querySelector('[data-qa="posting PROPERTY"][data-to-posting]');

        // Prefer the object page: related property cards may also appear below it.
        const detected = isDetail ? 'detail' : isListing ? 'listing' : null;
        if (!detected) {
            throw Error('Не удалось определить тип страницы Zonaprop. Проверьте, что объявления загрузились.');
        }

        return {mode: detected, data: scrapeZonaprop(detected)};
    }

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
    const detailGallery = () => {
        // Zonaprop's inline posting data contains the actual photo sequence.
        // DOM images only contain the hero and a handful of 360px previews,
        // while unrelated recommendation cards appear elsewhere on the page.
        for (const script of document.querySelectorAll('script:not([src])')) {
            const source = script.textContent || '';
            const matcher = /['"]pictures['"]\s*:\s*\[/g;
            let match;

            while ((match = matcher.exec(source))) {
                const start = source.indexOf('[', match.index);
                let depth = 0;
                let quoted = false;
                let escaped = false;
                let end = -1;

                for (let i = start; i < source.length; i++) {
                    const char = source[i];
                    if (quoted) {
                        if (escaped) escaped = false;
                        else if (char === '\\') escaped = true;
                        else if (char === '"') quoted = false;
                    } else if (char === '"') {
                        quoted = true;
                    } else if (char === '[') {
                        depth++;
                    } else if (char === ']' && --depth === 0) {
                        end = i + 1;
                        break;
                    }
                }

                if (end < 0) continue;

                let pictures;
                try {
                    pictures = JSON.parse(source.slice(start, end));
                } catch {
                    continue;
                }

                if (!Array.isArray(pictures) || !pictures.length) continue;

                const seen = new Set();
                const photos = pictures
                    .filter(item => Number(item?.multimediaTypeId) === 2)
                    .sort((a, b) => Number(a.order ?? 0) - Number(b.order ?? 0))
                    .map(item => {
                        // Prefer direct, non-resize CDN URLs. /avisos/resize/
                        // sometimes returns connection errors from the server.
                        const candidates = [
                            item.url1200x1200, item.resizeUrl1200x1200,
                            item.url730x532, item.resizeUrl720x532,
                            item.url360x266,
                        ];
                        return candidates.map(photoUrl).find(Boolean) || null;
                    })
                    .filter(link => {
                        if (!link) return false;
                        const key = link.split('/').at(-1);
                        if (seen.has(key)) return false;
                        seen.add(key);
                        return true;
                    });

                if (photos.length) return {photos, complete: true};
            }
        }

        // Fallback only to the actual property's gallery, never all images
        // on the page: recommendations belong to other properties.
        const primary = [...document.querySelectorAll(
            '[class*="imageGrid-module__imgProperty"], [data-qa="PROPERTY_GALLERY"] img'
        )];

        const original = images({querySelectorAll: () => primary});
        const promoted = original.map(link => {
            const large = link.replace(/\/avisos\/resize\//, '/avisos/')
                .replace(/\/(?:100x75|215x159|360x266|720x532)\//, '/1200x1200/');
            return photoUrl(large);
        }).filter(Boolean);

        if (promoted.length) {
            return {photos: promoted, complete: false};
        }

        const og = document.querySelector('meta[property="og:image"]')?.getAttribute('content');
        const fallback = photoUrl(og);
        return {photos: fallback ? [fallback] : [], complete: false};
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
    const locationFromPageData = () => {
        // Zonaprop embeds a typed location tree (BARRIO -> CIUDAD -> PAIS)
        // in an inline script. It is more reliable than the address label.
        for (const script of document.querySelectorAll('script:not([src])')) {
            const source = script.textContent || '';
            if (! source.includes('locationId')) continue;

            const matcher = /['"]location['"]\s*:\s*\{/g;
            let match;

            while ((match = matcher.exec(source))) {
                const start = match.index + match[0].length - 1;
                let depth = 0;
                let quoted = false;
                let escaped = false;
                let end = -1;

                for (let i = start; i < source.length; i++) {
                    const char = source[i];

                    if (quoted) {
                        if (escaped) escaped = false;
                        else if (char === '\\') escaped = true;
                        else if (char === '"') quoted = false;
                    } else if (char === '"') {
                        quoted = true;
                    } else if (char === '{') {
                        depth++;
                    } else if (char === '}' && --depth === 0) {
                        end = i + 1;
                        break;
                    }
                }

                if (end < 0) continue;

                let tree;
                try {
                    tree = JSON.parse(source.slice(start, end));
                } catch {
                    continue;
                }

                if (! tree?.locationId || ! tree?.label) continue;

                const result = {};
                for (let node = tree; node && typeof node === 'object'; node = node.parent) {
                    const name = clean(node.name);
                    if (! name) continue;

                    switch (clean(node.label).toUpperCase()) {
                        case 'PAIS': result.country = name; break;
                        case 'PROVINCIA': result.region = name; break;
                        case 'CIUDAD': result.city = name; break;
                        case 'LOCALIDAD':
                            result.city ||= name;
                            break;
                        case 'BARRIO': result.district = name; break;
                    }
                }

                // CABA is a city and a federal jurisdiction. Our DB has
                // separate region and city levels, so represent both.
                if (/^(capital federal|caba|ciudad aut[oó]noma de buenos aires)$/i.test(result.city || '')) {
                    result.region = 'Capital Federal';
                    result.city = 'Buenos Aires';
                }

                if (result.country && result.city) return result;
            }
        }

        return null;
    };

    const addressParts = text => {
        const parts = clean(text).split(/\s*,\s*/).filter(Boolean);
        const last = clean(parts.at(-1)).toLowerCase();

        if (/^(capital federal|caba|ciudad aut[oó]noma de buenos aires)$/i.test(last)) {
            return {
                address: parts.length >= 3 ? parts.slice(0, -2).join(', ') : null,
                region: 'Capital Federal',
                city: 'Buenos Aires',
                district: parts.length >= 2 ? parts.at(-2) : null,
            };
        }

        const isRegion = /buenos aires|costa atl[aá]ntica|gba|c[oó]rdoba|santa fe|mendoza|entre r[ií]os|neuqu[eé]n|r[ií]o negro|tucum[aá]n|salta|misiones|chubut|san juan|san luis|la pampa|corrientes|chaco|formosa|jujuy|catamarca|la rioja|santa cruz|tierra del fuego/i.test(last);
        return isRegion
            ? {address: parts.length >= 3 ? parts.slice(0, -2).join(', ') : null,
                city: parts.at(-2) || null, region: parts.at(-1) || null, district: null}
            : {address: parts.length >= 3 ? parts.slice(0, -2).join(', ') : null,
                city: parts.at(-1) || null, region: null,
                district: parts.length >= 2 ? parts.at(-2) : null};
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
                district: parts.district,
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
        const locationInfo = {
            ...addressParts(addressText),
            ...locationFromPageData(),
        };
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

        const gallery = detailGallery();
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
            photos: gallery.photos,
            photos_complete: gallery.complete,
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
