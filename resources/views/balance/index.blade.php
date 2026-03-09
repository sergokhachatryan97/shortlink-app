@extends('layouts.app')

@section('title', 'Balance')

@section('content')
<div class="container" style="max-width: 560px;">
    <h1 class="mb-2 fw-bold" style="font-size: 1.75rem; color: #1e293b;">Balance</h1>
    <p class="text-muted mb-4">Top up your balance to pay for link generation.</p>

    @if (session('success'))
        <div class="alert alert-success border-0 mb-4" style="border-radius: 10px;">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger border-0 mb-4" style="border-radius: 10px;">{{ session('error') }}</div>
    @endif

    <div class="card mb-4 border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body p-4">
            <p class="text-muted mb-1 small">Your balance</p>
            <h2 class="mb-0 fw-bold" style="color: #1e293b;">${{ number_format($balance, 2) }} USD</h2>
        </div>
    </div>

    <div class="card mb-4 border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body p-4">
            <h5 class="card-title fw-bold mb-3" style="color: #1e293b;">Add funds (Tron)</h5>
            <form id="topup-form">
                @csrf
                <div class="mb-3">
                    <label for="amount" class="form-label">Amount (USD)</label>
                    <input type="number" step="0.01" min="0.10" max="10000" class="form-control" id="amount" name="amount" value="10" required>
                </div>
                <button type="submit" class="btn btn-primary px-4" id="topup-btn" style="background: linear-gradient(135deg, var(--brand), var(--accent)); border: none; border-radius: 10px; font-weight: 600;">Pay with Tron</button>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body p-4">
            <h5 class="card-title fw-bold mb-3" style="color: #1e293b;">Recent transactions</h5>
            @forelse($transactions as $tx)
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span>{{ $tx->order_id }}</span>
                    <span>${{ number_format($tx->amount, 2) }} - {{ $tx->status }}</span>
                </div>
            @empty
                <p class="text-muted mb-0">No transactions yet.</p>
            @endforelse
        </div>
    </div>
</div>

@if(config('services.coinrush.store_key'))
<script src="{{ asset('js/tron-payment.js') }}"></script>
<script>
document.getElementById('topup-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('topup-btn');
    const amount = parseFloat(document.getElementById('amount').value);
    const prepareUrl = '{{ route("balance.topup.prepare") }}';
    const successUrl = '{{ route("balance.tron.success") }}';
    const storeKey = @json(config('services.coinrush.store_key'));
    const apiUrl = @json(config('services.coinrush.api_url', 'https://coinrush.link/store'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;

    btn.disabled = true;
    let orderId;
    try {
        const res = await fetch(prepareUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ amount: amount }),
            credentials: 'same-origin'
        });
        const data = await res.json();
        if (!res.ok || !data.order_id) throw new Error(data.error || 'Failed');
        orderId = data.order_id;
    } catch (err) {
        btn.disabled = false;
        alert(err.message);
        return;
    }

    if (!window.TronPayment) { alert('Widget not loaded'); btn.disabled = false; return; }
    TronPayment.init({ storeKey, apiUrl });
    TronPayment.openPayment({
        transactionId: orderId,
        amount: amount,
        asset: 'USDT',
        onSuccess: function(p) {
            window.location.href = successUrl + '?order_id=' + orderId;
        },
        onError: function(err) { alert(err.message); },
        onCancel: function() { btn.disabled = false; }
    });
});
</script>
@endif
@endsection
