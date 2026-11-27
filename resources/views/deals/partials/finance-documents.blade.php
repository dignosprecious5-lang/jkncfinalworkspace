@php
    $financeDocumentsService = app(\App\Services\DealFinanceDocuments::class);
    $canUploadFinanceDocuments = $financeDocumentsService->canUpload(auth()->user());
    $canViewFinanceDocuments = $financeDocumentsService->canView(auth()->user());
@endphp
@if($canViewFinanceDocuments)
<div class="finance-ledger-card" id="financeDocumentsCard">
    <div class="finance-header">
        <div class="finance-title-group">
            <h3 class="inquiry-main-title">Finance Documents</h3>
            <p class="inquiry-main-subtitle">Choose Quotation or Payment Notice before uploading a document.</p>
        </div>
        @if($canUploadFinanceDocuments)
        <button type="button" class="start-btn-primary" onclick="openFinanceDocumentUpload()">Upload Document</button>
        @endif
    </div>
    <p id="financeDocumentsError" role="alert" style="color: #b91c1c;"></p>
    <div id="financeDocumentsList" aria-live="polite"></div>
</div>
@if($canUploadFinanceDocuments)
<div id="financeDocumentUploadModal" class="inquiry-modal-backdrop" style="display: none;" onclick="if(event.target === this) closeFinanceDocumentUpload()" role="dialog" aria-modal="true" aria-labelledby="financeDocumentUploadTitle">
    <div class="inquiry-modal-card">
        <div class="inquiry-modal-head">
            <h3 id="financeDocumentUploadTitle" class="inquiry-modal-heading">Upload Finance Document</h3>
            <button type="button" class="inquiry-modal-close-btn" onclick="closeFinanceDocumentUpload()" aria-label="Close">&times;</button>
        </div>
        <form id="financeDocumentUploadForm" onsubmit="submitFinanceDocument(event)">
            <div class="inquiry-modal-body-content">
                <div class="inquiry-form-group">
                    <label for="financeDocumentType" class="inquiry-form-label">Document Type <span class="req-star">*</span></label>
                    <select id="financeDocumentType" class="inquiry-form-select" required onchange="changeFinanceDocumentType()">
                        <option value="">Choose document type</option>
                        <option value="quotation">Upload Quotation</option>
                        <option value="payment_notice">Upload Payment Notice</option>
                    </select>
                </div>
                <div id="financeDocumentFileGroup" class="inquiry-form-group" hidden>
                    <label for="financeDocumentFile" id="financeDocumentFileLabel" class="inquiry-form-label">Document</label>
                    <input type="file" id="financeDocumentFile" class="inquiry-form-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required disabled>
                    <p class="inquiry-main-subtitle">PDF, Word, JPG or PNG. Maximum size: 10 MB. The original file is preserved.</p>
                </div>
                <p id="financeDocumentUploadError" role="alert" style="color: #b91c1c;"></p>
            </div>
            <div class="inquiry-modal-foot">
                <button type="button" class="btn-modal-cancel" onclick="closeFinanceDocumentUpload()">Cancel</button>
                <button type="submit" id="financeDocumentSubmit" class="start-btn-primary" disabled>Upload Document</button>
            </div>
        </form>
    </div>
</div>
@endif
@endif
