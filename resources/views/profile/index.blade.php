@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="container" style="max-width: 520px;">
    <h1 class="mb-2 fw-bold" style="font-size: 1.75rem; color: #1e293b;">Profile</h1>
    <p class="text-muted mb-4">Update your name and password.</p>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius: 12px;">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}">
        @csrf

        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-3" style="color: #1e293b;">Name</h5>
                <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" class="form-control" required maxlength="255" style="border-radius: 10px;">
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-3" style="color: #1e293b;">Change password</h5>
                <p class="text-muted small mb-3">Leave blank to keep your current password. {{ auth()->user()->google_id ? 'You can set a password to sign in with email.' : '' }}</p>
                @if (!auth()->user()->google_id)
                <div class="mb-3">
                    <label for="current_password" class="form-label">Current password</label>
                    <input type="password" name="current_password" id="current_password" class="form-control" autocomplete="current-password" style="border-radius: 10px;">
                </div>
                @endif
                <div class="mb-3">
                    <label for="password" class="form-label">New password</label>
                    <input type="password" name="password" id="password" class="form-control" autocomplete="new-password" style="border-radius: 10px;">
                </div>
                <div>
                    <label for="password_confirmation" class="form-label">Confirm new password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" autocomplete="new-password" style="border-radius: 10px;">
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary px-4" style="background: linear-gradient(135deg, var(--brand), var(--accent)); border: none; border-radius: 10px; font-weight: 600;">Save changes</button>
    </form>
</div>
@endsection
