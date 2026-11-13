import { compressUploadFile } from './transmittal-v2-upload-compression.js';
import { V2UploadQueue, V2_UPLOAD_LIMITS, prepareV2Submission } from './transmittal-v2-upload-queue.js';

/**
 * V2-only bridge for the legacy ORDOFileTools interface.
 *
 * This file deliberately has no third-party package imports: pdf-lib and fflate are not
 * installed in this integration workspace. Image compression uses the
 * V2-only helper; PDF compression and ZIP extraction fail explicitly
 * until their reviewed dependencies are integrated in a later approved batch.
 */
export function formatUploadSize(bytes) {
    const value = Number(bytes);
    if (!Number.isFinite(value) || value < 0) return '0 B';
    if (value < 1024) return `${value} B`;

    const units = ['KB', 'MB', 'GB'];
    let size = value / 1024;
    let unit = 0;

    while (size >= 1024 && unit < units.length - 1) {
        size /= 1024;
        unit++;
    }

    return `${size.toFixed(1)} ${units[unit]}`;
}

export async function extractUploadArchive() {
    throw new Error('ZIP extraction is unavailable until the reviewed fflate dependency is integrated.');
}

if (typeof window !== 'undefined') {
    window.ORDOFileTools = {
        ...(window.ORDOFileTools || {}),
        compress: compressUploadFile,
        formatSize: formatUploadSize,
        extractArchive: extractUploadArchive,
        UploadQueue: V2UploadQueue,
        uploadLimits: V2_UPLOAD_LIMITS,
        prepareSubmission: prepareV2Submission,
        capabilities: Object.freeze({ pdfCompression: false, zipExtraction: false }),
    };
}
