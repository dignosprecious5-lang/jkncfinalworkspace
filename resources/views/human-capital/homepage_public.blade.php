<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>John Kelly & Company - Build Your Career</title>
    
    <meta name="og:title" content="John Kelly & Company">
    <meta name="og:image" content="{{ asset('images/jknc_logo.png') }}">
    <meta name="og:url" content="{{ config('app.url') }}">
    <meta name="og:description" content="Build your career with John Kelly & Company (JKNC). Join a consulting firm that empowers businesses and transforms visions across industries.">
    <meta name="description" content="Build your career with John Kelly & Company (JKNC). Join a consulting firm that empowers businesses and transforms visions across industries.">
    <meta name="author" content="JKNC">

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
            overflow-x: hidden;
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

        .nav-menu {
            display: flex;
            gap: 3rem;
            align-items: center;
        }

        .nav-menu a {
            font-weight: 500;
            color: #374151;
            text-decoration: none;
            transition: color 0.3s ease;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nav-menu a:hover {
            color: #2563eb;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.7) 0%, rgba(15, 23, 42, 0.5) 100%), 
                        url('{{ asset("images/bg_cover.png") }}') center/cover no-repeat;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 2rem;
            text-align: center;
            color: white;
        }

        .hero-content h1 {
            font-size: clamp(2.5rem, 8vw, 4.5rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            letter-spacing: -0.02em;
        }

        .hero-content p {
            font-size: clamp(1rem, 2.5vw, 1.25rem);
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.95);
            font-weight: 300;
            margin-bottom: 0;
        }

        /* Sections */
        section {
            padding: 5rem 2rem;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
        }

        .section-title {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 1rem;
            letter-spacing: -0.01em;
        }

        .section-subtitle {
            font-size: 1.125rem;
            color: #6b7280;
            margin-bottom: 4rem;
            font-weight: 400;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .divider {
            width: 60px;
            height: 4px;
            background: linear-gradient(to right, #2563eb, #1e40af);
            margin: 1.5rem auto 0;
            border-radius: 2px;
        }

        /* Careers Section */
        #careers {
            background: #ffffff;
        }

        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .job-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #f0f0f0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
        }

        .job-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.15);
            border-color: #e0e7ff;
        }

        .job-badge {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            width: fit-content;
        }

        .job-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.75rem;
        }

        .job-card p {
            color: #6b7280;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }

        .job-location {
            display: flex;
            align-items: center;
            color: #9ca3af;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }

        .job-location svg {
            width: 16px;
            height: 16px;
            margin-right: 0.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-apply {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            width: 100%;
        }

        .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            padding: 1rem 2.5rem;
            font-size: 1rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.35);
        }

        .view-all-btn {
            text-align: center;
        }

        /* Why Join Section */
        #why-join {
            background: #f9fafb;
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2.5rem;
        }

        .benefit-card {
            text-align: center;
            padding: 2rem;
        }

        .benefit-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .benefit-icon svg {
            width: 36px;
            height: 36px;
            color: #1e40af;
        }

        .benefit-card h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.75rem;
        }

        .benefit-card p {
            color: #6b7280;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Footer */
        footer {
            background: #111827;
            color: #d1d5db;
            padding: 4rem 2rem 2rem;
        }

        .footer-content {
            max-width: 1280px;
            margin: 0 auto;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2.5rem;
            margin-bottom: 2rem;
        }

        .footer-section h4 {
            font-size: 1.125rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1.5rem;
        }

        .footer-section p {
            color: #9ca3af;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section li {
            margin-bottom: 0.75rem;
        }

        .footer-section a {
            color: #9ca3af;
            text-decoration: none;
            transition: color 0.3s ease;
            font-size: 0.95rem;
        }

        .footer-section a:hover {
            color: #2563eb;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: #2563eb;
        }

        .social-links svg {
            width: 20px;
            height: 20px;
        }

        .footer-hours {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
            color: #9ca3af;
        }

        .footer-divider {
            border-top: 1px solid #374151;
            margin: 2rem 0;
            padding-top: 2rem;
        }

        .footer-bottom {
            text-align: center;
            color: #9ca3af;
            font-size: 0.9rem;
        }

        /* Scroll to Top */
        .scroll-top {
            position: fixed;
            right: 2rem;
            bottom: 2rem;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 999;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(37, 99, 235, 0.3);
            border: none;
        }

        .scroll-top:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(37, 99, 235, 0.4);
        }

        .scroll-top.show {
            display: flex;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-menu {
                gap: 1.5rem;
                font-size: 0.9rem;
            }

            .nav-container {
                padding: 0 1rem;
                height: 70px;
            }

            .logo img {
                height: 40px;
            }

            section {
                padding: 3rem 1rem;
            }

            .hero-content h1 {
                margin-bottom: 1rem;
            }

            .hero-content p {
                line-height: 1.6;
            }

            .jobs-grid {
                gap: 1.5rem;
            }

            .benefits-grid {
                gap: 1.5rem;
            }

            .footer-grid {
                gap: 2rem;
            }
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
            <div class="nav-menu">
                <a href="#careers">Careers</a>
            </div>
        </div>
    </nav>



    <!-- Careers Section -->
    <section id="careers">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Start Your Career With Us</h2>
                <div class="divider"></div>
                <p class="section-subtitle">Explore our current job openings and find the perfect opportunity for your career growth</p>
            </div>

            <div class="jobs-grid">
                @forelse($jobPostings as $job)
                <div class="job-card">
                    <span class="job-badge">{{ $job->status }}</span>
                    <h3>{{ $job->position_name }}</h3>
                    <p>{{ Str::limit($job->job_description, 150) }}</p>
                    <div class="job-location">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        </svg>
                        {{ $job->applicable_area ?? 'Location TBA' }}
                    </div>
                    <a href="{{ route('careers.job-detail', ['id' => $job->id]) }}" class="btn btn-apply">Apply Now</a>
                </div>
                @empty
                <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: #6b7280;">
                    <p style="font-size: 1.125rem; margin-bottom: 1rem;">No job openings available at this time.</p>
                    <p>Please check back soon for new opportunities!</p>
                </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Why Join Section -->
    <section id="why-join">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Why Join JKNC?</h2>
                <div class="divider"></div>
            </div>

            <div class="benefits-grid">
                <!-- Benefit 1 -->
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3>Career Growth</h3>
                    <p>Continuous learning and development opportunities to advance your professional career and acquire new skills.</p>
                </div>

                <!-- Benefit 2 -->
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10h.01M11 10h.01M7 10h.01M6 20h12a2 2 0 002-2V8a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3>Collaborative Culture</h3>
                    <p>Work with talented professionals in a supportive team environment that values diversity and inclusion.</p>
                </div>

                <!-- Benefit 3 -->
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3>Competitive Benefits</h3>
                    <p>Comprehensive compensation package, health insurance, and employee benefits to support your wellbeing.</p>
                </div>

                <!-- Benefit 4 -->
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.825-8.82-2.267m19.5-6.75A23.933 23.933 0 0112 3c-3.183 0-6.22.825-8.82 2.267m19.5 0a23.9 23.9 0 010 11.514m-9.38 6.173a23.903 23.903 0 0017.6-4.746m0-6.514a23.9 23.9 0 00-17.6-4.746"></path>
                        </svg>
                    </div>
                    <h3>Global Opportunities</h3>
                    <p>Work on diverse projects across industries and regions, gaining international exposure and experience.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-grid">
                <!-- Company Info -->
                <div class="footer-section">
                    <h4>John Kelly & Company</h4>
                    <p>Building careers and empowering businesses across industries through consulting excellence. Join our team and make an impact.</p>
                    <div class="social-links">
                        <a href="#" title="Facebook">
                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"></path></svg>
                        </a>
                        <a href="#" title="Twitter">
                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 002.856-3.515 10 10 0 01-2.856.975 4.958 4.958 0 002.165-2.724c-.951.564-2.005.974-3.127 1.195a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417a9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"></path></svg>
                        </a>
                        <a href="#" title="LinkedIn">
                            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.475-2.236-1.986-2.236-1.081 0-1.722.722-2.004 1.418-.103.249-.129.597-.129.946v5.441h-3.554s.05-8.824 0-9.745h3.554v1.378c-.009.015-.021.029-.033.042h.033v-.042c.43-.646 1.199-1.554 2.918-1.554 2.134 0 3.732 1.39 3.732 4.377v5.544zM5.337 8.855c-1.144 0-1.915-.762-1.915-1.715 0-.953.77-1.715 1.958-1.715 1.187 0 1.914.762 1.939 1.715 0 .953-.752 1.715-1.982 1.715zm1.581 11.597H3.635V9.562h3.283v10.89zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.225 0z"></path></svg>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="footer-section">
                    <h4>Company</h4>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Our Services</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">News & Blog</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>

                <!-- Resources -->
                <div class="footer-section">
                    <h4>Resources</h4>
                    <ul>
                        <li><a href="#">Support Center</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms & Conditions</a></li>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Sitemap</a></li>
                    </ul>
                </div>

                <!-- Business Hours -->
                <div class="footer-section">
                    <h4>Business Hours</h4>
                    <p><strong style="color: white;">Monday - Friday:</strong> 9:00 AM - 5:00 PM</p>
                    <p><strong style="color: white;">Saturday:</strong> 10:00 AM - 3:00 PM</p>
                    <p><strong style="color: white;">Sunday:</strong> Closed</p>
                </div>
            </div>

            <div class="footer-divider">
                <div class="footer-bottom">
                    <p>&copy; 2025 John Kelly & Company (JKNC). All rights reserved. | Designed with care for your career growth.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button class="scroll-top" id="scrollTop">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
        </svg>
    </button>

    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href !== '#') {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            });
        });

        // Scroll to top button functionality
        const scrollTopBtn = document.getElementById('scrollTop');
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollTopBtn.classList.add('show');
            } else {
                scrollTopBtn.classList.remove('show');
            }
        });

        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>
