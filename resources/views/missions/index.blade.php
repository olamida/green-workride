@extends('layouts.app')

@section('title', 'Missions')

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-heading text-2xl font-semibold text-ink-900">Missions</h1>
                <p class="mt-1 text-sm text-ink-500">Promoted volunteer activities. Do the activity — the app counts it and the sponsor pays you.</p>
            </div>
        </div>

        @if (! $enabled)
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                Missions are not enabled yet in this environment. When live, sponsors publish activities here and the app observes your rides and road reports.
            </div>
        @endif

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            @forelse ($missions as $entry)
                @php($mission = $entry['mission'])
                @php($progress = $entry['progress'])
                @php($submissions = $entry['submissions'])
                <div class="flex flex-col rounded-2xl border border-ink-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-forest-700">{{ $mission->activity_type->label() }}</p>
                            <h2 class="mt-1 font-heading text-lg font-semibold text-ink-900">{{ $mission->name }}</h2>
                        </div>
                        <span class="shrink-0 rounded-full bg-paper px-3 py-1.5 text-xs font-semibold text-forest-700">
                            {{ number_format($mission->reward_value) }} {{ str_replace('_', ' ', $mission->reward_type->value) }}
                        </span>
                    </div>

                    <p class="mt-2 text-sm text-ink-500">{{ $mission->description }}</p>
                    <p class="mt-1 text-xs text-ink-400">
                        by {{ $mission->sponsor_name ?: 'WorkRide Community' }} ·
                        {{ $mission->verification_mode->value === 'auto' ? 'auto-counted' : 'photo proof' }}
                        · {{ $mission->metric_window_days }} day window
                    </p>

                    @if ($mission->instructions)
                        <p class="mt-3 rounded-xl bg-paper p-3 text-xs text-ink-600">{{ $mission->instructions }}</p>
                    @endif

                    <div class="mt-4">
                        @if ($progress && $progress->status->value === 'in_progress')
                            <div class="flex items-center justify-between text-xs text-ink-500">
                                <span>{{ $progress->metric_count }} of {{ $mission->metric_goal }} {{ Str::plural('event', $mission->metric_goal) }}</span>
                                <span class="font-mono font-semibold text-forest-700">{{ $mission->progressPercent($progress->metric_count) }}%</span>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-ink-100">
                                <div class="h-full rounded-full bg-forest-500 transition-all" style="width: {{ $mission->progressPercent($progress->metric_count) }}%"></div>
                            </div>
                        @elseif ($progress && $progress->status->value === 'awarded')
                            <div class="flex items-center gap-2 rounded-xl bg-green-50 px-3 py-2 text-xs font-semibold text-green-700">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                Mission complete — reward paid on {{ $progress->awarded_at?->format('M j, Y') }}.
                            </div>
                        @else
                            <p class="text-xs text-ink-500">No progress yet — the app starts counting once you do the activity.</p>
                        @endif
                    </div>

                    @if ($mission->verification_mode->value === 'proof')
                        <form method="POST" action="{{ route('missions.proof', $mission) }}" enctype="multipart/form-data" class="mt-4 space-y-2 border-t border-ink-100 pt-4">
                            @csrf
                            <label class="block text-xs font-medium text-ink-600">
                                {{ $mission->proof_label ?: 'Upload proof photo' }}
                                <input type="file" name="proof_photo" accept="image/*" required
                                    class="mt-1 block w-full rounded-xl border border-ink-300 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-forest-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-forest-700">
                            </label>
                            <input type="text" name="note" placeholder="Optional note"
                                class="block w-full rounded-xl border border-ink-300 bg-white px-3 py-2 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                            <input type="hidden" name="lat" value="">
                            <input type="hidden" name="lng" value="">
                            <button class="w-full rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
                                Submit proof →
                            </button>
                            @error('proof_photo')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                            @error('mission')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </form>
                    @endif

                    @if ($submissions->isNotEmpty())
                        <p class="mt-3 text-xs text-ink-400">
                            Latest submission:
                            @foreach ($submissions->take(2) as $submission)
                                <span class="font-semibold capitalize text-ink-600">{{ $submission->status->value }}</span> · {{ $submission->created_at->diffForHumans() }}
                            @endforeach
                        </p>
                    @endif
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-ink-300 p-10 text-center text-sm text-ink-500">
                    No live missions right now. Sponsors publish them here — check back soon.
                </div>
            @endforelse
        </div>

        <div class="mt-10 overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <div class="border-b border-ink-100 px-6 py-4">
                <h2 class="font-heading font-semibold text-ink-900">Your completed missions</h2>
            </div>
            <div class="divide-y divide-ink-100">
                @forelse ($awards as $award)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                        <div>
                            <p class="text-sm font-medium text-ink-900">{{ $award->mission?->name }}</p>
                            <p class="text-xs text-ink-500">Paid {{ $award->awarded_at?->diffForHumans() }}</p>
                        </div>
                        <span class="font-mono text-sm font-semibold text-forest-700">
                            {{ number_format($award->mission?->reward_value ?? 0) }} {{ $award->mission ? str_replace('_', ' ', $award->mission->reward_type->value) : '' }}
                        </span>
                    </div>
                @empty
                    <p class="px-6 py-10 text-center text-sm text-ink-500">No completed missions yet.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
