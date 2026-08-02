@extends('layouts.app')

@section('title', 'Profile & safety')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <h1 class="font-heading text-2xl font-bold text-ink-900">Profile & safety</h1>
            <p class="mt-1 text-sm text-ink-500">
                Emergency contact, gender preference and women-only rides. Your emergency contact is never shown to other riders.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-5 rounded-2xl border border-ink-200 bg-white p-6">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-ink-700">Full name</label>
                    <input id="name" name="name" type="text" maxlength="255" value="{{ old('name', $user->name) }}" required
                           class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                </div>
                <div>
                    <label for="phone" class="mb-1 block text-sm font-medium text-ink-700">Phone</label>
                    <input id="phone" name="phone" type="text" maxlength="30" value="{{ old('phone', $user->phone) }}" placeholder="+234…"
                           class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                    @if ($user->hasVerifiedPhone())
                        <p class="mt-1 text-xs font-medium text-forest-700">✓ Phone verified</p>
                    @else
                        <a href="{{ route('verification.phone') }}" class="mt-1 inline-block text-xs font-semibold text-forest-600 hover:underline">
                            Verify this phone to book instantly →
                        </a>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-between rounded-xl border border-ink-200 p-4">
                <div>
                    <p class="text-sm font-semibold text-ink-900">Employer mobility</p>
                    <p class="mt-0.5 text-xs text-ink-500">Join your employer's program so they cover your commute.</p>
                </div>
                <a href="{{ route('employers.self') }}" class="shrink-0 rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">
                    Employers
                </a>
            </div>

            <div class="rounded-xl border border-ink-200 p-4">
                <p class="text-sm font-semibold text-ink-900">Ride preferences</p>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="gender" class="mb-1 block text-sm font-medium text-ink-700">Gender</label>
                        <select id="gender" name="gender" class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                            <option value="">Prefer not to say</option>
                            <option value="female" @selected(old('gender', $user->gender) === 'female')>Female</option>
                            <option value="male" @selected(old('gender', $user->gender) === 'male')>Male</option>
                            <option value="unspecified" @selected(old('gender', $user->gender) === 'unspecified')>Other</option>
                        </select>
                    </div>
                    <label class="mt-5 flex items-center gap-3 rounded-xl bg-rose-50 p-3">
                        <input type="checkbox" name="prefers_women_only" value="1" @checked(old('prefers_women_only', $user->prefers_women_only))
                               class="h-5 w-5 rounded border-ink-300 text-forest-600 focus:ring-forest-500">
                        <span class="text-sm font-medium text-ink-800">Show women-only rides first</span>
                    </label>
                </div>
                <p class="mt-2 text-xs text-ink-500">A preference, never a hard match — you can always toggle the filter on the trip board.</p>
            </div>

            <div class="rounded-xl border border-ink-200 p-4">
                <p class="text-sm font-semibold text-ink-900">Emergency contact</p>
                <p class="mt-0.5 text-xs text-ink-500">Used only if a ride goes wrong — never displayed to other riders.</p>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="emergency_contact_name" class="mb-1 block text-sm font-medium text-ink-700">Contact name</label>
                        <input id="emergency_contact_name" name="emergency_contact_name" type="text" maxlength="255" value="{{ old('emergency_contact_name', $user->emergency_contact_name) }}"
                               class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                    </div>
                    <div>
                        <label for="emergency_contact_phone" class="mb-1 block text-sm font-medium text-ink-700">Contact phone</label>
                        <input id="emergency_contact_phone" name="emergency_contact_phone" type="text" maxlength="30" value="{{ old('emergency_contact_phone', $user->emergency_contact_phone) }}" placeholder="+234…"
                               class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-forest-700">
                Save profile
            </button>
        </form>
    </div>
@endsection
