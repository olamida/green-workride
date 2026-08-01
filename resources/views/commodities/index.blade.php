@extends('layouts.app')

@section('title', 'Commodities')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-8">
        <h1 class="font-heading text-2xl font-semibold text-ink-900">Commodity Market</h1>
        <p class="mt-1 text-sm text-ink-500">
            Your wallet buys gold, rice, maize and fuel — a hedge against naira inflation.
            <strong>Subsidy credits can never buy goods</strong>; only cash and earnings are spendable here.
        </p>

        @if (! $enabled)
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                Commodity trading is not enabled yet in this environment.
            </div>
        @endif

        <div class="mt-8 grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
                    <div class="border-b border-ink-100 px-6 py-4">
                        <h2 class="font-heading font-semibold text-ink-900">Market</h2>
                    </div>
                    <div class="divide-y divide-ink-100">
                        @forelse ($market as $commodity)
                            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                                <div>
                                    <p class="text-sm font-medium text-ink-900">
                                        {{ $commodity->name }}
                                        <span class="ml-1 rounded bg-paper px-1.5 py-0.5 font-mono text-[10px] uppercase text-ink-500">{{ $commodity->symbol }}</span>
                                    </p>
                                    <p class="text-xs text-ink-500">{{ $commodity->category?->label() }} · ₦{{ number_format((float) $commodity->current_price_ngn, 2) }}/{{ $commodity->unit }}</p>
                                </div>
                                <form method="POST" action="{{ route('commodities.buy') }}" class="flex items-end gap-2">
                                    @csrf
                                    <input type="hidden" name="commodity_id" value="{{ $commodity->id }}">
                                    <div>
                                        <label class="text-[10px] font-medium uppercase tracking-wider text-ink-400">Qty ({{ $commodity->unit }})</label>
                                        <input type="number" name="quantity" step="0.0001" min="0.0001" value="1" required
                                            class="mt-1 w-28 rounded-xl border border-ink-300 bg-white px-3 py-2 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                                    </div>
                                    <button class="rounded-xl bg-forest-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-forest-700">
                                        Buy →
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="px-6 py-10 text-center text-sm text-ink-500">No tradable commodities listed yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div>
                <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
                    <div class="border-b border-ink-100 px-6 py-4">
                        <h2 class="font-heading font-semibold text-ink-900">Your portfolio</h2>
                    </div>
                    <div class="divide-y divide-ink-100">
                        @forelse ($portfolio as $position)
                            <div class="px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-ink-900">{{ $position->commodity->name }}</p>
                                    <p class="font-mono text-sm font-semibold text-forest-700">₦{{ number_format((float) $position->current_value_ngn, 2) }}</p>
                                </div>
                                <p class="mt-1 text-xs text-ink-500">
                                    {{ $position->quantity }} {{ $position->commodity->unit }}s · avg ₦{{ number_format((float) $position->avg_cost_ngn, 2) }}
                                </p>
                                <form method="POST" action="{{ route('commodities.sell') }}" class="mt-2 flex items-end gap-2">
                                    @csrf
                                    <input type="hidden" name="commodity_id" value="{{ $position->commodity_id }}">
                                    <input type="hidden" name="quantity" value="{{ $position->quantity }}">
                                    <button class="rounded-xl border border-forest-600 px-3 py-1.5 text-xs font-semibold text-forest-700 transition hover:bg-forest-50">
                                        Sell all →
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="px-6 py-10 text-center text-sm text-ink-500">Your holdings appear here after your first buy.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
