<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Projects | John Kelly & Company</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f8f9fb;
            color: #14213d;
            min-height: 100vh;
        }

        /* =========================
           TOP HEADER
        ========================= */

        .top-header {
            height: 58px;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 18px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
        }

        .logo {
            width: 200px;
            line-height: 15px;
        }

        .logo-main {
            font-family: Georgia, "Times New Roman", serif;
            font-size: 15px;
            font-weight: 700;
            color: #171717;
        }

        .logo-sub {
            font-family: Georgia, "Times New Roman", serif;
            font-size: 13px;
            color: #171717;
        }

        .logo-sub span {
            color: #315bc8;
            font-size: 15px;
            font-weight: bold;
        }

        .global-search {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 510px;
            height: 34px;
            background: #f1f2f5;
            border-radius: 22px;
            display: flex;
            align-items: center;
            padding: 0 15px;
        }

        .global-search-icon {
            color: #91a0b6;
            margin-right: 10px;
            font-size: 15px;
        }

        .global-search input {
            width: 100%;
            border: none;
            outline: none;
            background: transparent;
            font-size: 12px;
            color: #4b5563;
        }

        .global-search input::placeholder {
            color: #8b98aa;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification {
            position: relative;
            font-size: 20px;
            color: #566275;
        }

        .notification-badge {
            position: absolute;
            top: -7px;
            right: -9px;
            background: #dc2626;
            color: white;
            font-size: 9px;
            font-weight: bold;
            min-width: 18px;
            height: 18px;
            padding: 0 4px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #eef0f4;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #374151;
            font-size: 13px;
            font-weight: 600;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;
            top: 58px;
            bottom: 0;
            left: 0;
            width: 59px;
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            z-index: 90;
            padding-top: 13px;
        }

        .sidebar-item {
            width: 36px;
            height: 36px;
            margin: 0 auto 11px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #687588;
            font-size: 16px;
            cursor: pointer;
            transition: 0.15s ease;
        }

        .sidebar-item:hover {
            background: #f0f4fa;
        }

        .sidebar-item.active {
            background: #eaf2ff;
            color: #245fd0;
            border: 1px solid #d6e5ff;
        }

        /* =========================
           MAIN CONTENT
        ========================= */

        .main {
            margin-left: 59px;
            padding-top: 58px;
            min-height: 100vh;
        }

        .content {
            max-width: 1410px;
            margin: 0 auto;
            padding: 25px 28px 40px;
        }

        /* =========================
           BACK BUTTON
        ========================= */

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            text-decoration: none;
            color: #334155;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 6px;
            padding: 4px 0;
        }

        .back-link:hover {
            color: #163b87;
        }

        .back-arrow {
            font-size: 18px;
            line-height: 1;
        }

        /* =========================
           TITLE AREA
        ========================= */

        .title-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 21px;
        }

        .title-area h1 {
            font-size: 27px;
            font-weight: 600;
            color: #101828;
            margin-bottom: 5px;
        }

        .title-area p {
            font-size: 12.5px;
            line-height: 19px;
            color: #61718a;
            max-width: 700px;
        }

        .create-button {
            border: none;
            background: #173b86;
            color: white;
            height: 40px;
            padding: 0 20px;
            border-radius: 22px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 3px;
        }

        .create-button:hover {
            background: #102f6e;
        }

        /* =========================
           SUMMARY CARDS
        ========================= */

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: #ffffff;
            border: 1px solid #e1e5ea;
            border-radius: 14px;
            min-height: 91px;
            padding: 20px 18px 15px;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.03);
        }

        .summary-label {
            font-size: 10px;
            color: #506078;
            font-weight: 600;
            letter-spacing: 0.2px;
            margin-bottom: 8px;
        }

        .summary-number {
            font-size: 27px;
            font-weight: 600;
            color: #152238;
        }

        .summary-card.sow .summary-number {
            color: #453bd1;
        }

        .summary-card.progress .summary-number {
            color: #1d4ed8;
        }

        .summary-card.active .summary-number {
            color: #bd5c14;
        }

        .summary-card.completed .summary-number {
            color: #00805f;
        }

        /* =========================
           PROJECT REGISTRY
        ========================= */

        .registry {
            background: #ffffff;
            border: 1px solid #e0e4e9;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.03);
        }

        .registry-header {
            min-height: 74px;
            padding: 17px 17px 14px;
            border-bottom: 1px solid #e4e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .registry-title h2 {
            font-size: 16px;
            font-weight: 600;
            color: #132238;
            margin-bottom: 6px;
        }

        .registry-title p {
            font-size: 12px;
            color: #61718a;
        }

        .project-search {
            width: 198px;
            height: 34px;
            border: 1px solid #dfe4eb;
            border-radius: 7px;
            display: flex;
            align-items: center;
            padding: 0 10px;
            background: #fff;
        }

        .project-search span {
            color: #8a99ad;
            font-size: 14px;
            margin-right: 8px;
        }

        .project-search input {
            border: none;
            outline: none;
            width: 100%;
            font-size: 11px;
            color: #344054;
        }

        .project-search input::placeholder {
            color: #8491a4;
        }

        /* =========================
           TABLE
        ========================= */

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .projects-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .projects-table th {
            background: #fbfcfd;
            height: 41px;
            text-align: left;
            padding: 0 10px;
            border-bottom: 1px solid #e5e7eb;
            color: #44546a;
            font-size: 11px;
            font-weight: 600;
        }

        .projects-table td {
            height: 58px;
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            color: #27364b;
            font-size: 11px;
            vertical-align: middle;
        }

        .projects-table tbody tr:hover {
            background: #fafcff;
        }

        .projects-table tbody tr:last-child td {
            border-bottom: none;
        }

        .checkbox-column {
            width: 48px;
            text-align: center !important;
        }

        .project-column {
            width: 27%;
        }

        .deal-column {
            width: 10%;
        }

        .company-column {
            width: 18%;
        }

        .phase-column {
            width: 10%;
        }

        .owner-column {
            width: 16%;
        }

        .target-column {
            width: 10%;
        }

        .action-column {
            width: 7%;
            text-align: center !important;
        }

        .table-checkbox {
            width: 14px;
            height: 14px;
            cursor: pointer;
        }

        .project-name {
            font-weight: 600;
            color: #14213d;
            font-size: 11.5px;
            margin-bottom: 4px;
        }

        .project-code {
            font-size: 10px;
            color: #607089;
        }

        .deal-number {
            color: #42546d;
            line-height: 17px;
            word-break: break-word;
        }

        .company-name {
            color: #32435b;
            line-height: 17px;
        }

        .owner-name {
            color: #34445c;
            line-height: 17px;
        }

        .target-date {
            white-space: nowrap;
            color: #42546b;
        }

        /* =========================
           PHASE BADGES
        ========================= */

        .phase {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 24px;
            padding: 0 11px;
            border-radius: 14px;
            font-size: 10px;
            font-weight: 500;
            white-space: nowrap;
        }

        .phase-sow {
            background: #f2f4ff;
            color: #3949c7;
            border: 1px solid #bfc8ff;
        }

        .phase-progress {
            background: #eef7ff;
            color: #075bd3;
            border: 1px solid #b9d8ff;
        }

        /* =========================
           VIEW BUTTON
        ========================= */

        .view-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 57px;
            height: 32px;
            border: 1px solid #e0e5eb;
            border-radius: 18px;
            background: #ffffff;
            color: #34445a;
            font-size: 11px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
        }

        .view-button:hover {
            background: #f5f8fc;
            border-color: #cbd5e1;
        }

        /* =========================
           FOOTER
        ========================= */

        .footer {
            padding: 44px 0 10px;
            color: #768398;
            font-size: 10.5px;
        }

        .no-results {
            display: none;
            text-align: center;
            padding: 35px;
            color: #7b8799;
            font-size: 12px;
        }

        /* =========================================================
           CREATE PROJECT MODAL
        ========================================================= */

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.48);
            z-index: 1000;
            display: none;
            align-items: stretch;
            justify-content: center;
        }

        .modal-overlay.show {
            display: flex;
        }

        .create-project-modal {
            width: calc(100% - 86px);
            height: 100vh;
            background: #ffffff;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: -5px 0 25px rgba(0, 0, 0, 0.08);
        }

        /* =========================
           MODAL HEADER
        ========================= */

        .modal-header {
            height: 72px;
            min-height: 72px;
            padding: 15px 18px;
            border-bottom: 1px solid #e5e7eb;
            background: #ffffff;
            position: relative;
            z-index: 5;
        }

        .modal-title {
            font-size: 17px;
            font-weight: 600;
            color: #182338;
            margin-bottom: 7px;
        }

        .modal-subtitle {
            font-size: 12px;
            color: #758195;
        }

        .modal-close {
            position: absolute;
            right: 17px;
            top: 21px;
            width: 28px;
            height: 28px;
            border: none;
            background: transparent;
            color: #667085;
            font-size: 20px;
            cursor: pointer;
            line-height: 1;
        }

        .modal-close:hover {
            color: #172b4d;
        }

        /* =========================
           MODAL BODY
        ========================= */

        .modal-body {
            flex: 1;
            min-height: 0;
            display: grid;
            grid-template-columns: 55% 45%;
            overflow: hidden;
        }

        /* =========================
           LEFT PREVIEW
        ========================= */

        .preview-panel {
            padding: 20px 15px 20px 22px;
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            min-width: 0;
            overflow: hidden;
        }

        .preview-container {
            height: 100%;
            border: 1px solid #dce4ee;
            border-radius: 20px;
            background: #fbfcfe;
            padding: 18px 15px 15px;
            overflow: hidden;
        }

        .preview-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 0 2px 12px;
        }

        .preview-label {
            font-size: 9px;
            letter-spacing: 3px;
            font-weight: 600;
            color: #687c9d;
            margin-bottom: 15px;
        }

        .preview-title {
            font-size: 16px;
            color: #182338;
            font-weight: 500;
        }

        .default-badge {
            border: 1px solid #dce3ec;
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 9px;
            color: #607089;
            background: #ffffff;
        }

        .document-scroll {
            height: calc(100% - 66px);
            overflow-y: auto;
            padding: 0 9px 0 2px;
        }

        .document-paper {
            background: #ffffff;
            border: 1px solid #d2dbe7;
            min-height: 850px;
            padding: 10px;
            box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05);
        }

        .document-inner {
            border: 1px solid #203b70;
            min-height: 830px;
            padding: 8px 10px;
            font-family: Georgia, "Times New Roman", serif;
            color: #111827;
        }

        .document-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 5px 5px 10px;
        }

        .document-logo {
            line-height: 14px;
        }

        .document-logo-main {
            font-size: 15px;
            font-weight: bold;
        }

        .document-logo-sub {
            font-size: 13px;
        }

        .document-logo-sub span {
            color: #315bc8;
            font-weight: bold;
        }

        .document-title {
            text-align: right;
        }

        .document-title strong {
            display: block;
            font-size: 19px;
            letter-spacing: 0.3px;
        }

        .document-title small {
            font-size: 9px;
            color: #31528d;
            letter-spacing: 1px;
        }

        .document-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-top: 1px solid #dce3eb;
            border-left: 1px solid #dce3eb;
        }

        .document-info-box {
            min-height: 53px;
            padding: 7px;
            border-right: 1px solid #dce3eb;
            border-bottom: 1px solid #dce3eb;
        }

        .document-info-box span {
            display: block;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8px;
            color: #526889;
            margin-bottom: 5px;
        }

        .document-info-box strong {
            font-size: 9px;
        }

        .doc-section {
            margin-top: 10px;
        }

        .doc-section-title {
            height: 22px;
            background: #193f7d;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.3px;
        }

        .doc-table {
            width: 100%;
            border-collapse: collapse;
        }

        .doc-table th,
        .doc-table td {
            border: 1px solid #26384f;
            height: 25px;
            font-size: 8px;
            padding: 4px;
        }

        .doc-table th {
            text-align: center;
        }

        .doc-empty {
            text-align: center;
            color: #61718a;
        }

        .signature-box {
            height: 74px;
            border: 1px solid #26384f;
            border-top: none;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 9px;
        }

        .signature-line {
            width: 62%;
            border-bottom: 1px solid #1f2937;
            margin: 12px auto 5px;
        }

        .approval-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border: 1px solid #26384f;
            border-top: none;
        }

        .approval-box {
            min-height: 84px;
            padding: 9px;
        }

        .approval-box:first-child {
            border-right: 1px solid #26384f;
        }

        .approval-label {
            font-style: italic;
            font-size: 8px;
            margin-bottom: 25px;
        }

        .approval-line {
            border-bottom: 1px solid #26384f;
            margin-bottom: 6px;
        }

        .approval-small {
            font-size: 7px;
            font-style: italic;
        }

        .records-box {
            border: 1px solid #26384f;
            border-top: none;
            min-height: 70px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            font-size: 8px;
        }

        .records-left {
            padding: 10px;
            border-right: 1px solid #26384f;
            line-height: 20px;
        }

        .records-right {
            display: flex;
            align-items: center;
            justify-content: center;
            font-style: italic;
        }

        /* =========================
           RIGHT FORM
        ========================= */

        .form-panel {
            min-width: 0;
            overflow-y: auto;
            padding: 18px 18px 80px;
            background: #ffffff;
        }

        .form-section {
            border: 1px solid #e1e5eb;
            border-radius: 15px;
            padding: 14px;
            margin-bottom: 17px;
            background: #ffffff;
        }

        .form-section.no-border {
            border: none;
            padding: 0;
        }

        .section-heading {
            font-size: 13px;
            color: #1d2939;
            font-weight: 600;
            margin-bottom: 11px;
        }

        .section-description {
            font-size: 10px;
            color: #7b8798;
            line-height: 15px;
            margin-top: -5px;
            margin-bottom: 12px;
        }

        /* Create type */

        .creation-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .creation-option {
            border: 1px solid #dfe4eb;
            border-radius: 14px;
            padding: 14px;
            cursor: pointer;
            min-height: 78px;
            transition: 0.15s ease;
        }

        .creation-option:hover {
            border-color: #9cb4df;
        }

        .creation-option.selected {
            border: 1.5px solid #234790;
            box-shadow: 0 0 0 1px rgba(35, 71, 144, 0.05);
        }

        .creation-option-title {
            font-size: 12px;
            font-weight: 600;
            color: #1d2939;
            margin-bottom: 7px;
        }

        .creation-option-description {
            font-size: 10px;
            color: #667085;
            line-height: 14px;
        }

        /* Customer type */

        .customer-type-box {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 9px;
        }

        .radio-option {
            height: 34px;
            border: 1px solid #dfe4eb;
            border-radius: 7px;
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 0 10px;
            font-size: 11px;
            color: #475467;
            cursor: pointer;
        }

        .radio-option input {
            accent-color: #1769e0;
        }

        /* Labels and fields */

        .field {
            margin-bottom: 13px;
        }

        .field:last-child {
            margin-bottom: 0;
        }

        .field label {
            display: block;
            font-size: 11px;
            font-weight: 500;
            color: #475467;
            margin-bottom: 7px;
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            border: 1px solid #d4dae3;
            border-radius: 10px;
            height: 39px;
            padding: 0 12px;
            outline: none;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #344054;
            background: #ffffff;
        }

        .field textarea {
            height: 74px;
            resize: vertical;
            padding-top: 10px;
            padding-bottom: 10px;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            border-color: #315bc8;
            box-shadow: 0 0 0 2px rgba(49, 91, 200, 0.08);
        }

        .field input::placeholder,
        .field textarea::placeholder {
            color: #98a2b3;
        }

        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 11px;
        }

        .one-column {
            display: grid;
            grid-template-columns: 1fr;
        }

        /* Service area */

        .service-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 7px;
        }

        .check-option {
            min-height: 35px;
            border: 1px solid #e0e4ea;
            border-radius: 7px;
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 0 9px;
            font-size: 10.5px;
            color: #475467;
            cursor: pointer;
        }

        .check-option:hover {
            background: #fafcff;
        }

        .check-option input {
            width: 14px;
            height: 14px;
            accent-color: #173f83;
            flex-shrink: 0;
        }

        /* Products */

        .products-note {
            background: #fafbfc;
            border: 1px dashed #d8dee7;
            border-radius: 10px;
            min-height: 47px;
            display: flex;
            align-items: center;
            padding: 10px;
            color: #98a2b3;
            font-size: 10px;
            margin-bottom: 12px;
        }

        .products-title {
            font-size: 9px;
            font-weight: 600;
            color: #667085;
            margin-bottom: 7px;
            letter-spacing: 0.2px;
        }

        /* =========================
           MODAL FOOTER
        ========================= */

        .modal-footer {
            height: 70px;
            min-height: 70px;
            border-top: 1px solid #e5e7eb;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            padding: 0 18px;
            position: relative;
            z-index: 10;
        }

        .modal-cancel {
            height: 40px;
            padding: 0 18px;
            border-radius: 22px;
            background: #ffffff;
            border: 1px solid #d7dde5;
            color: #475467;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
        }

        .modal-create {
            height: 40px;
            padding: 0 21px;
            border-radius: 22px;
            background: #123a87;
            border: 1px solid #123a87;
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
        }

        .modal-create:hover {
            background: #0e2e6b;
        }

        .modal-cancel:hover {
            background: #f8fafc;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1100px) {

            .global-search {
                width: 350px;
            }

            .summary-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .modal-body {
                grid-template-columns: 50% 50%;
            }

        }

        @media (max-width: 800px) {

            .global-search {
                display: none;
            }

            .content {
                padding: 20px 15px;
            }

            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .title-row {
                flex-direction: column;
                gap: 15px;
            }

            .create-project-modal {
                width: 100%;
            }

            .modal-body {
                grid-template-columns: 1fr;
            }

            .preview-panel {
                display: none;
            }

            .creation-options,
            .customer-type-box,
            .two-column,
            .service-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>
</head>

<body>

    <!-- =========================================================
         TOP HEADER
    ========================================================= -->

    <header class="top-header">

        <div class="logo">
            <div class="logo-main">
                John Kelly
            </div>

            <div class="logo-sub">
                <span>ↄ</span> Company
            </div>
        </div>

        <div class="global-search">

            <span class="global-search-icon">
                ⌕
            </span>

            <input
                type="text"
                placeholder="Search"
            >

        </div>

        <div class="header-right">

            @include('components.notifications')

            <div class="avatar">
                P
            </div>

        </div>

    </header>


    <!-- =========================================================
         SIDEBAR
    ========================================================= -->

    <aside class="sidebar">

        <div class="sidebar-item active" title="Projects">
            ▱
        </div>

        <div class="sidebar-item" title="Contacts">
            ♟
        </div>

        <div class="sidebar-item" title="Marketing">
            ⚑
        </div>

        <div class="sidebar-item" title="Records">
            ▣
        </div>

        <div class="sidebar-item" title="Documents">
            ▤
        </div>

        <div class="sidebar-item" title="Finance">
            ▱
        </div>

        <div class="sidebar-item" title="Users">
            ♟
        </div>

        <div class="sidebar-item" title="Settings">
            ◎
        </div>

        <div class="sidebar-item" title="Reports">
            ⌁
        </div>

        <div class="sidebar-item" title="Files">
            ▣
        </div>

        <div class="sidebar-item active" title="Project Management">
            ⚑
        </div>

    </aside>


    <!-- =========================================================
         MAIN
    ========================================================= -->

    <main class="main">

        <div class="content">

            <a
                href="{{ route('project.index') }}"
                class="back-link"
            >

                <span class="back-arrow">
                    ←
                </span>

                <span>
                    Back to Projects
                </span>

            </a>


            <div class="title-row">

                <div class="title-area">

                    <h1>
                        Project
                    </h1>

                    <p>
                        Approved project and hybrid deals automatically open here,
                        with SOW, NTP, reporting, delivery, and completion tracked
                        inside one record.
                    </p>

                </div>


                <!-- CREATE PROJECT BUTTON -->

                <button
                    type="button"
                    class="create-button"
                    onclick="openCreateProject()"
                >
                    Create Project
                </button>

            </div>


            <!-- =====================================================
                 SUMMARY
            ===================================================== -->

            <div class="summary-grid">

                <div class="summary-card">

                    <div class="summary-label">
                        ALL PROJECTS
                    </div>

                    <div class="summary-number">
                        {{ $projects->count() }}
                    </div>

                </div>


                <div class="summary-card sow">

                    <div class="summary-label">
                        SOW
                    </div>

                    <div class="summary-number">
                        {{ $projects->where('phase', 'Proposal')->count() }}
                    </div>

                </div>


                <div class="summary-card progress">

                    <div class="summary-label">
                        IN PROGRESS
                    </div>

                    <div class="summary-number">
                        {{ $projects->where('phase', 'In Progress')->count() }}
                    </div>

                </div>


                <div class="summary-card active">

                    <div class="summary-label">
                        ACTIVE
                    </div>

                    <div class="summary-number">
                        {{ $projects->where('phase', 'Active')->count() }}
                    </div>

                </div>


                <div class="summary-card completed">

                    <div class="summary-label">
                        COMPLETED
                    </div>

                    <div class="summary-number">
                        {{ $projects->where('phase', 'Completed')->count() }}
                    </div>

                </div>

            </div>


            <!-- =====================================================
                 PROJECT REGISTRY
            ===================================================== -->

            <section class="registry">

                <div class="registry-header">

                    <div class="registry-title">

                        <h2>
                            Project Registry
                        </h2>

                        <p>
                            This list is now backed by approved deals instead of placeholder data.
                        </p>

                    </div>


                    <div class="project-search">

                        <span>
                            ⌕
                        </span>

                        <input
                            type="text"
                            id="projectSearch"
                            placeholder="Search projects..."
                            onkeyup="searchProjects()"
                        >

                    </div>

                </div>


                <div class="table-wrapper">

                    <table class="projects-table">

                        <thead>

                            <tr>

                                <th class="checkbox-column">
                                    <input
                                        type="checkbox"
                                        class="table-checkbox"
                                        id="selectAll"
                                        onclick="toggleAll(this)"
                                    >
                                </th>

                                <th class="project-column">
                                    Project
                                </th>

                                <th class="deal-column">
                                    Deal
                                </th>

                                <th class="company-column">
                                    Company
                                </th>

                                <th class="phase-column">
                                    Phase
                                </th>

                                <th class="owner-column">
                                    Owner
                                </th>

                                <th class="target-column">
                                    Target
                                </th>

                                <th class="action-column">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody id="projectTableBody">

                            @foreach($projects as $project)
                                <tr class="project-row">
                                    <td class="checkbox-column">
                                        <input type="checkbox" class="table-checkbox">
                                    </td>
                                    <td>
                                        <div class="project-name">{{ $project['project_name'] }}</div>
                                        <div class="project-code">{{ $project['project_number'] }}</div>
                                    </td>
                                    <td><div class="deal-number">{{ $project['deal_code'] }}</div></td>
                                    <td><div class="company-name">{{ $project['company'] ?: '-' }}</div></td>
                                    <td><span class="phase phase-{{ strtolower(str_replace(' ', '-', $project['phase'])) == 'in-progress' ? 'progress' : 'sow' }}">{{ $project['phase'] }}</span></td>
                                    <td><div class="owner-name">{{ $project['owner'] ?: '-' }}</div></td>
                                    <td><div class="target-date">{{ $project['target'] ?: '-' }}</div></td>
                                    <td class="action-column">
                                        <a href="{{ route('project.show', $project['id']) }}" class="view-button">View</a>
                                    </td>
                                </tr>
                            @endforeach

                            @if(false)

                            <tr class="project-row">

                                <td class="checkbox-column">
                                    <input
                                        type="checkbox"
                                        class="table-checkbox"
                                    >
                                </td>

                                <td>

                                    <div class="project-name">
                                        ATP - AUTHORITY TO PRINT
                                    </div>

                                    <div class="project-code">
                                        PROJ-2026-121
                                    </div>

                                </td>

                                <td>
                                    <div class="deal-number">
                                        -
                                    </div>
                                </td>

                                <td>

                                    <div class="company-name">
                                        SEO LLEM K-BEAUTY LOUNGE CORP.
                                    </div>

                                </td>

                                <td>

                                    <span class="phase phase-sow">
                                        SOW
                                    </span>

                                </td>

                                <td>
                                    -
                                </td>

                                <td>

                                    <div class="target-date">
                                        Sep 15, 2026
                                    </div>

                                </td>

                                <td class="action-column">

                                    <a
                                        href="#"
                                        class="view-button"
                                        onclick="viewProject('PROJ-2026-121'); return false;"
                                    >
                                        View
                                    </a>

                                </td>

                            </tr>


                            <tr class="project-row">

                                <td class="checkbox-column">
                                    <input
                                        type="checkbox"
                                        class="table-checkbox"
                                    >
                                </td>

                                <td>

                                    <div class="project-name">
                                        Transfer of Share From Dany and Ronald to X10
                                    </div>

                                    <div class="project-code">
                                        PROJ-2026-120
                                    </div>

                                </td>

                                <td>

                                    <div class="deal-number">
                                        CONDEAL-2026-<br>
                                        065
                                    </div>

                                </td>

                                <td>

                                    <div class="company-name">
                                        X10 REAL ESTATE CORPORATION
                                    </div>

                                </td>

                                <td>

                                    <span class="phase phase-sow">
                                        SOW
                                    </span>

                                </td>

                                <td>

                                    <div class="owner-name">
                                        Lyndon Earl Rio &amp; John Kelly<br>
                                        Abalde
                                    </div>

                                </td>

                                <td>

                                    <div class="target-date">
                                        Sep 18, 2026
                                    </div>

                                </td>

                                <td class="action-column">

                                    <a
                                        href="#"
                                        class="view-button"
                                        onclick="viewProject('PROJ-2026-120'); return false;"
                                    >
                                        View
                                    </a>

                                </td>

                            </tr>


                            <tr class="project-row">

                                <td class="checkbox-column">
                                    <input
                                        type="checkbox"
                                        class="table-checkbox"
                                    >
                                </td>

                                <td>

                                    <div class="project-name">
                                        Transfer Shares Ronald to Stephan
                                    </div>

                                    <div class="project-code">
                                        PROJ-2026-119
                                    </div>

                                </td>

                                <td>

                                    <div class="deal-number">
                                        CONDEAL-2026-<br>
                                        066
                                    </div>

                                </td>

                                <td>

                                    <div class="company-name">
                                        -
                                    </div>

                                </td>

                                <td>

                                    <span class="phase phase-sow">
                                        SOW
                                    </span>

                                </td>

                                <td>

                                    <div class="owner-name">
                                        John Kelly Abalde and Lyndon Earl<br>
                                        Rio
                                    </div>

                                </td>

                                <td>

                                    <div class="target-date">
                                        Sep 18, 2026
                                    </div>

                                </td>

                                <td class="action-column">

                                    <a
                                        href="#"
                                        class="view-button"
                                        onclick="viewProject('PROJ-2026-119'); return false;"
                                    >
                                        View
                                    </a>

                                </td>

                            </tr>


                            <tr class="project-row">

                                <td class="checkbox-column">
                                    <input
                                        type="checkbox"
                                        class="table-checkbox"
                                    >
                                </td>

                                <td>

                                    <div class="project-name">
                                        Transfer Shares Mandaluyong and Makati
                                    </div>

                                    <div class="project-code">
                                        PROJ-2026-118
                                    </div>

                                </td>

                                <td>

                                    <div class="deal-number">
                                        CONDEAL-2026-<br>
                                        054
                                    </div>

                                </td>

                                <td>

                                    <div class="company-name">
                                        Sanarex Med Group Inc.
                                    </div>

                                </td>

                                <td>

                                    <span class="phase phase-sow">
                                        SOW
                                    </span>

                                </td>

                                <td>

                                    <div class="owner-name">
                                        John Kelly Abalde and Lyndon Earl<br>
                                        Rio
                                    </div>

                                </td>

                                <td>

                                    <div class="target-date">
                                        Sep 11, 2026
                                    </div>

                                </td>

                                <td class="action-column">

                                    <a
                                        href="#"
                                        class="view-button"
                                        onclick="viewProject('PROJ-2026-118'); return false;"
                                    >
                                        View
                                    </a>

                                </td>

                            </tr>


                            <tr class="project-row">

                                <td class="checkbox-column">
                                    <input
                                        type="checkbox"
                                        class="table-checkbox"
                                    >
                                </td>

                                <td>

                                    <div class="project-name">
                                        SEC Registration
                                    </div>

                                    <div class="project-code">
                                        PROJ-2026-115
                                    </div>

                                </td>

                                <td>

                                    <div class="deal-number">
                                        CONDEAL-2026-<br>
                                        061
                                    </div>

                                </td>

                                <td>

                                    <div class="company-name">
                                        -
                                    </div>

                                </td>

                                <td>

                                    <span class="phase phase-progress">
                                        In Progress
                                    </span>

                                </td>

                                <td>

                                    <div class="owner-name">
                                        John Kelly Abalde
                                    </div>

                                </td>

                                <td>

                                    <div class="target-date">
                                        Sep 11, 2026
                                    </div>

                                </td>

                                <td class="action-column">

                                    <a
                                        href="#"
                                        class="view-button"
                                        onclick="viewProject('PROJ-2026-115'); return false;"
                                    >
                                        View
                                    </a>

                                </td>

                            </tr>


                            <tr class="project-row">

                                <td class="checkbox-column">
                                    <input
                                        type="checkbox"
                                        class="table-checkbox"
                                    >
                                </td>

                                <td>

                                    <div class="project-name">
                                        BIR and LGU Business Closure
                                    </div>

                                    <div class="project-code">
                                        PROJ-2026-113
                                    </div>

                                </td>

                                <td>

                                    <div class="deal-number">
                                        CONDEAL-2026-<br>
                                        034
                                    </div>

                                </td>

                                <td>

                                    <div class="company-name">
                                        X10 REAL ESTATE CORPORATION
                                    </div>

                                </td>

                                <td>

                                    <span class="phase phase-progress">
                                        In Progress
                                    </span>

                                </td>

                                <td>

                                    <div class="owner-name">
                                        John Kelly Abalde
                                    </div>

                                </td>

                                <td>

                                    <div class="target-date">
                                        Sep 11, 2026
                                    </div>

                                </td>

                                <td class="action-column">

                                    <a
                                        href="#"
                                        class="view-button"
                                        onclick="viewProject('PROJ-2026-113'); return false;"
                                    >
                                        View
                                    </a>

                                </td>

                            </tr>


                            <tr class="project-row">

                                <td class="checkbox-column">
                                    <input
                                        type="checkbox"
                                        class="table-checkbox"
                                    >
                                </td>

                                <td>

                                    <div class="project-name">
                                        BIR and LGU Business Closure
                                    </div>

                                    <div class="project-code">
                                        PROJ-2026-112
                                    </div>

                                </td>

                                <td>

                                    <div class="deal-number">
                                        CONDEAL-2026-<br>
                                        058
                                    </div>

                                </td>

                                <td>

                                    <div class="company-name">
                                        BONHUI CELLSHOP
                                    </div>

                                </td>

                                <td>

                                    <span class="phase phase-progress">
                                        In Progress
                                    </span>

                                </td>

                                <td>

                                    <div class="owner-name">
                                        John Kelly Abalde
                                    </div>

                                </td>

                                <td>

                                    <div class="target-date">
                                        Sep 11, 2026
                                    </div>

                                </td>

                                <td class="action-column">

                                    <a
                                        href="#"
                                        class="view-button"
                                        onclick="viewProject('PROJ-2026-112'); return false;"
                                    >
                                        View
                                    </a>

                                </td>

                            </tr>


                            <tr class="project-row">

                                <td class="checkbox-column">
                                    <input
                                        type="checkbox"
                                        class="table-checkbox"
                                    >
                                </td>

                                <td>

                                    <div class="project-name">
                                        Transfer RDO Mandaue to Cebu City
                                    </div>

                                    <div class="project-code">
                                        PROJ-2026-111
                                    </div>

                                </td>

                                <td>

                                    <div class="deal-number">
                                        CONDEAL-2026-<br>
                                        002
                                    </div>

                                </td>

                                <td>

                                    <div class="company-name">
                                        NEW ERA HAULING SERVICES<br>
                                        CORPORATION
                                    </div>

                                </td>

                                <td>

                                    <span class="phase phase-progress">
                                        In Progress
                                    </span>

                                </td>

                                <td>

                                    <div class="owner-name">
                                        Mari Louise Chua
                                    </div>

                                </td>

                                <td>

                                    <div class="target-date">
                                        Oct 12, 2026
                                    </div>

                                </td>

                                <td class="action-column">

                                    <a
                                        href="#"
                                        class="view-button"
                                        onclick="viewProject('PROJ-2026-111'); return false;"
                                    >
                                        View
                                    </a>

                                </td>

                            </tr>

                            @endif

                        </tbody>

                    </table>


                    <div
                        class="no-results"
                        id="noResults"
                    >
                        No projects found.
                    </div>

                </div>

            </section>


            <div class="footer">
                © 2026 John Kelly &amp; Company. All rights reserved.
            </div>

        </div>

    </main>


    <!-- =========================================================
         CREATE PROJECT MODAL
    ========================================================= -->

    <div
        class="modal-overlay"
        id="createProjectModal"
    >

        <div class="create-project-modal">

            <!-- =========================
                 MODAL HEADER
            ========================= -->

            <div class="modal-header">

                <div class="modal-title">
                    Create Project
                </div>

                <div class="modal-subtitle">
                    Manually create a project record and open the SOW form to fill out details, scope, activities, and requirements.
                </div>

                <button
                    type="button"
                    class="modal-close"
                    onclick="closeCreateProject()"
                    aria-label="Close"
                >
                    ×
                </button>

            </div>


            <!-- =========================
                 MODAL BODY
            ========================= -->

            <div class="modal-body">


                <!-- =================================================
                     LEFT SIDE — SOW PREVIEW
                ================================================== -->

                <div class="preview-panel">

                    <div class="preview-container">

                        <div class="preview-top">

                            <div>

                                <div class="preview-label">
                                    SOW FORM PREVIEW
                                </div>

                                <div class="preview-title">
                                    Blank Project Form
                                </div>

                            </div>

                            <div class="default-badge">
                                DEFAULT
                            </div>

                        </div>


                        <div class="document-scroll">

                            <div class="document-paper">

                                <div class="document-inner">


                                    <!-- DOCUMENT HEADER -->

                                    <div class="document-heading">

                                        <div class="document-logo">

                                            <div class="document-logo-main">
                                                John Kelly
                                            </div>

                                            <div class="document-logo-sub">
                                                <span>ↄ</span> Company
                                            </div>

                                        </div>


                                        <div class="document-title">

                                            <strong>
                                                SCOPE OF WORK
                                            </strong>

                                            <small>
                                                PROJ-F-002
                                            </small>

                                        </div>

                                    </div>


                                    <!-- DOCUMENT INFORMATION -->

                                    <div class="document-info">

                                        <div class="document-info-box">

                                            <span>
                                                CONDEAL REF NO.
                                            </span>

                                            <strong>
                                                -
                                            </strong>

                                        </div>


                                        <div class="document-info-box">

                                            <span>
                                                PROJECT CODE
                                            </span>

                                            <strong>
                                                Auto-generated
                                            </strong>

                                        </div>


                                        <div class="document-info-box">

                                            <span>
                                                CLIENT
                                            </span>

                                            <strong id="previewClient">
                                                Pending selection
                                            </strong>

                                        </div>


                                        <div class="document-info-box">

                                            <span>
                                                BUSINESS
                                            </span>

                                            <strong id="previewBusiness">
                                                Pending selection
                                            </strong>

                                        </div>


                                        <div class="document-info-box">

                                            <span>
                                                VERSION
                                            </span>

                                            <strong>
                                                1.0
                                            </strong>

                                        </div>


                                        <div class="document-info-box">

                                            <span>
                                                STATUS
                                            </span>

                                            <strong>
                                                Draft template
                                            </strong>

                                        </div>

                                    </div>


                                    <!-- WITHIN SCOPE -->

                                    <div class="doc-section">

                                        <div class="doc-section-title">
                                            WITHIN SCOPE
                                        </div>

                                        <table class="doc-table">

                                            <thead>

                                                <tr>

                                                    <th>
                                                        Main Task
                                                    </th>

                                                    <th>
                                                        Sub Task
                                                    </th>

                                                    <th>
                                                        Status
                                                    </th>

                                                </tr>

                                            </thead>

                                            <tbody>

                                                <tr>

                                                    <td
                                                        colspan="3"
                                                        class="doc-empty"
                                                    >
                                                        No within-scope items yet.
                                                    </td>

                                                </tr>

                                            </tbody>

                                        </table>

                                    </div>


                                    <!-- OUT OF SCOPE -->

                                    <div class="doc-section">

                                        <div class="doc-section-title">
                                            OUT OF SCOPE
                                        </div>

                                        <table class="doc-table">

                                            <tbody>

                                                <tr>

                                                    <td>
                                                        No out-of-scope items will be loaded until a saved template is selected.
                                                    </td>

                                                </tr>

                                            </tbody>

                                        </table>

                                    </div>


                                    <!-- SIGNATURE -->

                                    <div class="signature-box">

                                        <div style="width:100%;">

                                            <strong>
                                                Client representative signature
                                            </strong>

                                            <div class="signature-line"></div>

                                            <em>
                                                Client Fullname &amp; Signature
                                            </em>

                                        </div>

                                    </div>


                                    <!-- INTERNAL APPROVAL -->

                                    <div class="doc-section">

                                        <div class="doc-section-title">
                                            INTERNAL APPROVAL
                                        </div>

                                        <div class="approval-grid">

                                            <div class="approval-box">

                                                <div class="approval-label">
                                                    Prepared By
                                                </div>

                                                <div class="approval-line"></div>

                                                <div class="approval-small">
                                                    Name / Signature / Date
                                                </div>

                                            </div>


                                            <div class="approval-box">

                                                <div class="approval-label">
                                                    Reviewed By
                                                </div>

                                                <div class="approval-line"></div>

                                                <div class="approval-small">
                                                    Name / Signature / Date
                                                </div>

                                            </div>

                                        </div>

                                    </div>


                                    <!-- RECORDS -->

                                    <div class="doc-section">

                                        <div class="doc-section-title">
                                            RECORDS
                                        </div>

                                        <div class="records-box">

                                            <div class="records-left">

                                                Date Received: __________________

                                                <br>

                                                Date Returned: __________________

                                            </div>

                                            <div class="records-right">
                                                Conforme / Record Custodian
                                            </div>

                                        </div>

                                    </div>


                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     RIGHT SIDE — FORM
                ================================================== -->

                <div class="form-panel">


                    <!-- CREATE METHOD -->

                    <div class="form-section">

                        <div class="section-heading">
                            How do you want to create this project?
                        </div>


                        <div class="creation-options">


                            <div
                                class="creation-option"
                                id="linkDealOption"
                                onclick="selectCreationType('deal')"
                            >

                                <div class="creation-option-title">
                                    Link Existing Deal
                                </div>

                                <div class="creation-option-description">
                                    Pick an approved deal and preload its client,
                                    company, scope, and staffing details.
                                </div>

                            </div>


                            <div
                                class="creation-option selected"
                                id="manualOption"
                                onclick="selectCreationType('manual')"
                            >

                                <div class="creation-option-title">
                                    Manual
                                </div>

                                <div class="creation-option-description">
                                    Start manually, then optionally select an existing
                                    contact or company to fill the client details.
                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- CUSTOMER TYPE -->

                    <div class="form-section">

                        <div class="section-heading">
                            Customer Type
                        </div>

                        <div class="customer-type-box">

                            <label class="radio-option">

                                <input
                                    type="radio"
                                    name="customerType"
                                    value="business"
                                    onchange="updateCustomerType('Business')"
                                >

                                Business

                            </label>


                            <label class="radio-option">

                                <input
                                    type="radio"
                                    name="customerType"
                                    value="individual"
                                    checked
                                    onchange="updateCustomerType('Individual')"
                                >

                                Individual

                            </label>

                        </div>

                    </div>


                    <!-- CLIENT -->

                    <div class="form-section no-border">

                        <div class="section-heading">
                            Select Existing Contact / Client
                        </div>

                        <div class="section-description">
                            Select a customer type, then search contacts by name,
                            company, email, or mobile.
                        </div>


                        <div class="field">

                            <label>
                                Search Existing Client
                            </label>

                            <input
                                type="text"
                                id="clientSearch"
                                placeholder="Type name, company, email, or mobile..."
                                oninput="updateClientPreview()"
                            >

                        </div>


                        <div class="field">

                            <label>
                                SOW Template
                            </label>

                            <select id="sowTemplate">

                                <option>
                                    Start from blank/default
                                </option>

                                <option>
                                    Standard Legal Services
                                </option>

                                <option>
                                    Corporate Compliance
                                </option>

                                <option>
                                    Business Registration
                                </option>

                            </select>

                        </div>


                        <div class="field">

                            <label>
                                Project Name
                            </label>

                            <input
                                type="text"
                                id="projectName"
                                oninput="updateProjectPreview()"
                            >

                        </div>


                        <div class="two-column">

                            <div class="field">

                                <label>
                                    Client Name
                                </label>

                                <input
                                    type="text"
                                    id="clientName"
                                    oninput="updateClientPreview()"
                                >

                            </div>


                            <div class="field">

                                <label>
                                    Business Name
                                </label>

                                <input
                                    type="text"
                                    id="businessName"
                                    oninput="updateBusinessPreview()"
                                >

                            </div>

                        </div>


                        <div class="two-column">

                            <div class="field">

                                <label>
                                    Planned Start
                                </label>

                                <input
                                    type="date"
                                    id="plannedStart"
                                >

                            </div>


                            <div class="field">

                                <label>
                                    Target Completion
                                </label>

                                <input
                                    type="date"
                                    id="targetCompletion"
                                >

                            </div>

                        </div>


                        <div class="two-column">

                            <div class="field">

                                <label>
                                    Client Confirmation Name
                                </label>

                                <input
                                    type="text"
                                    id="confirmationName"
                                >

                            </div>


                            <div class="field">

                                <label>
                                    Project Manager
                                </label>

                                <input
                                    type="text"
                                    id="projectManager"
                                >

                            </div>

                        </div>


                        <div class="field">

                            <label>
                                Lead Consultant
                            </label>

                            <input
                                type="text"
                                id="leadConsultant"
                            >

                        </div>


                        <div class="field">

                            <label>
                                Lead Associate
                            </label>

                            <input
                                type="text"
                                id="leadAssociate"
                            >

                        </div>


                        <div class="field">

                            <label>
                                Sales &amp; Marketing
                            </label>

                            <input
                                type="text"
                                id="salesMarketing"
                            >

                        </div>


                        <div class="field">

                            <label>
                                Finance
                            </label>

                            <input
                                type="text"
                                id="finance"
                            >

                        </div>


                        <div class="field">

                            <label>
                                Scope Summary
                            </label>

                            <textarea
                                id="scopeSummary"
                                placeholder=""
                            ></textarea>

                        </div>


                        <div class="field">

                            <label>
                                SOW Engagement Requirements
                            </label>

                            <textarea
                                id="engagementRequirements"
                                placeholder="One requirement per line"
                            ></textarea>

                        </div>

                    </div>


                    <!-- SERVICE IDENTIFICATION -->

                    <div class="form-section">

                        <div class="section-heading">
                            Service Identification
                        </div>


                        <div class="field">

                            <label>
                                Service Area
                            </label>

                        </div>


                        <div class="service-grid">

                            <label class="check-option">
                                <input type="checkbox">
                                Accounting &amp; Compliance Advisory
                            </label>

                            <label class="check-option">
                                <input type="checkbox">
                                Business Strategy &amp; Process Advisory
                            </label>

                            <label class="check-option">
                                <input type="checkbox">
                                Corporate &amp; Regulatory Advisory
                            </label>

                            <label class="check-option">
                                <input type="checkbox">
                                Governance &amp; Policy Advisory
                            </label>

                            <label class="check-option">
                                <input type="checkbox">
                                Learning &amp; Capability Development
                            </label>

                            <label class="check-option">
                                <input type="checkbox">
                                People &amp; Talent Solutions
                            </label>

                            <label class="check-option">
                                <input type="checkbox">
                                Service Add-Ons
                            </label>

                            <label class="check-option">
                                <input type="checkbox">
                                Strategic Situations Advisory
                            </label>

                        </div>


                        <div
                            class="field"
                            style="margin-top:14px;"
                        >

                            <label>
                                Services
                            </label>

                            <div class="products-note">
                                Select a service area first to show matching services.
                            </div>

                        </div>

                    </div>


                    <!-- PRODUCTS -->

                    <div class="form-section">

                        <div class="section-heading">
                            Products
                        </div>

                        <div class="section-description">
                            Products follow the selected service area.
                            Without a selected service area, only products
                            without a service area are shown.
                        </div>


                        <div class="products-title">
                            PRODUCTS WITHOUT SERVICE AREA
                        </div>


                        <div class="service-grid">


                            <label class="check-option">
                                <input type="checkbox">
                                Archive Retrieval
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Digital Archive Copy
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Document Delivery (Metro Cebu)
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Document Delivery (Outside Metro Cebu/LBC)
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Drafting of Affidavits (Non-Legal Advice)
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Drafting of Agreements / Simple Contracts
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Drafting of Board Resolutions
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Drafting of Certifications
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Drafting of Compliance Documents
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Drafting of Demand Letters
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Drafting of Emails (Formal / Business)
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Drafting of Endorsement / Request Letters
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Drafting of Letters
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Drafting of Memorandum (Internal / External)
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Drafting of Notices
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Drafting of Policies &amp; Procedures
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Drafting of Reports / Formal Documents
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Drafting of Responses to Letters / Notices
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Drafting of Secretary's Certificates
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Notarization - Complex Documents
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Notarization - Simple Documents
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Photocopy
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Printing
                            </label>


                            <label class="check-option">
                                <input type="checkbox">
                                Stock Certificate Printing
                            </label>

                        </div>

                    </div>


                </div>

            </div>


            <!-- =================================================
                 MODAL FOOTER
            ================================================== -->

            <div class="modal-footer">

                <button
                    type="button"
                    class="modal-cancel"
                    onclick="closeCreateProject()"
                >
                    Cancel
                </button>


                <button
                    type="button"
                    class="modal-create"
                    onclick="submitCreateProject()"
                >
                    Create
                </button>

            </div>

        </div>

    </div>


    <!-- =========================================================
         JAVASCRIPT
    ========================================================= -->

    <script>

        /* =====================================================
           OPEN CREATE PROJECT
        ===================================================== */

        function openCreateProject() {

            const modal =
                document.getElementById('createProjectModal');

            modal.classList.add('show');

            document.body.style.overflow = 'hidden';

        }


        /* =====================================================
           CLOSE CREATE PROJECT
        ===================================================== */

        function closeCreateProject() {

            const modal =
                document.getElementById('createProjectModal');

            modal.classList.remove('show');

            document.body.style.overflow = '';

        }


        /* =====================================================
           ESCAPE KEY
        ===================================================== */

        document.addEventListener('keydown', function(event) {

            if (event.key === 'Escape') {

                closeCreateProject();

            }

        });


        /* =====================================================
           CLICK OUTSIDE MODAL
        ===================================================== */

        document
            .getElementById('createProjectModal')
            .addEventListener('click', function(event) {

                if (event.target === this) {

                    closeCreateProject();

                }

            });


        /* =====================================================
           CREATION TYPE
        ===================================================== */

        function selectCreationType(type) {

            const dealOption =
                document.getElementById('linkDealOption');

            const manualOption =
                document.getElementById('manualOption');

            if (type === 'deal') {

                dealOption.classList.add('selected');
                manualOption.classList.remove('selected');

            } else {

                manualOption.classList.add('selected');
                dealOption.classList.remove('selected');

            }

        }


        /* =====================================================
           CUSTOMER TYPE
        ===================================================== */

        function updateCustomerType(type) {

            console.log(
                'Customer type:',
                type
            );

        }


        /* =====================================================
           CLIENT PREVIEW
        ===================================================== */

        function updateClientPreview() {

            const clientName =
                document.getElementById('clientName').value.trim();

            const search =
                document.getElementById('clientSearch').value.trim();

            const preview =
                document.getElementById('previewClient');

            if (clientName !== '') {

                preview.textContent =
                    clientName;

            } else if (search !== '') {

                preview.textContent =
                    search;

            } else {

                preview.textContent =
                    'Pending selection';

            }

        }


        /* =====================================================
           BUSINESS PREVIEW
        ===================================================== */

        function updateBusinessPreview() {

            const businessName =
                document
                    .getElementById('businessName')
                    .value
                    .trim();

            const preview =
                document.getElementById('previewBusiness');

            if (businessName !== '') {

                preview.textContent =
                    businessName;

            } else {

                preview.textContent =
                    'Pending selection';

            }

        }


        /* =====================================================
           PROJECT PREVIEW
        ===================================================== */

        function updateProjectPreview() {

            const projectName =
                document
                    .getElementById('projectName')
                    .value
                    .trim();

            console.log(
                'Project:',
                projectName
            );

        }


        /* =====================================================
           SEARCH PROJECTS
        ===================================================== */

        function searchProjects() {

            const input =
                document
                    .getElementById('projectSearch')
                    .value
                    .toLowerCase()
                    .trim();

            const rows =
                document.querySelectorAll(
                    '.project-row'
                );

            let visibleRows = 0;


            rows.forEach(function(row) {

                const text =
                    row.innerText.toLowerCase();

                if (text.includes(input)) {

                    row.style.display = '';

                    visibleRows++;

                } else {

                    row.style.display = 'none';

                }

            });


            const noResults =
                document.getElementById('noResults');


            if (visibleRows === 0) {

                noResults.style.display = 'block';

            } else {

                noResults.style.display = 'none';

            }

        }


        /* =====================================================
           SELECT ALL
        ===================================================== */

        function toggleAll(source) {

            const checkboxes =
                document.querySelectorAll(
                    '.project-row .table-checkbox'
                );

            checkboxes.forEach(function(checkbox) {

                checkbox.checked =
                    source.checked;

            });

        }


        /* =====================================================
           VIEW PROJECT
        ===================================================== */

        function viewProject(projectCode) {

            alert(
                'Opening project: ' +
                projectCode
            );

        }


        /* =====================================================
           CREATE PROJECT
        ===================================================== */

        function submitCreateProject() {

            const projectName =
                document
                    .getElementById('projectName')
                    .value
                    .trim();

            const clientName =
                document
                    .getElementById('clientName')
                    .value
                    .trim();


            if (projectName === '') {

                alert(
                    'Please enter a Project Name.'
                );

                document
                    .getElementById('projectName')
                    .focus();

                return;

            }


            if (clientName === '') {

                alert(
                    'Please enter a Client Name.'
                );

                document
                    .getElementById('clientName')
                    .focus();

                return;

            }


            alert(
                'Project "' +
                projectName +
                '" is ready to be created.'
            );


            /*
             * Laravel integration:
             *
             * Dito natin ilalagay later ang POST request
             * papunta sa Laravel route/controller.
             *
             * Example:
             *
             * fetch('/projects', {
             *     method: 'POST',
             *     headers: {
             *         'Content-Type': 'application/json',
             *         'X-CSRF-TOKEN': csrfToken
             *     },
             *     body: JSON.stringify(...)
             * });
             */


            closeCreateProject();

        }

    </script>

</body>
</html>