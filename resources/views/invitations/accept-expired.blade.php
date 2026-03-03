<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Invalid - Mjengo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 text-center">
                    <h4 class="mb-2">Invitation Not Available</h4>
                    <p class="text-muted mb-3">This invitation link is expired, already used, or invalid.</p>
                    <a href="{{ route('login') }}" class="btn btn-outline-primary">Go to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
