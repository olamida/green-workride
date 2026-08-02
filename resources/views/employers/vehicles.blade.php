@extends('layouts.app')

@section('title', 'My vehicles')

@section('content')
    <div class="mb-8">
        <h1 class="font-heading text-2xl font-bold text-ink-900">My vehicles</h1>
        <p class="mt-1 text-sm text-ink-500">
            Register the vehicle you drive to work. A Control Tower admin verifies its papers before it can
            publish paid rides; volunteer rides can use any registered vehicle.
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <h2 class="font-heading font-semibold text-ink-900">Add a vehicle</h2>
            <form method="POST" action="{{ route('employer.vehicles.store') }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <input type="text" name="plate_number" value="{{ old('plate_number') }}" placeholder="Plate number (e.g. ABJ-849-KJ)" required
                        class="w-full rounded-xl border border-ink-300 bg-white px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    @error('plate_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <input type="text" name="make" value="{{ old('make') }}" placeholder="Make" required class="rounded-xl border border-ink-300 bg-white px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    <input type="text" name="model" value="{{ old('model') }}" placeholder="Model" required class="rounded-xl border border-ink-300 bg-white px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <input type="text" name="color" value="{{ old('color') }}" placeholder="Color" class="rounded-xl border border-ink-300 bg-white px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    <input type="number" name="seats" value="{{ old('seats', 4) }}" min="1" max="100" required class="rounded-xl border border-ink-300 bg-white px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    <select name="type" required class="rounded-xl border border-ink-300 bg-white px-4 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                        <option value="sedan" @selected(old('type') === 'sedan')>Sedan</option>
                        <option value="coaster" @selected(old('type') === 'coaster')>Coaster</option>
                        <option value="staff_bus" @selected(old('type') === 'staff_bus')>Staff Bus</option>
                        <option value="danfo" @selected(old('type') === 'danfo')>Danfo</option>
                    </select>
                </div>
                <button type="submit" class="w-full rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
                    Register vehicle
                </button>
            </form>
        </div>

        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <h2 class="font-heading font-semibold text-ink-900">Registered</h2>
            <div class="mt-4 space-y-3">
                @forelse ($vehicles as $vehicle)
                    <div class="flex items-center justify-between rounded-xl border border-ink-100 bg-paper px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-ink-800">{{ $vehicle->make }} {{ $vehicle->model }}</p>
                            <p class="font-mono text-xs text-ink-500">{{ $vehicle->plate_number }} · {{ $vehicle->seats }} seats</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-badge :status="$vehicle->papers_verified ? 'approved' : 'pending'" :label="$vehicle->papers_verified ? 'Verified' : 'Pending papers'" />
                            <form method="POST" action="{{ route('employer.vehicles.destroy', $vehicle) }}">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs font-medium text-red-600 hover:underline">Remove</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-ink-500">No vehicles registered yet.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
