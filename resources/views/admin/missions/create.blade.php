@extends('layouts.admin')

@section('title', 'New mission')

@section('page', 'Missions')

@section('content')
    <a href="{{ route('admin.missions.index') }}" class="text-sm font-medium text-forest-600 hover:underline">← All missions</a>

    <form method="POST" action="{{ route('admin.missions.store') }}" class="mt-4 max-w-2xl rounded-2xl border border-ink-200 bg-white p-6">
        @csrf

        <h2 class="font-heading font-semibold text-ink-900">New promoted activity</h2>
        <p class="mt-1 text-sm text-ink-500">
            Define what counts, the reward, and how it's verified. Auto missions are counted by the app
            from real rides/road reports; proof missions need a photo the promoter reviews.
        </p>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="text-sm font-medium text-ink-700">Mission name *</label>
                <input name="name" value="{{ old('name') }}" required placeholder="e.g. Give 5 free rides this week"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label class="text-sm font-medium text-ink-700">Description</label>
                <textarea name="description" rows="2"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">{{ old('description') }}</textarea>
            </div>

            <div class="sm:col-span-2">
                <label class="text-sm font-medium text-ink-700">Instructions (shown to members)</label>
                <textarea name="instructions" rows="2" placeholder="e.g. Carry at least 2 passengers on the Kubwa→CBD corridor before 9am."
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">{{ old('instructions') }}</textarea>
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Activity *</label>
                <select name="activity_type" class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    @foreach ($activities as $activity)
                        <option value="{{ $activity->value }}" {{ old('activity_type') === $activity->value ? 'selected' : '' }}>{{ $activity->label() }}</option>
                    @endforeach
                </select>
                @error('activity_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">How it's verified *</label>
                <select name="verification_mode" id="verification_mode"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    @foreach ($modes as $mode)
                        <option value="{{ $mode->value }}">{{ $mode->value }} ({{ $mode->value === 'auto' ? 'app-counted' : 'photo proof + review' }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Goal (number of events) *</label>
                <input type="number" name="metric_goal" min="1" value="{{ old('metric_goal', 5) }}" required
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                @error('metric_goal')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Window (days) *</label>
                <input type="number" name="metric_window_days" min="1" value="{{ old('metric_window_days', 7) }}" required
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                @error('metric_window_days')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Reward type *</label>
                <select name="reward_type"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}">{{ str_replace('_', ' ', $type->value) }}</option>
                    @endforeach
                </select>
                @error('reward_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Reward value *</label>
                <input type="number" name="reward_value" step="0.01" min="1" value="{{ old('reward_value') }}" required
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                @error('reward_value')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2" id="proof_label_row">
                <label class="text-sm font-medium text-ink-700">Proof label (what the photo must show)</label>
                <input name="proof_label" value="{{ old('proof_label') }}"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Sponsor type *</label>
                <select name="sponsor_type"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    @foreach ($sponsors as $sponsor)
                        <option value="{{ $sponsor->value }}">{{ str_replace('_', ' ', $sponsor->value) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Sponsor name</label>
                <input name="sponsor_name" value="{{ old('sponsor_name') }}"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Starts at</label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Ends at</label>
                <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Budget cap (₦, optional)</label>
                <input type="number" name="budget_total" step="0.01" min="1" value="{{ old('budget_total') }}"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
            </div>

            <div class="sm:col-span-2">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="status" value="published" checked> Publish immediately (start observing events)
                </label>
            </div>
        </div>

        <button class="mt-6 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
            Create mission →
        </button>
    </form>
@endsection
