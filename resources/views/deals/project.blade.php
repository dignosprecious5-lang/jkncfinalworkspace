<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Project Workspace - Scope of Work</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f5f8fc;
    color: #10243e;
}

/* =========================
   TOP HEADER
========================= */

.top-header {
    height: 60px;
    background: #ffffff;
    border-top: 1px solid #d9dfe7;
    border-bottom: 1px solid #e4e8ee;
    display: flex;
    align-items: center;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
}

.logo {
    width: 150px;
    padding-left: 27px;
    line-height: 15px;
    color: #151515;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 16px;
}

.logo-main {
    font-weight: 600;
}

.logo-sub {
    font-size: 15px;
    font-weight: 600;
}

.logo-mark {
    color: #1f5ca8;
    font-size: 13px;
    margin-right: 2px;
}

.global-search {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    width: 523px;
    height: 36px;
    border-radius: 20px;
    background: #f1f3f6;
    display: flex;
    align-items: center;
    padding: 0 16px;
    color: #94a3b8;
    font-size: 13px;
}

.search-icon {
    margin-right: 13px;
    font-size: 16px;
}

.header-right {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 20px;
    padding-right: 16px;
}

.notification {
    position: relative;
    font-size: 20px;
    color: #334155;
}

.notification-badge {
    position: absolute;
    top: -7px;
    right: -10px;
    background: #dc2626;
    color: white;
    border-radius: 10px;
    min-width: 19px;
    height: 19px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: bold;
}

.profile {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #eef0f3;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #475569;
    font-size: 14px;
}

/* =========================
   SIDEBAR
========================= */

.sidebar {
    position: fixed;
    top: 60px;
    left: 0;
    bottom: 0;
    width: 72px;
    background: #ffffff;
    border-right: 1px solid #e1e6ed;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 13px;
    z-index: 90;
}

.side-icon {
    width: 40px;
    height: 40px;
    margin: 6px 0;
    border-radius: 9px;
    background: #f5f6f8;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #708198;
    font-size: 17px;
}

.side-icon.active {
    background: #edf5ff;
    border: 1px solid #d7e8ff;
    color: #1764d1;
}

/* =========================
   MAIN
========================= */

.main {
    margin-left: 72px;
    padding: 79px 0 60px;
    min-height: 100vh;
    background:
        linear-gradient(
            180deg,
            #edf4fc 0px,
            #f5f8fc 320px,
            #ffffff 850px
        );
}

.content {
    width: calc(100% - 200px);
    max-width: 1500px;
    margin: 0 auto;
}

/* =========================
   BACK BAR
========================= */

.back-bar {
    height: 50px;
    background: #ffffff;
    border: 1px solid #d6e1ee;
    border-radius: 15px;
    display: flex;
    align-items: center;
    padding: 0 18px;
    font-size: 13px;
    margin-bottom: 15px;
    box-shadow: 0 2px 5px rgba(20, 50, 90, 0.02);
}

.back-arrow {
    font-size: 19px;
    margin-right: 4px;
    color: #334155;
}

.back-project {
    color: #315b88;
}

.slash {
    margin: 0 9px;
    color: #94a3b8;
}

.project-number {
    font-weight: 600;
    color: #153a67;
}

/* =========================
   PROJECT CARD
========================= */

.project-card {
    background: #ffffff;
    border: 1px solid #d6e1ee;
    border-radius: 15px;
    min-height: 176px;
    padding: 18px 18px 16px;
    margin-bottom: 16px;
    position: relative;
}

.workspace-label {
    font-size: 11px;
    letter-spacing: 3px;
    color: #50739a;
    margin-bottom: 8px;
}

.project-title {
    margin: 0;
    font-size: 21px;
    line-height: 27px;
    font-weight: 600;
    color: #071b35;
}

.project-code {
    margin-top: 8px;
    color: #647b98;
    font-size: 12px;
}

/* =========================
   INFO PILLS
========================= */

.info-pills {
    position: absolute;
    right: 18px;
    top: 19px;
    display: flex;
    gap: 7px;
}

.info-pill {
    height: 39px;
    border: 1px solid #d3e0ef;
    border-radius: 22px;
    padding: 0 15px;
    display: flex;
    align-items: center;
    gap: 9px;
    white-space: nowrap;
    font-size: 12px;
}

.pill-label {
    color: #7890ac;
}

.pill-value {
    color: #173d69;
    font-weight: 500;
}

/* =========================
   PROJECT BUTTONS
========================= */

.project-actions {
    display: flex;
    gap: 8px;
    margin-top: 20px;
}

.project-button {
    height: 39px;
    padding: 0 17px;
    border-radius: 21px;
    border: 1px solid #cddceb;
    background: #ffffff;
    color: #153b67;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .2px;
    cursor: pointer;
}

.project-button:hover {
    background: #f6f9fd;
}

.project-button.active {
    background: #214f8d;
    color: #ffffff;
    border-color: #214f8d;
    box-shadow: 0 7px 15px rgba(31, 76, 137, 0.20);
}

/* =========================
   DEAL / CONTACT BAR
========================= */

.reference-bar {
    height: 51px;
    background: #ffffff;
    border: 1px solid #d6e1ee;
    border-radius: 15px;
    display: flex;
    align-items: center;
    padding: 0 18px;
    gap: 34px;
    margin-bottom: 15px;
    font-size: 12px;
    color: #607995;
}

.reference-value {
    color: #0759c9;
    font-weight: 500;
}

/* =========================
   SCOPE LAYOUT
========================= */

.scope-layout {
    display: grid;
    grid-template-columns: 254px minmax(0, 1fr);
    gap: 17px;
    align-items: start;
}

/* =========================
   QUICK ACTIONS
========================= */

.quick-actions {
    background: #ffffff;
    border: 1px solid #d6e1ee;
    border-radius: 15px;
    padding: 13px;
}

.quick-title {
    color: #607590;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1.3px;
    margin: 2px 0 12px 1px;
}

.quick-box {
    border: 1px solid #d9e4ef;
    border-radius: 15px;
    padding: 11px;
    margin-bottom: 10px;
    background: #ffffff;
}

.quick-box-title {
    color: #879bb4;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 9px;
}

.status-pill {
    height: 35px;
    border: 1px solid #d7e2ef;
    border-radius: 19px;
    display: flex;
    align-items: center;
    padding: 0 12px;
    color: #526983;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 8px;
}

.status-pill:last-child {
    margin-bottom: 0;
}

.status-icon {
    margin-right: 7px;
    color: #526983;
}

.document-title-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.settings-button {
    width: 30px;
    height: 30px;
    border: 1px solid #cbd8e7;
    background: #ffffff;
    color: #50657e;
    font-size: 16px;
    cursor: pointer;
}

.document-button {
    width: 100%;
    min-height: 35px;
    padding: 8px 10px;
    border: 1px solid #cbd9e8;
    background: #ffffff;
    color: #526983;
    text-align: left;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 8px;
    cursor: pointer;
}

.document-button:last-child {
    margin-bottom: 0;
}

.document-button.primary {
    background: #234d91;
    border-color: #234d91;
    color: #ffffff;
}

.document-button:hover {
    background: #f4f8fd;
}

.document-button.primary:hover {
    background: #1e4380;
}

.approval-input {
    width: 100%;
    height: 34px;
    border: 1px solid #cbd9e8;
    padding: 0 8px;
    margin-bottom: 7px;
    font-size: 11px;
    color: #526983;
    outline: none;
}

.approval-input::placeholder {
    color: #9aabc0;
}

.approval-file {
    width: 100%;
    font-size: 10px;
    margin-bottom: 7px;
    color: #667b94;
}

