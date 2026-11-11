const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const parser = fs.readFileSync(path.join(__dirname, '..', 'parser.js'), 'utf8');
const propertyUrl = 'https://www.zonaprop.com.ar/propiedades/clasificado/test-58884569.html';
const cdn = 'https://imgar.zonapropcdn.com/avisos/1/00/58/88/45/69/';
const unrelated = 'https://imgar.zonapropcdn.com/avisos/1/00/60/64/32/28/360x266/2090606899.jpg';

// The uploaded Zonaprop HTML contains these 24 pictures in the embedded
// posting's "pictures" array. The DOM only exposes a few 360px thumbnails.
const filenames = [
    '2046926477.jpg', '2046926480.jpg', '2046926479.jpg',
    '2046926488.jpg', '2046926468.jpg', '2046926467.jpg',
    '2046926490.jpg', '2046926478.jpg', '2046926486.jpg',
    '2046926471.jpg', '2046926481.jpg', '2046926487.jpg',
    '2046926476.jpg', '2046926482.jpg', '2046926489.jpg',
    '2046926470.jpg', '2046926475.jpg', '2046926485.jpg',
    '2046926474.jpg', '2046926483.jpg', '2046926472.jpg',
    '2046926469.jpg', '2046926484.jpg', '2046926473.jpg',
];

function image(src) {
    return {
        getAttribute(key) {return key === 'src' ? src : null;},
    };
}

function scrape({embedded = true, gallery = []} = {}) {
    const pictures = filenames.map((name, order) => ({
        multimediaTypeId: 2,
        order,
        width: 1280,
        height: 960,
        resizeUrl1200x1200: cdn.replace('/avisos/', '/avisos/resize/') + '1200x1200/' + name,
        url1200x1200: cdn + '1200x1200/' + name,
        url360x266: cdn + '360x266/' + name,
    }));

    const scripts = embedded
        ? [{textContent: "const item = {'pictures': " + JSON.stringify(pictures) + '};'}]
        : [];

    const fields = {
        'h1.title-property, h1': {textContent: 'Piso Único 4 Ambientes'},
        '.title-type-sup-property': {textContent: 'Departamento'},
        '.section-location-property h4': {textContent: 'Paraguay 1800, Recoleta, Capital Federal'},
        '#longDescription, .article-section-description': {textContent: 'Apartamento en venta'},
    };

    const document = {
        querySelector(selector) {return fields[selector] || null;},
        querySelectorAll(selector) {
            if (selector === 'script:not([src])') return scripts;
            if (selector.includes('imageGrid-module__imgProperty')) return gallery;
            if (selector === 'img, source') return [...gallery, image(unrelated)];
            return [];
        },
    };

    return vm.runInNewContext(parser + "\nscrapeZonaprop('detail')", {
        document,
        location: new URL(propertyUrl),
        URL,
    });
}

test('extracts all 24 full-size property images in posting order', () => {
    const result = scrape({
        gallery: [image(cdn + '360x266/2046926477.jpg'), image(unrelated)],
    });

    assert.equal(result.photos_complete, true);
    assert.equal(result.photos.length, 24);
    assert.equal(result.photos[0], cdn + '1200x1200/2046926477.jpg');
    assert.equal(result.photos[1], cdn + '1200x1200/2046926480.jpg');
    assert.equal(result.photos[23], cdn + '1200x1200/2046926473.jpg');
    assert.ok(result.photos.every(link => link.includes('/1200x1200/')));
    assert.ok(result.photos.every(link => !link.includes('/avisos/resize/')));
    assert.ok(!result.photos.includes(unrelated));
});

test('fallback only reads this property gallery and promotes 360px previews', () => {
    const result = scrape({
        embedded: false,
        gallery: [
            image(cdn + '360x266/2046926477.jpg'),
            image(cdn + '360x266/2046926480.jpg'),
        ],
    });

    assert.equal(result.photos_complete, false);
    assert.equal(result.photos.length, 2);
    assert.equal(result.photos[0], cdn + '1200x1200/2046926477.jpg');
    assert.equal(result.photos[1], cdn + '1200x1200/2046926480.jpg');
    assert.ok(!result.photos.includes(unrelated));
});

test('unrelated recommendation images are never collected as property photos', () => {
    const result = scrape({embedded: false});

    assert.equal(result.photos_complete, false);
    assert.equal(result.photos.length, 0);
});
