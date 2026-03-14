<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
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

        $partnerId = $this->resolveReferralPartner($request);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'partner_id' => $partnerId,
        ]);

        $this->clearReferralAttribution($request);

        Auth::login($user);

        return redirect()
            ->intended(route('shortlink.index'))
            ->cookie(Cookie::forget('referral_code'));
    }

    private function resolveReferralPartner(Request $request): ?int
    {

        $code = $request->session()->get('referral_code');

        if (!$code) {
            return null;
        }

        $referralAt = $request->session()->get('referral_code_at');
        if ($referralAt && (now()->timestamp - $referralAt) > 60 * 60 * 24 * 30) {
            return null;
        }

        $partner = User::where('referral_code', strtoupper($code))
            ->where('is_partner', true)
            ->first();

        if (!$partner) {
            return null;
        }

        return $partner->id;
    }

    private function clearReferralAttribution(Request $request): void
    {
        $request->session()->forget(['referral_code', 'referral_code_at']);
    }

    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();

        $partnerId = $this->resolveReferralPartner($request);
        $isNewUser = !User::where('google_id', $googleUser->getId())->exists();

        $attrs = [
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'avatar' => $googleUser->getAvatar(),
            'password' => Hash::make(Str::random(32)),
        ];
        if ($isNewUser && $partnerId) {
            $attrs['partner_id'] = $partnerId;
        }

        $user = User::updateOrCreate(
            ['google_id' => $googleUser->getId()],
            $attrs
        );

        if ($isNewUser && $partnerId) {
            $this->clearReferralAttribution($request);
        }

        Auth::login($user, true);

        $redirect = redirect()->intended(route('shortlink.index'));
        if ($isNewUser && $partnerId) {
            $redirect->cookie(Cookie::forget('referral_code'));
        }
        return $redirect;
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

        $partnerId = $this->resolveReferralPartner($request);
        $isNewUser = !User::where('telegram_id', (string) $id)->exists();

        $attrs = [
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
        ];
        if ($isNewUser && $partnerId) {
            $attrs['partner_id'] = $partnerId;
        }

        $user = User::updateOrCreate(
            ['telegram_id' => (string) $id],
            $attrs
        );

        if ($isNewUser && $partnerId) {
            $this->clearReferralAttribution($request);
        }

        Auth::login($user, true);

        $redirect = redirect()->intended(route('shortlink.index'));
        if ($isNewUser && $partnerId) {
            $redirect->cookie(Cookie::forget('referral_code'));
        }
        return $redirect;
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('shortlink.index');
    }
}
