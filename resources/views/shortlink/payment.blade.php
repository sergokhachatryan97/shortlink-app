<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Required - Shortlink Generator</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light min-vh-100 d-flex align-items-center justify-content-center p-4">
    <div class="container" style="max-width: 420px;">
        <a href="{{ route('shortlink.index') }}" class="small text-primary text-decoration-none mb-4 d-inline-block">← Back</a>

        <h1 class="h4 fw-semibold mb-2">Payment Required</h1>
        <p class="text-muted mb-4 small">
            @if($freeTrialExhausted ?? false)
                You have already used your free trial. Generating <strong>{{ $count }}</strong> links requires payment.
            @else
                Generating <strong>{{ $count }}</strong> links ({{ $count - $freeLimit }} over free limit of {{ $freeLimit }}) requires payment.
            @endif
        </p>

        @if (session('error'))
            <div class="alert alert-danger mb-4 py-3 small">
                {{ session('error') }}
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-body">
                <p class="small text-muted mb-0">URL: <span class="text-dark text-break">{{ $url }}</span></p>
                <p class="mb-0 mt-2 h5">Amount: ${{ number_format($amount, 2) }} USD</p>
            </div>
        </div>

        <form method="POST" action="{{ route('shortlink.payment.initiate') }}">
            @csrf
            <button type="submit" class="btn btn-warning w-100">
                Pay with Crypto (Heleket)
            </button>
        </form>

        <p class="mt-4 small text-muted text-center">
            Powered by <a href="https://doc.heleket.com/" target="_blank" class="underline">Heleket</a> – Bitcoin, ETH, USDT & more.
        </p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
