<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $job->position ?: 'Job Opening' }} | John Kelly &amp; Company</title>

    <link rel="icon" type="image/png" href="{{ asset('images/image.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #ffffff;
            color: #111827;
            line-height: 1.6;
        }
        a { color: inherit; text-decoration: none; }

        :root {
            --brand: #2563eb;
            --brand-dark: #1e3a8a;
            --brand-soft: #eff6ff;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #dbe3ef;
            --panel: #f8fafc;
        }

        nav {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
        }

        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 80px;
        }

        .nav-brand {
            display: inline-flex;
            align-items: center;
            gap: 16px;
        }

        .logo img {
            height: 50px;
            width: auto;
            object-fit: contain;
        }

        .nav-title {
            color: var(--brand);
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .16em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .outline-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 18px;
            border: 1.5px solid var(--brand);
            color: var(--brand);
            border-radius: 10px;
            background: #fff;
            font-weight: 900;
        }

        .outline-btn:hover { background: var(--brand-soft); }

        .page {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 30px 0 70px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 20px;
        }

        .breadcrumb a { color: var(--brand); font-weight: 700; }

        .job-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 34px;
            align-items: start;
        }

        .main-content { min-width: 0; }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #bfdbfe;
            color: var(--brand);
            background: #fff;
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 14px;
        }

        .back-btn:hover { background: var(--brand-soft); }

        .job-hero {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 28px;
            margin-bottom: 30px;
        }

        .job-title {
            color: #020617;
            font-size: clamp(30px, 5vw, 48px);
            line-height: 1.08;
            font-weight: 900;
            letter-spacing: -.045em;
            max-width: 820px;
            margin-bottom: 18px;
        }

        .badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: 1px solid #bfdbfe;
            background: #dbeafe;
            color: #1d4ed8;
            padding: 6px 10px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 800;
        }

        .job-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-top: 18px;
        }

        .summary-item {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: var(--panel);
            padding: 13px 14px;
            min-height: 76px;
        }

        .summary-item strong {
            display: block;
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .summary-item span {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .apply-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 0 20px;
            border: 0;
            border-radius: 8px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            font-size: 14px;
            font-weight: 900;
            box-shadow: 0 10px 24px rgba(37, 99, 235, .22);
        }

        .apply-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 30px rgba(37, 99, 235, .28);
        }

        .details-section {
            margin-bottom: 34px;
        }

        .details-section h2,
        .details-section h3 {
            color: #020617;
            font-weight: 900;
            letter-spacing: -.025em;
        }

        .details-section h2 {
            font-size: 21px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }

        .details-section h3 {
            font-size: 17px;
            margin-bottom: 14px;
        }

        .body-text {
            color: #111827;
            font-size: 15.5px;
            line-height: 1.8;
            white-space: pre-line;
        }

        .body-text p { margin-bottom: 16px; }
        .body-text ul { margin: 10px 0 18px 22px; }
        .body-text li { margin-bottom: 7px; }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 24px;
            margin-top: 10px;
            font-size: 14px;
        }

        .info-row {
            display: grid;
            grid-template-columns: 112px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
        }

        .info-row strong { color: #020617; font-weight: 900; }
        .info-row span { color: #334155; }

        .side-panel {
            position: sticky;
            top: 102px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            background: #fff;
            box-shadow: 0 16px 40px rgba(15, 23, 42, .07);
            color: #334155;
        }

        .side-panel h3 {
            color: #020617;
            font-size: 17px;
            line-height: 1.35;
            font-weight: 900;
            margin-bottom: 10px;
        }

        .side-panel p {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 16px;
        }

        .side-panel .apply-btn,
        .side-panel .outline-btn {
            width: 100%;
            margin-bottom: 10px;
        }

        .side-list {
            border-top: 1px solid #e5e7eb;
            margin-top: 16px;
            padding-top: 16px;
            display: grid;
            gap: 10px;
            font-size: 13px;
        }

        .side-list div {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .side-list strong { color: #64748b; }
        .side-list span { color: #0f172a; font-weight: 800; text-align: right; }

        .footer {
            border-top: 1px solid #e5e7eb;
            background: #f8fafc;
            padding: 22px 0;
            color: #64748b;
            font-size: 13px;
        }

        .footer-inner {
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .footer strong { color: var(--brand); }

        .mobile-apply { display: none; }

        @media (max-width: 980px) {
            .job-layout { grid-template-columns: 1fr; }
            .side-panel { position: static; }
            .main-content { max-width: none; }
            .job-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 640px) {
            .brand-text { display: none; }
            .nav { min-height: 64px; }
            .nav-container { padding: 0 1rem; height: 68px; }
            .nav-brand { gap: 10px; }
            .nav-title { font-size: 10px; letter-spacing: .12em; }
            .logo img { height: 42px; }
            .nav-actions a:first-child { display: none; }
            .page { width: calc(100% - 24px); }
            .job-summary-grid { grid-template-columns: 1fr; }
            .info-grid { grid-template-columns: 1fr; }
            .info-row { grid-template-columns: 105px minmax(0, 1fr); }
            .side-panel { display: none; }
            .mobile-apply {
                display: block;
                position: sticky;
                bottom: 0;
                z-index: 40;
                padding: 12px;
                background: #fff;
                border-top: 1px solid #e5e7eb;
            }
            .mobile-apply .apply-btn { width: 100%; }
        }

        @media print {
            nav, .side-panel, .mobile-apply, .footer, .back-btn { display: none !important; }
            .page { width: 100%; padding: 0; }
            .job-layout { display: block; }
            body { background: white; }
        }
    </style>
</head>
<body>
@php
    $title = $job->position ?: 'Job Opening';
    $location = $job->location ?: $job->office_branch_site ?: $job->applicable_area ?: 'Location TBA';
    $department = $job->department_unit ?: $job->department ?: 'Department TBA';
    $description = $job->job_description ?: $job->duties_responsibilities ?: 'No job description has been added yet.';
    $requirements = $job->requirements ?: $job->education_req ?: null;
    $postedDate = $job->posted_date ? optional($job->posted_date)->format('M d, Y') : (optional($job->posting_start_date)->format('M d, Y') ?: 'Recently posted');

    $salary = null;
    if (!empty($job->min_salary_offer) || !empty($job->max_salary_offer)) {
        $min = $job->min_salary_offer ? 'PHP ' . number_format((float) $job->min_salary_offer, 0) : null;
        $max = $job->max_salary_offer ? 'PHP ' . number_format((float) $job->max_salary_offer, 0) : null;
        $salary = trim(($min ?: '') . (($min && $max) ? ' - ' : '') . ($max ?: ''));
    }

    $benefits = is_array($job->benefits_package) ? array_values(array_filter($job->benefits_package)) : [];
    $workSchedule = is_array($job->work_schedule) ? array_values(array_filter($job->work_schedule)) : [];
    $applyUrl = route('careers.apply') . '?job_id=' . $job->id;

    // Get MRF data for required documents
    $mrf = $job->mrf;
    $requiredDocuments = [];
    if ($mrf && is_array($mrf->required_documents)) {
        $requiredDocuments = array_values(array_filter($mrf->required_documents));
    }
    
    // Get division/unit info
    $divisionUnit = null;
    if ($job->division) {
        $divisionUnit = $job->division->name;
        if ($job->unit) {
            $divisionUnit .= ' / ' . $job->unit->name;
        }
    } elseif ($job->unit) {
        $divisionUnit = $job->unit->name;
    }
    
    $jobStatus = $job->status === 'Screening' ? 'Posted / Open' : ($job->status ?: 'Posted');
@endphp

<!-- Navigation Bar -->
<nav>
    <div class="nav-container">
        <div class="nav-brand">
            <div class="logo">
                <a href="{{ route('homepage.public') }}">
                    <img src="{{ asset('images/FINAL_LOGO.jpg') }}" onerror="this.src='{{ asset('images/imaglogo.png') }}'" alt="John Kelly &amp; Company Logo">
                </a>
            </div>
            <div class="nav-title">Career Opportunity</div>
        </div>
    </div>
</nav>

<main class="page">
    <a href="{{ route('homepage.public') }}#careers" class="back-btn">Back</a>

    <div class="breadcrumb">
        <a href="{{ route('homepage.public') }}#careers">Careers</a>
        <span>/</span>
        <span>{{ $title }}</span>
    </div>

    <div class="job-layout">
        <section class="main-content">

            <div class="job-hero">
                <h1 class="job-title">{{ $title }}</h1>

                <div class="badges">
                    @if($job->job_id)<span class="badge">Job ID: {{ $job->job_id }}</span>@endif
                    <span class="badge">{{ $job->status === 'Screening' ? 'Posted / Open' : ($job->status ?: 'Posted') }}</span>
                    <span class="badge">Posted: {{ $postedDate }}</span>
                </div>

                <div class="job-summary-grid">
                    <div class="summary-item">
                        <strong>Position / Title</strong>
                        <span>{{ $title }}</span>
                    </div>
                    <div class="summary-item">
                        <strong>Date Posted</strong>
                        <span>{{ $postedDate }}</span>
                    </div>
                    <div class="summary-item">
                        <strong>Job Status</strong>
                        <span>{{ $jobStatus }}</span>
                    </div>
                    <div class="summary-item">
                        <strong>Department</strong>
                        <span>{{ $department }}</span>
                    </div>
                    <div class="summary-item">
                        <strong>Division / Unit</strong>
                        <span>{{ $divisionUnit ?: 'TBA' }}</span>
                    </div>
                    <div class="summary-item">
                        <strong>Employment Type</strong>
                        <span>{{ $job->employment_type ?: 'TBA' }}</span>
                    </div>
                    <div class="summary-item">
                        <strong>Job Level / Rank</strong>
                        <span>{{ $job->position_level ?: 'TBA' }}</span>
                    </div>
                    <div class="summary-item">
                        <strong>Work Location</strong>
                        <span>{{ $location }}</span>
                    </div>
                    <div class="summary-item">
                        <strong>Work Arrangement</strong>
                        <span>{{ $job->office_branch_site ?: 'TBA' }}</span>
                    </div>
                    <div class="summary-item">
                        <strong>Work Schedule</strong>
                        <span>{{ count($workSchedule) ? implode(', ', array_slice($workSchedule, 0, 1)) : ($job->rest_days ?: 'TBA') }}</span>
                    </div>
                    <div class="summary-item">
                        <strong>Vacancies</strong>
                        <span>{{ $job->no_of_vacancies ? $job->no_of_vacancies . (((int) $job->no_of_vacancies) > 1 ? ' Open Positions' : ' Open Position') : 'TBA' }}</span>
                    </div>
                    <div class="summary-item">
                        <strong>Salary Range</strong>
                        <span>{{ $salary ?: 'Competitive' }}</span>
                    </div>
                </div>
            </div>

            <div class="details-section">
                <h2>Job Summary</h2>
                <div class="body-text">
                    <p>{!! nl2br(e($description)) !!}</p>
                </div>
            </div>

            @if($job->duties_responsibilities && $job->duties_responsibilities !== $job->job_description)
                <div class="details-section">
                    <h3>Duties & Responsibilities</h3>
                    <div class="body-text">{!! nl2br(e($job->duties_responsibilities)) !!}</div>
                </div>
            @endif

            <div class="details-section">
                <h2>Qualifications</h2>
                
                @if($job->education_req)
                    <h3>Educational Requirement</h3>
                    <div class="body-text">
                        <p>{!! nl2br(e($job->education_req)) !!}</p>
                    </div>
                @endif

                @if($job->experience_req)
                    <h3>Preferred Qualifications / Experience</h3>
                    <div class="body-text">
                        <p>{!! nl2br(e($job->experience_req)) !!}</p>
                    </div>
                @endif

                @if($job->skills_req)
                    <h3>Required Skills / Competencies</h3>
                    <div class="body-text">
                        <p>{!! nl2br(e($job->skills_req)) !!}</p>
                    </div>
                @endif
            </div>

            <div class="details-section">
                <h2>Work Details</h2>
                <div class="info-grid">
                    <div class="info-row">
                        <strong>Work Classification:</strong>
                        <span>{{ $job->office_branch_site ?: 'TBA' }}</span>
                    </div>
                    <div class="info-row">
                        <strong>Work Arrangement:</strong>
                        <span>{{ $job->applicable_area ?: 'TBA' }}</span>
                    </div>
                    <div class="info-row">
                        <strong>Work Location:</strong>
                        <span>{{ $location }}</span>
                    </div>
                    @if(count($workSchedule))
                        <div class="info-row">
                            <strong>Work Schedule:</strong>
                            <span>
                                @foreach($workSchedule as $schedule)
                                    {{ $schedule }}<br>
                                @endforeach
                            </span>
                        </div>
                    @endif
                    @if($job->rest_days)
                        <div class="info-row">
                            <strong>Rest Days:</strong>
                            <span>{{ $job->rest_days }}</span>
                        </div>
                    @endif
                </div>
            </div>

            @if(count($benefits))
                <div class="details-section">
                    <h2>Benefits</h2>
                    <div class="body-text">
                        <ul>
                            @foreach($benefits as $benefit)
                                <li>{{ $benefit }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if(count($requiredDocuments))
                <div class="details-section">
                    <h2>Required Applicant Documents</h2>
                    <div class="body-text">
                        <ul>
                            @foreach($requiredDocuments as $document)
                                <li>{{ $document }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </section>

        <aside class="side-panel">
            <h3>Interested in this role?</h3>
            <p>Review the details and submit your application through the online form.</p>
            <a href="{{ $applyUrl }}" class="apply-btn">Apply Now</a>
            <a href="{{ route('homepage.public') }}#careers" class="outline-btn">View Other Jobs</a>

            <div class="side-list">
                @if($job->job_id)<div><strong>Job ID</strong><span>{{ $job->job_id }}</span></div>@endif
                <div><strong>Status</strong><span>{{ $job->status ?: 'Posted' }}</span></div>
                <div><strong>Vacancies</strong><span>{{ $job->no_of_vacancies ?: 'TBA' }}</span></div>
                <div><strong>Salary</strong><span>{{ $salary ?: 'Competitive' }}</span></div>
                <div><strong>Target Hire</strong><span>{{ $job->target_hire_date ? optional($job->target_hire_date)->format('M d, Y') : 'TBA' }}</span></div>
            </div>
        </aside>
    </div>
</main>

<footer class="footer">
    <div class="footer-inner">
        <span><strong>JK&amp;C Careers Portal</strong></span>
        <span>John Kelly &amp; Company</span>
    </div>
</footer>

<div class="mobile-apply">
    <a href="{{ $applyUrl }}" class="apply-btn">Apply Now</a>
</div>
</body>
</html>
