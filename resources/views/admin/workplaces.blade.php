@extends('layouts.admin')

@section('title', 'Workplaces')

@section('page', 'Workplaces')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-ink-500">
            {{ $workplaces->count() }} seeded MDAs · {{ $workplaces->sum('users_count') }} staff attached
        </p>
        <div class="flex gap-2">
            @foreach (['Central Business District', 'Garki', 'Wuse', 'Idu'] as $zone)
                <a href="{{ route('admin.workplaces.index', ['zone' => $zone]) }}"
                    @class(['rounded-full px-3 py-1 text-xs font-medium transition', request('zone') === $zone ? 'bg-ink-900 text-white' : 'border border-ink-200 bg-white text-ink-600 hover:bg-ink-100'])>
                    {{ $zone }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($workplaces as $workplace)
            <div class="rounded-2xl border border-ink-200 bg-white p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="truncate font-heading text-sm font-semibold text-ink-900">{{ $workplace->name }}</h2>
                        @if ($workplace->acronym)
                            <p class="mt-0.5 font-mono text-xs text-forest-600">{{ $workplace->acronym }}</p>
                        @endif
                    </div>
                    @if ($workplace->is_government)
                        <span class="shrink-0 rounded-full bg-ink-100 px-2 py-0.5 text-xs font-medium text-ink-600">Govt</span>
                    @endif
                </div>

                <div class="mt-4 flex items-center justify-between text-xs text-ink-500">
                    <span>Zone: <span class="font-medium text-ink-700">{{ $workplace->zone }}</span></span>
                    <span>{{ $workplace->users_count }} staff</span>
                </div>

                @if ($workplace->lat && $workplace->lng)
                    <p class="mt-2 font-mono text-[11px] text-ink-400">
                        {{ $workplace->lat }}, {{ $workplace->lng }}
                    </p>
                @endif

                <div class="mt-4 border-t border-ink-100 pt-3 text-xs text-ink-500">
                    Geofence radius: <span class="font-mono font-medium text-ink-700">{{ number_format($workplace->geofence_radius_m) }} m</span>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-ink-200 bg-white p-12 text-center text-sm text-ink-500">
                No workplaces found.
            </div>
        @endforelse
    </div>
@endsection
