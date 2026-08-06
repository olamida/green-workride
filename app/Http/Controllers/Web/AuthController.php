<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Workplace;
use App\Services\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if (auth()->user()->is_banned) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'This account has been suspended. Contact support@workride.ng.',
                ])->onlyInput('email');
            }

            return redirect()->intended(route('go'));
        }

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register', [
            'workplaces' => Workplace::orderBy('name')->get(),
        ]);
    }

    public function register(Request $request, VerificationService $verificationService)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in(array_column(UserRole::assignableCases(), 'value'))],
            'workplace_id' => ['nullable', 'exists:workplaces,id'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => $data['role'],
            'workplace_id' => $data['workplace_id'] ?? null,
            'verification_level' => 0,
        ]);

        Wallet::create(['user_id' => $user->id]);

        // If the user selected their workplace, auto-submit a workplace verification.
        if (! empty($data['workplace_id'])) {
            $verificationService->submitWorkplace($user, $data['workplace_id']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('go');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in failed. Please try again.',
            ]);
        }

        $user = User::firstOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'avatar' => $googleUser->getAvatar(),
                'password' => Str::random(32),
                'role' => UserRole::Passenger,
            ],
        );

        Wallet::firstOrCreate(['user_id' => $user->id]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('go');
    }
}
