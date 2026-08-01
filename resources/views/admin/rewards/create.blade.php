@extends('layouts.admin')

@section('title', 'New campaign')

@section('page', 'Rewards')

@section('content')
    <a href="{{ route('admin.rewards.index') }}" class="text-sm font-medium text-forest-600 hover:underline">← All campaigns</a>

    <form method="POST" action="{{ route('admin.rewards.store') }}" class="mt-4 max-w-2xl rounded-2xl border border-ink-200 bg-white p-6">
        @csrf

        <h2 class="font-heading font-semibold text-ink-900">New reward campaign</h2>
        <p class="mt-1 text-sm text-ink-500">Payouts fire automatically on the trigger. Set a budget cap to control sponsor exposure.</p>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="text-sm font-medium text-ink-700">Campaign name *</label>
                <input name="name" value="{{ old('name') }}" required placeholder="e.g. Friday to-work bonus"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label class="text-sm font-medium text-ink-700">Description</label>
                <textarea name="description" rows="2"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Trigger *</label>
                <select name="trigger" class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    @foreach ($triggers as $trigger)
                        <option value="{{ $trigger->value }}">{{ str_replace('_', ' ', $trigger->value) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Period *</label>
                <select name="period" class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    @foreach ($periods as $period)
                        <option value="{{ $period->value }}">{{ $period->value }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Reward type *</label>
                <select name="type" class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}">{{ str_replace('_', ' ', $type->value) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Reward value *</label>
                <input type="number" name="value" step="0.01" min="1" value="{{ old('value') }}" required
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                @error('value')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Audience</label>
                <select name="audience" class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    <option value="">— Everyone —</option>
                    @foreach ($audiences as $audience)
                        <option value="{{ $audience->value }}">{{ $audience->value }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Budget cap (₦, optional)</label>
                <input type="number" name="budget_total" step="0.01" min="1" value="{{ old('budget_total') }}"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Sponsor type</label>
                <select name="sponsor_type" class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    <option value="government">Government</option>
                    <option value="private">Private company</option>
                    <option value="ngo">NGO</option>
                    <option value="foundation">Foundation</option>
                    <option value="cooperative">Cooperative</option>
                    <option value="community" selected>Community</option>
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

            <div class="sm:col-span-2">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="active" value="1" checked> Campaign active
                </label>
            </div>
        </div>

        <button class="mt-6 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
            Create campaign →
        </button>
    </form>
@endsection
