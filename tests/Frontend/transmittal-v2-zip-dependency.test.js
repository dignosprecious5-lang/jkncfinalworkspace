// Phase 19B source verification. No archive extraction, server, build, or Laravel bootstrap.
import assert from 'node:assert/strict';
import { readFileSync, existsSync } from 'node:fs';
import test from 'node:test';
import { assertDependencyPreservation, assertProtectedFiles, dependencyBaseline, ZIP_DEPENDENCY } from '../Support/transmittal-v2-dependency-contract.js';

test('only the exact approved ZIP dependency differs from the pre-install baseline', () => {
    assert.deepEqual(assertDependencyPreservation(), { existingPackageRecords: 162, approvedAddition: '@zip.js/zip.js' });
});

test('dependency preservation rejects unrelated declarations and changed lock records', () => {
    const manifest = JSON.parse(readFileSync(new URL('../../package.json', import.meta.url), 'utf8'));
    const lock = JSON.parse(readFileSync(new URL('../../package-lock.json', import.meta.url), 'utf8'));
    const path = Object.keys(dependencyBaseline().beforeLock.packages).find((p) => p && lock.packages[p].integrity);
    for (const field of ['version', 'integrity', 'resolved']) {
        const changed = structuredClone(lock);
        changed.packages[path][field] = 'unexpected';
        assert.throws(() => assertDependencyPreservation(manifest, changed), /existing records/);
    }
    const added = structuredClone(lock);
    added.packages['node_modules/unapproved'] = { version: '1.0.0' };
    assert.throws(() => assertDependencyPreservation(manifest, added), /existing records/);
    const changedName = structuredClone(lock);
    changedName.name = 'another-workspace';
    assert.throws(() => assertDependencyPreservation(manifest, changedName), /existing records/);
    const changedManifest = structuredClone(manifest);
    changedManifest.scripts.build = 'unexpected command';
    assert.throws(() => assertDependencyPreservation(changedManifest, lock), /approved ZIP declaration/);
});

test('installed zip.js version and default/native ES-module exports are verified without extracting', async () => {
    const metadata = JSON.parse(readFileSync(new URL('../../node_modules/@zip.js/zip.js/package.json', import.meta.url), 'utf8'));
    assert.equal(metadata.name, ZIP_DEPENDENCY.name);
    assert.equal(metadata.version, ZIP_DEPENDENCY.version);
    assert.equal(metadata.engines.node, '>=18.0.0');
    for (const kind of ['dependencies', 'optionalDependencies', 'peerDependencies']) {
        assert.deepEqual(Object.keys(metadata[kind] || {}), []);
    }
    const standard = await import('@zip.js/zip.js');
    const native = await import('@zip.js/zip.js/index-native.js');
    for (const api of [standard, native]) {
        assert.equal(typeof api.ZipReader, 'function');
        assert.equal(typeof api.BlobReader, 'function');
    }
});

test('fflate and pdf-lib remain absent from declarations, locks, and local installation', () => {
    const manifest = JSON.parse(readFileSync(new URL('../../package.json', import.meta.url), 'utf8'));
    const lock = JSON.parse(readFileSync(new URL('../../package-lock.json', import.meta.url), 'utf8'));
    for (const name of ['fflate', 'pdf-lib']) {
        assert.equal(manifest.dependencies?.[name], undefined);
        assert.equal(manifest.devDependencies?.[name], undefined);
        assert.equal(lock.packages['node_modules/' + name], undefined);
        assert.equal(existsSync(new URL('../../node_modules/' + name, import.meta.url)), false);
    }
});

test('installing the ZIP library does not enable the application archive/PDF capabilities', async () => {
    const toolsSource = readFileSync(new URL('../../resources/js/transmittal-v2-file-tools.js', import.meta.url), 'utf8');
    assert.match(toolsSource, /pdfCompression: false, zipExtraction: false/);
    assert.ok(!toolsSource.includes("from '@zip.js/zip.js"));
    const tools = await import('../../resources/js/transmittal-v2-file-tools.js');
    await assert.rejects(tools.extractUploadArchive(), /ZIP extraction is unavailable/);
    assert.equal(dependencyBaseline().securityAcceptance, 'pending');
});

test('protected files retain reviewed hashes or explicit V1/V2 registration contracts', () => {
    assert.equal(assertProtectedFiles(), 16);
});
