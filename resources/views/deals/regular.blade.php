
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        {{ $projectNumber ?? 'Regular Workspace' }} - ORDO
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }

        button,
        input,
        textarea,
        select {
            font-family: inherit;
        }

        .page {
            min-height: 100vh;
        }

        /* -------------------------------------------------
           HEADER
        ------------------------------------------------- */

        .topbar {
            height: 64px;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            color: #111827;
            font-size: 20px;
        }

        .brand-mark {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: #111827;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            border: 1px solid #d1d5db;
            background: white;
            color: #374151;
            padding: 9px 14px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
        }

        .btn:hover {
            background: #f9fafb;
        }

        .btn-primary {
            background: #111827;
            color: white;
            border-color: #111827;
        }

        .btn-primary:hover {
            background: #1f2937;
        }

        /* -------------------------------------------------
           MAIN
        ------------------------------------------------- */

        .container {
            width: min(1450px, calc(100% - 40px));
            margin: 0 auto;
            padding: 26px 0 50px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 18px;
        }

        .breadcrumb a {
            color: #4b5563;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .page-title-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 22px;
        }

        .page-title h1 {
            margin: 0;
            font-size: 27px;
            line-height: 1.2;
            color: #111827;
        }

        .page-title p {
            margin: 7px 0 0;
            color: #6b7280;
            font-size: 14px;
        }

        .reference-box {
            text-align: right;
        }

        .reference-label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin-bottom: 3px;
        }

        .reference-number {
            font-size: 20px;
            font-weight: 800;
            color: #111827;
        }

        /* -------------------------------------------------
           AUTHORITY CARD
        ------------------------------------------------- */

        .authority-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .authority-header {
            padding: 16px 18px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .authority-title {
            font-size: 15px;
            font-weight: 750;
            color: #111827;
        }

        .authority-subtitle {
            font-size: 12px;
            color: #6b7280;
            margin-top: 3px;
        }

        .status {
            display: inline-flex;
            align-items: center;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .status-active {
            background: #ecfdf5;
            color: #047857;
        }

        .status-pending {
            background: #fffbeb;
            color: #b45309;
        }

        .authority-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
        }

        .authority-item {
            padding: 15px 16px;
            border-right: 1px solid #e5e7eb;
        }

        .authority-item:last-child {
            border-right: none;
        }

        .field-label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 5px;
        }

        .field-value {
            font-size: 13px;
            font-weight: 650;
            color: #111827;
            word-break: break-word;
        }

        .field-value.muted {
            color: #9ca3af;
            font-weight: 500;
        }

        /* -------------------------------------------------
           NEXT ACTION
        ------------------------------------------------- */

        .next-action {
            background: #111827;
            color: white;
            border-radius: 10px;
            padding: 17px 19px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .next-action-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .08em;
            opacity: .65;
            margin-bottom: 4px;
        }

        .next-action-title {
            font-size: 16px;
            font-weight: 750;
        }

        .next-action-description {
            margin-top: 4px;
            font-size: 12px;
            opacity: .75;
        }

        .next-action .btn {
            white-space: nowrap;
        }

        /* -------------------------------------------------
           PROCESS TIMELINE
        ------------------------------------------------- */

        .section-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .section-header {
            padding: 16px 18px;
            border-bottom: 1px solid #e5e7eb;
        }

        .section-header h2 {
            margin: 0;
            font-size: 15px;
            color: #111827;
        }

        .section-header p {
            margin: 4px 0 0;
            color: #6b7280;
            font-size: 12px;
        }

        .timeline {
            padding: 18px;
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }

        .step {
            position: relative;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            border-radius: 8px;
            padding: 13px 10px;
            min-height: 82px;
            cursor: pointer;
            text-align: left;
            transition: .15s ease;
        }

        .step:hover {
            border-color: #9ca3af;
            transform: translateY(-1px);
        }

        .step.completed {
            border-color: #a7f3d0;
            background: #ecfdf5;
        }

        .step.current {
            border-color: #111827;
            background: #111827;
            color: white;
        }

        .step.locked {
            opacity: .55;
            cursor: not-allowed;
        }

        .step-number {
            width: 23px;
            height: 23px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #374151;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 800;
            margin-bottom: 9px;
        }

        .completed .step-number {
            background: #059669;
            color: white;
        }

        .current .step-number {
            background: white;
            color: #111827;
        }

        .step-title {
            font-size: 12px;
            font-weight: 750;
        }

        .step-status {
            margin-top: 4px;
            font-size: 10px;
            opacity: .7;
        }

        /* -------------------------------------------------
           TABS
        ------------------------------------------------- */

        .tabs {
            display: flex;
            gap: 0;
            border-bottom: 1px solid #e5e7eb;
            overflow-x: auto;
            background: white;
        }

        .tab {
            border: 0;
            background: transparent;
            padding: 14px 17px;
            color: #6b7280;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            border-bottom: 2px solid transparent;
        }

        .tab:hover {
            color: #111827;
        }

        .tab.active {
            color: #111827;
            border-bottom-color: #111827;
        }

        .tab-content {
            padding: 20px;
        }

        .tab-panel {
            display: none;
        }

        .tab-panel.active {
            display: block;
        }

        /* -------------------------------------------------
           GRID / CARDS
        ------------------------------------------------- */

        .two-column {
            display: grid;
            grid-template-columns: 1.2fr .8fr;
            gap: 18px;
        }

        .three-column {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .info-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            background: #fff;
        }

        .info-card h3 {
            margin: 0 0 12px;
            font-size: 13px;
            color: #111827;
        }

        .info-card p {
            margin: 0;
            color: #6b7280;
            font-size: 12px;
            line-height: 1.55;
        }

        .info-list {
            display: grid;
            gap: 10px;
        }

        .info-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 15px;
            padding-bottom: 9px;
            border-bottom: 1px solid #f0f1f3;
        }

        .info-row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .info-row .label {
            color: #6b7280;
            font-size: 11px;
        }

        .info-row .value {
            text-align: right;
            color: #111827;
            font-size: 12px;
            font-weight: 650;
        }

        /* -------------------------------------------------
           DOCUMENT PREVIEW
        ------------------------------------------------- */

        .preview-layout {
            display: grid;
            grid-template-columns: 1fr 330px;
            gap: 18px;
        }

        .document-preview {
            min-height: 430px;
            border: 1px solid #dfe3e8;
            border-radius: 8px;
            background: #f3f4f6;
            padding: 25px;
            display: flex;
            justify-content: center;
        }

        .document-page {
            width: min(100%, 700px);
            min-height: 370px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,.07);
            padding: 35px;
        }

        .document-page .doc-header {
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .document-page .doc-title {
            font-size: 18px;
            font-weight: 800;
            color: #111827;
        }

        .document-page .doc-ref {
            margin-top: 4px;
            font-size: 11px;
            color: #6b7280;
        }

        .document-line {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            margin-bottom: 9px;
        }

        .document-line.short {
            width: 55%;
        }

        .document-line.medium {
            width: 75%;
        }

        .document-line.long {
            width: 95%;
        }

        .preview-actions {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            height: fit-content;
        }

        .preview-actions h3 {
            margin: 0 0 13px;
            font-size: 13px;
        }

        .action-stack {
            display: grid;
            gap: 8px;
        }

        .action-stack .btn {
            width: 100%;
            text-align: left;
        }

        /* -------------------------------------------------
           TABLE
        ------------------------------------------------- */

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #6b7280;
            background: #f9fafb;
            padding: 11px 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eef0f2;
            font-size: 12px;
            color: #374151;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .badge {
            display: inline-flex;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
        }

        .badge-green {
            background: #ecfdf5;
            color: #047857;
        }

        .badge-yellow {
            background: #fffbeb;
            color: #b45309;
        }

        .badge-gray {
            background: #f3f4f6;
            color: #6b7280;
        }

        /* -------------------------------------------------
           FORMS
        ------------------------------------------------- */

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .form-group {
            display: grid;
            gap: 6px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-size: 11px;
            font-weight: 700;
            color: #4b5563;
        }

        .form-control {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 9px 10px;
            font-size: 12px;
            outline: none;
            background: white;
        }

        .form-control:focus {
            border-color: #6b7280;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        /* -------------------------------------------------
           EMPTY / LOCKED
        ------------------------------------------------- */

        .locked-message {
            padding: 25px;
            border: 1px dashed #d1d5db;
            border-radius: 8px;
            text-align: center;
            background: #f9fafb;
        }

        .locked-message strong {
            display: block;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .locked-message span {
            color: #6b7280;
            font-size: 12px;
        }

        /* -------------------------------------------------
           RESPONSIVE
        ------------------------------------------------- */

        @media (max-width: 1100px) {
            .authority-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .authority-item:nth-child(3) {
                border-right: none;
            }

            .timeline {
                grid-template-columns: repeat(4, 1fr);
            }

            .preview-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 800px) {
            .container {
                width: min(100% - 24px, 700px);
                padding-top: 18px;
            }

            .topbar {
                padding: 0 14px;
            }

            .page-title-row {
                flex-direction: column;
            }

            .reference-box {
                text-align: left;
            }

            .authority-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .authority-item {
                border-bottom: 1px solid #e5e7eb;
            }

            .timeline {
                grid-template-columns: repeat(2, 1fr);
            }

            .two-column,
            .three-column,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .next-action {
                align-items: flex-start;
                flex-direction: column;
            }

            .next-action .btn {
                width: 100%;
            }
        }

        @media (max-width: 500px) {
            .authority-grid {
                grid-template-columns: 1fr;
            }

            .authority-item {
                border-right: none;
            }

            .timeline {
                grid-template-columns: 1fr;
            }

            .document-page {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<div class="page">

    {{-- =================================================
         TOP BAR
    ================================================== --}}

    <header class="topbar">

        <div class="brand">
            <div class="brand-mark">O</div>
            <span>ORDO</span>
        </div>

        <div class="top-actions">

            <a
                href="{{ route('deals.show', ['id' => $deal->id]) }}"
                class="btn"
                style="text-decoration:none;"
            >
                Back to Deal
            </a>

            <button
                type="button"
                class="btn"
                onclick="window.print()"
            >
                Print
            </button>

        </div>

    </header>


    <main class="container">

        {{-- =================================================
             BREADCRUMB
        ================================================== --}}

        <div class="breadcrumb">

            <a href="{{ route('deals.index') }}">
                Deals
            </a>

            <span>/</span>

            <a href="{{ route('deals.show', ['id' => $deal->id]) }}">
                {{ $dealCode }}
            </a>

            <span>/</span>

            <span>Regular Workspace</span>

        </div>


        {{-- =================================================
             PAGE TITLE
        ================================================== --}}

        <div class="page-title-row">

            <div class="page-title">

                <h1>
                    Regular Workspace
                </h1>

                <p>
                    Recurring service delivery workspace for
                    {{ $clientName ?: 'Client' }}.
                </p>

            </div>

            <div class="reference-box">

                <div class="reference-label">
                    Regular Reference
                </div>

                <div class="reference-number">
                    {{ $regularNumber }}
                </div>

            </div>

        </div>


        {{-- =================================================
             AUTHORITY & ASSIGNMENT
        ================================================== --}}

        <section class="authority-card">

            <div class="authority-header">

                <div>

                    <div class="authority-title">
                        Authority & Assignment
                    </div>

                    <div class="authority-subtitle">
                        Source records and personnel authorization for this Regular engagement.
                    </div>

                </div>

                <span class="status status-active">
                    Active Engagement
                </span>

            </div>


            <div class="authority-grid">

                <div class="authority-item">

                    <div class="field-label">
                        Source Deal
                    </div>

                    <div class="field-value">
                        {{ $dealCode }}
                    </div>

                </div>


                <div class="authority-item">

                    <div class="field-label">
                        Client / Account
                    </div>

                    <div class="field-value">
                        {{ $clientName ?: '—' }}
                    </div>

                </div>


                <div class="authority-item">

                    <div class="field-label">
                        START
                    </div>

                    <div class="field-value muted">
                        Pending START Link
                    </div>

                </div>


                <div class="authority-item">

                    <div class="field-label">
                        Service Memo
                    </div>

                    <div class="field-value muted">
                        Pending Service Memo
                    </div>

                </div>


                <div class="authority-item">

                    <div class="field-label">
                        Assignment
                    </div>

                    <div class="field-value">
                        {{ $deal->assigned_consultant ?: 'Unassigned' }}
                    </div>

                </div>


                <div class="authority-item">

                    <div class="field-label">
                        Acknowledgement
                    </div>

                    <div class="field-value muted">
                        Pending
                    </div>

                </div>

            </div>

        </section>


        {{-- =================================================
             CURRENT NEXT ACTION
        ================================================== --}}

        <section class="next-action">

            <div>

                <div class="next-action-label">
                    Next Required Action
                </div>

                <div class="next-action-title">
                    Prepare RSAT for Current Cycle
                </div>

                <div class="next-action-description">
                    Define the recurring scope, tasks, timeline and responsible personnel.
                </div>

            </div>

            <button
                type="button"
                class="btn"
                onclick="openTab('plan')"
            >
                Open Plan
            </button>

        </section>


        {{-- =================================================
             REGULAR PROCESS
        ================================================== --}}

        <section class="section-card">

            <div class="section-header">

                <h2>
                    Regular Service Delivery Cycle
                </h2>

                <p>
                    RSAT → Review → NTP → Execution → RSAT Report → Transmittal → Next Cycle
                </p>

            </div>


            <div class="timeline">

                <button
                    type="button"
                    class="step current"
                    onclick="openTab('plan')"
                >

                    <div class="step-number">
                        1
                    </div>

                    <div class="step-title">
                        RSAT
                    </div>

                    <div class="step-status">
                        Current
                    </div>

                </button>


                <button
                    type="button"
                    class="step"
                    onclick="openTab('plan')"
                >

                    <div class="step-number">
                        2
                    </div>

                    <div class="step-title">
                        Review
                    </div>

                    <div class="step-status">
                        Pending
                    </div>

                </button>


                <button
                    type="button"
                    class="step"
                    onclick="openTab('plan')"
                >

                    <div class="step-number">
                        3
                    </div>

                    <div class="step-title">
                        NTP
                    </div>

                    <div class="step-status">
                        Pending
                    </div>

                </button>


                <button
                    type="button"
                    class="step locked"
                    onclick="openTab('execution')"
                >

                    <div class="step-number">
                        4
                    </div>

                    <div class="step-title">
                        Execution
                    </div>

                    <div class="step-status">
                        Awaiting NTP
                    </div>

                </button>


                <button
                    type="button"
                    class="step locked"
                    onclick="openTab('report')"
                >

                    <div class="step-number">
                        5
                    </div>

                    <div class="step-title">
                        RSAT Report
                    </div>

                    <div class="step-status">
                        Awaiting Execution
                    </div>

                </button>


                <button
                    type="button"
                    class="step locked"
                    onclick="openTab('delivery')"
                >

                    <div class="step-number">
                        6
                    </div>

                    <div class="step-title">
                        Transmittal
                    </div>

                    <div class="step-status">
                        Awaiting Report
                    </div>

                </button>


                <button
                    type="button"
                    class="step"
                    onclick="openTab('cycles')"
                >

                    <div class="step-number">
                        7
                    </div>

                    <div class="step-title">
                        Next Cycle
                    </div>

                    <div class="step-status">
                        Continuation
                    </div>

                </button>

            </div>

        </section>


        {{-- =================================================
             WORKSPACE
        ================================================== --}}

        <section class="section-card">

            {{-- TABS --}}

            <div class="tabs">

                <button
                    type="button"
                    class="tab active"
                    data-tab="overview"
                    onclick="openTab('overview')"
                >
                    Overview
                </button>

                <button
                    type="button"
                    class="tab"
                    data-tab="plan"
                    onclick="openTab('plan')"
                >
                    Plan
                </button>

                <button
                    type="button"
                    class="tab"
                    data-tab="execution"
                    onclick="openTab('execution')"
                >
                    Execution
                </button>

                <button
                    type="button"
                    class="tab"
                    data-tab="report"
                    onclick="openTab('report')"
                >
                    Report
                </button>

                <button
                    type="button"
                    class="tab"
                    data-tab="delivery"
                    onclick="openTab('delivery')"
                >
                    Delivery
                </button>

                <button
                    type="button"
                    class="tab"
                    data-tab="cycles"
                    onclick="openTab('cycles')"
                >
                    Cycles
                </button>

                <button
                    type="button"
                    class="tab"
                    data-tab="documents"
                    onclick="openTab('documents')"
                >
                    Documents
                </button>

                <button
                    type="button"
                    class="tab"
                    data-tab="history"
                    onclick="openTab('history')"
                >
                    History
                </button>

            </div>


            <div class="tab-content">


                {{-- =================================================
                     OVERVIEW
                ================================================== --}}

                <div
                    class="tab-panel active"
                    id="tab-overview"
                >

                    <div class="two-column">

                        <div class="info-card">

                            <h3>
                                Engagement Information
                            </h3>

                            <div class="info-list">

                                <div class="info-row">

                                    <span class="label">
                                        Regular Reference
                                    </span>

                                    <span class="value">
                                        {{ $regularNumber }}
                                    </span>

                                </div>


                                <div class="info-row">

                                    <span class="label">
                                        Source Deal
                                    </span>

                                    <span class="value">
                                        {{ $dealCode }}
                                    </span>

                                </div>


                                <div class="info-row">

                                    <span class="label">
                                        Client
                                    </span>

                                    <span class="value">
                                        {{ $clientName ?: '—' }}
                                    </span>

                                </div>


                                <div class="info-row">

                                    <span class="label">
                                        Engagement Type
                                    </span>

                                    <span class="value">
                                        {{ $deal->engagement_type ?: 'Regular (Retainer) Engagement' }}
                                    </span>

                                </div>


                                <div class="info-row">

                                    <span class="label">
                                        Service Department
                                    </span>

                                    <span class="value">
                                        {{ $deal->service_department ?: '—' }}
                                    </span>

                                </div>


                                <div class="info-row">

                                    <span class="label">
                                        Assigned Consultant
                                    </span>

                                    <span class="value">
                                        {{ $deal->assigned_consultant ?: 'Unassigned' }}
                                    </span>

                                </div>

                            </div>

                        </div>


                        <div class="info-card">

                            <h3>
                                Current Cycle
                            </h3>

                            <div class="info-list">

                                <div class="info-row">

                                    <span class="label">
                                        Cycle
                                    </span>

                                    <span class="value">
                                        Cycle 01
                                    </span>

                                </div>


                                <div class="info-row">

                                    <span class="label">
                                        Period
                                    </span>

                                    <span class="value">
                                        Current Period
                                    </span>

                                </div>


                                <div class="info-row">

                                    <span class="label">
                                        RSAT
                                    </span>

                                    <span class="value">
                                        <span class="badge badge-yellow">
                                            Draft
                                        </span>
                                    </span>

                                </div>


                                <div class="info-row">

                                    <span class="label">
                                        NTP
                                    </span>

                                    <span class="value">
                                        <span class="badge badge-gray">
                                            Not Issued
                                        </span>
                                    </span>

                                </div>


                                <div class="info-row">

                                    <span class="label">
                                        Execution
                                    </span>

                                    <span class="value">
                                        <span class="badge badge-gray">
                                            Not Started
                                        </span>
                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     PLAN / RSAT
                ================================================== --}}

                <div
                    class="tab-panel"
                    id="tab-plan"
                >

                    <div class="preview-layout">

                        <div class="document-preview">

                            <div class="document-page">

                                <div class="doc-header">

                                    <div class="doc-title">
                                        RSAT — Regular Service Activity & Task
                                    </div>

                                    <div class="doc-ref">
                                        {{ $regularNumber }} · Cycle 01 · Draft
                                    </div>

                                </div>

                                <div class="document-line long"></div>
                                <div class="document-line medium"></div>
                                <br>

                                <div class="field-label">
                                    Client
                                </div>

                                <p style="font-size:13px;margin:0 0 18px;">
                                    {{ $clientName ?: 'Client' }}
                                </p>

                                <div class="field-label">
                                    Source Deal
                                </div>

                                <p style="font-size:13px;margin:0 0 18px;">
                                    {{ $dealCode }}
                                </p>

                                <div class="field-label">
                                    Scope of Work
                                </div>

                                <div class="document-line long"></div>
                                <div class="document-line long"></div>
                                <div class="document-line medium"></div>

                                <br>

                                <div class="field-label">
                                    Responsible Personnel
                                </div>

                                <div class="document-line medium"></div>
                                <div class="document-line short"></div>

                            </div>

                        </div>


                        <div class="preview-actions">

                            <h3>
                                RSAT Actions
                            </h3>

                            <div class="action-stack">

                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    onclick="alert('RSAT editor will be connected to the Regular backend workflow.')"
                                >
                                    Edit RSAT
                                </button>

                                <button
                                    type="button"
                                    class="btn"
                                    onclick="alert('RSAT preview opened.')"
                                >
                                    Preview RSAT
                                </button>

                                <button
                                    type="button"
                                    class="btn"
                                    onclick="alert('RSAT submitted for internal review.')"
                                >
                                    Submit for Review
                                </button>

                            </div>

                            <div style="margin-top:18px;">

                                <div class="field-label">
                                    Version
                                </div>

                                <div class="field-value">
                                    v1 Draft
                                </div>

                            </div>

                            <div style="margin-top:13px;">

                                <div class="field-label">
                                    Internal Review
                                </div>

                                <div class="field-value muted">
                                    Not yet submitted
                                </div>

                            </div>

                            <div style="margin-top:13px;">

                                <div class="field-label">
                                    Client NTP
                                </div>

                                <div class="field-value muted">
                                    Waiting for approved RSAT
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     EXECUTION
                ================================================== --}}

                <div
                    class="tab-panel"
                    id="tab-execution"
                >

                    <div class="three-column">

                        <div class="info-card">

                            <h3>
                                Execution Status
                            </h3>

                            <p>
                                Execution becomes available after the current RSAT has been reviewed and client NTP has been obtained.
                            </p>

                            <div style="margin-top:13px;">

                                <span class="badge badge-gray">
                                    Awaiting NTP
                                </span>

                            </div>

                        </div>


                        <div class="info-card">

                            <h3>
                                Activities
                            </h3>

                            <p>
                                Linked activities and tasks for the current Regular cycle will appear here.
                            </p>

                            <div style="margin-top:13px;">

                                <button
                                    type="button"
                                    class="btn"
                                    onclick="alert('Activities are locked until NTP is available.')"
                                >
                                    View Activities
                                </button>

                            </div>

                        </div>


                        <div class="info-card">

                            <h3>
                                Progress
                            </h3>

                            <p>
                                No execution progress has been recorded for this cycle.
                            </p>

                            <div style="margin-top:13px;">

                                <span class="badge badge-gray">
                                    0%
                                </span>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     REPORT
                ================================================== --}}

                <div
                    class="tab-panel"
                    id="tab-report"
                >

                    <div class="preview-layout">

                        <div class="document-preview">

                            <div class="document-page">

                                <div class="doc-header">

                                    <div class="doc-title">
                                        RSAT Report
                                    </div>

                                    <div class="doc-ref">
                                        {{ $regularNumber }} · Current Cycle
                                    </div>

                                </div>

                                <div class="document-line long"></div>
                                <div class="document-line medium"></div>

                                <br>

                                <div class="field-label">
                                    Cycle Summary
                                </div>

                                <div class="document-line long"></div>
                                <div class="document-line long"></div>
                                <div class="document-line medium"></div>

                                <br>

                                <div class="field-label">
                                    Completed Activities
                                </div>

                                <div class="document-line long"></div>
                                <div class="document-line medium"></div>

                                <br>

                                <div class="field-label">
                                    Deliverables / Outputs
                                </div>

                                <div class="document-line long"></div>
                                <div class="document-line short"></div>

                            </div>

                        </div>


                        <div class="preview-actions">

                            <h3>
                                Report Actions
                            </h3>

                            <div class="action-stack">

                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    onclick="alert('RSAT Report creation will be connected to the backend workflow.')"
                                >
                                    Create RSAT Report
                                </button>

                                <button
                                    type="button"
                                    class="btn"
                                    onclick="alert('Report preview opened.')"
                                >
                                    Preview Report
                                </button>

                                <button
                                    type="button"
                                    class="btn"
                                    onclick="alert('Report cannot be delivered until execution is complete.')"
                                >
                                    Mark Ready for Delivery
                                </button>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     DELIVERY
                ================================================== --}}

                <div
                    class="tab-panel"
                    id="tab-delivery"
                >

                    <div class="info-card">

                        <h3>
                            Transmittal
                        </h3>

                        <p style="margin-bottom:15px;">
                            Outputs must be delivered through a Transmittal before the current cycle can be treated as formally delivered.
                        </p>

                        <div class="info-list">

                            <div class="info-row">

                                <span class="label">
                                    RSAT Report
                                </span>

                                <span class="value">
                                    <span class="badge badge-yellow">
                                        Pending
                                    </span>
                                </span>

                            </div>


                            <div class="info-row">

                                <span class="label">
                                    Transmittal
                                </span>

                                <span class="value">
                                    <span class="badge badge-gray">
                                        Not Created
                                    </span>
                                </span>

                            </div>


                            <div class="info-row">

                                <span class="label">
                                    Receipt
                                </span>

                                <span class="value">
                                    <span class="badge badge-gray">
                                        Not Available
                                    </span>
                                </span>

                            </div>

                        </div>

                        <div style="margin-top:18px;">

                            <button
                                type="button"
                                class="btn btn-primary"
                                onclick="alert('Transmittal creation will use the existing Regular transmittal route.')"
                            >
                                Create Transmittal
                            </button>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     CYCLES
                ================================================== --}}

                <div
                    class="tab-panel"
                    id="tab-cycles"
                >

                    <div class="table-wrap">

                        <table>

                            <thead>

                                <tr>
                                    <th>Cycle</th>
                                    <th>Period</th>
                                    <th>RSAT</th>
                                    <th>Report</th>
                                    <th>Delivery</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>

                            </thead>

                            <tbody>

                                <tr>

                                    <td>
                                        Cycle 01
                                    </td>

                                    <td>
                                        Current Period
                                    </td>

                                    <td>
                                        Draft
                                    </td>

                                    <td>
                                        Pending
                                    </td>

                                    <td>
                                        Pending
                                    </td>

                                    <td>
                                        <span class="badge badge-yellow">
                                            Current
                                        </span>
                                    </td>

                                    <td>
                                        <button
                                            type="button"
                                            class="btn"
                                            onclick="openTab('plan')"
                                        >
                                            Open
                                        </button>
                                    </td>

                                </tr>


                                <tr>

                                    <td>
                                        —
                                    </td>

                                    <td>
                                        Future
                                    </td>

                                    <td>
                                        —
                                    </td>

                                    <td>
                                        —
                                    </td>

                                    <td>
                                        —
                                    </td>

                                    <td>
                                        <span class="badge badge-gray">
                                            Not Created
                                        </span>
                                    </td>

                                    <td>
                                        <button
                                            type="button"
                                            class="btn"
                                            onclick="alert('Next cycle becomes available after the current cycle report and transmittal are complete.')"
                                        >
                                            Next Cycle
                                        </button>
                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>


                {{-- =================================================
                     DOCUMENTS
                ================================================== --}}

                <div
                    class="tab-panel"
                    id="tab-documents"
                >

                    <div class="table-wrap">

                        <table>

                            <thead>

                                <tr>
                                    <th>Document</th>
                                    <th>Reference</th>
                                    <th>Version</th>
                                    <th>Status</th>
                                    <th>Source</th>
                                    <th></th>
                                </tr>

                            </thead>

                            <tbody>

                                <tr>

                                    <td>
                                        RSAT
                                    </td>

                                    <td>
                                        {{ $regularNumber }}
                                    </td>

                                    <td>
                                        v1
                                    </td>

                                    <td>
                                        <span class="badge badge-yellow">
                                            Draft
                                        </span>
                                    </td>

                                    <td>
                                        ORDO
                                    </td>

                                    <td>
                                        <button
                                            type="button"
                                            class="btn"
                                            onclick="openTab('plan')"
                                        >
                                            Preview
                                        </button>
                                    </td>

                                </tr>


                                <tr>

                                    <td>
                                        NTP
                                    </td>

                                    <td>
                                        —
                                    </td>

                                    <td>
                                        —
                                    </td>

                                    <td>
                                        <span class="badge badge-gray">
                                            Not Created
                                        </span>
                                    </td>

                                    <td>
                                        ORDO
                                    </td>

                                    <td>
                                        <button
                                            type="button"
                                            class="btn"
                                            onclick="alert('NTP becomes available after RSAT approval.')"
                                        >
                                            View
                                        </button>
                                    </td>

                                </tr>


                                <tr>

                                    <td>
                                        RSAT Report
                                    </td>

                                    <td>
                                        —
                                    </td>

                                    <td>
                                        —
                                    </td>

                                    <td>
                                        <span class="badge badge-gray">
                                            Not Created
                                        </span>
                                    </td>

                                    <td>
                                        ORDO
                                    </td>

                                    <td>
                                        <button
                                            type="button"
                                            class="btn"
                                            onclick="openTab('report')"
                                        >
                                            View
                                        </button>
                                    </td>

                                </tr>


                                <tr>

                                    <td>
                                        Transmittal
                                    </td>

                                    <td>
                                        —
                                    </td>

                                    <td>
                                        —
                                    </td>

                                    <td>
                                        <span class="badge badge-gray">
                                            Not Created
                                        </span>
                                    </td>

                                    <td>
                                        ORDO
                                    </td>

                                    <td>
                                        <button
                                            type="button"
                                            class="btn"
                                            onclick="openTab('delivery')"
                                        >
                                            View
                                        </button>
                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>


                    <div style="margin-top:16px;">

                        <button
                            type="button"
                            class="btn"
                            onclick="alert('Manual fallback upload will be connected to the document workflow.')"
                        >
                            Upload Supporting Document
                        </button>

                    </div>

                </div>


                {{-- =================================================
                     HISTORY
                ================================================== --}}

                <div
                    class="tab-panel"
                    id="tab-history"
                >

                    <div class="table-wrap">

                        <table>

                            <thead>

                                <tr>
                                    <th>Date / Time</th>
                                    <th>Event</th>
                                    <th>User</th>
                                    <th>Status</th>
                                </tr>

                            </thead>

                            <tbody>

                                <tr>

                                    <td>
                                         {{ optional($deal->created_at?->timezone('Asia/Manila'))->format('M d, Y h:i A') ?: '—' }}
                                    </td>

                                    <td>
                                        Regular workspace created from Deal
                                    </td>

                                    <td>
                                        {{ $deal->created_by ?: 'System' }}
                                    </td>

                                    <td>
                                        <span class="badge badge-green">
                                            Recorded
                                        </span>
                                    </td>

                                </tr>


                                <tr>

                                    <td>
                                        —
                                    </td>

                                    <td>
                                        RSAT draft created
                                    </td>

                                    <td>
                                        System
                                    </td>

                                    <td>
                                        <span class="badge badge-yellow">
                                            Pending
                                        </span>
                                    </td>

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </section>

    </main>

</div>


<script>

    function openTab(tabName) {

        /*
         * Hide all tab panels
         */
        document.querySelectorAll('.tab-panel').forEach(function(panel) {
            panel.classList.remove('active');
        });

        /*
         * Remove active state from tabs
         */
        document.querySelectorAll('.tab').forEach(function(tab) {
            tab.classList.remove('active');
        });

        /*
         * Show selected panel
         */
        const panel = document.getElementById('tab-' + tabName);

        if (panel) {
            panel.classList.add('active');
        }

        /*
         * Activate matching tab
         */
        const tab = document.querySelector(
            '.tab[data-tab="' + tabName + '"]'
        );

        if (tab) {
            tab.classList.add('active');
        }

        /*
         * Keep the user at the workspace
         */
        window.scrollTo({
            top: document.querySelector('.section-card').offsetTop - 80,
            behavior: 'smooth'
        });
    }

</script>

</body>
</html>