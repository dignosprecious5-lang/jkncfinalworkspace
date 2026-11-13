/**
 * V2 Smart Upload state helpers. No DOM, network, storage, or package dependencies.
 * A File object is an identity; matching names/sizes never establish duplication.
 */
export const V2_UPLOAD_LIMITS = Object.freeze({
    maxFiles: 50,
    maxTotalBytes: 104857600,
    maxFileBytes: 52428800,
});

export class V2UploadQueue {
    constructor() {
        this.identities = new WeakMap();
        this.sequence = 0;
        this.scope = globalThis.crypto?.randomUUID?.() || Math.random().toString(36).slice(2);
        this.revision = 0;
        this.observed = '';
        this.pending = Promise.resolve();
        this.error = null;
        this.ocrRequests = new WeakMap();
    }

    fileId(file) {
        if (!file || typeof file !== 'object') throw new Error('Choose a valid file.');
        if (!this.identities.has(file)) {
            this.identities.set(file, `v2-${this.scope}-${++this.sequence}`);
        }
        return this.identities.get(file);
    }

    identify(item) {
        item.id = this.fileId(item.file);
        return item.id;
    }

    unique(items) {
        const seen = new Set();
        return items.filter((item) => {
            const id = this.identify(item);
            if (seen.has(id)) return false;
            seen.add(id);
            return true;
        });
    }

    validate(items, requireFiles = false) {
        const entries = this.unique(items);
        if (requireFiles && !entries.length) throw new Error('Choose at least one document before submitting.');
        if (entries.length > V2_UPLOAD_LIMITS.maxFiles) {
            throw new Error('A maximum of 50 files can be attached per submission.');
        }
        let bytes = 0;
        for (const item of entries) {
            const size = item.file.size;
            if (!Number.isSafeInteger(size) || size <= 0) throw new Error('Every attached file must have a valid, non-zero size.');
            if (size > V2_UPLOAD_LIMITS.maxFileBytes) throw new Error('Each file must be at most 52,428,800 bytes (50 MiB).');
            bytes += size;
            if (bytes > V2_UPLOAD_LIMITS.maxTotalBytes) {
                throw new Error('Files must total at most 104,857,600 bytes (100 MiB) per submission.');
            }
        }
        return entries;
    }

    admit(items, candidates) {
        // Validation precedes the caller replacing its queue: failures admit nothing.
        return this.validate([...items, ...candidates]);
    }

    observe(items) {
        const signature = this.unique(items).map((item) => item.id).join('|');
        if (signature !== this.observed) {
            this.observed = signature;
            this.revision++;
        }
    }

    clearError() { this.error = null; }

    trackAddition(work) {
        const operation = this.pending.then(work);
        const tracked = operation.then(
            (result) => { this.error = null; return result; },
            (error) => { this.error = error; throw error; },
        );
        // Keep the serial chain usable after rejection; whenIdle still reports it.
        this.pending = tracked.catch(() => {});
        return tracked;
    }

    async whenIdle() {
        let observed;
        do {
            observed = this.pending;
            await observed;
        } while (observed !== this.pending);
        if (this.error) throw this.error;
    }

    snapshot(items) {
        return this.validate(items, true).map((item) => ({
            item, id: item.id, file: item.file, category: item.category,
        }));
    }

    assertSnapshot(items, snapshot) {
        const current = this.validate(items, true);
        if (current.length !== snapshot.length || current.some((item, index) =>
            item !== snapshot[index].item || item.file !== snapshot[index].file
            || item.id !== snapshot[index].id || item.category !== snapshot[index].category)) {
            throw new Error('The upload queue changed during preparation. Review the files and submit again.');
        }
    }

    beginOcr(item) {
        const request = (this.ocrRequests.get(item) || 0) + 1;
        this.ocrRequests.set(item, request);
        return { item, id: this.identify(item), file: item.file, category: item.category, request };
    }

    isCurrent(items, ticket) {
        return Boolean(ticket && items.includes(ticket.item)
            && ticket.item.file === ticket.file
            && this.identify(ticket.item) === ticket.id
            && this.ocrRequests.get(ticket.item) === ticket.request);
    }

    resumeKey(file) {
        // Distinct same-metadata files must never reuse one completed upload ID.
        return `ordo-transmittal-v2-upload:${this.fileId(file)}:${encodeURIComponent(file.name)}:${file.size}:${file.lastModified}`;
    }

    partition(items) {
        const entries = this.validate(items, true);
        const direct = [];
        const resumable = [];
        const ids = new Set();
        for (const item of entries) {
            const id = item.file.resumableUploadId;
            if (id) {
                if (!/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(id)) {
                    throw new Error('A completed upload has an invalid identifier. Select that file again.');
                }
                const identity = id.toLowerCase();
                if (ids.has(identity)) throw new Error('Two different files reference the same resumable upload. Select those files again.');
                ids.add(identity);
                resumable.push(item);
            } else {
                direct.push(item);
            }
        }
        const documentIndices = new Map([...direct, ...resumable].map((item, index) => [item.id, index]));
        return { direct, resumable, documentIndices };
    }
}

export async function prepareV2Submission({ queue, readItems, waitForAnalysis, prepareResumable, commit }) {
    await queue.whenIdle();
    queue.validate(readItems(), true);
    await waitForAnalysis();
    await queue.whenIdle();
    const snapshot = queue.snapshot(readItems());
    for (const { file } of snapshot) {
        if (file.size > 20 * 1024 * 1024 && !file.resumableUploadId) {
            await prepareResumable(file);
            if (!file.resumableUploadId) throw new Error('A large file has not completed resumable preparation.');
        }
        queue.assertSnapshot(readItems(), snapshot);
    }
    // Commit is the sole side-effect boundary; it is never called on validation/preparation failure.
    queue.assertSnapshot(readItems(), snapshot);
    const plan = queue.partition(readItems());
    await commit(plan);
    return plan;
}
