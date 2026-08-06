@extends('layouts.app')

@section('title', 'Publish a Trip')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <a href="{{ route('trips.index') }}" class="text-sm font-semibold text-forest-600 hover:underline">← Trip Board</a>
            <h1 class="mt-2 font-heading text-2xl font-bold text-ink-900">Publish a trip</h1>
            <p class="mt-1 text-sm text-ink-500">
                Fixed anti-surge fare per corridor. Volunteer rides are always free and open to Level 1.
            </p>
        </div>

        @if (config('workride.fleet.enabled') && $asset)
            @php
                $fleetCleared = $asset->isServiceable() && $todayInspection && $todayInspection->is_passed;
            @endphp
            <div class="mb-6 rounded-2xl border px-4 py-3 text-sm
                {{ $fleetCleared ? 'border-green-200 bg-green-50 text-green-800' : 'border-amber-200 bg-amber-50 text-amber-800' }}">
                <p class="font-semibold">
                    {{ $asset->make }} {{ $asset->model }} · {{ $asset->plate_number }}
                    — {{ $fleetCleared ? 'cleared to publish' : ($todayInspection ? 'failed inspection today' : 'not inspected today') }}
                </p>
                <p class="mt-0.5 text-xs opacity-80">
                    @if ($fleetCleared)
                        Pre-trip inspection passed. Good to go.
                    @else
                        Complete the pre-trip inspection in <a href="{{ route('fleet.index') }}" class="font-semibold underline">My fleet</a> before publishing.
                    @endif
                </p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('trips.store') }}"
              class="rounded-2xl border border-ink-200 bg-white p-6"
              x-data="progressWizard({
                  steps: ['corridor', 'schedule', 'vehicle', 'publish'],
                  initial: 'corridor',
                  lat: '{{ old('current_lat') }}',
                  lng: '{{ old('current_lng') }}',
                  corridor: '{{ old('corridor', 'kubwa_cbd') }}',
                  isFreeVolunteer: {{ old('is_free_volunteer') ? 'true' : 'false' }},
                  corridorLabels: @json(collect($corridors)->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),
              })">
            @csrf

            <ol role="list" aria-label="Publish progress" class="flex justify-between gap-2">
                @foreach (['corridor' => 'Corridor', 'schedule' => 'Time & seats', 'vehicle' => 'Vehicle', 'publish' => 'Publish'] as $key => $label)
                    <li class="flex flex-1 flex-col items-center">
                        <div class="flex w-full items-center">
                            <button type="button" @click="go('{{ $key }}')"
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold transition"
                                    :class="isDone('{{ $key }}') ? 'bg-forest-600 text-white' : (isCurrent('{{ $key }}') ? 'bg-gold-100 text-gold-800 ring-4 ring-gold-200' : 'bg-ink-100 text-ink-500')"
                                    :aria-current="isCurrent('{{ $key }}') ? 'step' : undefined">
                                <template x-if="isDone('{{ $key }}')"><span aria-hidden="true">✓</span></template>
                                <template x-if="!isDone('{{ $key }}')"><span x-text="stepNumber('{{ $key }}')"></span></template>
                            </button>
                            @if (! $loop->last)
                                <div class="mx-2 h-0.5 flex-1" :class="isDone('{{ $key }}') ? 'bg-forest-500' : 'bg-ink-200'" aria-hidden="true"></div>
                            @endif
                        </div>
                        <p class="mt-1.5 text-center text-xs font-medium"
                           :class="isCurrent('{{ $key }}') ? 'text-ink-900' : (isDone('{{ $key }}') ? 'text-ink-500' : 'text-ink-400')">{{ $label }}</p>
                    </li>
                @endforeach
            </ol>

            <div x-ref="panel" class="mt-8 space-y-5">
                {{-- Step 1: Corridor --}}
                <div x-show="isCurrent('corridor')" x-cloak>
                    <div>
                        <label for="corridor" class="mb-1 block text-sm font-medium text-ink-700">Corridor</label>
                        <select id="corridor" name="corridor" required x-model="corridor"
                                class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                            @foreach ($corridors as $option)
                                <option value="{{ $option->value }}">{{ $option->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <label class="mt-4 flex items-center gap-3 rounded-xl border border-gold-200 bg-gold-50 p-4">
                        <input type="checkbox" name="is_free_volunteer" value="1" x-model="isFreeVolunteer" @checked(old('is_free_volunteer'))
                               class="h-5 w-5 rounded border-ink-300 text-forest-600 focus:ring-forest-500">
                        <span>
                            <span class="block text-sm font-semibold text-ink-900">Free volunteer ride</span>
                            <span class="block text-xs text-ink-500">No fare charged. Earn Green Points + fuel discount coupons.</span>
                        </span>
                    </label>
                </div>

                {{-- Step 2: Time & seats --}}
                <div x-show="isCurrent('schedule')" x-cloak class="space-y-4">
                    <div>
                        <label for="origin_text" class="mb-1 block text-sm font-medium text-ink-700">Origin</label>
                        <input id="origin_text" name="origin_text" type="text" maxlength="255" value="{{ old('origin_text') }}" required placeholder="e.g. Kubwa Junction" x-ref="origin"
                               class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                    </div>

                    <div>
                        <label for="destination_text" class="mb-1 block text-sm font-medium text-ink-700">Destination</label>
                        <input id="destination_text" name="destination_text" type="text" maxlength="255" value="{{ old('destination_text') }}" required placeholder="e.g. Federal Secretariat" x-ref="dest"
                               class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="departure_time" class="mb-1 block text-sm font-medium text-ink-700">Departure time</label>
                            <input id="departure_time" name="departure_time" type="datetime-local" value="{{ old('departure_time', \Carbon\Carbon::now()->addHours(2)->format('Y-m-d\TH:i')) }}" required x-ref="depart"
                                   class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                        </div>
                        <div>
                            <label for="total_seats" class="mb-1 block text-sm font-medium text-ink-700">Total seats</label>
                            <input id="total_seats" name="total_seats" type="number" min="1" max="60" value="{{ old('total_seats', 4) }}" required x-ref="seats"
                                   class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                        </div>
                    </div>

                    <label class="flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4">
                        <input type="checkbox" name="women_only" value="1" @checked(old('women_only'))
                               class="h-5 w-5 rounded border-ink-300 text-rose-600 focus:ring-rose-500">
                        <span>
                            <span class="block text-sm font-semibold text-ink-900">Women-only ride</span>
                            <span class="block text-xs text-ink-500">Only female riders can book this trip.</span>
                        </span>
                    </label>
                </div>

                {{-- Step 3: Vehicle & location --}}
                <div x-show="isCurrent('vehicle')" x-cloak class="space-y-4">
                    <div>
                        <label for="vehicle_id" class="mb-1 block text-sm font-medium text-ink-700">Vehicle</label>
                        <select id="vehicle_id" name="vehicle_id" class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                            <option value="">Auto-select my verified vehicle</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" @selected(old('vehicle_id') == $vehicle->id)>
                                    {{ $vehicle->make }} {{ $vehicle->model }} · {{ $vehicle->plate_number }}
                                    {{ $vehicle->papers_verified ? '' : '(papers unverified)' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="current_lat" class="mb-1 block text-sm font-medium text-ink-700">Latitude</label>
                            <input id="current_lat" name="current_lat" type="number" step="any" x-model="lat" placeholder="Auto from GPS"
                                   class="w-full rounded-xl border border-ink-200 px-3 py-2 font-mono text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                        </div>
                        <div>
                            <label for="current_lng" class="mb-1 block text-sm font-medium text-ink-700">Longitude</label>
                            <input id="current_lng" name="current_lng" type="number" step="any" x-model="lng" placeholder="Auto from GPS"
                                   class="w-full rounded-xl border border-ink-200 px-3 py-2 font-mono text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                        </div>
                    </div>
                    <p x-show="locationStatus" class="text-xs text-forest-700" x-text="locationStatus"></p>

                    <button type="button" @click="locate" class="rounded-xl border border-ink-200 px-4 py-2 text-sm font-medium text-ink-700 transition hover:bg-ink-100">
                        📍 Use my location
                    </button>
                </div>

                {{-- Step 4: Review & publish --}}
                <div x-show="isCurrent('publish')" x-cloak class="space-y-4">
                    <div class="rounded-xl border border-ink-100 bg-paper p-4 text-sm">
                        <dl class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs uppercase tracking-wider text-ink-400">Corridor</dt>
                                <dd class="mt-0.5 font-medium text-ink-900">
                                    <span x-text="corridorLabels[corridor] || corridor"></span>
                                    <span x-show="isFreeVolunteer" class="text-gold-700"> · free</span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wider text-ink-400">Type</dt>
                                <dd class="mt-0.5 font-medium text-ink-900">
                                    <span x-text="isFreeVolunteer ? 'Free volunteer ride' : 'Paid ride'"></span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wider text-ink-400">Route</dt>
                                <dd class="mt-0.5 font-medium text-ink-900">
                                    <span x-text="$refs.origin?.value || '—'"></span> → <span x-text="$refs.dest?.value || '—'"></span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wider text-ink-400">Seats</dt>
                                <dd class="mt-0.5 font-medium text-ink-900">
                                    <span x-text="$refs.seats?.value || '—'"></span> seats · <span x-text="$refs.depart?.value || '—'"></span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <button type="submit" class="w-full rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-forest-700">
                        Publish trip
                    </button>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between border-t border-ink-100 pt-5">
                <button type="button" @click="back()" x-show="!isFirst()" x-cloak
                        class="rounded-xl border border-ink-200 px-4 py-2 text-sm font-medium text-ink-700 transition hover:bg-ink-100">
                    ← Back
                </button>
                <button type="button" @click="next()" x-show="!isLast()" x-cloak
                        class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">
                    Continue →
                </button>
            </div>
        </form>
    </div>
@endsection
