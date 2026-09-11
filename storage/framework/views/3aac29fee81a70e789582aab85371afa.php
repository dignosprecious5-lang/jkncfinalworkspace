

<?php $__env->startSection('content'); ?>

<?php
    $stages = [
        'Inquiry',
        'Qualification',
        'Consultation',
        'Proposal',
        'Negotiation',
        'Payment',
        'Activation',
        'Closed Won',
        'Closed Lost'
    ];

    $currentStage = $deal->pipeline_stage ?: 'Inquiry';

    $stageIndex = array_search($currentStage, $stages, true);
    $stageIndex = $stageIndex === false ? 0 : $stageIndex;

    $contactName = trim(
        implode(' ', array_filter([
            $deal->salutation,
            $deal->first_name,
            $deal->middle_initial,
            $deal->last_name,
            $deal->name_extension
        ]))
    ) ?: ($deal->primary_contact_name ?: 'Not provided');

    $serviceAreas = $deal->service_areas ?: [];
    $servicesProducts = $deal->services_products ?: [];
    $requirements = $deal->client_requirements ?: [];
    $support = $deal->professional_support_required ?: [];

    $display = fn ($value) =>
        filled($value) ? $value : '-';

    $money = fn ($value) =>
        filled($value)
            ? '₱' . number_format((float) $value, 2)
            : '-';

    $date = fn ($value) =>
        $value ? $value->format('M d, Y') : '-';

    $listValue = function ($value) {
        if (is_array($value)) {
            return count($value)
                ? implode(', ', $value)
                : '-';
        }

        return filled($value) ? $value : '-';
    };

    $totalValue =
        $deal->total_estimated_engagement_value
        ?: $deal->amount;

    $plannedStart =
        $deal->planned_start_date
        ?: null;

    $estimatedCompletion =
        $deal->estimated_completion_date
        ?: null;

?>