.approval-button {
    width: 100%;
    min-height: 37px;
    background: #234d91;
    border: 1px solid #234d91;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    padding: 7px;
}

.template-info {
    border: 1px solid #d7e2ef;
    border-radius: 10px;
    padding: 10px;
    color: #607590;
    font-size: 10px;
    line-height: 15px;
}

/* =========================
   SCOPE DOCUMENT
========================= */

.scope-document {
    background: #ffffff;
    border: 1px solid #173c70;
    min-width: 0;
    padding: 27px 27px 30px;
}

.document-header {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 13px;
}

.document-logo {
    font-family: Georgia, "Times New Roman", serif;
    color: #111111;
    line-height: 16px;
    font-size: 19px;
    margin-left: 18px;
}

.document-logo-sub {
    font-size: 18px;
}

.document-logo-mark {
    color: #1f5ca8;
    font-size: 14px;
}

.document-heading {
    text-align: right;
}

.document-heading h1 {
    margin: 0;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 28px;
    color: #071b35;
    letter-spacing: .3px;
}

.document-heading small {
    display: block;
    margin-top: 4px;
    font-family: Georgia, "Times New Roman", serif;
    color: #64748b;
    font-size: 11px;
}

.document-information {
    display: grid;
    grid-template-columns: 1fr 1fr;
    column-gap: 25px;
    row-gap: 0;
    margin-bottom: 13px;
}

.info-row {
    height: 35px;
    display: grid;
    grid-template-columns: 158px 1fr;
    align-items: center;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 13px;
}

.info-label {
    color: #10243e;
}

.info-value {
    border-bottom: 1px solid #29374a;
    min-height: 26px;
    display: flex;
    align-items: center;
    color: #111827;
    padding-left: 1px;
}

.date-value {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* =========================
   TABLE SECTION
========================= */

.scope-section-title {
    height: 40px;
    background: #234d91;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 15px;
    font-weight: 700;
}

.scope-table-wrapper {
    overflow-x: auto;
}

.scope-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 10px;
    color: #111827;
}

.scope-table th,
.scope-table td {
    border: 1px solid #26323e;
    padding: 5px 4px;
    vertical-align: middle;
}

.scope-table th {
    height: 31px;
    text-align: center;
    font-weight: 500;
    background: #ffffff;
}

.scope-table td {
    height: 25px;
}

.scope-table th:nth-child(1) {
    width: 18%;
}

.scope-table th:nth-child(2) {
    width: 23%;
}

.scope-table th:nth-child(3) {
    width: 8%;
}

.scope-table th:nth-child(4) {
    width: 5%;
}

.scope-table th:nth-child(5) {
    width: 9%;
}

.scope-table th:nth-child(6) {
    width: 9%;
}

.scope-table th:nth-child(7) {
    width: 7%;
}

.scope-table th:nth-child(8) {
    width: 8%;
}

.scope-table th:nth-child(9) {
    width: 5%;
}

.scope-table td input,
.scope-table td select {
    width: 100%;
    border: 0;
    outline: none;
    background: transparent;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 10px;
    color: #111827;
}

.scope-table td input[type="date"] {
    min-width: 90px;
}

.delete-cell {
    text-align: center;
}

.delete-row {
    border: 0;
    background: transparent;
    color: #e11d2e;
    font-size: 16px;
    cursor: pointer;
}

.total-row td {
    height: 31px;
    font-weight: 600;
}

.total-label {
    text-align: right;
    font-size: 13px;
}

.add-row-container {
    display: flex;
    justify-content: flex-end;
    padding: 9px 0 15px;
}

.add-row-button {
    height: 35px;
    padding: 0 13px;
    background: #ffffff;
    border: 1px solid #cbd9e8;
    color: #536b87;
    font-size: 11px;
    cursor: pointer;
}

.add-row-button:hover {
    background: #f4f8fd;
}

/* =========================
   PROJECT STATUS SUMMARY
========================= */

.summary-title {
    height: 40px;
    background: #234d91;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 15px;
    font-weight: 700;
    margin-top: 4px;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 10px;
    padding: 18px 16px 12px;
}

.summary-card {
    border: 1px solid #d3dfec;
    padding: 10px;
    min-height: 79px;
}

.summary-card-label {
    color: #536d8c;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: .6px;
    margin-bottom: 7px;
}

.summary-card-value {
    height: 37px;
    border: 1px solid #d0dce9;
    display: flex;
    align-items: center;
    padding-left: 10px;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 13px;
    color: #34495f;
}

.text-section {
    padding: 0 16px 12px;
}

.text-section label {
    display: block;
    color: #536b87;
    font-size: 10px;
    font-weight: 700;
    margin-bottom: 7px;
}

.text-section textarea {
    width: 100%;
    height: 82px;
    resize: vertical;
    border: 1px solid #cbd9e8;
    outline: none;
    padding: 8px;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 12px;
}

/* =========================
   PROJECT DETAILS
========================= */

.project-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    column-gap: 25px;
    margin: 3px 16px 15px;
}

.detail-row {
    min-height: 35px;
    display: grid;
    grid-template-columns: 160px 1fr;
    align-items: center;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 13px;
}

.detail-value {
    min-height: 26px;
    border-bottom: 1px solid #29374a;
    display: flex;
    align-items: center;
    padding-left: 2px;
}

/* =========================
   CLIENT CONFIRMATION
========================= */

.blue-section-title {
    height: 39px;
    background: #234d91;
    color: #ffffff;
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 15px;
    font-weight: 700;
}

.client-confirmation {
    border: 1px solid #1c2938;
    margin-bottom: 15px;
}

.client-name {
    height: 67px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 13px;
}

.client-line {
    width: 310px;
    border-bottom: 1px solid #29374a;
    margin-top: 7px;
}

.client-signature {
    margin-top: 6px;
    font-style: italic;
    font-size: 12px;
}

/* =========================
   INTERNAL APPROVAL
========================= */

.internal-approval {
    border: 1px solid #1c2938;
}

.approval-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
}

.approval-column {
    border-right: 1px solid #1c2938;
}

.approval-column:last-child {
    border-right: 0;
}

.approval-row {
    min-height: 45px;
    border-bottom: 1px solid #1c2938;
    display: grid;
    grid-template-columns: 135px 1fr;
    align-items: center;
    padding: 0 8px;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 12px;
}

.approval-column .approval-row:last-child {
    border-bottom: 0;
}

.approval-label {
    font-style: italic;
}

.approval-value {
    border-bottom: 1px solid #29374a;
    min-height: 25px;
    display: flex;
    align-items: center;
}

.record-row {
    display: grid;
    grid-template-columns: 1fr 310px;
    min-height: 89px;
}

.record-signature {
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding-bottom: 14px;
    border-right: 1px solid #1c2938;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 12px;
    font-style: italic;
}

.record-dates {
    display: flex;
    flex-direction: column;
}

.record-date {
    flex: 1;
    display: grid;
    grid-template-columns: 120px 1fr;
    align-items: center;
    padding: 0 8px;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 12px;
}

.record-date:first-child {
    border-bottom: 1px solid #1c2938;
}

