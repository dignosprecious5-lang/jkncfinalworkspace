<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>START Workspace | ORDO Deals</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }

        /* =====================================================
           APP LAYOUT
        ===================================================== */

        .app {
            display: flex;
            min-height: 100vh;
        }

        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar {
            width: 245px;
            background: #172033;
            color: #ffffff;
            padding: 24px 16px;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            overflow-y: auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 12px 28px;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .logo-mark {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            background: #2f80ed;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .nav-section {
            margin-top: 12px;
        }

        .nav-label {
            color: #8993a7;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 12px 9px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #c8cfdb;
            text-decoration: none;
            padding: 11px 12px;
            border-radius: 8px;
            margin-bottom: 4px;
            font-size: 14px;
            transition: 0.2s;
        }

        .nav-item:hover {
            background: #222d43;
            color: #ffffff;
        }

        .nav-item.active {
            background: #2f80ed;
            color: #ffffff;
            font-weight: 600;
        }

        .nav-icon {
            width: 20px;
            text-align: center;
            font-size: 15px;
        }

        /* =====================================================
           MAIN
        ===================================================== */

        .main {
            margin-left: 245px;
            width: calc(100% - 245px);
            min-height: 100vh;
        }

        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {
            height: 70px;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
        }

        .breadcrumbs {
            color: #7b8494;
            font-size: 13px;
        }

        .breadcrumbs strong {
            color: #263247;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .search {
            width: 220px;
            height: 36px;
            border: 1px solid #dfe4ec;
            border-radius: 8px;
            padding: 0 12px;
            outline: none;
        }

        .notification {
            font-size: 19px;
            color: #667085;
        }

        .user {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e8f1ff;
            color: #2f80ed;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
        }

        /* =====================================================
           CONTENT
        ===================================================== */

        .content {
            padding: 30px 34px 50px;
            max-width: 1500px;
            margin: 0 auto;
        }

        .page-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .page-heading h1 {
            font-size: 27px;
            color: #172033;
            margin-bottom: 7px;
        }

        .page-heading p {
            color: #7b8494;
            font-size: 14px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 12px;
            border-radius: 20px;
            background: #fff4db;
            color: #9a6700;
            font-size: 12px;
            font-weight: 600;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #e5a11a;
        }

        /* =====================================================
           WORKFLOW
        ===================================================== */

        .workflow {
            background: #ffffff;
            border: 1px solid #e5e9f0;
            border-radius: 12px;
            padding: 18px 22px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(20, 32, 55, 0.04);
        }

        .workflow-title {
            color: #6b7280;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .8px;
            margin-bottom: 15px;
        }

        .workflow-steps {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .workflow-step {
            padding: 9px 14px;
            border-radius: 8px;
            background: #f1f4f8;
            color: #667085;
            font-size: 12px;
            font-weight: 600;
        }

        .workflow-step.completed {
            background: #e9f7ef;
            color: #277a4c;
        }

        .workflow-step.current {
            background: #e8f1ff;
            color: #2f80ed;
        }

        .workflow-arrow {
            color: #a1a9b7;
        }

        /* =====================================================
           SUMMARY CARDS
        ===================================================== */

        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e5e9f0;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(20, 32, 55, 0.04);
        }

        .card-header {
            padding: 18px 20px;
            border-bottom: 1px solid #edf0f4;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 15px;
            color: #172033;
        }

        .card-body {
            padding: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .info-label {
            color: #8a93a3;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 5px;
        }

        .info-value {
            color: #263247;
            font-size: 14px;
            font-weight: 600;
        }

        /* =====================================================
           ACTIVATION STATUS
        ===================================================== */

        .activation-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 11px 0;
            border-bottom: 1px solid #eef1f5;
        }

        .activation-item:last-child {
            border-bottom: none;
        }

        .activation-left {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }

        .check {
            width: 21px;
            height: 21px;
            border-radius: 50%;
            background: #e9f7ef;
            color: #277a4c;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }

        .activation-state {
            font-size: 12px;
            color: #667085;
        }

        /* =====================================================
           SECTION
        ===================================================== */

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .section-heading h2 {
            font-size: 18px;
            color: #172033;
        }

        .section-heading p {
            font-size: 12px;
            color: #8993a3;
            margin-top: 4px;
        }

        /* =====================================================
           BUTTONS
        ===================================================== */

        .btn {
            border: none;
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
        }

        .btn-primary {
            background: #2f80ed;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #216dcc;
        }

        .btn-secondary {
            background: #f1f4f8;
            color: #465166;
        }

        .btn-secondary:hover {
            background: #e6eaf0;
        }

        .btn-outline {
            background: #ffffff;
            border: 1px solid #dce2ea;
            color: #465166;
        }

        /* =====================================================
           ENGAGEMENT GROUP
        ===================================================== */

        .group-card {
            margin-bottom: 18px;
            overflow: hidden;
        }

        .group-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .group-code {
            color: #2f80ed;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: .5px;
        }

        .group-title {
            font-size: 18px;
            color: #172033;
            margin-bottom: 5px;
        }

        .group-type {
            color: #7b8494;
            font-size: 12px;
        }

        .group-status {
            padding: 6px 11px;
            border-radius: 18px;
            background: #fff4db;
            color: #9a6700;
            font-size: 11px;
            font-weight: 600;
        }

        .group-content {
            padding: 20px;
        }

        .scope-box {
            background: #f8f9fb;
            border-radius: 9px;
            padding: 15px;
            margin: 18px 0;
        }

        .scope-title {
            color: #7b8494;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 7px;
        }

        .scope-text {
            color: #465166;
            font-size: 13px;
            line-height: 1.5;
        }

        .group-details {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        /* =====================================================
           ASSIGNMENTS
        ===================================================== */

        .assignment-section {
            border-top: 1px solid #edf0f4;
            padding-top: 18px;
        }

        .assignment-title {
            font-size: 13px;
            font-weight: 700;
            color: #263247;
            margin-bottom: 10px;
        }

        .assignment {
            background: #f8f9fb;
            border-radius: 8px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .assignment-person {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e8f1ff;
            color: #2f80ed;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }

        .assignment-role {
            font-size: 11px;
            color: #8a93a3;
            margin-bottom: 2px;
        }

        .assignment-name {
            font-size: 13px;
            color: #263247;
            font-weight: 600;
        }

        .assignment-status {
            background: #e9f7ef;
            color: #277a4c;
            border-radius: 15px;
            padding: 5px 10px;
            font-size: 10px;
            font-weight: 700;
        }

        .no-assignment {
            color: #8993a3;
            font-size: 12px;
            padding: 12px 0;
        }

        .group-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 16px;
        }

        /* =====================================================
           ALERTS
        ===================================================== */

        .alert {
            border-radius: 9px;
            padding: 12px 15px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .alert-success {
            background: #e9f7ef;
            color: #277a4c;
            border: 1px solid #cdebd9;
        }

        .alert-error {
            background: #fff0f0;
            color: #b42318;
            border: 1px solid #f2cccc;
        }

        .alert-error ul {
            margin: 8px 0 0 18px;
        }

        /* =====================================================
           EMPTY STATE
        ===================================================== */

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #8993a3;
        }

        .empty-state-icon {
            font-size: 30px;
            margin-bottom: 12px;
        }

        .empty-state h3 {
            color: #465166;
            font-size: 15px;
            margin-bottom: 6px;
        }

        .empty-state p {
            font-size: 12px;
        }

        /* =====================================================
           MODAL
        ===================================================== */

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            z-index: 1000;
            padding: 30px;
            overflow-y: auto;
        }

        .modal-overlay.open {
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .modal {
            background: #ffffff;
            width: 760px;
            max-width: 100%;
            border-radius: 14px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .2);
            margin: 20px auto;
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e8ebf0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 17px;
            color: #172033;
        }

        .close-btn {
            border: none;
            background: transparent;
            font-size: 23px;
            color: #7b8494;
            cursor: pointer;
        }

        .modal-body {
            padding: 24px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 17px;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            font-size: 12px;
            color: #465166;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .form-control {
            width: 100%;
            border: 1px solid #dce2ea;
            border-radius: 8px;
            padding: 10px 11px;
            font-size: 13px;
            color: #263247;
            background: #ffffff;
            outline: none;
        }

        .form-control:focus {
            border-color: #2f80ed;
            box-shadow: 0 0 0 3px rgba(47, 128, 237, .1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 90px;
        }

        .modal-footer {
            border-top: 1px solid #e8ebf0;
            padding: 16px 24px;
            display: flex;
            justify-content: flex-end;
            gap: 9px;
        }

        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 1000px) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
                width: calc(100% - 210px);
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .group-details {
                grid-template-columns: 1fr 1fr;
            }

            .search {
                width: 150px;
            }
        }

        @media (max-width: 760px) {

            .sidebar {
                width: 70px;
                padding: 20px 8px;
            }

            .logo {
                justify-content: center;
                padding: 0 0 25px;
            }

            .logo-text,
            .nav-label,
            .nav-item span:not(.nav-icon) {
                display: none;
            }

            .nav-item {
                justify-content: center;
                padding: 12px;
            }

            .main {
                margin-left: 70px;
                width: calc(100% - 70px);
            }

            .topbar {
                padding: 0 16px;
            }

            .breadcrumbs {
                font-size: 11px;
            }

            .search {
                display: none;
            }

            .content {
                padding: 20px 15px 40px;
            }

            .page-heading {
                flex-direction: column;
                gap: 12px;
            }

            .workflow-steps {
                overflow-x: auto;
                flex-wrap: nowrap;
            }

            .workflow-step {
                white-space: nowrap;
            }

            .info-grid,
            .group-details,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .group-top {
                flex-direction: column;
            }

            .group-actions {
                flex-wrap: wrap;
            }
        }
    </style>
</head>

<body>

<div class="app">

    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">

        <div class="logo">
            <div class="logo-mark">O</div>
            <span class="logo-text">ORDO</span>
        </div>

        <div class="nav-section">

            <div class="nav-label">
                Workspace
            </div>

            <a href="<?php echo e(route('deals.index')); ?>" class="nav-item">
                <span class="nav-icon">▦</span>
                <span>Dashboard</span>
            </a>

            <a href="<?php echo e(route('deals.index')); ?>" class="nav-item">
                <span class="nav-icon">◎</span>
                <span>Accounts</span>
            </a>

            <a href="<?php echo e(route('deals.index')); ?>" class="nav-item">
                <span class="nav-icon">◆</span>
                <span>Deals</span>
            </a>

            <a href="#" class="nav-item">
                <span class="nav-icon">◈</span>
                <span>CASA</span>
            </a>

            <a
                href="<?php echo e(route('deals.start', ['id' => $deal->id])); ?>"
                class="nav-item active"
            >
                <span class="nav-icon">◉</span>
                <span>START</span>
            </a>

            <a href="<?php echo e(route('projects.index')); ?>" class="nav-item">
                <span class="nav-icon">▤</span>
                <span>Engagements</span>
            </a>

            <a href="#" class="nav-item">
                <span class="nav-icon">▧</span>
                <span>Documents</span>
            </a>

            <a href="#" class="nav-item">
                <span class="nav-icon">▥</span>
                <span>Reports</span>
            </a>

            <a href="#" class="nav-item">
                <span class="nav-icon">◇</span>
                <span>Incentives</span>
            </a>

        </div>

        <div class="nav-section">

            <div class="nav-label">
                System
            </div>

            <a href="#" class="nav-item">
                <span class="nav-icon">⚙</span>
                <span>Settings</span>
            </a>

        </div>

    </aside>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="main">

        <!-- TOPBAR -->

        <header class="topbar">

            <div class="breadcrumbs">

                Deals

                <span> / </span>

                <strong>
                    <?php echo e($deal->deal_code); ?>

                </strong>

                <span> / </span>

                START

            </div>

            <div class="topbar-right">

                <input
                    type="text"
                    class="search"
                    placeholder="Search..."
                >

                <div class="notification">
                    ♢
                </div>

                <div class="user">
                    <?php echo e(strtoupper(substr($deal->created_by ?: 'U', 0, 1))); ?>

                </div>

            </div>

        </header>


        <!-- CONTENT -->

        <section class="content">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <div class="page-heading">

                <div>

                    <h1>
                        START Workspace
                    </h1>

                    <p>

                        <?php echo e($deal->deal_code); ?>


                        <?php if($deal->primary_contact_name): ?>

                            — <?php echo e($deal->primary_contact_name); ?>


                        <?php else: ?>

                            — <?php echo e(trim(collect([
                                $deal->first_name,
                                $deal->middle_initial,
                                $deal->last_name
                            ])->filter()->implode(' '))); ?>


                        <?php endif; ?>

                    </p>

                </div>


                <div>

                    <span class="status-pill">

                        <span class="status-dot"></span>

                        <?php echo e($start->status ?: 'Draft'); ?>


                    </span>

                </div>

            </div>


            <!-- =================================================
                 SUCCESS / ERRORS
            ================================================== -->

            <?php if(session('success')): ?>

                <div class="alert alert-success">

                    ✓
                    <?php echo e(session('success')); ?>


                </div>

            <?php endif; ?>


            <?php if($errors->any()): ?>

                <div class="alert alert-error">

                    <strong>
                        Please fix the following:
                    </strong>

                    <ul>

                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                            <li>
                                <?php echo e($error); ?>

                            </li>

                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    </ul>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 WORKFLOW
            ================================================== -->

            <div class="workflow">

                <div class="workflow-title">
                    Commercial Lifecycle
                </div>

                <div class="workflow-steps">

                    <div class="workflow-step completed">
                        Account
                    </div>

                    <span class="workflow-arrow">→</span>

                    <div class="workflow-step completed">
                        Deal
                    </div>

                    <span class="workflow-arrow">→</span>

                    <div class="workflow-step completed">
                        CASA
                    </div>

                    <span class="workflow-arrow">→</span>

                    <div class="workflow-step current">
                        START
                    </div>

                    <span class="workflow-arrow">→</span>

                    <div class="workflow-step">
                        Service Memo
                    </div>

                    <span class="workflow-arrow">→</span>

                    <div class="workflow-step">
                        Regular / Project
                    </div>

                </div>

            </div>


            <!-- =================================================
                 SUMMARY
            ================================================== -->

            <div class="summary-grid">


                <!-- START SUMMARY -->

                <div class="card">

                    <div class="card-header">

                        <h2>
                            START Summary
                        </h2>

                    </div>

                    <div class="card-body">

                        <div class="info-grid">

                            <div>

                                <div class="info-label">
                                    Deal
                                </div>

                                <div class="info-value">
                                    <?php echo e($deal->deal_code); ?>

                                </div>

                            </div>


                            <div>

                                <div class="info-label">
                                    START Record
                                </div>

                                <div class="info-value">
                                    START-<?php echo e(str_pad($start->id, 3, '0', STR_PAD_LEFT)); ?>

                                </div>

                            </div>


                            <div>

                                <div class="info-label">
                                    Client
                                </div>

                                <div class="info-value">

                                    <?php echo e($deal->primary_contact_name
                                        ?: trim(collect([
                                            $deal->first_name,
                                            $deal->middle_initial,
                                            $deal->last_name,
                                            $deal->name_extension
                                        ])->filter()->implode(' '))); ?>


                                </div>

                            </div>


                            <div>

                                <div class="info-label">
                                    Customer Type
                                </div>

                                <div class="info-value">
                                    <?php echo e($deal->customer_type ?: '-'); ?>

                                </div>

                            </div>


                            <div>

                                <div class="info-label">
                                    Engagement Type
                                </div>

                                <div class="info-value">
                                    <?php echo e($deal->engagement_type ?: '-'); ?>

                                </div>

                            </div>


                            <div>

                                <div class="info-label">
                                    START Status
                                </div>

                                <div class="info-value">
                                    <?php echo e($start->status ?: 'Draft'); ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ACTIVATION STATUS -->

                <div class="card">

                    <div class="card-header">

                        <h2>
                            Activation Status
                        </h2>

                    </div>

                    <div class="card-body">


                        <div class="activation-item">

                            <div class="activation-left">

                                <span class="check">
                                    ✓
                                </span>

                                <span>
                                    START Record
                                </span>

                            </div>

                            <span class="activation-state">
                                Created
                            </span>

                        </div>


                        <div class="activation-item">

                            <div class="activation-left">

                                <span class="check">
                                    ✓
                                </span>

                                <span>
                                    Engagement Groups
                                </span>

                            </div>

                            <span class="activation-state">
                                <?php echo e($start->engagementGroups->count()); ?>

                            </span>

                        </div>


                        <div class="activation-item">

                            <div class="activation-left">

                                <span class="check">
                                    ✓
                                </span>

                                <span>
                                    Assignments
                                </span>

                            </div>

                            <span class="activation-state">

                                <?php echo e($start->engagementGroups->sum(function ($group) {
                                    return $group->assignments->count();
                                })); ?>


                            </span>

                        </div>


                        <div class="activation-item">

                            <div class="activation-left">

                                <span class="check">
                                    ●
                                </span>

                                <span>
                                    Current Step
                                </span>

                            </div>

                            <span class="activation-state">
                                <?php echo e($start->status ?: 'Draft'); ?>

                            </span>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 ENGAGEMENT GROUPS
            ================================================== -->

            <div class="section-heading">

                <div>

                    <h2>
                        Engagement Groups
                    </h2>

                    <p>
                        Structure the operational engagements created from this START record.
                    </p>

                </div>


                <button
                    type="button"
                    class="btn btn-primary"
                    onclick="openGroupModal()"
                >
                    + Add Engagement Group
                </button>

            </div>


            <!-- =================================================
                 GROUP LIST
            ================================================== -->

            <?php $__empty_1 = true; $__currentLoopData = $start->engagementGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                <div class="card group-card">

                    <div class="group-content">


                        <!-- GROUP HEADER -->

                        <div class="group-top">

                            <div>

                                <div class="group-code">

                                    <?php echo e($group->group_code ?: 'GROUP-' . str_pad($group->id, 3, '0', STR_PAD_LEFT)); ?>


                                </div>


                                <h3 class="group-title">

                                    <?php echo e($group->title ?: $group->group_name ?: 'Unnamed Group'); ?>


                                </h3>


                                <div class="group-type">

                                    <?php echo e($group->engagement_type); ?>


                                </div>

                            </div>


                            <div class="group-status">

                                <?php echo e($group->status ?: 'Draft'); ?>


                            </div>

                        </div>


                        <!-- SCOPE -->

                        <div class="scope-box">

                            <div class="scope-title">
                                Scope / Deliverables
                            </div>

                            <div class="scope-text">

                                <?php echo e($group->scope_deliverables ?: 'No scope or deliverables have been defined yet.'); ?>


                            </div>

                        </div>


                        <!-- DETAILS -->

                        <div
                            class="group-details"
                            id="group-details-<?php echo e($group->id); ?>"
                            style="display: none;"
                        >


                            <div>

                                <div class="info-label">
                                    Start Date
                                </div>

                                <div class="info-value">

                                    <?php echo e($group->start_date
                                        ? \Carbon\Carbon::parse($group->start_date)->format('F d, Y')
                                        : '-'); ?>


                                </div>

                            </div>


                            <div>

                                <div class="info-label">
                                    Target End Date
                                </div>

                                <div class="info-value">

                                    <?php echo e($group->target_end_date
                                        ? \Carbon\Carbon::parse($group->target_end_date)->format('F d, Y')
                                        : '-'); ?>


                                </div>

                            </div>


                            <div>

                                <div class="info-label">
                                    Duration
                                </div>

                                <div class="info-value">

                                    <?php echo e($group->duration_days
                                        ? $group->duration_days . ' days'
                                        : '-'); ?>


                                </div>

                            </div>


                            <div>

                                <div class="info-label">
                                    Project Manager
                                </div>

                                <div class="info-value">

                                    <?php echo e($group->project_manager ?: '-'); ?>


                                </div>

                            </div>


                            <div>

                                <div class="info-label">
                                    Service Frequency
                                </div>

                                <div class="info-value">

                                    <?php echo e($group->service_frequency ?: '-'); ?>


                                </div>

                            </div>


                            <div>

                                <div class="info-label">
                                    Billing Frequency
                                </div>

                                <div class="info-value">

                                    <?php echo e($group->billing_frequency ?: '-'); ?>


                                </div>

                            </div>


                            <div>

                                <div class="info-label">
                                    Reporting Frequency
                                </div>

                                <div class="info-value">

                                    <?php echo e($group->reporting_frequency ?: '-'); ?>


                                </div>

                            </div>


                            <div>

                                <div class="info-label">
                                    Deal Items
                                </div>

                                <div class="info-value">

                                    <?php if($group->deal_item_ids): ?>

                                        <?php echo e(is_array($group->deal_item_ids)
                                            ? count($group->deal_item_ids)
                                            : count(json_decode($group->deal_item_ids, true) ?: [])); ?>


                                    <?php else: ?>

                                        -

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>


                        <!-- ASSIGNMENTS -->

                        <div
                            class="assignment-section"
                            id="group-assignments-<?php echo e($group->id); ?>"
                        >

                            <div class="assignment-title">
                                Assignments
                            </div>


                            <?php $__empty_2 = true; $__currentLoopData = $group->assignments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>

                                <div class="assignment">

                                    <div class="assignment-person">

                                        <div class="avatar">

                                            <?php echo e(strtoupper(substr($assignment->assigned_to ?: 'U', 0, 1))); ?>


                                        </div>

                                        <div>

                                            <div class="assignment-role">

                                                <?php echo e($assignment->role); ?>


                                            </div>

                                            <div class="assignment-name">

                                                <?php echo e($assignment->assigned_to ?: 'Unassigned'); ?>


                                            </div>

                                        </div>

                                    </div>


                                    <div class="assignment-status">

                                        <?php echo e($assignment->status ?: 'Pending'); ?>


                                    </div>

                                </div>

                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>

                                <div class="no-assignment">

                                    No assignments have been created for this engagement group yet.

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- ACTIONS -->

                        <div class="group-actions">

                            <button
                                type="button"
                                class="btn btn-secondary"
                                onclick="toggleGroupDetails(<?php echo e($group->id); ?>)"
                            >
                                View Details
                            </button>


                            <button
                                type="button"
                                class="btn btn-outline"
                                onclick="editEngagementGroup(<?php echo e($group->id); ?>)"
                            >
                                Edit
                            </button>


                            <button
                                type="button"
                                class="btn btn-primary"
                                onclick="showAssignments(<?php echo e($group->id); ?>)"
                            >
                                Assignments
                            </button>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     EDIT ENGAGEMENT GROUP MODAL
                ================================================== -->

                <div
                    id="editGroupModal-<?php echo e($group->id); ?>"
                    class="modal-overlay edit-group-modal"
                    onclick="closeEditGroupModal(event, <?php echo e($group->id); ?>)"
                >

                    <div
                        class="modal"
                        onclick="event.stopPropagation()"
                    >

                        <div class="modal-header">

                            <div>

                                <h2>
                                    Edit Engagement Group
                                </h2>

                                <p
                                    style="
                                        margin: 5px 0 0;
                                        color: #94a3b8;
                                        font-size: 10px;
                                    "
                                >
                                    <?php echo e($group->group_code ?: 'GROUP-' . str_pad($group->id, 3, '0', STR_PAD_LEFT)); ?>

                                </p>

                            </div>


                            <button
                                type="button"
                                class="close-btn"
                                onclick="closeEditGroupModal(null, <?php echo e($group->id); ?>)"
                            >
                                ×
                            </button>

                        </div>


                        <form
                            method="POST"
                            action="<?php echo e(route('deals.start.groups.update', [
                                'id' => $deal->id,
                                'groupId' => $group->id
                            ])); ?>"
                        >

                            <?php echo csrf_field(); ?>

                            <?php echo method_field('PUT'); ?>


                            <div class="modal-body">

                                <div class="form-grid">


                                    <!-- ENGAGEMENT TYPE -->

                                    <div class="form-group">

                                        <label
                                            class="form-label"
                                            for="edit_engagement_type_<?php echo e($group->id); ?>"
                                        >
                                            Engagement Type *
                                        </label>

                                        <select
                                            class="form-control"
                                            name="engagement_type"
                                            id="edit_engagement_type_<?php echo e($group->id); ?>"
                                            required
                                        >

                                            <option value="">
                                                Select Engagement Type
                                            </option>

                                            <option
                                                value="Regular Engagement"
                                                <?php echo e($group->engagement_type === 'Regular Engagement' ? 'selected' : ''); ?>

                                            >
                                                Regular Engagement
                                            </option>

                                            <option
                                                value="Project Engagement"
                                                <?php echo e($group->engagement_type === 'Project Engagement' ? 'selected' : ''); ?>

                                            >
                                                Project Engagement
                                            </option>

                                        </select>

                                    </div>


                                    <!-- GROUP NAME -->

                                    <div class="form-group">

                                        <label
                                            class="form-label"
                                            for="edit_group_name_<?php echo e($group->id); ?>"
                                        >
                                            Group Name *
                                        </label>

                                        <input
                                            type="text"
                                            class="form-control"
                                            name="group_name"
                                            id="edit_group_name_<?php echo e($group->id); ?>"
                                            value="<?php echo e($group->group_name); ?>"
                                            required
                                        >

                                    </div>


                                    <!-- TITLE -->

                                    <div class="form-group full">

                                        <label
                                            class="form-label"
                                            for="edit_title_<?php echo e($group->id); ?>"
                                        >
                                            Engagement Title
                                        </label>

                                        <input
                                            type="text"
                                            class="form-control"
                                            name="title"
                                            id="edit_title_<?php echo e($group->id); ?>"
                                            value="<?php echo e($group->title); ?>"
                                        >

                                    </div>


                                    <!-- SCOPE -->

                                    <div class="form-group full">

                                        <label
                                            class="form-label"
                                            for="edit_scope_deliverables_<?php echo e($group->id); ?>"
                                        >
                                            Scope / Deliverables
                                        </label>

                                        <textarea
                                            class="form-control"
                                            name="scope_deliverables"
                                            id="edit_scope_deliverables_<?php echo e($group->id); ?>"
                                        ><?php echo e($group->scope_deliverables); ?></textarea>

                                    </div>


                                    <!-- START DATE -->

                                    <div class="form-group">

                                        <label
                                            class="form-label"
                                            for="edit_start_date_<?php echo e($group->id); ?>"
                                        >
                                            Start Date
                                        </label>

                                        <input
                                            type="date"
                                            class="form-control"
                                            name="start_date"
                                            id="edit_start_date_<?php echo e($group->id); ?>"
                                            value="<?php echo e($group->start_date ? \Carbon\Carbon::parse($group->start_date)->format('Y-m-d') : ''); ?>"
                                        >

                                    </div>


                                    <!-- TARGET END DATE -->

                                    <div class="form-group">

                                        <label
                                            class="form-label"
                                            for="edit_target_end_date_<?php echo e($group->id); ?>"
                                        >
                                            Target End Date
                                        </label>

                                        <input
                                            type="date"
                                            class="form-control"
                                            name="target_end_date"
                                            id="edit_target_end_date_<?php echo e($group->id); ?>"
                                            value="<?php echo e($group->target_end_date ? \Carbon\Carbon::parse($group->target_end_date)->format('Y-m-d') : ''); ?>"
                                        >

                                    </div>


                                    <!-- DURATION -->

                                    <div class="form-group">

                                        <label
                                            class="form-label"
                                            for="edit_duration_days_<?php echo e($group->id); ?>"
                                        >
                                            Duration (Days)
                                        </label>

                                        <input
                                            type="number"
                                            class="form-control"
                                            name="duration_days"
                                            id="edit_duration_days_<?php echo e($group->id); ?>"
                                            min="1"
                                            value="<?php echo e($group->duration_days); ?>"
                                        >

                                    </div>


                                    <!-- SERVICE FREQUENCY -->

                                    <div class="form-group">

                                        <label
                                            class="form-label"
                                            for="edit_service_frequency_<?php echo e($group->id); ?>"
                                        >
                                            Service Frequency
                                        </label>

                                        <select
                                            class="form-control"
                                            name="service_frequency"
                                            id="edit_service_frequency_<?php echo e($group->id); ?>"
                                        >

                                            <option value="">
                                                Select Frequency
                                            </option>

                                            <option
                                                value="One-time"
                                                <?php echo e($group->service_frequency === 'One-time' ? 'selected' : ''); ?>

                                            >
                                                One-time
                                            </option>

                                            <option
                                                value="Weekly"
                                                <?php echo e($group->service_frequency === 'Weekly' ? 'selected' : ''); ?>

                                            >
                                                Weekly
                                            </option>

                                            <option
                                                value="Monthly"
                                                <?php echo e($group->service_frequency === 'Monthly' ? 'selected' : ''); ?>

                                            >
                                                Monthly
                                            </option>

                                            <option
                                                value="Quarterly"
                                                <?php echo e($group->service_frequency === 'Quarterly' ? 'selected' : ''); ?>

                                            >
                                                Quarterly
                                            </option>

                                            <option
                                                value="Annual"
                                                <?php echo e($group->service_frequency === 'Annual' ? 'selected' : ''); ?>

                                            >
                                                Annual
                                            </option>

                                        </select>

                                    </div>


                                    <!-- BILLING FREQUENCY -->

                                    <div class="form-group">

                                        <label
                                            class="form-label"
                                            for="edit_billing_frequency_<?php echo e($group->id); ?>"
                                        >
                                            Billing Frequency
                                        </label>

                                        <select
                                            class="form-control"
                                            name="billing_frequency"
                                            id="edit_billing_frequency_<?php echo e($group->id); ?>"
                                        >

                                            <option value="">
                                                Select Billing Frequency
                                            </option>

                                            <option
                                                value="Full Payment Before Service"
                                                <?php echo e($group->billing_frequency === 'Full Payment Before Service' ? 'selected' : ''); ?>

                                            >
                                                Full Payment Before Service
                                            </option>

                                            <option
                                                value="Monthly"
                                                <?php echo e($group->billing_frequency === 'Monthly' ? 'selected' : ''); ?>

                                            >
                                                Monthly
                                            </option>

                                            <option
                                                value="Quarterly"
                                                <?php echo e($group->billing_frequency === 'Quarterly' ? 'selected' : ''); ?>

                                            >
                                                Quarterly
                                            </option>

                                            <option
                                                value="Milestone"
                                                <?php echo e($group->billing_frequency === 'Milestone' ? 'selected' : ''); ?>

                                            >
                                                Milestone
                                            </option>

                                        </select>

                                    </div>


                                    <!-- REPORTING FREQUENCY -->

                                    <div class="form-group">

                                        <label
                                            class="form-label"
                                            for="edit_reporting_frequency_<?php echo e($group->id); ?>"
                                        >
                                            Reporting Frequency
                                        </label>

                                        <select
                                            class="form-control"
                                            name="reporting_frequency"
                                            id="edit_reporting_frequency_<?php echo e($group->id); ?>"
                                        >

                                            <option value="">
                                                Select Frequency
                                            </option>

                                            <option
                                                value="Weekly"
                                                <?php echo e($group->reporting_frequency === 'Weekly' ? 'selected' : ''); ?>

                                            >
                                                Weekly
                                            </option>

                                            <option
                                                value="Monthly"
                                                <?php echo e($group->reporting_frequency === 'Monthly' ? 'selected' : ''); ?>

                                            >
                                                Monthly
                                            </option>

                                            <option
                                                value="Quarterly"
                                                <?php echo e($group->reporting_frequency === 'Quarterly' ? 'selected' : ''); ?>

                                            >
                                                Quarterly
                                            </option>

                                            <option
                                                value="As Required"
                                                <?php echo e($group->reporting_frequency === 'As Required' ? 'selected' : ''); ?>

                                            >
                                                As Required
                                            </option>

                                        </select>

                                    </div>


                                    <!-- PROJECT MANAGER -->

                                    <div class="form-group">

                                        <label
                                            class="form-label"
                                            for="edit_project_manager_<?php echo e($group->id); ?>"
                                        >
                                            Project Manager
                                        </label>

                                        <input
                                            type="text"
                                            class="form-control"
                                            name="project_manager"
                                            id="edit_project_manager_<?php echo e($group->id); ?>"
                                            value="<?php echo e($group->project_manager); ?>"
                                        >

                                    </div>


                                    <!-- NOTES -->

                                    <div class="form-group full">

                                        <label
                                            class="form-label"
                                            for="edit_notes_<?php echo e($group->id); ?>"
                                        >
                                            Notes
                                        </label>

                                        <textarea
                                            class="form-control"
                                            name="notes"
                                            id="edit_notes_<?php echo e($group->id); ?>"
                                        ><?php echo e($group->notes); ?></textarea>

                                    </div>


                                </div>

                            </div>


                            <!-- EDIT FOOTER -->

                            <div class="modal-footer">

                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    onclick="closeEditGroupModal(null, <?php echo e($group->id); ?>)"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    Save Changes
                                </button>

                            </div>

                        </form>

                    </div>

                </div>


            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                <div class="card">

                    <div class="empty-state">

                        <div class="empty-state-icon">
                            ◇
                        </div>

                        <h3>
                            No engagement groups yet
                        </h3>

                        <p>
                            Create an engagement group to begin structuring this START record.
                        </p>

                        <br>

                        <button
                            type="button"
                            class="btn btn-primary"
                            onclick="openGroupModal()"
                        >
                            + Create Engagement Group
                        </button>

                    </div>

                </div>

            <?php endif; ?>


        </section>

    </main>

