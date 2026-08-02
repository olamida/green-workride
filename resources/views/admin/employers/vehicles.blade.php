@extends('layouts.admin')

@section('title', 'Fleet — '.$employer->name)

@section('page', 'Employers')

@section('content')
    <a href="{{ route('admin.employers.show', $employer) }}" class="text-sm font-medium text-forest-600 hover:underline">← {{ $employer->name }}</a>

    <div class="mt-3 flex items-center justify-between gap-4">
        <h1 class="font-heading text-2xl font-semibold text-ink-900">Fleet</h1>
        <span class="text-sm text-ink-500">{{ $vehicles->count() }} vehicle(s) registered by staff</span>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ink-100">
                <thead>
                    <tr class="bg-paper text-left text-xs font-medium uppercase tracking-wider text-ink-400">
                        <th class="px-5 py-3">Owner</th>
                        <th class="px-5 py-3">Vehicle</th>
                        <th class="px-5 py-3">Plate</th>
                        <th class="px-5 py-3">Seats</th>
                        <th class="px-5 py-3">Papers</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($vehicles as $vehicle)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-ink-900">{{ $vehicle->owner?->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-ink-500">{{ $vehicle->owner?->email ?? '' }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-ink-700">{{ $vehicle->make }} {{ $vehicle->model }}</td>
                            <td class="px-5 py-4 font-mono text-xs text-ink-500">{{ $vehicle->plate_number }}</td>
                            <td class="px-5 py-4 text-sm text-ink-700">{{ $vehicle->seats }}</td>
                            <td class="px-5 py-4">
                                <x-badge :status="$vehicle->papers_verified ? 'approved' : 'pending'" :label="$vehicle->papers_verified ? 'Verified' : 'Pending'" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-ink-500">No vehicles registered by staff yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
