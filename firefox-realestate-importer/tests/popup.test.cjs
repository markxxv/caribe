const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const popup = fs.readFileSync(path.join(__dirname, '..', 'popup.js'), 'utf8');

function openPopup(settings = {}) {
    const fields = {};
    for (const id of [
        'source', 'pageType', 'collect', 'collectSend', 'phase', 'current',
        'progress', 'count', 'errors', 'upload', 'download', 'cancel', 'endpoint',
        'token', 'panel',
    ]) {
        fields[id] = {
            value: '',
            textContent: '',
            disabled: false,
            hidden: false,
            max: 1,
            classList: {toggle() {}},
            addEventListener() {},
            focus() {this.focused = true;},
        };
    }
    const storage = {...settings};
    const messages = [];
    const browser = {
        tabs: {query: async () => []},
        storage: {
            local: {
                async get(keys) {
                    keys = Array.isArray(keys) ? keys : [keys];
                    return Object.fromEntries(keys.filter(key => Object.hasOwn(storage, key)).map(key => [key, storage[key]]));
                },
                async set(values) {Object.assign(storage, values);},
            },
            onChanged: {addListener() {}},
        },
        runtime: {sendMessage: async message => {messages.push(message);return {ok:true};}},
        permissions: {request: async () => true},
    };
    const context = vm.createContext({
        document: {getElementById: id => fields[id]},
        browser,
        URL,
        console,
        setTimeout() {},
    });
    vm.runInContext(popup + '\nthis.popupControls = {initialize,execute};', context);
    return {fields, storage, messages, controls: context.popupControls};
}

test('shows the missing source URL explicitly', async () => {
    const app = openPopup();
    await app.controls.initialize();
    await app.controls.execute('collect');
    assert.match(app.fields.errors.textContent, /Укажите URL страницы Zonaprop/);
    assert.match(app.fields.count.textContent, /Новый запуск не начался/);
    assert.equal(app.messages.length, 0);
});

test('missing endpoint is a validation error, not a URL constructor exception', async () => {
    const app = openPopup({items: [{source_url: 'https://www.zonaprop.com.ar/propiedades/test.html', title: 'Test'}]});
    await app.controls.initialize();
    await app.controls.execute('upload');
    assert.match(app.fields.errors.textContent, /Laravel API endpoint не заполнен/);
    assert.equal(app.messages.length, 0);
});

test('saved API endpoint and token survive collecting JSON only', async () => {
    const endpoint = 'https://visitacaribe.com/api/realestate/import';
    const token = 'sensitive-local-token';
    const sourceUrl = 'https://www.zonaprop.com.ar/propiedades/test.html';
    const app = openPopup({endpointUrl: endpoint, apiToken: token, sourceUrl});
    await app.controls.initialize();
    assert.equal(app.fields.endpoint.value, endpoint);
    assert.equal(app.fields.token.value, token);
    await app.controls.execute('collect');
    assert.equal(app.storage.endpointUrl, endpoint);
    assert.equal(app.storage.apiToken, token);
    assert.equal(app.messages.length, 1);
    assert.equal(app.messages[0].url, sourceUrl);
});

test('rejects invalid endpoints before requesting permission', async () => {
    const app = openPopup({
        endpointUrl: 'https://visitacaribe.com/api/missing',
        apiToken: 'a-token',
    });
    await app.controls.initialize();
    await app.controls.execute('upload');
    assert.match(app.fields.errors.textContent, /должен заканчиваться на/);
    assert.equal(app.messages.length, 0);
});
