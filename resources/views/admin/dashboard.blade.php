<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Shortlink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand">Shortlink Admin</span>
            <a href="{{ route('admin.logout') }}" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </nav>
    <div class="container py-4">
        @if (session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header fw-semibold">Settings</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.settings.update') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label small">Price per link (USD)</label>
                                <input type="number" name="price_per_link" step="0.001" min="0.001"
                                       value="{{ $pricePerLink }}" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Minimum amount (USD)</label>
                                <input type="number" name="min_amount" step="0.01" min="0.01"
                                       value="{{ $minAmount }}" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header fw-semibold">Overview</div>
                    <div class="card-body">
                        <p class="mb-0"><strong>Total paid:</strong> ${{ number_format($totalPaid, 2) }} USD</p>
                        <p class="mb-0 mt-2"><strong>Transactions:</strong> {{ \App\Models\ShortlinkTransaction::count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header fw-semibold">Transactions</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Identifier</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $t)
                                <tr>
                                    <td><code class="small">{{ $t->order_id }}</code></td>
                                    <td>${{ number_format($t->amount, 2) }}</td>
                                    <td>
                                        <span class="badge {{ $t->status === 'paid' ? 'bg-success' : ($t->status === 'failed' ? 'bg-danger' : 'bg-secondary') }}">
                                            {{ $t->status }}
                                        </span>
                                    </td>
                                    <td>{{ $t->count }}</td>
                                    <td><small class="text-muted">{{ Str::limit($t->identifier ?? '-', 20) }}</small></td>
                                    <td><small>{{ $t->created_at->format('Y-m-d H:i') }}</small></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No transactions yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-2">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
