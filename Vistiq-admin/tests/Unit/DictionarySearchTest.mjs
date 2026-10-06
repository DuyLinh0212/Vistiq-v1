import { test } from 'node:test';
import assert from 'node:assert/strict';
import { initializeDictionarySearch } from '../../resources/js/dictionary-search.js';

class Element {
    constructor() {
        this.listeners = new Map();
        this.attributes = new Map();
        this.children = [];
        this.textContent = '';
        this.value = '';
        this.hidden = false;
    }
    addEventListener(name, callback) { this.listeners.set(name, callback); }
    emit(name, event = {}) { return this.listeners.get(name)?.(event); }
    setAttribute(name, value) { this.attributes.set(name, value); }
    removeAttribute(name) { this.attributes.delete(name); }
    append(...elements) { this.children.push(...elements); }
    replaceChildren(...elements) { this.children = elements; }
    scrollIntoView(options) { this.scrollOptions = options; }
    focus() { this.focused = true; }
}

function harness(request) {
    const input = new Element();
    const list = new Element();
    const filter = new Element();
    const status = new Element();
    const submit = new Element();
    const clear = new Element();
    const form = new Element();
    const windowListeners = new Map();
    list.hidden = true;
    filter.value = 'all';
    form.dataset = { suggestionsUrl: '/api/v1/dictionary/suggestions' };
    form.submissions = 0;
    form.requestSubmit = () => { form.submissions += 1; form.emit('submit'); };
    form.querySelector = (selector) => ({
        '[data-dictionary-input]': input,
        '[data-dictionary-suggestions]': list,
        '[data-dictionary-status]': status,
        'select[name="type"]': filter,
        'button[type="submit"]': submit,
        '[data-dictionary-clear]': clear,
    })[selector];
    const root = {
        querySelectorAll: (selector) => selector === '[data-dictionary-search]' ? [form] : [],
        createElement: () => new Element(),
    };
    globalThis.window = {
        location: { origin: 'http://localhost' },
        addEventListener: (name, callback) => windowListeners.set(name, callback),
    };
    initializeDictionarySearch(root, request);
    return { input, list, filter, status, submit, clear, form, windowListeners };
}

const response = (data) => ({ ok: true, json: async () => ({ data }) });
const words = [
    { term: 'achieve', type_label: 'Từ vựng' },
    { term: 'achievement', type_label: 'Từ vựng' },
];
const keyEvent = (key) => ({ key, prevented: false, preventDefault() { this.prevented = true; } });

test('the clear button empties the query, closes suggestions and restores keyboard focus', async () => {
    const ui = harness(async () => response(words));
    ui.input.value = 'achieve';
    await ui.filter.emit('change');
    assert.equal(ui.list.hidden, false);
    ui.clear.emit('click');
    assert.equal(ui.input.value, '');
    assert.equal(ui.input.focused, true);
    assert.equal(ui.list.hidden, true);
    assert.equal(ui.input.attributes.get('aria-expanded'), 'false');
    assert.equal(ui.form.submissions, 0);
});

test('typing debounces requests and includes the current content filter', async (context) => {
    context.mock.timers.enable({ apis: ['setTimeout'] });
    const calls = [];
    const ui = harness(async (url) => { calls.push(url); return response(words); });
    ui.filter.value = 'vocabulary';
    ui.input.value = 'ach';
    ui.input.emit('input');
    ui.input.value = 'achie';
    ui.input.emit('input');
    context.mock.timers.tick(249);
    assert.equal(calls.length, 0);
    context.mock.timers.tick(1);
    await new Promise(setImmediate);
    assert.equal(calls.length, 1);
    assert.equal(calls[0].searchParams.get('q'), 'achie');
    assert.equal(calls[0].searchParams.get('type'), 'vocabulary');
    assert.equal(ui.list.children.length, 2);
});

test('an older response cannot replace suggestions for a newer query', async () => {
    const pending = [];
    const ui = harness(() => new Promise((resolve) => pending.push(resolve)));
    ui.input.value = 'ach';
    const first = ui.filter.emit('change');
    ui.input.value = 'beaut';
    const second = ui.filter.emit('change');
    pending[1](response([{ term: 'beautiful', type_label: 'Từ vựng' }]));
    await second;
    pending[0](response(words));
    await first;
    assert.equal(ui.list.children[0].children[0].textContent, 'beautiful');
});

test('arrow up selects the last suggestion and Enter submits the chosen term', async () => {
    const ui = harness(async () => response(words));
    ui.input.value = 'ach';
    await ui.filter.emit('change');
    const up = keyEvent('ArrowUp');
    ui.input.emit('keydown', up);
    assert.equal(up.prevented, true);
    assert.equal(ui.list.children[1].attributes.get('aria-selected'), 'true');
    assert.equal(ui.input.attributes.get('aria-activedescendant'), 'dictionary-suggestion-1');
    assert.deepEqual(ui.list.children[1].scrollOptions, { block: 'nearest' });
    ui.input.emit('keydown', keyEvent('Enter'));
    assert.equal(ui.input.value, 'achievement');
    assert.equal(ui.form.submissions, 1);
    assert.equal(ui.list.hidden, true);
});

