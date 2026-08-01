@extends('layouts.app')

@section('title', 'Shop')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-8">
        <h1 class="font-heading text-2xl font-semibold text-ink-900">Commodity Shop</h1>
        <p class="mt-1 text-sm text-ink-500">
            Buy physical goods with wallet cash or earnings and collect via QR voucher.
            <strong>Subsidy credits can never buy goods</strong> — they are ride-only.
        </p>

        @if (! $enabled)
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                The shop is not enabled yet in this environment.
            </div>
        @endif

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-ink-200 bg-white p-5">
                <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Cash balance</p>
                <p class="mt-2 font-mono text-2xl font-semibold text-forest-700">₦{{ number_format((float) ($wallet?->cash_balance ?? 0), 2) }}</p>
            </div>
            <div class="rounded-2xl border border-ink-200 bg-white p-5">
                <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Earned balance</p>
                <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">₦{{ number_format((float) ($wallet?->earned_balance ?? 0), 2) }}</p>
            </div>
            <div class="rounded-2xl border border-ink-200 bg-white p-5">
                <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Subsidy credits</p>
                <p class="mt-2 font-mono text-2xl font-semibold text-ink-900">₦{{ number_format((float) ($wallet?->subsidy_credits ?? 0), 2) }}</p>
                <p class="mt-1 text-xs text-ink-500">Not spendable in the shop.</p>
            </div>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
                <div class="border-b border-ink-100 px-6 py-4">
                    <h2 class="font-heading font-semibold text-ink-900">Order goods</h2>
                </div>
                <form method="POST" action="{{ route('shop.store') }}" class="divide-y divide-ink-100">
                    @csrf
                    @forelse ($items as $item)
                        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                            <div>
                                <p class="text-sm font-medium text-ink-900">{{ $item->name }}</p>
                                <p class="text-xs text-ink-500">{{ $item->category?->label() }} · ₦{{ number_format((float) $item->current_price_ngn, 2) }}/{{ $item->unit }}</p>
                            </div>
                            <label class="flex items-center gap-2 text-sm text-ink-600">
                                Qty
                                <input type="number" name="items[{{ $loop->index }}][quantity]" step="0.0001" min="0" value="0"
                                    class="w-24 rounded-xl border border-ink-300 bg-white px-3 py-2 text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                                {{ $item->unit }}
                                <input type="hidden" name="items[{{ $loop->index }}][commodity_id]" value="{{ $item->id }}">
                            </label>
                        </div>
                    @empty
                        <p class="px-6 py-10 text-center text-sm text-ink-500">No shop items listed yet.</p>
                    @endforelse

                    <div class="flex flex-wrap items-center gap-3 px-6 py-4">
                        <label class="text-sm font-medium text-ink-700">Pay from</label>
                        <select name="paid_from" class="rounded-xl border border-ink-300 bg-white px-3 py-2 text-sm">
                            <option value="cash">Cash balance</option>
                            <option value="earned">Earned balance</option>
                        </select>
                        <button class="ml-auto rounded-xl bg-forest-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-forest-700">
                            Place order →
                        </button>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
                <div class="border-b border-ink-100 px-6 py-4">
                    <h2 class="font-heading font-semibold text-ink-900">Your orders</h2>
                </div>
                <div class="divide-y divide-ink-100">
                    @forelse ($orders as $order)
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <p class="font-mono text-xs text-ink-500">{{ $order->reference }}</p>
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold capitalize
                                    {{ $order->status === \App\Enums\OrderStatus::Placed ? 'bg-green-50 text-green-700' : ($order->status === \App\Enums\OrderStatus::Cancelled ? 'bg-ink-50 text-ink-500' : 'bg-paper text-ink-700') }}">
                                    {{ $order->status->value }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm font-semibold text-ink-900">₦{{ number_format((float) $order->total_ngn, 2) }}</p>
                            <p class="mt-0.5 text-xs text-ink-500">
                                @foreach ($order->items as $line)
                                    {{ $line['quantity'] }} {{ $line['unit'] }} {{ $line['name'] }} ·
                                @endforeach
                                paid from {{ $order->paid_from->value }}
                            </p>
                            @if ($order->status === \App\Enums\OrderStatus::Placed)
                                <form method="POST" action="{{ route('shop.cancel', $order) }}" class="mt-2">
                                    @csrf
                                    <button class="text-xs font-semibold text-red-600 hover:underline">Cancel order →</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="px-6 py-10 text-center text-sm text-ink-500">No orders yet. Your QR collection voucher appears here.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