</div>


<!-- ============================================================
     CREATE ENGAGEMENT GROUP MODAL
============================================================= -->

<div
    id="groupModal"
    class="modal-overlay"
    onclick="closeGroupModal(event)"
>

    <div
        class="modal"
        onclick="event.stopPropagation()"
    >

        <div class="modal-header">

            <h2>
                Create Engagement Group
            </h2>

            <button
                type="button"
                class="close-btn"
                onclick="closeGroupModal()"
            >
                ×
            </button>

        </div>


        <form
            method="POST"
            action="<?php echo e(route('deals.start.groups.store', ['id' => $deal->id])); ?>"
        >

            <?php echo csrf_field(); ?>


            <div class="modal-body">

                <div class="form-grid">


                    <div class="form-group">

                        <label
                            class="form-label"
                            for="engagement_type"
                        >
                            Engagement Type *
                        </label>

                        <select
                            class="form-control"
                            name="engagement_type"
                            id="engagement_type"
                            required
                        >

                            <option value="">
                                Select Engagement Type
                            </option>

                            <option value="Regular Engagement">
                                Regular Engagement
                            </option>

                            <option value="Project Engagement">
                                Project Engagement
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label
                            class="form-label"
                            for="group_name"
                        >
                            Group Name *
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="group_name"
                            id="group_name"
                            value="<?php echo e(old('group_name')); ?>"
                            placeholder="Example: Project Compliance Group"
                            required
                        >

                    </div>


                    <div class="form-group full">

                        <label
                            class="form-label"
                            for="title"
                        >
                            Engagement Title
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="title"
                            id="title"
                            value="<?php echo e(old('title')); ?>"
                            placeholder="Example: Business Compliance Project"
                        >

                    </div>


                    <div class="form-group full">

                        <label
                            class="form-label"
                            for="scope_deliverables"
                        >
                            Scope / Deliverables
                        </label>

                        <textarea
                            class="form-control"
                            name="scope_deliverables"
                            id="scope_deliverables"
                            placeholder="Describe the agreed scope and deliverables..."
                        ><?php echo e(old('scope_deliverables')); ?></textarea>

                    </div>


                    <div class="form-group">

                        <label
                            class="form-label"
                            for="start_date"
                        >
                            Start Date
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            name="start_date"
                            id="start_date"
                            value="<?php echo e(old('start_date')); ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label
                            class="form-label"
                            for="target_end_date"
                        >
                            Target End Date
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            name="target_end_date"
                            id="target_end_date"
                            value="<?php echo e(old('target_end_date')); ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label
                            class="form-label"
                            for="duration_days"
                        >
                            Duration (Days)
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            name="duration_days"
                            id="duration_days"
                            min="1"
                            value="<?php echo e(old('duration_days')); ?>"
                            placeholder="7"
                        >

                    </div>


                    <div class="form-group">

                        <label
                            class="form-label"
                            for="service_frequency"
                        >
                            Service Frequency
                        </label>

                        <select
                            class="form-control"
                            name="service_frequency"
                            id="service_frequency"
                        >

                            <option value="">
                                Select Frequency
                            </option>

                            <option value="One-time">
                                One-time
                            </option>

                            <option value="Weekly">
                                Weekly
                            </option>

                            <option value="Monthly">
                                Monthly
                            </option>

                            <option value="Quarterly">
                                Quarterly
                            </option>

                            <option value="Annual">
                                Annual
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label
                            class="form-label"
                            for="billing_frequency"
                        >
                            Billing Frequency
                        </label>

                        <select
                            class="form-control"
                            name="billing_frequency"
                            id="billing_frequency"
                        >

                            <option value="">
                                Select Billing Frequency
                            </option>

                            <option value="Full Payment Before Service">
                                Full Payment Before Service
                            </option>

                            <option value="Monthly">
                                Monthly
                            </option>

                            <option value="Quarterly">
                                Quarterly
                            </option>

                            <option value="Milestone">
                                Milestone
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label
                            class="form-label"
                            for="reporting_frequency"
                        >
                            Reporting Frequency
                        </label>

                        <select
                            class="form-control"
                            name="reporting_frequency"
                            id="reporting_frequency"
                        >

                            <option value="">
                                Select Frequency
                            </option>

                            <option value="Weekly">
                                Weekly
                            </option>

                            <option value="Monthly">
                                Monthly
                            </option>

                            <option value="Quarterly">
                                Quarterly
                            </option>

                            <option value="As Required">
                                As Required
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label
                            class="form-label"
                            for="project_manager"
                        >
                            Project Manager
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="project_manager"
                            id="project_manager"
                            value="<?php echo e(old('project_manager')); ?>"
                            placeholder="Project Manager"
                        >

                    </div>


                    <div class="form-group full">

                        <label
                            class="form-label"
                            for="notes"
                        >
                            Notes
                        </label>

                        <textarea
                            class="form-control"
                            name="notes"
                            id="notes"
                            placeholder="Additional notes..."
                        ><?php echo e(old('notes')); ?></textarea>

                    </div>

                </div>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    onclick="closeGroupModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Create Engagement Group
                </button>

            </div>

        </form>

    </div>

