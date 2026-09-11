@extends('layouts.app')

@section('content')
@php
    $areas = $deal->service_areas ?: [];
    $items = $deal->services_products ?: [];

    $servicesTotal = (float) (
        $deal->total_service_fee
        ?: (
            ($deal->est_professional_fee ?: 0)
            + ($deal->est_government_fee ?: 0)
            + ($deal->est_service_support_fee ?: 0)
        )
    );

    $productsTotal = (float) ($deal->total_product_fee ?: 0);
    $discount = (float) ($proposal->discount ?? $deal->discount ?? 0);
    $tax = (float) ($proposal->tax ?? 0);

    $subtotal = $servicesTotal + $productsTotal - $discount;
    $total = $subtotal + $tax;

    $contact = trim(
        implode(
            ' ',
            array_filter([
                $deal->salutation,
                $deal->first_name,
                $deal->middle_initial,
                $deal->last_name,
                $deal->name_extension
            ])
        )
    ) ?: ($deal->primary_contact_name ?: null);

    $itemNames = array_values(
        array_filter(
            array_map(
                fn ($item) => is_array($item)
                    ? ($item['name'] ?? null)
                    : $item,
                $items
            ),
            fn ($value) => filled($value)
        )
    );

    $money = fn ($number) => '₱' . number_format((float) $number, 2);
@endphp

