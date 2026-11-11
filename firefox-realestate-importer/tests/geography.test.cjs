const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const parser = fs.readFileSync(path.join(__dirname, '..', 'parser.js'), 'utf8');
const detailUrl = 'https://www.zonaprop.com.ar/propiedades/clasificado/test-58884569.html';

function documentFor({tree = null, address = 'Paraguay al 1800, Recoleta, Capital Federal', listing = false} = {}) {
    const card = {
        getAttribute(key) {
            return key === 'data-to-posting' ? '/propiedades/clasificado/test-58884569.html' : null;
        },
        querySelector(selector) {
            if (selector === '[data-qa="POSTING_CARD_LOCATION"]') {
                return {textContent: 'Recoleta, Capital Federal'};
            }
            return null;
        },
        querySelectorAll() {return [];},
    };
    const elements = {
        'h1.title-property, h1': {textContent: 'Piso Único 4 Ambientes'},
        '.title-type-sup-property': {textContent: 'Departamento'},
        '.section-location-property h4': {textContent: address},
        '#longDescription, .article-section-description': {textContent: 'Departamento de prueba'},
    };

    return {
        querySelector(selector) {
            return listing
                ? (selector === '[data-qa="posting PROPERTY"][data-to-posting]' ? card : null)
                : (elements[selector] || null);
        },
        querySelectorAll(selector) {
            if (selector === 'script:not([src])') {
                return tree ? [{textContent: "const posting = {'location': " + JSON.stringify(tree) + ', other: true};'}] : [];
            }

            return listing && selector === '[data-qa="posting PROPERTY"][data-to-posting]'
                ? [card]
                : [];
        },
    };
}

function scrape(mode, opts = {}) {
    return vm.runInNewContext(parser + "\nscrapeZonaprop('" + mode + "')", {
        document: documentFor(opts),
        location: new URL(opts.listing ? 'https://www.zonaprop.com.ar/casas-venta-recoleta.html' : detailUrl),
        URL,
    });
}

const capital = {
    locationId: 'V1-C-1003675',
    name: 'Recoleta',
    label: 'BARRIO',
    parent: {
        locationId: 'V1-B-6',
        name: 'Capital Federal',
        label: 'CIUDAD',
        acronym: 'CABA',
        parent: {
            locationId: 'V1-A-1',
            name: 'Argentina',
            label: 'PAIS',
        },
    },
};

test('Recoleta is a district of Buenos Aires, not a city (embedded hierarchy)', () => {
    const result = scrape('detail', {tree: capital});
    assert.equal(result.country, 'Argentina');
    assert.equal(result.region, 'Capital Federal');
    assert.equal(result.city, 'Buenos Aires');
    assert.equal(result.district, 'Recoleta');
    assert.equal(result.address, 'Paraguay al 1800');
});

test('Capital Federal address fallback works without embedded hierarchy', () => {
    const result = scrape('detail');
    assert.equal(result.region, 'Capital Federal');
    assert.equal(result.city, 'Buenos Aires');
    assert.equal(result.district, 'Recoleta');
});

test('Listing card maps Recoleta under Buenos Aires', () => {
    const result = scrape('listing', {listing: true});
    assert.equal(result.items.length, 1);
    assert.equal(result.items[0].region, 'Capital Federal');
    assert.equal(result.items[0].city, 'Buenos Aires');
    assert.equal(result.items[0].district, 'Recoleta');
});

test('Province, city and barrio keep their own levels', () => {
    const location = {
        locationId: 'V1-C-99', name: 'Pinamar Norte', label: 'BARRIO',
        parent: {
            locationId: 'V1-B-88', name: 'Pinamar', label: 'CIUDAD',
            parent: {
                locationId: 'V1-P-1', name: 'Buenos Aires', label: 'PROVINCIA',
                parent: {locationId: 'V1-A-1', name: 'Argentina', label: 'PAIS'},
            },
        },
    };
    const result = scrape('detail', {tree: location, address: 'Florentino Ameghino 1000, Pinamar Norte, Pinamar'});
    assert.equal(result.region, 'Buenos Aires');
    assert.equal(result.city, 'Pinamar');
    assert.equal(result.district, 'Pinamar Norte');
});
