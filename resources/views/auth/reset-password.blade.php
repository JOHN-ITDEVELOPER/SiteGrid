<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Reset your SiteGrid password.">
     <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.ico') }}">
    <title>Reset Password - SiteGrid</title>
    
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

        /* Form Styles */
        .form-group {
            margin-bottom: 1.25rem;
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

        /* Header Logo */
        .header-logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .header-logo img {
            height: 3rem;
            width: auto;
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
                <h1>Set New Password</h1>
                <p>Create a new password for your SiteGrid account</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- Reset Password Form -->
            <form id="resetForm" action="{{ route('password.update') }}" method="POST">
                @csrf
                
                <!-- Hidden Token and Email -->
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email_display" class="form-control" 
                           value="{{ $email }}" disabled>
                    <small class="form-help">Password will be reset for this email</small>
                </div>

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

                <button type="submit" class="btn btn-primary">
                    Reset Password
                </button>
            </form>

            <!-- Footer -->
            <div class="reset-footer">
                <p><a href="{{ route('login') }}">← Back to sign in</a></p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password strength meter
        const newPasswordInput = document.getElementById('newPassword');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

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

        // Form validation
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const password = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

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
        });
    </script>
</body>
</html>
