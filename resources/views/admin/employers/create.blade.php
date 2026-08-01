@extends('layouts.admin')

@section('title', 'Register employer')

@section('page', 'Employers')

@section('content')
    <a href="{{ route('admin.employers.index') }}" class="text-sm font-medium text-forest-600 hover:underline">← All employers</a>

    <form method="POST" action="{{ route('admin.employers.store') }}" class="mt-4 max-w-2xl rounded-2xl border border-ink-200 bg-white p-6">
        @csrf

        <h2 class="font-heading font-semibold text-ink-900">Corporate Mobility Program</h2>
        <p class="mt-1 text-sm text-ink-500">Define the coverage policy — the engine pays the share on every eligible staff booking.</p>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="text-sm font-medium text-ink-700">Employer name *</label>
                <input name="name" value="{{ old('name') }}" required
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Phone</label>
                <input name="phone" value="{{ old('phone') }}"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">RC number</label>
                <input name="rc_number" value="{{ old('rc_number') }}" placeholder="e.g. RC 123456"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Zone (CBD / KUBWA / NYANYA…)</label>
                <input name="zone" value="{{ old('zone') }}" placeholder="e.g. CBD"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
            </div>

            <div class="sm:col-span-2">
                <label class="text-sm font-medium text-ink-700">Address</label>
                <input name="address" value="{{ old('address') }}"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Workplace (optional)</label>
                <select name="workplace_id" class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    <option value="">— None —</option>
                    @foreach ($workplaces as $workplace)
                        <option value="{{ $workplace->id }}">{{ $workplace->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Program type *</label>
                <select name="program_type" id="program_type" class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    @foreach ($programTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div id="percent-field" class="hidden">
                <label class="text-sm font-medium text-ink-700">Percent covered (%)</label>
                <input type="number" name="percent_covered" step="1" min="0" max="100" value="{{ old('percent_covered') }}"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
            </div>

            <div id="capped-field" class="hidden">
                <label class="text-sm font-medium text-ink-700">Max per trip (₦)</label>
                <input type="number" name="max_per_trip" step="1" min="0" value="{{ old('max_per_trip') }}"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
            </div>

            <div id="direction-field" class="hidden">
                <label class="text-sm font-medium text-ink-700">Covered direction</label>
                <select name="covered_direction" class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                    <option value="to_work">To work (into CBD/IDU)</option>
                    <option value="from_work">From work (out to zone)</option>
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-ink-700">Monthly cap per employee (₦)</label>
                <input type="number" name="max_monthly_per_employee" step="1" min="0" value="{{ old('max_monthly_per_employee') }}"
                    class="mt-1 w-full rounded-xl border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
            </div>

            <div class="sm:col-span-2">
                <label class="text-sm font-medium text-ink-700">Covered corridors (blank = all)</label>
                <div class="mt-2 flex flex-wrap gap-3">
                    @foreach (['kubwa_cbd' => 'KUBWA → CBD', 'nyanya_idu' => 'NYANYA → IDU', 'lugbe_cbd' => 'LUGBE → CBD'] as $value => $label)
                        <label class="flex items-center gap-2 rounded-xl border border-ink-200 bg-paper px-3 py-2 text-sm">
                            <input type="checkbox" name="corridors[]" value="{{ $value }}"> {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="sm:col-span-2">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="active" value="1" checked> Program active
                </label>
            </div>
        </div>

        <button class="mt-6 rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
            Register employer →
        </button>
    </form>

    <script>
        const type = document.getElementById('program_type');
        const toggleFields = () => {
            document.getElementById('percent-field').classList.toggle('hidden', type.value !== 'percent');
            document.getElementById('capped-field').classList.toggle('hidden', type.value !== 'capped');
            document.getElementById('direction-field').classList.toggle('hidden', type.value !== 'one_way');
        };
        type.addEventListener('change', toggleFields);
        toggleFields();
    </script>
@endsection