<style>

    /* =========================================================
       DEAL DETAIL PAGE
       ========================================================= */

    .deal-detail-page {
        min-height: calc(100vh - 60px);
        padding: 22px 28px 50px;
        background: #f1f5fb;
        color: #172033;
    }

    /* =========================================================
       BREADCRUMB
       ========================================================= */

    .deal-detail-breadcrumb {
        width: 100%;
        max-width: 1250px;
        margin: 0 auto 14px;
        background: #ffffff;
        border: 1px solid #dce5f0;
        border-radius: 10px;
        padding: 13px 17px;
        font-size: 13px;
        color: #475569;
    }

    .deal-detail-breadcrumb a {
        color: #334155;
        text-decoration: none;
        font-weight: 600;
    }

    .deal-detail-breadcrumb a:hover {
        color: #1d4ed8;
    }


    /* =========================================================
       HEADER
       ========================================================= */

    .deal-detail-header {
        width: 100%;
        max-width: 1250px;
        margin: 0 auto;
        background: #ffffff;
        border: 1px solid #dce5f0;
        border-radius: 10px;
        padding: 18px 17px 19px;
    }

    .deal-header-top {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .detail-title {
        margin: 0;
        font-size: 21px;
        line-height: 1.25;
        font-weight: 700;
        color: #07162d;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 9px;
        border-radius: 20px;
        background: #fff4c7;
        border: 1px solid #f4d46b;
        color: #9a6700;
        font-size: 10px;
        font-weight: 700;
    }

    .deal-stage-row {
        margin-top: 9px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .stage-badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        border-radius: 14px;
        background: #e8edff;
        color: #3457d5;
        font-size: 10px;
        font-weight: 700;
    }

    .masked-value {
        font-size: 12px;
        letter-spacing: 3px;
        color: #111827;
        font-weight: 700;
    }

    .eye-icon {
        color: #64748b;
        font-size: 12px;
    }


    /* =========================================================
       MAIN LAYOUT
       ========================================================= */

    .detail-layout {
        width: 100%;
        max-width: 1250px;
        margin: 14px auto 0;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 390px;
        gap: 14px;
        align-items: start;
    }

    .detail-main {
        min-width: 0;
    }


    /* =========================================================
       CARDS
       ========================================================= */

    .detail-section,
    .quick-actions,
    .related-card,
    .detail-tabs {
        background: #ffffff;
        border: 1px solid #dce5f0;
        border-radius: 10px;
    }

    .detail-section {
        padding: 16px 14px;
        margin-bottom: 14px;
        scroll-margin-top: 85px;
    }

    .detail-section h2 {
        margin: 0 0 15px;
        font-size: 17px;
        line-height: 1.25;
        color: #07162d;
        font-weight: 700;
    }


    /* =========================================================
       TWO COLUMN INFORMATION
       ========================================================= */

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 42px;
        row-gap: 15px;
    }

    .detail-item {
        min-width: 0;
    }

    .detail-label {
        margin-bottom: 3px;
        color: #64748b;
        font-size: 10px;
        line-height: 1.3;
    }

    .detail-value {
        color: #0f2747;
        font-size: 12px;
        line-height: 1.35;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .detail-value.muted-value {
        color: #64748b;
        font-weight: 500;
    }


    /* =========================================================
       QUICK ACTIONS
       ========================================================= */

    .quick-actions {
        position: sticky;
        top: 75px;
        padding: 14px;
    }

    .quick-actions-title {
        margin: 0 0 13px;
        color: #60718b;
        font-size: 13px;
        line-height: 1;
        letter-spacing: 1.2px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .quick-group {
        border: 1px solid #dce5f0;
        border-radius: 12px;
        padding: 9px;
        margin-bottom: 10px;
        background: #ffffff;
    }

    .quick-group:last-child {
        margin-bottom: 0;
    }

    .quick-group-title {
        margin: 0 0 8px;
        color: #91a0b5;
        font-size: 10px;
        letter-spacing: .8px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .quick-action {
        width: 100%;
        min-height: 34px;
        display: flex;
        align-items: center;
        gap: 7px;
        border: 1px solid #cbd8e8;
        border-radius: 7px;
        background: #ffffff;
        color: #40516a;
        padding: 7px 10px;
        margin-bottom: 7px;
        text-align: left;
        text-decoration: none;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: .15s ease;
    }

    .quick-action:last-child {
        margin-bottom: 0;
    }

    .quick-action:hover {
        border-color: #94b6e8;
        color: #1e4f95;
        background: #f8fbff;
    }

    .quick-action.primary {
        background: #244f91;
        border-color: #244f91;
        color: #ffffff;
    }

    .quick-action.primary:hover {
        background: #1d437d;
        color: #ffffff;
    }

    .stage-update-form {
        position: relative;
    }

    .stage-toggle {
        position: relative;
        text-align: left;
    }

    .stage-options {
        display: none;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }

    .stage-options.is-open {
        display: block;
    }

    .quick-icon {
        width: 14px;
        min-width: 14px;
        text-align: center;
        font-size: 11px;
    }


    /* =========================================================
       RELATED CONTACT
       ========================================================= */

    .related-card {
        padding: 15px;
        margin-top: 14px;
    }

    .related-card h2 {
        margin: 0 0 12px;
        color: #172033;
        font-size: 14px;
        font-weight: 700;
    }

    .contact-card {
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .contact-avatar {
        width: 34px;
        height: 34px;
        min-width: 34px;
        border-radius: 50%;
        background: #dce9ff;
        color: #3869d7;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
    }

    .contact-name {
        font-size: 13px;
        font-weight: 700;
        color: #172033;
        margin-bottom: 3px;
    }

    .contact-position {
        font-size: 10px;
        color: #64748b;
        margin-bottom: 7px;
    }

    .contact-line {
        color: #64748b;
        font-size: 10px;
        line-height: 1.6;
    }

    .contact-line i {
        width: 13px;
        color: #475569;
    }


    /* =========================================================
       TAGS
       ========================================================= */

    .tag-link {
        display: inline-block;
        color: #2563eb;
        font-size: 11px;
        font-weight: 600;
        text-decoration: none;
    }

    .tag-link:hover {
        text-decoration: underline;
    }


    /* =========================================================
       DEAL FORM PREVIEW
       ========================================================= */

    .deal-form-preview {
        border: 1px solid #1e293b;
        background: #ffffff;
        overflow: hidden;
    }

    .deal-form-title {
        text-align: center;
        font-size: 20px;
        font-weight: 800;
        color: #050b17;
        padding: 9px 8px;
        border-bottom: 1px solid #1e293b;
    }

    .deal-form-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
    }

    .form-cell {
        min-height: 48px;
        padding: 7px;
        border-right: 1px solid #1e293b;
        border-bottom: 1px solid #1e293b;
        font-size: 9px;
        color: #111827;
    }

    .form-cell:nth-child(4n) {
        border-right: 0;
    }

    .form-cell-label {
        font-size: 8px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .form-cell-value {
        font-size: 9px;
        line-height: 1.3;
    }

    .form-section-header {
        grid-column: 1 / -1;
        padding: 5px;
        text-align: center;
        color: #ffffff;
        background: #24498c;
        border-bottom: 1px solid #1e293b;
        font-size: 10px;
        font-weight: 700;
    }

    .form-full-cell {
        grid-column: 1 / -1;
        min-height: 48px;
        padding: 7px;
        border-bottom: 1px solid #1e293b;
        font-size: 9px;
    }

    .form-half-cell {
        grid-column: span 2;
        min-height: 48px;
        padding: 7px;
        border-right: 1px solid #1e293b;
        border-bottom: 1px solid #1e293b;
        font-size: 9px;
    }

    .form-half-cell:nth-last-child(1) {
        border-right: 0;
    }

    .form-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8px;
    }

    .form-table th,
    .form-table td {
        border: 1px solid #1e293b;
        padding: 5px;
        text-align: left;
        vertical-align: top;
    }

    .form-table th {
        font-weight: 800;
        text-align: center;
    }


    /* =========================================================
       START FORM
       ========================================================= */

    .start-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 13px;
    }

    .start-header h2 {
        margin-bottom: 4px;
    }

    .start-description {
        margin: 0;
        color: #64748b;
        font-size: 11px;
    }

    .start-actions {
        display: flex;
        gap: 7px;
        flex-wrap: wrap;
    }

    .start-action {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 8px 10px;
        border: 1px solid #cbd8e8;
        border-radius: 6px;
        background: #ffffff;
        color: #334155;
        text-decoration: none;
        font-size: 10px;
        font-weight: 600;
    }

    .start-action:hover {
        color: #1e4f95;
        border-color: #94b6e8;
    }

    .start-paper {
        border: 2px solid #24498c;
        padding: 10px;
        background: #ffffff;
    }

    .start-status-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px;
        border: 1px solid #dce5f0;
        background: #f8fafc;
        font-size: 10px;
    }

    .start-status {
        display: inline-block;
        padding: 3px 9px;
        border-radius: 15px;
        background: #e5edf7;
        color: #53657c;
        font-weight: 700;
    }

    .approval-required {
        color: #64748b;
        font-size: 9px;
        font-weight: 700;
    }

    .start-brand-title {
        text-align: center;
        padding: 12px 5px;
        font-family: Georgia, "Times New Roman", serif;
        color: #07162d;
    }

    .start-company {
        font-size: 19px;
        font-weight: 700;
        margin-bottom: 3px;
    }

    .start-title {
        font-size: 20px;
        line-height: 1.15;
        font-weight: 800;
    }

    .start-subtitle {
        margin-top: 4px;
        font-size: 8px;
        color: #64748b;
    }

    .start-info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        border: 1px solid #1e293b;
    }

    .start-info-cell {
        padding: 5px;
        border-right: 1px solid #1e293b;
        border-bottom: 1px solid #1e293b;
        font-size: 8px;
    }

    .start-info-cell:nth-child(2n) {
        border-right: 0;
    }

    .start-label {
        font-weight: 700;
        text-transform: uppercase;
        font-size: 7px;
        color: #334155;
    }

    .start-value {
        font-size: 8px;
        margin-top: 2px;
    }

    .start-section-title {
        padding: 5px;
        text-align: center;
        background: #24498c;
        color: #ffffff;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 13px;
        font-weight: 700;
        border: 1px solid #1e293b;
        border-top: 0;
    }

    .start-empty {
        padding: 13px;
        border: 1px solid #1e293b;
        border-top: 0;
        color: #7890aa;
        font-size: 9px;
        font-style: italic;
    }

    .start-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8px;
    }

    .start-table th,
    .start-table td {
        border: 1px solid #1e293b;
        padding: 5px;
        vertical-align: top;
    }

    .start-table th {
        text-align: center;
        background: #eef4ff;
        font-weight: 700;
    }

    .clearance-title {
        padding: 7px;
        border: 1px solid #1e293b;
        border-top: 0;
        text-align: center;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 16px;
        font-weight: 700;
    }

    .clearance-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
    }

    .clearance-cell {
        min-height: 100px;
        border: 1px solid #1e293b;
        border-top: 0;
        padding: 7px;
        text-align: center;
        font-size: 8px;
    }

    .signature-line {
        margin-top: 40px;
        border-top: 1px solid #1e293b;
        padding-top: 5px;
        font-size: 7px;
    }

    .record-row {
        display: grid;
        grid-template-columns: 2fr 1fr;
    }

    .record-cell {
        min-height: 65px;
        border: 1px solid #1e293b;
        border-top: 0;
        padding: 7px;
        font-size: 8px;
    }

    .rejection-box {
        border: 1px solid #1e293b;
        border-top: 0;
        padding: 7px;
    }

    .rejection-label {
        font-size: 8px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
    }

    .rejection-input {
        min-height: 35px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
    }


    /* =========================================================
       STAGE PROGRESS
       ========================================================= */

    .stage-progress {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 3px;
        overflow-x: auto;
        padding: 8px 4px 4px;
    }

    .stage-step {
        position: relative;
        flex: 1;
        min-width: 75px;
        text-align: center;
        color: #64748b;
        font-size: 9px;
        white-space: nowrap;
    }

    .stage-step:not(:last-child)::after {
        content: "";
        position: absolute;
        top: 11px;
        left: calc(50% + 12px);
        right: calc(-50% + 12px);
        height: 2px;
        background: #e2e8f0;
        z-index: 0;
    }

    .stage-step.complete:not(:last-child)::after {
        background: #2563eb;
    }

    .stage-dot {
        position: relative;
        z-index: 2;
        width: 28px;
        height: 28px;
        margin: 0 auto 6px;
        border-radius: 50%;
        border: 1px solid #dbe3ed;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        color: transparent;
        font-size: 12px;
        font-weight: 800;
    }

    .stage-step.complete .stage-dot {
        background: #2f6bea;
        border-color: #2f6bea;
        color: #ffffff;
    }

    .stage-step.complete .stage-dot::before {
        content: "✓";
    }

    .stage-step.current {
        color: #2563eb;
        font-weight: 700;
    }

    .stage-step.current .stage-dot {
        background: #ffffff;
        border: 3px solid #2563eb;
    }


    /* =========================================================
       TABS
       ========================================================= */

    .detail-tabs {
        padding: 5px;
        display: flex;
        gap: 3px;
        flex-wrap: wrap;
    }

    .detail-tabs a {
        color: #64748b;
        text-decoration: none;
        padding: 8px 10px;
        border-radius: 7px;
        font-size: 10px;
        font-weight: 500;
    }

    .detail-tabs a:first-child {
        background: #eff6ff;
        color: #2563eb;
    }

    .detail-tabs a:hover {
        background: #eff6ff;
        color: #2563eb;
    }

    .timeline-entry {
        margin-top: 12px;
        border: 1px solid #e5eaf1;
        border-radius: 9px;
        padding: 12px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .timeline-icon {
        width: 27px;
        height: 27px;
        min-width: 27px;
        border-radius: 50%;
        background: #e2edff;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
    }

    .timeline-title {
        font-size: 12px;
        font-weight: 700;
        color: #172033;
    }

    .timeline-meta {
        margin-top: 3px;
        color: #64748b;
        font-size: 9px;
    }


    /* =========================================================
       TABLES
       ========================================================= */

    .detail-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10px;
    }

    .detail-table th,
    .detail-table td {
        border: 1px solid #d7e0eb;
        padding: 8px;
        text-align: left;
        color: #475569;
    }

    .detail-table th {
        color: #334155;
        background: #f8fafc;
        font-weight: 700;
    }


    /* =========================================================
       RESPONSIVE
       ========================================================= */

    @media (max-width: 1100px) {
        .detail-layout {
            grid-template-columns: minmax(0, 1fr) 330px;
        }
    }

    @media (max-width: 900px) {
        .deal-detail-page {
            padding: 16px;
        }

        .detail-layout {
            grid-template-columns: 1fr;
        }

        .quick-actions {
            position: static;
        }

        .detail-grid {
            column-gap: 25px;
        }

        .clearance-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 650px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }

        .deal-form-grid {
            grid-template-columns: 1fr 1fr;
        }

        .form-cell:nth-child(4n) {
            border-right: 1px solid #1e293b;
        }

        .start-header {
            display: block;
        }

        .start-actions {
            margin-top: 10px;
        }

        .clearance-grid {
            grid-template-columns: 1fr;
        }

        .start-info-grid {
            grid-template-columns: 1fr;
        }

        .start-info-cell:nth-child(2n) {
            border-right: 1px solid #1e293b;
        }

        .stage-step {
            min-width: 65px;
        }
    }

