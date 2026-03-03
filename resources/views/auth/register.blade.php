<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create your SiteGrid site account and start managing your workforce today.">
     <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.ico') }}">
    <title>Create Account - SiteGrid</title>
    
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-indigo: #1e1b4b;
            --accent-orange: #f97316;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
        }
        
        body {
            background-image:
                linear-gradient(135deg, #f97316 0%, rgba(124, 58, 237, 0.75) 100%),
                url("{{ asset('images/login.jpg') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
        }

        .register-container {
            width: 100%;
            max-width: 28rem;
            padding: 1rem;t
        }

        .register-card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
            padding: 2rem;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--primary-indigo);
            margin-bottom: 0.5rem;
        }

        .register-header p {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        /* Tab Navigation */
        .nav-tabs {
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 1.5rem;
        }

        .nav-link {
            color: var(--text-muted);
            border: none;
            padding: 0.75rem 1rem;
            font-weight: 500;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .nav-link:hover {
            color: var(--primary-indigo);
            border-bottom-color: var(--accent-orange);
        }

        .nav-link.active {
            color: var(--primary-indigo);
            border-bottom-color: var(--accent-orange);
            background: transparent;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--primary-indigo);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.625rem;
            font-size: 0.95rem;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-orange);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
            background-color: white;
        }

        .form-help {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .strength-meter {
            height: 0.25rem;
            background-color: #e5e7eb;
            border-radius: 0.125rem;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }

        .strength-bar.weak {
            width: 33%;
            background-color: #ef4444;
        }

        .strength-bar.fair {
            width: 66%;
            background-color: #f59e0b;
        }

        .strength-bar.strong {
            width: 100%;
            background-color: #10b981;
        }

        /* Terms Checkbox */
        .form-check {
            margin-bottom: 1rem;
        }

        .form-check-input {
            border: 1px solid #d1d5db;
            border-radius: 0.25rem;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--accent-orange);
            border-color: var(--accent-orange);
        }

        .form-check-label {
            cursor: pointer;
            font-size: 0.875rem;
            color: var(--text-dark);
            margin-left: 0.5rem;
        }

        .form-check-label a {
            color: var(--accent-orange);
            text-decoration: none;
            font-weight: 500;
        }

        .form-check-label a:hover {
            text-decoration: underline;
        }

        /* Buttons */
        .btn-primary {
            background-color: var(--accent-orange);
            border: none;
            padding: 0.625rem 1rem;
            font-weight: 600;
            border-radius: 0.375rem;
            transition: all 0.2s;
            width: 100%;
        }

        .btn-primary:hover {
            background-color: #ea580c;
            color: white;
        }

        .btn-primary:disabled {
            background-color: #d1d5db;
            cursor: not-allowed;
        }

        /* Links */
        .register-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }

        .register-footer p {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: 0;
        }

        .register-footer a {
            color: var(--accent-orange);
            text-decoration: none;
            font-weight: 600;
        }

        .register-footer a:hover {
            text-decoration: underline;
        }

        /* Alerts */
        .alert {
            border-radius: 0.375rem;
            border: none;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .alert-info {
            background-color: #dbeafe;
            color: #0c4a6e;
        }

        /* OTP Input Group */
        .otp-container {
            display: none;
        }

        .otp-container.show {
            display: block;
        }

        /* Loading State */
        .btn-primary[disabled] {
            pointer-events: none;
        }

        /* Header Logo */
        .header-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header-logo img {
            height: 3rem;
            width: auto;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <!-- Logo -->
            <div class="header-logo">
                <img src="{{ asset('images/logo.png') }}" alt="SiteGrid Logo">
            </div>

            <!-- Header -->
            <div class="register-header">
                <h1>Create Your Account</h1>
                <p>Get started with SiteGrid - manage your workforce in minutes</p>
            </div>

            <!-- Info Box -->
            <div style="background-color: #f3f4f6; border-left: 4px solid var(--accent-orange); padding: 1rem; border-radius: 0.375rem; margin-bottom: 1.5rem; font-size: 0.875rem; color: var(--text-dark);">
                📧 We'll send a verification link to your email to confirm your account.
            </div>

            <!-- Registration Form -->
            <form id="registerForm" action="{{ route('register.email') }}" method="POST">
                @csrf
                
                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $error)
                            <p class="mb-1">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="form-group">
                    <label class="form-label">Business/Site Name</label>
                    <input type="text" name="site_name" class="form-control @error('site_name') is-invalid @enderror" 
                           placeholder="Your company or site name" required value="{{ old('site_name') }}">
                    <small class="form-help">The name of your site or business</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                           placeholder="you@company.com" required value="{{ old('email') }}">
                    <small class="form-help">We'll use this to verify your account and send you updates</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" 
                           placeholder="••••••••" required>
                    <div class="strength-meter">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <small class="form-help" id="strengthText">Password must be at least 8 characters</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" 
                           placeholder="••••••••" required>
                </div>

                <!-- Terms Checkbox -->
                <div class="form-check mb-3">
                    <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" id="termsCheck" name="terms" required>
                    <label class="form-check-label" for="termsCheck">
                        By creating an account, you agree to our <a href="#" target="_blank">Terms of Service</a>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <span id="submitText">Create Account</span>
                    <span id="submitLoader" class="d-none">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Creating...
                    </span>
                </button>
            </form>

            <!-- Footer -->
            <div class="register-footer">
                <p>Already have an account? <a href="{{ route('login') }}">Sign in here</a></p>
                <p style="margin-top: 0.5rem;">Forgot your password? <a href="{{ route('password.request') }}">Reset it</a></p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password strength meter
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let text = '';

                if (password.length >= 8) strength++;
                if (password.length >= 12) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[!@#$%^&*]/.test(password)) strength++;

                strengthBar.classList.remove('weak', 'fair', 'strong');
                
                if (strength <= 2) {
                    strengthBar.classList.add('weak');
                    text = '⚠️ Weak password';
                } else if (strength <= 3) {
                    strengthBar.classList.add('fair');
                    text = '👌 Fair password';
                } else {
                    strengthBar.classList.add('strong');
                    text = '✅ Strong password';
                }

                strengthText.textContent = text;
            });
        }

        // Form validation and loading state
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('password_confirmation').value;
            const termsCheck = document.getElementById('termsCheck').checked;

            if (!termsCheck) {
                e.preventDefault();
                alert('You must accept the terms and conditions');
                return false;
            }

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
                return false;
            }

            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters');
                return false;
            }

            // Show loading state and disable button
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const submitLoader = document.getElementById('submitLoader');
            
            submitBtn.disabled = true;
            submitText.classList.add('d-none');
            submitLoader.classList.remove('d-none');
        });
    </script>
</body>
</html>
