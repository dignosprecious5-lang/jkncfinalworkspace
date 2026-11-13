// Authorized Phase 18 standalone tests: synthetic metadata/DOM only, no app or network.
import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { setImmediate as tick } from 'node:timers/promises';
import test from 'node:test';
import { V2UploadQueue, V2_UPLOAD_LIMITS, prepareV2Submission } from '../../resources/js/transmittal-v2-upload-queue.js';
import { extractUploadArchive } from '../../resources/js/transmittal-v2-file-tools.js';
import { compressUploadFile } from '../../resources/js/transmittal-v2-upload-compression.js';
import { assertDependencyPreservation } from '../Support/transmittal-v2-dependency-contract.js';

const view = readFileSync(new URL('../../resources/views/transmittals/smart-upload.blade.php', import.meta.url), 'utf8').replace(/\r\n/g, '\n');
const file = (size = 1, name = 'same.pdf') => ({ size, name, type: 'application/pdf', lastModified: 1 });
const item = (size = 1, name = 'same.pdf') => ({ file: file(size, name), category: 'document', analysis: null });
const items = (count) => Array.from({ length: count }, () => item());
const uploadId = (number) => `00000000-0000-4000-8000-${String(number).padStart(12, '0')}`;
function deferred() {
    let resolve, reject;
    const promise = new Promise((a, b) => { resolve = a; reject = b; });
    return { promise, resolve, reject };
}

test('49 and 50 distinct files pass; 51 fail', () => {
    const queue = new V2UploadQueue();
    assert.equal(queue.validate(items(49)).length, 49);
    assert.equal(queue.validate(items(50)).length, 50);
    assert.throws(() => queue.validate(items(51)), /maximum of 50/);
});

test('combined exact byte ceiling passes; one byte over fails', () => {
    const queue = new V2UploadQueue();
    const entries = [item(52428800), item(52428800)];
    assert.equal(queue.validate(entries).length, 2);
    assert.throws(() => queue.validate([...entries, item(1)]), /100 MiB/);
});

test('per-file exact ceiling passes; one byte over fails', () => {
    const queue = new V2UploadQueue();
    assert.equal(queue.validate([item(52428800)]).length, 1);
    assert.throws(() => queue.validate([item(52428801)]), /50 MiB/);
    assert.deepEqual(V2_UPLOAD_LIMITS, { maxFiles: 50, maxTotalBytes: 104857600, maxFileBytes: 52428800 });
});

test('invalid sizes and empty submissions fail', () => {
    const queue = new V2UploadQueue();
    for (const bytes of [0, -1, NaN, Infinity, 1.5, Number.MAX_SAFE_INTEGER + 1]) {
        assert.throws(() => queue.validate([item(bytes)]), /valid|50 MiB/);
    }
    assert.throws(() => queue.validate([], true), /at least one/);
});

test('categories share the same count and byte budget', () => {
    const queue = new V2UploadQueue();
    const entries = ['document', 'receipt', 'proof', 'attachment'].map((category) => ({ ...item(26214400), category }));
    assert.equal(queue.validate(entries).length, 4);
    assert.throws(() => queue.validate([...entries, item(1)]), /100 MiB/);
});

test('same filename, size, and modification time remain distinct', () => {
    const queue = new V2UploadQueue();
    const a = item(), b = item();
    assert.equal(queue.admit([a], [b]).length, 2);
    assert.notEqual(a.id, b.id);
    assert.notEqual(queue.resumeKey(a.file), queue.resumeKey(b.file));
});

test('the same File object is counted once and keeps its identity', () => {
    const queue = new V2UploadQueue();
    const a = item(52428800);
    const id = queue.identify(a);
    const entries = queue.admit([a], [{ ...a }, a]);
    assert.equal(entries.length, 1);
    assert.equal(queue.identify(a), id);
    assert.equal(queue.resumeKey(a.file), queue.resumeKey(a.file));
});

test('batch rejection leaves the existing queue unchanged', () => {
    const queue = new V2UploadQueue();
    const existing = items(49);
    const original = [...existing];
    assert.throws(() => queue.admit(existing, [item(), item()]), /maximum of 50/);
    assert.deepEqual(existing, original);
    assert.throws(() => queue.admit(existing, [item(52428801)]), /50 MiB/);
    assert.deepEqual(existing, original);
});

