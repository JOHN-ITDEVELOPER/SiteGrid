<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Complete your SiteGrid profile.">
     <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.ico') }}">
    <title>Complete Your Profile  SiteGrid</title>
    
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
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
        }

        .profile-container {
            width: 100%;
            max-width: 32rem;
            padding: 1rem;
        }

        .profile-card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
            padding: 2rem;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--primary-indigo);
            margin-bottom: 0.5rem;
        }

        .profile-header p {
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

        /* Alerts */
        .alert {
            border-radius: 0.375rem;
            border: none;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .alert-info {
            background-color: #dbeafe;
            color: #0c4a6e;
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

        /* Progress Indicator */
        .progress-step {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background-color: #f3f4f6;
            border-radius: 0.375rem;
        }

        .step-indicator {
            width: 2.5rem;
            height: 2.5rem;
            background-color: #10b981;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }

        .step-text h4 {
            margin: 0 0 0.25rem 0;
            font-size: 0.95rem;
            color: var(--primary-indigo);
            font-weight: 600;
        }

        .step-text p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Success Animation */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-banner {
            animation: slideIn 0.5s ease-out;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
            font-weight: 500;
        }

        .success-banner svg {
            width: 1.5rem;
            height: 1.5rem;
            flex-shrink: 0;
            animation: slideIn 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-card">
            <!-- Logo -->
            <div class="header-logo">
                <img src="{{ asset('images/logo.png') }}" alt="SiteGrid Logo">
            </div>

            <!-- Header -->
            <div class="profile-header">
                <h1>Complete Your Profile</h1>
                <p>Just a few details to get your account ready</p>
            </div>

            @if(session('verified_success'))
                <div class="success-banner">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                    </svg>
                    <span>{{ session('status', 'Email verified successfully!') }}</span>
                </div>
            @elseif(session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            <!-- Progress -->
            <div class="progress-step">
                <div class="step-indicator">✓</div>
                <div class="step-text">
                    <h4>Step 1: Email Verified</h4>
                    <p>Your email has been verified successfully</p>
                </div>
            </div>

            <!-- Profile Form -->
            <form id="profileForm" action="{{ route('profile.store') }}" method="POST">
                @csrf

                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $error)
                            <p class="mb-1">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                           placeholder="Full names" required value="{{ old('name', Auth::user()->name) }}">
                    <small class="form-help">Your full name or business owner name</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number (Optional)</label>
                    <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" 
                           placeholder="+254 7XXXXXXXX" value="{{ old('phone', Auth::user()->phone) }}">
                    <small class="form-help">We'll use this for account notifications</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Company Name (Optional)</label>
                    <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror" 
                           placeholder="Your company name" value="{{ old('company_name') }}">
                    <small class="form-help">The name of your business or organization</small>
                </div>

                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <span id="submitText">Complete Profile</span>
                    <span id="submitLoader" class="d-none">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Saving...
                    </span>
                </button>
            </form>

            <!-- Help Text -->
            <div style="margin-top: 1.5rem; padding: 1rem; background-color: #f3f4f6; border-radius: 0.375rem;">
                <p style="margin: 0; font-size: 0.85rem; color: var(--text-dark);">
                    ℹ️ You can update these details anytime in your account settings.
                </p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation and loading state
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const name = document.querySelector('input[name="name"]').value.trim();

            if (!name) {
                e.preventDefault();
                alert('Full name is required');
                return false;
            }

            if (name.length < 2) {
                e.preventDefault();
                alert('Full name must be at least 2 characters');
                return false;
            }

            // Show loading state
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
