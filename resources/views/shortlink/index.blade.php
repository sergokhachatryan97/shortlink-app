<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="heleket" content="89c70a02" />
    <title>Trastly</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light min-vh-100 d-flex align-items-center justify-content-center p-4">
    <div class="container" style="max-width: 420px;">
        <h1 class="h4 fw-semibold mb-2">Shortlink Generator</h1>
        <p class="text-muted mb-4 small">
            Free trial (one-time per device): up to 50 links. Same device, any browser = one trial total. After that, payment required.
        </p>

        @if (session('error'))
            <div class="alert alert-danger mb-4 py-3 small">
                {{ session('error') }}
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success mb-4 py-3 small">
                {{ session('success') }}
            </div>
        @endif

        @if (session('download_ready'))
            <div class="alert alert-success mb-4 py-3">
                <a href="{{ route('shortlink.download') }}" class="text-decoration-none fw-medium d-inline-flex align-items-center gap-2">
                    <svg class="flex-shrink-0" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download links as CSV (Excel)
                </a>
            </div>
        @endif

        <form id="shortlink-form" class="d-flex flex-column gap-3">
            @csrf
            <input type="hidden" name="fingerprint" id="fingerprint" value="">
            <div>
                <label for="url" class="form-label small fw-medium">Link (URL)</label>
                <input type="url" id="url" name="url" required placeholder="https://example.com"
                       class="form-control">
            </div>
            <div>
                <label for="count" class="form-label small fw-medium">Quantity</label>
                <input type="number" id="count" name="count" required min="1" max="1000" value="10"
                       class="form-control">
                <p class="form-text small text-muted mt-1">Free trial: 1–50 links (once per device, any browser). Above 50 or after trial used: payment required.</p>
            </div>
            <button type="submit" id="generate-btn" class="btn btn-primary">
                Generate Links
            </button>
        </form>
    </div>

    <script>
        function simpleFingerprint() {
            const data = [
                (screen.width || 0) + 'x' + (screen.height || 0),
                (screen.availWidth || 0) + 'x' + (screen.availHeight || 0),
                (screen.colorDepth || 0),
                (screen.pixelDepth || 0),
                new Date().getTimezoneOffset(),
                (navigator.hardwareConcurrency || 0),
                (navigator.deviceMemory || 0),
                navigator.language,
                (navigator.languages && navigator.languages[0]) || navigator.language
            ].join('|');
            let hash = 0;
            for (let i = 0; i < data.length; i++) {
                const c = data.charCodeAt(i);
                hash = ((hash << 5) - hash) + c;
                hash = hash >>> 0;
            }
            return 'device_' + hash.toString(36);
        }
        document.getElementById('fingerprint').value = simpleFingerprint();

        document.getElementById('shortlink-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('generate-btn');
            const fpInput = document.getElementById('fingerprint');
            if (!fpInput.value) fpInput.value = simpleFingerprint();

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Processing';

            try {
                const formData = new FormData(e.target);
                const res = await fetch('{{ route('shortlink.generate') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                const data = await res.json();

                if (data.requires_payment && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }

                if (data.success && data.download_url) {
                    const a = document.createElement('a');
                    a.href = data.download_url;
                    a.download = 'shortlinks.csv';
                    a.style.display = 'none';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);

                    btn.disabled = false;
                    btn.innerHTML = 'Generate Links';

                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success mb-4 py-3 small';
                    alertDiv.innerHTML = '<strong>Done!</strong> Your file has been downloaded.';
                    document.querySelector('.container').insertBefore(alertDiv, document.getElementById('shortlink-form'));
                    setTimeout(function() { alertDiv.remove(); }, 5000);
                    return;
                }

                alert(data.message || 'Something went wrong');
            } catch (err) {
                alert('Something went wrong. Please try again.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Generate Links';
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