.record-date-value {
    border-bottom: 1px solid #29374a;
    height: 25px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* =========================
   SOW REPORT SCREEN
========================= */

.sow-screen {
    width: 100%;
}

.report-header {
    background: #ffffff;
    border: 1px solid #d6e1ee;
    border-radius: 15px;
    min-height: 119px;
    padding: 19px 21px;
    position: relative;
    margin-bottom: 20px;
}

.report-label {
    font-size: 10px;
    letter-spacing: 3px;
    color: #54779d;
    margin-bottom: 8px;
}

.report-title {
    margin: 0;
    font-size: 27px;
    font-weight: 500;
    color: #071b35;
}

.report-description {
    margin-top: 8px;
    color: #5e7694;
    font-size: 12px;
}

.report-summary {
    position: absolute;
    right: 21px;
    bottom: 18px;
    display: flex;
    gap: 10px;
}

.summary-pill {
    height: 41px;
    border: 1px solid #d1dfed;
    border-radius: 22px;
    padding: 0 16px;
    display: flex;
    align-items: center;
    gap: 9px;
    color: #7990ab;
    font-size: 12px;
}

.summary-value {
    color: #173e6d;
    font-weight: 500;
}

.report-table-card {
    background: #ffffff;
    border: 1px solid #d6e1ee;
    border-radius: 15px;
    overflow: hidden;
    min-height: 232px;
}

.table-search-area {
    height: 70px;
    padding: 15px 21px;
    border-bottom: 1px solid #e0e6ee;
    display: flex;
    align-items: center;
}

.table-search {
    width: 420px;
    height: 40px;
    border: 1px solid #d8e3ef;
    border-radius: 10px;
    outline: none;
    padding: 0 14px 0 35px;
    color: #506b8b;
    font-size: 12px;
    background: #ffffff;
}

.search-wrapper {
    position: relative;
}

.table-search-icon {
    position: absolute;
    left: 13px;
    top: 11px;
    color: #7991ac;
    font-size: 15px;
}

.table-header {
    height: 48px;
    background: #f8fafc;
    border-bottom: 1px solid #dce4ed;
    display: grid;
    grid-template-columns:
        48px
        1fr
        1.2fr
        1.2fr
        1.1fr
        .7fr;
    align-items: center;
    padding: 0 8px;
}

.table-header div {
    color: #4e6c8e;
    font-size: 10px;
    letter-spacing: 1.5px;
    font-weight: 700;
}

.select-box {
    width: 15px;
    height: 15px;
    border: 1px solid #8d99a8;
    border-radius: 2px;
    margin-left: 3px;
}

.empty-state {
    height: 108px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #607996;
    font-size: 12px;
}

.empty-state strong {
    color: #183e6b;
    font-weight: 600;
    margin: 0 4px;
}

.back-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    color: #3568c4;
    text-decoration: none;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    border: none;
    padding: 0;
}

.back-button:hover,
.back-button:focus,
.back-button:active {
    background: transparent;
    color: #3568c4;
    text-decoration: none;
}
/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1250px) {

    .content {
        width: calc(100% - 60px);
    }

    .info-pills {
        position: static;
        margin-top: 15px;
        flex-wrap: wrap;
    }

    .scope-layout {
        grid-template-columns: 235px minmax(0, 1fr);
    }
}

