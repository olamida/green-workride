@extends('layouts.admin')

@section('title', 'New Schedule')

@section('page', 'New Recurring Schedule')

@section('content')
    <a href="{{ route('admin.schedules.index') }}" class="text-sm font-semibold text-forest-600 hover:underline">← All schedules</a>

    <div class="mt-4 max-w-2xl rounded-2xl border border-ink-200 bg-white p-6">
        <form method="POST" action="{{ route('admin.schedules.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="route_id" class="mb-1 block text-sm font-medium text-ink-700">GTFS route</label>
                <select name="route_id" id="route_id" required class="w-full rounded-lg border-ink-300 shadow-sm focus:border-forest-500 focus:ring-forest-500">
                    <option value="">Select a corridor route…</option>
                    @foreach ($routes as $route)
                        <option value="{{ $route->id }}" @selected(old('route_id') == $route->id)>
                            {{ $route->route_long_name ?? $route->route_id }} · {{ $route->corridor }}
                        </option>
                    @endforeach
                </select>
                @error('route_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="driver_id" class="mb-1 block text-sm font-medium text-ink-700">Driver</label>
                    <select name="driver_id" id="driver_id" required class="w-full rounded-lg border-ink-300 shadow-sm focus:border-forest-500 focus:ring-forest-500">
                        <option value="">Select driver…</option>
                        @foreach ($drivers as $driver)
                            <option value="{{ $driver->id }}" @selected(old('driver_id') == $driver->id)>{{ $driver->name }}</option>
                        @endforeach
                    </select>
                    @error('driver_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="vehicle_id" class="mb-1 block text-sm font-medium text-ink-700">Vehicle</label>
                    <select name="vehicle_id" id="vehicle_id" required class="w-full rounded-lg border-ink-300 shadow-sm focus:border-forest-500 focus:ring-forest-500">
                        <option value="">Select vehicle…</option>
                        @foreach ($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" @selected(old('vehicle_id') == $vehicle->id)>
                                {{ $vehicle->plate_number }} · {{ $vehicle->seats }} seats · {{ $vehicle->owner?->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('vehicle_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="departure_time" class="mb-1 block text-sm font-medium text-ink-700">First departure</label>
                    <input type="time" name="departure_time" id="departure_time" value="{{ old('departure_time', '06:30') }}" required class="w-full rounded-lg border-ink-300 shadow-sm focus:border-forest-500 focus:ring-forest-500">
                    @error('departure_time') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="end_time" class="mb-1 block text-sm font-medium text-ink-700">End time <span class="text-ink-400">(blank = single run)</span></label>
                    <input type="time" name="end_time" id="end_time" value="{{ old('end_time') }}" class="w-full rounded-lg border-ink-300 shadow-sm focus:border-forest-500 focus:ring-forest-500">
                    @error('end_time') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="frequency_minutes" class="mb-1 block text-sm font-medium text-ink-700">Frequency (min)</label>
                    <input type="number" name="frequency_minutes" id="frequency_minutes" min="5" max="120" value="{{ old('frequency_minutes', 15) }}" required class="w-full rounded-lg border-ink-300 shadow-sm focus:border-forest-500 focus:ring-forest-500">
                    @error('frequency_minutes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <span class="mb-1 block text-sm font-medium text-ink-700">Runs on</span>
                <div class="flex flex-wrap gap-2">
                    @foreach ([['mon', 'Mon'], ['tue', 'Tue'], ['wed', 'Wed'], ['thu', 'Thu'], ['fri', 'Fri'], ['sat', 'Sat'], ['sun', 'Sun']] as [$day, $label])
                        <label class="flex items-center gap-1.5 rounded-lg border border-ink-200 bg-white px-3 py-1.5 text-sm">
                            <input type="checkbox" name="days_of_week[]" value="{{ $day }}" @checked(in_array($day, old('days_of_week', ['mon', 'tue', 'wed', 'thu', 'fri']), true))
                                   class="h-4 w-4 rounded border-ink-300 text-forest-600 focus:ring-forest-500">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @error('days_of_week') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.schedules.index') }}" class="rounded-xl border border-ink-200 px-4 py-2 text-sm font-semibold text-ink-700 hover:bg-paper">Cancel</a>
                <button type="submit" class="rounded-xl bg-forest-600 px-4 py-2 text-sm font-semibold text-white hover:bg-forest-700">Create schedule</button>
            </div>
        </form>
    </div>
@endsection
