<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Offer - John Kelly &amp; Company (JK&amp;C Inc.)</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #e9eef5;
            color: #061533;
            font-family: Arial, Helvetica, sans-serif;
        }

        .page-shell {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 360px;
            gap: 24px;
            max-width: 1320px;
            margin: 0 auto;
            padding: 28px;
        }

        .document-stack {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 24px;
            min-width: 0;
        }

        .a4-page {
            width: 794px;
            min-height: 1123px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 32px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.14);
            position: relative;
            font-size: 12px;
            line-height: 1.625;
            break-after: page;
            page-break-after: always;
        }

        .doc-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 16px;
            margin-bottom: 22px;
        }

        .brand-logo {
            height: 64px;
            width: auto;
            object-fit: contain;
        }

        .brand-name {
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: #64748b;
        }

        .doc-title {
            font-size: 24px;
            font-weight: 900;
            color: #1e3a8a;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            text-align: right;
        }

        .page-number {
            position: absolute;
            right: 18mm;
            top: 20mm;
            color: #5d6f95;
            font-size: 10px;
        }

        h1,
        h2,
        h3,
        p {
            margin-top: 0;
        }

        h1 {
            color: #0f2f8f;
            font-size: 21px;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        h2 {
            color: #1e3a8a;
            font-size: 14px;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            margin: 22px 0 14px;
        }

        .section-bar {
            background: #1e3a8a;
            color: #ffffff;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            padding: 8px 12px;
            margin: 28px 0 0;
        }

        h3 {
            color: #07152f;
            font-size: 11px;
            margin: 0 0 6px;
        }

        p {
            font-size: 11px;
            line-height: 1.52;
            margin-bottom: 9px;
        }

        .letter-meta {
            display: grid;
            grid-template-columns: 32mm 1fr;
            gap: 5px 10px;
            margin-bottom: 18px;
            font-size: 11px;
        }

        .letter-meta strong {
            color: #0f2f8f;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .info-table th,
        .info-table td {
            border: 1px solid #d7deea;
            padding: 7px 9px;
            vertical-align: top;
            text-align: left;
        }

        .info-table th {
            width: 192px;
            background: #f5f7fb;
            color: #0f172a;
            font-weight: 900;
        }

        .term {
            margin-bottom: 12px;
        }

        .term-body {
            white-space: pre-line;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 26px;
            margin-top: 30px;
            font-size: 11px;
        }

        .signature-card {
            border: 1px solid #d7deea;
            padding: 16px;
            min-height: 145px;
        }

        .signature-title {
            color: #0f2f8f;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-bottom: 32px;
        }

        .signature-line {
            border-top: 1px solid #061533;
            padding-top: 7px;
            margin-top: 22px;
            font-weight: 700;
        }

        .signature-caption {
            color: #667694;
            font-size: 10px;
            margin-top: 3px;
        }

        .action-panel {
            position: sticky;
            top: 24px;
            align-self: start;
            background: #ffffff;
            border: 1px solid #d7deea;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.12);
            padding: 22px;
        }

        .action-panel h2 {
            border: 0;
            padding: 0;
            margin: 0 0 10px;
            font-size: 16px;
        }

        .summary-list {
            display: grid;
            gap: 12px;
            margin: 18px 0;
        }

        .summary-item {
            border-bottom: 1px solid #e5eaf2;
            padding-bottom: 10px;
        }

        .summary-label {
            color: #667694;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .summary-value {
            color: #061533;
            font-size: 13px;
            font-weight: 800;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 30px;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .status-open {
            background: #eef4ff;
            color: #0f2f8f;
        }

        .status-accepted {
            background: #eaf8ef;
            color: #166534;
        }

        .status-declined {
            background: #feecec;
            color: #991b1b;
        }

        .field-label {
            display: block;
            color: #33415c;
            font-size: 12px;
            font-weight: 800;
            margin: 18px 0 8px;
        }

        input[type="file"] {
            width: 100%;
            border: 1px dashed #9fb0ce;
            background: #f8fafc;
            padding: 12px;
            font-size: 12px;
        }

        .help-text {
            color: #667694;
            font-size: 12px;
            line-height: 1.45;
            margin: 8px 0 0;
        }

        .button-row {
            display: grid;
            gap: 10px;
            margin-top: 18px;
        }

        .btn {
            border: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 11px 16px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
        }

        .btn-primary {
            background: #0f2f8f;
            color: #ffffff;
        }

        .btn-muted {
            background: #eef2f7;
            color: #0f2f8f;
        }

        .btn-danger {
            background: #fff1f1;
            color: #b91c1c;
        }

        .error {
            color: #b91c1c;
            font-size: 12px;
            margin-top: 7px;
        }

        @media (max-width: 1180px) {
            .page-shell {
                grid-template-columns: 1fr;
            }

            .action-panel {
                position: static;
                order: -1;
            }
        }

        @media (max-width: 860px) {
            .page-shell {
                padding: 14px;
            }

            .a4-page {
                width: 100%;
                min-height: auto;
                padding: 22px;
            }

            .signature-grid {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            body {
                background: #ffffff;
            }

            .page-shell {
                display: block;
                max-width: none;
                padding: 0;
            }

            .action-panel {
                display: none;
            }

            .document-stack {
                display: block;
            }

            .a4-page {
                width: 210mm;
                min-height: 297mm;
                box-shadow: none;
                page-break-after: always;
            }
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
            [
                'title' => '1. Employment Particulars',
                'body' => "The Employee's position, employment type, start date, compensation, benefits, work arrangement, schedule, work location, reporting line, and other employment particulars shall be as stated in the Position Information above.\nAll employment particulars shall remain subject to Company policy, operational requirements, lawful management prerogative, and applicable law.",
            ],
            [
                'title' => '2. Duties and Assignment',
                'body' => "The Employee shall perform the duties, functions, responsibilities, tasks, projects, and assignments required by the position and such other related duties as may be assigned by the Company.\nThe Company reserves the right to modify, expand, reduce, transfer, reassign, or restructure the Employee's duties, responsibilities, reporting relationships, department, unit, branch, work location, schedule, or assignment based on operational requirements, business needs, client requirements, project requirements, emergencies, deadlines, or other legitimate business considerations.",
            ],
            [
                'title' => '3. Employment Status',
                'body' => "The Employee's employment status shall be as stated in the Position Information above.\nWhere applicable, regularization shall be subject to Company standards, performance requirements, Company policy, and applicable law. Nothing in this Job Offer shall be construed as a guarantee of regular employment or continued employment for any specific period.",
            ],
            [
                'title' => '4. Compensation and Benefits',
                'body' => "The Employee shall receive only the compensation and benefits stated in the Position Information above, subject to lawful deductions, withholding taxes, government-mandated contributions, Company policy, and applicable law.\nBonuses, incentives, allowances, commissions, salary adjustments, profit sharing, and other additional compensation or benefits shall not be guaranteed unless expressly approved in writing by the Company.",
            ],
            [
                'title' => '5. Compliance with Company Issuances',
                'body' => "The Employee shall comply with all Company policies, manuals, handbooks, memoranda, notices, resolutions, circulars, directives, codes of conduct, operating procedures, security requirements, compliance requirements, and lawful instructions issued by the Company, whether existing or later issued, amended, or implemented.\nViolation of Company policies or lawful instructions may result in disciplinary action in accordance with Company policy, due process, and applicable law.",
            ],
            [
                'title' => '6. Confidentiality, Intellectual Property, and Company Property',
                'body' => "The Employee shall keep all Company, client, supplier, employee, contractor, financial, commercial, operational, legal, technical, and business information strictly confidential.\nAll work products, documents, reports, databases, systems, software, templates, manuals, presentations, designs, content, records, inventions, developments, concepts, processes, methodologies, intellectual property, and other outputs created, developed, prepared, accessed, received, or handled by the Employee in connection with employment shall belong exclusively to the Company to the fullest extent permitted by law.\nAll Company records, files, documents, equipment, devices, credentials, access rights, systems, materials, and property issued to or accessed by the Employee shall remain the property of the Company and shall be returned immediately upon demand or upon separation from employment.\nThe Employee shall not disclose, copy, use, transfer, retain, reproduce, publish, or distribute Company or client information except when authorized for official duties. These obligations shall continue after employment ends.",
            ],
            [
                'title' => '7. Privacy and Data Protection',
                'body' => "The Employee shall comply with all Company privacy, data protection, information security, records management, cybersecurity, and confidentiality requirements.\nEmployee, client, Company, and third-party information shall be handled only for authorized business purposes and in accordance with applicable law and Company policy.",
            ],
            [
                'title' => '8. Management Prerogative',
                'body' => "Nothing in this Job Offer shall limit the Company's lawful management prerogative to manage, direct, organize, control, assign, transfer, supervise, discipline, evaluate, restructure, suspend, investigate, or otherwise administer its business operations and workforce consistent with applicable law.",
            ],
            [
                'title' => '9. Separation and Termination',
                'body' => "Employment may be suspended, separated, terminated, or otherwise ended only in accordance with Company policy, due process, and applicable law.\nNothing in this Job Offer shall be interpreted as a guarantee of continued employment for any specific duration.",
            ],
            [
                'title' => '10. Superseding Clause',
                'body' => "This Job Offer constitutes only the initial offer of employment.\nUpon execution of a formal Employment Contract, Employment Agreement, Probationary Employment Agreement, Regular Employment Agreement, or similar employment document, such document shall automatically supersede, replace, and govern over this Job Offer.\nIn case of conflict or inconsistency, the subsequently executed employment document shall prevail.",
            ],
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

        $statusClass = match ($jobOffer->status) {
            'Accepted' => 'status-accepted',
            'Declined' => 'status-declined',
            default => 'status-open',
        };
    @endphp

    <main class="page-shell">
        <section class="document-stack" aria-label="Job Offer Document">
            <article class="a4-page">
                <div class="page-number">Page 1</div>
                <header class="doc-header">
                    <img src="{{ asset('images/FINAL_LOGO.jpg') }}" onerror="this.src='{{ asset('images/imaglogo.png') }}'" alt="John Kelly &amp; Company (JK&amp;C Inc.)" class="brand-logo">
                    <div>
                        <div class="doc-title">Job Offer</div>
                        <div class="brand-name">John Kelly &amp; Company (JK&amp;C Inc.)</div>
                    </div>
                </header>

                <h1>Job Offer</h1>

                <div class="letter-meta">
                    <strong>Date:</strong>
                    <span>{{ $offerDate }}</span>
                    <strong>Applicant Name:</strong>
                    <span>{{ $applicantName }}</span>
                    <strong>Applicant ID:</strong>
                    <span>{{ $applicantId }}</span>
                    <strong>Address:</strong>
                    <span>{{ $applicantAddress }}</span>
                </div>

                <p>Dear {{ $applicantName }},</p>
                <p>John Kelly &amp; Company (JK&amp;C Inc.) is pleased to offer employment under the terms and conditions stated in this Job Offer.</p>
                <p>This Job Offer is confidential and subject to Company policies, procedures, memoranda, notices, resolutions, directives, lawful instructions, management prerogative, and applicable laws.</p>

                <div class="section-bar">Position Information</div>
                <table class="info-table">
                    <tbody>
                        @foreach ($positionRows as $label => $rowValue)
                            <tr>
                                <th>{{ $label }}</th>
                                <td>{{ $rowValue }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </article>

            @foreach ($termPages as $pageIndex => $page)
                <article class="a4-page">
                    <div class="page-number">Page {{ $pageIndex + 2 }}</div>
                    <header class="doc-header">
                        <div class="brand-name" style="color: #1e3a8a;">Job Offer</div>
                        <div class="brand-name">John Kelly &amp; Company (JK&amp;C Inc.)</div>
                    </header>

                    <h2>Terms and Conditions</h2>

                    @foreach ($page['terms'] as $term)
                        <section class="term">
                            <h3>{{ $term['title'] }}</h3>
                            <p class="term-body">{{ $term['body'] }}</p>
                        </section>
                    @endforeach

                    @if ($page['includeAcceptance'])
                        <h2>Acceptance of Job Offer</h2>
                        <p>By signing below, the Applicant acknowledges that this Job Offer has been read, understood, and accepted.</p>
                        <p>Acceptance of this Job Offer also confirms agreement to comply with all Company policies, procedures, memoranda, notices, resolutions, directives, and lawful instructions.</p>
                        <p>This Job Offer shall automatically expire if not accepted on or before {{ $offerExpiryDate }}, unless extended by the Company in writing.</p>

                        <div class="signature-grid">
                            <div>
                                <p style="font-weight: 900; letter-spacing: 0.12em; text-transform: uppercase; text-align: center;">For the Company</p>
                                <div class="signature-line" style="text-align: center;">
                                    <p style="margin: 0; font-weight: 700;">Authorized Representative</p>
                                    <p style="margin: 0;">President / Human Capital</p>
                                </div>
                                <p style="margin-top: 24px;">Date: ______________________</p>
                            </div>
                            <div>
                                <p style="font-weight: 900; letter-spacing: 0.12em; text-transform: uppercase; text-align: center;">Accepted By</p>
                                <div class="signature-line" style="text-align: center;">
                                    <p style="margin: 0; font-weight: 700;">Applicant Signature</p>
                                    <p style="margin: 0;">{{ $applicantName }}</p>
                                </div>
                                <p style="margin-top: 24px;">Date: ______________________</p>
                            </div>
                        </div>
                    @endif
                </article>
            @endforeach
        </section>

        <aside class="action-panel" aria-label="Job Offer Actions">
            <h2>Review Job Offer</h2>
            <span class="status-badge {{ $statusClass }}">{{ $jobOffer->status ?? 'Pending' }}</span>

            <div class="summary-list">
                <div class="summary-item">
                    <div class="summary-label">Applicant</div>
                    <div class="summary-value">{{ $applicantName }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Position</div>
                    <div class="summary-value">{{ $position }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Department</div>
                    <div class="summary-value">{{ $department }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Start Date</div>
                    <div class="summary-value">{{ $startDate }}</div>
                </div>
            </div>

            @if ($jobOffer->status === 'Accepted')
                <p class="help-text">This job offer has already been accepted. The Human Capital team will proceed with the next onboarding steps.</p>
                @if ($jobOffer->signed_offer_path)
                    <p class="help-text">Signed copy received.</p>
                @endif
            @elseif ($jobOffer->status === 'Declined')
                <p class="help-text">This job offer has already been declined. Please contact the Human Capital team if this was a mistake.</p>
            @else
                <form method="POST" action="{{ route('job-offer.accept.submit', $jobOffer->accept_token) }}" enctype="multipart/form-data">
                    @csrf
                    <label class="field-label" for="signed_offer">Upload signed copy</label>
                    <input id="signed_offer" name="signed_offer" type="file" accept=".pdf,.jpg,.jpeg,.png" required>
                    <p class="help-text">Required before accepting. Accepted files: PDF, JPG, JPEG, PNG up to 10 MB.</p>
                    @error('signed_offer')
                        <div class="error">{{ $message }}</div>
                    @enderror

                    <div class="button-row">
                        <a href="{{ route('job-offer.download', $jobOffer->accept_token) }}" class="btn btn-muted">Download Document</a>
                        <button type="submit" class="btn btn-primary">Accept Job Offer</button>
                        <a href="{{ route('job-offer.decline', $jobOffer->accept_token) }}" class="btn btn-danger">Decline Job Offer</a>
                    </div>
                </form>
            @endif
        </aside>
    </main>
</body>
</html>
