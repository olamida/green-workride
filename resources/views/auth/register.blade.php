@extends('layouts.auth')

@section('title', 'Create account')

@section('content')
    <h1 class="font-heading text-2xl font-bold text-ink-900">Create your account</h1>
    <p class="mt-1 text-sm text-ink-500">Verify once. Ride every day.</p>

    @if ($errors->any())
        <div class="mt-4">
            @foreach ($errors->all() as $error)
                <x-flash type="error">{{ $error }}</x-flash>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="name" class="block text-sm font-medium text-ink-700">Full name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                class="mt-1 w-full rounded-xl border border-ink-300 px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-ink-700">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                class="mt-1 w-full rounded-xl border border-ink-300 px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
        </div>
        <div>
            <label for="phone" class="block text-sm font-medium text-ink-700">Phone <span class="text-ink-400">(optional)</span></label>
            <input id="phone" type="text" name="phone" value="{{ old('phone') }}"
                class="mt-1 w-full rounded-xl border border-ink-300 px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
        </div>
        <div>
            <label for="role" class="block text-sm font-medium text-ink-700">I am a…</label>
            <select id="role" name="role" required
                class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                <option value="passenger" {{ old('role') === 'passenger' ? 'selected' : '' }}>Passenger — I need a ride</option>
                <option value="volunteer" {{ old('role') === 'volunteer' ? 'selected' : '' }}>Volunteer — I give free rides</option>
                <option value="both" {{ old('role') === 'both' ? 'selected' : '' }}>Driver & Passenger</option>
            </select>
        </div>
        <div>
            <label for="workplace_id" class="block text-sm font-medium text-ink-700">Your MDAs / Workplace <span class="text-ink-400">(optional)</span></label>
            <select id="workplace_id" name="workplace_id"
                class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                <option value="">Select workplace</option>
                @foreach ($workplaces as $workplace)
                    <option value="{{ $workplace->id }}" {{ old('workplace_id') == $workplace->id ? 'selected' : '' }}>
                        {{ $workplace->name }} @if ($workplace->acronym)({{ $workplace->acronym }})@endif
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-ink-400">Selecting your workplace auto-submits a Level 1 verification.</p>
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-ink-700">Password</label>
            <input id="password" type="password" name="password" required
                class="mt-1 w-full rounded-xl border border-ink-300 px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-ink-700">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                class="mt-1 w-full rounded-xl border border-ink-300 px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
        </div>
        <button type="submit" class="w-full rounded-xl bg-forest-600 px-4 py-2.5 font-semibold text-white transition hover:bg-forest-700">
            Create account
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-500">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold text-forest-600 hover:underline">Sign in</a>
    </p>

    <p class="mt-4 text-center text-sm">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1 font-medium text-ink-500 transition hover:text-forest-600">
            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25h10.638A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
            Back to homepage
        </a>
    </p>
@endsection
