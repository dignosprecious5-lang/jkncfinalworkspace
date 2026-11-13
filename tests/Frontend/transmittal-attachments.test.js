// Prepared source only. This flag must be set by a separately approved isolated launcher.
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';

if (process.env.ORDO_ATTACHMENT_SYNTHETIC_TESTS !== 'approved-isolated-runtime') {
    throw new Error('Attachment frontend tests are not execution-approved in the host worktree.');
}
const view = readFileSync(new URL('../../resources/views/transmittal/index.blade.php', import.meta.url), 'utf8');
const start = view.indexOf('function prepareTransmittalUploads(');
const end = view.indexOf('async function submitTransmittal(', start);
assert.ok(start >= 0 && end > start, 'The actual V1 attachment function boundaries must exist.');
const source = view.slice(start, end);

class SyntheticFile {
    constructor(size) { this.size = size; }
}

function context(fetchResult = async () => ({ ok: true, redirected: false, json: async () => ({ id: 1, message: 'Saved' }) })) {
    const state = { previewItems: [], extraAttachments: [] };
    const calls = [];
    const alerts = [];
    const button = { disabled: false, textContent: 'Save' };
    class SyntheticFormData {
        constructor() { this.parts = []; }
        append(key, value) { this.parts.push([key, value]); }
    }
    const sandbox = vm.createContext({
        File: SyntheticFile, FormData: SyntheticFormData,
        transmittalAttachmentLimits: { max_files: 50, max_total_bytes: 104857600, item_max_kib: 10240, supporting_max_kib: 20480 },
        getAlpineData: () => state, document: { getElementById: () => button },
        alert: message => alerts.push(message), window: { confirm: () => false },
        fetch: async (url, options) => { calls.push({ url, options }); return fetchResult(); },
        closeAddSection() {}, renderTable: async () => {}, currentPage: 1, currentWorkflowFilter: 'uploaded',
    });
    vm.runInContext('let transmittalSaveInFlight = false; let transmittalSaveNeedsReview = false;\n' + source, sandbox);
    return { sandbox, state, calls, alerts, button };
}

test('50 synthetic files at exactly 100 MiB are accepted without reading file contents', () => {
    const { sandbox, state } = context();
    state.extraAttachments = Array.from({ length: 50 }, () => new SyntheticFile(2 * 1024 * 1024));
    assert.equal(sandbox.prepareTransmittalUploads(state).files.length, 50);
    state.extraAttachments.push(new SyntheticFile(1));
    assert.throws(() => sandbox.prepareTransmittalUploads(state), /50 files/);
    state.extraAttachments.pop();
    state.extraAttachments[0] = new SyntheticFile(2 * 1024 * 1024 + 1);
    assert.throws(() => sandbox.prepareTransmittalUploads(state), /100 MiB/);
});

test('per-file limits and original sparse item positions are retained', () => {
    const { sandbox, state } = context();
    const original = new SyntheticFile(10 * 1024 * 1024);
    state.previewItems = [{ file: null }, { file: null }, { file: original }];
    state.extraAttachments = [new SyntheticFile(20 * 1024 * 1024)];
    const result = sandbox.prepareTransmittalUploads(state);
    assert.equal(result.manifest.item_files[0].index, 2);
    assert.equal(result.files[0].file, original);
    state.previewItems[2].file = new SyntheticFile(10 * 1024 * 1024 + 1);
    assert.throws(() => sandbox.prepareTransmittalUploads(state), /10 MiB/);
});

test('a second Save cannot send while the first request is pending', async () => {
    let finish;
    const result = context(() => new Promise(resolve => { finish = resolve; }));
    const first = result.sandbox.saveTransmittal();
    assert.equal(result.button.disabled, true);
    await result.sandbox.saveTransmittal();
    assert.equal(result.calls.length, 1);
    const manifestPart = result.calls[0].options.body.parts.find(([key]) => key === 'upload_manifest');
    assert.equal(JSON.parse(manifestPart[1]).version, 1);
    finish({ ok: true, redirected: false, json: async () => ({ id: 1, message: 'Saved' }) });
    await first;
    assert.equal(result.button.disabled, false);
});

test('a network failure retains files and prevents an unconfirmed retry', async () => {
    const result = context(async () => { throw new TypeError('Synthetic network failure'); });
    const original = new SyntheticFile(1024);
    result.state.extraAttachments = [original];
    await result.sandbox.saveTransmittal();
    await result.sandbox.saveTransmittal();
    assert.equal(result.calls.length, 1);
    assert.equal(result.state.extraAttachments[0], original);
    assert.ok(result.alerts.some(message => message.includes('outcome is unknown')));
});

test('an upstream non-JSON 413 is handled without rendering response HTML', async () => {
    const result = context(async () => ({ ok: false, status: 413, redirected: false, json: async () => { throw new Error('<html>upstream</html>'); } }));
    await result.sandbox.saveTransmittal();
    assert.equal(result.button.disabled, false);
    assert.ok(result.alerts[0].includes('server rejected'));
    assert.ok(!result.alerts[0].includes('<html>'));
});
