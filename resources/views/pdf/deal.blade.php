@php
    $data = $dealFormData ?? ($detail ?? []);
    $logoBase64 = $data['logo_base64'] ?? ($logoBase64 ?? '');
    if (! filled($logoBase64)) {
        $logoPath = public_path('images/imaglogo.png');
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }
    }

    $val = static function ($key, $fallback = '-') use ($data): string {
        $v = $data[$key] ?? null;
        if (is_array($v)) {
            $v = collect($v)->flatten()->filter(fn ($i) => filled($i))->implode(', ');
        }
        return filled($v) ? (string) $v : $fallback;
    };

    $requirements = $data['client_requirements'] ?? [];
    if (! is_array($requirements) || count($requirements) === 0) {
        $statusMap = is_array($data['requirements_status_map'] ?? null) ? $data['requirements_status_map'] : [];
        $requirements = [
            [
                'requirement' => 'Client Contact Form',
                'provided' => 'Yes',
                'pending' => '-',
                'required_action' => '-',
            ],
            [
                'requirement' => 'Deal Form',
                'provided' => 'Yes',
                'pending' => '-',
                'required_action' => '-',
            ],
            [
                'requirement' => 'Business Information Form',
                'provided' => 'Yes',
                'pending' => '-',
                'required_action' => '-',
            ],
            [
                'requirement' => 'Client Information Form',
                'provided' => '-',
                'pending' => 'Yes',
                'required_action' => '-',
            ],
            [
                'requirement' => 'Service Task Activation & Routing Tracker (Start)',
                'provided' => '-',
                'pending' => 'Yes',
                'required_action' => '-',
            ],
        ];
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Consulting and Deal Form - {{ $val('deal_no', $val('deal_form_no', 'DEAL')) }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 0.75in;
        }
        table, td, th, div, p, span {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #0f172a;
            font-size: 8pt;
            line-height: 1.15;
            background: #ffffff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .toolbar {
            width: 100%;
            margin-bottom: 8px;
            text-align: right;
        }
        .toolbar-btn {
            display: inline-block;
            padding: 6px 14px;
            background: #0b2a68;
            color: #ffffff;
            text-decoration: none;
            font-size: 8pt;
            font-weight: bold;
            border-radius: 4px;
            margin-left: 6px;
        }
        .toolbar-btn.back {
            background: #475569;
        }
        @media print {
            .toolbar {
                display: none !important;
            }
        }
        .sheet-frame {
            width: 100%;
            border: 1.5px solid #0b2a68;
        }
        table.grid-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
            padding: 0;
        }
        table.grid-table td, table.grid-table th {
            border-right: 1px solid #0b2a68;
            border-bottom: 1px solid #0b2a68;
            padding: 2.5px 5px;
            vertical-align: top;
        }
        table.grid-table tr:last-child td {
            border-bottom: none;
        }
        table.grid-table td:last-child, table.grid-table th:last-child {
            border-right: none;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border-bottom: 1.5px solid #0b2a68;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 4px 6px;
        }
        .brand-logo {
            max-height: 42px;
            width: auto;
            display: block;
        }
        .brand-fallback-title {
            font-family: "Times New Roman", Georgia, serif;
            font-size: 17pt;
            font-weight: bold;
            color: #0b1d3a;
            line-height: 1;
        }
        .brand-fallback-sub {
            font-family: "Times New Roman", Georgia, serif;
            font-size: 12pt;
            font-style: italic;
            color: #0b1d3a;
            line-height: 1;
        }
        .doc-title {
            text-align: right;
            font-size: 13pt;
            font-weight: 800;
            color: #000000;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            line-height: 1.1;
        }
        .doc-meta {
            text-align: right;
            font-size: 8pt;
            color: #1e293b;
            margin-top: 3px;
        }
        .section-title {
            background: #0b2a68;
            color: #ffffff;
            text-align: center;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 2.5px 0;
            border-bottom: 1px solid #0b2a68;
            border-top: 1px solid #0b2a68;
            width: 100%;
        }
        .label {
            font-size: 6.2pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #000000;
            display: block;
            line-height: 1.05;
            margin-bottom: 1.5px;
        }
        .value {
            font-size: 7.5pt;
            color: #0f172a;
            min-height: 9pt;
            line-height: 1.15;
            word-wrap: break-word;
        }
        .req-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
            padding: 0;
        }
        .req-table th {
            background: #ffffff;
            font-size: 6.2pt;
            font-weight: bold;
            text-transform: uppercase;
            border-right: 1px solid #0b2a68;
            border-bottom: 1px solid #0b2a68;
            padding: 2.5px 5px;
            text-align: left;
            color: #000000;
            line-height: 1.05;
        }
        .req-table td {
            border-right: 1px solid #0b2a68;
            border-bottom: 1px solid #0b2a68;
            padding: 2.5px 5px;
            font-size: 7.5pt;
            color: #0f172a;
            vertical-align: middle;
            text-align: left;
            line-height: 1.15;
        }
        .req-table tr:last-child td {
            border-bottom: none;
        }
        .req-table td:last-child, .req-table th:last-child {
            border-right: none;
        }
        .footer-note {
            text-align: right;
            font-size: 6.5pt;
            color: #475569;
            font-style: italic;
            padding: 3px 2px 0 0;
        }
    </style>