</style>


<div class="deal-detail-page">

    

    <div class="deal-detail-breadcrumb">
        <a href="<?php echo e(route('deals.index')); ?>">
            ← Deals
        </a>

        <span style="margin:0 7px;">/</span>

        <strong>
            <?php echo e($display($deal->deal_code)); ?>

        </strong>
    </div>


    

    <header class="deal-detail-header">

        <div class="deal-header-top">

            <h1 class="detail-title">
                <?php echo e($display($deal->deal_code)); ?>

            </h1>

            <span class="status-badge">
                Pending
            </span>

        </div>

        <div class="deal-stage-row">

            <span class="stage-badge">
                <?php echo e($currentStage); ?>

            </span>

            <span class="masked-value">
                ••••••
            </span>

            <span class="eye-icon">
                ◉
            </span>

        </div>

    </header>


    

    <div class="detail-layout">

        <main class="detail-main">


            

            <section class="detail-section" id="deal-information">

                <h2>
                    Deal Information
                </h2>

                <div class="detail-grid">

                    <div class="detail-item">
                        <div class="detail-label">Deal Code</div>
                        <div class="detail-value">
                            <?php echo e($display($deal->deal_code)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Company Name</div>
                        <div class="detail-value">
                            <?php echo e($display($deal->company)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Contact Person Name</div>
                        <div class="detail-value">
                            <?php echo e($contactName); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Contact Person Position</div>
                        <div class="detail-value">
                            <?php echo e($display($deal->position)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Email Address</div>
                        <div class="detail-value">
                            <?php echo e($display($deal->email)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Contact Number</div>
                        <div class="detail-value">
                            <?php echo e($display($deal->mobile_number)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Client Type</div>
                        <div class="detail-value">
                            <?php echo e($display($deal->customer_type)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Industry</div>
                        <div class="detail-value">
                            <?php echo e($display($deal->companyRecord?->industry ?? null)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Qualification Result</div>
                        <div class="detail-value">
                            <?php echo e($display($deal->qualification_result ?? null)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Qualification Notes</div>
                        <div class="detail-value">
                            <?php echo e($display($deal->qualification_notes ?? null)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Deal Stage</div>
                        <div class="detail-value">
                            <?php echo e($currentStage); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Expected Close Date</div>
                        <div class="detail-value">
                            <?php echo e($date($deal->expected_close)); ?>

                        </div>
                    </div>

                </div>

            </section>


            

            <section class="detail-section" id="service-details">

                <h2>
                    Service and Engagement Details
                </h2>

                <div class="detail-grid">

                    <div class="detail-item">
                        <div class="detail-label">Service Type</div>
                        <div class="detail-value">
                            <?php echo e($display($deal->service_type ?? null)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Product Type</div>
                        <div class="detail-value">
                            <?php echo e($display($deal->product_type ?? null)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Engagement Type</div>
                        <div class="detail-value">
                            <?php echo e($display($deal->engagement_type)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Deal Code</div>
                        <div class="detail-value">
                            <?php echo e($display($deal->deal_code)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Engagement Duration</div>
                        <div class="detail-value">
                            <?php echo e(filled($deal->estimated_duration_days)
                                ? $deal->estimated_duration_days
                                : '-'); ?>

                        </div>
                    </div>

                </div>

            </section>


            

            <section class="detail-section" id="financial-details">

                <h2>
                    Financial Details
                </h2>

                <div class="detail-grid">

                    <div class="detail-item">
                        <div class="detail-label">
                            Deal Value
                        </div>

                        <div class="detail-value">
                            <?php echo e($money($deal->amount ?: $deal->total_estimated_engagement_value)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">
                            Pricing Model
                        </div>

                        <div class="detail-value">
                            <?php echo e($display($deal->pricing_model ?? $deal->engagement_type)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">
                            Payment Terms
                        </div>

                        <div class="detail-value">
                            <?php echo e($display($deal->payment_terms)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">
                            Commission Applicable
                        </div>

                        <div class="detail-value">
                            <?php echo e($display($deal->commission_applicable ?? null)); ?>

                        </div>
                    </div>

                </div>

            </section>


            

            <section class="detail-section">

                <h2>
                    Referral and Lead Source
                </h2>

                <div class="detail-grid">

                    <div class="detail-item">
                        <div class="detail-label">
                            Lead Source
                        </div>

                        <div class="detail-value">
                            <?php echo e($display($deal->client_search)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">
                            Referred By
                        </div>

                        <div class="detail-value">
                            <?php echo e($display($deal->referred_by)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">
                            Referral Type
                        </div>

                        <div class="detail-value">
                            <?php echo e($display($deal->referral_type ?? null)); ?>

                        </div>
                    </div>

                </div>

            </section>


            

            <section class="detail-section">

                <h2>
                    Deal Ownership and Team Assignment
                </h2>

                <div class="detail-grid">

                    <div class="detail-item">
                        <div class="detail-label">
                            Lead Consultant
                        </div>

                        <div class="detail-value">
                            <?php echo e($display($deal->lead_consultant)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">
                            Lead Associate
                        </div>

                        <div class="detail-value">
                            <?php echo e($display($deal->lead_associate)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">
                            Finance
                        </div>

                        <div class="detail-value">
                            <?php echo e($display($deal->finance)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">
                            Handling Team
                        </div>

                        <div class="detail-value">
                            <?php echo e($display($deal->handling_team ?? null)); ?>

                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">
                            Assigned Team Members
                        </div>

                        <div class="detail-value">
                            <?php echo e($display($deal->assigned_team_members ?? null)); ?>

                        </div>
                    </div>

                </div>

            </section>


            

            <section class="detail-section" id="deal-form-preview">

                <h2>
                    Deal Form Preview
                </h2>

                <div class="deal-form-preview">

                    <div class="deal-form-title">
                        Consulting &amp; Deal Form
                    </div>

                    <div class="deal-form-grid">

                        <div class="form-cell">
                            <div class="form-cell-label">Deal Name</div>
                            <div class="form-cell-value">
                                <?php echo e($display($deal->deal_code)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Stage</div>
                            <div class="form-cell-value">
                                <?php echo e($currentStage); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Engagement Type</div>
                            <div class="form-cell-value">
                                <?php echo e($display($deal->engagement_type)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Total Value</div>
                            <div class="form-cell-value">
                                <?php echo e($money($totalValue)); ?>

                            </div>
                        </div>


                        <div class="form-section-header">
                            Contact Information
                        </div>


                        <div class="form-cell">
                            <div class="form-cell-label">Salutation</div>
                            <div class="form-cell-value">
                                <?php echo e($display($deal->salutation)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">First Name</div>
                            <div class="form-cell-value">
                                <?php echo e($display($deal->first_name)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Middle Initial</div>
                            <div class="form-cell-value">
                                <?php echo e($display($deal->middle_initial)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Last Name</div>
                            <div class="form-cell-value">
                                <?php echo e($display($deal->last_name)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Name Extension</div>
                            <div class="form-cell-value">
                                <?php echo e($display($deal->name_extension)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Sex</div>
                            <div class="form-cell-value">
                                <?php echo e($display($deal->sex ?? null)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Date of Birth</div>
                            <div class="form-cell-value">
                                <?php echo e($date($deal->date_of_birth ?? null)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Email Address</div>
                            <div class="form-cell-value">
                                <?php echo e($display($deal->email)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Mobile Number</div>
                            <div class="form-cell-value">
                                <?php echo e($display($deal->mobile_number)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Position / Designation</div>
                            <div class="form-cell-value">
                                <?php echo e($display($deal->position)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Address</div>
                            <div class="form-cell-value">
                                <?php echo e($display($deal->address ?? null)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Company</div>
                            <div class="form-cell-value">
                                <?php echo e($display($deal->company)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">Company Address</div>
                            <div class="form-cell-value">
                                <?php echo e($display($deal->company_address ?? null)); ?>

                            </div>
                        </div>


                        <div class="form-section-header">
                            Service Identification
                        </div>


                        <div class="form-full-cell">
                            <div class="form-cell-label">
                                Service Area
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($listValue($serviceAreas)); ?>

                            </div>
                        </div>

                        <div class="form-full-cell">
                            <div class="form-cell-label">
                                Services
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($listValue($servicesProducts)); ?>

                            </div>
                        </div>


                        <div class="form-section-header">
                            Products
                        </div>


                        <div class="form-full-cell">
                            <div class="form-cell-label">
                                Products / Deliverables
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($listValue($servicesProducts)); ?>

                            </div>
                        </div>

                        <div class="form-full-cell">
                            <div class="form-cell-label">
                                Scope of Work
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($display($deal->scope_of_work)); ?>

                            </div>
                        </div>


                        <div class="form-section-header">
                            Client Requirements
                        </div>

                        <div class="form-full-cell" style="padding:0;">

                            <table class="form-table">

                                <thead>
                                    <tr>
                                        <th>Requirement</th>
                                        <th>Provided</th>
                                        <th>Pending</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php $__empty_1 = true; $__currentLoopData = $requirements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $requirement => $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                                        <tr>

                                            <td>
                                                <?php echo e(is_numeric($requirement)
                                                    ? $requirement + 1
                                                    : $requirement); ?>

                                            </td>

                                            <td style="text-align:center;">
                                                <?php echo e($status === 'provided' ? 'Yes' : '-'); ?>

                                            </td>

                                            <td style="text-align:center;">
                                                <?php echo e($status === 'pending' ? 'Yes' : '-'); ?>

                                            </td>

                                        </tr>

                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                                        <tr>
                                            <td colspan="3" style="text-align:center;">
                                                No requirements recorded.
                                            </td>
                                        </tr>

                                    <?php endif; ?>

                                </tbody>

                            </table>

                        </div>


                        <div class="form-section-header">
                            Fees &amp; Payment
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Professional Fee
                            </div>
                            <div class="form-cell-value">
                                <?php echo e($money($deal->est_professional_fee)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Government Fees
                            </div>
                            <div class="form-cell-value">
                                <?php echo e($money($deal->est_government_fee)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Service Support Fee
                            </div>
                            <div class="form-cell-value">
                                <?php echo e($money($deal->est_service_support_fee)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Discount
                            </div>
                            <div class="form-cell-value">
                                <?php echo e($money($deal->discount)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Total Value
                            </div>
                            <div class="form-cell-value">
                                <?php echo e($money($totalValue)); ?>

                            </div>
                        </div>

                        <div class="form-full-cell">
                            <div class="form-cell-label">
                                Payment Terms
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($display($deal->payment_terms)); ?>

                            </div>
                        </div>


                        <div class="form-section-header">
                            Timeline &amp; Assessment
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Planned Start Date
                            </div>
                            <div class="form-cell-value">
                                <?php echo e($date($plannedStart)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Estimated Duration
                            </div>
                            <div class="form-cell-value">
                                <?php echo e(filled($deal->estimated_duration_days)
                                    ? $deal->estimated_duration_days
                                    : '-'); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Estimated Completion Date
                            </div>
                            <div class="form-cell-value">
                                <?php echo e($date($estimatedCompletion)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Confirmed Delivery Date
                            </div>
                            <div class="form-cell-value">
                                <?php echo e($date($deal->confirmed_delivery_date)); ?>

                            </div>
                        </div>

                        <div class="form-half-cell">
                            <div class="form-cell-label">
                                Client Preferred Completion Date
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($date($deal->client_preferred_completion_date)); ?>

                            </div>
                        </div>

                        <div class="form-half-cell">
                            <div class="form-cell-label">
                                Confirmed Delivery Date
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($date($deal->confirmed_delivery_date)); ?>

                            </div>
                        </div>

                        <div class="form-full-cell">
                            <div class="form-cell-label">
                                Timeline Notes
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($display($deal->timeline_notes)); ?>

                            </div>
                        </div>

                        <div class="form-half-cell">
                            <div class="form-cell-label">
                                Service Complexity
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($display($deal->service_complexity)); ?>

                            </div>
                        </div>

                        <div class="form-half-cell">
                            <div class="form-cell-label">
                                Professional Support Required
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($listValue($support)); ?>

                            </div>
                        </div>

                        <div class="form-full-cell">
                            <div class="form-cell-label">
                                Notes / Explanation
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($display($deal->complexity_notes)); ?>

                            </div>
                        </div>


                        <div class="form-section-header">
                            Proposal &amp; Internal Assignment
                        </div>

                        <div class="form-half-cell">
                            <div class="form-cell-label">
                                Proposal Decision
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($display($deal->proposal_decision)); ?>

                            </div>
                        </div>

                        <div class="form-half-cell">
                            <div class="form-cell-label">
                                Decline Reason
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($display($deal->decline_reason ?? null)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Assigned Consultant
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($display($deal->assigned_consultant)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Assigned Associate
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($display($deal->assigned_associate)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Service Department / Unit
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($display($deal->service_department)); ?>

                            </div>
                        </div>


                        <div class="form-section-header">
                            Notes &amp; Approval
                        </div>

                        <div class="form-half-cell">
                            <div class="form-cell-label">
                                Consultant Notes
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($display($deal->consultant_notes)); ?>

                            </div>
                        </div>

                        <div class="form-half-cell">
                            <div class="form-cell-label">
                                Associate Notes
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($display($deal->associate_notes)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Prepared By
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($display($deal->owner_name)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Reviewed By
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($display($deal->reviewed_by)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Approval Date
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($date($deal->approval_date)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                Client Fullname &amp; Signature
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($display($deal->client_fullname_signature)); ?>

                            </div>
                        </div>

                        <div class="form-cell">
                            <div class="form-cell-label">
                                President
                            </div>

                            <div class="form-cell-value">
                                <?php echo e($display($deal->president)); ?>

                            </div>
                        </div>

                    </div>

                </div>

            </section>


            

            <section class="detail-section" id="start-form">

                <div class="start-header">

                    <div>
                        <h2>
                            START Form
                        </h2>

                        <p class="start-description">
                            Manage the project START intake directly from this deal.
                        </p>
                    </div>

                    <div class="start-actions">

                        <a href="#start-form"
                           class="start-action">
                            <span>▣</span>
                            Download START PDF
                        </a>

                        <a href="#start-form"
                           class="start-action">
                            <span>✎</span>
                            Edit START
                        </a>

                        <a href="#start-form"
                           class="start-action">
                            <span>↗</span>
                            Open Full Project
                        </a>

                    </div>

                </div>


                <div class="start-paper">

                    <div class="start-status-row">

                        <div>
                            START Status:
                            <span class="start-status">
                                Draft
                            </span>
                        </div>

                        <div class="approval-required">
                            ADMIN APPROVAL REQUIRED
                        </div>

                    </div>


                    <div class="start-brand-title">

                        <div class="start-company">
                            John Kelly
                            <br>
                            Company
                        </div>

                        <div class="start-title">
                            SERVICE TASK ACTIVATION AND
                            <br>
                            ROUTING TRACKER (START)
                        </div>

                        <div class="start-subtitle">
                            CASA-F-01-v1.0.03-16.26
                        </div>

                    </div>


                    <div class="start-info-grid">

                        <div class="start-info-cell">
                            <div class="start-label">
                                Client Name
                            </div>

                            <div class="start-value">
                                <?php echo e($contactName); ?>

                            </div>
                        </div>

                        <div class="start-info-cell">
                            <div class="start-label">
                                Product
                            </div>

                            <div class="start-value">
                                <?php echo e($display($deal->product_type ?? null)); ?>

                            </div>
                        </div>

                        <div class="start-info-cell">
                            <div class="start-label">
                                Business Name
                            </div>

                            <div class="start-value">
                                <?php echo e($display($deal->company)); ?>

                            </div>
                        </div>

                        <div class="start-info-cell">
                            <div class="start-label">
                                Services
                            </div>

                            <div class="start-value">
                                <?php echo e($listValue($servicesProducts)); ?>

                            </div>
                        </div>

                        <div class="start-info-cell">
                            <div class="start-label">
                                CONDEAL Ref No.
                            </div>

                            <div class="start-value">
                                <?php echo e($display($deal->deal_code)); ?>

                            </div>
                        </div>

                        <div class="start-info-cell">
                            <div class="start-label">
                                Date
                            </div>

                            <div class="start-value">
                                <?php echo e(now()->format('m/d/Y')); ?>

                            </div>
                        </div>

                        <div class="start-info-cell">
                            <div class="start-label">
                                Service Area
                            </div>

                            <div class="start-value">
                                <?php echo e($listValue($serviceAreas)); ?>

                            </div>
                        </div>

                        <div class="start-info-cell">
                            <div class="start-label">
                                Engagement Type
                            </div>

                            <div class="start-value">
                                <?php echo e($display($deal->engagement_type)); ?>

                            </div>
                        </div>

                        <div class="start-info-cell">
                            <div class="start-label">
                                Date Started
                            </div>

                            <div class="start-value">
                                <?php echo e($date($plannedStart)); ?>

                            </div>
                        </div>

                        <div class="start-info-cell">
                            <div class="start-label">
                                Date Completed
                            </div>

                            <div class="start-value">
                                <?php echo e($date($estimatedCompletion)); ?>

                            </div>
                        </div>

                    </div>


                    <div class="start-section-title">
                        Client Due Diligence (KYC) Documents
                    </div>

                    <div style="
                        padding:5px;
                        text-align:center;
                        background:#eef4ff;
                        border-left:1px solid #1e293b;
                        border-right:1px solid #1e293b;
                        font-size:8px;
                        font-weight:700;
                    ">
                        JURIDICAL ENTITY
                        <i>(Corporation / OPC / Partnership / Cooperative)</i>
                    </div>

                    <div class="start-empty">
                        No KYC requirements available for this business organization yet.
                    </div>


                    <div class="start-section-title">
                        Engagement-Specific Requirements
                    </div>

                    <table class="start-table">

                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Requirement / Document</th>
                                <th>Notes</th>
                                <th>Purpose</th>
                                <th>Provided By</th>
                                <th>Submitted To</th>
                                <th>Assigned To</th>
                                <th>Timeline</th>
                            </tr>
                        </thead>

                        <tbody>

                            <tr>
                                <td>1</td>

                                <td>
                                    Initial intake and supporting documents
                                </td>

                                <td>-</td>

                                <td>
                                    START intake
                                </td>

                                <td>
                                    Client
                                </td>

                                <td>
                                    Sales &amp; Marketing
                                </td>

                                <td>
                                    <?php echo e($display($deal->assigned_associate)); ?>

                                </td>

                                <td>
                                    To be scheduled
                                </td>
                            </tr>

                        </tbody>

                    </table>


                    <div class="clearance-title">
                        CLEARANCE
                    </div>

                    <div class="clearance-grid">

                        <div class="clearance-cell">
                            ASSIGNED TO REGULAR/PROJECT TEAM LEAD

                            <div class="signature-line">
                                Signature over Printed Name
                            </div>
                        </div>

                        <div class="clearance-cell">
                            LEAD CONSULTANT CONFIRMED

                            <div style="margin-top:20px;">
                                <?php echo e($display($deal->lead_consultant)); ?>

                            </div>

                            <div class="signature-line">
                                Signature over Printed Name
                            </div>
                        </div>

                        <div class="clearance-cell">
                            LEAD ASSOCIATE ASSIGNED

                            <div style="margin-top:20px;">
                                <?php echo e($display($deal->lead_associate)); ?>

                            </div>

                            <div class="signature-line">
                                Signature over Printed Name
                            </div>
                        </div>

                        <div class="clearance-cell">
                            SALES &amp; MARKETING

                            <div style="margin-top:20px;">
                                <?php echo e($display($deal->sales_marketing)); ?>

                            </div>

                            <div class="signature-line">
                                Signature over Printed Name
                            </div>
                        </div>

                    </div>


                    <div class="record-row">

                        <div class="record-cell">

                            <div style="text-align:center;">
                                <i>
                                    Record Custodian (Name and Signature)
                                </i>
                            </div>

                            <div class="signature-line">
                                Record Custodian
                            </div>

                        </div>

                        <div class="record-cell">

                            <div>
                                Date Recorded:
                                <?php echo e(now()->format('m/d/Y')); ?>

                            </div>

                            <div style="margin-top:20px;">
                                Date Signed:
                                __________________
                            </div>

                        </div>

                    </div>


                    <div class="rejection-box">

                        <div class="rejection-label">
                            REJECTION / HOLD REASON
                        </div>

                        <div class="rejection-input"></div>

                    </div>

                </div>

            </section>


            

            <section class="detail-section" id="stage-progress">

                <h2>
                    Deal Stage Progress
                </h2>

                <div class="stage-progress">

                    <?php $__currentLoopData = $stages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $stage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                        <div class="stage-step
                            <?php echo e($index < $stageIndex ? 'complete' : ''); ?>

                            <?php echo e($index === $stageIndex ? 'current' : ''); ?>">

                            <div class="stage-dot"></div>

                            <?php echo e($stage); ?>


                        </div>

                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                </div>

            </section>


            

            <section class="detail-section" id="activities">

                <div class="detail-tabs">

                    <a href="#activities">
                        Timeline
                    </a>

                    <a href="#notes">
                        Notes
                    </a>

                    <a href="#activities">
                        Activities
                    </a>

                    <a href="#emails">
                        Emails
                    </a>

                    <a href="#stage-history">
                        Stage History
                    </a>

                    <a href="#files">
                        Files
                    </a>

                    <a href="#products">
                        Products
                    </a>

                </div>


                <div class="timeline-entry">

                    <div class="timeline-icon">
                        ◉
                    </div>

                    <div>

                        <div class="timeline-title">
                            Deal created
                        </div>

                        <div class="timeline-meta">
                            <?php echo e(optional($deal->created_at?->timezone('Asia/Manila'))->format('Y-m-d, h:i A')); ?>

                            -
                            <?php echo e($display($deal->owner_name ?? null)); ?>

                        </div>

                    </div>

                </div>

            </section>


        </main>


        

        <aside>


            

            <div class="quick-actions">

                <h2 class="quick-actions-title">
                    Quick Actions
                </h2>


                <div class="quick-group">

                    <div class="quick-group-title">
                        Deal Actions
                    </div>


                    <a href="#start-form"
                       class="quick-action primary">

                        <span class="quick-icon">
                            ▣
                        </span>

                        START Form

                    </a>


                    <a href="<?php echo e(route('deals.proposal', $deal)); ?>"
                       class="quick-action">

                        <span class="quick-icon">
                            ●
                        </span>

                        View Proposal

                    </a>


                      <form method="POST"
                          action="<?php echo e(route('deals.stage.update', $deal)); ?>"
                          class="stage-update-form">

                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PATCH'); ?>

                        <input type="hidden"
                               name="return_to"
                               value="<?php echo e(url()->current()); ?>">

                        <div class="stage-control">
                            <button
                                type="button"
                                class="quick-action stage-toggle"
                                aria-expanded="false"
                                onclick="toggleStageOptions(this)">

                            <span class="quick-icon">
                                ▦
                            </span>

                                Update Stage

                            </button>

                            <select
                                class="stage-options"
                            name="pipeline_stage"
                            style="
                                width:100%;
                                min-height:34px;
                                border:1px solid #cbd8e8;
                                border-radius:7px;
                                padding:0 9px;
                                margin-bottom:7px;
                                color:#40516a;
                                font-size:11px;
                                background:#fff;
                            "
                            aria-label="Update deal stage"
                                onchange="this.form.submit()">

                            <?php $__currentLoopData = $stages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                <option
                                    value="<?php echo e($stage); ?>"
                                    <?php if($stage === $currentStage): echo 'selected'; endif; ?>
                                >
                                    <?php echo e($stage); ?>

                                </option>

                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                            </select>
                        </div>

                    </form>


                    <a href="#"
                        class="quick-action"
                        onclick="openDealDrawer('edit', <?php echo e($deal->id); ?>); return false;">

                       <span class="quick-icon"> 
                                   ✎ 
                          </span> 

                             Edit Deal 

                    </a>

                </div>


                <div class="quick-group">

                    <div class="quick-group-title">
                        Workspaces
                    </div>

                   <a href="<?php echo e(route('deals.project', $deal->id)); ?>"
                       class="quick-action">

                        <span class="quick-icon">
                            ⚑
                        </span>

                        Open Project Workspace

                    </a>

                </div>


                <div class="quick-group">

                    <div class="quick-group-title">
                        Exports
                    </div>

                    <a href="<?php echo e(route('deals.proposal', $deal)); ?>"
                       class="quick-action">

                        <span class="quick-icon">
                            ▣
                        </span>

                        Download PDF

                    </a>

                </div>

            </div>


            

            <div class="related-card">

                <h2>
                    Related Contact
                </h2>

                <div class="contact-card">

                    <div class="contact-avatar">

                        <?php
                            $initials = collect(
                                preg_split('/\s+/', trim($contactName))
                            )
                            ->filter()
                            ->take(2)
                            ->map(fn ($name) => strtoupper(substr($name, 0, 1)))
                            ->implode('');
                        ?>

                        <?php echo e($initials ?: 'C'); ?>


                    </div>


                    <div>

                        <div class="contact-name">
                            <?php echo e($contactName); ?>

                        </div>

                        <div class="contact-position">
                            <?php echo e($display($deal->position)); ?>

                        </div>

                        <div class="contact-line">
                            ✉ <?php echo e($display($deal->email)); ?>

                        </div>

                        <div class="contact-line">
                            ☎ <?php echo e($display($deal->mobile_number)); ?>

                        </div>

                    </div>

                </div>

            </div>


            

            <div class="related-card">

                <h2>
                    Tags
                </h2>

                <a href="#"
                   class="tag-link"
                   onclick="return false;">

                    + Add Tag

                </a>

            </div>


        </aside>

    </div>

</div>

<script>
    function toggleStageOptions(button) {
        const options = button.nextElementSibling;
        const expanded = options.classList.toggle('is-open');

        button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }
</script>

<?php echo $__env->make('components.deal-drawer', ['returnTo' => url()->current()], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\JKC\ordodeals\resources\views/deals/show.blade.php ENDPATH**/ ?>