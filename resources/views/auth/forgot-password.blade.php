<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Reset your SiteGrid password with email or phone.">
     <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.ico') }}">
    <title>Forgot Password - SiteGrid</title>
    
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

        .reset-container {
            width: 100%;
            max-width: 28rem;
            padding: 1rem;
        }

        .reset-card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
            padding: 2rem;
        }

        .reset-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .reset-header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--primary-indigo);
            margin-bottom: 0.5rem;
        }

        .reset-header p {
            color: var(--text-muted);
            font-size: 0.875rem;
            line-height: 1.5;
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
        .reset-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }

        .reset-footer p {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: 0;
        }

        .reset-footer a {
            color: var(--accent-orange);
            text-decoration: none;
            font-weight: 600;
        }

        .reset-footer a:hover {
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

        /* Info Box */
        .info-box {
            background-color: #f3f4f6;
            border-left: 4px solid var(--accent-orange);
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: var(--text-dark);
        }

        /* Steps */
        .step-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .step-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            background-color: var(--accent-orange);
            color: white;
            border-radius: 50%;
            font-weight: 700;
            flex-shrink: 0;
        }

        .step-content h4 {
            margin: 0 0 0.25rem 0;
            color: var(--primary-indigo);
            font-size: 0.95rem;
            font-weight: 600;
        }

        .step-content p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.85rem;
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

        /* OTP/Reset Forms */
        .otp-section,
        .reset-password-section {
            display: none;
        }

        .otp-section.show,
        .reset-password-section.show {
            display: block;
        }

        /* Strength Meter */
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
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <!-- Logo -->
            <div class="header-logo">
                <img src="{{ asset('images/logo.png') }}" alt="SiteGrid Logo">
            </div>

            <!-- Header -->
            <div class="reset-header">
                <h1>Reset Your Password</h1>
                <p>Choose how you'd like to recover your account</p>
            </div>

            @if(session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="email-tab" data-bs-toggle="tab" data-bs-target="#email-recovery" type="button" role="tab">
                        ✉️ Email
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="phone-tab" data-bs-toggle="tab" data-bs-target="#phone-recovery" type="button" role="tab">
                        📱 Phone
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Email Recovery Tab -->
                <div class="tab-pane fade show active" id="email-recovery" role="tabpanel">
                    <div class="info-box">
                        ℹ️ We'll send a password reset link to your email. The link expires in 60 minutes.
                    </div>

                    <form id="emailRecoveryForm" action="{{ route('password.email') }}" method="POST">
                        @csrf
                        
                        @if($errors->has('email'))
                            <div class="alert alert-danger">
                                {{ $errors->first('email') }}
                            </div>
                        @endif

                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                   placeholder="you@company.com" required value="{{ old('email') }}">
                            <small class="form-help">Enter the email associated with your account</small>
                        </div>

                        <button type="submit" class="btn btn-primary" id="emailSubmitBtn">
                            <span id="emailSubmitText">Send Reset Link</span>
                            <span id="emailSubmitLoader" class="d-none">
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                Sending...
                            </span>
                        </button>
                    </form>

                    <div class="mt-3">
                        <div class="step-item">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Enter your email</h4>
                                <p>Use the email address associated with your account</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Check your inbox</h4>
                                <p>We'll send a reset link (check spam if not found)</p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>Set new password</h4>
                                <p>Click the link and create a new password</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Phone Recovery Tab -->
                <div class="tab-pane fade" id="phone-recovery" role="tabpanel">
                    <div id="phoneInitial">
                        <div class="info-box">
                            ℹ️ We'll send a verification code to your phone. Use it to reset your password.
                        </div>

                        <form id="phoneRecoveryForm">
                            @csrf
                            
                            @if($errors->has('phone'))
                                <div class="alert alert-danger">
                                    {{ $errors->first('phone') }}
                                </div>
                            @endif

                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" id="recoveryPhone" name="phone" class="form-control" 
                                       placeholder="+254 7XXXXXXXX" required value="{{ old('phone') }}">
                                <small class="form-help">The phone number associated with your account</small>
                            </div>

                            <button type="submit" class="btn btn-primary" id="phoneSubmitBtn">
                                <span id="phoneSubmitText">Send Verification Code</span>
                                <span id="phoneSubmitLoader" class="d-none">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    Sending...
                                </span>
                            </button>
                        </form>

                        <div class="mt-3">
                            <div class="step-item">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4>Enter your phone</h4>
                                    <p>Use the phone number associated with your account</p>
                                </div>
                            </div>
                            <div class="step-item">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4>Verify code</h4>
                                    <p>Enter the 6-digit code sent to your phone</p>
                                </div>
                            </div>
                            <div class="step-item">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4>Reset password</h4>
                                    <p>Create and confirm your new password</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- OTP Verification Section -->
                    <div id="otpSection" class="otp-section">
                        <div class="alert alert-info">
                            Verification code sent to <strong id="displayPhone"></strong>
                        </div>

                        <form id="otpVerifyForm">
                            @csrf

                            <div class="form-group">
                                <label class="form-label">Verification Code</label>
                                <input type="text" id="otpCode" name="otp_code" maxlength="6" class="form-control text-center fs-4 font-monospace" 
                                       placeholder="000000" required>
                                <small class="form-help">Enter the 6-digit code sent to your phone</small>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" id="otpSubmitBtn">
                                    <span id="otpSubmitText">Verify Code</span>
                                    <span id="otpSubmitLoader" class="d-none">
                                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                        Verifying...
                                    </span>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="resetPhoneForm()">
                                    Use different number
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Reset Password Section -->
                    <div id="resetPasswordSection" class="reset-password-section">
                        <form id="resetPasswordForm" action="{{ route('password.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="phone" id="resetPhone">
                            <input type="hidden" name="otp_code" id="resetOtpCode">

                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" id="newPassword" name="password" class="form-control" 
                                       placeholder="••••••••" required>
                                <div class="strength-meter">
                                    <div class="strength-bar" id="strengthBar"></div>
                                </div>
                                <small class="form-help" id="strengthText">Password must be at least 8 characters</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" id="confirmPassword" name="password_confirmation" class="form-control" 
                                       placeholder="••••••••" required>
                            </div>

                            <button type="submit" class="btn btn-primary" id="resetSubmitBtn">
                                <span id="resetSubmitText">Reset Password</span>
                                <span id="resetSubmitLoader" class="d-none">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    Resetting...
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="reset-footer">
                <p><a href="{{ route('login') }}">← Back to sign in</a></p>
                <p style="margin-top: 0.5rem;">Don't have an account? <a href="{{ route('register') }}">Create one here</a></p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Email form submission with loader
        document.getElementById('emailRecoveryForm')?.addEventListener('submit', function(e) {
            const emailSubmitBtn = document.getElementById('emailSubmitBtn');
            const emailSubmitText = document.getElementById('emailSubmitText');
            const emailSubmitLoader = document.getElementById('emailSubmitLoader');
            
            emailSubmitBtn.disabled = true;
            emailSubmitText.classList.add('d-none');
            emailSubmitLoader.classList.remove('d-none');
        });

        // Password strength meter
        const newPasswordInput = document.getElementById('newPassword');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', function() {
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

        // Phone form submission
        document.getElementById('phoneRecoveryForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const phone = document.getElementById('recoveryPhone').value;
            const phoneSubmitBtn = document.getElementById('phoneSubmitBtn');
            const phoneSubmitText = document.getElementById('phoneSubmitText');
            const phoneSubmitLoader = document.getElementById('phoneSubmitLoader');
            
            // Show loading state
            phoneSubmitBtn.disabled = true;
            phoneSubmitText.classList.add('d-none');
            phoneSubmitLoader.classList.remove('d-none');
            
            // Simulate sending code
            setTimeout(() => {
                document.getElementById('displayPhone').textContent = phone;
                document.getElementById('phoneInitial').style.display = 'none';
                document.getElementById('otpSection').classList.add('show');
                
                // Reset loader
                phoneSubmitBtn.disabled = false;
                phoneSubmitText.classList.remove('d-none');
                phoneSubmitLoader.classList.add('d-none');
            }, 1000);
        });

        // OTP verification
        document.getElementById('otpVerifyForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const otpCode = document.getElementById('otpCode').value;
            const phone = document.getElementById('recoveryPhone').value;
            const otpSubmitBtn = document.getElementById('otpSubmitBtn');
            const otpSubmitText = document.getElementById('otpSubmitText');
            const otpSubmitLoader = document.getElementById('otpSubmitLoader');
            
            if (otpCode.length !== 6) {
                alert('Please enter a 6-digit code');
                return;
            }

            // Show loading state
            otpSubmitBtn.disabled = true;
            otpSubmitText.classList.add('d-none');
            otpSubmitLoader.classList.remove('d-none');

            // Simulate OTP verification
            setTimeout(() => {
                // Store values for password reset form
                document.getElementById('resetPhone').value = phone;
                document.getElementById('resetOtpCode').value = otpCode;
                
                // Show password reset section
                document.getElementById('otpSection').classList.remove('show');
                document.getElementById('resetPasswordSection').classList.add('show');
                
                // Reset loader
                otpSubmitBtn.disabled = false;
                otpSubmitText.classList.remove('d-none');
                otpSubmitLoader.classList.add('d-none');
            }, 1000);
        });

        // Reset password form
        document.getElementById('resetPasswordForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const resetSubmitBtn = document.getElementById('resetSubmitBtn');
            const resetSubmitText = document.getElementById('resetSubmitText');
            const resetSubmitLoader = document.getElementById('resetSubmitLoader');

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

            // Show loading state
            resetSubmitBtn.disabled = true;
            resetSubmitText.classList.add('d-none');
            resetSubmitLoader.classList.remove('d-none');
        });

        // Reset phone form
        function resetPhoneForm() {
            const phoneSubmitBtn = document.getElementById('phoneSubmitBtn');
            const phoneSubmitText = document.getElementById('phoneSubmitText');
            const phoneSubmitLoader = document.getElementById('phoneSubmitLoader');
            
            document.getElementById('recoveryPhone').value = '';
            document.getElementById('otpCode').value = '';
            document.getElementById('phoneInitial').style.display = 'block';
            document.getElementById('otpSection').classList.remove('show');
            document.getElementById('resetPasswordSection').classList.remove('show');
            
            // Reset loader state
            phoneSubmitBtn.disabled = false;
            phoneSubmitText.classList.remove('d-none');
            phoneSubmitLoader.classList.add('d-none');
        }
    </script>
</body>
</html>