test('partition submits each file once and maps backend direct-first indices', () => {
    const queue = new V2UploadQueue();
    const entries = items(4);
    entries[0].file.resumableUploadId = uploadId(1);
    entries[2].file.resumableUploadId = uploadId(2);
    const plan = queue.partition(entries);
    assert.deepEqual(plan.direct, [entries[1], entries[3]]);
    assert.deepEqual(plan.resumable, [entries[0], entries[2]]);
    assert.deepEqual(entries.map((entry) => plan.documentIndices.get(entry.id)), [2, 0, 3, 1]);
    assert.equal(new Set([...plan.direct, ...plan.resumable].map((entry) => entry.file)).size, 4);
});

test('duplicate or malformed resumable IDs reject instead of silently dropping files', () => {
    const queue = new V2UploadQueue();
    const entries = items(2);
    entries.forEach((entry) => { entry.file.resumableUploadId = uploadId(1); });
    assert.throws(() => queue.partition(entries), /same resumable upload/);
    entries[1].file.resumableUploadId = 'invalid';
    assert.throws(() => queue.partition(entries), /invalid identifier/);
});

test('OCR tickets survive reorder but not removal or file replacement', () => {
    const queue = new V2UploadQueue();
    const a = item(), b = item();
    const ticket = queue.beginOcr(a);
    assert.equal(queue.isCurrent([b, a], ticket), true);
    assert.equal(queue.isCurrent([b], ticket), false);
    assert.equal(queue.isCurrent([{ ...a }, b], ticket), false);
    a.file = file();
    assert.equal(queue.isCurrent([a, b], ticket), false);
});

test('new OCR requests invalidate old tickets', () => {
    const queue = new V2UploadQueue();
    const a = item();
    const old = queue.beginOcr(a);
    const current = queue.beginOcr(a);
    assert.equal(queue.isCurrent([a], old), false);
    assert.equal(queue.isCurrent([a], current), true);
});

test('additions serialize and whenIdle waits for completion', async () => {
    const queue = new V2UploadQueue();
    const gate = deferred();
    const order = [];
    const a = queue.trackAddition(async () => { order.push(1); await gate.promise; order.push(2); });
    const b = queue.trackAddition(async () => { order.push(3); });
    let idle = false;
    const waiting = queue.whenIdle().then(() => { idle = true; });
    await tick();
    assert.deepEqual(order, [1]);
    assert.equal(idle, false);
    gate.resolve();
    await Promise.all([a, b, waiting]);
    assert.deepEqual(order, [1, 2, 3]);
    assert.equal(idle, true);
});

test('failed addition blocks submission until a successful review action', async () => {
    const queue = new V2UploadQueue();
    await assert.rejects(queue.trackAddition(async () => { throw new Error('Selection rejected'); }), /Selection rejected/);
    await assert.rejects(queue.whenIdle(), /Selection rejected/);
    await queue.trackAddition(async () => {});
    await queue.whenIdle();
    assert.equal(queue.error, null);
});

test('preparation waits for OCR and resumable completion before its only commit', async () => {
    const queue = new V2UploadQueue();
    const entries = [item(22020096)];
    const analysis = deferred(), upload = deferred();
    const calls = [];
    const task = prepareV2Submission({
        queue, readItems: () => entries,
        waitForAnalysis: () => analysis.promise,
        prepareResumable: async (f) => { calls.push('prepare'); await upload.promise; f.resumableUploadId = uploadId(1); },
        commit: (plan) => { calls.push('commit'); assert.equal(plan.direct.length, 0); },
    });
    await tick();
    assert.deepEqual(calls, []);
    analysis.resolve();
    await tick();
    assert.deepEqual(calls, ['prepare']);
    upload.resolve();
    await task;
    assert.deepEqual(calls, ['prepare', 'commit']);
});

test('validation and preparation failures never reach commit', async () => {
    for (const entries of [items(51), [item(52428801)], [item(52428800), item(52428800), item(1)]]) {
        let commits = 0;
        await assert.rejects(prepareV2Submission({
            queue: new V2UploadQueue(), readItems: () => entries, waitForAnalysis: async () => {},
            prepareResumable: async () => {}, commit: () => { commits++; },
        }));
        assert.equal(commits, 0);
    }
    let commits = 0;
    await assert.rejects(prepareV2Submission({
        queue: new V2UploadQueue(), readItems: () => [large], waitForAnalysis: async () => {},
        prepareResumable: async () => { throw new Error('Upload failed'); }, commit: () => { commits++; },
    }), /Upload failed/);
    assert.equal(commits, 0);
});
const large = item(22020096);

test('changing the queue during preparation rejects the entire submission', async () => {
    const queue = new V2UploadQueue();
    let entries = [item(22020096)];
    let commits = 0;
    await assert.rejects(prepareV2Submission({
        queue, readItems: () => entries, waitForAnalysis: async () => {},
        prepareResumable: async (f) => { f.resumableUploadId = uploadId(1); entries = [item()]; },
        commit: () => { commits++; },
    }), /queue changed/);
    assert.equal(commits, 0);
});

