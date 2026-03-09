<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirming Payment - Shortlink Generator</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); min-height: 100vh; }
        .spinner { width: 40px; height: 40px; border: 3px solid #e2e8f0; border-top-color: #6366f1; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body class="min-vh-100" style="padding-top: 64px;">
    @include('components.navbar')
    <div class="d-flex align-items-center justify-content-center p-4" style="min-height: calc(100vh - 64px);">
    <div class="container text-center" style="max-width: 400px;">
        <div class="spinner mx-auto mb-3"></div>
        <h5 class="fw-semibold text-dark mb-2">Confirming your payment</h5>
        <p class="text-muted mb-4">This usually takes a few seconds. Please wait...</p>
        <a href="{{ route('shortlink.index') }}" class="btn btn-outline-secondary">Back to generator</a>
    </div>
    </div>
    <script>
        (function() {
            const pollUrl = @json($pollUrl);
            const interval = setInterval(async function() {
                try {
                    const r = await fetch(pollUrl);
                    const data = await r.json();
                    if (data.status === 'paid' || data.status === 'failed') {
                        clearInterval(interval);
                        window.location.reload();
                    }
                } catch (e) {}
            }, 2500);
        })();
    </script>
</body>
</html>
