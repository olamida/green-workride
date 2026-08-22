{{-- WalletPill — dual balance cash + office support --}}
@props([
    'cashBalance' => 0,
    'subsidyCredits' => 0,
    'earnedBalance' => 0,
    'showDetails' => false,
    'onTopUp' => null,
    'onWithdraw' => null,
    'onTransfer' => null,
])

<?php
$cash = number_format($cashBalance, 0);
$subsidy = number_format($subsidyCredits, 0);
$earned = number_format($earnedBalance, 0);
$hasSubsidy = $subsidyCredits > 0;
$hasEarned = $earnedBalance > 0;
?>

<div class="wr-card p-4 space-y-3" data-wallet-pill>
    {{-- Hero dual balance --}}
    <div class="grid grid-cols-2 gap-3">
        {{-- Office Support (Subsidy) - visual priority first --}}
        @if ($hasSubsidy)
            <div class="relative rounded-2xl bg-forest-50 p-4 border border-forest-100"
                 aria-label="Office support balance">
                <div class="flex items-center gap-1.5 text-xs font-semibold text-forest-700 mb-1">
                    <x-icon name="building" class="h-3.5 w-3.5" />
                    Office Support
                </div>
                <div class="font-heading font-mono text-2xl font-bold text-forest-900 tabular-nums">
                    ₦{{ $subsidy }}
                </div>
                <div class="absolute top-2 right-2 text-[10px] text-forest-500">Paid by MDA</div>
            </div>
        @else
            <div class="relative rounded-2xl bg-ink-50 p-4 border border-ink-100"
                 aria-label="Office support balance - none available">
                <div class="flex items-center gap-1.5 text-xs font-semibold text-ink-500 mb-1">
                    <x-icon name="building" class="h-3.5 w-3.5" />
                    Office Support
                </div>
                <div class="font-heading font-mono text-2xl font-bold text-ink-400 tabular-nums">
                    ₦0
                </div>
            </div>
        @endif

        {{-- My Money (Cash) --}}
        <div class="relative rounded-2xl bg-white p-4 border border-ink-200"
             aria-label="Cash balance">
            <div class="flex items-center gap-1.5 text-xs font-semibold text-ink-600 mb-1">
                <x-icon name="wallet" class="h-3.5 w-3.5" />
                My Money
            </div>
            <div class="font-heading font-mono text-2xl font-bold text-ink-900 tabular-nums">
                ₦{{ $cash }}
            </div>
        </div>
    </div>

    {{-- Earned Balance (Money from Driving) --}}
    @if ($hasEarned)
        <div class="relative rounded-2xl bg-gold-50 p-4 border border-gold-100"
             aria-label="Money from driving balance">
            <div class="flex items-center gap-1.5 text-xs font-semibold text-gold-700 mb-1">
                <x-icon name="truck" class="h-3.5 w-3.5" />
                Money from Driving
            </div>
            <div class="font-heading font-mono text-xl font-bold text-gold-900 tabular-nums">
                ₦{{ $earned }}
            </div>
        </div>
    @endif

    {{-- Quick actions --}}
    @if ($showDetails)
        <div class="grid grid-cols-3 gap-2 pt-2 border-t border-ink-100">
            <button type="button"
                    wire:click="{{ $onTopUp }}"
                    onclick="{{ $onTopUp }}"
                    class="btn-secondary text-sm py-2.5"
                    aria-label="Add money to wallet">
                <x-icon name="plus" class="h-4 w-4" />
                Top Up
            </button>
            <button type="button"
                    wire:click="{{ $onWithdraw }}"
                    onclick="{{ $onWithdraw }}"
                    class="btn-secondary text-sm py-2.5"
                    aria-label="Withdraw to bank">
                <x-icon name="bank" class="h-4 w-4" />
                Withdraw
            </button>
            <button type="button"
                    wire:click="{{ $onTransfer }}"
                    onclick="{{ $onTransfer }}"
                    class="btn-secondary text-sm py-2.5"
                    aria-label="Send money to someone">
                <x-icon name="send" class="h-4 w-4" />
                Transfer
            </button>
        </div>
    @endif
</div>