<style>
    .proposal-page{
        min-height:calc(100vh - 60px);
        padding:22px 28px;
        background:#f1f5fb;
        overflow:hidden
    }

    .proposal-header,
    .proposal-panel{
        background:#fff;
        border:1px solid #dce5f0;
        border-radius:10px
    }

    .proposal-header{
        max-width:1400px;
        margin:0 auto 14px;
        padding:15px 18px;
        display:flex;
        justify-content:space-between;
        gap:18px;
        align-items:center
    }

    .proposal-header-title,
    .proposal-actions{
        display:flex;
        align-items:center;
        gap:8px;
        flex-wrap:wrap
    }

    .proposal-back{
        color:#334155;
        text-decoration:none;
        font-size:13px;
        font-weight:600
    }

    .proposal-heading{
        color:#07162d;
        font-size:19px;
        font-weight:700
    }

    .proposal-status{
        padding:5px 9px;
        border:1px solid #f4d46b;
        border-radius:16px;
        color:#9a6700;
        background:#fff4c7;
        font-size:10px;
        font-weight:700
    }

    .proposal-actions input{
        width:190px;
        height:33px;
        border:1px solid #cbd8e8;
        border-radius:6px;
        padding:0 9px;
        font-size:11px
    }

    .proposal-button{
        min-height:33px;
        padding:7px 11px;
        border:1px solid #cbd8e8;
        border-radius:6px;
        background:#fff;
        color:#334155;
        font-size:11px;
        font-weight:600;
        cursor:pointer
    }

    .proposal-button.primary{
        background:#244f91;
        border-color:#244f91;
        color:#fff
    }

    .proposal-layout{
        max-width:1400px;
        height:calc(100vh - 173px);
        min-height:420px;
        margin:auto;
        display:grid;
        grid-template-columns:minmax(330px,40%) minmax(0,60%);
        gap:14px
    }

    .proposal-panel{
        min-width:0;
        min-height:0;
        display:flex;
        flex-direction:column;
        overflow:hidden
    }

    .proposal-panel-header{
        padding:16px 17px 13px;
        border-bottom:1px solid #e5ebf3
    }

    .proposal-panel-header h1{
        margin:0 0 4px;
        color:#07162d;
        font-size:17px
    }

    .proposal-panel-header p{
        margin:0;
        color:#64748b;
        font-size:11px
    }

    .proposal-form-scroll,
    .proposal-preview-scroll{
        flex:1 1 auto;
        min-height:0;
        overflow-y:auto;
        overflow-x:hidden;
        overscroll-behavior:contain;
        scrollbar-gutter:stable
    }

    .proposal-form-scroll{
        padding:14px 17px 28px
    }

    .proposal-info,
    .approval-notice{
        padding:11px 12px;
        border-radius:7px;
        font-size:10px;
        line-height:1.45
    }

    .proposal-info{
        margin-bottom:14px;
        border:1px solid #b9d5ff;
        background:#eff6ff;
        color:#24518f
    }

    .approval-notice{
        border:1px solid #f3d18a;
        background:#fff8e6;
        color:#966b13
    }

    .proposal-card{
        margin-bottom:14px;
        padding:14px;
        border:1px solid #dce5f0;
        border-radius:8px
    }

    .proposal-card h2{
        margin:0 0 12px;
        color:#334155;
        font-size:13px
    }

    .proposal-grid{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:11px
    }

    .proposal-field.full{
        grid-column:1/-1
    }

    .proposal-field label{
        display:block;
        margin-bottom:5px;
        color:#64748b;
        font-size:10px;
        font-weight:700
    }

    .proposal-field input,
    .proposal-field textarea{
        width:100%;
        border:1px solid #d5dfeb;
        border-radius:6px;
        padding:8px 9px;
        color:#334155;
        font:inherit;
        font-size:11px
    }

    .proposal-field textarea{
        min-height:72px;
        resize:vertical
    }

    .proposal-table{
        width:100%;
        border-collapse:collapse;
        font-size:10px
    }

    .proposal-table th,
    .proposal-table td{
        padding:8px 7px;
        border:1px solid #dce5f0;
        text-align:left;
        color:#475569
    }

    .proposal-table th{
        background:#f8fafc;
        color:#64748b
    }

    .total-row{
        display:flex;
        justify-content:space-between;
        padding:7px 0;
        border-bottom:1px solid #edf1f5;
        color:#64748b;
        font-size:11px
    }

    .total-row strong{
        color:#172033
    }

    .total-row.total{
        border-bottom:0;
        color:#172033;
        font-weight:700
    }

    .form-actions{
        display:flex;
        gap:8px;
        margin-bottom:14px
    }

    .form-actions .proposal-button{
        flex:1
    }

    .preview-toolbar{
        padding:10px 13px;
        border-bottom:1px solid #e5ebf3;
        display:flex;
        gap:5px;
        flex-wrap:wrap
    }

    .preview-toolbar button{
        height:27px;
        border:1px solid #cbd8e8;
        border-radius:5px;
        background:#fff;
        color:#40516a;
        font-size:11px;
        cursor:pointer
    }

    .preview-toolbar .template-button{
        margin-left:auto
    }

    .preview-workspace{
        min-height:100%;
        padding:22px 20px 45px;
        background:#eef2f7
    }

    .proposal-paper{
        width:min(100%,700px);
        min-height:920px;
        margin:0 auto 24px;
        padding:68px 65px;
        background:#fff;
        border:1px solid #d7e0eb;
        box-shadow:0 3px 12px rgba(30,48,75,.1);
        color:#172033
    }

    .paper-brand{
        text-align:center;
        color:#07162d;
        font-family:Georgia,"Times New Roman",serif
    }

    .paper-company{
        font-size:21px;
        font-weight:700;
        line-height:1.1
    }

    .paper-year{
        margin-top:18px;
        color:#244f91;
        font-size:28px;
        font-weight:700
    }

    .paper-service{
        margin-top:6px;
        font-size:14px;
        font-weight:700
    }

    .paper-date{
        margin-top:38px;
        color:#64748b;
        font-size:11px;
        text-align:right
    }

    .paper-rule{
        margin:14px 0 22px;
        border:0;
        border-top:2px solid #244f91
    }

    .paper-title{
        color:#244f91;
        font-family:Georgia,"Times New Roman",serif;
        font-size:22px;
        font-weight:700
    }

    .paper-text{
        color:#475569;
        font-size:12px;
        line-height:1.7;
        white-space:pre-wrap
    }

    .editable{
        padding:3px;
        border-radius:3px;
        outline:1px dashed transparent
    }

    .editable:hover,
    .editable:focus{
        outline-color:#8bb8f2;
        background:#eff6ff
    }

    .paper-summary{
        margin-top:25px;
        border-collapse:collapse;
        width:100%;
        font-size:11px
    }

    .paper-summary td{
        padding:8px 5px;
        border-bottom:1px solid #dce5f0
    }

    .paper-summary td:last-child{
        text-align:right;
        font-weight:700
    }

    .paper-footer{
        margin-top:70px;
        color:#94a3b8;
        font-size:9px;
        text-align:center
    }

    @media(max-width:900px){
        .proposal-page{
            overflow:auto
        }

        .proposal-layout{
            height:auto;
            grid-template-columns:1fr
        }

        .proposal-panel{
            min-height:520px
        }

        .proposal-header{
            align-items:flex-start;
            flex-direction:column
        }

        .proposal-actions{
            justify-content:flex-start
        }
    }

    @media(max-width:560px){
        .proposal-grid{
            grid-template-columns:1fr
        }

        .proposal-field.full{
            grid-column:auto
        }

        .proposal-paper{
            padding:38px 26px;
            min-height:720px
        }

        .preview-workspace{
            padding:14px 8px 30px
        }
    }

    @media print{
        .sidebar,
        .topbar,
        .proposal-header,
        .proposal-panel-header,
        .preview-toolbar,
        .proposal-form-panel{
            display:none!important
        }

        .proposal-page,
        .proposal-layout,
        .proposal-panel,
        .proposal-preview-scroll,
        .preview-workspace{
            height:auto;
            overflow:visible;
            background:#fff;
            border:0;
            padding:0
        }

        .proposal-paper{
            box-shadow:none;
            margin:0 auto
        }
    }
