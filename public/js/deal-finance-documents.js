/* One Finance upload entry point; originals and server classifications remain authoritative. */
(function (global) {
    'use strict';
    const labels = {quotation: 'Quotation', payment_notice: 'Payment Notice'};
    const escape = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    const label = type => labels[type] || '';
    function previewKind(record) {
        const mime = String(record.mime_type || '').toLowerCase();
        if (mime === 'application/pdf') return 'pdf';
        if (['image/jpeg', 'image/png'].includes(mime)) return 'image';
        return 'download';
    }
    function previewHtml(record) {
        const title = label(record.document_type);
        const details = '<h2 style="font-size:18px; margin:0 0 8px;">' + title + '</h2>'
            + '<p style="overflow-wrap:anywhere;">' + escape(record.original_name) + '</p>'
            + '<p style="color:#64748b; font-size:12px;">'
            + escape(record.mime_type || 'Document') + (record.size != null ? ' | ' + Math.ceil(record.size / 1024) + ' KB' : '')
            + (record.uploaded_at ? ' | Uploaded ' + escape(new Date(record.uploaded_at).toLocaleString()) : '') + '</p>';
        if (!record.available) return details + '<p role="alert">The original uploaded file is no longer available.</p>';
        const download = '<p><a class="start-btn-primary" href="' + escape(record.download_url) + '">Download Original</a></p>';
        let content;
        if (previewKind(record) === 'pdf') {
            content = '<iframe title="' + title + ': ' + escape(record.original_name) + '" src="' + escape(record.url) + '" style="width:100%; height:70vh; min-height:420px; border:0;"></iframe>';
        } else if (previewKind(record) === 'image') {
            content = '<img src="' + escape(record.url) + '" alt="' + title + ': ' + escape(record.original_name) + '" style="display:block; max-width:100%; max-height:75vh; object-fit:contain; margin:auto;">';
        } else {
            content = '<p>This file type cannot be displayed directly in the browser. Download the original document to view it.</p>';
        }
        return '<div style="padding:16px; background:white;">' + details + download + content + '</div>';
    }
    if (typeof module !== 'undefined' && module.exports) module.exports = {previewKind, previewHtml, label};
    if (!global.document) return;
    const el = id => document.getElementById(id);
    const config = global.DEAL_BOOTSTRAP || {};
    let records = config.financeDocuments || [];
    let uploading = false;
    function render() {
        const list = el('financeDocumentsList');
        if (!list) return;
        list.innerHTML = Object.keys(labels).map(type => {
            const items = records.filter(record => record.document_type === type);
            return '<section aria-label="' + label(type) + '" style="padding:12px 0; border-top:1px solid #e2e8f0;"><h4 style="margin:0 0 10px;">' + label(type) + '</h4>'
                + (items.length ? items.map(record => '<div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:8px 0;">'
                    + '<div style="min-width:0; overflow-wrap:anywhere;"><strong>' + escape(record.original_name) + '</strong>'
                    + '<div style="font-size:12px; color:#64748b;">' + label(type)
                    + (record.size != null ? ' | ' + Math.ceil(record.size / 1024) + ' KB' : '')
                    + (record.uploaded_at ? ' | ' + escape(new Date(record.uploaded_at).toLocaleString()) : '')
                    + (!record.available ? ' ? File unavailable' : '') + '</div></div>'
                    + '<button type="button" class="start-btn-primary" data-document-id="' + escape(record.id) + '" onclick="previewFinanceDocument(this.dataset.documentId)">Preview</button></div>').join('')
                    : '<p class="inquiry-main-subtitle">No ' + label(type).toLowerCase() + ' documents uploaded.</p>') + '</section>';
        }).join('');
    }
    function preview(record) {
        if (!record || !label(record.document_type)) return false;
        const quotation = record.document_type === 'quotation';
        const modal = el(quotation ? 'previewQuotationModal' : 'previewPaymentNoticeModal');
        const paper = el(quotation ? 'previewQuotationPaperContainer' : 'previewPaymentNoticePaperContainer');
        if (!modal || !paper) return false;
        paper.innerHTML = previewHtml(record);
        modal.style.display = 'flex';
        return true;
    }
    global.previewFinanceDocument = id => preview(records.find(record => String(record.id) === String(id)));
    global.ordoFinanceDocuments = {
        previewLatest: type => preview(records.find(record => record.document_type === type)),
        render,
    };
    global.openFinanceDocumentUpload = function () {
        if (uploading || !el('financeDocumentUploadModal')) return;
        el('financeDocumentUploadForm').reset();
        el('financeDocumentUploadError').textContent = '';
        global.changeFinanceDocumentType();
        el('financeDocumentUploadModal').style.display = 'flex';
        el('financeDocumentType').focus();
    };
    global.closeFinanceDocumentUpload = function () {
        if (!uploading && el('financeDocumentUploadModal')) el('financeDocumentUploadModal').style.display = 'none';
    };
    global.changeFinanceDocumentType = function () {
        const type = el('financeDocumentType').value;
        const selected = Boolean(label(type));
        el('financeDocumentFileGroup').hidden = !selected;
        el('financeDocumentFile').disabled = !selected;
        el('financeDocumentFile').value = '';
        el('financeDocumentFileLabel').textContent = selected ? label(type) + ' File *' : 'Document';
        el('financeDocumentSubmit').disabled = !selected;
        el('financeDocumentUploadTitle').textContent = selected ? 'Upload ' + label(type) : 'Upload Finance Document';
        el('financeDocumentUploadError').textContent = '';
    };
    global.submitFinanceDocument = async function (event) {
        event.preventDefault();
        if (uploading) return;
        const type = el('financeDocumentType').value;
        const file = el('financeDocumentFile').files[0];
        const error = el('financeDocumentUploadError');
        error.textContent = '';
        if (!label(type)) { error.textContent = 'Select Quotation or Payment Notice first.'; return; }
        if (!file) { error.textContent = 'Choose the document to upload.'; return; }
        if (file.size > 10 * 1024 * 1024) { error.textContent = 'The document must not exceed 10 MB.'; return; }
        const data = new FormData();
        data.append('document_type', type);
        data.append('document', file);
        uploading = true;
        el('financeDocumentSubmit').disabled = true;
        el('financeDocumentType').disabled = true;
        el('financeDocumentFile').disabled = true;
        el('financeDocumentSubmit').textContent = 'Uploading...';
        try {
            const response = await fetch(config.financeDocumentsUploadUrl, {
                method:'POST', credentials:'same-origin', body:data,
                headers: {Accept:'application/json', 'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content || ''},
            });
            const result = await response.json();
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'Unable to upload document.');
            records = result.documents || [];
            render();
            uploading = false;
            global.closeFinanceDocumentUpload();
            global.previewFinanceDocument(result.id);
        } catch (failure) {
            error.textContent = failure.message || 'Unable to upload document. Please try again.';
        } finally {
            uploading = false;
            el('financeDocumentSubmit').disabled = false;
            el('financeDocumentType').disabled = false;
            el('financeDocumentFile').disabled = false;
            el('financeDocumentSubmit').textContent = 'Upload Document';
        }
    };
    document.addEventListener('keydown', event => { if (event.key === 'Escape') global.closeFinanceDocumentUpload(); });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', render);
    else render();
})(typeof window === 'undefined' ? globalThis : window);
