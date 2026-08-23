@extends('layouts.app')

@section('title', 'Employer mobility')

@section('content')
    <div class="mb-8">
        <h1 class="font-heading text-2xl font-bold text-ink-900">Employer mobility</h1>
        <p class="mt-1 text-sm text-ink-500">
            Your employer can pay for your commute. Join their program — once approved you get
            Level 1 workplace verification and ride coverage automatically.
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <h2 class="font-heading font-semibold text-ink-900">Your memberships</h2>
            <div class="mt-4 space-y-3">
                @forelse ($user->employerMemberships as $membership)
                    <div class="flex items-center justify-between rounded-xl border border-ink-100 bg-paper px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-ink-800">{{ $membership->employer?->name ?? 'Employer' }}</p>
                            <p class="text-xs text-ink-500">
                                @if ($membership->isPending())
                                    Awaiting approval by your employer.
                                @elseif ($membership->isActive())
                                    Active — your commute can be covered on this program's corridors.
                                @else
                                    Status: {{ $membership->status->label() }}.
                                @endif
                            </p>
                        </div>
                        <x-badge :status="$membership->status->value" />
                    </div>
                @empty
                    <p class="text-sm text-ink-500">You haven't joined any employer program yet.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <h2 class="font-heading font-semibold text-ink-900">Join a program</h2>
            <p class="mt-1 text-sm text-ink-500">Request to join an employer that runs mobility on WorkRide.</p>
            <div class="mt-4 space-y-3">
                @forelse ($openEmployers as $employer)
                    <div class="flex items-center justify-between rounded-xl border border-ink-100 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-ink-800">{{ $employer->name }}</p>
                            <p class="text-xs text-ink-500">{{ $employer->program_type->label() }} program · {{ $employer->zone ?: 'FCT' }}</p>
                        </div>
                        <form method="POST" action="{{ route('employers.join', $employer) }}">
                            @csrf
                            <button class="rounded-lg bg-forest-600 px-4 py-2 text-xs font-semibold text-white hover:bg-forest-700">
                                Request to join
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-ink-500">No open employer programs right now.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-ink-200 bg-white p-6">
        <h2 class="font-heading font-semibold text-ink-900">Register a vehicle</h2>
        <p class="mt-1 text-sm text-ink-500">
            Own the staff bus, coaster or car you drive to work? Register it so it can run as a WorkRide
            corridor vehicle.
        </p>
        <a href="{{ route('employers.vehicles') }}" class="mt-3 inline-block rounded-xl bg-forest-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
            Manage my vehicles
        </a>
    </div>
@endsection