</div>


<!-- ============================================================
     ASSIGNMENT MODAL
============================================================= -->

<style>

    .assignment-modal {
        position: fixed;
        inset: 0;

        background: rgba(15, 23, 42, .35);

        display: none;
        align-items: center;
        justify-content: center;

        z-index: 9999;

        padding: 20px;
    }


    .assignment-modal.open {
        display: flex;
    }


    .assignment-modal-card {
        width: 100%;
        max-width: 520px;

        background: #ffffff;

        border: 1px solid #e2e8f0;
        border-radius: 10px;

        box-shadow:
            0 20px 50px rgba(15, 23, 42, .15);

        padding: 22px;
    }


    .assignment-modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;

        margin-bottom: 22px;
    }


    .assignment-modal-eyebrow {
        color: #94a3b8;

        font-size: 9px;
        font-weight: 700;

        letter-spacing: .08em;
    }


    .assignment-modal-header h2 {
        margin: 5px 0 0;

        color: #334155;

        font-size: 18px;
        font-weight: 700;
    }


    .assignment-modal-close {
        width: 30px;
        height: 30px;

        border: 0;
        border-radius: 6px;

        background: transparent;

        color: #64748b;

        font-size: 22px;

        cursor: pointer;
    }


    .assignment-modal-close:hover {
        background: #f1f5f9;
    }


    .assignment-form-field {
        margin-bottom: 16px;
    }


    .assignment-form-field label {
        display: block;

        margin-bottom: 6px;

        color: #475569;

        font-size: 10px;
        font-weight: 700;
    }


    .assignment-form-field input,
    .assignment-form-field select,
    .assignment-form-field textarea {
        width: 100%;

        box-sizing: border-box;

        border: 1px solid #dbe2ea;
        border-radius: 6px;

        background: #ffffff;

        color: #334155;

        padding: 9px 10px;

        font-size: 11px;

        outline: none;
    }


    .assignment-form-field input:focus,
    .assignment-form-field select:focus,
    .assignment-form-field textarea:focus {
        border-color: #2458d7;

        box-shadow:
            0 0 0 2px rgba(36, 88, 215, .08);
    }


    .assignment-form-field textarea {
        resize: vertical;
    }


    .assignment-modal-actions {
        display: flex;

        justify-content: flex-end;

        gap: 8px;

        margin-top: 22px;
    }


    /* =====================================================
       EDIT MODAL
    ===================================================== */

    .edit-group-modal {
        z-index: 10000;
    }


    .edit-group-modal .modal {
        max-height: 90vh;
        overflow-y: auto;
    }


    .edit-group-modal .modal-body {
        max-height: 65vh;
        overflow-y: auto;
    }

