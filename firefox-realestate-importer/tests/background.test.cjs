const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const background = fs.readFileSync(path.join(__dirname, '..', 'background.js'), 'utf8');
const endpoint = 'https://visitacaribe.com/api/realestate/import';
const item = {
    source_platform: 'zonaprop',
    source_url: 'https://www.zonaprop.com.ar/propiedades/casa-test-123.html',
    title: 'Casa de prueba',
};

function createBackground(fetchImpl, stored = {}) {
    const storage = {job: {}, items: [item], ...stored};
    let onMessage;

    const browser = {
        storage: {
            local: {
                async get(keys) {
                    keys = typeof keys === 'string' ? [keys] : keys;
                    return Object.fromEntries(keys.filter(key => Object.hasOwn(storage, key))
                        .map(key => [key, storage[key]]));
                },
                async set(values) {
                    Object.assign(storage, values);
                },
            },
        },
        runtime: {
            onMessage: {
                addListener(callback) {
                    onMessage = callback;
                },
            },
        },
        tabs: {},
    };

    const context = vm.createContext({
        browser,
        fetch: fetchImpl,
        AbortController,
        URL,
        FormData,
        Blob,
        setTimeout,
        clearTimeout,
        console,
    });

    vm.runInContext(background + '\nthis.testApi = {ready,process};', context);

    return {
        storage,
        controls: context.testApi,
        send: message => onMessage(message),
    };
}

test('Stop aborts a pending Laravel request and unlocks the interface', async () => {
    let requestStarted;
    const started = new Promise(resolve => { requestStarted = resolve; });

    const app = createBackground((_endpoint, options) => new Promise((_resolve, reject) => {
        options.signal.addEventListener('abort', () => reject(new Error('Fetch aborted')), {once: true});
        requestStarted(options.signal);
    }));

    await app.controls.ready;
    const running = app.controls.process({type: 'upload', endpoint, token: 'test-token'});
    const signal = await started;

    assert.equal(app.storage.job.running, true);
    assert.equal((await app.send({type: 'cancel'})).ok, true);

    await running;
    assert.equal(signal.aborted, true);
    assert.equal(app.storage.job.running, false);
    assert.equal(app.storage.job.phase, 'canceled');
    assert.deepEqual(app.storage.items, [item]);
});

test('HTTP 422 field errors do not count as successful imports', async () => {
    const app = createBackground(async () => ({
        ok: false,
        status: 422,
        async json() {
            return {message: 'Invalid data', errors: {'items.0.price': ['Must be numeric']}};
        },
    }));

    await app.controls.ready;
    await app.controls.process({type: 'upload', endpoint, token: 'test-token'});

    assert.equal(app.storage.job.uploaded, 0);
    assert.equal(app.storage.job.phase, 'done_with_errors');
    assert.match(app.storage.job.errors[0].message, /items\.0\.price/);
});

test('Successful response is reported only when Laravel returns a saved item ID', async () => {
    const app = createBackground(async () => ({
        ok: true,
        status: 200,
        async json() {return {items: [{id: 321}], errors: []};},
    }));

    await app.controls.ready;
    await app.controls.process({type: 'upload', endpoint, token: 'test-token'});

    assert.equal(app.storage.job.phase, 'done');
    assert.equal(app.storage.job.uploaded, 1);
    assert.equal(app.storage.results[0].id, 321);
});

test('MV3 background restart clears stale running state without losing JSON', async () => {
    const app = createBackground(async () => {
        throw new Error('Unexpected network call');
    }, {job: {running: true, phase: 'upload', processed: 1, total: 5}});

    await app.controls.ready;

    assert.equal(app.storage.job.running, false);
    assert.equal(app.storage.job.phase, 'interrupted');
    assert.equal(app.storage.items[0].source_url, item.source_url);
});


test('failed CDN photo is skipped, next one uploads directly from Firefox', async () => {
    const first = 'https://imgar.zonapropcdn.com/avisos/1/00/58/88/45/69/1200x1200/2046926477.jpg';
    const second = 'https://imgar.zonapropcdn.com/avisos/1/00/58/88/45/69/1200x1200/2046926480.jpg';
    const requests = [];

    const app = createBackground(async (url, options) => {
        requests.push({url, options});
        if (url === endpoint) return {ok: true, status: 200, async json() {
            return {items: [{id: 321, photo_sources: []}], errors: []};
        }};
        if (url === first) throw new Error('CDN connection refused');
        if (url === second) return {
            ok: true,
            headers: {get(name) {return name === 'content-type' ? 'image/jpeg' : '4';}},
            async blob() {return new Blob(['test'], {type: 'image/jpeg'});},
        };
        if (url.endsWith('/api/realestate/import/photo')) {
            assert.equal(options.method, 'POST');
            assert.equal(options.body.get('realestate_id'), '321');
            assert.equal(options.body.get('source_url'), second);
            return {ok: true, status: 200, async json() {return {id: 123, skipped: false};}};
        }
        throw new Error('Unexpected URL ' + url);
    }, {items: [{...item, photos: [first, second]}]});

    await app.controls.ready;
    await app.controls.process({type: 'upload', endpoint, token: 'test-token'});
    assert.equal(app.storage.job.phase, 'done_with_errors');
    assert.equal(app.storage.job.uploaded, 1);
    assert.equal(app.storage.job.errors.length, 1);
    assert.equal(app.storage.results[0].photos_uploaded, 1);
    assert.equal(requests.length, 4);
});

test('already imported photo is not downloaded again', async () => {
    const photo = 'https://imgar.zonapropcdn.com/avisos/1/00/58/88/45/69/1200x1200/2046926477.jpg';
    const requests = [];

    const app = createBackground(async url => {
        requests.push(url);
        if (url !== endpoint) throw new Error('Photo must not be downloaded');
        return {ok: true, status: 200, async json() {
            return {items: [{
                id: 321,
                photo_sources: [{source_key: '2046926477.jpg', source_url: photo}],
            }], errors: []};
        }};
    }, {items: [{...item, photos: [photo]}]});

    await app.controls.ready;
    await app.controls.process({type: 'upload', endpoint, token: 'test-token'});
    assert.deepEqual(requests, [endpoint]);
    assert.equal(app.storage.job.phase, 'done');
    assert.equal(app.storage.results[0].photos_uploaded, 0);
});

test('Stop interrupts active CDN download without losing saved property', async () => {
    const photo = 'https://imgar.zonapropcdn.com/avisos/1/00/58/88/45/69/1200x1200/2046926477.jpg';
    let requestStarted;
    const started = new Promise(resolve => {requestStarted = resolve;});

    const app = createBackground(async (url, options) => {
        if (url === endpoint) return {ok: true, status: 200, async json() {
            return {items: [{id: 321, photo_sources: []}], errors: []};
        }};
        if (url !== photo) throw new Error('Unexpected URL ' + url);
        return new Promise((_resolve, reject) => {
            options.signal.addEventListener('abort', () => reject(new Error('Aborted')), {once: true});
            requestStarted(options.signal);
        });
    }, {items: [{...item, photos: [photo]}]});

    await app.controls.ready;
    const running = app.controls.process({type: 'upload', endpoint, token: 'test-token'});
    const signal = await started;
    assert.equal((await app.send({type: 'cancel'})).ok, true);
    await running;

    assert.equal(signal.aborted, true);
    assert.equal(app.storage.job.phase, 'canceled');
    assert.equal(app.storage.job.running, false);
    assert.equal(app.storage.results[0].id, 321);
});
