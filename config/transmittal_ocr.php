<?php

return [
    // Trusted administrator-supplied absolute paths to Tesseract and pdftoppm.
    // No PATH fallback: the OCR service rejects missing or invalid executables.
    'binary' => env('TRANSMITTAL_OCR_BINARY'),
    'pdf_binary' => env('TRANSMITTAL_OCR_PDF_BINARY'),
];