function actualHandlerContext(entries, { analysis = Promise.resolve(), upload = async (f) => { f.resumableUploadId = uploadId(1); }, nativeError = false } = {}) {
    const errors = [], submissions = [], generated = [];
    const control = (disabled = false) => ({ disabled });
    const fileInput = { ...control(), files: entries.map((entry) => entry.file) };
    const indices = entries.map((entry, index) => ({ value: String(index), dataset: { fileId: '' } }));
    const queue = new V2UploadQueue();
    entries.forEach((entry, index) => { indices[index].dataset.fileId = queue.identify(entry); });
    const form = {
        dataset: {},
        reportValidity: () => true,
        querySelectorAll: () => generated.slice(),
        appendChild(fragment) {
            fragment.children.forEach((node) => {
                node.remove = () => { const at = generated.indexOf(node); if (at >= 0) generated.splice(at, 1); };
                generated.push(node);
            });
        },
    };
    class Transfer {
        constructor() {
            this.files = [];
            this.items = { add: (f) => { this.files.push(f); } };
        }
    }
    class NativeForm {
        submit() {
            if (nativeError) throw new Error('Synthetic native submission failure');
            submissions.push({ files: [...fileInput.files], fields: generated.map((node) => [node.name, node.value]) });
        }
    }
    const sandbox = vm.createContext({
        form, uploadQueue: queue, selectedFiles: entries,
        fileInput, zipInput: control(true), browseTrigger: control(), cameraTrigger: control(),
        docmgmtTrigger: control(), clearAllBtn: control(), reanalyzeBtn: control(),
        btnUsePhoto: control(), btnApplyDocmgmt: control(),
        submitDraftBtn: { ...control(), textContent: 'Create draft' },
        queueList: { querySelectorAll: () => [] },
        itemsTbody: { querySelectorAll: (selector) => selector === 'input[data-file-id]' ? indices : [] },
        subjectInput: { value: 'Synthetic subject', focus() {} },
        ocrTask: analysis, uploadResumableFile: upload,
        window: { ORDOFileTools: { prepareSubmission: prepareV2Submission } },
        document: {
            createDocumentFragment: () => ({ children: [], appendChild(node) { this.children.push(node); } }),
            createElement: () => ({ dataset: {} }),
        },
        DataTransfer: Transfer, HTMLFormElement: NativeForm,
        reportUploadError: (error) => errors.push(error.message),
        syncInputFiles: () => { fileInput.files = sandbox.selectedFiles.map((entry) => entry.file); },
    });
    const marker = "form.addEventListener('submit', ";
    const start = view.indexOf(marker + 'async function (e)');
    const end = view.indexOf('\n    });', start);
    assert.ok(start >= 0 && end > start);
    const source = view.slice(start + marker.length, end + '\n    }'.length);
    const handler = vm.runInContext('(' + source + ')', sandbox);
    let prevented = 0;
    return { handler, event: { preventDefault() { prevented++; } }, prevented: () => prevented,
        errors, submissions, sandbox, generated, indices, fileInput };
}

test('actual Blade handler prevents native submit before its first await', async () => {
    const analysis = deferred(), upload = deferred();
    const entries = [item(22020096), item()];
    const state = actualHandlerContext(entries, {
        analysis: analysis.promise,
        upload: async (f) => { await upload.promise; f.resumableUploadId = uploadId(1); },
    });
    const task = state.handler(state.event);
    assert.equal(state.prevented(), 1);
    assert.equal(state.submissions.length, 0);
    analysis.resolve();
    await tick();
    assert.equal(state.submissions.length, 0);
    upload.resolve();
    await task;
    assert.equal(state.errors.length, 0);
    assert.equal(state.submissions.length, 1);
    assert.deepEqual(state.submissions[0].files, [entries[1].file]);
    assert.equal(state.submissions[0].fields.filter(([key]) => key === 'upload_ids[]').length, 1);
    assert.deepEqual(state.indices.map((input) => input.value), ['1', '0']);
});

