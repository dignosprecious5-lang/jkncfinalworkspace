<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $job->position_name }} - John Kelly & Company</title>
    
    <link rel="shortcut icon" href="{{ asset('images/jknc_logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background: #f9fafb;
        }

        /* Navigation */
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

        .logo img {
            height: 50px;
            width: auto;
            object-fit: contain;
        }

        .nav-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-btn {
            padding: 0.75rem 1.5rem;
            border: 2px solid #2563eb;
            background: transparent;
            color: #2563eb;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-btn:hover {
            background: #2563eb;
            color: white;
        }

        /* Main Container */
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        main {
            padding: 4rem 2rem;
        }

        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin-bottom: 2rem;
            color: #6b7280;
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: #2563eb;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .breadcrumb a:hover {
            color: #1e40af;
            text-decoration: underline;
        }

        /* Header */
        .job-header {
            background: white;
            border-radius: 16px;
            padding: 3rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #f0f0f0;
        }

        .job-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 1rem;
            line-height: 1.1;
        }

        .job-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .meta-value {
            font-size: 1.125rem;
            font-weight: 700;
            color: #1f2937;
        }

        .status-badge {
            display: inline-block;
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 1rem;
            width: fit-content;
        }

        /* Content Sections */
        .section {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #f0f0f0;
        }

        .section h2 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .section h2::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 24px;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            border-radius: 2px;
        }

        .section-content {
            color: #4b5563;
            line-height: 1.8;
        }

        .section-content p {
            margin-bottom: 1.5rem;
        }

        .section-content ul {
            list-style: none;
            padding-left: 0;
        }

        .section-content li {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }

        .section-content li::before {
            content: '✓';
            display: inline-block;
            color: #2563eb;
            font-weight: 800;
            font-size: 1.25rem;
            flex-shrink: 0;
            margin-left: -1.5rem;
        }

        .section-content strong {
            color: #1f2937;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-apply {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            flex: 1;
            min-width: 200px;
        }

        .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.35);
        }

        .btn-back {
            background: transparent;
            color: #2563eb;
            border: 2px solid #2563eb;
            flex: 1;
            min-width: 200px;
        }

        .btn-back:hover {
            background: #2563eb;
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-container {
                padding: 0 1rem;
                height: 70px;
            }

            .logo img {
                height: 40px;
            }

            main {
                padding: 2rem 1rem;
            }

            .job-header {
                padding: 2rem;
            }

            .job-header h1 {
                font-size: 1.75rem;
            }

            .section {
                padding: 1.5rem;
            }

            .section h2 {
                font-size: 1.25rem;
            }

            .job-meta {
                gap: 1.5rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        /* Icon Styles */
        .icon {
            width: 20px;
            height: 20px;
            display: inline-block;
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav>
        <div class="nav-container">
            <div class="logo">
                <a href="{{ route('homepage.public') }}">
                    <img src="{{ asset('images/jknc_logo.png') }}" alt="JKNC Logo">
                </a>
            </div>
            <div class="nav-actions">
                <a href="{{ route('homepage.public') }}" class="nav-btn">
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back to Careers
                </a>
            </div>
        </div>
    </nav>

    <main>
        <div class="container">
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="{{ route('homepage.public') }}">Careers</a>
                <span>/</span>
                <span>{{ $job->position_name }}</span>
            </div>

            <!-- Job Header -->
            <div class="job-header">
                <span class="status-badge">{{ $job->status }}</span>
                <h1>{{ $job->position_name }}</h1>
                
                <div class="job-meta">
                    @if($job->applicable_area)
                    <div class="meta-item">
                        <span class="meta-label">Location</span>
                        <span class="meta-value">{{ $job->applicable_area }}</span>
                    </div>
                    @endif

                    @if($job->employment_type)
                    <div class="meta-item">
                        <span class="meta-label">Employment Type</span>
                        <span class="meta-value">{{ $job->employment_type }}</span>
                    </div>
                    @endif

                    @if($job->date_needed)
                    <div class="meta-item">
                        <span class="meta-label">Start Date</span>
                        <span class="meta-value">{{ $job->date_needed->format('M d, Y') }}</span>
                    </div>
                    @endif

                    @if($job->salaryGrade)
                    <div class="meta-item">
                        <span class="meta-label">Salary Grade</span>
                        <span class="meta-value">{{ $job->salaryGrade->salary_grade_name ?? 'Competitive' }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Job Description -->
            @if($job->job_description)
            <div class="section">
                <h2>Job Description</h2>
                <div class="section-content">
                    {!! nl2br(e($job->job_description)) !!}
                </div>
            </div>
            @endif

            <!-- Responsibilities -->
            @if($job->job_responsibilities)
            <div class="section">
                <h2>Key Responsibilities</h2>
                <div class="section-content">
                    {!! nl2br(e($job->job_responsibilities)) !!}
                </div>
            </div>
            @endif

            <!-- Requirements -->
            @if($job->job_requirements)
            <div class="section">
                <h2>Requirements</h2>
                <div class="section-content">
                    {!! nl2br(e($job->job_requirements)) !!}
                </div>
            </div>
            @endif

            <!-- Qualifications -->
            @if($job->job_qualifications)
            <div class="section">
                <h2>Qualifications</h2>
                <div class="section-content">
                    {!! nl2br(e($job->job_qualifications)) !!}
                </div>
            </div>
            @endif

            <!-- Benefits -->
            @if($job->benefits_package && is_array($job->benefits_package))
            <div class="section">
                <h2>Benefits Package</h2>
                <div class="section-content">
                    <ul>
                        @foreach($job->benefits_package as $benefit)
                            @if(is_string($benefit))
                                <li>{{ $benefit }}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            <!-- Work Schedule -->
            @if($job->work_schedule && is_array($job->work_schedule))
            <div class="section">
                <h2>Work Schedule</h2>
                <div class="section-content">
                    <ul>
                        @foreach($job->work_schedule as $schedule)
                            @if(is_string($schedule))
                                <li>{{ $schedule }}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            <!-- Additional Information -->
            <div class="section">
                <h2>Additional Information</h2>
                <div class="section-content">
                    @if($job->departmentRecord)
                        <p><strong>Department:</strong> {{ $job->departmentRecord->department_name ?? 'N/A' }}</p>
                    @endif
                    
                    @if($job->office)
                        <p><strong>Office:</strong> {{ $job->office->office_name ?? 'N/A' }}</p>
                    @endif

                    @if($job->posting_start_date)
                        <p><strong>Posting Start Date:</strong> {{ $job->posting_start_date->format('M d, Y') }}</p>
                    @endif

                    @if($job->target_hire_date)
                        <p><strong>Target Hire Date:</strong> {{ $job->target_hire_date->format('M d, Y') }}</p>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="{{ route('careers.apply', ['job_id' => $job->id]) }}" class="btn btn-apply">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                        Apply Now
                    </a>
                    <a href="{{ route('homepage.public') }}" class="btn btn-back">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Back to Careers
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Optional: Add scroll animations
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.section');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animation = 'fadeIn 0.6s ease-out';
                    }
                });
            }, { threshold: 0.1 });

            sections.forEach(section => observer.observe(section));
        });

        // Add fade-in animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
