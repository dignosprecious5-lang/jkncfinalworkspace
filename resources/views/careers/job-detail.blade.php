<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $job->position ?: 'Job Opening' }} | John Kelly &amp; Company</title>

    <link rel="shortcut icon" href="{{ asset('images/jknc_logo.png') }}">
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
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, .97);
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 8px rgba(15, 23, 42, .04);
        }

        .nav {
            width: min(1120px, calc(100% - 32px));
            min-height: 72px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        .brand img {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }

        .brand-text strong {
            display: block;
            color: var(--ink);
            font-size: 15px;
            font-weight: 900;
            letter-spacing: -.03em;
            line-height: 1.1;
        }

        .brand-text span {
            display: block;
            margin-top: 3px;
            color: var(--brand);
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .22em;
            text-transform: uppercase;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 800;
        }

        .nav-actions a:first-child {
            color: #334155;
        }

        .nav-actions a:first-child:hover {
            color: var(--brand);
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

        .subnav {
            border-bottom: 1px solid #e5e7eb;
            background: #fff;
        }

        .subnav-inner {
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 12px 0;
            color: #334155;
            font-size: 13px;
            font-weight: 800;
            overflow-x: auto;
        }

        .subnav-links {
            display: flex;
            align-items: center;
            gap: 22px;
            white-space: nowrap;
        }

        .subnav-links .active {
            color: var(--brand);
            border-bottom: 2px solid var(--brand);
            padding-bottom: 4px;
        }

        .search-jobs { color: var(--brand); white-space: nowrap; }

        .page {
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
            padding: 28px 0 70px;
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
            grid-template-columns: minmax(0, 1fr) 280px;
            gap: 38px;
            align-items: start;
        }

        .main-content { max-width: 760px; }

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

        .job-company-logo {
            width: 118px;
            height: 78px;
            object-fit: contain;
            object-position: left center;
            margin-bottom: 16px;
        }

        .job-title {
            color: #020617;
            font-size: clamp(27px, 4vw, 38px);
            line-height: 1.08;
            font-weight: 900;
            letter-spacing: -.045em;
            max-width: 720px;
            margin-bottom: 13px;
        }

        .job-highlights {
            display: grid;
            gap: 7px;
            margin: 12px 0 12px;
            color: #334155;
            font-size: 15px;
        }

        .highlight-row {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            flex: 0 0 auto;
        }

        .dot-blue { background: var(--brand); }
        .dot-sky { background: #38bdf8; }
        .dot-green { background: #16a34a; }

        .company-name {
            margin: 12px 0 8px;
            color: var(--brand);
            font-size: 17px;
            font-weight: 900;
        }

        .badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 17px;
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

        .location-line {
            display: flex;
            gap: 8px;
            align-items: start;
            color: #111827;
            font-size: 15px;
            margin: 14px 0 18px;
        }

        .posted-line {
            color: #334155;
            font-size: 14px;
            margin-bottom: 22px;
        }

        .posted-line strong { color: #111827; }

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
            margin-top: 42px;
            margin-bottom: 36px;
        }

        .details-section h2,
        .details-section h3 {
            color: #020617;
            font-weight: 900;
            letter-spacing: -.025em;
        }

        .details-section h2 {
            font-size: 22px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 9px;
            margin-bottom: 22px;
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
            gap: 9px 34px;
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
            border-left: 1px solid #e5e7eb;
            padding-left: 26px;
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
            .side-panel { position: static; border-left: 0; border-top: 1px solid #e5e7eb; padding-left: 0; padding-top: 24px; }
            .main-content { max-width: none; }
        }

        @media (max-width: 640px) {
            .brand-text { display: none; }
            .nav { min-height: 64px; }
            .nav-actions a:first-child { display: none; }
            .subnav-links { gap: 14px; }
            .page { width: calc(100% - 24px); }
            .job-company-logo { width: 92px; height: 62px; }
            .info-grid { grid-template-columns: 1fr; }
            .info-row { grid-template-columns: 105px minmax(0, 1fr); }
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
            .topbar, .subnav, .side-panel, .mobile-apply, .footer, .back-btn { display: none !important; }
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
        $min = $job->min_salary_offer ? '₱' . number_format((float) $job->min_salary_offer, 0) : null;
        $max = $job->max_salary_offer ? '₱' . number_format((float) $job->max_salary_offer, 0) : null;
        $salary = trim(($min ?: '') . (($min && $max) ? ' - ' : '') . ($max ?: ''));
    }

    $benefits = is_array($job->benefits_package) ? array_values(array_filter($job->benefits_package)) : [];
    $workSchedule = is_array($job->work_schedule) ? array_values(array_filter($job->work_schedule)) : [];
    $applyUrl = route('careers.apply') . '?job_id=' . $job->id;
@endphp

<header class="topbar">
    <div class="nav">
        <a href="{{ route('homepage.public') }}" class="brand">
            <img src="{{ asset('images/jknc_logo.png') }}" onerror="this.src='{{ asset('images/imaglogo.png') }}'" alt="John Kelly &amp; Company Logo">
            <div class="brand-text">
                <strong>John Kelly &amp; Company</strong>
                <span>Careers Portal</span>
            </div>
        </a>
        <div class="nav-actions">
            <a href="{{ route('homepage.public') }}#openings">Search Jobs</a>
            <a href="{{ route('careers.apply') }}" class="outline-btn">Submit Application</a>
        </div>
    </div>
</header>

<div class="subnav">
    <div class="subnav-inner">
        <div class="subnav-links">
            <span class="active">Job Details</span>
            <span>{{ $job->employment_type ?: 'Open Role' }}</span>
            <span>{{ $department }}</span>
        </div>
        <a href="{{ route('homepage.public') }}#openings" class="search-jobs">⌕ Search Jobs</a>
    </div>
</div>

<main class="page">
    <a href="{{ route('homepage.public') }}#openings" class="back-btn">‹ Back</a>

    <div class="breadcrumb">
        <a href="{{ route('homepage.public') }}#openings">Careers</a>
        <span>/</span>
        <span>{{ $title }}</span>
    </div>

    <div class="job-layout">
        <section class="main-content">

            <img src="{{ asset('images/jknc_logo.png') }}" onerror="this.src='{{ asset('images/imaglogo.png') }}'" alt="John Kelly &amp; Company" class="job-company-logo">

            <h1 class="job-title">{{ $title }}</h1>

            <div class="job-highlights">
                @if($job->no_of_vacancies)
                    <div class="highlight-row"><span class="dot dot-blue"></span>{{ $job->no_of_vacancies }} Open {{ ((int) $job->no_of_vacancies) > 1 ? 'Positions' : 'Position' }}</div>
                @endif
                @if($job->employment_type)
                    <div class="highlight-row"><span class="dot dot-sky"></span>{{ $job->employment_type }}</div>
                @endif
                <div class="highlight-row"><span class="dot dot-green"></span>Structured role with practical business experience</div>
            </div>

            <div class="company-name">John Kelly &amp; Company</div>

            <div class="badges">
                @if($job->job_id)<span class="badge">Job ID: {{ $job->job_id }}</span>@endif
                <span class="badge">Posted: {{ $postedDate }}</span>
                @if($salary)<span class="badge">{{ $salary }}</span>@endif
            </div>

            <div class="location-line">📍 <span>{{ $location }}</span></div>

            <div class="posted-line">
                <div>Posted On: <strong>{{ $postedDate }}</strong></div>
                @if($job->job_id)<div>Job ID: {{ $job->job_id }}</div>@endif
            </div>

            <a href="{{ $applyUrl }}" class="apply-btn">Apply Now ↗</a>

            <div class="details-section">
                <h2>Details</h2>
                <div class="body-text">
                    <p><strong>John Kelly &amp; Company</strong> is looking for a qualified candidate for the <strong>{{ $title }}</strong> position.</p>
                    <p>{!! nl2br(e($description)) !!}</p>
                </div>
            </div>

            @if($job->duties_responsibilities && $job->duties_responsibilities !== $job->job_description)
                <div class="details-section">
                    <h3>Responsibilities</h3>
                    <div class="body-text">{!! nl2br(e($job->duties_responsibilities)) !!}</div>
                </div>
            @endif

            @if($requirements || $job->experience_req || $job->skills_req || $job->licenses_req || $job->preferred_qualifications)
                <div class="details-section">
                    <h3>Qualifications</h3>
                    <div class="body-text">
                        <ul>
                            @if($requirements)<li>{{ $requirements }}</li>@endif
                            @if($job->experience_req)<li>{{ $job->experience_req }}</li>@endif
                            @if($job->skills_req)<li>{{ $job->skills_req }}</li>@endif
                            @if($job->licenses_req)<li>{{ $job->licenses_req }}</li>@endif
                            @if($job->preferred_qualifications)<li>{{ $job->preferred_qualifications }}</li>@endif
                        </ul>
                    </div>
                </div>
            @endif

            <div class="details-section">
                <h3>Job Information</h3>
                <div class="info-grid">
                    <div class="info-row"><strong>Location:</strong><span>{{ $location }}</span></div>
                    <div class="info-row"><strong>Department:</strong><span>{{ $department }}</span></div>
                    <div class="info-row"><strong>Type:</strong><span>{{ $job->employment_type ?: 'TBA' }}</span></div>
                    <div class="info-row"><strong>Vacancies:</strong><span>{{ $job->no_of_vacancies ?: '—' }}</span></div>
                    <div class="info-row"><strong>Salary:</strong><span>{{ $salary ?: 'Competitive' }}</span></div>
                    <div class="info-row"><strong>Target Hire:</strong><span>{{ $job->target_hire_date ? optional($job->target_hire_date)->format('M d, Y') : '—' }}</span></div>
                </div>
            </div>

            @if(count($benefits))
                <div class="details-section">
                    <h3>What’s in it for you?</h3>
                    <div class="body-text">
                        <ul>
                            @foreach($benefits as $benefit)
                                <li>{{ $benefit }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if(count($workSchedule) || $job->rest_days)
                <div class="details-section">
                    <h3>Work Schedule</h3>
                    <div class="body-text">
                        @if(count($workSchedule))
                            <ul>
                                @foreach($workSchedule as $schedule)
                                    <li>{{ $schedule }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @if($job->rest_days)<p><strong>Rest Days:</strong> {{ $job->rest_days }}</p>@endif
                    </div>
                </div>
            @endif

            <div class="details-section">
                <a href="{{ $applyUrl }}" class="apply-btn">Apply Now ↗</a>
            </div>
        </section>

        <aside class="side-panel">
            <h3>Interested in this role?</h3>
            <p>Review the details and submit your application through the online form.</p>
            <a href="{{ $applyUrl }}" class="apply-btn">Apply Now</a>
            <a href="{{ route('homepage.public') }}#openings" class="outline-btn">View Other Jobs</a>

            <div class="side-list">
                @if($job->job_id)<div><strong>Job ID</strong><span>{{ $job->job_id }}</span></div>@endif
                <div><strong>Status</strong><span>{{ $job->status ?: 'Posted' }}</span></div>
                <div><strong>Vacancies</strong><span>{{ $job->no_of_vacancies ?: '—' }}</span></div>
                <div><strong>Salary</strong><span>{{ $salary ?: 'Competitive' }}</span></div>
                <div><strong>Target Hire</strong><span>{{ $job->target_hire_date ? optional($job->target_hire_date)->format('M d, Y') : '—' }}</span></div>
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
    <a href="{{ $applyUrl }}" class="apply-btn">Apply Now ↗</a>
</div>
</body>
</html>
