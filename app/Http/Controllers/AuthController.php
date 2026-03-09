<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->intended(route('shortlink.index'));
        }
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('shortlink.index'));
        }

        return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
    }

    public function showRegisterForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->intended(route('shortlink.index'));
        }
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        return redirect()->intended(route('shortlink.index'));
    }

    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::updateOrCreate(
            ['google_id' => $googleUser->getId()],
            [
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'avatar' => $googleUser->getAvatar(),
                'password' => Hash::make(Str::random(32)),
            ]
        );

        Auth::login($user, true);

        return redirect()->intended(route('shortlink.index'));
    }

    public function telegram(Request $request): RedirectResponse
    {
        $data = $request->all();
        $id = $data['id'] ?? null;
        $firstName = $data['first_name'] ?? '';
        $lastName = $data['last_name'] ?? '';
        $username = $data['username'] ?? '';
        $hash = $data['hash'] ?? '';

        if (!$id || !$hash) {
            return redirect()->route('shortlink.index')->with('error', 'Invalid Telegram data');
        }

        $botToken = config('services.telegram.bot_token');
        if (!$botToken) {
            return redirect()->route('shortlink.index')->with('error', 'Telegram login not configured');
        }

        $checkHash = $hash;
        unset($data['hash']);
        ksort($data);
        $dataCheckString = implode("\n", array_map(fn ($k, $v) => "$k=$v", array_keys($data), $data));
        $secretKey = hash('sha256', $botToken, true);
        $computedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($computedHash, $checkHash)) {
            return redirect()->route('shortlink.index')->with('error', 'Invalid Telegram signature');
        }

        if (isset($data['auth_date']) && (time() - (int) $data['auth_date']) > 86400) {
            return redirect()->route('shortlink.index')->with('error', 'Telegram data expired');
        }

        $name = trim($firstName . ' ' . $lastName) ?: $username ?: 'Telegram User';
        $email = $username ? $username . '@telegram.user' : 'tg' . $id . '@telegram.user';

        $user = User::updateOrCreate(
            ['telegram_id' => (string) $id],
            [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
            ]
        );

        Auth::login($user, true);

        return redirect()->intended(route('shortlink.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('shortlink.index');
    }
}