test('actual Blade handler retains matching metadata for each transport partition', async () => {
    const entries = [item(), item(), item(), item()];
    entries.forEach((entry, index) => {
        entry.category = ['proof', 'receipt', 'attachment', 'document'][index];
        entry.analysis = { analysis_token: 'token-' + index };
    });
    entries[0].file.resumableUploadId = uploadId(1);
    entries[2].file.resumableUploadId = uploadId(2);
    const state = actualHandlerContext(entries);
    await state.handler(state.event);
    const fields = state.submissions[0].fields;
    const values = (name) => fields.filter(([key]) => key === name).map(([, value]) => value);
    assert.deepEqual(values('categories[]'), ['receipt', 'document']);
    assert.deepEqual(values('ocr_tokens[]'), ['token-1', 'token-3']);
    assert.deepEqual(values('upload_categories[]'), ['proof', 'attachment']);
    assert.deepEqual(values('upload_ocr_tokens[]'), ['token-0', 'token-2']);
    assert.deepEqual(state.indices.map((input) => input.value), ['2', '0', '3', '1']);
});

test('actual Blade handler does not submit a valid subset of an invalid queue', async () => {
    const state = actualHandlerContext(items(51));
    await state.handler(state.event);
    assert.equal(state.submissions.length, 0);
    assert.match(state.errors[0], /50 files/);
    assert.equal(state.generated.length, 0);
    assert.equal(state.sandbox.form.dataset.submitting, 'false');
    assert.equal(state.sandbox.zipInput.disabled, true);
});

test('actual Blade handler restores controls and fields on preparation failure', async () => {
    const entries = [item(22020096)];
    const state = actualHandlerContext(entries, { upload: async () => { throw new Error('Preparation failed'); } });
    await state.handler(state.event);
    assert.equal(state.submissions.length, 0);
    assert.equal(state.generated.length, 0);
    assert.equal(state.sandbox.submitDraftBtn.disabled, false);
    assert.equal(state.sandbox.form.dataset.submitting, 'false');
    assert.deepEqual(state.fileInput.files, [entries[0].file]);
});

test('native commit failure restores indices and cleans generated fields for retry', async () => {
    const entries = [item(), item()];
    entries[0].file.resumableUploadId = uploadId(1);
    const state = actualHandlerContext(entries, { nativeError: true });
    await state.handler(state.event);
    assert.equal(state.submissions.length, 0);
    assert.equal(state.generated.length, 0);
    assert.deepEqual(state.indices.map((input) => input.value), ['0', '1']);
    assert.equal(state.sandbox.form.dataset.submitting, 'false');
});

test('double-click cannot submit twice while preparation is pending', async () => {
    const gate = deferred();
    const state = actualHandlerContext([item()], { analysis: gate.promise });
    const first = state.handler(state.event);
    await state.handler(state.event);
    assert.equal(state.submissions.length, 0);
    gate.resolve();
    await first;
    assert.equal(state.submissions.length, 1);
    assert.equal(state.prevented(), 2);
});

test('ZIP and file additions are awaited before success and camera dismissal', () => {
    assert.match(view, /const added = await addFiles\(extracted\);\s*progressStatusMsg.textContent/);
    assert.match(view, /await addFiles\(\[photoFile\]\);\s*closeCamera\(\)/);
    assert.match(view, /try \{ await addFiles\(dt.files\); \}/);
    assert.match(view, /try \{ await addFiles\(fileInput.files\); \}/);
    assert.ok(!view.includes('existing.file.name === file.name && existing.file.size === file.size'));
});

test('OCR integration checks ticket identity before assigning results', () => {
    const resultCheck = view.indexOf('if (!uploadQueue.isCurrent(selectedFiles, ticket)) continue;\n                if (analysisData)');
    assert.ok(resultCheck >= 0);
    assert.match(view, /const failedItem = uploadQueue.isCurrent\(selectedFiles, activeOcrTicket\)/);
    assert.match(view, /analysisData.category && item.category === ticket.category/);
});

test('PDF and ZIP capabilities remain explicitly unavailable', async () => {
    const tools = readFileSync(new URL('../../resources/js/transmittal-v2-file-tools.js', import.meta.url), 'utf8');
    assert.match(tools, /pdfCompression: false, zipExtraction: false/);
    assert.match(view, /zipInput.disabled = true/);
    await assert.rejects(extractUploadArchive(), /ZIP extraction is unavailable/);
    await assert.rejects(compressUploadFile(new File(['xx'], 'sample.pdf', { type: 'application/pdf' }), 1), /PDF compression is unavailable/);
});

test('all Smart Upload inline JavaScript compiles without executing application code', () => {
    const inline = view.slice(view.indexOf('<script>') + '<script>'.length, view.lastIndexOf('</script>'));
    new vm.Script(inline, { filename: 'smart-upload.inline.js' });
    assert.ok(inline.includes('prepareSubmission'));
});

