import assert from 'node:assert/strict';
import test from 'node:test';
import { PDFDocument } from 'pdf-lib';
import { compressUploadFile } from '../../resources/js/upload-compression.js';

test('oversized non-PDF documents are rejected without changing their bytes', async () => {
    const originalBytes = new Uint8Array([0x50, 0x4b, 0x03, 0x04, 1, 2, 3, 4]);
    const file = new File([originalBytes], 'archive.docx', {
        type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    });

    await assert.rejects(
        compressUploadFile(file, 4),
        /This file type must be reduced with another tool/
    );
    assert.deepEqual(new Uint8Array(await file.arrayBuffer()), originalBytes);
    assert.equal(file.size, originalBytes.length);
});

test('PDF files continue to use format-aware compression', async () => {
    const document = await PDFDocument.create();
    document.addPage();
    const pdfBytes = await document.save();
    const originalBytes = new Uint8Array(pdfBytes.length + 2048);
    originalBytes.set(pdfBytes);
    originalBytes.fill(0x20, pdfBytes.length);
    const file = new File([originalBytes], 'large.pdf', { type: 'application/pdf' });

    const compressed = await compressUploadFile(file, file.size);

    assert.equal(compressed.type, 'application/pdf');
    assert.ok(compressed.size < file.size);
    assert.equal((await PDFDocument.load(await compressed.arrayBuffer())).getPageCount(), 1);
});

test('image files continue to use browser image compression', async (context) => {
    const originalBitmap = globalThis.createImageBitmap;
    const originalDocument = globalThis.document;
    globalThis.createImageBitmap = async () => ({
        width: 100,
        height: 100,
        close() {},
    });
    globalThis.document = {
        createElement: () => ({
            getContext: () => ({
                fillRect() {},
                drawImage() {},
                set fillStyle(_value) {},
            }),
            toBlob(callback, type) {
                callback(new Blob([new Uint8Array(20)], { type }));
            },
        }),
    };
    context.after(() => {
        globalThis.createImageBitmap = originalBitmap;
        globalThis.document = originalDocument;
    });

    const file = new File([new Uint8Array(100)], 'photo.jpg', { type: 'image/jpeg' });
    const compressed = await compressUploadFile(file, 50);

    assert.equal(compressed.type, 'image/jpeg');
    assert.ok(compressed.size < file.size);
    assert.ok(compressed.size <= 50);
});
