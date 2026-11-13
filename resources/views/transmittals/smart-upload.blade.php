@extends('layouts.app')

@section('title', 'Smart Upload')

@section('content')
@vite('resources/js/transmittal-v2-file-tools.js')

<style>
    /* ==============================
       SMART UPLOAD ROOT & STYLES
    ============================== */
    .smart-upload-container {
        width: min(100%, 1240px);
        min-width: 0;
        margin-inline: auto;
        padding-bottom: 48px;
    }

    .smart-upload-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        width: 100%;
        margin: 0 0 18px;
        padding: 22px 26px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }

    .smart-upload-title .eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 8px;
        padding: 4px 11px;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        border-radius: 20px;
        color: #1d4ed8;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: .6px;
        text-transform: uppercase;
    }

    .smart-upload-title h1 {
        margin: 0 0 6px;
        color: #1f2937;
        font-size: 30px;
        font-weight: 600;
        letter-spacing: -0.025em;
        line-height: 1;
        font-family: inherit;
    }

    .smart-upload-title .muted {
        margin: 0;
        color: #64748b;
        font-size: 15px;
        line-height: 1.5;
    }

    .smart-upload-heading-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 38px;
        padding: 8px 14px;
        border: 1px solid #dbe3ef;
        border-radius: 9px;
        background: #ffffff;
        color: #334155;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all .15s ease;
    }

    .btn-action:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #0f172a;
    }

    .smart-upload-close {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        background: #ffffff;
        color: #475569;
        font-size: 20px;
        line-height: 1;
        text-decoration: none;
        transition: all .15s ease;
    }

    .smart-upload-close:hover {
        background: #fee2e2;
        border-color: #fecaca;
        color: #dc2626;
    }

    /* ==============================
       PANEL & TOOLBAR
    ============================== */
    .smart-panel {
        width: 100%;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 24px;
        margin: 0;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }

    .smart-upload-workspace {
        max-width: none;
    }

    #smart-upload-form {
        display: grid;
        gap: 18px;
        min-width: 0;
    }

    .panel-section-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }

    .smart-upload-workspace > .panel-section-title > div:first-child {
        min-width: 220px;
        flex: 1 1 auto;
    }

    .panel-section-title h2 {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: #0f172a;
        font-size: 19px;
        font-weight: 700;
    }

    .panel-section-title .section-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        color: #111827;
        background: #f1f3f7;
        border-radius: 8px;
        font-size: 16px;
    }

    .panel-section-title p {
        margin: 3px 0 0;
        color: #64748b;
        font-size: 14px;
    }

    /* Actions toolbar */
    .smart-toolbar {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        align-items: center;
        gap: 10px;
        margin-bottom: 0;
        min-width: 0;
        width: min(100%, 540px);
        max-width: 100%;
        box-sizing: border-box;
        padding: 2px 0;
    }

    .btn-toolbar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 38px;
        padding: 7px 10px;
        white-space: nowrap;
        font-size: 13px;
        font-weight: 600;
        border-radius: 8px;
        cursor: pointer;
        transition: all .15s ease;
        border: 1px solid transparent;
    }

    .smart-toolbar > .btn-toolbar {
        width: 100%;
        min-width: 0;
    }

    .btn-toolbar-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
        flex: 0 0 16px;
        border-radius: 5px;
        background: #f1f3f7;
        color: #111827;
    }

    .btn-toolbar-icon svg {
        display: block;
        width: 11px;
        height: 11px;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .btn-toolbar-primary {
        background: #2563eb;
        color: #ffffff;
    }
    .btn-toolbar-primary:hover {
        background: #1d4ed8;
    }

    .btn-toolbar-outline {
        background: #ffffff;
        border-color: #cbd5e1;
        color: #334155;
    }
    .btn-toolbar-outline:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        color: #0f172a;
    }

    .btn-toolbar-purple,
    .btn-toolbar-blue {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
    }
    .btn-toolbar-purple:hover,
    .btn-toolbar-blue:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #ffffff;
    }
    .btn-toolbar-purple .btn-toolbar-icon,
    .btn-toolbar-blue .btn-toolbar-icon {
        background: #1e40af;
        color: #ffffff;
    }

    .btn-toolbar-danger {
        background: #fff;
        border-color: #fecaca;
        color: #dc2626;
        margin-left: 0;
    }
    .btn-toolbar-danger:hover {
        background: #fee2e2;
    }

    /* ==============================
       DRAG & DROP ZONE
    ============================== */
    .smart-drop-zone {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        min-height: 190px;
        padding: 28px 20px;
        border: 2px dashed #93c5fd;
        border-radius: 12px;
        background: #f8fbff;
        text-align: center;
        cursor: pointer;
        transition: all .2s ease;
    }

    .smart-drop-zone:hover,
    .smart-drop-zone.drag-over {
        border-color: #2563eb;
        background: #eff6ff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
    }

    .smart-drop-zone.compact {
        min-height: 100px;
        padding: 16px;
        flex-direction: row;
        gap: 16px;
    }

    .smart-drop-zone .upload-icon-circle {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #dbeafe;
        color: #2563eb;
        font-size: 20px;
        font-weight: 700;
        transition: transform .2s ease;
    }

    .smart-drop-zone:hover .upload-icon-circle {
        transform: translateY(-2px);
    }

    .smart-drop-zone strong {
        color: #0f172a;
        font-size: 17px;
        font-weight: 700;
    }

    .smart-drop-zone .drop-hint {
        color: #64748b;
        font-size: 14px;
    }

    .smart-drop-zone .drop-hint u {
        color: #2563eb;
        font-weight: 600;
        text-underline-offset: 3px;
    }

    .smart-drop-zone .drop-limits {
        color: #94a3b8;
        font-size: 13px;
    }

    .smart-drop-zone input[type="file"] {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        clip-path: inset(50%);
    }

    /* ==============================
       UPLOAD & OCR PROGRESS BAR
    ============================== */
    .progress-container {
        margin: 16px 0;
        padding: 16px 20px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }

    .progress-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 600;
        color: #334155;
    }

    .progress-track {
        width: 100%;
        height: 8px;
        background: #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        width: 0%;
        background: linear-gradient(90deg, #3b82f6, #2563eb);
        border-radius: 10px;
        transition: width .3s ease;
    }

    .progress-status-msg {
        margin-top: 8px;
        font-size: 13px;
        color: #64748b;
    }
    .progress-status-msg.error {
        color: #dc2626;
        font-weight: 600;
    }

    /* ==============================
       UPLOADED FILES QUEUE CARDS
    ============================== */
    .files-queue-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 16px;
    }

    .file-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 18px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        transition: all .15s ease;
    }

    .file-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
    }

    .file-card-main {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
        flex: 1;
    }

    .file-badge-type {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        flex-shrink: 0;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .5px;
        text-transform: uppercase;
        background: #f1f5f9;
        color: #475569;
    }
    .file-badge-type.pdf { background: #fee2e2; color: #dc2626; }
    .file-badge-type.doc, .file-badge-type.docx { background: #dbeafe; color: #2563eb; }
    .file-badge-type.img, .file-badge-type.jpg, .file-badge-type.png { background: #fef3c7; color: #d97706; }

    .file-meta-col {
        min-width: 0;
        flex: 1;
    }

    .file-meta-name {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .file-meta-details {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 4px;
        font-size: 13px;
        color: #64748b;
    }

    .file-card-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }

    .file-category-select {
        min-height: 36px;
        padding: 6px 12px;
        border: 1px solid #dbe3ef;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        background: #ffffff;
        outline: none;
    }
    .file-category-select:focus {
        border-color: #3b82f6;
    }

    .btn-remove-file {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border: 1px solid transparent;
        border-radius: 7px;
        background: transparent;
        color: #94a3b8;
        font-size: 18px;
        cursor: pointer;
        transition: all .15s ease;
    }
    .btn-remove-file:hover {
        background: #fee2e2;
        color: #dc2626;
        border-color: #fecaca;
    }

    /* ==============================
       CONFIDENCE & SOURCE BADGES
    ============================== */
    .badge-confidence {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 9px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .3px;
        text-transform: uppercase;
    }

    .badge-confidence.ocr,
    .badge-confidence.filename,
    .badge-confidence.review {
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid #bfdbfe;
        font-weight: 550;
    }

    .badge-confidence.matched {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
    }

    .badge-confidence.autofilled {
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid #bfdbfe;
        font-weight: 550;
    }

    .badge-confidence.duplicate {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    /* ==============================
       LIST OF ITEMS TABLE
    ============================== */
    .items-table-wrapper {
        width: 100%;
        overflow-x: auto;
        margin-top: 14px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #ffffff;
    }

    .items-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
        text-align: left;
    }

    .items-table th {
        background: #f8fafc;
        padding: 12px 14px;
        font-weight: 700;
        color: #475569;
        font-size: 13px;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }

    .items-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        font-size: 13.5px;
    }

    .items-table tr:hover td {
        background: #f8fafc;
    }

    .items-table input {
        width: 100%;
        min-height: 36px;
        padding: 6px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: 13px;
        color: #0f172a;
        background: #ffffff;
        outline: none;
    }
    .items-table input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px #dbeafe;
    }

    .items-table .col-pos { width: 36px; text-align: center; color: #64748b; font-weight: 700; }
    .items-table .col-file { width: 140px; }
    .items-table .col-particular { min-width: 160px; }
    .items-table .col-unique { width: 120px; }
    .items-table .col-qty { width: 65px; }
    .items-table .col-desc { min-width: 160px; }
    .items-table .col-remarks { width: 130px; }
    .items-table .col-source { width: 190px; min-width: 160px; }
    .item-source-cell { display: flex; flex-direction: column; align-items: flex-start; gap: 5px; }
    .item-source-file { max-width: 180px; color: #64748b; font-size: 10px; overflow-wrap: anywhere; }
    .items-table .col-action { width: 36px; text-align: center; }

    .file-attachment-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        max-width: 130px;
        padding: 3px 6px;
        background: #f1f5f9;
        border-radius: 6px;
        font-size: 11px;
        color: #475569;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ==============================
       TRANSMITTAL DETAILS FORM
    ============================== */
    .fields-grid-2 {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }

    .field-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .field-label-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .field-label {
        font-size: 12px;
        font-weight: 700;
        color: #334155;
    }

    .field-label .required {
        color: #dc2626;
    }

    .field-input {
        width: 100%;
        min-height: 40px;
        padding: 8px 12px;
        border: 1px solid #dbe3ef;
        border-radius: 8px;
        font-size: 13px;
        color: #0f172a;
        background: #ffffff;
        outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .field-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px #dbeafe;
    }

    /* ==============================
       CONFIRMATION FOOTER
    ============================== */
    .confirmation-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-top: 24px;
        padding: 18px 24px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }

    .confirmation-notice {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #475569;
        font-size: 12px;
    }

    .confirmation-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    .btn-submit-main {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 42px;
        padding: 10px 22px;
        background: #2563eb;
        color: #ffffff;
        border: none;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
        transition: all .15s ease;
    }
    .btn-submit-main:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(37, 99, 235, 0.3);
    }
    .btn-submit-main:disabled {
        opacity: .65;
        cursor: wait;
        transform: none;
        box-shadow: none;
    }

    /* ==============================
       MODALS: CAMERA & DOC MGMT
    ============================== */
    .smart-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(4px);
        padding: 20px;
    }

    .smart-modal {
        background: #ffffff;
        border-radius: 16px;
        width: 100%;
        max-width: 650px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        border: 1px solid #cbd5e1;
    }

    .smart-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
    }

    .smart-modal-header h3 {
        margin: 0;
        color: #0f172a;
        font-size: 16px;
        font-weight: 700;
    }

    .smart-modal-body {
        padding: 20px;
    }

    .smart-modal-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        padding: 14px 20px;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
        border-bottom-left-radius: 16px;
        border-bottom-right-radius: 16px;
    }

    .camera-preview-wrapper {
        position: relative;
        width: 100%;
        height: 340px;
        background: #0f172a;
        border-radius: 10px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .camera-video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .camera-canvas {
        display: none;
    }

    /* Responsive */
    @media (max-width: 1100px) {
        .fields-grid-2 {
            grid-template-columns: minmax(0, 1fr);
        }
        .smart-upload-heading {
            flex-direction: column;
            align-items: flex-start;
            padding: 18px;
        }
        .confirmation-banner {
            flex-direction: column;
            align-items: flex-start;
        }
        .confirmation-actions {
            width: 100%;
            justify-content: flex-end;
        }
        .smart-upload-workspace > .panel-section-title {
            align-items: stretch;
            flex-direction: column;
            gap: 10px;
            width: 100%;
            min-width: 0;
        }
        .smart-upload-workspace > .panel-section-title > div:first-child {
            min-width: 0;
        }
        .smart-toolbar {
            width: 100%;
        }
        .smart-upload-container .smart-panel,
        .smart-upload-container .panel-section-title {
            min-width: 0;
        }
        .smart-panel {
            padding: 18px;
        }
    }

    @media (max-width: 520px) {
        .smart-upload-heading {
            padding: 16px;
            border-radius: 12px;
        }
        .smart-panel {
            padding: 14px;
            border-radius: 12px;
        }
        .smart-upload-title h1 {
            font-size: 23px;
        }
        .smart-toolbar {
            gap: 6px;
        }
        .smart-toolbar .btn-toolbar {
            flex-direction: column;
            gap: 3px;
            min-height: 48px;
            padding: 5px 3px;
            white-space: normal;
            text-align: center;
            line-height: 1.15;
        }
    }
</style>

<div class="smart-upload-container">

    {{-- HEADER --}}
    <div class="smart-upload-heading">
        <div class="smart-upload-title">
            <h1>Smart Upload</h1>
            <p class="muted">
                Upload or drag multiple documents, run OCR classification, detect metadata &amp; duplicates, auto-populate the items list, and review before creating your draft.
            </p>
        </div>

        <div class="smart-upload-heading-actions">
            <a class="smart-upload-close" href="{{ route('transmittals.index') }}" aria-label="Close Smart Upload" title="Close">
                &times;
            </a>
        </div>
    </div>

    {{-- FORM --}}
    <form
        method="POST"
        action="{{ route('transmittals.smart-upload.store') }}"
        enctype="multipart/form-data"
        id="smart-upload-form"
        data-upload-start-url="{{ route('transmittals.uploads.start') }}"
        data-upload-max-bytes="{{ config('transmittal_v2.upload.max_bytes', 52428800) }}"
        data-chunk-bytes="{{ config('transmittal_v2.upload.chunk_bytes', 5242880) }}"
        data-upload-analyze-url="{{ url('/transmittals/uploads') }}"
        data-analyze-url="{{ route('transmittals.analyze') }}"
        data-max-ocr-files="{{ config('transmittal_v2.upload.max_files', 50) }}"
    >
        @csrf

        {{-- SECTION 1: UPLOAD & DROPZONE --}}
        <section class="smart-panel smart-upload-workspace">
            <div class="panel-section-title">
                <div>
                    <h2>Upload Documents</h2>
                    <p>Select multiple files, drag and drop documents, capture a photo, or choose from Document Management.</p>
                </div>

                <div class="smart-toolbar">
                    <button type="button" class="btn-toolbar btn-toolbar-primary" id="btn-browse-trigger">
                        Browse Multiple Files
                    </button>
                    <button type="button" class="btn-toolbar btn-toolbar-outline" id="btn-camera-trigger">
                        Take a Photo
                    </button>
                    <button type="button" class="btn-toolbar btn-toolbar-purple" id="btn-docmgmt-trigger">
                        Document Management
                    </button>
                    <button type="button" class="btn-toolbar btn-toolbar-outline" id="btn-reanalyze" style="display: none;">
                        Re-analyze OCR
                    </button>
                    <button type="button" class="btn-toolbar btn-toolbar-danger" id="btn-clear-all" style="display: none;">
                        Clear All
                    </button>
                </div>
            </div>

            {{-- PERSISTENT MULTI-FILE DROP ZONE --}}
            <label class="smart-drop-zone" id="smart-dropzone" for="smart-document-input">
                <strong>Drag &amp; drop your files here (Multiple Files Supported)</strong>
                <span class="drop-hint">or <u>browse from your device</u> · Drop additional files anytime</span>
                <small class="drop-limits">
                    PDF, JPG, PNG, TIFF, DOC/DOCX · Up to {{ number_format(config('transmittal_v2.upload.max_bytes', 52428800) / 1048576) }} MB per file · Up to {{ number_format(config('transmittal_v2.upload.max_files', 50)) }} files per OCR batch
                </small>

                <input
                    type="file"
                    name="documents[]"
                    id="smart-document-input"
                    multiple
                    accept=".pdf,.jpg,.jpeg,.png,.tif,.tiff,.doc,.docx,.txt"
                    data-upload-max-bytes="{{ config('transmittal_v2.upload.max_bytes', 52428800) }}"
                >
            </label>
            <input type="file" id="smart-zip-input" accept=".zip,application/zip,application/x-zip-compressed" hidden>

            {{-- PROGRESS TRACKER --}}
            <div class="progress-container" id="upload-progress-container" style="display: none;">
                <div class="progress-header">
                    <span id="progress-title">Uploading &amp; Analyzing files...</span>
                    <span id="progress-percentage">0%</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill" id="progress-fill"></div>
                </div>
                <div class="progress-status-msg" id="progress-status-msg">
                    Reading documents and extracting metadata with OCR...
                </div>
            </div>

            {{-- UPLOADED FILES QUEUE --}}
            <div id="smart-queue-container" style="display: none; margin-top: 20px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-size: 13px; font-weight: 700; color: #1e293b;">
                        Attached Documents (<span id="queue-count">0</span>)
                    </span>
                    <span style="font-size: 11px; color: #64748b;">
                        Categorize each document or verify detected types
                    </span>
                </div>

                <div class="files-queue-list" id="files-queue-list"></div>
            </div>
        </section>

        {{-- SECTION 2: AUTO-CREATED LIST OF ITEMS TABLE --}}
        <section class="smart-panel" id="smart-items-section" style="display: none;">
            <div class="panel-section-title">
                <div>
                    <h2>List of Items (Auto-created from Documents)</h2>
                    <p>One row auto-created per detected document. You can edit every field, add custom items, or remove rows.</p>
                </div>
                <button type="button" class="btn-toolbar btn-toolbar-outline" id="btn-add-item-row">
                    + Add Item Row
                </button>
            </div>

            <div class="items-table-wrapper">
                <table class="items-table" id="smart-items-table">
                    <thead>
                        <tr>
                            <th class="col-pos">#</th>
                            <th class="col-file">Attached Document</th>
                            <th class="col-particular">Particular / Title <span class="required" style="color: #dc2626;">*</span></th>
                            <th class="col-unique">Unique ID / Ref</th>
                            <th class="col-qty">Qty</th>
                            <th class="col-desc">Description</th>
                            <th class="col-remarks">Remarks</th>
                            <th class="col-source">Source</th>
                            <th class="col-action"></th>
                        </tr>
                    </thead>
                    <tbody id="smart-items-tbody">
                        {{-- Items rows dynamically injected here --}}
                    </tbody>
                </table>
            </div>
        </section>

        {{-- SECTION 3: TRANSMITTAL DETAILS & ROUTING (REVIEW SCREEN) --}}
        <section class="smart-panel" id="smart-details-section" style="display: none;">
            <div class="panel-section-title">
                <div>
                    <h2>Transmittal Header &amp; Recipient Review</h2>
                    <p>OCR extracted details are editable and tagged with confidence/source labels.</p>
                </div>
            </div>

            <div class="fields-grid-2">
                {{-- Subject --}}
                <div class="field-group">
                    <div class="field-label-row">
                        <label class="field-label" for="subject-field">
                            Subject <span class="required">*</span>
                        </label>
                        <span class="badge-confidence review" id="badge-subject">Review Required</span>
                    </div>
                    <input
                        class="field-input"
                        name="subject"
                        id="subject-field"
                        value="{{ old('subject', '') }}"
                        required
                        maxlength="200"
                        placeholder="e.g. Architectural Drawing Set for Submittal"
                    >
                </div>

                {{-- External Reference --}}
                <div class="field-group">
                    <div class="field-label-row">
                        <label class="field-label" for="reference-field">
                            Document Reference / Control No.
                        </label>
                        <span class="badge-confidence review" id="badge-reference">Review Required</span>
                    </div>
                    <input
                        class="field-input"
                        name="external_reference"
                        id="reference-field"
                        value="{{ old('external_reference', '') }}"
                        maxlength="120"
                        placeholder="e.g. DWG-2026-001"
                    >
                </div>
            </div>

            <div class="fields-grid-2">
                {{-- Related Company --}}
                <div class="field-group">
                    <div class="field-label-row">
                        <label class="field-label" for="company-field">
                            Company / Client / Organization
                        </label>
                        <span class="badge-confidence review" id="badge-company">Review Required</span>
                    </div>
                    <input
                        class="field-input"
                        name="related_company"
                        id="company-field"
                        value="{{ old('related_company', '') }}"
                        maxlength="200"
                        placeholder="e.g. Acme Construction Corp"
                    >
                </div>

                {{-- Delivery Address --}}
                <div class="field-group">
                    <div class="field-label-row">
                        <label class="field-label" for="address-field">
                            Delivery Address / Location
                        </label>
                        <span class="badge-confidence review" id="badge-address">Review Required</span>
                    </div>
                    <input
                        class="field-input"
                        name="recipient_address"
                        id="address-field"
                        value="{{ old('recipient_address', '') }}"
                        maxlength="500"
                        placeholder="e.g. 100 Main Street, Suite 400"
                    >
                </div>
            </div>

            <div class="fields-grid-2">
                {{-- Recipient Name --}}
                <div class="field-group">
                    <div class="field-label-row">
                        <label class="field-label" for="recipient-name-field">
                            Recipient Attention Name
                        </label>
                        <span class="badge-confidence review" id="badge-recipient-name">Review Required</span>
                    </div>
                    <input
                        class="field-input"
                        name="recipient_name"
                        id="recipient-name-field"
                        value="{{ old('recipient_name', '') }}"
                        maxlength="160"
                        placeholder="e.g. Engr. John Miller"
                    >
                </div>

                {{-- Recipient Email --}}
                <div class="field-group">
                    <div class="field-label-row">
                        <label class="field-label" for="recipient-email-field">
                            Recipient Email Address
                        </label>
                        <span class="badge-confidence review" id="badge-recipient-email">Review Required</span>
                    </div>
                    <input
                        class="field-input"
                        name="recipient_email"
                        id="recipient-email-field"
                        type="email"
                        value="{{ old('recipient_email', '') }}"
                        maxlength="254"
                        placeholder="e.g. john.miller@example.com"
                    >
                </div>
            </div>

            <div class="fields-grid-2">
                {{-- Transmittal Date --}}
                <div class="field-group">
                    <div class="field-label-row">
                        <label class="field-label" for="date-field">
                            Transmittal Date
                        </label>
                        <span class="badge-confidence autofilled" id="badge-date">Default date</span>
                    </div>
                    <input
                        class="field-input"
                        type="date"
                        name="transmittal_date"
                        id="date-field"
                        value="{{ old('transmittal_date', now()->toDateString()) }}"
                    >
                </div>

                {{-- Purpose / Remarks --}}
                <div class="field-group">
                    <div class="field-label-row">
                        <label class="field-label" for="purpose-field">
                            Purpose / Transmittal Instructions
                        </label>
                        <span class="badge-confidence autofilled">Default</span>
                    </div>
                    <input
                        class="field-input"
                        name="purpose"
                        id="purpose-field"
                        value="{{ old('purpose', 'For review, approval, and official record.') }}"
                        maxlength="500"
                        placeholder="e.g. For your review and approval"
                    >
                </div>
            </div>
        </section>

        {{-- SECTION 4: CONFIRMATION & CREATE DRAFT / TRN --}}
        <div class="confirmation-banner" id="smart-confirmation-section" style="display: none;">
            <div class="confirmation-notice">
                <div>
                    <strong style="color: #0f172a; font-size: 13px;">Ready to create transmittal?</strong>
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                        Only after confirmation will the working draft and permanent tracking number (TRN) be generated.
                    </div>
                </div>
            </div>

            <div class="confirmation-actions">
                <a class="btn-action" href="{{ route('transmittals.index') }}">
                    Cancel
                </a>
                <button type="submit" class="btn-submit-main" id="btn-submit-draft">
                    Confirm &amp; Create Working Draft (TRN) →
                </button>
            </div>
        </div>
    </form>
</div>

{{-- MODAL 1: CAMERA CAPTURE --}}
<div class="smart-modal-backdrop" id="camera-modal" style="display: none;">
    <div class="smart-modal">
        <div class="smart-modal-header">
            <h3>Take a Photo / Document Scanner</h3>
            <button type="button" class="btn-remove-file" id="btn-close-camera">&times;</button>
        </div>
        <div class="smart-modal-body">
            <div class="camera-preview-wrapper">
                <video class="camera-video" id="camera-video" autoplay playsinline></video>
                <canvas class="camera-canvas" id="camera-canvas"></canvas>
            </div>
            <p id="camera-status" style="margin: 10px 0 0; font-size: 12px; color: #64748b; text-align: center;">
                Position your document clearly within frame, then click Capture Photo.
            </p>
        </div>
        <div class="smart-modal-footer">
            <button type="button" class="btn-action" id="btn-switch-camera">Flip Camera</button>
            <button type="button" class="btn-action" id="btn-cancel-camera">Cancel</button>
            <button type="button" class="btn-toolbar btn-toolbar-primary" id="btn-snap-photo">Capture Photo</button>
            <button type="button" class="btn-toolbar btn-toolbar-primary" id="btn-use-photo" style="display: none;">Use Photo</button>
        </div>
    </div>
</div>

{{-- MODAL 2: DOCUMENT MANAGEMENT PICKER --}}
<div class="smart-modal-backdrop" id="docmgmt-modal" style="display: none;">
    <div class="smart-modal" style="max-width: 750px;">
        <div class="smart-modal-header">
            <h3>Select from Document Management</h3>
            <button type="button" class="btn-remove-file" id="btn-close-docmgmt">&times;</button>
        </div>
        <div class="smart-modal-body">
            <div style="margin-bottom: 14px;">
                <input
                    type="text"
                    id="docmgmt-search"
                    class="field-input"
                    placeholder="Search documents by name, category, or TRN..."
                >
            </div>

            <div style="max-height: 320px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px;">
                <table class="items-table" id="docmgmt-table">
                    <thead>
                        <tr>
                            <th style="width: 30px;"></th>
                            <th>Document Name</th>
                            <th>Category</th>
                            <th>TRN Reference</th>
                            <th>Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentDocuments ?? [] as $doc)
                            <tr class="docmgmt-row" data-name="{{ $doc->original_name }}" data-trn="{{ $doc->transmittal?->tracking_number }}" data-category="{{ $doc->category }}">
                                <td>
                                    <input type="checkbox" class="docmgmt-checkbox" value="{{ $doc->id }}" data-doc-name="{{ $doc->original_name }}" data-doc-category="{{ $doc->category }}" data-doc-trn="{{ $doc->transmittal?->tracking_number }}">
                                </td>
                                <td><strong>{{ $doc->original_name }}</strong></td>
                                <td><span class="badge-confidence ocr">{{ strtoupper($doc->category) }}</span></td>
                                <td><span style="font-family: monospace; font-size: 11px;">{{ $doc->transmittal?->tracking_number ?? '—' }}</span></td>
                                <td>{{ number_format(($doc->size_bytes ?? 0) / 1024, 1) }} KB</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 24px; color: #94a3b8;">
                                    No previously archived documents available in Document Management.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="smart-modal-footer">
            <button type="button" class="btn-action" id="btn-cancel-docmgmt">Close</button>
            <button type="button" class="btn-toolbar btn-toolbar-primary" id="btn-apply-docmgmt">Attach Selected to Transmittal</button>
        </div>
    </div>