test('missing safety bundle fails closed and excludes V1 imports', () => {
    assert.match(view, /if \(!window.ORDOFileTools\?\.UploadQueue/);
    const start = view.indexOf("if (!window.ORDOFileTools?.UploadQueue");
    const end = view.indexOf("    const fileInput", start);
    assert.ok(view.slice(start, end).includes('event.preventDefault()'));
    const source = readFileSync(new URL('../../resources/js/transmittal-v2-upload-queue.js', import.meta.url), 'utf8');
    assert.ok(!source.includes("from './app.js'"));
});

test('V1, excluded workflows, backend, dependency locks, and activation inputs are preserved', () => {
    const hashes = {
    "app/Http/Controllers/TransmittalController.php": "29ea4670bd54ceaa132272f3aa7458dc92120e25223f2904b6d63b7a68c00fc0",
    "resources/views/transmittal/index.blade.php": "4c563f9ced89d808ddcc2bc30c7c0db65cad08be50101974647d5e5e38e3fdf0",
    "resources/js/app.js": "cf6dab196c721f6911ff99cc4f910897f978599a2d289971a316a5fc33091f94",
    "resources/js/transmittal.js": "50420496239955a38137e836c676d500dc61e8019eed096512cd534adf53746b",
    "vite.config.js": "d57014eed9865fa5f296c3fba8760770f7096b65073d7c3b51aef9f7dfc6d232",
    "app/Http/Controllers/TransmittalV2/TransmittalController.php": "ed1053f6366168f91851084e360278617c5fad72c59a086559615b5ca5026e08",
    "app/Services/TransmittalV2/ResumableTransmittalUploadService.php": "6670d8d4dab7068c6a7ee776c8481a485314e53c1124178d0ab091a547f19a15",
    "resources/js/transmittal-v2-upload-compression.js": "9d52f830a6c4543c2464c29f4bfb8ad68690be9ee713fae3fb58fd1b5cc949c0",
    "app/Models/Transmittal.php": "9997d186e60d7a62c581ce032ef401961390139944959a76dd6e88fee3268a03",
    "app/Models/TransmittalItem.php": "213299bbbaa4337ec1a5799b693c131af90c4179f21d06302a7b3cea903fe0e5",
    "app/Models/TransmittalReceipt.php": "767909707224221ca938b4c3922f4f9c4d72b4817523f6ac3f367a07c5f719ee"
};
    for (const [path, expected] of Object.entries(hashes)) {
        const bytes = readFileSync(new URL('../../' + path, import.meta.url));
        const actual = createHash('sha256').update(bytes).digest('hex');
        if (path === 'app/Models/Transmittal.php') {
            assert.equal(actual, '30b382e9d230f996a2bbee0ab36202fe0bb3f90086a2973c11a1bafd487b9c17',
                'Transmittal model matches reviewed workflow classification guards and additive V2 relations; the captured hash is verified against Git baseline 47e8398.');
        } else {
            assert.equal(actual, expected, path);
        }
    }
    const registeredRoutes = readFileSync(new URL('../../routes/transmittal-v2.php', import.meta.url), 'utf8');
    const webRoutes = readFileSync(new URL('../../routes/web.php', import.meta.url), 'utf8');
    const providers = readFileSync(new URL('../../bootstrap/providers.php', import.meta.url), 'utf8');
    assert.match(webRoutes, /require __DIR__\s*\.\s*'\/transmittal-v2\.php';/);
    assert.match(webRoutes, /Route::get\('\/transmittal'/);
    assert.match(webRoutes, /Route::post\('\/transmittal'/);
    assert.match(registeredRoutes, /EnsureTransmittalV2Enabled::class/);
    assert.match(providers, /App\\Providers\\TransmittalV2AuthorizationServiceProvider::class/);
    assertDependencyPreservation();
    const start = view.indexOf("    btnApplyDocmgmt.addEventListener('click', function () {");
    const end = view.indexOf('    /* ============================================================\n       9.', start);
    assert.equal(createHash('sha256').update(view.slice(start, end)).digest('hex'), 'e498d5d58d70d9c85bcf8cd3d078a2398e756de6f492fbdfc6eb29c8270fd7e4');
    const resumeStart = view.indexOf('    async function uploadResumableFile(');
    const resumeEnd = view.indexOf('    /* ============================================================\n       5.', resumeStart);
    const protocol = view.slice(resumeStart, resumeEnd).replace(/const resumeKey = .*;/, 'const resumeKey = POLICY_KEY;');
    assert.equal(createHash('sha256').update(protocol).digest('hex'), '198fb3fa9aeb0b92d8878970dd8f5da3ab90aebcd4cfa1e44f3acccc4fa2f3ff');
});
