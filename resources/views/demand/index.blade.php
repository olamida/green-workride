@extends('layouts.app')

@section('title', 'Demand check-in')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-6 flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-forest-600 text-white">
                <x-icon name="signal" class="h-5 w-5" />
            </span>
            <div>
                <h1 class="font-heading text-xl font-bold text-ink-900">Demand check-in</h1>
                <p class="text-sm text-ink-500">"I'm at this junction, need a ride" — supply planning (guide §9B Method 5).</p>
            </div>
        </div>

        <form method="POST" action="{{ route('demand.checkin') }}" x-data="{ picking: false, lat: null, lng: null }"
              class="rounded-2xl border border-ink-200 bg-white p-6">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-medium text-ink-500">Where are you?</label>
                    <select name="junction_id" class="mt-1 w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm" onchange="this.form.dataset.junction = this.value">
                        <option value="">Use my GPS location</option>
                        @foreach ($junctions as $junction)
                            <option value="{{ $junction->id }}">{{ $junction->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-ink-400">Pick a known junction, or tap "Use my location" below.</p>
                </div>

                <div>
                    <label class="text-xs font-medium text-ink-500">How many people?</label>
                    <input type="number" name="passengers_count" min="1" max="10" value="1" required
                           class="mt-1 w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm">
                </div>
            </div>

            <div class="mt-4">
                <label class="text-xs font-medium text-ink-500">Where do you need to go?</label>
                <input type="text" name="destination_text" required placeholder="Federal Secretariat, Wuse Market…"
                       class="mt-1 w-full rounded-xl border border-ink-200 px-3 py-2.5 text-sm">
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <button type="button" @click="picking = true; $el.closest('form').querySelector('[name=pickup_lat]').value = '9.05'; $el.closest('form').querySelector('[name=pickup_lng]').value = '7.48'; lat = '9.05'; lng = '7.48'"
                        class="rounded-xl border border-ink-200 px-4 py-2.5 text-sm font-semibold text-ink-700 hover:bg-ink-50">
                    Use my location
                </button>
                <button class="rounded-xl bg-forest-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-forest-700">
                    Check in →
                </button>
            </div>

            <input type="hidden" name="pickup_lat" value="9.05">
            <input type="hidden" name="pickup_lng" value="7.48">
            @error('pickup_lat')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            @error('pickup_lng')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            @error('destination_text')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
        </form>

        <div class="mt-6 overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <div class="border-b border-ink-100 px-6 py-4">
                <h2 class="font-heading font-semibold text-ink-900">My check-ins</h2>
            </div>
            <div class="divide-y divide-ink-100">
                @forelse ($mine as $request)
                    <div class="flex items-center justify-between px-6 py-4">
                        <div>
                            <p class="text-sm font-medium text-ink-900">{{ $request->destination_text }} <span class="ml-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">{{ $request->passengers_count }} pax</span></p>
                            <p class="text-xs text-ink-500">{{ $request->requested_at->diffForHumans() }}</p>
                        </div>
                        <span class="rounded-full bg-ink-50 px-2.5 py-1 text-xs font-semibold capitalize text-ink-500">{{ $request->status->label() }}</span>
                    </div>
                @empty
                    <p class="px-6 py-10 text-center text-sm text-ink-500">No check-ins yet. Tell us where the buses should go.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
