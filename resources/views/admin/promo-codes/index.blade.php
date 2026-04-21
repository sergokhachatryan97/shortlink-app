<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Promo codes — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">← Admin</a>
            <span class="text-white-50 small">Promo codes</span>
        </div>
    </nav>
    <div class="container py-4">
        @if (session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif

        <div class="card mb-4">
            <div class="card-header fw-semibold">Create promo code</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.promo-codes.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="auto_generate" value="1" id="auto_generate">
                            <label class="form-check-label" for="auto_generate">Generate code automatically</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Code (if not auto)</label>
                        <input type="text" name="code" class="form-control form-control-sm" maxlength="64" placeholder="SUMMER2026">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Discount type</label>
                        <select name="discount_type" class="form-select form-select-sm" required>
                            <option value="percent">Percent %</option>
                            <option value="fixed">Fixed USD</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Value</label>
                        <input type="number" name="discount_value" step="0.01" min="0" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked>
                            <label class="form-check-label small" for="is_active">Active</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Expires (optional)</label>
                        <input type="datetime-local" name="expires_at" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Max uses (optional)</label>
                        <input type="number" name="max_uses" min="1" class="form-control form-control-sm" placeholder="Unlimited">
                    </div>
                    <div class="col-md-4 d-flex flex-column gap-2 pt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="once_per_user" value="1" id="once_per_user">
                            <label class="form-check-label small" for="once_per_user">One use per user</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="first_purchase_only" value="1" id="first_purchase_only">
                            <label class="form-check-label small" for="first_purchase_only">First subscription only</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small">Limit to plans (leave empty = all plans)</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($plans as $plan)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="applies_to_plan_ids[]" value="{{ $plan->id }}" id="plan_{{ $plan->id }}">
                                    <label class="form-check-label small" for="plan_{{ $plan->id }}">{{ $plan->name }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm">Create</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span class="fw-semibold">All promo codes</span>
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search code" style="width: 160px;">
                    <select name="active" class="form-select form-select-sm" style="width: 120px;">
                        <option value="">Any status</option>
                        <option value="1" @selected(request('active') === '1')>Active</option>
                        <option value="0" @selected(request('active') === '0')>Inactive</option>
                    </select>
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Filter</button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Uses</th>
                                <th>Max</th>
                                <th>Expires</th>
                                <th>Flags</th>
                                <th>Active</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($promoCodes as $p)
                                <tr>
                                    <td><code>{{ $p->code }}</code></td>
                                    <td>{{ $p->discount_type }}</td>
                                    <td>{{ $p->discount_type === 'percent' ? $p->discount_value.'%' : '$'.$p->discount_value }}</td>
                                    <td>{{ $p->usages_count }}</td>
                                    <td>{{ $p->max_uses ?? '—' }}</td>
                                    <td class="small">{{ $p->expires_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td class="small">
                                        @if ($p->once_per_user) <span class="badge bg-secondary">1× user</span> @endif
                                        @if ($p->first_purchase_only) <span class="badge bg-secondary">1st sub</span> @endif
                                        @if ($p->applies_to_plan_ids) <span class="badge bg-info text-dark">Plans</span> @endif
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.promo-codes.update', $p) }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="is_active" value="{{ $p->is_active ? '0' : '1' }}">
                                            <button type="submit" class="btn btn-sm {{ $p->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                                {{ $p->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td><a href="{{ route('admin.promo-codes.show', $p) }}" class="small">History</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-muted p-3">No promo codes yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($promoCodes->hasPages())
                <div class="card-footer py-2">{{ $promoCodes->links() }}</div>
            @endif
        </div>
    </div>
</body>
</html>