</style>


<div
    id="assignmentModal"
    class="assignment-modal"
    onclick="closeAssignmentModal(event)"
>

    <div
        class="assignment-modal-card"
        onclick="event.stopPropagation()"
    >

        <div class="assignment-modal-header">

            <div>

                <div class="assignment-modal-eyebrow">
                    ENGAGEMENT GROUP
                </div>

                <h2>
                    Create Assignment
                </h2>

            </div>


            <button
                type="button"
                class="assignment-modal-close"
                onclick="closeAssignmentModal()"
            >
                ×
            </button>

        </div>


        <form
            id="assignmentForm"
            method="POST"
        >

            <?php echo csrf_field(); ?>


            <div class="assignment-form-field">

                <label for="assignment_role">
                    Role
                </label>

                <input
                    type="text"
                    id="assignment_role"
                    name="role"
                    placeholder="Example: Project Manager"
                    required
                >

            </div>


            <div class="assignment-form-field">

                <label for="assignment_assigned_to">
                    Assigned To
                </label>

                <input
                    type="text"
                    id="assignment_assigned_to"
                    name="assigned_to"
                    placeholder="Enter assigned person"
                    required
                >

            </div>


            <div class="assignment-form-field">

                <label for="assignment_status">
                    Status
                </label>

                <select
                    id="assignment_status"
                    name="status"
                    required
                >

                    <option value="Assigned">
                        Assigned
                    </option>

                    <option value="Pending">
                        Pending
                    </option>

                    <option value="Reassigned">
                        Reassigned
                    </option>

                    <option value="Completed">
                        Completed
                    </option>

                </select>

            </div>


            <div class="assignment-form-field">

                <label for="assignment_notes">
                    Notes
                </label>

                <textarea
                    id="assignment_notes"
                    name="notes"
                    rows="4"
                    placeholder="Optional notes"
                ></textarea>

            </div>


            <div class="assignment-modal-actions">

                <button
                    type="button"
                    class="btn btn-outline"
                    onclick="closeAssignmentModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Create Assignment
                </button>

            </div>

        </form>

    </div>