@media (max-width: 950px) {

    .scope-layout {
        grid-template-columns: 1fr;
    }

    .quick-actions {
        width: 100%;
    }

    .scope-document {
        overflow-x: auto;
    }

    .document-information,
    .project-details {
        grid-template-columns: 1fr;
    }

    .summary-cards {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 700px) {

    .global-search {
        width: 35%;
    }

    .content {
        width: calc(100% - 30px);
    }

    .sidebar {
        width: 58px;
    }

    .main {
        margin-left: 58px;
    }

    .project-card {
        min-height: auto;
    }

    .summary-cards {
        grid-template-columns: repeat(2, 1fr);
    }

    .approval-grid {
        grid-template-columns: 1fr;
    }

    .approval-column {
        border-right: 0;
    }

    .approval-column:first-child {
        border-bottom: 1px solid #1c2938;
    }
}
</style>
</head>

<body>

<!-- =========================
     TOP HEADER
========================= -->

<header class="top-header">

    <div class="logo">
        <div class="logo-main">John Kelly</div>
        <div class="logo-sub">
            <span class="logo-mark">♧</span>Company
        </div>
    </div>

    <div class="global-search">
        <span class="search-icon">⌕</span>
        <span>Search</span>
    </div>

    <div class="header-right">

        @include('components.notifications')

        <div class="profile">P</div>

    </div>

</header>


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

    <div class="side-icon active">▱</div>
    <div class="side-icon">♟</div>
    <div class="side-icon">⚑</div>
    <div class="side-icon">▣</div>
    <div class="side-icon">▧</div>
    <div class="side-icon">▤</div>
    <div class="side-icon">♟</div>
    <div class="side-icon">◎</div>
    <div class="side-icon">⌁</div>
    <div class="side-icon">▣</div>
    <div class="side-icon active">⚒</div>

</aside>


<!-- =========================
     MAIN
========================= -->

<main class="main">

<div class="content">

    <!-- BACK -->

    <div class="back-bar">

        <a href="{{ route('project.index') }}">

        ←
      </a>
        <span class="back-project">Project</span>
        <span class="slash">/</span>
        <span class="project-number">{{ $projectNumber }}</span>

    </div>


    <!-- PROJECT WORKSPACE -->

    <section class="project-card">

        <div class="workspace-label">
            PROJECT WORKSPACE
        </div>

        <h1 class="project-title">
            {{ $deal->deal_title ?: 'Business Compliance Project' }}
        </h1>

        <div class="project-code">
            {{ $projectNumber }} - {{ $dealCode }}
        </div>


        <div class="info-pills">

            <div class="info-pill">
                <span class="pill-label">Business</span>
                <span class="pill-value">
                    {{ $deal->company ?: $deal->company_name ?: '-' }}
                </span>
            </div>

            <div class="info-pill">
                <span class="pill-label">Client</span>
                <span class="pill-value">
                    {{ $clientName ?: '-' }}
                </span>
            </div>

            <div class="info-pill">
                <span class="pill-label">Planned Start</span>
                <span class="pill-value">
                    {{ optional($deal->planned_start_date)->format('M d, Y') ?: '-' }}
                </span>
            </div>

            <div class="info-pill">
                <span class="pill-label">Target Completion</span>
                <span class="pill-value">
                    {{ optional($deal->expected_close ?: $deal->estimated_completion_date)->format('M d, Y') ?: '-' }}
                </span>
            </div>

        </div>


        <div class="project-actions">

            <button
                class="project-button active"
                onclick="showScope()">
                SCOPE OF WORK
            </button>

            <button
                class="project-button"
                onclick="showSowReport()">
                SOW REPORT
            </button>

        </div>

    </section>


    <!-- DEAL / CONTACT -->

    <section class="reference-bar">

        <div>
            Deal:
            <span class="reference-value">
                    <a href="#"
                        onclick="openDealDrawer('edit', {{ $deal->id }}); return false;"
                        style="color: inherit; text-decoration: none;">
                        {{ $dealCode }}
                    </a>
            </span>
        </div>

        <div>
            Contact:
            <span class="reference-value">
                {{ $clientName ?: '-' }}
            </span>
        </div>

    </section>


    <!-- =========================
         CONTENT
    ========================= -->

    <div id="screenContent">

        <!-- =========================
             SCOPE OF WORK
        ========================= -->

        <div class="scope-layout">


            <!-- =========================
                 QUICK ACTIONS
            ========================= -->

            <aside class="quick-actions">

                <div class="quick-title">
                    QUICK ACTIONS
                </div>


                <!-- STATUS -->

                <div class="quick-box">

                    <div class="quick-box-title">
                        STATUS
                    </div>

                    <div class="status-pill">
                        <span class="status-icon">⌛</span>
                        NTP not generated
                    </div>

                    <div class="status-pill">
                        <span class="status-icon">◧</span>
                        COC pending
                        <br>
                        completion approval
                    </div>

                </div>


                <!-- DOCUMENT ACTIONS -->

                <div class="quick-box">

                    <div class="document-title-row">

                        <div class="quick-box-title">
                            DOCUMENT ACTIONS
                        </div>

                        <button class="settings-button">
                            ⚙
                        </button>

                    </div>


                    <button
                        type="submit"
                        form="scopeForm"
                        class="document-button primary">
                        Save Scope of Work
                    </button>
                    <button
                        class="document-button"
                        onclick="generateSowReport()">
                        Generate SOW Report
                    </button>

                    <button
                        class="document-button"
                        onclick="generateCoc()">
                        Generate COC
                    </button>

                    <button
                        class="document-button"
                        onclick="generateTransmittal()">
                        Generate Transmittal
                    </button>

                    <button
                        class="document-button"
                        onclick="generateNtp()">
                        Generate NTP
                    </button>

                    <button
                        class="document-button"
                        onclick="downloadPdf()">
                        Download PDF
                    </button>

                </div>


                <!-- MANUAL APPROVE SOW -->

                <div class="quick-box">

                    <div class="quick-box-title">
                        MANUAL APPROVE SOW
                    </div>

                    <input
                        type="file"
                        class="approval-file">

                    <input
                        type="text"
                        class="approval-input"
                        placeholder="Approval note">

                    <button
                        class="approval-button"
                        onclick="manualApproveSow()">
                        Manual Approve SOW
                    </button>

                </div>


                <!-- MANUAL NTP APPROVAL -->

                <div class="quick-box">

                    <div class="quick-box-title">
                        MANUAL NTP APPROVAL
                    </div>

                    <input
                        type="file"
                        class="approval-file">

                    <input
                        type="text"
                        class="approval-input"
                        placeholder="Approval note">

                    <button
                        class="approval-button"
                        onclick="uploadNtp()">
                        Upload Signed NTP &amp;
                        <br>
                        Approve
                    </button>

                </div>


                <!-- COC COMPLETION -->

                <div class="quick-box">

                    <div class="quick-box-title">
                        COC COMPLETION
                    </div>

                    <input
                        type="file"
                        class="approval-file">

                    <input
                        type="text"
                        class="approval-input"
                        placeholder="Approval note">

                    <button
                        class="approval-button"
                        onclick="uploadCoc()">
                        Upload Signed COC &amp;
                        <br>
                        Complete
                    </button>

                </div>


                <!-- TEMPLATES -->

                <div class="quick-box">

                    <div class="quick-box-title">
                        TEMPLATES
                    </div>

                    <button class="document-button">
                        Make a Template
                    </button>

                    <div class="template-info">
                        1 saved SOW template
                        <br>
                        available in Create Project.
                    </div>

                </div>

            </aside>


            <!-- =========================
                 SCOPE DOCUMENT
            ========================= -->

            <section class="scope-document">
                <form
                id="scopeForm"
                method="POST"
                action="{{ route('deals.project.scope.save', ['id' => $deal->id]) }}">
                    @csrf

                <!-- DOCUMENT HEADER -->

                <div class="document-header">

                    <div class="document-logo">

                        <div>
                            John Kelly
                        </div>

                        <div class="document-logo-sub">
                            <span class="document-logo-mark">♧</span>
                            Company
                        </div>

                    </div>


                    <div class="document-heading">

                        <h1>
                            SCOPE OF WORK
                        </h1>

                        <small>
                            {{ $projectNumber }}
                        </small>

                    </div>

                </div>


                <!-- DOCUMENT INFORMATION -->

                <div class="document-information">

                    <div>

                        <div class="info-row">

                            <div class="info-label">
                                Conde​al Reference No.:
                            </div>

                            <div class="info-value">
                                {{ $dealCode }}
                            </div>

                        </div>

                        <div class="info-row">

                            <div class="info-label">
                                Business Name:
                            </div>

                            <div class="info-value">
                                {{ $deal->company ?: $deal->company_name ?: '-' }}
                            </div>

                        </div>

                        <div class="info-row">

                            <div class="info-label">
                                Client Name:
                            </div>

                            <div class="info-value">
                                {{ $clientName ?: '-' }}
                            </div>

                        </div>

                    </div>


                    <div>

                        <div class="info-row">

                            <div class="info-label">
                                Version No.:
                            </div>

                            <div class="info-value">
                            </div>

                        </div>

                        <div class="info-row">

                            <div class="info-label">
                                SOW No.:
                            </div>

                            <div class="info-value">
                                SOW-{{ date('Y') }}-{{ str_pad($deal->id, 3, '0', STR_PAD_LEFT) }}
                            </div>

                        </div>

                        <div class="info-row">

                            <div class="info-label">
                                Date Prepared:
                            </div>

                            <div class="info-value date-value">

                                <span>
                                    {{ optional($deal->created_at?->timezone('Asia/Manila'))->format('m/d/Y') }}
                                </span>

                                <span>▣</span>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- WITHIN SCOPE -->

                <div class="scope-section-title">
                    WITHIN SCOPE
                </div>


                <div class="scope-table-wrapper">

                    <table class="scope-table" id="withinScopeTable">

                        <thead>

                            <tr>

                                <th>
                                    Main Task Description
                                </th>

                                <th>
                                    Sub Task Description
                                </th>

                                <th>
                                    Responsible
                                </th>

                                <th>
                                    Duration
                                </th>

                                <th>
                                    Start Date
                                </th>

                                <th>
                                    End Date
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Remarks
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody id="withinScopeBody">

                            @php
                             $scopeItems = $deal->projectScopeItems;
                             @endphp

                             @forelse($scopeItems->where('scope_type', 'within_scope') as $item)
                                <tr>

                                    <td>
                                        <input
                                            type="text"
                                            name="within_scope[{{ $loop->index }}][main_task]"
                                            value="{{ $item->main_task }}"
                                        >
                                    </td>

                                    <td>
                                        <input
                                            type="text"
                                            name="within_scope[{{ $loop->index }}][sub_task]"
                                            value="{{ $item->sub_task }}"
                                        >
                                    </td>

                                    <td>
                                        <input
                                            type="text"
                                            name="within_scope[{{ $loop->index }}][responsible]"
                                            value="{{ $item->responsible }}"
                                        >
                                    </td>

                                    <td>
                                        <input
                                            type="number"
                                            name="within_scope[{{ $loop->index }}][duration]"
                                            value="{{ $item->duration }}"
                                        >
                                    </td>

                                    <td>
                                        <input
                                            type="date"
                                            name="within_scope[{{ $loop->index }}][start_date]"
                                            value="{{ optional($item->start_date)->format('Y-m-d') }}"
                                        >
                                    </td>

                                    <td>
                                        <input
                                            type="date"
                                            name="within_scope[{{ $loop->index }}][end_date]"
                                            value="{{ optional($item->end_date)->format('Y-m-d') }}"
                                        >
                                    </td>

                                    <td>
                                        <select name="within_scope[{{ $loop->index }}][status]">

                                            <option value="Open"
                                                {{ $item->status === 'Open' ? 'selected' : '' }}>
                                                Open
                                            </option>

                                            <option value="In Progress"
                                                {{ $item->status === 'In Progress' ? 'selected' : '' }}>
                                                In Progress
                                            </option>

                                            <option value="Delayed"
                                                {{ $item->status === 'Delayed' ? 'selected' : '' }}>
                                                Delayed
                                            </option>

                                            <option value="Complete"
                                                {{ $item->status === 'Complete' ? 'selected' : '' }}>
                                                Complete
                                            </option>

                                            <option value="On Hold"
                                                {{ $item->status === 'On Hold' ? 'selected' : '' }}>
                                                On Hold
                                            </option>

                                        </select>
                                    </td>

                                    <td>
                                        <input
                                            type="text"
                                            name="within_scope[{{ $loop->index }}][remarks]"
                                            value="{{ $item->remarks }}"
                                        >
                                    </td>

                                    <td class="delete-cell">

                                        <button
                                            type="button"
                                            class="delete-row"
                                            onclick="deleteRow(this)">
                                            ×
                                        </button>

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="9" class="doc-empty">
                                        No within-scope items yet.
                                    </td>
                                </tr>

                            @endforelse

                            @if(false)

                            <tr>
                                <td>
                                    Share Transfer Documentation
                                </td>

                                <td>
                                    Review and preparation of documents required for the share transfer
                                </td>

                                <td>
                                    Mari Louise Chua
                                </td>

                                <td>
                                    1
                                </td>

                                <td>
                                    08/20/2026
                                </td>

                                <td>
                                    08/21/2026
                                </td>

                                <td>
                                    Complete
                                </td>

                                <td></td>

                                <td class="delete-cell">
                                    <button
                                        class="delete-row"
                                        onclick="deleteRow(this)">
                                        ×
                                    </button>
                                </td>
                            </tr>


                            <tr>
                                <td>
                                    Share Transfer Documentation
                                </td>

                                <td>
                                    Coordination with the transferor/s and transferee/s
                                </td>

                                <td>
                                    Mari Louise Chua
                                </td>

                                <td>
                                    2
                                </td>

                                <td>
                                    08/20/2026
                                </td>

                                <td>
                                    08/22/2026
                                </td>

                                <td>
                                    Complete
                                </td>

                                <td></td>

                                <td class="delete-cell">
                                    <button
                                        class="delete-row"
                                        onclick="deleteRow(this)">
                                        ×
                                    </button>
                                </td>
                            </tr>


                            <tr>
                                <td>
                                    Share Transfer Documentation
                                </td>

                                <td>
                                    Facilitation of execution and signing of share-transfer documents
                                </td>

                                <td>
                                    Mari Louise Chua
                                </td>

                                <td>
                                    3
                                </td>

                                <td>
                                    08/21/2026
                                </td>

                                <td>
                                    08/24/2026
                                </td>

                                <td>
                                    Complete
                                </td>

                                <td></td>

                                <td class="delete-cell">
                                    <button
                                        class="delete-row"
                                        onclick="deleteRow(this)">
                                        ×
                                    </button>
                                </td>
                            </tr>


                            <tr>
                                <td>
                                    Share Transfer Documentation
                                </td>

                                <td>
                                    Coordination and collection of required supporting documents
                                </td>

                                <td>
                                    Mari Louise Chua
                                </td>

                                <td>
                                    3
                                </td>

                                <td>
                                    08/20/2026
                                </td>

                                <td>
                                    08/23/2026
                                </td>

                                <td>
                                    Delayed
                                </td>

                                <td></td>

                                <td class="delete-cell">
                                    <button
                                        class="delete-row"
                                        onclick="deleteRow(this)">
                                        ×
                                    </button>
                                </td>
                            </tr>


                            <tr>
                                <td>
                                    Makati Documentation &amp; Execution
                                </td>

                                <td>
                                    Coordinate with concerned parties in Makati
                                </td>

                                <td>
                                    Mari Louise Chua
                                </td>

                                <td>
                                    1
                                </td>

                                <td>
                                    08/24/2026
                                </td>

                                <td>
                                    08/25/2026
                                </td>

                                <td>
                                    Delayed
                                </td>

                                <td></td>

                                <td class="delete-cell">
                                    <button
                                        class="delete-row"
                                        onclick="deleteRow(this)">
                                        ×
                                    </button>
                                </td>
                            </tr>


                            <tr>
                                <td>
                                    Makati Documentation &amp; Execution
                                </td>

                                <td>
                                    Facilitate signing and completion of required share-transfer documents
                                </td>

                                <td>
                                    Mari Louise Chua
                                </td>

                                <td>
                                    1
                                </td>

                                <td>
                                    08/24/2026
                                </td>

                                <td>
                                    08/25/2026
                                </td>

                                <td>
                                    Delayed
                                </td>

                                <td></td>

                                <td class="delete-cell">
                                    <button
                                        class="delete-row"
                                        onclick="deleteRow(this)">
                                        ×
                                    </button>
                                </td>
                            </tr>


                            <tr>
                                <td>
                                    Makati Documentation &amp; Execution
                                </td>

                                <td>
                                    Facilitate notarization, where applicable
                                </td>

                                <td>
                                    Mari Louise Chua
                                </td>

                                <td>
                                    1
                                </td>

                                <td>
                                    08/24/2026
                                </td>

                                <td>
                                    08/25/2026
                                </td>

                                <td>
                                    Delayed
                                </td>

                                <td></td>

                                <td class="delete-cell">
                                    <button
                                        class="delete-row"
                                        onclick="deleteRow(this)">
                                        ×
                                    </button>
                                </td>
                            </tr>


                            <tr>
                                <td>
                                    Makati Documentation &amp; Execution
                                </td>

                                <td>
                                    Receive and consolidate executed documents for processing
                                </td>

                                <td>
                                    Mari Louise Chua
                                </td>

                                <td>
                                    1
                                </td>

                                <td>
                                    08/24/2026
                                </td>

                                <td>
                                    08/25/2026
                                </td>

                                <td>
                                    Delayed
                                </td>

                                <td></td>

                                <td class="delete-cell">
                                    <button
                                        class="delete-row"
                                        onclick="deleteRow(this)">
                                        ×
                                    </button>
                                </td>
                            </tr>


                            <tr>
                                <td>
                                    Mandaluyong Documentation &amp; Execution
                                </td>

                                <td>
                                    Coordinate with concerned parties in Mandaluyong
                                </td>

                                <td>
                                    Mari Louise Chua
                                </td>

                                <td>
                                    1
                                </td>

                                <td>
                                    08/25/2026
                                </td>

                                <td>
                                    08/26/2026
                                </td>

                                <td>
                                    Delayed
                                </td>

                                <td></td>

                                <td class="delete-cell">
                                    <button
                                        class="delete-row"
                                        onclick="deleteRow(this)">
                                        ×
                                    </button>
                                </td>
                            </tr>


                            <tr>
                                <td>
                                    Mandaluyong Documentation &amp; Execution
                                </td>

                                <td>
                                    Facilitate signing and completion of required share-transfer documents
                                </td>

                                <td>
                                    Mari Louise Chua
                                </td>

                                <td>
                                    1
                                </td>

                                <td>
                                    08/25/2026
                                </td>

                                <td>
                                    08/26/2026
                                </td>

                                <td>
                                    Delayed
                                </td>

                                <td></td>

                                <td class="delete-cell">
                                    <button
                                        class="delete-row"
                                        onclick="deleteRow(this)">
                                        ×
                                    </button>
                                </td>
                            </tr>


                            <tr>
                                <td>
                                    Mandaluyong Documentation &amp; Execution
                                </td>

                                <td>
                                    Facilitate notarization, where applicable
                                </td>

                                <td>
                                    Mari Louise Chua
                                </td>

                                <td>
                                    1
                                </td>

                                <td>
                                    08/25/2026
                                </td>

                                <td>
                                    08/26/2026
                                </td>

                                <td>
                                    Delayed
                                </td>

                                <td></td>

                                <td class="delete-cell">
                                    <button
                                        class="delete-row"
                                        onclick="deleteRow(this)">
                                        ×
                                    </button>
                                </td>
                            </tr>


                            <tr>
                                <td>
                                    Mandaluyong Documentation &amp; Execution
                                </td>

                                <td>
                                    Receive and consolidate executed documents for processing
                                </td>

                                <td>
                                    Mari Louise Chua
                                </td>

                                <td>
                                    1
                                </td>

                                <td>
                                    08/25/2026
                                </td>

                                <td>
                                    08/26/2026
                                </td>

                                <td>
                                    Delayed
                                </td>

                                <td></td>

                                <td class="delete-cell">
                                    <button
                                        class="delete-row"
                                        onclick="deleteRow(this)">
                                        ×
                                    </button>
                                </td>
                            </tr>


                            <tr>
                                <td>
                                    BIR Share Transfer Processing
                                </td>

                                <td>
                                    Prepare and organize applicable BIR documentary requirements
                                </td>

                                <td>
                                    Mari Louise Chua
                                </td>

                                <td>
                                    2
                                </td>

                                <td>
                                    08/25/2026
                                </td>

                                <td>
                                    08/27/2026
                                </td>

                                <td>
                                    Open
                                </td>

                                <td></td>

                                <td class="delete-cell">
                                    <button
                                        class="delete-row"
                                        onclick="deleteRow(this)">
                                        ×
                                    </button>
                                </td>
                            </tr>


                            <tr>
                                <td>
                                    BIR Share Transfer Processing
                                </td>

                                <td>
                                    Facilitate filing/submission of applicable tax returns
                                </td>

                                <td>
                                    Mari Louise Chua
                                </td>

                                <td>
                                    1
                                </td>

                                <td>
                                    08/26/2026
                                </td>

                                <td>
                                    08/27/2026
                                </td>

                                <td>
                                    Open
                                </td>

                                <td></td>

                                <td class="delete-cell">
                                    <button
                                        class="delete-row"
                                        onclick="deleteRow(this)">
                                        ×
                                    </button>
                                </td>
                            </tr>


                            <tr>
                                <td>
                                    BIR Share Transfer Processing
                                </td>

                                <td>
                                    Facilitate payment processing of applicable taxes
                                </td>

                                <td>
                                    Mari Louise Chua
                                </td>

                                <td>
                                    1
                                </td>

                                <td>
                                    08/26/2026
                                </td>

                                <td>
                                    08/27/2026
                                </td>

                                <td>
                                    Open
                                </td>

                                <td></td>

                                <td class="delete-cell">
                                    <button
                                        class="delete-row"
                                        onclick="deleteRow(this)">
                                        ×
                                    </button>
                                </td>
                            </tr>


                            <tr>
                                <td>
                                    BIR Share Transfer Processing
                                </td>

                                <td>
                                    Facilitate application and processing for the applicable transfer
                                </td>

                                <td>
                                    Mari Louise Chua
                                </td>

                                <td>
                                    30
                                </td>

                                <td>
                                    08/27/2026
                                </td>

                                <td>
                                    09/26/2026
                                </td>

                                <td>
                                    Open
                                </td>

                                <td></td>

                                <td class="delete-cell">
                                    <button
                                        class="delete-row"
                                        onclick="deleteRow(this)">
                                        ×
                                    </button>
                                </td>
                            </tr>


                            <tr>
                                <td>
                                    BIR Share Transfer Processing
                                </td>

                                <td>
                                    Monitor and follow up the application until release
                                </td>

                                <td>
                                    Mari Louise Chua
                                </td>

                                <td>
                                    7
                                </td>

                                <td>
                                    09/28/2026
                                </td>

                                <td>
                                    10/05/2026
                                </td>

                                <td>
                                    Open
                                </td>

                                <td></td>

                                <td class="delete-cell">
                                    <button
                                        class="delete-row"
                                        onclick="deleteRow(this)">
                                        ×
                                    </button>
                                </td>
                            </tr>


                            <tr>
                                <td>
                                    Corporate Records Turnover
                                </td>

                                <td>
                                    Consolidate completed and government-issued share documents
                                </td>

                                <td>
                                    Mari Louise Chua
                                </td>

                                <td>
                                    1
                                </td>

                                <td>
                                    mm/dd/yyyy
                                </td>

                                <td>
                                    mm/dd/yyyy
                                </td>

                                <td>
                                    Open
                                </td>

                                <td></td>

                                <td class="delete-cell">
                                    <button
                                        class="delete-row"
                                        onclick="deleteRow(this)">
                                        ×
                                    </button>
                                </td>
                            </tr>


                            <tr>
                                <td>
                                    Corporate Records Turnover
                                </td>

                                <td>
                                    Turn over completed documents to the client and/or concerned party
                                </td>

                                <td>
                                    Mari Louise Chua
                                </td>

                                <td>
                                    1
                                </td>

                                <td>
                                    mm/dd/yyyy
                                </td>

                                <td>
                                    mm/dd/yyyy
                                </td>

                                <td>
                                    Open
                                </td>

                                <td></td>

                                <td class="delete-cell">
                                    <button
                                        class="delete-row"
                                        onclick="deleteRow(this)">
                                        ×
                                    </button>
                                </td>
                            </tr>

                            @endif

                        </tbody>


                        <tfoot>

                            <tr class="total-row">

                                <td colspan="7"></td>

                                <td class="total-label">
                                    Total:
                                </td>

                                <td>
                                    <span id="withinTotal">
                                        19 item(s)
                                    </span>
                                </td>

                            </tr>

                        </tfoot>

                    </table>

                </div>


                <div class="add-row-container">

                    <button
                        type="button"
                        class="add-row-button"
                        onclick="addWithinScopeRow()">
                        Add Row
                    </button>
                </div>


                <!-- =========================
                     OUT OF SCOPE
                ========================= -->

                <div class="scope-section-title">
                    OUT OF SCOPE
                </div>


                <div class="scope-table-wrapper">

                    <table class="scope-table">

                        <thead>

                            <tr>

                                <th>
                                    Main Task Description
                                </th>

                                <th>
                                    Sub Task Description
                                </th>

                                <th>
                                    Responsible
                                </th>

                                <th>
                                    Duration
                                </th>

                                <th>
                                    Start Date
                                </th>

                                <th>
                                    End Date
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Remarks
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody id="outScopeBody">

                            @forelse($scopeItems->where('scope_type', 'out_of_scope') as $item)

                                <tr>

                                    <td>
                                        <input
                                            type="text"
                                            name="out_of_scope[{{ $loop->index }}][main_task]"
                                            value="{{ $item->main_task }}"
                                        >
                                    </td>

                                    <td>
                                        <input
                                            type="text"
                                            name="out_of_scope[{{ $loop->index }}][sub_task]"
                                            value="{{ $item->sub_task }}"
                                        >
                                    </td>

                                    <td>
                                        <input
                                            type="text"
                                            name="out_of_scope[{{ $loop->index }}][responsible]"
                                            value="{{ $item->responsible }}"
                                        >
                                    </td>

                                    <td>
                                        <input
                                            type="number"
                                            name="out_of_scope[{{ $loop->index }}][duration]"
                                            value="{{ $item->duration }}"
                                        >
                                    </td>

                                    <td>
                                        <input
                                            type="date"
                                            name="out_of_scope[{{ $loop->index }}][start_date]"
                                            value="{{ optional($item->start_date)->format('Y-m-d') }}"
                                        >
                                    </td>

                                    <td>
                                        <input
                                            type="date"
                                            name="out_of_scope[{{ $loop->index }}][end_date]"
                                            value="{{ optional($item->end_date)->format('Y-m-d') }}"
                                        >
                                    </td>

                                    <td>
                                        <select name="out_of_scope[{{ $loop->index }}][status]">

                                            <option value="Open"
                                                {{ $item->status === 'Open' ? 'selected' : '' }}>
                                                Open
                                            </option>

                                            <option value="In Progress"
                                                {{ $item->status === 'In Progress' ? 'selected' : '' }}>
                                                In Progress
                                            </option>

                                            <option value="Delayed"
                                                {{ $item->status === 'Delayed' ? 'selected' : '' }}>
                                                Delayed
                                            </option>

                                            <option value="Complete"
                                                {{ $item->status === 'Complete' ? 'selected' : '' }}>
                                                Complete
                                            </option>

                                            <option value="On Hold"
                                                {{ $item->status === 'On Hold' ? 'selected' : '' }}>
                                                On Hold
                                            </option>

                                        </select>
                                    </td>

                                    <td>
                                        <input
                                            type="text"
                                            name="out_of_scope[{{ $loop->index }}][remarks]"
                                            value="{{ $item->remarks }}"
                                        >
                                    </td>

                                    <td class="delete-cell">

                                        <button
                                            type="button"
                                            class="delete-row"
                                            onclick="deleteRow(this)">
                                            ×
                                        </button>

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="9" class="doc-empty">
                                        No out-of-scope items yet.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                        <tfoot>

                            <tr class="total-row">

                                <td colspan="7"></td>

                                <td class="total-label">
                                    Total:
                                </td>

                                <td>
                                    <span id="outTotal">
                                        0 item(s)
                                    </span>
                                </td>

                            </tr>

                        </tfoot>

                    </table>

                </div>


                <div class="add-row-container">

                    <button
                            type="button"
                            class="add-row-button"
                            onclick="addOutScopeRow()">
                            Add Row
                    </button>

                </div>


                <!-- =========================
                     PROJECT STATUS SUMMARY
                ========================= -->

                <div class="summary-title">
                    PROJECT STATUS SUMMARY
                </div>
                @php
                    $withinScopeItems = $scopeItems->where('scope_type', 'within_scope');

                    $totalMainTasks = $withinScopeItems->count();
                    $openCount = $withinScopeItems->where('status', 'Open')->count();
                    $inProgressCount = $withinScopeItems->where('status', 'In Progress')->count();
                    $delayedCount = $withinScopeItems->where('status', 'Delayed')->count();
                    $completedCount = $withinScopeItems->where('status', 'Complete')->count();
                    $onHoldCount = $withinScopeItems->where('status', 'On Hold')->count();
                @endphp


                <div class="summary-cards">

                    <div class="summary-card">

                        <div class="summary-card-label">
                            TOTAL MAIN TASKS
                        </div>

                        <div
                            class="summary-card-value"
                            id="totalMainTasks">
                            {{ $totalMainTasks }}
                        </div>

                    </div>


                    <div class="summary-card">

                        <div class="summary-card-label">
                            OPEN
                        </div>

                        <div class="summary-card-value">
                            {{ $openCount }}
                        </div>

                    </div>


                    <div class="summary-card">

                        <div class="summary-card-label">
                            IN PROGRESS
                        </div>

                        <div class="summary-card-value">
                            {{ $inProgressCount }}
                        </div>

                    </div>


                    <div class="summary-card">

                        <div class="summary-card-label">
                            DELAYED
                        </div>

                        <div class="summary-card-value">
                            {{ $delayedCount }}
                        </div>

                    </div>


                    <div class="summary-card">

                        <div class="summary-card-label">
                            COMPLETED
                        </div>

                        <div class="summary-card-value">
                            {{ $completedCount }}
                        </div>

                    </div>


                    <div class="summary-card">

                        <div class="summary-card-label">
                            ON HOLD
                        </div>

                        <div class="summary-card-value">
                            {{ $onHoldCount }}
                        </div>

                    </div>

                </div>


                <!-- KEY ISSUES -->

                <div class="text-section">

                    <label>
                        KEY ISSUES &amp; OBSERVATIONS
                    </label>

                    <textarea></textarea>

                </div>


                <!-- RECOMMENDATIONS -->

                <div class="text-section">

                    <label>
                        RECOMMENDATIONS
                    </label>

                    <textarea></textarea>

                </div>


                <!-- SUMMARY & WAY FORWARD -->

                <div class="text-section">

                    <label>
                        SUMMARY &amp; WAY FORWARD
                    </label>

                    <textarea></textarea>

                </div>


                <!-- PROJECT DETAILS -->

                <div class="project-details">

                    <div>

                        <div class="detail-row">

                            <div>
                                Project Start Date:
                            </div>

                            <div class="detail-value">
                                {{ optional($deal->planned_start_date)->format('M d, Y') ?: '-' }}
                            </div>

                        </div>

                        <div class="detail-row">

                            <div>
                                Target Completion Date:
                            </div>

                            <div class="detail-value">
                                {{ optional($deal->estimated_completion_date)->format('M d, Y') ?: '-' }}
                            </div>

                        </div>

                        <div class="detail-row">

                            <div>
                                NTP Status:
                            </div>

                            <div class="detail-value">
                                Pending
                            </div>

                        </div>

                    </div>


                    <div>

                        <div class="detail-row">

                            <div>
                                Client Preferred Completion Date:
                            </div>

                            <div class="detail-value">
                                {{ optional($deal->client_preferred_completion_date)->format('M d, Y') ?: '-' }}
                            </div>

                        </div>

                        <div class="detail-row">

                            <div>
                                Approval Status:
                            </div>

                            <div class="detail-value">
                                Draft
                            </div>

                        </div>

                    </div>

                </div>


                <!-- CLIENT CONFIRMATION -->

                <div class="client-confirmation">

                    <div class="blue-section-title">
                        CLIENT CONFIRMATION
                    </div>

                    <div class="client-name">

                        <div>
                            {{ $clientName ?: '-' }}
                        </div>

                        <div class="client-line"></div>

                        <div class="client-signature">
                            Client Fullname &amp; Signature
                        </div>

                    </div>

                </div>


                <!-- INTERNAL APPROVAL -->

                <div class="internal-approval">

                    <div class="blue-section-title">
                        INTERNAL APPROVAL
                    </div>


                    <div class="approval-grid">


                        <div class="approval-column">

                            <div class="approval-row">

                                <div class="approval-label">
                                    Prepared By:
                                </div>

                                <div class="approval-value">
                                    {{ $deal->prepared_by ?: '-' }}
                                </div>

                            </div>


                            <div class="approval-row">

                                <div class="approval-label">
                                    Referred By/Closed By:
                                </div>

                                <div class="approval-value">
                                    {{ $deal->referred_by ?: '-' }}
                                </div>

                            </div>


                            <div class="approval-row">

                                <div class="approval-label">
                                    Lead Consultant:
                                </div>

                                <div class="approval-value">
                                    {{ $deal->lead_consultant ?: '-' }}
                                </div>

                            </div>


                            <div class="approval-row">

                                <div class="approval-label">
                                    Finance:
                                </div>

                                <div class="approval-value">
                                   {{ $deal->finance ?: '-' }}
                                </div>

                            </div>

                        </div>


                        <div class="approval-column">

                            <div class="approval-row">

                                <div class="approval-label">
                                    Reviewed By:
                                </div>

                                <div class="approval-value">
                                    {{ $deal->reviewed_by ?: '-' }}
                                </div>

                            </div>


                            <div class="approval-row">

                                <div class="approval-label">
                                    Sales &amp; Marketing:
                                </div>

                                <div class="approval-value">
                                   {{ $deal->sales_marketing ?: '-' }}
                                </div>

                            </div>


                            <div class="approval-row">

                                <div class="approval-label">
                                    Lead Associate Assigned:
                                </div>

                                <div class="approval-value">
                                  {{ $deal->lead_associate ?: '-' }}
                                </div>

                            </div>


                            <div class="approval-row">

                                <div class="approval-label">
                                    President:
                                </div>

                                <div class="approval-value">
                                    {{ $deal->president ?: '-' }}
                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="record-row">

                        <div class="record-signature">
                        <input
                            type="text"
                            name="record_custodian"
                            value="{{ $deal->record_custodian }}"
                            placeholder="Record Custodian (Name and Signature)"
                        >
                    </div>

                        <div class="record-dates">

                            <div class="record-date">

                                <div>
                                    Date Recorded :
                                </div>

                                <div class="record-date-value">
                                    <span>{{ optional($deal->date_recorded)->format('m/d/Y') ?: '-' }}</span>
                                    <span>▣</span>
                                </div>

                            </div>


                            <div class="record-date">

                                <div>
                                    Date Signed :
                                </div>

                                <div class="record-date-value">
                                    <span>{{ optional($deal->date_signed)->format('m/d/Y') ?: '-' }}</span>
                                    <span>▣</span>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

             </form>
            </section>

        </div>

    </div>

</div>

</main>


<script>

/* =========================
   SCREEN SWITCHING
========================= */

function showScope() {

    document.querySelector('.project-button:nth-child(1)')
        .classList.add('active');

    document.querySelector('.project-button:nth-child(2)')
        .classList.remove('active');

    location.reload();
}


function showSowReport() {

    document.querySelector('.project-button:nth-child(1)')
        .classList.remove('active');

    document.querySelector('.project-button:nth-child(2)')
        .classList.add('active');

    document.getElementById("screenContent").innerHTML = `

        <section class="sow-screen">

            <div class="report-header">

                <div class="report-label">
                    REPORT REGISTRY
                </div>

                <h2 class="report-title">
                    SOW Reports
                </h2>

                <div class="report-description">
                    Generated scope of work reports are recorded here from the Scope of Work tab.
                </div>

                <div class="report-summary">

                    <div class="summary-pill">
                        <span>Total Reports</span>
                        <span class="summary-value">0</span>
                    </div>

                    <div class="summary-pill">
                        <span>Latest Report</span>
                        <span class="summary-value">-</span>
                    </div>

                </div>

            </div>


            <div class="report-table-card">

                <div class="table-search-area">

                    <div class="search-wrapper">

                        <span class="table-search-icon">
                            ⌕
                        </span>

                        <input
                            type="text"
                            class="table-search"
                            placeholder="Search report number or status..."
                        >

                    </div>

                </div>


                <div class="table-header">

                    <div>
                        <div class="select-box"></div>
                    </div>

                    <div>
                        REPORT NO.
                    </div>

                    <div>
                        DATE OF REPORTING
                    </div>

                    <div>
                        DATE SENT TO CLIENT
                    </div>

                    <div>
                        DATE APPROVED
                    </div>

                    <div>
                        STATUS
                    </div>

                </div>


                <div class="empty-state">

                    No generated SOW reports yet.
                    <strong>
                        Use Generate SOW Report
                    </strong>
                    in the Scope of Work tab.

                </div>

            </div>

        </section>

    `;
}


/* =========================
   QUICK ACTIONS
========================= */

function saveScope() {
    alert("Scope of Work saved.");
}

function generateSowReport() {
    alert("Generate SOW Report");
}

function generateCoc() {
    alert("Generate COC");
}

function generateTransmittal() {
    alert("Generate Transmittal");
}

function generateNtp() {
    alert("Generate NTP");
}

function downloadPdf() {
    alert("Download PDF");
}

function manualApproveSow() {
    alert("Manual Approve SOW");
}

function uploadNtp() {
    alert("Upload Signed NTP & Approve");
}

function uploadCoc() {
    alert("Upload Signed COC & Complete");
}


/* =========================
   DELETE ROW
========================= */

function deleteRow(button) {

    const row = button.closest("tr");

    if (row) {
        row.remove();
        updateTotals();
    }
}


/* =========================
   ADD WITHIN SCOPE ROW
========================= */

function addWithinScopeRow() {

    const body = document.getElementById("withinScopeBody");
    const index = body.querySelectorAll("tr").length;

    const row = document.createElement("tr");

    row.innerHTML = `

        <td>
            <input type="text"
                name="within_scope[${index}][main_task]">
        </td>

        <td>
            <input type="text"
                name="within_scope[${index}][sub_task]">
        </td>

        <td>
            <input type="text"
                name="within_scope[${index}][responsible]">
        </td>

        <td>
            <input type="number"
                name="within_scope[${index}][duration]">
        </td>

        <td>
            <input type="date"
                name="within_scope[${index}][start_date]">
        </td>

        <td>
            <input type="date"
                name="within_scope[${index}][end_date]">
        </td>

        <td>
            <select name="within_scope[${index}][status]">
                <option value="Open">Open</option>
                <option value="In Progress">In Progress</option>
                <option value="Delayed">Delayed</option>
                <option value="Complete">Complete</option>
                <option value="On Hold">On Hold</option>
            </select>
        </td>

        <td>
            <input type="text"
                name="within_scope[${index}][remarks]">
        </td>

        <td class="delete-cell">
            <button
                type="button"
                class="delete-row"
                onclick="deleteRow(this)">
                ×
            </button>
        </td>

    `;

    body.appendChild(row);

    updateTotals();
}

/* =========================
   ADD OUT OF SCOPE ROW
========================= */

function addOutScopeRow() {

    const body = document.getElementById("outScopeBody");
    const index = body.querySelectorAll("tr").length;

    const row = document.createElement("tr");

    row.innerHTML = `

        <td>
            <input type="text"
                name="out_of_scope[${index}][main_task]">
        </td>

        <td>
            <input type="text"
                name="out_of_scope[${index}][sub_task]">
        </td>

        <td>
            <input type="text"
                name="out_of_scope[${index}][responsible]">
        </td>

        <td>
            <input type="number"
                name="out_of_scope[${index}][duration]">
        </td>

        <td>
            <input type="date"
                name="out_of_scope[${index}][start_date]">
        </td>

        <td>
            <input type="date"
                name="out_of_scope[${index}][end_date]">
        </td>

        <td>
            <select name="out_of_scope[${index}][status]">
                <option value="Open">Open</option>
                <option value="In Progress">In Progress</option>
                <option value="Delayed">Delayed</option>
                <option value="Complete">Complete</option>
                <option value="On Hold">On Hold</option>
            </select>
        </td>

        <td>
            <input type="text"
                name="out_of_scope[${index}][remarks]">
        </td>

        <td class="delete-cell">
            <button
                type="button"
                class="delete-row"
                onclick="deleteRow(this)">
                ×
            </button>
        </td>

    `;

    body.appendChild(row);

    updateTotals();
}


/* =========================
   UPDATE TOTALS
========================= */

function updateTotals() {

    const withinBody =
        document.getElementById("withinScopeBody");

    const outBody =
        document.getElementById("outScopeBody");

    const withinCount =
        withinBody
            ? withinBody.querySelectorAll("tr").length
            : 0;

    const outCount =
        outBody
            ? outBody.querySelectorAll("tr").length
            : 0;

    document.getElementById("withinTotal").textContent =
        withinCount + " item(s)";

    document.getElementById("outTotal").textContent =
        outCount + " item(s)";

    document.getElementById("totalMainTasks").textContent =
        withinCount;
}


/* =========================
   INITIAL TOTAL
========================= */

updateTotals();

</script>

@include('components.deal-drawer', ['returnTo' => url()->current()])

</body>
</html>