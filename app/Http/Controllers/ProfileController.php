<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function index()
    {
        return view('profile.index');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
        ];

        $requireCurrentPassword = ! $user->google_id;

        if ($request->filled('password')) {
            if ($requireCurrentPassword) {
                $rules['current_password'] = ['required', 'string'];
            }
            $rules['password'] = ['required', 'string', 'confirmed', Password::defaults()];
        }

        $validated = $request->validate($rules);

        $user->name = $validated['name'];
        $user->save();

        if ($request->filled('password')) {
            if ($requireCurrentPassword && ! Hash::check($validated['current_password'] ?? '', $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }
            $user->password = $validated['password'];
            $user->save();
        }

        $message = 'Profile updated.';
        if ($request->filled('password')) {
            $message = 'Profile and password updated.';
        }

        return back()->with('success', $message);
    }
}
