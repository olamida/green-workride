@extends('layouts.admin')

@section('title', 'Settings')

@section('page', 'Settings')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <p class="max-w-xl text-sm text-ink-500">
            Guide §8 — corridor fares are fixed and anti-surge. Tune them here (naira per seat) and the
            change applies to every new trip immediately — no deploy required. Leave a field blank to
            restore the committed config default. Every change is written to the change-control trail.
        </p>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-ink-200 bg-white">
        <div class="border-b border-ink-100 px-6 py-4">
            <h2 class="font-heading font-semibold text-ink-900">Corridor fares <span class="text-xs font-normal text-ink-400">(max fare per seat — `workride.max_fare_per_corridor`)</span></h2>
        </div>
        <form method="POST" action="{{ route('admin.settings.store') }}" class="grid gap-4 p-6 sm:grid-cols-3">
            @csrf
            @foreach ($corridors as $row)
                <div>
                    <label class="text-xs font-medium text-ink-500">
                        {{ $row['corridor']->label() }}
                        @if ($row['overridden'])
                            <span class="ml-1 inline-flex rounded-full bg-gold-100 px-2 py-0.5 text-[10px] font-semibold text-gold-800">override</span>
                        @endif
                    </label>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="text-sm text-ink-400">₦</span>
                        <input
                            type="number"
                            name="fares[{{ $row['corridor']->value }}]"
                            step="50"
                            min="100"
                            max="5000"
                            placeholder="Blank = default"
                            value="{{ old('fares.' . $row['corridor']->value, $row['overridden'] ? $row['fare'] : '') }}"
                            class="w-full rounded-xl border border-ink-200 px-3 py-2 font-mono text-sm"
                        >
                    </div>
                    <p class="mt-1 text-[11px] text-ink-400">
                        Default ₦{{ number_format($row['fare']) }} · anti-surge max
                    </p>
                    @error('fares.' . $row['corridor']->value)
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
            <div class="sm:col-span-3">
                <button class="rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-forest-700">Save corridor fares →</button>
            </div>
        </form>
    </div>
@endsection
