import { PDFDocument } from 'pdf-lib';

export const formatUploadSize = (bytes) => {
    if (bytes >= 1024 ** 3) return `${(bytes / (1024 ** 3)).toFixed(1)} GB`;
    if (bytes >= 1024 ** 2) return `${(bytes / (1024 ** 2)).toFixed(0)} MB`;
    return `${(bytes / 1024).toFixed(0)} KB`;
};

export const compressUploadFile = async (file, maxBytes) => {
    const extension = file.name.split('.').pop()?.toLowerCase();
    if (extension === 'pdf' || file.type === 'application/pdf') {
        try {
            const document = await PDFDocument.load(await file.arrayBuffer());
            const bytes = await document.save({ useObjectStreams: true, addDefaultPage: false });
            if (bytes.byteLength >= maxBytes || bytes.byteLength >= file.size) {
                throw new Error('The PDF could not be reduced below the maximum size. It may already be compressed.');
            }
            return new File([bytes], file.name, { type: 'application/pdf', lastModified: file.lastModified });
        } catch (error) {
            if (error instanceof Error && error.message.includes('could not be reduced')) throw error;
            throw new Error('This PDF could not be compressed in the browser. Choose a smaller or unencrypted PDF.');
        }
    }

    const isImage = file.type.startsWith('image/') || ['jpg', 'jpeg', 'png', 'tif', 'tiff'].includes(extension);
    if (!isImage) {
        throw new Error('Automatic compression is available for PDF and image files. This file type must be reduced with another tool.');
    }

    let bitmap;
    try {
        bitmap = await createImageBitmap(file);
    } catch {
        throw new Error('This image format cannot be compressed by this browser. Choose a smaller file or convert it to JPG or PNG.');
    }

    const keepPng = extension === 'png';
    const outputType = keepPng ? 'image/png' : 'image/jpeg';
    const outputExtension = keepPng ? 'png' : 'jpg';
    const baseName = file.name.replace(/\.[^.]+$/, '');
    let smallestBlob = null;

    try {
        for (const scale of [1, 0.85, 0.7, 0.58, 0.5]) {
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(bitmap.width * scale));
            canvas.height = Math.max(1, Math.round(bitmap.height * scale));
            const context = canvas.getContext('2d');
            if (!context) throw new Error('Image compression is unavailable in this browser.');
            if (outputType === 'image/jpeg') {
                context.fillStyle = '#fff';
                context.fillRect(0, 0, canvas.width, canvas.height);
            }
            context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);

            const qualities = outputType === 'image/jpeg' ? [0.88, 0.78, 0.68, 0.58] : [undefined];
            for (const quality of qualities) {
                const blob = await new Promise((resolve) => canvas.toBlob(resolve, outputType, quality));
                if (!blob) continue;
                if (!smallestBlob || blob.size < smallestBlob.size) smallestBlob = blob;
                if (blob.size < maxBytes && blob.size < file.size) {
                    return new File([blob], `${baseName}.${outputExtension}`, { type: outputType, lastModified: file.lastModified });
                }
            }
        }
    } finally {
        bitmap.close?.();
    }

    if (!smallestBlob || smallestBlob.size >= file.size || smallestBlob.size >= maxBytes) {
        throw new Error('The image could not be reduced below the maximum while keeping it readable.');
    }

    return new File([smallestBlob], `${baseName}.${outputExtension}`, { type: outputType, lastModified: file.lastModified });
};
