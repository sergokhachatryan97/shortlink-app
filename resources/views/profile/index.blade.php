@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="container" style="max-width: 560px;">
    <div class="page-header">
        <h1 class="page-title">Profile</h1>
        <p class="page-subtitle">Update your name and password.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 mb-4" style="border-radius: 12px;">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger border-0 mb-4" style="border-radius: 12px;">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}">
        @csrf

        <div class="card card-dashboard mb-4">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-3" style="color: #1e293b;">Manage account</h5>
                <div class="mb-0">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', auth()->user()->name) }}" class="form-control" required maxlength="255" style="border-radius: 10px;">
                </div>
                @if(auth()->user()->email)
                <div class="mt-3">
                    <label class="form-label">Email</label>
                    <input type="text" class="form-control" value="{{ auth()->user()->email }}" disabled style="border-radius: 10px; background: #f8fafc;">
                </div>
                @endif
            </div>
        </div>

        <div class="card card-dashboard mb-4">
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

        <button type="submit" class="btn btn-primary-gradient px-4 text-white">Save changes</button>
    </form>
</div>
@endsection
