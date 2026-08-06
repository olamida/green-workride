@props([
    'action',
    'fare' => 0,
    'isFree' => false,
    'walletBalance' => null,
    'subsidyBalance' => null,
    'canSubsidy' => false,
    'canRideCredit' => false,
])

@php
    $fare = (int) $fare;
    $cashAndEarned = (float) ($walletBalance ?? 0);
    $subsidy = (float) ($subsidyBalance ?? 0);
    $walletOk = ($cashAndEarned + $subsidy) >= $fare;
    $subsidyOk = $canSubsidy && $subsidy >= $fare;
@endphp

<form method="POST" action="{{ $action }}"
      x-data="paymentPicker({
          fare: {{ $fare }},
          isFree: {{ $isFree ? 'true' : 'false' }},
          walletOk: {{ $walletOk ? 'true' : 'false' }},
          subsidyOk: {{ $subsidyOk ? 'true' : 'false' }},
          rideCreditOk: {{ $canRideCredit ? 'true' : 'false' }},
      })"
      @submit="submitting = true" class="mt-4 space-y-4">
    @csrf
    <input type="hidden" name="payment_method" x-model="method">

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    @if ($isFree)
        <div class="flex items-center justify-between gap-3 rounded-xl border border-gold-200 bg-gold-50 px-4 py-3">
            <span class="text-sm font-semibold text-ink-900">Free volunteer ride</span>
            <span class="rounded-full bg-gold-100 px-2.5 py-0.5 text-xs font-semibold text-gold-800">₦0</span>
        </div>
    @else
        <div class="space-y-2" role="radiogroup" aria-label="Pay with">
            <button type="button" role="radio" :aria-checked="isPicked('wallet')" @click="pick('wallet')" :disabled="! walletOk"
                    :class="isPicked('wallet') ? 'border-forest-500 bg-forest-50' : 'border-ink-200 bg-white'"
                    class="flex min-h-[56px] w-full items-center justify-between gap-3 rounded-xl border px-4 py-3 text-left transition active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50">
                <span class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-forest-100 text-forest-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h2m4 0h2m-10-5l1.5-5h13l1.5 5M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-ink-900">Wallet</span>
                        <span class="block text-xs text-ink-500">₦{{ number_format($cashAndEarned, 2) }} cash &amp; earned
                            @if ($subsidy > 0) · plus ₦{{ number_format($subsidy, 2) }} subsidy @endif
                        </span>
                    </span>
                </span>
                <span x-show="isPicked('wallet')" class="flex h-6 w-6 items-center justify-center rounded-full bg-forest-600 text-white">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
            </button>

            @if ($canSubsidy)
                <button type="button" role="radio" :aria-checked="isPicked('subsidy_credit')" @click="pick('subsidy_credit')" :disabled="! subsidyOk"
                        :class="isPicked('subsidy_credit') ? 'border-forest-500 bg-forest-50' : 'border-ink-200 bg-white'"
                        class="flex min-h-[56px] w-full items-center justify-between gap-3 rounded-xl border px-4 py-3 text-left transition active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50">
                    <span class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gold-100 text-gold-800">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 11l5-5m0 0h5m-5 5V6M21 13l-5 5m0 0h-5m5-5v5"/>
                            </svg>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-ink-900">Subsidy credits</span>
                            <span class="block text-xs text-ink-500">
                                ₦{{ number_format($subsidy, 2) }} available
                                @if (! $subsidyOk)<span class="text-rose-600"> — not enough</span>@endif
                            </span>
                        </span>
                    </span>
                    <span x-show="isPicked('subsidy_credit')" class="flex h-6 w-6 items-center justify-center rounded-full bg-forest-600 text-white">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </span>
                </button>
            @endif

            <button type="button" role="radio" :aria-checked="isPicked('cash')" @click="pick('cash')"
                    :class="isPicked('cash') ? 'border-forest-500 bg-forest-50' : 'border-ink-200 bg-white'"
                    class="flex min-h-[56px] w-full items-center justify-between gap-3 rounded-xl border px-4 py-3 text-left transition active:scale-[0.98]">
                <span class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-ink-100 text-ink-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a4 4 0 00-4-4H9a4 4 0 00-4 4v10a4 4 0 004 4h4a4 4 0 004-4v-2m-4-4h11m0 0l-3-3m3 3l-3 3"/>
                        </svg>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-ink-900">Cash to driver</span>
                        <span class="block text-xs text-ink-500">Pay ₦{{ number_format($fare, 0) }} directly when you board</span>
                    </span>
                </span>
                <span x-show="isPicked('cash')" class="flex h-6 w-6 items-center justify-center rounded-full bg-forest-600 text-white">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
            </button>

            @if ($canRideCredit)
                <button type="button" role="radio" :aria-checked="isPicked('ride_credit')" @click="pick('ride_credit')"
                        :class="isPicked('ride_credit') ? 'border-forest-500 bg-forest-50' : 'border-ink-200 bg-white'"
                        class="flex min-h-[56px] w-full items-center justify-between gap-3 rounded-xl border px-4 py-3 text-left transition active:scale-[0.98]">
                    <span class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-forest-50 text-forest-700">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-ink-900">Ride credit</span>
                            <span class="block text-xs text-ink-500">Ride now, repay a seat by driving a future trip</span>
                        </span>
                    </span>
                    <span x-show="isPicked('ride_credit')" class="flex h-6 w-6 items-center justify-center rounded-full bg-forest-600 text-white">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </span>
                </button>
            @endif
        </div>
    @endif

    <button type="submit" :disabled="submitting || ! canSubmit()"
            class="flex min-h-[56px] w-full items-center justify-center gap-2 rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-forest-700 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60">
        <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <span x-text="submitLabel()">Confirm seat · ₦{{ number_format($fare, 0) }}</span>
    </button>
</form>
