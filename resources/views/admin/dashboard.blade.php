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
        <div class="container d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span class="navbar-brand mb-0">Shortlink Admin</span>
            <div class="d-flex align-items-center gap-2">
                @if (($adminRole ?? 'super_admin') === 'super_admin')
                    <span class="badge bg-warning text-dark">Super admin</span>
                @else
                    <span class="badge bg-info text-dark">Admin</span>
                @endif
                <a href="{{ route('admin.logout') }}" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>
    @php
        $activeTab = request()->get('tab', 'settings');
        if (($adminRole ?? 'super_admin') !== 'super_admin' && in_array($activeTab, ['transactions', 'partner-payouts'], true)) {
            $activeTab = 'settings';
        }
    @endphp
    <div class="container py-4">
        @if (session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger py-2">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'settings' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'settings']) }}">Settings</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'users' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'users']) }}">User list</a>
            </li>
            @if (($adminRole ?? 'super_admin') === 'super_admin')
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'transactions' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'transactions']) }}">Transactions</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'partner-payouts' ? 'active' : '' }}" href="{{ route('admin.dashboard', ['tab' => 'partner-payouts']) }}">Partner payouts</a>
                </li>
            @endif
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
                                <label class="form-label small">Partner payout provider</label>
                                <input type="text" class="form-control form-control-sm" value="Heleket — USDT (TRC20) only" readonly style="max-width: 220px;">
                                <input type="hidden" name="partner_default_payout_provider" value="heleket">
                                <small class="text-muted">Partners receive USDT (TRC20) via Heleket. Valid addresses only (start with T, 34 chars).</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Partner commission %</label>
                                <input type="number" name="partner_default_commission_percent" step="0.01" min="0" max="100"
                                       value="{{ $partnerDefaultCommissionPercent ?? 10 }}" class="form-control" style="max-width: 120px;">
                                <small class="text-muted">Commission rate for all partners. Override per partner in User list.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Partner minimum payout (USD)</label>
                                <input type="number" name="partner_min_payout_amount" step="0.01" min="0" max="100000"
                                       value="{{ $partnerMinPayoutAmount ?? 100 }}" class="form-control" style="max-width: 120px;" required>
                                <small class="text-muted">Payout only when batch total reaches this amount. Below = stays pending.</small>
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
            <div class="card-header fw-semibold">Subscription plans (name & description per language)</div>
            <div class="card-body p-0">
                @foreach ($plans ?? [] as $plan)
                @php
                    $nameTrans = $plan->name_translations ?? [];
                    $descTrans = $plan->description_translations ?? [];
                @endphp
                <div class="p-3 border-bottom">
                    <form method="POST" action="{{ route('admin.plans.update', $plan) }}">
                        @csrf
                        <input type="hidden" name="tab" value="settings">
                        <div class="d-flex flex-wrap align-items-start gap-3 mb-2">
                            <strong class="text-nowrap">{{ $plan->slug }}</strong>
                            @if ($plan->links_limit == 0)
                                <span class="badge bg-secondary">Unlimited</span>
                            @endif
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-12 small fw-semibold text-muted">Name</div>
                            <div class="col-md-4">
                                <label class="form-label small mb-0">EN</label>
                                <input type="text" name="name_en" value="{{ old('name_en', $nameTrans['en'] ?? $plan->name) }}" class="form-control form-control-sm" placeholder="Name (English)">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-0">中文</label>
                                <input type="text" name="name_zh" value="{{ old('name_zh', $nameTrans['zh'] ?? '') }}" class="form-control form-control-sm" placeholder="Name (Chinese)">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-0">RU</label>
                                <input type="text" name="name_ru" value="{{ old('name_ru', $nameTrans['ru'] ?? '') }}" class="form-control form-control-sm" placeholder="Name (Russian)">
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-12 small fw-semibold text-muted">Description</div>
                            <div class="col-md-4">
                                <label class="form-label small mb-0">EN</label>
                                <textarea name="description_en" class="form-control form-control-sm" rows="2" placeholder="Description (English)">{{ old('description_en', $descTrans['en'] ?? $plan->description) }}</textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-0">中文</label>
                                <textarea name="description_zh" class="form-control form-control-sm" rows="2" placeholder="Description (Chinese)">{{ old('description_zh', $descTrans['zh'] ?? '') }}</textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-0">RU</label>
                                <textarea name="description_ru" class="form-control form-control-sm" rows="2" placeholder="Description (Russian)">{{ old('description_ru', $descTrans['ru'] ?? '') }}</textarea>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <label class="small mb-0">Links limit</label>
                            <input type="number" name="links_limit" value="{{ old('links_limit', $plan->links_limit) }}" min="0" step="1" class="form-control form-control-sm" style="width: 80px;" title="0 = unlimited">
                            <label class="small mb-0">Price (USD)</label>
                            <input type="number" name="price_usd" value="{{ old('price_usd', $plan->price_usd) }}" min="0" step="0.01" class="form-control form-control-sm" style="width: 90px;">
                            <button type="submit" class="btn btn-sm btn-primary">Save plan</button>
                        </div>
                    </form>
                </div>
                @endforeach
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
                                <th>Payout (USDT)</th>
                                <th>Commission %</th>
                                <th>Balance</th>
                                @if (($adminRole ?? 'super_admin') === 'super_admin')
                                    <th>Add balance</th>
                                @endif
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
                                        <span class="small text-muted" title="USDT (TRC20) via Heleket">Heleket USDT</span>
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
                                    <td>${{ \App\Support\MoneyDisplay::plainDecimal($user->balance ?? 0) }}</td>
                                    @if (($adminRole ?? 'super_admin') === 'super_admin')
                                        <td>
                                            <form method="POST" action="{{ route('admin.users.add-balance') }}" class="d-inline-flex align-items-center gap-2">
                                                @csrf
                                                <input type="hidden" name="user" value="{{ $user->id }}">
                                                <input type="hidden" name="tab" value="users">
                                                <input type="number" name="amount" value="10" step="0.01" min="0.01" max="10000" class="form-control form-control-sm" style="width: 90px;" required>
                                                <button type="submit" class="btn btn-sm btn-success">Add</button>
                                            </form>
                                        </td>
                                    @endif
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

        {{-- Partners set their valid USDT (TRC20) wallet in Partner Dashboard --}}
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
                                <th>User</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Identifier</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $t)
                                @php
                                    $txUserId = (is_string($t->identifier ?? null) && str_starts_with($t->identifier, 'user:'))
                                        ? (int) substr($t->identifier, 5)
                                        : null;
                                    $txUser = $txUserId ? ($transactionUsersById[$txUserId] ?? null) : null;
                                @endphp
                                <tr>
                                    <td><code class="small">{{ $t->order_id }}</code></td>
                                    <td>
                                        @if ($txUser)
                                            <span class="fw-medium">{{ $txUser->name }}</span>
                                            <br><small class="text-muted">{{ $txUser->email }}</small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
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
                                <tr><td colspan="7" class="text-center text-muted py-4">No transactions yet</td></tr>
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
        @if (count($requestedWithdrawals ?? []) > 0)
        <div class="card mb-3 border-warning">
            <div class="card-header fw-semibold bg-light">Withdrawal requests (mark paid or reject)</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Partner</th>
                            <th>Amount</th>
                            <th>Wallet</th>
                            <th>Requested</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requestedWithdrawals as $w)
                        <tr>
                            <td>{{ $w['partner']->name ?? '—' }}<br><small class="text-muted">{{ $w['partner']->email }}</small><br><small>ID: {{ $w['partner']->id }}</small></td>
                            <td>${{ number_format($w['total'], 2) }} USDT</td>
                            <td><code class="small">{{ Str::limit($w['wallet'] ?? '—', 20) }}</code></td>
                            <td><small>{{ $w['requested_at']->format('Y-m-d H:i') }}</small></td>
                            <td class="small">
                                <form method="POST" action="{{ route('admin.partner-payouts.mark-paid') }}" class="d-inline-flex align-items-center gap-1 flex-wrap mb-1">
                                    @csrf
                                    <input type="hidden" name="partner_user_id" value="{{ $w['partner']->id }}">
                                    <input type="text" name="provider_transaction_id" class="form-control form-control-sm" style="width: 120px;" placeholder="Tx ID (optional)">
                                    <button type="submit" class="btn btn-sm btn-success">Mark paid</button>
                                </form>
                                <form method="POST" action="{{ route('admin.partner-payouts.reject') }}" class="d-inline-flex align-items-center gap-1 flex-wrap" onsubmit="return confirm('Reject this withdrawal request?');">
                                    @csrf
                                    <input type="hidden" name="partner_user_id" value="{{ $w['partner']->id }}">
                                    <input type="text" name="reason" class="form-control form-control-sm" style="width: 120px;" placeholder="Reason (optional)">
                                    <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
        <div class="card">
            <div class="card-header fw-semibold">Partner commission payouts (USDT TRC20)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Source user</th>
                                <th>Partner</th>
                                <th>Amount</th>
                                <th>Provider</th>
                                <th>Payment source</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($partnerPayouts ?? [] as $p)
                                <tr>
                                    <td>{{ $p?->id }}</td>
                                    <td>{{ $p?->sourceUser?->email ?? '#' . $p?->source_user_id }}</td>
                                    <td>{{ $p?->partnerUser?->email ?? '#' . $p?->partner_user_id }}</td>
                                    <td>${{ number_format($p->commission_amount, 2) }}</td>
                                    <td>{{ $p?->provider }}</td>
                                    <td><small class="text-muted">{{ $p?->source_provider ?? '—' }}</small></td>
                                    <td><span class="badge {{ $p->status === 'paid' ? 'bg-success' : ($p?->status === 'failed' ? 'bg-danger' : ($p->status === 'requested' ? 'bg-warning text-dark' : ($p->status === 'rejected' ? 'bg-danger' : 'bg-secondary'))) }}">{{ $p->status }}</span></td>
                                    <td><small>{{ $p?->updated_at?->format('Y-m-d H:i') }}</small></td>
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