</style>

<style>
    .preview-toolbar{
        flex-wrap:nowrap;
        overflow-x:auto;
        white-space:nowrap
    }

    .preview-toolbar button{
        flex:0 0 auto;
        padding:0 9px;
        white-space:nowrap
    }

    .preview-toolbar .template-button{
        margin-left:0;
        background:#eff6ff;
        border-color:#9fc2f2;
        color:#244f91
    }

    .proposal-preview-panel .preview-toolbar{
        flex-wrap:nowrap;
        overflow:hidden;
        gap:2px;
        white-space:nowrap
    }

    .proposal-preview-panel .preview-toolbar button{
        flex:1 1 0;
        min-width:0;
        height:27px;
        padding:0 4px;
        overflow:hidden;
        font-size:9px;
        white-space:nowrap;
        text-overflow:clip
    }

    .proposal-preview-panel .preview-toolbar .template-button{
        flex:1.8 1 0
    }

    .proposal-preview-panel .preview-toolbar{
        flex:0 0 49px;
        min-height:49px;
        margin:12px;
        padding:10px 8px;
        border:1px solid #dce5f0;
        border-radius:8px;
        background:#ffffff;
        overflow:hidden;
        box-sizing:border-box
    }

    .proposal-preview-panel .preview-toolbar button{
        height:27px;
        line-height:25px;
        font-size:8px;
        overflow:visible;
        text-overflow:clip
    }

    .proposal-preview-panel .proposal-preview-scroll{
        min-height:0;
        overflow-y:auto;
        overflow-x:hidden
    }

    .template-dialog{
        width:min(420px,calc(100% - 32px));
        border:1px solid #dce5f0;
        border-radius:10px;
        padding:20px;
        color:#172033
    }

    .template-dialog::backdrop{
        background:rgba(15,23,42,.25)
    }

    .template-dialog h2{
        margin:0 0 8px;
        color:#07162d;
        font-size:16px
    }

    .template-dialog p{
        margin:0 0 18px;
        color:#64748b;
        font-size:12px;
        line-height:1.5
    }

    .template-dialog-actions{
        display:flex;
        justify-content:flex-end;
        gap:8px
    }
</style>

