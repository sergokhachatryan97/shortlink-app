<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $promoCode->code }} — Usage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ route('admin.promo-codes.index') }}">← Promo codes</a>
        </div>
    </nav>
    <div class="container py-4">
        <h1 class="h4 mb-3"><code>{{ $promoCode->code }}</code></h1>
        <p class="text-muted small mb-4">
            Type: <strong>{{ $promoCode->discount_type }}</strong>
            @if ($promoCode->discount_type === 'percent') {{ $promoCode->discount_value }}% @else ${{ $promoCode->discount_value }} @endif
            · Total uses: <strong>{{ $promoCode->usages_count }}</strong>
            @if ($promoCode->max_uses) / max {{ $promoCode->max_uses }} @endif
        </p>

        <div class="card">
            <div class="card-header fw-semibold">Usage history</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>When</th>
                                <th>User</th>
                                <th>Context</th>
                                <th>Plan</th>
                                <th>Original</th>
                                <th>Discount</th>
                                <th>Final</th>
                                <th>Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($usages as $u)
                                <tr>
                                    <td class="small">{{ $u->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="small">#{{ $u->user_id }} {{ $u->user?->email }}</td>
                                    <td>{{ $u->context }}</td>
                                    <td>{{ $u->subscriptionPlan?->name }}</td>
                                    <td>${{ number_format((float) $u->original_amount, 2) }}</td>
                                    <td>${{ number_format((float) $u->discount_amount, 2) }}</td>
                                    <td>${{ number_format((float) $u->final_amount, 2) }}</td>
                                    <td class="small"><code>{{ $u->shortlink_transaction_order_id }}</code></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-muted p-3">No redemptions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($usages->hasPages())
                <div class="card-footer py-2">{{ $usages->links() }}</div>
            @endif
        </div>
    </div>
</body>
</html>