test('arrow down selects the first suggestion and Escape closes it', async () => {
    const ui = harness(async () => response(words));
    ui.input.value = 'ach';
    await ui.filter.emit('change');
    ui.input.emit('keydown', keyEvent('ArrowDown'));
    assert.equal(ui.list.children[0].attributes.get('aria-selected'), 'true');
    ui.input.emit('keydown', keyEvent('Escape'));
    assert.equal(ui.list.hidden, true);
    assert.equal(ui.input.attributes.get('aria-expanded'), 'false');
    assert.equal(ui.input.attributes.has('aria-activedescendant'), false);
});

test('clearing input prevents a late response from reopening suggestions', async (context) => {
    context.mock.timers.enable({ apis: ['setTimeout'] });
    let resolve;
    const ui = harness(() => new Promise((done) => { resolve = done; }));
    ui.input.value = 'ach';
    const pending = ui.filter.emit('change');
    ui.input.value = '';
    ui.input.emit('input');
    resolve(response(words));
    await pending;
    context.mock.timers.tick(250);
    assert.equal(ui.list.hidden, true);
    assert.equal(ui.status.textContent, '');
});

test('failed suggestions retain the normal search form and show useful feedback', async () => {
    const ui = harness(async () => ({ ok: false }));
    ui.input.value = 'ach';
    await ui.filter.emit('change');
    assert.match(ui.status.textContent, /vẫn có thể nhấn Tra cứu/);
    assert.equal(ui.list.hidden, true);
    assert.equal(ui.input.attributes.has('aria-busy'), false);
    ui.form.requestSubmit();
    assert.equal(ui.form.submissions, 1);
    assert.equal(ui.submit.textContent, 'Đang tra cứu…');
    ui.windowListeners.get('pageshow')();
    assert.equal(ui.submit.textContent, 'Tra cứu');
    assert.equal(ui.form.attributes.has('aria-busy'), false);
});

test('empty suggestions allow Enter to continue normal search', async () => {
    const ui = harness(async () => response([]));
    ui.input.value = 'unknown';
    await ui.filter.emit('change');
    const enter = keyEvent('Enter');
    ui.input.emit('keydown', enter);
    assert.equal(enter.prevented, false);
    assert.match(ui.status.textContent, /Nhấn Enter/);
});

test('suggestion labels render imported markup as plain text and clicks select a term', async () => {
    const unsafeTerm = '<img src=x onerror=alert(1)>';
    const ui = harness(async () => response([{ term: unsafeTerm, type_label: 'Từ vựng' }]));
    ui.input.value = 'img';
    await ui.filter.emit('change');
    assert.equal(ui.list.children[0].children[0].textContent, unsafeTerm);
    assert.equal(ui.list.children[0].children[0].children.length, 0);
    ui.list.children[0].emit('click');
    assert.equal(ui.input.value, unsafeTerm);
    assert.equal(ui.form.submissions, 1);
});

test('browser pronunciation uses an English voice and reports completion and errors', () => {
    const button = new Element();
    const status = new Element();
    const spoken = [];
    let cancellations = 0;
    button.hidden = true;
    button.dataset = { pronounce: 'achieve' };
    button.parentElement = { querySelector: () => status };
    const root = {
        querySelectorAll: (selector) => selector === '[data-pronounce]' ? [button] : [],
    };
    globalThis.window = {
        SpeechSynthesisUtterance: class { constructor(text) { this.text = text; } },
        speechSynthesis: {
            cancel: () => { cancellations += 1; },
            speak: (utterance) => spoken.push(utterance),
        },
    };

    initializeDictionarySearch(root);
    button.emit('click');

    assert.equal(button.hidden, false);
    assert.equal(cancellations, 1);
    assert.equal(spoken[0].text, 'achieve');
    assert.equal(spoken[0].lang, 'en-US');
    assert.equal(spoken[0].rate, 0.85);
    assert.match(status.textContent, /giọng đọc của trình duyệt/);
    spoken[0].onend();
    assert.equal(status.textContent, '');
    button.emit('click');
    spoken[1].onerror();
    assert.match(status.textContent, /Hãy thử lại/);
});

test('unsupported browsers keep the pronunciation control hidden', () => {
    const button = new Element();
    button.hidden = true;
    const root = { querySelectorAll: (selector) => selector === '[data-pronounce]' ? [button] : [] };
    globalThis.window = {};

    initializeDictionarySearch(root);

    assert.equal(button.hidden, true);
});
