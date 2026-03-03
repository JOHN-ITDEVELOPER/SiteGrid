<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accept Invitation - Mjengo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h4 class="mb-2">Accept Site Invitation</h4>
                    <p class="text-muted">You were invited to join <strong>{{ $invitation->site->name }}</strong> as <strong>{{ $invitation->role }}</strong>.</p>

                    @if($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('invites.accept.submit', $invitation->token) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input class="form-control" name="phone" value="{{ old('phone', $invitation->phone) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input class="form-control" name="name" value="{{ old('name') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password (for new account)</label>
                            <input type="password" class="form-control" name="password">
                            <small class="text-muted">Optional if you already have an account with this phone.</small>
                        </div>

                        <button class="btn btn-primary w-100">Accept Invitation</button>
                    </form>

                    <div class="mt-3 text-muted small">This link expires on {{ $invitation->expires_at?->format('d M Y H:i') }}.</div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