</head>
<body>

@if(!empty($autoPrint) || request()->has('preview'))
<div class="toolbar">
    <a href="javascript:history.back()" class="toolbar-btn back">← Back</a>
    <a href="javascript:window.print()" class="toolbar-btn">🖨 Print</a>
</div>
@endif

<div class="sheet-frame">

    <!-- 1. CONSULTING & DEAL FORM HEADER -->
    <table class="header-table">
        <tr>
            <td style="width: 45%;">
                @if(filled($logoBase64))
                    <img src="{{ $logoBase64 }}" class="brand-logo" alt="John Kelly & Company">
                @else
                    <div class="brand-fallback-title">John Kelly</div>
                    <div class="brand-fallback-sub">&amp; Company</div>
                @endif
            </td>
            <td style="width: 55%; text-align: right;">
                <div class="doc-title">CONSULTING &amp; DEAL FORM</div>
                <div class="doc-meta">
                    Deal No. {{ $val('deal_no', $val('deal_form_no', '-')) }} &nbsp;&nbsp;&nbsp;&nbsp; Date {{ $val('header_date', $val('date', date('m/d/Y'))) }}
                </div>
            </td>
        </tr>
    </table>

    <!-- 2. DEAL SUMMARY -->
    <table class="grid-table" style="border-bottom: 1px solid #0b2a68;">
        <tr>
            <td style="width: 33%; border-bottom: none;"><span class="label">Deal Name</span><div class="value">{{ $val('deal_name', $val('deal_title', '-')) }}</div></td>
            <td style="width: 19%; border-bottom: none;"><span class="label">Stage</span><div class="value">{{ $val('stage', '-') }}</div></td>
            <td style="width: 27%; border-bottom: none;"><span class="label">Engagement Type</span><div class="value">{{ $val('engagement_type', $val('engagement_term', '-')) }}</div></td>
            <td style="width: 21%; border-bottom: none;"><span class="label">Total Value</span><div class="value">{{ $val('total_value', 'P0.00') }}</div></td>
        </tr>
    </table>

    <!-- 3. CONTACT INFORMATION -->
    <div class="section-title">Contact Information</div>
    
    <!-- Contact Info Row 1 (6 columns) -->
    <table class="grid-table" style="border-bottom: 1px solid #0b2a68;">
        <tr>
            <td style="width: 11%; border-bottom: none;"><span class="label">Salutation</span><div class="value">{{ $val('salutation', '-') }}</div></td>
            <td style="width: 23%; border-bottom: none;"><span class="label">First Name</span><div class="value">{{ $val('first_name', '-') }}</div></td>
            <td style="width: 12%; border-bottom: none;"><span class="label">Middle Initial</span><div class="value">{{ $val('middle_initial', '-') }}</div></td>
            <td style="width: 22%; border-bottom: none;"><span class="label">Last Name</span><div class="value">{{ $val('last_name', '-') }}</div></td>
            <td style="width: 16%; border-bottom: none;"><span class="label">Name Extension</span><div class="value">{{ $val('name_extension', '-') }}</div></td>
            <td style="width: 16%; border-bottom: none;"><span class="label">Sex</span><div class="value">{{ $val('sex', '-') }}</div></td>
        </tr>
    </table>

    <!-- Contact Info Row 2 (4 columns) -->
    <table class="grid-table" style="border-bottom: 1px solid #0b2a68;">
        <tr>
            <td style="width: 23%; border-bottom: none;"><span class="label">Date of Birth</span><div class="value">{{ $val('date_of_birth', '-') }}</div></td>
            <td style="width: 28%; border-bottom: none;"><span class="label">Email Address</span><div class="value">{{ $val('email', $val('email_address', '-')) }}</div></td>
            <td style="width: 22%; border-bottom: none;"><span class="label">Mobile Number</span><div class="value">{{ $val('mobile', $val('mobile_number', '-')) }}</div></td>
            <td style="width: 27%; border-bottom: none;"><span class="label">Position / Designation</span><div class="value">{{ $val('position', $val('contact_person_position', '-')) }}</div></td>
        </tr>
    </table>

    <!-- Contact Info Row 3 (3 columns) -->
    <table class="grid-table">
        <tr>
            <td style="width: 50%; border-bottom: none;"><span class="label">Address</span><div class="value">{{ $val('address', '-') }}</div></td>
            <td style="width: 20%; border-bottom: none;"><span class="label">Company</span><div class="value">{{ $val('company', $val('company_name', '-')) }}</div></td>
            <td style="width: 30%; border-bottom: none;"><span class="label">Company Address</span><div class="value">{{ $val('company_address', '-') }}</div></td>
        </tr>
    </table>

    <!-- 4. SERVICE IDENTIFICATION -->
    <div class="section-title">Service Identification</div>
    <table class="grid-table">
        <tr>
            <td style="width: 50%; border-bottom: none;"><span class="label">Service Area</span><div class="value">{{ $val('service_area', '-') }}</div></td>
            <td style="width: 50%; border-bottom: none;"><span class="label">Services</span><div class="value">{{ $val('services', '-') }}</div></td>
        </tr>
    </table>

    <!-- 5. PRODUCTS -->
    <div class="section-title">Products</div>
    <table class="grid-table">
        <tr>
            <td style="width: 50%; border-bottom: none;"><span class="label">Products / Deliverables</span><div class="value">{{ $val('products', $val('products_deliverables', '-')) }}</div></td>
            <td style="width: 50%; border-bottom: none;"><span class="label">Scope of Work</span><div class="value">{{ $val('scope_of_work', '-') }}</div></td>
        </tr>
    </table>

    <!-- 6. CLIENT REQUIREMENTS -->
    <div class="section-title" style="background: #163b7a;">Client Requirements</div>
    <table class="req-table">
        <tr>
            <th style="width: 46%;">Requirement</th>
            <th style="width: 18%;">Provided</th>
            <th style="width: 18%;">Pending</th>
            <th style="width: 18%;">Required Action</th>
        </tr>
        @foreach($requirements as $req)
        <tr>
            <td style="{{ $loop->last ? 'border-bottom: none;' : '' }}">{{ $req['requirement'] ?? '-' }}</td>
            <td style="{{ $loop->last ? 'border-bottom: none;' : '' }}">{{ $req['provided'] ?? '-' }}</td>
            <td style="{{ $loop->last ? 'border-bottom: none;' : '' }}">{{ $req['pending'] ?? '-' }}</td>
            <td style="{{ $loop->last ? 'border-bottom: none;' : '' }}">{{ $req['required_action'] ?? '-' }}</td>
        </tr>
        @endforeach
    </table>

    <!-- 7. TIMELINE & ASSESSMENT -->
    <div class="section-title" style="background: #163b7a;">Timeline &amp; Assessment</div>
    
    <!-- Timeline Row 1 (4 columns) -->
    <table class="grid-table" style="border-bottom: 1px solid #0b2a68;">
        <tr>
            <td style="width: 25%; border-bottom: none;"><span class="label">Planned Start Date</span><div class="value">{{ $val('planned_start_date', '-') }}</div></td>
            <td style="width: 25%; border-bottom: none;"><span class="label">Estimated Duration</span><div class="value">{{ $val('estimated_duration', '-') }}</div></td>
            <td style="width: 25%; border-bottom: none;"><span class="label">Estimated Completion Date</span><div class="value">{{ $val('estimated_completion_date', '-') }}</div></td>
            <td style="width: 25%; border-bottom: none;"><span class="label">Confirmed Delivery Date</span><div class="value">{{ $val('confirmed_delivery_date', '-') }}</div></td>
        </tr>
    </table>

    <!-- Timeline Row 2 (3 columns) -->
    <table class="grid-table">
        <tr>
            <td style="width: 35%; border-bottom: none;"><span class="label">Timeline Notes</span><div class="value">{{ $val('timeline_notes', '-') }}</div></td>
            <td style="width: 25%; border-bottom: none;"><span class="label">Service Complexity</span><div class="value">{{ $val('service_complexity', '-') }}</div></td>
            <td style="width: 40%; border-bottom: none;"><span class="label">Professional Support Required</span><div class="value">{{ $val('professional_support_required', $val('support_required', '-')) }}</div></td>
        </tr>
    </table>

    <!-- 8. FEES & PAYMENT -->
    <div class="section-title">Fees &amp; Payment</div>
    <table class="grid-table">
        <tr>
            <td style="width: 25%; border-bottom: none;"><span class="label">Professional Fee</span><div class="value">{{ $val('professional_fee', $val('estimated_professional_fee', '-')) }}</div></td>
            <td style="width: 25%; border-bottom: none;"><span class="label">Government Fees</span><div class="value">{{ $val('government_fees', $val('estimated_government_fees', 'P0.00')) }}</div></td>
            <td style="width: 25%; border-bottom: none;"><span class="label">Service Support Fee</span><div class="value">{{ $val('service_support_fee', $val('estimated_service_support_fee', 'P0.00')) }}</div></td>
            <td style="width: 25%; border-bottom: none;"><span class="label">Payment Terms</span><div class="value">{{ $val('payment_terms', '-') }}</div></td>
        </tr>
    </table>

    <!-- 9. PROPOSAL & INTERNAL ASSIGNMENT -->
    <div class="section-title">Proposal &amp; Internal Assignment</div>
    
    <!-- Proposal Row 1 (3 columns) -->
    <table class="grid-table" style="border-bottom: 1px solid #0b2a68;">
        <tr>
            <td style="width: 35%; border-bottom: none;"><span class="label">Proposal Decision</span><div class="value">{{ $val('proposal_decision', '-') }}</div></td>
            <td style="width: 35%; border-bottom: none;"><span class="label">Decline Reason</span><div class="value">{{ $val('decline_reason', '-') }}</div></td>
            <td style="width: 30%; border-bottom: none;"><span class="label">Service Department / Unit</span><div class="value">{{ $val('service_department_unit', $val('service_department', '-')) }}</div></td>
        </tr>
    </table>

    <!-- Proposal Row 2 (2 columns) -->
    <table class="grid-table">
        <tr>
            <td style="width: 50%; border-bottom: none;"><span class="label">Assigned Consultant</span><div class="value">{{ $val('assigned_consultant', $val('lead_consultant', '-')) }}</div></td>
            <td style="width: 50%; border-bottom: none;"><span class="label">Assigned Associate</span><div class="value">{{ $val('assigned_associate', $val('lead_associate_assigned', '-')) }}</div></td>
        </tr>
    </table>

    <!-- 10. NOTES & APPROVAL -->
    <div class="section-title">Notes &amp; Approval</div>
    
    <!-- Notes Row 1 (2 columns) -->
    <table class="grid-table" style="border-bottom: 1px solid #0b2a68;">
        <tr>
            <td style="width: 50%; border-bottom: none;"><span class="label">Consultant Notes</span><div class="value">{{ $val('consultant_notes', '-') }}</div></td>
            <td style="width: 50%; border-bottom: none;"><span class="label">Associate Notes</span><div class="value">{{ $val('associate_notes', '-') }}</div></td>
        </tr>
    </table>

    <!-- Signatures Row 2 (4 columns) -->
    <table class="grid-table" style="border-bottom: 1px solid #0b2a68;">
        <tr>
            <td style="width: 26%; border-bottom: none;"><span class="label">Prepared By</span><div class="value">{{ $val('prepared_by', '-') }}</div></td>
            <td style="width: 26%; border-bottom: none;"><span class="label">Reviewed By</span><div class="value">{{ $val('reviewed_by', '-') }}</div></td>
            <td style="width: 22%; border-bottom: none;"><span class="label">Date</span><div class="value">{{ $val('internal_date', date('M d, Y')) }}</div></td>
            <td style="width: 26%; border-bottom: none;"><span class="label">Client Fullname &amp; Signature</span><div class="value">{{ $val('client_fullname_signature', '-') }}</div></td>
        </tr>
    </table>

    <!-- Signatures Row 3 (6 columns) -->
    <table class="grid-table">
        <tr>
            <td style="width: 16.66%; border-bottom: none;"><span class="label">Referred / Closed<br>By</span><div class="value">{{ $val('referred_closed_by', '-') }}</div></td>
            <td style="width: 16.66%; border-bottom: none;"><span class="label">Sales &amp; Marketing</span><div class="value">{{ $val('internal_sales_marketing', $val('sales_marketing', '-')) }}</div></td>
            <td style="width: 16.66%; border-bottom: none;"><span class="label">Lead Consultant</span><div class="value">{{ $val('lead_consultant', '-') }}</div></td>
            <td style="width: 16.66%; border-bottom: none;"><span class="label">Lead Associate</span><div class="value">{{ $val('lead_associate_assigned', $val('lead_associate', '-')) }}</div></td>
            <td style="width: 16.66%; border-bottom: none;"><span class="label">Finance</span><div class="value">{{ $val('internal_finance', $val('finance', '-')) }}</div></td>
            <td style="width: 16.70%; border-bottom: none;"><span class="label">President</span><div class="value">{{ $val('internal_president', $val('president', 'John Kelly Abalde')) }}</div></td>
        </tr>
    </table>

</div>

<!-- 11. COMPANY FOOTER -->
<div class="footer-note">John Kelly &amp; Company</div>

@if(!empty($autoPrint))
<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>
@endif

</body>
</html>