</div>

{{-- MODAL 3: IN-SYSTEM COMPRESSION CONFIRMATION MODAL --}}
<div class="smart-modal-backdrop" id="compress-modal" style="display: none; z-index: 9999;">
    <div class="smart-modal" style="max-width: 520px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
        <div class="smart-modal-header" style="border-bottom: 1px solid #fee2e2; background: #fff5f5; border-top-left-radius: 12px; border-top-right-radius: 12px; padding: 16px 20px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <h3 style="margin: 0; color: #991b1b; font-size: 16px; font-weight: 700;">File Exceeds Size Limit</h3>
            </div>
            <button type="button" class="btn-remove-file" id="btn-close-compress-modal" style="font-size: 18px;">&times;</button>
        </div>
        <div class="smart-modal-body" style="padding: 20px;">
            <p style="font-size: 14px; color: #374151; margin-top: 0; margin-bottom: 12px; line-height: 1.5;">
                The file you selected exceeds the maximum upload limit of <strong>50 MB</strong>:
            </p>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px;">
                <div id="compress-modal-filename" style="font-weight: 600; color: #0f172a; font-size: 13.5px; word-break: break-all;"></div>
                <div id="compress-modal-filesize" style="font-size: 12px; color: #dc2626; font-weight: 600; margin-top: 4px;"></div>
            </div>
            <div id="compress-modal-offer" style="font-size: 13px; color: #475569; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px; line-height: 1.45;">
                <strong style="color: #1d4ed8; display: block; margin-bottom: 4px;">Direct In-System Compression Available</strong>
                Image compression can create a smaller copy. PDF compression and ZIP extraction are currently unavailable. Originals are preserved.
            </div>
            <div id="compress-modal-cannot-compress" style="display: none; font-size: 13px; color: #991b1b; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px; line-height: 1.45;">
                This document exceeds 50 MB. You can use Compress &amp; Upload to reduce it automatically or upload it as a compressed ZIP archive.
            </div>
            <div id="compress-modal-loading" style="display: none; text-align: center; padding: 16px 0;">
                <div style="display: inline-block; width: 24px; height: 24px; border: 3px solid #bfdbfe; border-top-color: #2563eb; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
                <p style="margin: 8px 0 0; font-size: 13px; color: #2563eb; font-weight: 600;">Compressing file in-system, please wait...</p>
            </div>
        </div>
        <div class="smart-modal-footer" style="padding: 14px 20px; border-top: 1px solid #f1f5f9; background: #f8fafc; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 10px;">
            <button type="button" class="btn-action" id="btn-cancel-compress" style="padding: 8px 16px; font-size: 13px; font-weight: 600; border: 1px solid #cbd5e1; background: #ffffff; border-radius: 6px; cursor: pointer;">Cancel</button>
            <button type="button" class="btn-action" id="btn-upload-zip-modal" style="padding: 8px 16px; font-size: 13px; font-weight: 600; border: 1px solid #93c5fd; background: #eff6ff; color: #1d4ed8; border-radius: 6px; cursor: pointer;">Upload Compressed ZIP</button>
            <button type="button" class="btn-toolbar btn-toolbar-primary" id="btn-confirm-compress" style="padding: 8px 18px; font-size: 13px; font-weight: 600; border-radius: 6px; background: #2563eb; color: #ffffff; border: none; cursor: pointer;">Compress &amp; Upload</button>
        </div>
    </div>
