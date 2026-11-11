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
