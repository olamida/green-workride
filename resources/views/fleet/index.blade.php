@extends('layouts.app')

@section('title', 'My fleet')

@section('content')
    <div class="mb-6">
        <h1 class="font-heading text-2xl font-bold text-ink-900">My fleet</h1>
        <p class="mt-1 text-sm text-ink-500">
            Daily pre-trip inspection before you publish, one-tap fault reporting and your maintenance
            calendar. A failed inspection or open fault grounds the bus until Control Tower clears it.
        </p>
    </div>

    @if (! $enabled)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-800">
            Fleet is not enabled in this region yet. It ships when the first leased 18-seaters land for the
            Kubwa-CBD pilot.
        </div>
    @elseif ($assets->isEmpty())
        <div class="rounded-2xl border border-ink-200 bg-white p-10 text-center">
            <p class="text-sm font-medium text-ink-900">No bus assigned to you yet</p>
            <p class="mt-1 text-sm text-ink-500">Control Tower assigns leased vehicles to verified drivers for the corridor pilot.</p>
        </div>
    @else
        <div class="space-y-6">
            @foreach ($assets as $asset)
                @php
                    $inspection = $todayInspections[$asset->id] ?? null;
                    $cleared = $asset->isServiceable() && $inspection && $inspection->is_passed;
                    $openFaultsCount = $asset->openFaultsCount();
                @endphp

                <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-6 py-4">
                        <div>
                            <h2 class="font-heading font-semibold text-ink-900">{{ $asset->make }} {{ $asset->model }}</h2>
                            <p class="font-mono text-xs text-ink-500">
                                {{ $asset->plate_number }} · {{ $asset->asset_type->label() }} · {{ number_format($asset->mileage) }} km
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $asset->isServiceable() ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                {{ $asset->status->label() }}
                            </span>
                            <x-badge :status="$cleared ? 'approved' : ($inspection ? 'pending' : 'neutral')"
                                     :label="$cleared ? 'Cleared today' : ($inspection ? 'Failed today' : 'Not inspected')" />
                        </div>
                    </div>

                    <div class="grid gap-6 p-6 lg:grid-cols-2">
                        <div>
                            <h3 class="text-sm font-semibold text-ink-800">Pre-trip inspection</h3>
                            @if ($cleared)
                                <p class="mt-2 rounded-xl bg-green-50 px-4 py-3 text-xs text-green-800">
                                    You're cleared to publish today's trips. Completed {{ $inspection->created_at->format('H:i') }}.
                                </p>
                            @endif
                            <form method="POST" action="{{ route('fleet.inspect', $asset) }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                                @csrf
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="text-xs font-medium text-ink-600">Tyres photo</label>
                                        <input type="file" name="tyre_photo" accept="image/*"
                                            class="mt-1 block w-full rounded-xl border border-ink-300 bg-white text-xs file:mr-3 file:rounded-lg file:border-0 file:bg-forest-50 file:px-3 file:py-2 file:font-semibold file:text-forest-700">
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-ink-600">Interior photo</label>
                                        <input type="file" name="interior_photo" accept="image/*"
                                            class="mt-1 block w-full rounded-xl border border-ink-300 bg-white text-xs file:mr-3 file:rounded-lg file:border-0 file:bg-forest-50 file:px-3 file:py-2 file:font-semibold file:text-forest-700">
                                    </div>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <input type="text" name="oil_level" placeholder="Oil level (e.g. Full / Low)" maxlength="50"
                                        class="rounded-xl border border-ink-300 bg-white px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                                    <select name="is_passed" required class="rounded-xl border border-ink-300 bg-white px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                                        <option value="">Pass or fail…</option>
                                        <option value="1">Pass — ready to drive</option>
                                        <option value="0">Fail — issue found</option>
                                    </select>
                                </div>
                                <textarea name="notes" rows="2" maxlength="500" placeholder="Notes (required when failing)"
                                    class="w-full rounded-xl border border-ink-300 bg-white px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100"></textarea>
                                <button type="submit" class="rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
                                    Submit inspection
                                </button>
                            </form>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-semibold text-ink-800">Report a fault</h3>
                                <form method="POST" action="{{ route('fleet.faults', $asset) }}" class="mt-2 space-y-3">
                                    @csrf
                                    <textarea name="description" rows="2" maxlength="1000" placeholder="What's wrong? e.g. low oil pressure light, brake judder" required
                                        class="w-full rounded-xl border border-ink-300 bg-white px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100"></textarea>
                                    <div class="flex items-center gap-3">
                                        <select name="severity" class="rounded-xl border border-ink-300 bg-white px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                                            <option value="1">1 · Minor</option>
                                            <option value="2">2 · Low</option>
                                            <option value="3" selected>3 · Medium</option>
                                            <option value="4">4 · High</option>
                                            <option value="5">5 · Critical</option>
                                        </select>
                                        <button type="submit" class="rounded-xl border border-amber-300 px-4 py-2.5 text-sm font-semibold text-amber-800 hover:bg-amber-50">
                                            Report
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div>
                                <h3 class="text-sm font-semibold text-ink-800">Open faults</h3>
                                @if ($asset->faults()->whereIn('status', ['open', 'in_progress'])->exists())
                                    <ul class="mt-2 space-y-2">
                                        @foreach ($asset->faults()->whereIn('status', ['open', 'in_progress'])->latest()->get() as $fault)
                                            <li class="rounded-xl border border-amber-100 bg-amber-50 px-4 py-2.5 text-xs">
                                                <span class="font-semibold text-amber-800">Severity {{ $fault->severity }}/5</span>
                                                <span class="text-amber-700"> · {{ $fault->description }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="mt-2 text-xs text-ink-500">No open faults. Fleet is healthy.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($openFaults->isNotEmpty() || $upcomingMaintenance->isNotEmpty())
            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                @if ($openFaults->isNotEmpty())
                    <div class="rounded-2xl border border-ink-200 bg-white p-5">
                        <h2 class="font-heading text-sm font-semibold text-ink-900">My open faults</h2>
                        <ul class="mt-3 space-y-2">
                            @foreach ($openFaults as $fault)
                                <li class="flex items-center justify-between gap-3 text-xs">
                                    <span class="text-ink-700">{{ $fault->description }}</span>
                                    <span class="shrink-0 font-mono text-ink-400">{{ $fault->asset->plate_number }} · S{{ $fault->severity }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($upcomingMaintenance->isNotEmpty())
                    <div class="rounded-2xl border border-ink-200 bg-white p-5">
                        <h2 class="font-heading text-sm font-semibold text-ink-900">Upcoming maintenance</h2>
                        <ul class="mt-3 space-y-2">
                            @foreach ($upcomingMaintenance as $job)
                                <li class="flex items-center justify-between gap-3 text-xs">
                                    <span class="text-ink-700">{{ $job->asset->plate_number }} · {{ $job->type->label() }}</span>
                                    <span class="shrink-0 font-mono text-ink-400">Due {{ $job->due_date->format('j M Y') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif
    @endif
@endsection
