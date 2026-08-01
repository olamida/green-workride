@extends('layouts.app')

@section('title', 'Wallet')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-heading text-2xl font-bold text-ink-900">Wallet</h1>
            <p class="mt-1 text-sm text-ink-500">Dual balance — cash for paid rides, subsidy credits from your employer (spent first).</p>
        </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <div class="rounded-2xl border border-ink-200 bg-white p-6">
            <p class="text-xs font-medium uppercase tracking-wider text-ink-400">Cash balance</p>
            <p class="mt-2 font-mono text-3xl font-semibold text-ink-900">₦{{ number_format((float) $wallet->cash_balance, 2) }}</p>
            <p class="mt-1 text-xs text-ink-500">Top up with card, transfer or USSD via Paystack.</p>
        </div>

        <div class="rounded-2xl border border-forest-200 bg-forest-50 p-6">
            <p class="text-xs font-medium uppercase tracking-wider text-forest-700">Subsidy credits</p>
            <p class="mt-2 font-mono text-3xl font-semibold text-forest-800">₦{{ number_format((float) $wallet->subsidy_credits, 2) }}</p>
            <p class="mt-1 text-xs text-forest-700">Issued by your workplace. Spent first on bookings.</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-5">
        <div class="rounded-2xl border border-ink-200 bg-white p-6 lg:col-span-2">
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

        <div class="rounded-2xl border border-ink-200 bg-white p-6 lg:col-span-3">
            <div class="flex items-center justify-between">
                <h2 class="font-heading font-semibold text-ink-900">Recent transactions</h2>
                <span class="text-xs text-ink-400">Last {{ $transactions->count() }}</span>
            </div>

            <div class="mt-4 space-y-2">
                @forelse ($transactions as $transaction)
                    <div class="flex items-center justify-between rounded-xl border border-ink-100 bg-paper px-4 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-ink-800">{{ $transaction->description ?? $transaction->type->label() }}</p>
                            <p class="font-mono text-[11px] text-ink-400">
                                {{ $transaction->created_at->format('d M Y, H:i') }} · {{ $transaction->type->label() }}
                            </p>
                        </div>
                        <span @class([
                            'shrink-0 font-mono text-sm font-semibold',
                            'text-forest-700' => in_array($transaction->type->value, ['credit', 'subsidy', 'refund', 'top_up'], true),
                            'text-ink-400' => in_array($transaction->type->value, ['hold'], true),
                            'text-red-600' => in_array($transaction->type->value, ['capture', 'debit'], true),
                        ])>
                            {{ in_array($transaction->type->value, ['credit', 'subsidy', 'refund', 'top_up'], true) ? '+' : '' }}₦{{ number_format((float) $transaction->amount, 2) }}
                        </span>
                    </div>
                @empty
                    <p class="py-10 text-center text-sm text-ink-500">No transactions yet.</p>
                @endforelse
            </div>
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
