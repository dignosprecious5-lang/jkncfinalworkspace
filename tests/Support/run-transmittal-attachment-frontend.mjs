import assert from 'node:assert/strict';
import { realpathSync } from 'node:fs';

if (process.argv.length !== 2 || process.execArgv.length !== 0) {
    throw new Error('The attachment frontend launcher accepts no arguments or Node startup overrides.');
}

const root = realpathSync(new URL('../../', import.meta.url));
assert.equal(root.replaceAll('\\', '/').toLowerCase(),
    'c:/users/admin/documents/original/jknc_transmittal_integration');
assert.equal(realpathSync(new URL('../Frontend/transmittal-attachments.test.js', import.meta.url)),
    realpathSync(`${root}/tests/Frontend/transmittal-attachments.test.js`));

process.env.ORDO_ATTACHMENT_SYNTHETIC_TESTS = 'approved-isolated-runtime';
await import('../Frontend/transmittal-attachments.test.js');
