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

        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('trips.store') }}" class="space-y-5 rounded-2xl border border-ink-200 bg-white p-6" x-data="publishTrip()">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="corridor" class="mb-1 block text-sm font-medium text-ink-700">Corridor</label>
                    <select id="corridor" name="corridor" required x-model="corridor"
                            class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                        @foreach ($corridors as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="total_seats" class="mb-1 block text-sm font-medium text-ink-700">Total seats</label>
                    <input id="total_seats" name="total_seats" type="number" min="1" max="60" value="{{ old('total_seats', 4) }}" required
                           class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
                </div>
            </div>

            <div>
                <label for="origin_text" class="mb-1 block text-sm font-medium text-ink-700">Origin</label>
                <input id="origin_text" name="origin_text" type="text" maxlength="255" value="{{ old('origin_text') }}" required placeholder="e.g. Kubwa Junction"
                       class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
            </div>

            <div>
                <label for="destination_text" class="mb-1 block text-sm font-medium text-ink-700">Destination</label>
                <input id="destination_text" name="destination_text" type="text" maxlength="255" value="{{ old('destination_text') }}" required placeholder="e.g. Federal Secretariat"
                       class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
            </div>

            <div>
                <label for="departure_time" class="mb-1 block text-sm font-medium text-ink-700">Departure time</label>
                <input id="departure_time" name="departure_time" type="datetime-local" value="{{ old('departure_time', \Carbon\Carbon::now()->addHours(2)->format('Y-m-d\TH:i')) }}" required
                       class="w-full rounded-xl border border-ink-200 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-200">
            </div>

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

            <label class="flex items-center gap-3 rounded-xl border border-gold-200 bg-gold-50 p-4">
                <input type="checkbox" name="is_free_volunteer" value="1" x-model="isFreeVolunteer" @checked(old('is_free_volunteer'))
                       class="h-5 w-5 rounded border-ink-300 text-forest-600 focus:ring-forest-500">
                <span>
                    <span class="block text-sm font-semibold text-ink-900">Free volunteer ride</span>
                    <span class="block text-xs text-ink-500">No fare charged. Earn Green Points + fuel discount coupons.</span>
                </span>
            </label>

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

            <button type="submit" class="w-full rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-forest-700">
                Publish trip
            </button>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        function publishTrip() {
            return {
                corridor: '{{ old('corridor', 'kubwa_cbd') }}',
                isFreeVolunteer: false,
                lat: '{{ old('current_lat') }}',
                lng: '{{ old('current_lng') }}',
                locationStatus: '',
                locate() {
                    if (!navigator.geolocation) {
                        this.locationStatus = 'Geolocation not supported by this browser.';
                        return;
                    }
                    this.locationStatus = 'Locating…';
                    navigator.geolocation.getCurrentPosition((position) => {
                        this.lat = position.coords.latitude.toFixed(7);
                        this.lng = position.coords.longitude.toFixed(7);
                        this.locationStatus = 'Location set. Publish from here to appear on the board.';
                    }, () => {
                        this.locationStatus = 'Could not get your location. You can still publish without it.';
                    });
                },
            };
        }
    </script>
@endsection
