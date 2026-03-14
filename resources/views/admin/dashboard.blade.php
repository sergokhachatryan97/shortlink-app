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
    @php
        $activeTab = request()->get('tab', 'settings');
    @endphp
    <div class="container py-4">
        @if (session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'settings' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'settings']) }}">Settings</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'users' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'users']) }}">User list</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'transactions' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'transactions']) }}">Transactions</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'partner-payouts' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'partner-payouts']) }}">Partner payouts</a>
            </li>
        </ul>

        @if ($activeTab === 'settings')
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header fw-semibold">Settings</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.settings.update') }}">
                            @csrf
                            <input type="hidden" name="tab" value="settings">
                            <div class="mb-3">
                                <label class="form-label small">Price per link (USD)</label>
                                <input type="number" name="price_per_link" step="0.001" min="0.001"
                                       value="{{ $pricePerLink }}" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Global default payout provider</label>
                                <select name="partner_default_payout_provider" class="form-select">
                                    <option value="heleket" {{ ($partnerDefaultPayoutProvider ?? 'heleket') === 'heleket' ? 'selected' : '' }}>Heleket</option>
                                    <option value="coinrush" {{ ($partnerDefaultPayoutProvider ?? '') === 'coinrush' ? 'selected' : '' }}>CoinRush</option>
                                </select>
                                <small class="text-muted">Applied to all partners unless overridden per user.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Global default commission %</label>
                                <input type="number" name="partner_default_commission_percent" step="0.01" min="0" max="100"
                                       value="{{ $partnerDefaultCommissionPercent ?? 10 }}" class="form-control" style="max-width: 120px;">
                                <small class="text-muted">Applied to all partners unless overridden per payout setting.</small>
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

        <div class="card mb-4">
            <div class="card-header fw-semibold">Subscription plans</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Plan</th>
                                <th>Description · Links limit · Price (USD)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($plans ?? [] as $plan)
                                <tr>
                                    <td class="align-top pt-3">
                                        <strong>{{ $plan->name }}</strong>
                                        @if ($plan->links_limit == 0)
                                            <span class="badge bg-secondary ms-1">Unlimited</span>
                                        @endif
                                    </td>
                                    <td class="align-top" colspan="2">
                                        <form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="d-flex flex-wrap align-items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="tab" value="settings">
                                            <input type="text" name="description" value="{{ old('description', $plan->description) }}" class="form-control form-control-sm" placeholder="Plan description" style="min-width: 200px;">
                                            <input type="number" name="links_limit" value="{{ $plan->links_limit }}" min="0" step="1" class="form-control form-control-sm" style="width: 80px;" title="0 = unlimited">
                                            <input type="number" name="price_usd" value="{{ $plan->price_usd }}" min="0" step="0.01" class="form-control form-control-sm" style="width: 90px;">
                                            <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if (empty($plans) || $plans->isEmpty())
                    <p class="text-muted text-center py-3 mb-0">No subscription plans. Run SubscriptionPlanSeeder.</p>
                @endif
            </div>
        </div>
        @endif

        @if ($activeTab === 'users')
        <div class="card mb-4">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span>Users</span>
                <span class="badge bg-secondary">{{ $users->total() }} users</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Email</th>
                                <th>Name</th>
                                <th>Partner</th>
                                <th>Payout</th>
                                <th>Commission %</th>
                                <th>Balance</th>
                                <th>Add balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td><code class="small">{{ $user->id }}</code></td>
                                    <td>{{ $user->email ?? '—' }}</td>
                                    <td>{{ $user->name ?? '—' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.users.set-partner') }}" class="d-inline-flex align-items-center gap-1">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                                            <input type="hidden" name="tab" value="users">
                                            <input type="number" name="partner_id" value="{{ $user->partner_id }}" min="0" placeholder="0=clear" class="form-control form-control-sm" style="width: 80px;">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Set</button>
                                        </form>
                                    </td>
                                    <td>
                                        @if($user->is_partner)
                                        <form method="POST" action="{{ route('admin.users.set-payout-provider') }}" class="d-inline-flex align-items-center gap-1">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                                            <input type="hidden" name="tab" value="users">
                                            <select name="payout_provider" class="form-select form-select-sm" style="width: 100px;">
                                                <option value="">Default</option>
                                                <option value="heleket" {{ ($user->payout_provider ?? '') === 'heleket' ? 'selected' : '' }}>Heleket</option>
                                                <option value="coinrush" {{ ($user->payout_provider ?? '') === 'coinrush' ? 'selected' : '' }}>CoinRush</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Set</button>
                                        </form>
                                        @else
                                        —
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->is_partner)
                                        <form method="POST" action="{{ route('admin.users.set-commission-percent') }}" class="d-inline-flex align-items-center gap-1">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                                            <input type="hidden" name="tab" value="users">
                                            <input type="number" name="commission_percent" step="0.01" min="0" max="100" value="{{ $user->commission_percent ?? '' }}" placeholder="—" class="form-control form-control-sm" style="width: 70px;" title="Leave empty for global default">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Set</button>
                                        </form>
                                        @else
                                        —
                                        @endif
                                    </td>
                                    <td>${{ number_format($user->balance ?? 0, 2) }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.users.add-balance') }}" class="d-inline-flex align-items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="user" value="{{ $user->id }}">
                                            <input type="hidden" name="tab" value="users">
                                            <input type="number" name="amount" value="10" step="0.01" min="0.01" max="10000" class="form-control form-control-sm" style="width: 90px;" required>
                                            <button type="submit" class="btn btn-sm btn-success">Add</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($users->isEmpty())
                    <p class="text-muted text-center py-4 mb-0">No users yet</p>
                @else
                    <div class="p-2">
                        {{ $users->appends(['tab' => 'users'])->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
        @endif

        @if ($activeTab === 'transactions')
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
                    {{ $transactions->appends(['tab' => 'transactions'])->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
        @endif

        @if ($activeTab === 'partner-payouts')
        <div class="card">
            <div class="card-header fw-semibold">Partner commission payouts</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Source user</th>
                                <th>Partner</th>
                                <th>Amount</th>
                                <th>Payout</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($partnerPayouts as $p)
                                <tr>
                                    <td>{{ $p->id }}</td>
                                    <td>{{ $p->sourceUser?->email ?? '#' . $p->source_user_id }}</td>
                                    <td>{{ $p->partnerUser?->email ?? '#' . $p->partner_user_id }}</td>
                                    <td>${{ number_format($p->commission_amount, 2) }}</td>
                                    <td>{{ $p->provider }}</td>
                                    <td><small class="text-muted">{{ $p->source_provider ?? '—' }}</small></td>
                                    <td><span class="badge {{ $p->status === 'paid' ? 'bg-success' : ($p->status === 'failed' ? 'bg-danger' : 'bg-secondary') }}">{{ $p->status }}</span></td>
                                    <td><small>{{ $p->created_at->format('Y-m-d H:i') }}</small></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">No partner payouts yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-2">
                    {{ $partnerPayouts->appends(['tab' => 'partner-payouts'])->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
        @endif
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
