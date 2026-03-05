<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Payroll, attendance & site operations for construction, farms & events – with USSD & M-Pesa payouts.">
    <meta name="theme-color" content="#1e1b4b">
    
    <!-- Open Graph / SEO -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="SiteGrid - Workforce, payroll & site operations">
    <meta property="og:description" content="Manage onsite teams – attendance, resource tracking, and secure mobile payouts via USSD & M-Pesa.">
    <meta property="og:image" content="{{ asset('images/og-hero.png') }}">
    
    <!-- Twitter Card -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url('/') }}">
    <meta property="twitter:title" content="SiteGrid - Payroll & Attendance for Construction Sites">
    <meta property="twitter:description" content="Capture attendance, compute weekly pay, and pay workers using M-Pesa – with USSD for feature phones.">
    <meta property="twitter:image" content="{{ asset('images/og-hero.png') }}">
    
    <!-- Preload hero image -->
    <link rel="preload" as="image" href="{{ asset('images/hero-illustration.jpg') }}">
    
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.ico') }}">
    <title>SiteGrid - Workforce, payroll & site operations for construction, farms & events</title>
    
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* SiteGrid Brand Colors */
        :root {
            --primary-indigo: #1e1b4b;
            --accent-orange: #f97316;
            --accent-orange-dark: #ea580c;
        }
        
        /* Utility classes */
        .bg-accent {
            background-color: var(--accent-orange) !important;
        }
        .border-accent {
            border-color: var(--accent-orange) !important;
        }
        .text-accent {
            color: var(--accent-orange) !important;
        }
        
        /* Smooth scroll with header offset */
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 80px; /* matches sticky header height */
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            color: #374151;
        }

        /* Typography */
        h1, h2, h3 {
            color: var(--primary-indigo);
            font-weight: 700;
        }

        /* Custom Button Styles */
        .btn-primary {
            background-color: var(--accent-orange);
            border-color: var(--accent-orange);
            color: white;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease-out;
        }

        .btn-primary:hover,
        .btn-primary:focus-visible {
            background-color: var(--accent-orange-dark);
            border-color: var(--accent-orange-dark);
            color: white;
            transform: scale(1.05);
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.2);
            outline: 2px solid var(--accent-orange);
            outline-offset: 2px;
        }

        .btn-secondary {
            border: 2px solid var(--primary-indigo);
            color: var(--primary-indigo);
            background-color: transparent;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease-out;
            display: inline-block;
            text-align: center;
            text-decoration: none;
        }

        .btn-secondary:hover,
        .btn-secondary:focus-visible {
            background-color: #f5f3ff;
            text-decoration: none;
            color: var(--primary-indigo);
            outline: 2px solid var(--primary-indigo);
            outline-offset: 2px;
        }

        /* Header */
        header {
            position: sticky;
            top: 0;
            z-index: 50;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-brand img {
            height: 3rem; /* reduced from 5.5rem */
            width: auto;
            transition: height 0.2s;
        }

        @media (max-width: 768px) {
            .navbar-brand img {
                height: 2.5rem; /* reduced from 3.75rem */
            }
        }

        .logo-icon {
            width: 2rem;
            height: 2rem;
            background-color: var(--primary-indigo);
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.125rem;
        }

        .logo-text {
            font-size: 1.25rem;
            font-weight: bold;
            color: var(--primary-indigo);
        }

        nav a {
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        nav a:hover {
            color: var(--primary-indigo);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(to bottom right, #e0e7ff, #fff, #fed7aa);
            padding: 4rem 0;
        }

        .hero h1 {
            font-size: 2.25rem;
            line-height: 1.2;
            margin-bottom: 1.5rem;
        }

        @media (min-width: 992px) {
            .hero h1 {
                font-size: 3.75rem;
            }
        }

        .hero p {
            font-size: 1.125rem;
            color: #4b5563;
            line-height: 1.75;
            margin-bottom: 2rem;
        }

        .trust-line {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 1.5rem;
        }

        .trust-icon {
            width: 1.25rem;
            height: 1.25rem;
            color: var(--accent-orange);
        }

        .hero-image {
            border-radius: 0.5rem;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
            object-fit: cover;
            width: 100%;
            height: auto;
        }

        /* Feature Cards */
        .feature-card {
            text-align: center;
            padding: 1.5rem;
            transition: all 0.3s ease;
            border-radius: 0.5rem;
        }

        .feature-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transform: scale(1.05);
        }

        .feature-icon {
            width: 2rem;
            height: 2rem;
            background-color: #fed7aa;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: #ea580c;
        }

        .feature-card h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        /* How It Works Section */
        .how-it-works {
            background-color: #f0f4ff;
            padding: 4rem 0;
        }

        .step-card {
            background-color: white;
            padding: 2rem;
            border-radius: 0.5rem;
            border-left: 4px solid var(--accent-orange);
        }

        .step-circle {
            width: 3rem;
            height: 3rem;
            background-color: var(--accent-orange);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        /* Pricing Section */
        .pricing-card {
            background: linear-gradient(to bottom right, #f0f4ff, #fff, #fed7aa);
            border: 2px solid #e0e7ff;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .price-amount {
            font-size: 3rem;
            font-weight: bold;
            color: var(--primary-indigo);
            margin: 1rem 0;
        }

        .price-currency {
            color: var(--accent-orange);
            font-size: 2.25rem;
        }

        .offer-badge {
            background-color: #fed7aa;
            border: 1px solid #f59e0b;
            border-radius: 0.5rem;
            padding: 1rem;
            margin: 1.5rem 0;
            color: #92400e;
            font-weight: 600;
        }

        .pricing-features {
            list-style: none;
            padding: 0;
            margin: 1.5rem 0;
            text-align: left;
        }

        .pricing-features li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #374151;
        }

        .pricing-features svg {
            width: 1.25rem;
            height: 1.25rem;
            color: #10b981;
        }

        /* Testimonials */
        .testimonial-item {
            background-color: #f3f4f6;
            padding: 1.5rem;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }

        .testimonial-stars {
            color: #fbbf24;
            margin-bottom: 1rem;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .author-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background-color: #dbeafe;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--primary-indigo);
            text-transform: uppercase;
        }

        .author-info h5 {
            margin: 0;
            font-size: 0.875rem;
        }

        .author-info p {
            margin: 0;
            font-size: 0.75rem;
            color: #6b7280;
        }

        .pilot-cta {
            background-color: white;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
        }

        /* FAQ Section */
        .faq-item {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.2s;
        }

        .faq-item:hover {
            border-color: var(--accent-orange);
        }

        .faq-item summary {
            cursor: pointer;
            font-weight: 600;
            color: var(--primary-indigo);
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }

        .faq-item summary svg {
            width: 1.25rem;
            height: 1.25rem;
            color: #6b7280;
            transition: transform 0.2s;
        }

        .faq-item[open] summary svg {
            transform: rotate(180deg);
        }

        .faq-item p {
            margin-top: 1rem;
            color: #374151;
        }

        /* Contact Section */
        .contact-section {
            background-color: var(--primary-indigo);
            color: white;
            padding: 3rem 0;
        }

        .contact-section h2 {
            color: white;
            margin-bottom: 1rem;
        }

        .contact-section p {
            color: #e0e7ff;
            margin-bottom: 2rem;
        }

        .contact-box {
            background-color: #312e81;
            padding: 2rem;
            border-radius: 0.5rem;
        }

        .contact-box h3 {
            color: white;
            margin-bottom: 1.5rem;
        }

        /* Dark form inputs */
        .form-control-dark {
            background-color: #4c1d95;
            border: 1px solid #6366f1;
            color: white;
            border-radius: 0.375rem;
            padding: 0.5rem;
        }

        .form-control-dark::placeholder {
            color: #a5b4fc;
        }

        .form-control-dark:focus {
            outline: none;
            border-color: var(--accent-orange);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
            background-color: #4c1d95;
        }

        .form-label {
            color: #e0e7ff;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        /* Footer */
        footer {
            background-color: #111827;
            color: #d1d5db;
            padding: 3rem 0 1rem;
            border-top: 1px solid #1f2937;
        }

        footer h4 {
            color: white;
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        footer a {
            color: #d1d5db;
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }

        footer a:hover {
            color: white;
        }

        footer ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        footer ul li {
            margin-bottom: 0.5rem;
        }

        .footer-bottom {
            border-top: 1px solid #1f2937;
            padding-top: 2rem;
            margin-top: 2rem;
            text-align: center;
            font-size: 0.875rem;
            color: #9ca3af;
        }

        /* Toast Notifications */
        #successToast {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
            background-color: #10b981;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            z-index: 1060;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        #successToast.hidden {
            display: none !important;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar-collapse {
                margin-top: 0.75rem;
                padding-top: 0.75rem;
                border-top: 1px solid #e5e7eb;
            }

            .navbar .btn-primary {
                width: 100%;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .hero-image {
                display: none;
            }

            .price-amount {
                font-size: 2.25rem;
            }

            .contact-box {
                margin-bottom: 1.5rem;
            }
        }

        /* Focus outline for accessibility */
        :focus-visible {
            outline: 2px solid var(--accent-orange);
            outline-offset: 2px;
        }
    </style>
</head>
<body class="bg-white text-gray-900 font-sans">
    <!-- Header / Navigation -->
    <header class="border-bottom">
        <div class="container-lg py-2">
            <nav class="navbar navbar-expand-md p-0">
                <!-- Logo -->
                <a href="{{ url('/') }}" class="navbar-brand logo m-0">
                    <img src="{{ asset('images/logo.png') }}" alt="SiteGrid Logo">
                </a>

                <!-- Mobile Toggle -->
                <button class="navbar-toggler border-0 shadow-none px-2" type="button" data-bs-toggle="collapse" data-bs-target="#landingNav" aria-controls="landingNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="landingNav">
                    <!-- Navigation Links -->
                    <div class="navbar-nav ms-md-auto me-md-4 gap-md-3">
                        <a href="#how-it-works" class="nav-link text-dark">How it works</a>
                        <a href="#pricing" class="nav-link text-dark">Pricing</a>
                        <a href="#faq" class="nav-link text-dark">FAQ</a>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="d-flex flex-column flex-md-row gap-2 align-items-md-center">
                        <a href="{{ route('login') }}" class="text-dark text-decoration-none btn btn-link text-start px-0 px-md-2">Sign in</a>
                        <a href="{{ route('register') }}" class="btn btn-primary">Create a site</a>
                    </div>
                </div>
            </nav>
        </div>
    </header>
    
    <!-- Main Content -->
    <main>
        <!-- Hero Section -->
        <section class="hero py-5 py-lg-6">
            <div class="container-lg">
                <div class="row align-items-center g-4 g-lg-5">
                    <!-- Hero Content -->
                    <div class="col-12 col-lg-6 fade-in-up">
                        <h1>Payroll, attendance & site operations for every on-site workforce</h1>
                        
                        <p class="fs-6 text-muted mt-3">
                            Manage sites, workers and resources across construction, agriculture, events and small industry - capture attendance, compute periodic pay, and disburse payouts (including feature-phone support via USSD).
                        </p>
                        
                        <div class="d-flex gap-2 align-items-center mt-3 flex-wrap">
                            <span class="badge bg-light text-dark">Construction</span>
                            <span class="badge bg-light text-dark">Agriculture</span>
                            <span class="badge bg-light text-dark">Events</span>
                            <span class="badge bg-light text-dark">Manufacturing</span>
                        </div>
                        
                        <!-- CTAs -->
                        <div class="d-flex flex-column flex-sm-row gap-2 mb-3 mt-4">
                            <a href="{{ route('register') }}" class="btn btn-primary">
                                Create a site - it's free to try
                            </a>
                            <a href="#contact" class="btn btn-secondary">
                                Request a demo
                            </a>
                        </div>
                        
                        <!-- Micro-trust line -->
                        <div class="trust-line">
                            <svg class="trust-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Trusted by small contractors - pilot-ready. No bank account required.
                        </div>
                    </div>
                    
                    <!-- Hero Image (static, no rotation) -->
                    <div class="col-12 col-lg-6 d-none d-lg-block">
                        <img id="heroImage" src="{{ asset('images/hero-illustration.jpg') }}" alt="Team managing workers, materials and payouts across multiple site types" class="hero-image" width="600" height="400" loading="eager">
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Feature Strip (3 Columns) -->
        <section class="py-4 bg-body-secondary border-top border-bottom">
            <div class="container-lg">
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <!-- Feature 1: Attendance -->
                    <div class="col text-center">
                        <div class="mb-3">
                            <div class="d-inline-flex align-items-center justify-content-center p-2 bg-light rounded">
                                <svg class="feature-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <h3 class="h5 text-dark mb-2">Attendance & time capture</h3>
                        <p class="text-muted">Foreman logs in via web, workers check in with USSD – no smartphone needed.</p>
                    </div>
                    
                    <!-- Feature 2: Flexible Pay Cycles -->
                    <div class="col text-center">
                        <div class="mb-3">
                            <div class="d-inline-flex align-items-center justify-content-center p-2 bg-light rounded">
                                <svg class="feature-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <h3 class="h5 text-dark mb-2">Flexible pay cycles & payouts</h3>
                        <p class="text-muted">Daily, weekly or monthly with M-Pesa – reduce payroll disputes.</p>
                    </div>
                    
                    <!-- Feature 3: Works Everywhere -->
                    <div class="col text-center">
                        <div class="mb-3">
                            <div class="d-inline-flex align-items-center justify-content-center p-2 bg-light rounded">
                                <svg class="feature-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 14h8m-8-4h8m-8-4h8M5 20h14a2 2 0 002-2V4a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                        <h3 class="h5 text-dark mb-2">Works on smartphones & feature phones</h3>
                        <p class="text-muted">USSD support for workers without smartphones – inclusive by design.</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Use Cases Grid -->
        <section class="py-5 py-lg-6 bg-white">
            <div class="container-lg">
                <div class="text-center mb-5">
                    <h2 class="h3 text-dark mb-3">Works across all sectors</h2>
                </div>
                
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                    <!-- Construction -->
                    <div class="col">
                        <div class="text-center p-3">
                            <h4 class="h6 text-dark mb-2">Construction Sites</h4>
                            <p class="small text-muted">Manage daily labor, equipment, and material costs – reduce payroll errors.</p>
                        </div>
                    </div>
                    
                    <!-- Agriculture -->
                    <div class="col">
                        <div class="text-center p-3">
                            <h4 class="h6 text-dark mb-2">Farms & Plantations</h4>
                            <p class="small text-muted">Track seasonal workers and harvest payouts – no more lost timesheets.</p>
                        </div>
                    </div>
                    
                    <!-- Events -->
                    <div class="col">
                        <div class="text-center p-3">
                            <h4 class="h6 text-dark mb-2">Event Staffing</h4>
                            <p class="small text-muted">Quick onboarding for crew, fast end-of-event payroll – even for one‑day hires.</p>
                        </div>
                    </div>
                    
                    <!-- Manufacturing -->
                    <div class="col">
                        <div class="text-center p-3">
                            <h4 class="h6 text-dark mb-2">Small Manufacturing</h4>
                            <p class="small text-muted">Daily or shift-based payroll and resource tracking – keep production moving.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- How It Works (3 Steps) -->
        <section id="how-it-works" class="py-5 py-lg-6 bg-light">
            <div class="container-lg">
                <div class="text-center mb-5">
                    <h2 class="h2 text-dark mb-3">How it works</h2>
                    <p class="fs-6 text-muted">Simple three-step process to get started</p>
                </div>
                
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <!-- Step 1 -->
                    <div class="col">
                        <div class="bg-white p-4 rounded border-start border-accent">
                            <div class="mb-3">
                                <span class="badge bg-accent text-dark fs-6">1</span>
                            </div>
                            <h3 class="h5 text-dark mb-2">Create a site</h3>
                            <p class="text-muted small">Add workers & set rates. Takes just a few minutes.</p>
                        </div>
                    </div>
                    
                    <!-- Step 2 -->
                    <div class="col">
                        <div class="bg-white p-4 rounded border-start border-accent">
                            <div class="mb-3">
                                <span class="badge bg-accent text-dark fs-6">2</span>
                            </div>
                            <h3 class="h5 text-dark mb-2">Mark attendance</h3>
                            <p class="text-muted small">Each day: foreman logs in web, or workers dial USSD code.</p>
                        </div>
                    </div>
                    
                    <!-- Step 3 -->
                    <div class="col">
                        <div class="bg-white p-4 rounded border-start border-accent">
                            <div class="mb-3">
                                <span class="badge bg-accent text-dark fs-6">3</span>
                            </div>
                            <h3 class="h5 text-dark mb-2">Compute & payout</h3>
                            <p class="text-muted small">Approve pay, payout via M-Pesa. All in one place.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Pricing Section -->
        <section id="pricing" class="py-5 py-lg-6 bg-white">
            <div class="container-lg">
                <div class="text-center mb-5">
                    <h2 class="h2 text-dark mb-3">Simple, transparent pricing</h2>
                    <p class="fs-6 text-muted">No hidden fees. Scale as you grow.</p>
                </div>
                
                <!-- Pricing Card -->
                <div class="mx-auto" style="max-width: 32rem;">
                    <div class="pricing-card p-4 rounded border shadow-sm">
                        <div class="text-center mb-4">
                            <p class="display-4 text-dark mb-2">
                                KES <span class="text-accent">50</span>
                            </p>
                            <p class="text-muted">per worker / per week</p>
                            <p class="small text-muted mt-2">Billed weekly</p>
                        </div>
                        
                        <!-- Offer -->
                        <div class="pricing-offer mb-4 p-3 border rounded text-muted small">
                            <p class="text-center mb-0">
                                <strong>🎯 First 10 workers free for 4 weeks</strong>
                            </p>
                        </div>
                        
                        <!-- Features (implicit in pricing) -->
                        <ul class="list-unstyled mb-4">
                            <li class="d-flex gap-2 mb-2">
                                <span class="text-success">✓</span>
                                <span class="text-dark">Unlimited sites</span>
                            </li>
                            <li class="d-flex gap-2 mb-2">
                                <span class="text-success">✓</span>
                                <span class="text-dark">USSD + web access</span>
                            </li>
                            <li class="d-flex gap-2 mb-2">
                                <span class="text-success">✓</span>
                                <span class="text-dark">Works across sites — construction, farms, events</span>
                            </li>
                            <li class="d-flex gap-2 mb-2">
                                <span class="text-success">✓</span>
                                <span class="text-dark">Email support</span>
                            </li>
                        </ul>
                        
                        <a href="{{ route('register') }}" class="btn btn-primary w-100 d-block">Start free trial</a>
                        
                        <!-- Removed dead link -->
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Social Proof / Testimonials -->
        <section class="py-5 py-lg-6 bg-body-secondary border-top">
            <div class="container-lg">
                <div class="text-center mb-5">
                    <h2 class="h2 text-dark mb-3">Trusted by small contractors</h2>
                    <p class="fs-6 text-muted">See how teams are simplifying payroll</p>
                </div>
                
                <!-- Testimonials Grid -->
                <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
                    <!-- Testimonial 1 -->
                    <div class="col">
                        <div class="testimonial-item bg-white p-4 rounded">
                            <div class="mb-3">
                                <span class="text-warning">★★★★★</span>
                            </div>
                            <p class="text-dark mb-4">
                                "SiteGrid cut our payroll time from 3 hours to 30 minutes. Works across our sites - from farms to events."
                            </p>
                            <div class="d-flex gap-2">
                                <div class="author-avatar">DK</div>
                                <div class="author-info">
                                    <h5 class="fw-semibold text-dark mb-0">David Kipchoge</h5>
                                    <p class="small text-muted mb-0">Operations Lead</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Testimonial 2 -->
                    <div class="col">
                        <div class="testimonial-item bg-white p-4 rounded">
                            <div class="mb-3">
                                <span class="text-warning">★★★★★</span>
                            </div>
                            <p class="text-dark mb-4">
                                "We run five sites and SiteGrid syncs everything. Especially love USSD - workers without smartphones can check in."
                            </p>
                            <div class="d-flex gap-2">
                                <div class="author-avatar">SM</div>
                                <div class="author-info">
                                    <h5 class="fw-semibold text-dark mb-0">Sarah Muthoni</h5>
                                    <p class="small text-muted mb-0">Site Manager</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Testimonial 3 -->
                    <div class="col">
                        <div class="testimonial-item bg-white p-4 rounded">
                            <div class="mb-3">
                                <span class="text-warning">★★★★★</span>
                            </div>
                            <p class="text-dark mb-4">
                                "No upfront fees, no minimum. Perfect for our pilot. We're scaling up to 40 workers next month."
                            </p>
                            <div class="d-flex gap-2">
                                <div class="author-avatar">JK</div>
                                <div class="author-info">
                                    <h5 class="fw-semibold text-dark mb-0">James Kariuki</h5>
                                    <p class="small text-muted mb-0">Project Owner</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pilot Partners / Alternative if no customers -->
                <div class="p-4 bg-white rounded border text-center">
                    <p class="text-dark fw-semibold mb-0">
                        💼 Pilot partners welcome - 
                        <a href="#contact" class="text-accent text-decoration-none fw-semibold">contact us to get started</a>
                    </p>
                </div>
            </div>
        </section>
        
        <!-- FAQ Section (Bootstrap Accordion) -->
        <section id="faq" class="py-5 py-lg-6 bg-white">
            <div class="container" style="max-width: 56rem;">
                <div class="text-center mb-5">
                    <h2 class="h2 text-dark mb-3">Frequently asked questions</h2>
                    <p class="fs-6 text-muted">Everything you need to know</p>
                </div>
                
                <div class="accordion" id="faqAccordion">
                    <!-- FAQ Item 1 -->
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="heading1">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="false" aria-controls="faq1">
                                How does USSD work?
                            </button>
                        </h3>
                        <div id="faq1" class="accordion-collapse collapse" aria-labelledby="heading1" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Workers dial a code (e.g., <code>*123*456#</code>) to check in. Works for farms, construction sites, events, and more. They confirm attendance, and we record it instantly. No data, no app, no smartphone required.
                            </div>
                        </div>
                    </div>
                    
                    <!-- FAQ Item 2 -->
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="heading2">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false" aria-controls="faq2">
                                How are payouts protected?
                            </button>
                        </h3>
                        <div id="faq2" class="accordion-collapse collapse" aria-labelledby="heading2" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Site owners approve payroll before we send funds. Funds are held in an escrow until the owner releases them. All transactions are logged for audit and dispute resolution.
                            </div>
                        </div>
                    </div>
                    
                    <!-- FAQ Item 3 -->
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="heading3">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false" aria-controls="faq3">
                                What are payment fees?
                            </button>
                        </h3>
                        <div id="faq3" class="accordion-collapse collapse" aria-labelledby="heading3" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Our platform charge is KES 50 per worker per week. M-Pesa transfer fees are paid by the site owner (standard Safaricom rates, ~KES 21–33 per transaction). USSD dialing is free on most networks.
                            </div>
                        </div>
                    </div>
                    
                    <!-- FAQ Item 4 -->
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="heading4">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4" aria-expanded="false" aria-controls="faq4">
                                What if my workers don't have M-Pesa?
                            </button>
                        </h3>
                        <div id="faq4" class="accordion-collapse collapse" aria-labelledby="heading4" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Most workers in Kenya have M-Pesa. If they don't, the site owner can payout manually, or we can help you set up a group account. Chat with sales for custom integrations.
                            </div>
                        </div>
                    </div>
                    
                    <!-- FAQ Item 5 -->
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="heading5">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5" aria-expanded="false" aria-controls="faq5">
                                Can I trial SiteGrid for free?
                            </button>
                        </h3>
                        <div id="faq5" class="accordion-collapse collapse" aria-labelledby="heading5" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes! Your first 10 workers are free for 4 weeks. No payment method needed to start. After that, it's KES 50 per worker per week.
                            </div>
                        </div>
                    </div>
                    
                    <!-- FAQ Item 6: Materials & Tools -->
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="heading6">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6" aria-expanded="false" aria-controls="faq6">
                                Can I track materials and tools?
                            </button>
                        </h3>
                        <div id="faq6" class="accordion-collapse collapse" aria-labelledby="heading6" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes — SiteGrid supports onsite inventory: receive deliveries, issue materials to tasks, and log tool checkouts so you can reconcile costs by pay cycle.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Contact / Demo CTA -->
        <section id="contact" class="py-5 py-lg-6 bg-dark text-white">
            <div class="container" style="max-width: 56rem;">
                <div class="text-center mb-5">
                    <h2 class="h2 text-white mb-3">Ready to simplify payroll?</h2>
                    <p class="fs-6 text-gray-300">Start free today or request a demo from our team.</p>
                </div>
                
                <div class="row g-4">
                    <!-- Quick Signup -->
                    <div class="col-12 col-md-6">
                        <div class="bg-dark p-4 rounded border border-secondary">
                            <h3 class="h5 text-white mb-4">Quick Start (2 min)</h3>
                            <p class="text-white-50 small mb-3">Start your free trial now – no credit card required.</p>
                            <a href="{{ route('register') }}" class="btn btn-primary w-100">
                                Create a site
                            </a>
                        </div>
                    </div>
                    
                    <!-- Demo Form -->
                    <div class="col-12 col-md-6">
                        <div class="bg-dark p-4 rounded border border-secondary">
                            <h3 class="h5 text-white mb-4">Request a Demo</h3>
                            <form id="demoForm" action="{{ route('demo.submit') }}" method="POST">
                                @csrf
                                
                                <div class="mb-3">
                                    <label for="demo_name" class="form-label">Full Name</label>
                                    <input type="text" id="demo_name" name="name" required class="form-control form-control-dark" placeholder="Full name">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="demo_company" class="form-label">Company</label>
                                    <input type="text" id="demo_company" name="company" class="form-control form-control-dark" placeholder="Your Company (e.g., Farm, Event Co, Contractor)">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="demo_email" class="form-label">Email</label>
                                    <input type="email" id="demo_email" name="email" required class="form-control form-control-dark" placeholder="you@example.com">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="demo_phone" class="form-label">Phone (optional)</label>
                                    <input type="tel" id="demo_phone" name="phone" class="form-control form-control-dark" placeholder="+254 7XXXXXXXX">
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    Send Request
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <!-- Footer -->
    <footer class="bg-dark text-gray-300 py-5 border-top">
        <div class="container-lg">
            <div class="row g-4 mb-4">
                <!-- Brand -->
                <div class="col-12 col-sm-6 col-lg-auto">
                    <div class="d-flex gap-2 align-items-center mb-3">
                        <img src="{{ asset('images/logo.png') }}" alt="SiteGrid Logo" style="height: 3rem; width: auto;">
                    </div>
                    <p class="small">Workforce, payroll & site operations for construction, agriculture, events and more.</p>
                </div>
                
                <!-- Product Links -->
                <div class="col-12 col-sm-6 col-lg">
                    <h6 class="fw-semibold text-white mb-3">Product</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="#" class="text-gray-300 text-decoration-none hover-white">Features</a></li>
                        <li class="mb-2"><a href="#pricing" class="text-gray-300 text-decoration-none hover-white">Pricing</a></li>
                        <li class="mb-2"><a href="#how-it-works" class="text-gray-300 text-decoration-none hover-white">How it works</a></li>
                        <li><a href="{{ route('login') }}" class="text-gray-300 text-decoration-none hover-white">Sign in</a></li>
                    </ul>
                </div>
                
                <!-- Company Links -->
                <div class="col-12 col-sm-6 col-lg">
                    <h6 class="fw-semibold text-white mb-3">Company</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="#contact" class="text-gray-300 text-decoration-none hover-white">Contact</a></li>
                        <li class="mb-2"><a href="#faq" class="text-gray-300 text-decoration-none hover-white">FAQ</a></li>
                        <li class="mb-2"><a href="#" class="text-gray-300 text-decoration-none hover-white">Blog</a></li>
                        <li><a href="#" class="text-gray-300 text-decoration-none hover-white">Docs</a></li>
                    </ul>
                </div>
                
                <!-- Legal -->
                <div class="col-12 col-sm-6 col-lg">
                    <h6 class="fw-semibold text-white mb-3">Legal</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="#" class="text-gray-300 text-decoration-none hover-white">Privacy Policy</a></li>
                        <li class="mb-2"><a href="#" class="text-gray-300 text-decoration-none hover-white">Terms of Service</a></li>
                        <li><a href="#" class="text-gray-300 text-decoration-none hover-white">Cookie Policy</a></li>
                    </ul>
                </div>
                
                <!-- Social -->
                <div class="col-12 col-sm-6 col-lg">
                    <h6 class="fw-semibold text-white mb-3">Follow</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="#" class="text-gray-300 text-decoration-none hover-white">Twitter</a></li>
                        <li class="mb-2"><a href="#" class="text-gray-300 text-decoration-none hover-white">LinkedIn</a></li>
                        <li><a href="#" class="text-gray-300 text-decoration-none hover-white">GitHub</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-top">
                <p class="text-center small text-gray-400 mt-4 mb-3">
                    © 2026 SiteGrid. All rights reserved.
                </p>
                <p class="text-center text-xs text-gray-500 mb-0">
                    <a href="#" class="text-gray-400 text-decoration-none">Read our Privacy Policy</a>.
                </p>
            </div>
        </div>
    </footer>
    
    <!-- Signup Modal (Bootstrap) -->
    <div class="modal fade" id="signupModal" tabindex="-1" aria-labelledby="signupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title h4" id="signupModalLabel">📋 Create Your Site Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="text-muted mb-4">Manage your site and track worker payments. Get started in 2 minutes.</p>
                    
                    <form id="signupFormPhone" action="{{ route('signup.phone') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label small">Phone number</label>
                            <input type="tel" id="phone" name="phone" required class="form-control" placeholder="+254 7XXXXXXXX" aria-describedby="phone-help">
                            <small id="phone-help" class="d-block text-muted mt-1">International format (e.g., +254 for Kenya)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="name_signup" class="form-label small">Your name</label>
                            <input type="text" id="name_signup" name="name" class="form-control" placeholder="Your full name">
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            Send verification code
                        </button>
                        
                        <p class="text-center text-xs text-muted mt-3 mb-0">
                            We'll verify your phone number with a one-time code.
                            <a href="#" class="text-accent text-decoration-none">Read our Privacy Policy</a>.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- OTP Verification Modal (Bootstrap) -->
    <div class="modal fade" id="otpModal" tabindex="-1" aria-labelledby="otpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title h4" id="otpModalLabel">✅ Verify your phone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <p id="otpPhone" class="text-muted mb-4">Enter the code sent to <strong>+254 ••••••••</strong></p>
                    
                    <form id="otpForm" action="{{ route('signup.verify-otp') }}" method="POST">
                        @csrf
                        <input type="hidden" id="otpPhoneInput" name="phone">
                        
                        <div class="mb-3">
                            <label for="otp_code" class="form-label small">Verification code</label>
                            <input type="text" id="otp_code" name="otp_code" required maxlength="6" class="form-control text-center fs-4 tracking-widest font-monospace" placeholder="000000" aria-describedby="otp-help">
                            <small id="otp-help" class="d-block text-muted mt-2">
                                Didn't get it? <button type="button" id="resendBtn" class="btn btn-link btn-sm p-0">Resend in 30s</button>
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            Verify & Complete Setup
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Success Toast Notification -->
    <div id="successToast" class="toast align-items-center text-white bg-success border-0 position-fixed bottom-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true" style="z-index: 1060; display: none;">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage">
                Success!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Bootstrap modals
        const signupModal = new bootstrap.Modal(document.getElementById('signupModal'));
        const otpModal = new bootstrap.Modal(document.getElementById('otpModal'));
        const successToast = new bootstrap.Toast(document.getElementById('successToast'), { delay: 4000 });
        
        // Function to open signup modal (can be called from any button)
        function openSignupModal() {
            signupModal.show();
        }
        
        // Phone form submission
        document.getElementById('signupFormPhone').addEventListener('submit', function(e) {
            e.preventDefault();
            const phone = document.getElementById('phone').value;
            const name = document.getElementById('name_signup').value;
            
            if (!phone || phone.length < 9) {
                alert('Please enter a valid phone number');
                return;
            }
            
            const button = this.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = 'Sending code...';
            
            // Simulate AJAX call
            setTimeout(() => {
                // Mask phone for display
                const masked = '+254 ' + phone.slice(-9, -4) + '••••';
                document.getElementById('otpPhone').innerHTML = `Enter the code sent to <strong>${masked}</strong>`;
                document.getElementById('otpPhoneInput').value = phone;
                
                signupModal.hide();
                otpModal.show();
                
                button.disabled = false;
                button.textContent = 'Send verification code';
                
                // Start resend timer when OTP modal opens
                startResendTimer();
            }, 1500);
        });
        
        // OTP form submission
        document.getElementById('otpForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const otp = document.getElementById('otp_code').value;
            
            if (!otp || otp.length < 4) {
                alert('Please enter a valid code');
                return;
            }
            
            const button = this.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = 'Verifying...';
            
            setTimeout(() => {
                otpModal.hide();
                showToast('Account created! Redirecting to dashboard...');
                setTimeout(() => {
                    window.location.href = '/dashboard';
                }, 2000);
            }, 1500);
        });
        
        // Demo form submission
        document.getElementById('demoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const button = this.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = 'Sending...';
            
            setTimeout(() => {
                showToast('Demo request sent! We\'ll contact you soon.');
                this.reset();
                button.disabled = false;
                button.textContent = 'Send Request';
            }, 1500);
        });
        
        // Toast helper
        function showToast(message) {
            document.getElementById('toastMessage').textContent = message;
            successToast.show();
        }
        
        // Resend OTP timer
        let resendCountdown = 30;
        const resendBtn = document.getElementById('resendBtn');
        
        function startResendTimer() {
            resendCountdown = 30;
            resendBtn.disabled = true;
            resendBtn.textContent = `Resend in ${resendCountdown}s`;
            
            const timer = setInterval(() => {
                resendCountdown--;
                resendBtn.textContent = `Resend in ${resendCountdown}s`;
                
                if (resendCountdown <= 0) {
                    clearInterval(timer);
                    resendBtn.disabled = false;
                    resendBtn.textContent = 'Resend now';
                }
            }, 1000);
        }
        
        resendBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!this.disabled) {
                showToast('Code resent to your phone!');
                startResendTimer();
            }
        });
        
        // Analytics tracking placeholder
        function trackEvent(eventName, eventData = {}) {
            if (window.gtag) {
                gtag('event', eventName, eventData);
            }
            console.log('Event tracked:', eventName, eventData);
        }
        
        // Track CTA clicks
        document.querySelectorAll('.btn-primary').forEach(btn => {
            btn.addEventListener('click', function() {
                if (this.textContent.includes('Create a site')) {
                    trackEvent('signup_initiated', { method: 'cta_click' });
                }
            });
        });
    </script>
    
    <!-- Google Tag Manager (noscript) placeholder -->
    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-XXXXXXX"
        height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
</body>
</html>