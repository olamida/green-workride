@extends('layouts.app')

@section('title', 'Wallet')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-heading text-2xl font-bold text-ink-900">Wallet</h1>
            <p class="mt-1 text-sm text-ink-500">Triple balance — cash for top-ups, earned balance from driving, and subsidy credits from your employer (spent first on rides).</p>
        </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-3">
        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Cash balance</p>
            <p class="mt-2 font-mono text-3xl font-semibold text-ink-900">₦{{ number_format((float) $wallet->cash_balance, 2) }}</p>
            <p class="mt-1 text-xs text-ink-500">Top up with card, transfer or USSD via Paystack.</p>
        </div>

        <div class="rounded-2xl border border-gold-200 bg-amber-50 p-6">
            <p class="text-xs font-medium uppercase tracking-wider text-amber-700">Earned balance</p>
            <p class="mt-2 font-mono text-3xl font-semibold text-amber-800">₦{{ number_format((float) $wallet->earned_balance, 2) }}</p>
            <p class="mt-1 text-xs text-amber-700">Driver earnings after commission, union fee and insurance. Withdraw to bank or send for free.</p>
        </div>

        <div class="rounded-2xl border border-forest-200 bg-forest-50 p-6">
            <p class="text-xs font-medium uppercase tracking-wider text-forest-700">Subsidy credits</p>
            <p class="mt-2 font-mono text-3xl font-semibold text-forest-800">₦{{ number_format((float) $wallet->subsidy_credits, 2) }}</p>
            <p class="mt-1 text-xs text-forest-700">Issued by your workplace. Spent first on bookings. Not transferable.</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-5">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-2xl border border-ink-200 bg-white p-6">
                <h2 class="font-heading font-semibold text-ink-900">Top up</h2>
                <p class="mt-1 text-sm text-ink-500">Amount in naira (₦). Minimum ₦100.</p>

                <form method="POST" action="{{ route('wallet.topup') }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label for="amount" class="text-sm font-medium text-ink-700">Amount</label>
                        <div class="mt-1 flex items-center rounded-xl border border-ink-300 bg-white focus-within:ring-2 focus-within:ring-forest-100">
                            <span class="pl-4 font-mono text-lg font-semibold text-ink-500">₦</span>
                            <input type="number" name="amount" id="amount" min="100" step="100" required value="{{ old('amount') }}"
                                class="w-full rounded-xl bg-transparent px-3 py-3 font-mono text-lg font-semibold outline-none placeholder:font-normal placeholder:text-ink-300">
                        </div>
                        @error('amount')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button class="w-full rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-forest-700">
                        Continue to Paystack →
                    </button>
                </form>

                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ([1000, 2000, 5000, 10000] as $quick)
                        <button type="button" data-quick="{{ $quick }}"
                            class="rounded-full border border-ink-200 bg-paper px-3 py-1 text-xs font-medium text-ink-600 transition hover:border-forest-400 hover:text-forest-700">
                            ₦{{ number_format($quick) }}
                        </button>
                    @endforeach
                </div>
            </div>

            @if (config('workride.time_bank.enabled'))
                <div class="rounded-2xl border border-ink-200 bg-white p-6">
                    <h2 class="font-heading font-semibold text-ink-900">Send money</h2>
                    <p class="mt-1 text-sm text-ink-500">Transfer to a verified colleague. Cash transfers carry a 1% fee (min ₦10); earned transfers are free. Daily limit ₦{{ number_format((float) config('workride.p2p.daily_limit'), 0) }}.</p>

                    <form method="POST" action="{{ route('wallet.transfer') }}" class="mt-4 space-y-4">
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="receiver_phone" class="text-sm font-medium text-ink-700">Receiver phone</label>
                                <input type="text" name="receiver_phone" id="receiver_phone" required value="{{ old('receiver_phone') }}"
                                    placeholder="0803…" class="mt-1 w-full rounded-xl border border-ink-300 px-3 py-2 text-sm outline-none focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                                @error('receiver_phone')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="amount" class="text-sm font-medium text-ink-700">Amount</label>
                                <input type="number" name="amount" min="1" step="100" required value="{{ old('amount') }}"
                                    class="mt-1 w-full rounded-xl border border-ink-300 px-3 py-2 text-sm outline-none focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                                @error('amount')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div>
                            <label for="type" class="text-sm font-medium text-ink-700">Pay from</label>
                            <select name="type" id="type" class="mt-1 w-full rounded-xl border border-ink-300 px-3 py-2 text-sm focus:border-forest-500 focus:outline-none focus:ring-2 focus:ring-forest-100">
                                <option value="cash">Cash balance (1% fee)</option>
                                <option value="earned">Earned balance (free)</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button class="w-full rounded-xl bg-forest-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-forest-700">
                            Send →
                        </button>
                    </form>
                </div>

                <div class="rounded-2xl border border-ink-200 bg-white p-6">
                    <h2 class="font-heading font-semibold text-ink-900">Withdraw to bank</h2>
                    <p class="mt-1 text-sm text-ink-500">Debits earned balance first, then cash — never subsidy credits. Min ₦{{ number_format((float) config('workride.payout.min_amount'), 0) }}, max ₦{{ number_format((float) config('workride.payout.max_amount'), 0) }}.</p>

                    <form method="POST" action="{{ route('wallet.withdraw') }}" class="mt-4 space-y-4">
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="bank_code" class="text-sm font-medium text-ink-700">Bank code</label>
                                <input type="text" name="bank_code" id="bank_code" required value="{{ old('bank_code') }}"
                                    placeholder="044 (Access)" class="mt-1 w-full rounded-xl border border-ink-300 px-3 py-2 text-sm outline-none focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                                @error('bank_code')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="account_number" class="text-sm font-medium text-ink-700">Account number</label>
                                <input type="text" name="account_number" required value="{{ old('account_number') }}"
                                    class="mt-1 w-full rounded-xl border border-ink-300 px-3 py-2 text-sm outline-none focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                                @error('account_number')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div>
                            <label for="amount" class="text-sm font-medium text-ink-700">Amount</label>
                            <input type="number" name="amount" min="1" step="100" required value="{{ old('amount') }}"
                                class="mt-1 w-full rounded-xl border border-ink-300 px-3 py-2 text-sm outline-none focus:border-forest-500 focus:ring-2 focus:ring-forest-100">
                            @error('amount')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button class="w-full rounded-xl bg-ink-800 px-4 py-3 text-sm font-semibold text-white transition hover:bg-ink-900">
                            Withdraw →
                        </button>
                    </form>
                </div>
            @endif

            @if ($rideCredits->isNotEmpty())
                <div class="rounded-2xl border border-gold-200 bg-amber-50 p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="font-heading font-semibold text-amber-900">Ride credits (Time-Bank)</h2>
                        <span class="rounded-full bg-amber-200 px-3 py-1 text-xs font-semibold text-amber-900">{{ $outstandingSeats }} seat(s) owed</span>
                    </div>
                    <p class="mt-1 text-sm text-amber-700">“Ride now, drive later.” Carry passengers to repay seats.</p>

                    <div class="mt-4 space-y-2">
                        @foreach ($rideCredits as $credit)
                            <div class="flex items-center justify-between rounded-xl border border-amber-200 bg-white px-4 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-ink-800">
                                        {{ $credit->trip?->route_name ?? 'Trip' }} · ₦{{ number_format((float) $credit->fare_value, 0) }}
                                    </p>
                                    <p class="font-mono text-[11px] text-ink-400">
                                        {{ $credit->seats_repaid }}/{{ $credit->seats_owed }} seats repaid · due {{ $credit->due_date?->format('d M Y') }} · {{ $credit->status->label() }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-6 lg:col-span-3">
            <div class="rounded-2xl border border-ink-200 bg-white p-6">
                <div class="flex items-center justify-between">
                    <h2 class="font-heading font-semibold text-ink-900">Recent transactions</h2>
                    <span class="text-xs text-ink-400">Last {{ $transactions->count() }}</span>
                </div>

                <div class="mt-4 space-y-2">
                    @forelse ($transactions as $transaction)
                        @php
                            $credits = ['credit', 'subsidy', 'refund', 'top_up', 'earned', 'p2p_credit'];
                            $neutral = ['hold'];
                            $debits = ['capture', 'debit', 'p2p_debit', 'fee', 'payout'];
                            $positive = in_array($transaction->type->value, $credits, true);
                            $negative = in_array($transaction->type->value, $debits, true);
                        @endphp
                        <div class="flex items-center justify-between rounded-xl border border-ink-100 bg-paper px-4 py-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-ink-800">{{ $transaction->description ?? $transaction->type->label() }}</p>
                                <p class="font-mono text-[11px] text-ink-400">
                                    {{ $transaction->created_at->format('d M Y, H:i') }} · {{ $transaction->type->label() }}
                                </p>
                            </div>
                            <span @class([
                                'shrink-0 font-mono text-sm font-semibold',
                                'text-forest-700' => $positive,
                                'text-ink-400' => $neutral,
                                'text-red-600' => $negative,
                            ])>
                                {{ $positive ? '+' : '' }}₦{{ number_format((float) $transaction->amount, 2) }}
                            </span>
                        </div>
                    @empty
                        <p class="py-10 text-center text-sm text-ink-500">No transactions yet.</p>
                    @endforelse
                </div>
            </div>

            @if ($transfers->isNotEmpty())
                <div class="rounded-2xl border border-ink-200 bg-white p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="font-heading font-semibold text-ink-900">Recent transfers</h2>
                        <span class="text-xs text-ink-400">Last {{ $transfers->count() }}</span>
                    </div>
                    <div class="mt-4 space-y-2">
                        @foreach ($transfers as $transfer)
                            <div class="flex items-center justify-between rounded-xl border border-ink-100 bg-paper px-4 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-ink-800">
                                        To {{ $transfer->receiver?->name ?? 'Unknown' }} · {{ $transfer->type->label() }}
                                    </p>
                                    <p class="font-mono text-[11px] text-ink-400">{{ $transfer->reference }}</p>
                                </div>
                                <span class="shrink-0 font-mono text-sm font-semibold text-red-600">
                                    -₦{{ number_format((float) $transfer->amount + (float) $transfer->fee, 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($payouts->isNotEmpty())
                <div class="rounded-2xl border border-ink-200 bg-white p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="font-heading font-semibold text-ink-900">Recent withdrawals</h2>
                        <span class="text-xs text-ink-400">Last {{ $payouts->count() }}</span>
                    </div>
                    <div class="mt-4 space-y-2">
                        @foreach ($payouts as $payout)
                            <div class="flex items-center justify-between rounded-xl border border-ink-100 bg-paper px-4 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-ink-800">To account ****{{ substr($payout->account_number, -4) }}</p>
                                    <p class="font-mono text-[11px] text-ink-400">{{ $payout->reference }} · {{ $payout->status->label() }}</p>
                                </div>
                                <span class="shrink-0 font-mono text-sm font-semibold text-red-600">
                                    -₦{{ number_format((float) $payout->amount, 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('[data-quick]').forEach((btn) => {
            btn.addEventListener('click', () => {
                document.getElementById('amount').value = btn.dataset.quick;
            });
        });
    </script>
@endsection