</div>


<!-- ============================================================
     JAVASCRIPT
============================================================= -->

<script>

    /* =====================================================
       CREATE GROUP MODAL
    ===================================================== */

    function openGroupModal() {

        const modal =
            document.getElementById('groupModal');

        if (!modal) {
            return;
        }

        modal.classList.add('open');

        document.body.style.overflow = 'hidden';

    }


    function closeGroupModal(event) {

        const modal =
            document.getElementById('groupModal');

        if (!modal) {
            return;
        }

        if (
            event &&
            event.target !== modal
        ) {
            return;
        }

        modal.classList.remove('open');

        document.body.style.overflow = '';

    }


    /* =====================================================
       VIEW DETAILS
    ===================================================== */

    function toggleGroupDetails(groupId) {

        const details =
            document.getElementById(
                'group-details-' + groupId
            );

        if (!details) {
            return;
        }


        if (
            details.style.display === 'none' ||
            details.style.display === ''
        ) {

            details.style.display = 'grid';

        } else {

            details.style.display = 'none';

        }

    }


    /* =====================================================
       EDIT ENGAGEMENT GROUP
    ===================================================== */

    function editEngagementGroup(groupId) {

        const modal =
            document.getElementById(
                'editGroupModal-' + groupId
            );

        if (!modal) {

            alert(
                'Edit form for this engagement group is not available.'
            );

            return;
        }


        modal.classList.add('open');

        document.body.style.overflow = 'hidden';

    }


    function closeEditGroupModal(event, groupId) {

        const modal =
            document.getElementById(
                'editGroupModal-' + groupId
            );

        if (!modal) {
            return;
        }


        if (
            event &&
            event.target !== modal
        ) {
            return;
        }


        modal.classList.remove('open');

        document.body.style.overflow = '';

    }


    /* =====================================================
       ASSIGNMENTS
    ===================================================== */

    function showAssignments(groupId) {

        const modal =
            document.getElementById(
                'assignmentModal'
            );

        const form =
            document.getElementById(
                'assignmentForm'
            );

        if (!modal || !form) {

            alert(
                'Assignment form is not available.'
            );

            return;
        }


        form.action =
            "<?php echo e(url('/deals/' . $deal->id . '/start/groups')); ?>"
            + "/" + groupId
            + "/assignments";


        modal.classList.add('open');

        document.body.style.overflow = 'hidden';

    }


    function closeAssignmentModal(event) {

        const modal =
            document.getElementById(
                'assignmentModal'
            );

        if (!modal) {
            return;
        }


        if (
            event &&
            event.target !== modal
        ) {
            return;
        }


        modal.classList.remove('open');

        document.body.style.overflow = '';

    }


    /* =====================================================
       ESCAPE KEY
    ===================================================== */

    document.addEventListener(
        'keydown',
        function(event) {

            if (event.key !== 'Escape') {
                return;
            }


            const groupModal =
                document.getElementById(
                    'groupModal'
                );


            const assignmentModal =
                document.getElementById(
                    'assignmentModal'
                );


            if (
                groupModal &&
                groupModal.classList.contains('open')
            ) {

                groupModal.classList.remove('open');

            }


            if (
                assignmentModal &&
                assignmentModal.classList.contains('open')
            ) {

                assignmentModal.classList.remove('open');

            }


            const editModals =
                document.querySelectorAll(
                    '.edit-group-modal.open'
                );


            editModals.forEach(function(modal) {

                modal.classList.remove('open');

            });


            document.body.style.overflow = '';

        }
    );

</script>


</body><?php /**PATH C:\JK&C\ordodeals\resources\views/starts/show.blade.php ENDPATH**/ ?>