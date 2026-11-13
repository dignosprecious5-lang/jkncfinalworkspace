/**
 * V2-only browser compression helper. It creates a new file; it never mutates
 * or writes the user's original file. PDF compression remains unavailable
 * until pdf-lib is dependency-reviewed and integrated.
 */
export async function compressUploadFile(file, maxBytes) {
    if (!(file instanceof File)) {
        throw new TypeError('Choose a valid file before compressing.');
    }

    if (!Number.isSafeInteger(maxBytes) || maxBytes < 1) {
        throw new RangeError('The configured upload size limit is invalid.');
    }

    if (file.size <= maxBytes) return file;

    const extension = (file.name.split('.').pop() || '').toLowerCase();
    const isPdf = file.type === 'application/pdf' || extension === 'pdf';
    if (isPdf) {
        throw new Error('PDF compression is unavailable until the reviewed pdf-lib dependency is integrated. Choose a smaller PDF.');
    }

    const supportedImages = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!supportedImages.includes(file.type.toLowerCase())) {
        throw new Error('Automatic compression is currently available for JPEG, PNG, and WebP images only.');
    }

    if (typeof createImageBitmap !== 'function') {
        throw new Error('This browser does not support safe in-browser image compression.');
    }

    const bitmap = await createImageBitmap(file);

    try {
        const maxDimension = 2400;
        const scale = Math.min(1, maxDimension / Math.max(bitmap.width, bitmap.height));
        const width = Math.max(1, Math.round(bitmap.width * scale));
        const height = Math.max(1, Math.round(bitmap.height * scale));
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const context = canvas.getContext('2d');

        if (!context) throw new Error('This browser could not prepare the image for compression.');

        context.fillStyle = '#fff';
        context.fillRect(0, 0, width, height);
        context.drawImage(bitmap, 0, 0, width, height);

        for (const quality of [0.85, 0.7, 0.55, 0.4, 0.3]) {
            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
            if (!blob) throw new Error('The browser could not encode the compressed image.');
            if (blob.size <= maxBytes) {
                const name = file.name.replace(/\.[^/.]+$/, '') || 'upload';
                return new File([blob], `${name}.jpg`, { type: 'image/jpeg', lastModified: Date.now() });
            }
        }

        throw new Error('The image could not be reduced below the upload limit while retaining a usable image.');
    } finally {
        bitmap.close?.();
    }
}
