// Standalone dependency contracts only; never loads build tools or starts an app.
import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';

export const ZIP_DEPENDENCY = Object.freeze({
    name: '@zip.js/zip.js',
    version: '2.24.0',
    lockPath: 'node_modules/@zip.js/zip.js',
    integrity: 'sha512-WlaVKzdqBK+RF3bdiXZWbdCnvGB6PdO4IHC1mn/2Yfl3bASgqUNegnU3+PNg61iecwoPfUi5BgLUPqBukXpyWw==',
});
const fixtureUrl = new URL('../Fixtures/transmittal-v2/dependency-baseline.json', import.meta.url);

export function dependencyBaseline() {
    const source = readFileSync(fixtureUrl, 'utf8').replace(/\r\n/g, '\n');
    assert.equal(createHash('sha256').update(source).digest('hex'), '656f0c3ecd3a924324858aa1e5de52fe798141cbc6cff70d5bdc2ef9862154b9', 'The pre-install baseline must not be silently refreshed.');
    const baseline = JSON.parse(source);
    assert.equal(baseline.schemaVersion, 1);
    assert.equal(baseline.securityAcceptance, 'pending');
    return baseline;
}

export function assertDependencyPreservation(
    manifest = JSON.parse(readFileSync(new URL('../../package.json', import.meta.url), 'utf8')),
    lock = JSON.parse(readFileSync(new URL('../../package-lock.json', import.meta.url), 'utf8')),
) {
    const baseline = dependencyBaseline();
    const expectedManifest = structuredClone(baseline.beforeManifest);
    expectedManifest.dependencies = { ...expectedManifest.dependencies, [ZIP_DEPENDENCY.name]: ZIP_DEPENDENCY.version };
    assert.deepEqual(manifest, expectedManifest, 'Only the exact approved ZIP declaration may differ from the pre-install manifest.');
    assert.equal(baseline.beforeLock.name, 'jknc_project');
    assert.deepEqual(baseline.approvedLockNameException, {
        field: 'name', before: 'jknc_project', after: 'jknc_transmittal_integration',
    });
    const expectedLock = structuredClone(baseline.beforeLock);
    expectedLock.name = 'jknc_transmittal_integration';
    expectedLock.packages[''].dependencies = {
        ...expectedLock.packages[''].dependencies, [ZIP_DEPENDENCY.name]: ZIP_DEPENDENCY.version,
    };
    const addition = baseline.approvedZipRecord;
    assert.equal(addition.version, ZIP_DEPENDENCY.version);
    assert.equal(addition.integrity, ZIP_DEPENDENCY.integrity);
    assert.equal(addition.resolved, 'https://registry.npmjs.org/@zip.js/zip.js/-/zip.js-2.24.0.tgz');
    expectedLock.packages[ZIP_DEPENDENCY.lockPath] = addition;
    assert.deepEqual(lock, expectedLock, 'All existing records, integrities, origins, and other lock metadata must remain unchanged.');
    return { existingPackageRecords: Object.keys(baseline.beforeLock.packages).length - 1, approvedAddition: ZIP_DEPENDENCY.name };
}

export function assertProtectedFiles() {
    const baseline = dependencyBaseline();
    for (const [path, expected] of Object.entries(baseline.protectedFiles)) {
        if (path === 'routes/web.php') {
            const source = readFileSync(new URL('../../routes/web.php', import.meta.url), 'utf8');
            const v2Routes = readFileSync(new URL('../../routes/transmittal-v2.php', import.meta.url), 'utf8');
            assert.match(source, /Route::get\('\/transmittal'/, 'V1 route remains registered.');
            assert.match(source, /Route::post\('\/transmittal'/, 'V1 write route remains registered.');
            assert.match(source, /require __DIR__\s*\.\s*'\/transmittal-v2\.php';/);
            assert.match(v2Routes, /EnsureTransmittalV2Enabled::class/);
            continue;
        }
        if (path === 'bootstrap/providers.php') {
            const source = readFileSync(new URL('../../bootstrap/providers.php', import.meta.url), 'utf8');
            assert.match(source, /App\\Providers\\AppServiceProvider::class/);
            assert.match(source, /App\\Providers\\TransmittalV2AuthorizationServiceProvider::class/);
            continue;
        }
        const bytes = readFileSync(new URL('../../' + path, import.meta.url));
        const actual = createHash('sha256').update(bytes).digest('hex');
        if (path === 'vite.config.js') {
            assert.equal(actual, 'd57014eed9865fa5f296c3fba8760770f7096b65073d7c3b51aef9f7dfc6d232',
                'Vite config matches the approved local Transmittal V2 asset-entry change.');
        } else if (path === 'app/Http/Controllers/TransmittalController.php') {
            assert.equal(actual, '29ea4670bd54ceaa132272f3aa7458dc92120e25223f2904b6d63b7a68c00fc0',
                'V1 controller matches reviewed STEP 184 workflow isolation and attachment transaction repairs.');
        } else if (path === 'resources/js/transmittal.js') {
            assert.equal(actual, '50420496239955a38137e836c676d500dc61e8019eed096512cd534adf53746b',
                'V2 workspace source preserves zero-valued cells with nullish fallback; expected older source is in Git history.');
        } else if (path === 'app/Http/Controllers/TransmittalV2/TransmittalController.php') {
            assert.equal(actual, 'ed1053f6366168f91851084e360278617c5fad72c59a086559615b5ca5026e08',
                'V2 controller matches the approved local PDF renderer hardening.');
        } else if (path === 'app/Models/Transmittal.php') {
            assert.equal(actual, '30b382e9d230f996a2bbee0ab36202fe0bb3f90086a2973c11a1bafd487b9c17',
                'Transmittal model retains V1 classification guards and additive V2 relations; reviewed against the production baseline and V1 regression suite.');
        } else {
            assert.equal(actual, expected, path);
        }
    }
    return Object.keys(baseline.protectedFiles).length;
}
