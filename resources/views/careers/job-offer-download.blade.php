<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Offer</title>
    <style>
        @page {
            size: A4;
            margin: 18mm;
        }

        body {
            color: #0f172a;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.55;
        }

        .page {
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .header {
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .logo {
            height: 54px;
            width: auto;
        }

        .title {
            float: right;
            text-align: right;
            color: #1e3a8a;
            font-size: 22px;
            font-weight: 900;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-top: 5px;
        }

        .subtitle {
            color: #64748b;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1.6px;
            text-transform: uppercase;
        }

        .clear {
            clear: both;
        }

        .meta {
            margin: 22px 0;
        }

        .meta p {
            margin: 3px 0;
        }

        p {
            margin: 0 0 8px;
        }

        .section-bar {
            background: #1e3a8a;
            color: #ffffff;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 7px 10px;
            margin-top: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            vertical-align: top;
            text-align: left;
        }

        th {
            width: 34%;
            background: #f8fafc;
            font-weight: 800;
        }

        h2 {
            color: #1e3a8a;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 1.4px;
            text-transform: uppercase;
            margin: 0 0 12px;
        }

        h3 {
            font-size: 10.5px;
            margin: 0 0 5px;
        }

        .term {
            margin-bottom: 11px;
        }

        .term-body {
            white-space: pre-line;
        }

        .page-head {
            border-bottom: 1px solid #e2e8f0;
            color: #1e3a8a;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding-bottom: 8px;
            margin-bottom: 18px;
        }

        .page-number {
            float: right;
            color: #94a3b8;
            letter-spacing: 0;
            text-transform: none;
        }

        .signature-grid {
            width: 100%;
            margin-top: 38px;
            border-collapse: separate;
        }

        .signature-grid td {
            border: 0;
            padding: 0;
        }

        .signature-cell {
            width: 48%;
            vertical-align: top;
            text-align: center;
        }

        .signature-spacer {
            width: 4%;
        }

        .signature-title {
            font-weight: 900;
            letter-spacing: 1.3px;
            text-transform: uppercase;
            margin-bottom: 46px;
        }

        .signature-line {
            border-top: 1px solid #334155;
            padding-top: 6px;
        }

        .date-line {
            margin-top: 24px;
            text-align: left;
        }
    </style>
</head>
<body>
    @php
        $details = is_array($jobOffer->offer_details) ? $jobOffer->offer_details : [];
        $value = fn ($key, $fallback = 'N/A') => filled($details[$key] ?? null) ? $details[$key] : $fallback;
        $applicantName = $value('applicantName', $jobOffer->name ?? 'N/A');
        $applicantId = $value('applicantId');
        $applicantAddress = $value('applicantAddress', 'N/A');
        $offerDate = $value('offerDate', optional($jobOffer->created_at)->format('F d, Y h:i A') ?? now()->format('F d, Y h:i A'));
        $position = $value('position', $jobOffer->position ?? 'N/A');
        $department = $value('department', $jobOffer->department ?? 'N/A');
        $division = $value('division');
        $unit = $value('unit');
        $jobId = $value('jobId');
        $employmentType = $value('employmentType', $jobOffer->employment_type ?? 'N/A');
        $jobLevelRank = $value('jobLevelRank');
        $workArrangement = $value('workArrangement');
        $workSchedule = $value('workSchedule');
        $startDate = $value('startDate', optional($jobOffer->start_date)->format('F d, Y') ?? 'N/A');
        $salaryGrade = $value('salaryGrade');
        $salary = $value('salary', $jobOffer->salary ?? 'N/A');
        $benefits = $value('benefits', $jobOffer->benefits ?? 'As required by law and/or Company policy');
        $companyAddress = $value('companyAddress', $jobOffer->company_address ?? 'N/A');
        $branchOffice = $value('branchOffice');
        $immediateSupervisor = $value('immediateSupervisor');
        $reportingTo = $value('reportingTo');
        $offerExpiryDate = $value('offerExpiryDate');

        $logoPath = file_exists(public_path('images/FINAL_LOGO.jpg'))
            ? public_path('images/FINAL_LOGO.jpg')
            : public_path('images/imaglogo.png');

        $positionRows = [
            'Applicant Name' => $applicantName,
            'Applicant ID' => $applicantId,
            'Position / Title' => $position,
            'Department' => $department,
            'Division' => $division,
            'Unit' => $unit,
            'Job ID' => $jobId,
            'Employment Type' => $employmentType,
            'Job Level / Rank' => $jobLevelRank,
            'Work Arrangement' => $workArrangement,
            'Work Schedule' => $workSchedule,
            'Start Date' => $startDate,
            'Salary Grade' => $salaryGrade,
            'Salary' => $salary,
            'Benefits' => $benefits,
            'Company Address' => $companyAddress,
            'Branch / Office' => $branchOffice,
            'Immediate Supervisor' => $immediateSupervisor,
            'Reporting To' => $reportingTo,
            'Offer Expiry Date' => $offerExpiryDate,
        ];

        $terms = [
            ['title' => '1. Employment Particulars', 'body' => "The Employee's position, employment type, start date, compensation, benefits, work arrangement, schedule, work location, reporting line, and other employment particulars shall be as stated in the Position Information above.\nAll employment particulars shall remain subject to Company policy, operational requirements, lawful management prerogative, and applicable law."],
            ['title' => '2. Duties and Assignment', 'body' => "The Employee shall perform the duties, functions, responsibilities, tasks, projects, and assignments required by the position and such other related duties as may be assigned by the Company.\nThe Company reserves the right to modify, expand, reduce, transfer, reassign, or restructure the Employee's duties, responsibilities, reporting relationships, department, unit, branch, work location, schedule, or assignment based on operational requirements, business needs, client requirements, project requirements, emergencies, deadlines, or other legitimate business considerations."],
            ['title' => '3. Employment Status', 'body' => "The Employee's employment status shall be as stated in the Position Information above.\nWhere applicable, regularization shall be subject to Company standards, performance requirements, Company policy, and applicable law. Nothing in this Job Offer shall be construed as a guarantee of regular employment or continued employment for any specific period."],
            ['title' => '4. Compensation and Benefits', 'body' => "The Employee shall receive only the compensation and benefits stated in the Position Information above, subject to lawful deductions, withholding taxes, government-mandated contributions, Company policy, and applicable law.\nBonuses, incentives, allowances, commissions, salary adjustments, profit sharing, and other additional compensation or benefits shall not be guaranteed unless expressly approved in writing by the Company."],
            ['title' => '5. Compliance with Company Issuances', 'body' => "The Employee shall comply with all Company policies, manuals, handbooks, memoranda, notices, resolutions, circulars, directives, codes of conduct, operating procedures, security requirements, compliance requirements, and lawful instructions issued by the Company, whether existing or later issued, amended, or implemented.\nViolation of Company policies or lawful instructions may result in disciplinary action in accordance with Company policy, due process, and applicable law."],
            ['title' => '6. Confidentiality, Intellectual Property, and Company Property', 'body' => "The Employee shall keep all Company, client, supplier, employee, contractor, financial, commercial, operational, legal, technical, and business information strictly confidential.\nAll work products, documents, reports, databases, systems, software, templates, manuals, presentations, designs, content, records, inventions, developments, concepts, processes, methodologies, intellectual property, and other outputs created, developed, prepared, accessed, received, or handled by the Employee in connection with employment shall belong exclusively to the Company to the fullest extent permitted by law.\nAll Company records, files, documents, equipment, devices, credentials, access rights, systems, materials, and property issued to or accessed by the Employee shall remain the property of the Company and shall be returned immediately upon demand or upon separation from employment.\nThe Employee shall not disclose, copy, use, transfer, retain, reproduce, publish, or distribute Company or client information except when authorized for official duties. These obligations shall continue after employment ends."],
            ['title' => '7. Privacy and Data Protection', 'body' => "The Employee shall comply with all Company privacy, data protection, information security, records management, cybersecurity, and confidentiality requirements.\nEmployee, client, Company, and third-party information shall be handled only for authorized business purposes and in accordance with applicable law and Company policy."],
            ['title' => '8. Management Prerogative', 'body' => "Nothing in this Job Offer shall limit the Company's lawful management prerogative to manage, direct, organize, control, assign, transfer, supervise, discipline, evaluate, restructure, suspend, investigate, or otherwise administer its business operations and workforce consistent with applicable law."],
            ['title' => '9. Separation and Termination', 'body' => "Employment may be suspended, separated, terminated, or otherwise ended only in accordance with Company policy, due process, and applicable law.\nNothing in this Job Offer shall be interpreted as a guarantee of continued employment for any specific duration."],
            ['title' => '10. Superseding Clause', 'body' => "This Job Offer constitutes only the initial offer of employment.\nUpon execution of a formal Employment Contract, Employment Agreement, Probationary Employment Agreement, Regular Employment Agreement, or similar employment document, such document shall automatically supersede, replace, and govern over this Job Offer.\nIn case of conflict or inconsistency, the subsequently executed employment document shall prevail."],
        ];

        $maxUnits = 128;
        $acceptanceUnits = 28;
        $termPages = [];
        $currentPage = ['terms' => [], 'units' => 14, 'includeAcceptance' => false];

        foreach ($terms as $term) {
            $body = (string) $term['body'];
            $bodyLines = substr_count($body, "\n");
            $units = 5 + (int) ceil(strlen((string) $term['title']) / 70) + (int) ceil(strlen($body) / 90) + $bodyLines;

            if (count($currentPage['terms']) && $currentPage['units'] + $units > $maxUnits) {
                $termPages[] = $currentPage;
                $currentPage = ['terms' => [], 'units' => 14, 'includeAcceptance' => false];
            }

            $currentPage['terms'][] = $term;
            $currentPage['units'] += $units;
        }

        if ($currentPage['units'] + $acceptanceUnits <= $maxUnits) {
            $currentPage['includeAcceptance'] = true;
            $termPages[] = $currentPage;
        } else {
            $termPages[] = $currentPage;
            $termPages[] = ['terms' => [], 'units' => $acceptanceUnits, 'includeAcceptance' => true];
        }
    @endphp

    <div class="page">
        <div class="header">
            <div class="title">
                JOB OFFER
                <div class="subtitle">John Kelly &amp; Company (JK&amp;C Inc.)</div>
            </div>
            @if (file_exists($logoPath))
                <img src="{{ $logoPath }}" class="logo" alt="John Kelly & Company">
            @endif
            <div class="clear"></div>
        </div>

        <div class="meta">
            <p><strong>Date:</strong> {{ $offerDate }}</p>
            <p><strong>Applicant Name:</strong> {{ $applicantName }}</p>
            <p><strong>Applicant ID:</strong> {{ $applicantId }}</p>
            <p><strong>Address:</strong> {{ $applicantAddress }}</p>
        </div>

        <p>Dear {{ $applicantName }},</p>
        <p>John Kelly &amp; Company (JK&amp;C INC.) is pleased to offer employment under the terms and conditions stated in this Job Offer.</p>
        <p>This Job Offer is confidential and subject to Company policies, procedures, memoranda, notices, resolutions, directives, lawful instructions, management prerogative, and applicable laws.</p>

        <div class="section-bar">Position Information</div>
        <table>
            <tbody>
                @foreach ($positionRows as $label => $rowValue)
                    <tr>
                        <th>{{ $label }}</th>
                        <td>{{ $rowValue ?: 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @foreach ($termPages as $pageIndex => $page)
        <div class="page">
            <div class="page-head">
                Job Offer
                <span class="page-number">Page {{ $pageIndex + 2 }}</span>
            </div>

            <h2>Terms and Conditions</h2>

            @foreach ($page['terms'] as $term)
                <div class="term">
                    <h3>{{ $term['title'] }}</h3>
                    <p class="term-body">{{ $term['body'] }}</p>
                </div>
            @endforeach

            @if ($page['includeAcceptance'])
                <h2 style="margin-top: 26px;">Acceptance of Job Offer</h2>
                <p>By signing below, the Applicant acknowledges that this Job Offer has been read, understood, and accepted.</p>
                <p>Acceptance of this Job Offer also confirms agreement to comply with all Company policies, procedures, memoranda, notices, resolutions, directives, and lawful instructions.</p>
                <p>This Job Offer shall automatically expire if not accepted on or before <strong>{{ $offerExpiryDate }}</strong>, unless extended by the Company in writing.</p>

                <table class="signature-grid">
                    <tr>
                        <td class="signature-cell">
                            <div class="signature-title">For the Company</div>
                            <div class="signature-line">
                                <strong>Authorized Representative</strong><br>
                                President / Human Capital
                            </div>
                            <div class="date-line">Date: ______________________</div>
                        </td>
                        <td class="signature-spacer"></td>
                        <td class="signature-cell">
                            <div class="signature-title">Accepted By</div>
                            <div class="signature-line">
                                <strong>Applicant Signature</strong><br>
                                {{ $applicantName }}
                            </div>
                            <div class="date-line">Date: ______________________</div>
                        </td>
                    </tr>
                </table>
            @endif
        </div>
    @endforeach
</body>
</html>
