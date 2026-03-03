<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.ico') }}">
    <title>Login - SiteGrid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-indigo: #1e1b4b;
            --accent-orange: #f97316;
            --accent-orange-dark: #ea580c;
        }

        body {
            background-color: var(--primary-indigo);
            background-image:
                linear-gradient(135deg, #f97316 0%, rgba(124, 58, 237, 0.75) 100%),
                url("{{ asset('images/login.jpg') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .form-label {
            color: #374151;
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-control-lg {
            padding: 0.875rem 1rem;
            font-size: 1rem;
            border-radius: 0.5rem;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .form-control-lg:focus {
            border-color: var(--accent-orange);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15);
            color: #1f2937;
        }

        .form-control-lg::placeholder {
            color: #9ca3af;
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.4s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-orange) 0%, var(--accent-orange-dark) 100%);
            border: none;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--accent-orange-dark) 0%, #c2410c 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(249, 115, 22, 0.35);
            color: white;
        }

        .btn-primary:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            transform: none;
        }

        .btn-link {
            font-weight: 600;
            text-decoration: none;
        }

        .btn-link:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 0.5rem;
            border: none;
            animation: slideDown 0.3s ease;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }

        .input-group .btn-outline-secondary {
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            color: #6b7280;
            padding: 0.5rem 0.75rem;
            transition: all 0.3s ease;
        }

        .input-group .btn-outline-secondary:hover {
            border-color: var(--accent-orange);
            color: var(--accent-orange);
            background-color: transparent;
        }

        .form-check-label {
            cursor: pointer;
            user-select: none;
            font-size: 0.95rem;
            color: #374151;
        }

        .text-white-50 {
            color: rgba(255, 255, 255, 0.7);
        }

        a.text-white {
            color: white !important;
            transition: color 0.3s ease;
        }

        a.text-white:hover {
            color: rgba(255, 255, 255, 0.8) !important;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.2em;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .h3 {
            color: #1f2937;
        }

        .text-muted {
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="min-vh-100 d-flex align-items-center justify-content-center py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <!-- Back Button -->
                    <div class="mb-4">
                        <a href="{{ route('landing') }}" class="text-white text-decoration-none d-flex align-items-center gap-2" style="font-weight: 500;">
                            <i class="bi bi-arrow-left fs-5"></i>
                            <span>Back to home</span>
                        </a>
                    </div>

                    <!-- Login Card -->
                    <div class="card">
                        <div class="card-body p-5">
                            <h1 class="h3 fw-bold mb-2">Welcome back</h1>
                            <p class="text-muted mb-4">Sign in to manage your site or track your work</p>

                            <!-- Alert Messages -->
                            <div id="alertBox" style="display: none;" class="alert alert-dismissible fade show mb-3" role="alert">
                                <span id="alertMessage"></span>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>

                            <!-- Session Messages -->
                            @if(session('status'))
                                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                                    <i class="bi bi-check-circle me-2"></i>
                                    {{ session('status') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            @if(session('verified_success'))
                                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <strong>✓ Email Verified!</strong> You can now login with your credentials.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            @if($errors->any())
                                @foreach($errors->all() as $error)
                                    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                                        <i class="bi bi-exclamation-circle me-2"></i>
                                        {{ $error }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                @endforeach
                            @endif

                            <!-- Login Form -->
                            <form id="loginForm" onsubmit="handleLoginSubmit(event)">
                                @csrf

                                <!-- Login Field (Phone or Email) -->
                                <div class="mb-3">
                                    <label for="login" class="form-label">Phone number or email</label>
                                    <input 
                                        type="text" 
                                        class="form-control form-control-lg" 
                                        id="login" 
                                        name="login" 
                                        placeholder="+254712345678 or user@example.com"
                                        required
                                        autocomplete="email"
                                    >
                                    <small class="text-muted d-block mt-2">Enter your phone number (with country code) or email address</small>
                                </div>

                                <!-- Password Field -->
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <input 
                                            type="password" 
                                            class="form-control form-control-lg" 
                                            id="password" 
                                            name="password" 
                                            placeholder="••••••••"
                                            required
                                            autocomplete="current-password"
                                        >
                                        <button 
                                            class="btn btn-outline-secondary" 
                                            type="button" 
                                            onclick="togglePasswordVisibility()"
                                        >
                                            <i class="bi bi-eye" id="toggleIcon"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Remember Me & Forgot -->
                                <div class="row align-items-center mb-4">
                                    <div class="col">
                                        <div class="form-check">
                                            <input 
                                                class="form-check-input" 
                                                type="checkbox" 
                                                id="remember" 
                                                name="remember"
                                            >
                                            <label class="form-check-label" for="remember">
                                                Remember me
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{ route('password.request') }}" class="text-primary text-decoration-none small fw-600">Forgot password?</a>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <span id="btnText">Sign in</span>
                                    <span id="btnSpinner" style="display: none;">
                                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                        Signing in...
                                    </span>
                                </button>
                            </form>

                            <hr class="my-4" style="background-color: #e5e7eb;">

                            <!-- Sign Up Link -->
                            <p class="text-center text-muted mb-0">
                                Don't have an account? 
                                <a href="{{ route('register') }}" class="btn btn-link btn-sm p-0 text-primary fw-600">
                                    Create a site
                                </a>
                            </p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="text-center mt-4">
                        <p class="text-white-50 small">
                            By signing in, you agree to our 
                            <a href="#" class="text-white text-decoration-none">Terms of Service</a> and 
                            <a href="#" class="text-white text-decoration-none">Privacy Policy</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }

        function showAlert(message, type = 'danger') {
            const alertBox = document.getElementById('alertBox');
            const alertMessage = document.getElementById('alertMessage');
            
            alertBox.className = `alert alert-${type} alert-dismissible fade show mb-3`;
            alertMessage.textContent = message;
            alertBox.style.display = 'block';
            
            // Auto-dismiss success alerts
            if (type === 'success') {
                setTimeout(() => {
                    alertBox.style.display = 'none';
                }, 3000);
            }
        }

        async function handleLoginSubmit(e) {
            e.preventDefault();
            
            const loginInput = document.getElementById('login');
            const passwordInput = document.getElementById('password');
            const submitBtn = document.querySelector('button[type="submit"]');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');

            // Validation
            if (!loginInput.value.trim()) {
                showAlert('Please enter your phone number or email');
                return;
            }

            if (!passwordInput.value || passwordInput.value.length < 6) {
                showAlert('Password must be at least 6 characters');
                return;
            }

            // Show loading state
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline';

            try {
                const response = await fetch('{{ route("login.submit") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({
                        login: loginInput.value.trim(),
                        password: passwordInput.value,
                        remember: document.getElementById('remember').checked
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('✓ Login successful! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect || '/dashboard';
                    }, 1000);
                } else {
                    showAlert(data.message || 'Login failed. Please try again.', 'danger');
                    submitBtn.disabled = false;
                    btnText.style.display = 'inline';
                    btnSpinner.style.display = 'none';
                }
            } catch (error) {
                console.error('Login error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
                submitBtn.disabled = false;
                btnText.style.display = 'inline';
                btnSpinner.style.display = 'none';
            }
        }

        // Set focus on load
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('login').focus();
        });
    </script>
</body>
</html>