<div class="proposal-page">

    <header class="proposal-header">

        <div class="proposal-header-title">

            <a
                class="proposal-back"
                href="{{ route('deals.show', $deal) }}"
            >
                ← Create Proposal
            </a>

            <span class="proposal-heading">
                {{ $deal->deal_code ?: 'Deal' }}
            </span>

        </div>

        <div class="proposal-actions">

            <span class="proposal-status">
                Exact preview ready
            </span>

            <button
                class="proposal-button"
                type="button"
                onclick="window.print()"
            >
                Download PDF
            </button>

            <form
                method="POST"
                action="{{ route('deals.proposal.send', $deal) }}"
                style="display:flex;gap:7px;align-items:center;"
            >
                @csrf

                <input
                    type="email"
                    name="recipient_email"
                    value="{{ old('recipient_email', $proposal->recipient_email ?: $deal->email) }}"
                    placeholder="Recipient email"
                    required
                >

                <button
                    class="proposal-button primary"
                    type="submit"
                >
                    Send Proposal
                </button>

            </form>

        </div>

    </header>

    <div class="proposal-layout">

        <section class="proposal-panel proposal-form-panel">

            <div class="proposal-panel-header">

                <h1>
                    Create Proposal Form
                </h1>

                <p>
                    The form is auto-filled from the deal. Edit any field and the right-side
                    preview regenerates for the final PDF output.
                </p>

            </div>

            <div class="proposal-form-scroll">

                <div class="proposal-info">
                    You can now edit key proposal content directly in the preview on the right.
                    Click the blue-highlighted text blocks to edit them, and the saved proposal
                    fields will stay in sync.
                </div>

                <form
                    method="POST"
                    action="{{ route('deals.proposal.store', $deal) }}"
                    id="proposal-form"
                >
                    @csrf

                    <div class="proposal-card">

                        <h2>
                            Client Information
                        </h2>

                        <div class="proposal-grid">

                            <div class="proposal-field">

                                <label>
                                    Deal Code
                                </label>

                                <input
                                    value="{{ $deal->deal_code ?: '-' }}"
                                    readonly
                                >

                            </div>

                            <div class="proposal-field">

                                <label>
                                    Client Type
                                </label>

                                <input
                                    value="{{ $deal->customer_type ?: '-' }}"
                                    readonly
                                >

                            </div>

                            <div class="proposal-field">

                                <label>
                                    Client Name
                                </label>

                                <input
                                    value="{{ $contact ?: '' }}"
                                    readonly
                                >

                            </div>

                            <div class="proposal-field">

                                <label>
                                    Company
                                </label>

                                <input
                                    value="{{ $deal->company ?: '-' }}"
                                    readonly
                                >

                            </div>

                            <div class="proposal-field">

                                <label>
                                    Email Address
                                </label>

                                <input
                                    value="{{ $deal->email ?: '-' }}"
                                    readonly
                                >

                            </div>

                            <div class="proposal-field">

                                <label>
                                    Mobile Number
                                </label>

                                <input
                                    value="{{ $deal->mobile_number ?: '-' }}"
                                    readonly
                                >

                            </div>

                        </div>

                    </div>

                    <div class="proposal-card">

                        <h2>
                            Proposal Details
                        </h2>

                        <div class="proposal-grid">

                            <div class="proposal-field full">

                                <label for="subject">
                                    Proposal Title
                                </label>

                                <input
                                    id="subject"
                                    name="subject"
                                    value="{{ old('subject', $proposal->subject ?: $deal->deal_title) }}"
                                >

                            </div>

                            <div class="proposal-field">

                                <label>
                                    Pipeline Stage
                                </label>

                                <input
                                    value="{{ $deal->pipeline_stage ?: '-' }}"
                                    readonly
                                >

                            </div>

                            <div class="proposal-field">

                                <label>
                                    Engagement Type
                                </label>

                                <input
                                    value="{{ $deal->engagement_type ?: '-' }}"
                                    readonly
                                >

                            </div>

                            <div class="proposal-field full">

                                <label for="introduction">
                                    Introduction
                                </label>

                                <textarea
                                    id="introduction"
                                    name="introduction"
                                >{{ old('introduction', $proposal->introduction ?: 'We are pleased to present this proposal for the engagement described below.') }}</textarea>

                            </div>

                        </div>

                    </div>

                    <div class="proposal-card">

                        <h2>
                            Products
                        </h2>

                        <table class="proposal-table">

                            <thead>

                                <tr>

                                    <th>
                                        Product ID
                                    </th>

                                    <th>
                                        Name
                                    </th>

                                    <th style="text-align:right">
                                        Price
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                @forelse($items as $index => $item)

                                    <tr>

                                        <td>
                                            {{ $index + 1 }}
                                        </td>

                                        <td>
                                            {{ is_array($item) ? ($item['name'] ?? 'Service') : $item }}
                                        </td>

                                        <td style="text-align:right">
                                            -
                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td
                                            colspan="3"
                                            style="text-align:center"
                                        >
                                            No products selected.
                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                        <div
                            style="display:flex;justify-content:space-between;margin-top:10px;color:#64748b;font-size:11px"
                        >

                            <span>
                                Total Products
                            </span>

                            <strong>
                                {{ $money($productsTotal) }}
                            </strong>

                        </div>

                    </div>

                    <div class="proposal-card">

                        <h2>
                            COMPUTED TOTALS
                        </h2>

                        <div class="total-row">

                            <span>
                                Total Services
                            </span>

                            <strong>
                                {{ $money($servicesTotal) }}
                            </strong>

                        </div>

                        <div class="total-row">

                            <span>
                                Total Products
                            </span>

                            <strong>
                                {{ $money($productsTotal) }}
                            </strong>

                        </div>

                        <div class="total-row">

                            <span>
                                Discount
                            </span>

                            <strong>
                                {{ $money($discount) }}
                            </strong>

                        </div>

                        <div class="total-row">

                            <span>
                                Tax
                            </span>

                            <strong>
                                {{ $money($tax) }}
                            </strong>

                        </div>

                        <div class="total-row">

                            <span>
                                Subtotal
                            </span>

                            <strong>
                                {{ $money($subtotal) }}
                            </strong>

                        </div>

                        <div class="total-row total">

                            <span>
                                Total
                            </span>

                            <strong>
                                {{ $money($total) }}
                            </strong>

                        </div>

                        <div class="total-row">

                            <span>
                                Downpayment
                            </span>

                            <strong>
                                {{ $money(0) }}
                            </strong>

                        </div>

                        <div class="total-row">

                            <span>
                                Balance
                            </span>

                            <strong>
                                {{ $money($total) }}
                            </strong>

                        </div>

                    </div>

                    <div class="proposal-card">

                        <h2>
                            Proposal Content
                        </h2>

                        <div class="proposal-grid">

                            <div class="proposal-field full">

                                <label for="scope">
                                    Scope of Work
                                </label>

                                <textarea
                                    id="scope"
                                    name="scope"
                                >{{ old('scope', $proposal->scope ?: $deal->scope_of_work) }}</textarea>

                            </div>

                            <div class="proposal-field full">

                                <label for="terms">
                                    Payment Terms
                                </label>

                                <textarea
                                    id="terms"
                                    name="terms"
                                >{{ old('terms', $proposal->terms ?: $deal->payment_terms) }}</textarea>

                            </div>

                            <div class="proposal-field">

                                <label for="discount">
                                    Discount
                                </label>

                                <input
                                    id="discount"
                                    name="discount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value="{{ old('discount', $proposal->discount ?? $deal->discount) }}"
                                >

                            </div>

                            <div class="proposal-field">

                                <label for="tax">
                                    Tax
                                </label>

                                <input
                                    id="tax"
                                    name="tax"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value="{{ old('tax', $proposal->tax) }}"
                                >

                            </div>

                        </div>

                    </div>

                    <div class="form-actions">

                        <button
                            class="proposal-button primary"
                            type="submit"
                        >
                            Save Proposal
                        </button>

                        <button
                            class="proposal-button"
                            type="button"
                            onclick="refreshPreview()"
                        >
                            Refresh Preview
                        </button>

                    </div>

                </form>

                <div class="approval-notice">
                    A global proposal template update is waiting for admin approval.
                    New deals continue using the last approved template until the pending
                    template is approved.
                </div>

            </div>

        </section>

        <section class="proposal-panel proposal-preview-panel">

            <div class="proposal-panel-header">

                <h1>
                    Proposal Preview
                </h1>

                <p>
                    This preview is aligned with the downloadable PDF output.
                </p>

            </div>

            <div class="preview-toolbar">

                <button
                    type="button"
                    onclick="formatPreview('bold')"
                >
                    <strong>B</strong>
                </button>

                <button
                    type="button"
                    onclick="formatPreview('italic')"
                >
                    <em>I</em>
                </button>

                <button
                    type="button"
                    onclick="formatPreview('underline')"
                >
                    <u>U</u>
                </button>

                <button
                    type="button"
                    onclick="formatPreview('insertUnorderedList')"
                >
                    • List
                </button>

                <button
                    type="button"
                    onclick="formatPreview('insertOrderedList')"
                >
                    1. List
                </button>

                <button
                    type="button"
                    onclick="formatPreview('justifyLeft')"
                >
                    Left
                </button>

                <button
                    type="button"
                    onclick="formatPreview('justifyCenter')"
                >
                    Center
                </button>

                <button
                    type="button"
                    onclick="formatPreview('justifyRight')"
                >
                    Right
                </button>

                <button
                    type="button"
                    onclick="formatPreview('removeFormat')"
                >
                    Clear Format
                </button>

                <button
                    class="template-button"
                    type="button"
                    onclick="saveTemplateNotice()"
                >
                    Save Global Template
                </button>

            </div>

            <div class="proposal-preview-scroll">

                <div class="preview-workspace">

                    {{-- PAGE 1 --}}

                    <article class="proposal-paper">

                        <div class="paper-brand">

                            <div class="paper-company">
                                John Kelly<br>
                                &amp; Company
                            </div>

                            <div class="paper-year">
                                {{ now()->format('Y') }}
                            </div>

                            <div class="paper-service">
                                {{ implode(' & ', $areas) ?: 'Professional Advisory Services' }}
                            </div>

                        </div>

                        <div class="paper-date">
                            {{ now()->format('F d, Y') }}
                        </div>

                        <hr class="paper-rule">

                        {{-- Proposal title appears only once --}}

                        @if($proposal->subject || $deal->deal_title)

                            <div
                                class="paper-title editable"
                                contenteditable="true"
                                data-source="subject"
                            >
                                {{ $proposal->subject ?: $deal->deal_title }}
                            </div>

                        @endif

                        {{-- Prepared for only appears when client information exists --}}

                        @if($contact || $deal->company)

                            <p class="paper-text">

                                Prepared for

                                @if($contact)
                                    <strong>{{ $contact }}</strong>
                                @elseif($deal->company)
                                    <strong>{{ $deal->company }}</strong>
                                @endif

                                @if($contact && $deal->company)
                                    <br>{{ $deal->company }}
                                @endif

                            </p>

                        @endif

                        {{-- Scope only appears when there is scope content --}}

                        @if($proposal->scope || $deal->scope_of_work)

                            <h3 style="color:#244f91;font-family:Georgia,serif">
                                Scope of Work
                            </h3>

                            <p
                                class="paper-text editable"
                                contenteditable="true"
                                data-source="scope"
                            >
                                {{ $proposal->scope ?: $deal->scope_of_work }}
                            </p>

                        @endif

                        <table class="paper-summary">

                            <tr>

                                <td>
                                    Total Services
                                </td>

                                <td>
                                    {{ $money($servicesTotal) }}
                                </td>

                            </tr>

                            <tr>

                                <td>
                                    Total Products
                                </td>

                                <td>
                                    {{ $money($productsTotal) }}
                                </td>

                            </tr>

                            <tr>

                                <td>
                                    Discount
                                </td>

                                <td>
                                    {{ $money($discount) }}
                                </td>

                            </tr>

                            <tr>

                                <td>
                                    Total Engagement Value
                                </td>

                                <td>
                                    {{ $money($total) }}
                                </td>

                            </tr>

                        </table>

                        {{-- Payment terms only appear when they exist --}}

                        @if($proposal->terms || $deal->payment_terms)

                            <p class="paper-text">

                                Payment terms:
                                {{ $proposal->terms ?: $deal->payment_terms }}

                            </p>

                        @endif

                        <div class="paper-footer">
                            {{ $deal->deal_code ?: 'Proposal' }} · Confidential
                        </div>

                    </article>

                    {{-- PAGE 2 --}}

                    <article class="proposal-paper">

                        <div class="paper-title">
                            Engagement Details
                        </div>

                        <p class="paper-text">
                            This page continues the selected deal's proposal information.
                        </p>

                        {{-- Services and deliverables only appear when items exist --}}

                        @if($itemNames)

                            <h3 style="color:#244f91;font-family:Georgia,serif">
                                Services and Deliverables
                            </h3>

                            <p class="paper-text">
                                {{ implode(', ', $itemNames) }}
                            </p>

                        @endif

                        {{-- Timeline only appears when at least one timeline field exists --}}

                        @if(
                            $deal->planned_start_date
                            || $deal->estimated_completion_date
                            || $deal->estimated_duration_days
                        )

                            <h3 style="color:#244f91;font-family:Georgia,serif">
                                Timeline
                            </h3>

                            <p class="paper-text">

                                @if($deal->planned_start_date)

                                    Planned start:
                                    {{ $deal->planned_start_date->format('M d, Y') }}

                                @endif

                                @if($deal->estimated_completion_date)

                                    @if($deal->planned_start_date)
                                        <br>
                                    @endif

                                    Estimated completion:
                                    {{ $deal->estimated_completion_date->format('M d, Y') }}

                                @endif

                                @if($deal->estimated_duration_days)

                                    @if(
                                        $deal->planned_start_date
                                        || $deal->estimated_completion_date
                                    )
                                        <br>
                                    @endif

                                    Duration:
                                    {{ $deal->estimated_duration_days }} days

                                @endif

                            </p>

                        @endif

                        {{-- Contact section only appears when contact information exists --}}

                        @if($contact || $deal->email || $deal->mobile_number)

                            <h3 style="color:#244f91;font-family:Georgia,serif">
                                Contact
                            </h3>

                            <p class="paper-text">

                                @if($contact)

                                    {{ $contact }}

                                @endif

                                @if($deal->email)

                                    @if($contact)
                                        <br>
                                    @endif

                                    {{ $deal->email }}

                                @endif

                                @if($deal->mobile_number)

                                    @if($contact || $deal->email)
                                        <br>
                                    @endif

                                    {{ $deal->mobile_number }}

                                @endif

                            </p>

                        @endif

                        <div class="paper-footer">
                            {{ $deal->deal_code ?: 'Proposal' }} · Page 2
                        </div>

                    </article>

                    {{-- PAGE 3 --}}

                    <article class="proposal-paper">

                        <div class="paper-title">
                            Acceptance and Next Steps
                        </div>

                        {{-- Proposal decision only appears when it exists --}}

                        @if($deal->proposal_decision)

                            <p class="paper-text">

                                Proposal decision:
                                {{ $deal->proposal_decision }}

                            </p>

                        @endif

                        <p class="paper-text">
                            The final proposal content is based on the current deal record
                            and saved proposal fields.
                        </p>

                        {{-- Prepared By only appears when Deal Owner exists --}}

                        @if($deal->owner_name)

                            <h3 style="color:#244f91;font-family:Georgia,serif">
                                Prepared By
                            </h3>

                            <p class="paper-text">
                                {{ $deal->owner_name }}
                            </p>

                        @endif

                        <div class="paper-footer">
                            {{ $deal->deal_code ?: 'Proposal' }} · Page 3
                        </div>

                    </article>

                </div>

            </div>

        </section>

    </div>

