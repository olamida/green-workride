{{-- WalletPill: dual-balance visual showing Office Support first, then Money from Driving, then My Money --}}
@props([
    'cash' => 0,
    'subsidy' => 0,
    'earned' => 0,
    'compact' => false,
])

@php
    $cash = (float) $cash;
    $subsidy = (float) $subsidy;
    $earned = (float) $earned;
    $total = $cash + $subsidy + $earned;
@endphp

@if ($compact)
    <div class="inline-flex items-center gap-1.5 rounded-full bg-ink-50 px-3 py-1.5 text-sm">
        <span class="font-mono font-semibold text-ink-900">₦{{ number_format($total, 0) }}</span>
        <span class="sr-only">Total wallet balance</span>
    </div>
@else
    <div class="rounded-2xl border border-ink-200 bg-white p-5">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <h3 class="font-heading text-base font-semibold text-ink-900">Wallet</h3>
            <span class="font-mono text-lg font-bold text-ink-900">₦{{ number_format($total, 0) }}</span>
        </div>

        <div class="space-y-3">
            {{-- Office Support (Subsidy) --}}
            @if ($subsidy > 0)
                <div class="flex items-center justify-between gap-3 rounded-xl bg-forest-50/60 px-4 py-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-forest-100 text-forest-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </span>
                        <span class="font-semibold text-ink-900">Office Support</span>
                    </div>
                    <span class="font-mono font-semibold text-forest-700">₦{{ number_format($subsidy, 0) }}</span>
                </div>
            @endif

            {{-- Money from Driving (Earned) --}}
            @if ($earned > 0)
                <div class="flex items-center justify-between gap-3 rounded-xl bg-gold-50/60 px-4 py-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gold-100 text-gold-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        <span class="font-semibold text-ink-900">Money from Driving</span>
                    </div>
                    <span class="font-mono font-semibold text-gold-700">₦{{ number_format($earned, 0) }}</span>
                </div>
            @endif

            {{-- My Money (Cash) --}}
            @if ($cash > 0)
                <div class="flex items-center justify-between gap-3 rounded-xl bg-ink-50 px-4 py-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-ink-100 text-ink-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        <span class="font-semibold text-ink-900">My Money</span>
                    </div>
                    <span class="font-mono font-semibold text-ink-700">₦{{ number_format($cash, 0) }}</span>
                </div>
            @endif

            {{-- Empty state --}}
            @if ($total === 0)
                <div class="text-center py-6 text-ink-500">
                    <p class="font-semibold">Wallet is empty</p>
                    <p class="text-sm mt-1">Add money to book rides instantly</p>
                </div>
            @endif
        </div>

        <div class="mt-4 pt-4 border-t border-ink-100 text-xs text-ink-500">
            <p class="mb-1"><span class="font-semibold text-forest-700">1.</span> Office Support — Company or Government help — Trackable, you can see receipt</p>
            <p class="mb-1"><span class="font-semibold text-gold-700">2.</span> Money from Driving — Your earnings from giving rides</p>
            <p><span class="font-semibold text-ink-700">3.</span> My Money — Your own cash top-up</p>
        </div>
    </div>
@endif