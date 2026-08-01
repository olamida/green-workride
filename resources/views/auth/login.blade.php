@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    <h1 class="font-heading text-2xl font-bold text-ink-900">Welcome back</h1>
    <p class="mt-1 text-sm text-ink-500">Sign in to see your rides and wallet.</p>

    @if ($errors->any())
        <div class="mt-4">
            @foreach ($errors->all() as $error)
                <x-flash type="error">{{ $error }}</x-flash>
            @endforeach
        </div>
    @endif

    @if (config('workride.google_enabled'))
        <a href="{{ route('auth.google') }}" class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl border border-ink-300 bg-white px-4 py-2.5 text-sm font-semibold text-ink-700 transition hover:bg-ink-50">
            <svg class="h-4 w-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1Z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23Z"/><path fill="#FBBC05" d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.18a11 11 0 0 0 0 9.88l3.66-2.84Z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1A11 11 0 0 0 2.18 7.06l3.66 2.84C6.71 7.31 9.14 5.38 12 5.38Z"/></svg>
            Continue with Google
        </a>
        <div class="my-5 flex items-center gap-3 text-xs text-ink-400">
            <span class="h-px flex-1 bg-ink-200"></span>
            or with email
            <span class="h-px flex-1 bg-ink-200"></span>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-4 space-y-4">
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium text-ink-700">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                class="mt-1 w-full rounded-xl border border-ink-300 px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-ink-700">Password</label>
            <input id="password" type="password" name="password" required
                class="mt-1 w-full rounded-xl border border-ink-300 px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
        </div>
        <label class="flex items-center gap-2 text-sm text-ink-600">
            <input type="checkbox" name="remember" class="rounded border-ink-300 text-forest-600 focus:ring-forest-100">
            Remember me
        </label>
        <button type="submit" class="w-full rounded-xl bg-forest-600 px-4 py-2.5 font-semibold text-white transition hover:bg-forest-700">
            Sign in
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-500">
        New to WorkRide?
        <a href="{{ route('register') }}" class="font-semibold text-forest-600 hover:underline">Create an account</a>
    </p>
@endsection