</div>

<div
    id="template-status"
    role="status"
    style="position:fixed;left:-9999px"
>
    Global template changes require admin approval.
</div>

<dialog
    id="template-dialog"
    class="template-dialog"
>

    <h2>
        Save Global Template
    </h2>

    <p>
        Save the current proposal formatting and content as the new global proposal template?
    </p>

    <div class="template-dialog-actions">

        <button
            class="proposal-button"
            type="button"
            onclick="closeTemplateDialog()"
        >
            Cancel
        </button>

        <button
            class="proposal-button primary"
            type="button"
            onclick="confirmSaveTemplate()"
        >
            Save Template
        </button>

    </div>

</dialog>

<script>
    var savedSelection = null;

    function rememberSelection() {

        var selection = window.getSelection();

        if (!selection.rangeCount) {
            return;
        }

        var range = selection.getRangeAt(0);

        var container = range.commonAncestorContainer;

        var parentElement = container.nodeType === 1
            ? container
            : container.parentElement;

        if (!parentElement) {
            return;
        }

        var editable =
            parentElement.closest('[contenteditable="true"]');

        if (editable) {
            savedSelection = range.cloneRange();
        }
    }

    document.addEventListener(
        'selectionchange',
        rememberSelection
    );

    document
        .querySelectorAll('.preview-toolbar button')
        .forEach(function (button) {

            button.addEventListener(
                'mousedown',
                function (event) {

                    event.preventDefault();

                    rememberSelection();

                }
            );

        });

    function restoreSelection() {

        if (!savedSelection) {
            return;
        }

        var selection = window.getSelection();

        selection.removeAllRanges();

        selection.addRange(savedSelection);
    }

    function syncPreviewField(source, value) {

        document
            .querySelectorAll('[data-source="' + source + '"]')
            .forEach(function (element) {

                if (document.activeElement !== element) {

                    element.textContent = value || '';

                }

            });
    }

    document
        .querySelectorAll('[data-source]')
        .forEach(function (element) {

            element.addEventListener(
                'input',
                function () {

                    var field =
                        document.getElementById(
                            element.dataset.source
                        );

                    if (field) {

                        field.value =
                            element.innerText;

                    }

                }
            );

        });

    [
        'subject',
        'introduction',
        'scope',
        'terms'
    ].forEach(function (id) {

        var field =
            document.getElementById(id);

        if (field) {

            field.addEventListener(
                'input',
                function () {

                    syncPreviewField(
                        id,
                        field.value
                    );

                }
            );

        }

    });

    function refreshPreview() {

        [
            'subject',
            'introduction',
            'scope',
            'terms'
        ].forEach(function (id) {

            var field =
                document.getElementById(id);

            if (field) {

                syncPreviewField(
                    id,
                    field.value
                );

            }

        });

    }

    var toolbarLabels = [
        'Bold',
        'Italic',
        'Underline',
        'Bullets',
        'Numbering',
        'Left',
        'Center',
        'Right',
        'Clear Format',
        'Save Global Template'
    ];

    document
        .querySelectorAll('.preview-toolbar button')
        .forEach(function (button, index) {

            if (toolbarLabels[index]) {

                button.textContent =
                    toolbarLabels[index];

            }

        });

    function formatPreview(command) {

        restoreSelection();

        document.execCommand(
            command,
            false,
            null
        );

        rememberSelection();
    }

    function saveTemplateNotice() {

        document
            .getElementById('template-dialog')
            .showModal();

    }

    function closeTemplateDialog() {

        document
            .getElementById('template-dialog')
            .close();

    }

    function confirmSaveTemplate() {

        var template = {};

        document
            .querySelectorAll('[data-source]')
            .forEach(function (element) {

                template[element.dataset.source] =
                    element.innerHTML;

            });

        localStorage.setItem(
            'ordo-global-proposal-template',
            JSON.stringify(template)
        );

        closeTemplateDialog();

        document
            .getElementById('template-status')
            .textContent =
                'Global proposal template saved successfully.';

    }
</script>

@endsection