</div>

{{-- JAVASCRIPT LOGIC --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('smart-upload-form');
    if (!window.ORDOFileTools?.UploadQueue || !window.ORDOFileTools?.prepareSubmission) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            alert('V2 upload safety tools are unavailable. Reload after the reviewed V2 assets are built.');
        });
        return;
    }
    const fileInput = document.getElementById('smart-document-input');
    const zipInput = document.getElementById('smart-zip-input');
    const dropZone = document.getElementById('smart-dropzone');
    const browseTrigger = document.getElementById('btn-browse-trigger');
    const cameraTrigger = document.getElementById('btn-camera-trigger');
    const docmgmtTrigger = document.getElementById('btn-docmgmt-trigger');
    const reanalyzeBtn = document.getElementById('btn-reanalyze');
    const clearAllBtn = document.getElementById('btn-clear-all');

    const progressContainer = document.getElementById('upload-progress-container');
    const progressFill = document.getElementById('progress-fill');
    const progressPercentage = document.getElementById('progress-percentage');
    const progressTitle = document.getElementById('progress-title');
    const progressStatusMsg = document.getElementById('progress-status-msg');

    const queueContainer = document.getElementById('smart-queue-container');
    const queueList = document.getElementById('files-queue-list');
    const queueCount = document.getElementById('queue-count');

    const itemsSection = document.getElementById('smart-items-section');
    const itemsTbody = document.getElementById('smart-items-tbody');
    const addItemBtn = document.getElementById('btn-add-item-row');

    const detailsSection = document.getElementById('smart-details-section');
    const confirmationSection = document.getElementById('smart-confirmation-section');
    const submitDraftBtn = document.getElementById('btn-submit-draft');

    // Header inputs
    const subjectInput = document.getElementById('subject-field');
    const referenceInput = document.getElementById('reference-field');
    const companyInput = document.getElementById('company-field');
    const addressInput = document.getElementById('address-field');
    const recipientNameInput = document.getElementById('recipient-name-field');
    const recipientEmailInput = document.getElementById('recipient-email-field');

    // Badges
    const badgeSubject = document.getElementById('badge-subject');
    const badgeReference = document.getElementById('badge-reference');
    const badgeCompany = document.getElementById('badge-company');
    const badgeAddress = document.getElementById('badge-address');
    const badgeRecipientName = document.getElementById('badge-recipient-name');
    const badgeRecipientEmail = document.getElementById('badge-recipient-email');

    // Modals
    const cameraModal = document.getElementById('camera-modal');
    const cameraVideo = document.getElementById('camera-video');
    const cameraCanvas = document.getElementById('camera-canvas');
    const cameraStatus = document.getElementById('camera-status');
    const btnSnapPhoto = document.getElementById('btn-snap-photo');
    const btnUsePhoto = document.getElementById('btn-use-photo');
    const btnSwitchCamera = document.getElementById('btn-switch-camera');
    const btnCloseCamera = document.getElementById('btn-close-camera');
    const btnCancelCamera = document.getElementById('btn-cancel-camera');

    const docmgmtModal = document.getElementById('docmgmt-modal');
    const docmgmtSearch = document.getElementById('docmgmt-search');
    const btnCloseDocmgmt = document.getElementById('btn-close-docmgmt');
    const btnCancelDocmgmt = document.getElementById('btn-cancel-docmgmt');
    const btnApplyDocmgmt = document.getElementById('btn-apply-docmgmt');

    // State
    const uploadQueue = new window.ORDOFileTools.UploadQueue();
    let selectedFiles = []; // Stable file identities plus review metadata
    let cameraStream = null;
    let cameraFacingMode = 'environment';
    let capturedPhotoBlob = null;
    let isAnalyzing = false;
    let ocrTask = Promise.resolve();
    let ocrRequested = false;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const uploadChunkBytes = Number(form.dataset.chunkBytes || 5242880);
    const maxOcrFiles = window.ORDOFileTools.uploadLimits.maxFiles;
    const maxUploadBytes = window.ORDOFileTools.uploadLimits.maxFileBytes;
    const formatUploadSize = (bytes) => {
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
    };
    const analyzeUrl = form.dataset.analyzeUrl || '/transmittals/analyze';
    const uploadStartUrl = form.dataset.uploadStartUrl || '/transmittals/uploads';

    /* ============================================================
       1. MULTI-FILE QUEUE & ACCUMULATION
    ============================================================ */
    function syncInputFiles() {
        selectedFiles = uploadQueue.unique(selectedFiles);
        uploadQueue.observe(selectedFiles);
        const transfer = new DataTransfer();
        selectedFiles.forEach(function (item) {
            if (item.file instanceof File) {
                transfer.items.add(item.file);
            }
        });
        fileInput.files = transfer.files;
    }

    async function compressImageFile(file, maxBytes) {
        return new Promise(function (resolve) {
            if (!file.type.startsWith('image/')) {
                resolve(file);
                return;
            }
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = function (event) {
                const img = new Image();
                img.src = event.target.result;
                img.onload = function () {
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;
                    const maxDim = 2400;
                    if (width > maxDim || height > maxDim) {
                        if (width > height) {
                            height = Math.round((height * maxDim) / width);
                            width = maxDim;
                        } else {
                            width = Math.round((width * maxDim) / height);
                            height = maxDim;
                        }
                    }
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    let quality = 0.85;
                    function attemptCompress() {
                        canvas.toBlob(function (blob) {
                            if (!blob) {
                                resolve(file);
                                return;
                            }
                            if (blob.size <= maxBytes || quality <= 0.3) {
                                const compressedFile = new File([blob], file.name.replace(/\.[^/.]+$/, '.jpg'), {
                                    type: 'image/jpeg',
                                    lastModified: Date.now()
                                });
                                resolve(compressedFile);
                            } else {
                                quality -= 0.15;
                                attemptCompress();
                            }
                        }, 'image/jpeg', quality);
                    }
                    attemptCompress();
                };
                img.onerror = function () {
                    resolve(file);
                };
            };
            reader.onerror = function () {
                resolve(file);
            };
        });
    }

    async function compressOversizedFile(file, maxBytes) {
        let compressionError = null;
        if (window.ORDOFileTools && typeof window.ORDOFileTools.compress === 'function') {
            try {
                const compressedByTool = await window.ORDOFileTools.compress(file, maxBytes);
                if (compressedByTool && compressedByTool.size <= maxBytes) {
                    return compressedByTool;
                }
            } catch (error) {
                compressionError = error;
            }
        }

        const ext = (file.name.split('.').pop() || '').toLowerCase();
        const isImage = file.type.startsWith('image/') || ['jpg', 'jpeg', 'png', 'tif', 'tiff'].includes(ext);
        const isPdf = file.type === 'application/pdf' || ext === 'pdf';
        if (isImage) {
            const imgCompressed = await compressImageFile(file, maxBytes);
            if (imgCompressed && imgCompressed.size <= maxBytes) {
                return imgCompressed;
            }
        }

        if (compressionError instanceof Error) throw compressionError;
        if (isPdf) throw new Error('This PDF could not be compressed in the browser. Choose a smaller or unencrypted PDF.');
        if (isImage) throw new Error('The image could not be reduced below the maximum while keeping it readable.');
        throw new Error('Automatic compression is available for PDF and image files. This file type must be reduced with another tool.');
    }

    // Compression Modal Elements
    const compressModal = document.getElementById('compress-modal');
    const compressModalFilename = document.getElementById('compress-modal-filename');
    const compressModalFilesize = document.getElementById('compress-modal-filesize');
    const compressModalOffer = document.getElementById('compress-modal-offer');
    const compressModalCannotCompress = document.getElementById('compress-modal-cannot-compress');
    const compressModalLoading = document.getElementById('compress-modal-loading');
    const btnCloseCompressModal = document.getElementById('btn-close-compress-modal');
    const btnCancelCompress = document.getElementById('btn-cancel-compress');
    const btnUploadZipModal = document.getElementById('btn-upload-zip-modal');
    const btnConfirmCompress = document.getElementById('btn-confirm-compress');

    function promptOversizeCompression(file) {
        return new Promise(function (resolve) {
            if (!compressModal) {
                resolve({ shouldCompress: false });
                return;
            }

            const fileMb = (file.size / (1024 * 1024)).toFixed(1);
            const limitMb = Math.round(maxUploadBytes / (1024 * 1024));

            compressModalFilename.textContent = file.name;
            compressModalFilesize.textContent = `Current size: ${fileMb} MB (Limit: ${limitMb} MB)`;

            compressModalOffer.style.display = 'block';
            compressModalCannotCompress.style.display = 'none';
            btnConfirmCompress.style.display = 'inline-flex';
            btnConfirmCompress.textContent = 'Compress & Upload';
            if (btnUploadZipModal) { btnUploadZipModal.style.display = 'none'; btnUploadZipModal.disabled = true; }

            compressModalLoading.style.display = 'none';
            compressModal.style.display = 'flex';

            function cleanup() {
                compressModal.style.display = 'none';
                compressModalLoading.style.display = 'none';
                btnConfirmCompress.removeEventListener('click', onConfirm);
                btnCancelCompress.removeEventListener('click', onCancel);
                btnCloseCompressModal.removeEventListener('click', onCancel);
                if (btnUploadZipModal) btnUploadZipModal.removeEventListener('click', onZip);
            }

            function onConfirm() {
                compressModalOffer.style.display = 'none';
                compressModalLoading.style.display = 'block';
                btnConfirmCompress.disabled = true;
                btnCancelCompress.disabled = true;
                if (btnUploadZipModal) btnUploadZipModal.disabled = true;
                setTimeout(function () {
                    btnConfirmCompress.disabled = false;
                    btnCancelCompress.disabled = false;
                    if (btnUploadZipModal) btnUploadZipModal.disabled = false;
                    cleanup();
                    resolve({ shouldCompress: true });
                }, 300);
            }

            function onZip() {
                cleanup();
                resolve({ shouldCompress: false, openZip: true });
            }

            function onCancel() {
                cleanup();
                resolve({ shouldCompress: false, cancelled: true });
            }

            btnConfirmCompress.addEventListener('click', onConfirm);
            btnCancelCompress.addEventListener('click', onCancel);
            btnCloseCompressModal.addEventListener('click', onCancel);
            if (btnUploadZipModal) btnUploadZipModal.addEventListener('click', onZip);
        });
    }

    function reportUploadError(error) {
        progressContainer.style.display = 'block';
        progressStatusMsg.className = 'progress-status-msg error';
        progressStatusMsg.setAttribute('role', 'alert');
        progressStatusMsg.textContent = error instanceof Error ? error.message : String(error);
    }

    function addFiles(files) {
        if (form.dataset.submitting === 'true') {
            return Promise.reject(new Error('Upload preparation is in progress. Wait before adding files.'));
        }
        const incoming = Array.from(files || []);
        if (!incoming.length) return Promise.resolve(0);
        return uploadQueue.trackAddition(async () => {
            const revision = uploadQueue.revision;
            const candidates = [];
            for (const original of incoming) {
                let file = original;
                if (!(file instanceof File)) throw new Error('Choose a valid file.');
                if (file.size > maxUploadBytes) {
                    const extension = (file.name.split('.').pop() || '').toLowerCase();
                    if (file.type === 'application/pdf' || extension === 'pdf') {
                        throw new Error('PDF compression is unavailable. Choose a PDF of at most 50 MiB.');
                    }
                    const choice = await promptOversizeCompression(file);
                    if (!choice.shouldCompress) {
                        throw new Error('No files from this selection were added. Select smaller files or clear the queue.');
                    }
                    file = await compressOversizedFile(file, maxUploadBytes);
                }
                candidates.push({
                    file, category: detectCategoryFromName(file.name), analysis: null,
                    duplicateWarning: false, matchedRecord: null, ocrStatus: 'pending',
                });
            }
            if (revision !== uploadQueue.revision) {
                throw new Error('The queue changed while files were being prepared. Select those files again.');
            }
            const previousCount = uploadQueue.unique(selectedFiles).length;
            selectedFiles = uploadQueue.admit(selectedFiles, candidates);
            syncInputFiles();
            renderQueue();
            updateSectionsVisibility();
            void triggerOcrAnalysis();
            return selectedFiles.length - previousCount;
        });
    }

    function detectCategoryFromName(name) {
        const lower = name.toLowerCase();
        if (lower.includes('receipt') || lower.includes('acknowledgment')) return 'receipt';
        if (lower.includes('proof') || lower.includes('transmission')) return 'proof';
        if (lower.includes('attachment') || lower.includes('appendix')) return 'attachment';
        return 'document';
    }

    function removeFile(id) {
        if (form.dataset.submitting === 'true') return;
        const index = selectedFiles.findIndex((item) => uploadQueue.identify(item) === id);
        if (index < 0) return;
        uploadQueue.clearError();
        selectedFiles.splice(index, 1);
        syncInputFiles();
        renderQueue();
        rebuildItemsTable();
        updateSectionsVisibility();
    }

    function clearAllFiles() {
        if (form.dataset.submitting === 'true') return;
        uploadQueue.clearError();
        // Invalidate in-flight additions even when the queue was already empty.
        uploadQueue.revision++;
        selectedFiles = [];
        syncInputFiles();
        renderQueue();
        rebuildItemsTable();
        updateSectionsVisibility();
    }

    browseTrigger.addEventListener('click', function () {
        fileInput.click();
    });

    fileInput.addEventListener('change', async function () {
        try { await addFiles(fileInput.files); }
        catch (error) { syncInputFiles(); reportUploadError(error); }
    });

    zipInput.disabled = true;
    zipInput?.addEventListener('change', async function () {
        const archive = zipInput.files?.[0];
        zipInput.value = '';
        if (!archive) return;
        try {
            if (!window.ORDOFileTools?.capabilities?.zipExtraction) {
                throw new Error('ZIP extraction is unavailable until the V2 archive helper and fflate dependency are integrated. Select files directly instead.');
            }
            // Future extraction must complete admission before any success message.
            const extractArchive = window.ORDOFileTools.extractArchive;
            const extracted = await extractArchive(archive, {
                maxBytes: maxUploadBytes, maxFiles: maxOcrFiles,
                maxTotalBytes: window.ORDOFileTools.uploadLimits.maxTotalBytes,
            });
            const added = await addFiles(extracted);
            progressStatusMsg.textContent = `${added} file(s) were admitted to the upload queue.`;
        } catch (error) {
            reportUploadError(error);
        }
    });

    clearAllBtn.addEventListener('click', clearAllFiles);
    reanalyzeBtn.addEventListener('click', triggerOcrAnalysis);

    /* ============================================================
       2. DRAG AND DROP (MULTIPLE FILES)
    ============================================================ */
    ['dragenter', 'dragover'].forEach(function (name) {
        dropZone.addEventListener(name, function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add('drag-over');
        });
    });

    ['dragleave', 'dragend'].forEach(function (name) {
        dropZone.addEventListener(name, function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('drag-over');
        });
    });

    dropZone.addEventListener('drop', async function (e) {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('drag-over');

        const dt = e.dataTransfer;
        if (dt && dt.files && dt.files.length > 0) {
            try { await addFiles(dt.files); }
            catch (error) { syncInputFiles(); reportUploadError(error); }
        }
    });

    // Window level drag prevention
    window.addEventListener('dragover', function (e) { e.preventDefault(); });
    window.addEventListener('drop', function (e) { e.preventDefault(); });

    /* ============================================================
       3. QUEUE RENDERING & CARDS
    ============================================================ */
    function renderQueue() {
        queueList.innerHTML = '';
        queueCount.textContent = selectedFiles.length;

        if (selectedFiles.length === 0) {
            queueContainer.style.display = 'none';
            dropZone.classList.remove('compact');
            return;
        }

        queueContainer.style.display = 'block';
        dropZone.classList.add('compact');

        selectedFiles.forEach(function (item, index) {
            uploadQueue.identify(item);
            const card = document.createElement('div');
            card.className = 'file-card';

            const ext = item.file.name.split('.').pop().toLowerCase();
            let extClass = 'file-badge-type';
            if (ext === 'pdf') extClass += ' pdf';
            else if (['doc', 'docx'].includes(ext)) extClass += ' doc';
            else if (['jpg', 'jpeg', 'png', 'tiff', 'tif'].includes(ext)) extClass += ' img';

            const fileMain = document.createElement('div');
            fileMain.className = 'file-card-main';
            const fileBadge = document.createElement('span');
            fileBadge.className = extClass;
            fileBadge.textContent = ext.slice(0, 4);

            const fileMeta = document.createElement('div');
            fileMeta.className = 'file-meta-col';
            const fileName = document.createElement('p');
            fileName.className = 'file-meta-name';
            fileName.title = item.file.name;
            fileName.textContent = item.file.name;

            const fileDetails = document.createElement('div');
            fileDetails.className = 'file-meta-details';
            const fileSize = document.createElement('span');
            fileSize.textContent = `${(item.file.size / 1024).toFixed(1)} KB`;
            const status = document.createElement('span');
            status.className = 'badge-confidence review';
            if (item.ocrStatus === 'running') {
                status.className = 'badge-confidence ocr';
                status.textContent = 'Analyzing OCR...';
            } else if (item.ocrStatus === 'completed') {
                status.className = item.matchedRecord ? 'badge-confidence matched' : 'badge-confidence ocr';
                status.textContent = item.matchedRecord
                    ? `Matched Record: ${item.matchedRecord.tracking_number || 'DB Match'}`
                    : 'OCR Extracted';
            } else if (item.ocrStatus === 'error') {
                status.textContent = item.ocrError || 'Review Required';
                status.title = 'OCR failed. Retry analysis or continue with manual review.';
            } else {
                status.textContent = 'Pending OCR';
            }
            fileDetails.append(fileSize, status);

            if (item.duplicateWarning) {
                const duplicate = document.createElement('span');
                duplicate.className = 'badge-confidence duplicate';
                duplicate.title = item.duplicateWarning;
                duplicate.textContent = item.duplicateWarning;
                fileDetails.append(duplicate);
            }
            fileMeta.append(fileName, fileDetails);
            fileMain.append(fileBadge, fileMeta);

            const actions = document.createElement('div');
            actions.className = 'file-card-actions';
            const categorySelect = document.createElement('select');
            categorySelect.className = 'file-category-select';
            categorySelect.dataset.fileId = item.id;
            categorySelect.setAttribute('aria-label', `Category for ${item.file.name}`);
            [['document', 'Document'], ['receipt', 'Receipt'], ['proof', 'Proof'], ['attachment', 'Attachment']]
                .forEach(([value, label]) => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = label;
                    option.selected = item.category === value;
                    categorySelect.append(option);
                });
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'btn-remove-file';
            removeButton.dataset.removeId = item.id;
            removeButton.title = 'Remove file';
            removeButton.setAttribute('aria-label', `Remove ${item.file.name}`);
            removeButton.textContent = '×';
            actions.append(categorySelect, removeButton);
            card.append(fileMain, actions);

            if (item.file.size > maxUploadBytes) {
                const assist = document.createElement('div');
                assist.className = 'upload-size-assist error';
                const heading = document.createElement('strong');
                heading.className = 'upload-limit-heading';
                heading.textContent = 'Upload Limit Reached';
                const message = document.createElement('p');
                message.className = 'upload-size-message error';
                message.setAttribute('role', 'alert');
                message.textContent = `This file exceeds the 50 MiB maximum. Choose a smaller file or use available image compression.`;
                const choose = document.createElement('span');
                choose.className = 'upload-limit-choice';
                choose.textContent = 'Choose an option';
                const compress = document.createElement('button');
                compress.type = 'button';
                compress.className = 'upload-compress-button';
                compress.textContent = 'Compress and use';
                compress.addEventListener('click', async function () {
                    if (form.dataset.submitting === 'true') return;
                    compress.disabled = true;
                    message.textContent = `Compressing ${item.file.name}...`;
                    const ticket = uploadQueue.beginOcr(item);
                    try {
                        await uploadQueue.trackAddition(async () => {
                            const compressed = await compressOversizedFile(item.file, maxUploadBytes);
                            if (!uploadQueue.isCurrent(selectedFiles, ticket)) throw new Error('The file changed while compression was running.');
                            const replacement = { ...item, file: compressed, analysis: null, duplicateWarning: false, matchedRecord: null, ocrStatus: 'pending' };
                            selectedFiles = uploadQueue.validate(selectedFiles.map((entry) => entry === item ? replacement : entry));
                            syncInputFiles();
                            renderQueue();
                            updateSectionsVisibility();
                            void triggerOcrAnalysis();
                        });
                    } catch (error) {
                        reportUploadError(error);
                        compress.disabled = false;
                    }
                });
                const archiveButton = document.createElement('button');
                archiveButton.type = 'button';
                archiveButton.className = 'upload-compress-button';
                archiveButton.textContent = 'ZIP extraction unavailable';
                archiveButton.disabled = true;
                archiveButton.addEventListener('click', () => zipInput?.click());
                const archiveHint = document.createElement('small');
                archiveHint.className = 'upload-limit-archive-hint';
                archiveHint.textContent = 'ZIP extraction is unavailable; select the source files directly.';
                assist.append(heading, message, choose, compress, archiveButton, archiveHint);
                card.append(assist);
            }

            queueList.appendChild(card);
        });

        // Category change listener
        queueList.querySelectorAll('.file-category-select').forEach(function (sel) {
            sel.addEventListener('change', function () {
                if (form.dataset.submitting === 'true') return;
                const item = selectedFiles.find((entry) => uploadQueue.identify(entry) === this.dataset.fileId);
                if (item) item.category = this.value;
            });
        });

        // Remove button listener
        queueList.querySelectorAll('[data-remove-id]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                removeFile(this.dataset.removeId);
            });
        });
    }

    function updateSectionsVisibility() {
        const hasFiles = selectedFiles.length > 0;
        clearAllBtn.style.display = hasFiles ? 'inline-flex' : 'none';
        reanalyzeBtn.style.display = hasFiles ? 'inline-flex' : 'none';
        itemsSection.style.display = hasFiles ? 'block' : 'none';
        detailsSection.style.display = hasFiles ? 'block' : 'none';
        confirmationSection.style.display = hasFiles ? 'flex' : 'none';
    }

    /* ============================================================
       4. OCR / AI EXTRACTION PER FILE & DUPLICATE DETECTION
    ============================================================ */
    function triggerOcrAnalysis() {
        if (isAnalyzing) { ocrRequested = true; return ocrTask; }
        ocrRequested = false;
        ocrTask = runOcrAnalysis();
        return ocrTask;
    }

    async function runOcrAnalysis() {
        if (isAnalyzing || selectedFiles.length === 0) return;
        try { uploadQueue.validate(selectedFiles); }
        catch (error) { reportUploadError(error); return; }
        const oversized = selectedFiles.filter((item) => item.file.size > maxUploadBytes);
        if (oversized.length) {
            progressContainer.style.display = 'block';
            progressStatusMsg.className = 'progress-status-msg error';
            progressStatusMsg.textContent = `${oversized.length} file(s) exceed the ${formatUploadSize(maxUploadBytes)} per-file maximum. Compress them from the file list before OCR.`;
            return;
        }
        isAnalyzing = true;

        progressContainer.style.display = 'block';
        progressFill.style.width = '10%';
        progressPercentage.textContent = '10%';
        progressTitle.textContent = 'Extracting Document Intelligence...';
        progressStatusMsg.className = 'progress-status-msg';
        progressStatusMsg.textContent = 'Sending documents to OCR engine for classification and metadata detection...';

        const ocrCandidates = selectedFiles.filter(function (item) {
            return /\.(pdf|jpe?g|png|tiff?)$/i.test(item.file.name);
        });

        if (ocrCandidates.length === 0) {
            progressFill.style.width = '100%';
            progressPercentage.textContent = '100%';
            progressStatusMsg.textContent = 'Non-OCR files attached. You can review details manually.';
            isAnalyzing = false;
            rebuildItemsTable();
            return;
        }

        let activeOcrTicket = null;
        try {
            let processed = 0;
            const total = Math.min(ocrCandidates.length, maxOcrFiles);

            for (let i = 0; i < total; i++) {
                const item = ocrCandidates[i];
                if (!selectedFiles.includes(item)) continue;
                const ticket = uploadQueue.beginOcr(item);
                activeOcrTicket = ticket;
                item.ocrStatus = 'running';
                renderQueue();

                let analysisData = null;

                // Support chunked upload if file > 20MB
                if (item.file.size > 20 * 1024 * 1024) {
                    progressStatusMsg.textContent = `Uploading large file chunks for ${item.file.name}...`;
                    const uploadId = await uploadResumableFile(ticket.file);
                    if (!uploadQueue.isCurrent(selectedFiles, ticket)) continue;
                    const analyzeRes = await fetch(`${uploadStartUrl}/${uploadId}/analyze`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' }
                    });
                    analysisData = await analyzeRes.json();
                    if (!analyzeRes.ok) throw new Error(analysisData.message || `OCR failed for ${item.file.name}`);
                } else {
                    const formData = new FormData();
                    formData.append('documents[]', ticket.file);

                    const res = await fetch(analyzeUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                        body: formData
                    });
                    const resJson = await res.json();
                    if (!res.ok) throw new Error(resJson.message || `OCR failed for ${item.file.name}`);
                    analysisData = resJson.documents ? resJson.documents[0] : resJson;
                }

                if (!uploadQueue.isCurrent(selectedFiles, ticket)) continue;
                if (analysisData) {
                    item.analysis = analysisData;
                    item.ocrStatus = 'completed';
                    if (analysisData.category && item.category === ticket.category) item.category = analysisData.category;
                    if (analysisData.matched_record) {
                        item.matchedRecord = analysisData.matched_record;
                        item.duplicateWarning = analysisData.matched_record.label + (analysisData.matched_record.tracking_number ? ` (${analysisData.matched_record.tracking_number})` : '');
                    }
                }

                processed++;
                const pct = Math.round((processed / total) * 90) + 10;
                progressFill.style.width = pct + '%';
                progressPercentage.textContent = pct + '%';
                progressStatusMsg.textContent = `Analyzed ${processed} of ${total} documents...`;
            }

            progressFill.style.width = '100%';
            progressPercentage.textContent = '100%';
            progressTitle.textContent = 'Analysis Completed!';
            progressStatusMsg.textContent = `Processed ${processed} document(s). Metadata extracted and review screen ready.`;

            // Auto-populate header details from extracted fields
            applyExtractedHeaderFields();

            // Auto-create List of Items rows
            rebuildItemsTable();

        } catch (err) {
            const failedItem = uploadQueue.isCurrent(selectedFiles, activeOcrTicket) ? activeOcrTicket.item : null;
            if (failedItem) {
                failedItem.ocrStatus = 'error';
                failedItem.ocrError = err instanceof Error ? err.message : 'OCR analysis failed.';
            }
            if (failedItem) reportUploadError(err);
        } finally {
            isAnalyzing = false;
            renderQueue();
            if (ocrRequested && selectedFiles.length) {
                ocrRequested = false;
                await runOcrAnalysis();
            }
        }
    }

    // Helper for large chunked uploads
    async function uploadResumableFile(file, restartCount = 0) {
        if (file.resumableUploadId) return file.resumableUploadId;

        const resumeKey = uploadQueue.resumeKey(file);
        const readJson = async (response) => response.json().catch(() => ({}));
        const forgetResume = () => {
            try { sessionStorage.removeItem(resumeKey); } catch { /* Session storage is optional. */ }
            delete file.resumableUploadId;
        };
        let upload = null;
        try {
            const saved = JSON.parse(sessionStorage.getItem(resumeKey) || 'null');
            if (saved && /^[0-9a-f-]{36}$/i.test(saved.upload_id)) upload = saved;
        } catch {
            upload = null;
        }

        if (upload) {
            const check = await fetch(`${uploadStartUrl}/${upload.upload_id}/complete`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            });
            const checked = await readJson(check);
            if (check.ok) {
                file.resumableUploadId = checked.upload_id;
                return checked.upload_id;
            }
            if (check.status !== 409 || checked.message !== 'Some upload chunks are missing.') {
                forgetResume();
                if (restartCount >= 1) throw new Error(checked.message || `Could not resume ${file.name}.`);
                return uploadResumableFile(file, restartCount + 1);
            }
        } else {
            const start = await fetch(uploadStartUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: file.name, size: file.size, type: file.type || 'application/octet-stream' }),
            });
            upload = await readJson(start);
            if (!start.ok) throw new Error(upload.message || `Could not start upload for ${file.name}.`);
            try {
                sessionStorage.setItem(resumeKey, JSON.stringify({
                    upload_id: upload.upload_id,
                    chunk_size: upload.chunk_size,
                    chunk_count: upload.chunk_count,
                }));
            } catch { /* Upload remains usable for the current page session. */ }
        }

        const uploadId = upload.upload_id;
        const chunkCount = Number(upload.chunk_count);
        const chunkSize = Number(upload.chunk_size || uploadChunkBytes);
        if (!/^[0-9a-f-]{36}$/i.test(uploadId) || !Number.isInteger(chunkCount) || chunkCount < 1 || !Number.isInteger(chunkSize) || chunkSize < 1) {
            forgetResume();
            throw new Error(`The server returned invalid upload details for ${file.name}.`);
        }

        progressContainer.style.display = 'block';
        for (let index = 0; index < chunkCount; index++) {
            const chunk = file.slice(index * chunkSize, Math.min(file.size, (index + 1) * chunkSize));
            let response = null;
            let result = {};
            for (let attempt = 0; attempt < 3; attempt++) {
                try {
                    const body = new FormData();
                    body.append('chunk', chunk, `${file.name}.part`);
                    response = await fetch(`${uploadStartUrl}/${uploadId}/chunks/${index}`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                        body,
                    });
                    result = await readJson(response);
                    if (response.ok || (response.status < 500 && response.status !== 429)) break;
                } catch (error) {
                    if (attempt === 2) throw error;
                }
                if (attempt < 2) await new Promise((resolve) => setTimeout(resolve, 300 * (attempt + 1)));
            }
            if (!response?.ok) {
                if (response?.status === 409 && restartCount < 1) {
                    forgetResume();
                    return uploadResumableFile(file, restartCount + 1);
                }
                throw new Error(result.message || `Upload chunk ${index + 1} failed for ${file.name}.`);
            }
            const percent = Math.round(((index + 1) / chunkCount) * 100);
            progressFill.style.width = `${percent}%`;
            progressPercentage.textContent = `${percent}%`;
            progressStatusMsg.textContent = `Uploading ${file.name}: ${index + 1} of ${chunkCount} chunks...`;
        }

        const complete = await fetch(`${uploadStartUrl}/${uploadId}/complete`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
        });
        const completed = await readJson(complete);
        if (!complete.ok) throw new Error(completed.message || `Verification failed for ${file.name}.`);
        if (completed.upload_id !== uploadId) throw new Error(`The upload identifier for ${file.name} changed unexpectedly.`);

        file.resumableUploadId = completed.upload_id;
        return completed.upload_id;
    }

    /* ============================================================
       5. APPLY EXTRACTED HEADER DETAILS & CONFIDENCE LABELS
    ============================================================ */
    function applyExtractedHeaderFields() {
        const analyses = selectedFiles.map(function (item) { return item.analysis; }).filter(Boolean);
        if (analyses.length === 0) return;

        function findField(key) {
            for (let i = 0; i < analyses.length; i++) {
                if (analyses[i].fields && analyses[i].fields[key]) {
                    return {
                        value: analyses[i].fields[key],
                        metadata: analyses[i].field_metadata ? analyses[i].field_metadata[key] : null
                    };
                }
            }
            return null;
        }

        // Subject
        const sub = findField('subject');
        if (sub && sub.value) {
            if (!subjectInput.value || subjectInput.value === 'Smart Upload Document') {
                subjectInput.value = sub.value;
                setConfidenceBadge(badgeSubject, 'autofilled', 'OCR detected');
            }
        } else if (!subjectInput.value && selectedFiles.length > 0) {
            const cleanName = selectedFiles[0].file.name.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ');
            subjectInput.value = cleanName.charAt(0).toUpperCase() + cleanName.slice(1);
            setConfidenceBadge(badgeSubject, 'filename', 'From filename');
        }

        // Reference
        const ref = findField('reference') || findField('unique_id');
        if (ref && ref.value && !referenceInput.value) {
            referenceInput.value = ref.value;
            setConfidenceBadge(badgeReference, 'autofilled', 'OCR detected');
        }

        // Company
        const comp = findField('company');
        if (comp && comp.value && !companyInput.value) {
            companyInput.value = comp.value;
            setConfidenceBadge(badgeCompany, 'autofilled', 'OCR detected');
        }

        // Address
        const addr = findField('recipient_address');
        if (addr && addr.value && !addressInput.value) {
            addressInput.value = addr.value;
            setConfidenceBadge(badgeAddress, 'autofilled', 'OCR detected');
        }

        // Recipient Name
        const recName = findField('recipient_name');
        if (recName && recName.value && !recipientNameInput.value) {
            recipientNameInput.value = recName.value;
            setConfidenceBadge(badgeRecipientName, 'autofilled', 'OCR detected');
        }

        // Recipient Email
        const recEmail = findField('recipient_email');
        if (recEmail && recEmail.value && !recipientEmailInput.value) {
            recipientEmailInput.value = recEmail.value;
            setConfidenceBadge(badgeRecipientEmail, 'autofilled', 'OCR - High');
        }
    }

    function setConfidenceBadge(badgeEl, type, text) {
        if (!badgeEl) return;
        badgeEl.className = 'badge-confidence ' + type;
        badgeEl.textContent = text;
    }

    // When user types in header fields, update badge to edited/user-reviewed
    [
        [subjectInput, badgeSubject],
        [referenceInput, badgeReference],
        [companyInput, badgeCompany],
        [addressInput, badgeAddress],
        [recipientNameInput, badgeRecipientName],
        [recipientEmailInput, badgeRecipientEmail]
    ].forEach(function ([input, badge]) {
        if (input && badge) {
            input.addEventListener('input', function () {
                if (input.value.trim().length > 0) {
                    setConfidenceBadge(badge, 'autofilled', 'User Verified');
                } else {
                    setConfidenceBadge(badge, 'review', 'Review Required');
                }
            });
        }
    });

    /* ============================================================
       6. AUTO-CREATE ONE LIST OF ITEMS ROW PER DETECTED DOCUMENT
    ============================================================ */
    function rebuildItemsTable() {
        itemsTbody.innerHTML = '';

        if (selectedFiles.length === 0) {
            return;
        }

        selectedFiles.forEach(function (item, index) {
            const fields = item.analysis && item.analysis.fields ? item.analysis.fields : {};
            const cleanName = item.file.name.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ');

            const particular = fields.subject || cleanName;
            const uniqueId = fields.unique_id || fields.reference || '';
            const qty = '1';
            const desc = fields.description || `${item.category.charAt(0).toUpperCase() + item.category.slice(1)} attachment (${item.file.name})`;
            const remarks = item.ocrStatus === 'completed' ? 'OCR detected' : 'Attached from Smart Upload';

            let sourceLabel = 'Needs review';
            let sourceClass = 'review';
            if (item.ocrStatus === 'completed') {
                sourceLabel = item.matchedRecord ? 'Matched record' : 'Extracted by OCR';
                sourceClass = item.matchedRecord ? 'matched' : 'ocr';
            }

            addItemRowElement({
                index: index,
                docIndex: index,
                fileId: uploadQueue.identify(item),
                fileName: item.file.name,
                particular: particular,
                uniqueId: uniqueId,
                qty: qty,
                desc: desc,
                remarks: remarks,
                sourceLabel: sourceLabel,
                sourceClass: sourceClass,
                sourceFileName: item.file.name
            });
        });
    }

    function addItemRowElement(data) {
        const row = document.createElement('tr');
        row.className = 'item-row';
        const rowIndex = itemsTbody.children.length;

        const addCell = (className) => {
            const cell = document.createElement('td');
            cell.className = className;
            row.append(cell);
            return cell;
        };
        const positionCell = addCell('col-pos');
        positionCell.textContent = String(rowIndex + 1);

        const fileCell = addCell('col-file');
        const fileLabel = document.createElement('span');
        fileLabel.className = 'file-attachment-pill';
        fileLabel.title = data.fileName || 'Custom Item';
        fileLabel.textContent = data.fileName
            ? `${data.fileName.slice(0, 15)}${data.fileName.length > 15 ? '...' : ''}`
            : 'Manual item';
        fileCell.append(fileLabel);
        const documentIndex = document.createElement('input');
        documentIndex.type = 'hidden';
        documentIndex.name = `items[${rowIndex}][document_index]`;
        documentIndex.value = data.docIndex !== undefined ? String(data.docIndex) : '';
        if (data.fileId) documentIndex.dataset.fileId = data.fileId;
        fileCell.append(documentIndex);

        const addTextInput = (cellClass, name, value, placeholder, required = false) => {
            const cell = addCell(cellClass);
            const input = document.createElement('input');
            input.type = 'text';
            input.name = `items[${rowIndex}][${name}]`;
            input.value = value ?? '';
            input.placeholder = placeholder;
            input.required = required;
            cell.append(input);
        };
        addTextInput('col-particular', 'particular', data.particular, 'Particular / item title', true);
        addTextInput('col-unique', 'unique_id', data.uniqueId, 'Ref / serial ID');
        addTextInput('col-qty', 'quantity', data.qty ?? '1', '1');
        addTextInput('col-desc', 'description', data.desc, 'Description / specifications');
        addTextInput('col-remarks', 'remarks', data.remarks || 'User reviewed', 'Remarks');

        const sourceCell = addCell('col-source');
        const sourceContent = document.createElement('div');
        sourceContent.className = 'item-source-cell';
        sourceCell.append(sourceContent);
        const actionCell = addCell('col-action');
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'btn-remove-file';
        removeButton.title = 'Delete row';
        removeButton.style.fontSize = '14px';
        removeButton.textContent = '×';
        actionCell.append(removeButton);

        const sourceBadge = document.createElement('span');
        sourceBadge.className = `badge-confidence ${['ocr', 'matched', 'review', 'autofilled'].includes(data.sourceClass) ? data.sourceClass : 'autofilled'}`;
        sourceBadge.textContent = data.sourceLabel || 'Manual';
        sourceContent.appendChild(sourceBadge);

        if (data.sourceFileName) {
            const sourceFile = document.createElement('small');
            sourceFile.className = 'item-source-file';
            sourceFile.textContent = `OCR source file: ${data.sourceFileName}`;
            sourceFile.title = data.sourceFileName;
            sourceContent.appendChild(sourceFile);
        }

        // Row deletion
        row.querySelector('.col-action button').addEventListener('click', function () {
            if (form.dataset.submitting === 'true') return;
            row.remove();
            renumberItemRows();
        });

        itemsTbody.appendChild(row);
    }

    function renumberItemRows() {
        Array.from(itemsTbody.children).forEach(function (row, idx) {
            row.querySelector('.col-pos').textContent = idx + 1;
            row.querySelectorAll('input').forEach(function (input) {
                input.name = input.name.replace(/items\[\d+\]/, `items[${idx}]`);
            });
        });
    }

    addItemBtn.addEventListener('click', function () {
        addItemRowElement({
            index: itemsTbody.children.length,
            docIndex: '',
            fileName: '',
            particular: 'Additional Item ' + (itemsTbody.children.length + 1),
            uniqueId: '',
            qty: '1',
            desc: '',
            remarks: 'Manually added item',
            sourceLabel: 'User added',
            sourceClass: 'autofilled',
            sourceFileName: ''
        });
    });

    /* ============================================================
       7. CAMERA CAPTURE (PHOTO SCANNER)
    ============================================================ */
    cameraTrigger.addEventListener('click', async function () {
        cameraModal.style.display = 'flex';
        cameraStatus.textContent = 'Starting camera preview...';
        btnSnapPhoto.style.display = 'inline-flex';
        btnUsePhoto.style.display = 'none';

        try {
            await startCamera();
            cameraStatus.textContent = 'Position your document clearly, then click Capture Photo.';
        } catch (err) {
            console.error('Camera error:', err);
            cameraStatus.textContent = 'Could not access camera. Please allow camera permissions or browse files instead.';
        }
    });

    async function startCamera() {
        if (cameraStream) {
            cameraStream.getTracks().forEach(function (track) { track.stop(); });
        }
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: cameraFacingMode } },
            audio: false
        });
        cameraVideo.srcObject = cameraStream;
        await cameraVideo.play();
    }

    btnSwitchCamera.addEventListener('click', async function () {
        cameraFacingMode = cameraFacingMode === 'environment' ? 'user' : 'environment';
        await startCamera();
    });

    btnSnapPhoto.addEventListener('click', function () {
        if (!cameraVideo.videoWidth) return;

        cameraCanvas.width = cameraVideo.videoWidth;
        cameraCanvas.height = cameraVideo.videoHeight;
        const ctx = cameraCanvas.getContext('2d');
        ctx.drawImage(cameraVideo, 0, 0);

        cameraCanvas.toBlob(function (blob) {
            if (blob) {
                capturedPhotoBlob = blob;
                btnSnapPhoto.style.display = 'none';
                btnUsePhoto.style.display = 'inline-flex';
                cameraStatus.textContent = 'Photo captured! Click "Use Photo" to attach it, or close to retake.';
            }
        }, 'image/jpeg', 0.92);
    });

    btnUsePhoto.addEventListener('click', async function () {
        if (capturedPhotoBlob) {
            const fileName = `captured-doc-${Date.now()}.jpg`;
            const photoFile = new File([capturedPhotoBlob], fileName, { type: 'image/jpeg' });
            try {
                await addFiles([photoFile]);
                closeCamera();
            } catch (error) { reportUploadError(error); }
        }
    });

    function closeCamera() {
        if (cameraStream) {
            cameraStream.getTracks().forEach(function (track) { track.stop(); });
            cameraStream = null;
        }
        cameraVideo.srcObject = null;
        cameraModal.style.display = 'none';
    }

    btnCloseCamera.addEventListener('click', closeCamera);
    btnCancelCamera.addEventListener('click', closeCamera);

    /* ============================================================
       8. DOCUMENT MANAGEMENT PICKER
    ============================================================ */
    docmgmtTrigger.addEventListener('click', function () {
        docmgmtModal.style.display = 'flex';
    });

    function closeDocmgmt() {
        docmgmtModal.style.display = 'none';
    }

    btnCloseDocmgmt.addEventListener('click', closeDocmgmt);
    btnCancelDocmgmt.addEventListener('click', closeDocmgmt);

    // Filter search
    if (docmgmtSearch) {
        docmgmtSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.docmgmt-row').forEach(function (row) {
                const name = row.dataset.name.toLowerCase();
                const trn = (row.dataset.trn || '').toLowerCase();
                const cat = row.dataset.category.toLowerCase();
                const match = name.includes(q) || trn.includes(q) || cat.includes(q);
                row.style.display = match ? '' : 'none';
            });
        });
    }

    btnApplyDocmgmt.addEventListener('click', function () {
        const checked = document.querySelectorAll('.docmgmt-checkbox:checked');
        if (checked.length === 0) {
            alert('Please select at least one document from Document Management.');
            return;
        }

        checked.forEach(function (chk) {
            const name = chk.dataset.docName;
            const category = chk.dataset.docCategory || 'document';
            const trn = chk.dataset.docTrn;

            // Create a virtual file representation or row item
            const mockBlob = new Blob([`Document Management Archive: ${name} (TRN: ${trn})`], { type: 'application/pdf' });
            const virtualFile = new File([mockBlob], name, { type: 'application/pdf' });

            selectedFiles.push({
                file: virtualFile,
                category: category,
                analysis: {
                    fields: { subject: name.replace(/\.[^/.]+$/, ''), reference: trn },
                    field_metadata: { subject: { source: 'doc_management', confidence: 'high' } }
                },
                duplicateWarning: false,
                matchedRecord: { label: 'Doc Mgmt Record', tracking_number: trn, document_name: name },
                ocrStatus: 'completed'
            });
        });

        syncInputFiles();
        renderQueue();
        rebuildItemsTable();
        applyExtractedHeaderFields();
        updateSectionsVisibility();
        closeDocmgmt();
    });

    /* ============================================================
       9. FORM SUBMISSION (CREATE DRAFT & PERMANENT TRN)
    ============================================================ */
    form.addEventListener('submit', async function (e) {
        // Cancel native submission synchronously, before the first await.
        e.preventDefault();
        if (form.dataset.submitting === 'true') return;
        form.dataset.submitting = 'true';
        const originalButtonText = submitDraftBtn.textContent;
        const controls = [
            fileInput, zipInput, browseTrigger, cameraTrigger, docmgmtTrigger, clearAllBtn,
            reanalyzeBtn, btnUsePhoto, btnApplyDocmgmt, submitDraftBtn,
            ...queueList.querySelectorAll('button, select'), ...itemsTbody.querySelectorAll('button'),
        ].filter(Boolean);
        const disabledStates = controls.map((control) => [control, control.disabled]);
        controls.forEach((control) => { control.disabled = true; });
        submitDraftBtn.textContent = 'Preparing complete upload submission...';
        let submitted = false;
        const originalIndices = [];
        try {
            await window.ORDOFileTools.prepareSubmission({
                queue: uploadQueue,
                readItems: () => selectedFiles,
                waitForAnalysis: () => ocrTask,
                prepareResumable: uploadResumableFile,
                commit: (plan) => {
                    if (!subjectInput.value.trim()) {
                        subjectInput.focus();
                        throw new Error('Please provide a Transmittal Subject.');
                    }
                    if (!form.reportValidity()) throw new Error('Review the highlighted fields before submitting.');
                    const transfer = new DataTransfer();
                    const fragment = document.createDocumentFragment();
                    const append = (name, value) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = name;
                        input.value = value;
                        input.dataset.v2UploadField = 'true';
                        fragment.appendChild(input);
                    };
                    plan.direct.forEach((item) => {
                        transfer.items.add(item.file);
                        append('categories[]', item.category || 'document');
                        append('ocr_tokens[]', item.analysis?.analysis_token || '');
                    });
                    plan.resumable.forEach((item) => {
                        append('upload_ids[]', item.file.resumableUploadId);
                        append('upload_categories[]', item.category || 'document');
                        append('upload_ocr_tokens[]', item.analysis?.analysis_token || '');
                    });
                    itemsTbody.querySelectorAll('input[data-file-id]').forEach((input) => {
                        const index = plan.documentIndices.get(input.dataset.fileId);
                        if (index === undefined) throw new Error('An item refers to a removed file. Review the item list.');
                        originalIndices.push([input, input.value]);
                        input.value = String(index);
                    });
                    form.querySelectorAll('[data-v2-upload-field]').forEach((input) => input.remove());
                    form.appendChild(fragment);
                    fileInput.files = transfer.files;
                    fileInput.disabled = false; // Only direct files are successful form controls.
                    HTMLFormElement.prototype.submit.call(form);
                    submitted = true;
                },
            });
        } catch (error) {
            form.querySelectorAll('[data-v2-upload-field]').forEach((input) => input.remove());
            originalIndices.forEach(([input, value]) => { input.value = value; });
            syncInputFiles();
            reportUploadError(error);
        } finally {
            if (!submitted) {
                form.dataset.submitting = 'false';
                disabledStates.forEach(([control, disabled]) => { control.disabled = disabled; });
                submitDraftBtn.textContent = originalButtonText;
            }
        }
    });

});
</script>

@endsection
