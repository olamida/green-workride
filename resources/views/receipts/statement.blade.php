@extends('receipts.layout')

@section('label', 'Monthly Commute Statement')
@section('title', 'MONTHLY COMMUTE STATEMENT')
@section('rows')
    <div class="row"><span>Month</span><span>{{ $month->format('F Y') }}</span></div>
    <div class="row"><span>Rides taken</span><span>{{ $totalRides }}</span></div>
    <div class="row"><span>Paid rides</span><span>{{ $paidRides }}</span></div>
    <div class="row"><span>Subsidy-covered</span><span>₦{{ number_format($subsidyFare, 2) }}</span></div>
    <div class="row"><span>Wallet-paid</span><span>₦{{ number_format($walletFare, 2) }}</span></div>
    <div class="row"><span>Cash-paid</span><span>₦{{ number_format($cashFare, 2) }}</span></div>
    <div class="row"><span>Total commute cost</span><span>₦{{ number_format($totalFare, 2) }}</span></div>

    @if ($bookings->count())
        <div style="margin-top:16px;border-top:1px dashed #cbd5e1;padding-top:12px;">
            <p style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#64748b;margin-bottom:8px;">Ride log</p>
            @foreach ($bookings as $b)
                <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:13px;border-bottom:1px solid #f1f5f9;">
                    <span style="color:#475569">{{ $b->created_at->format('d M') }} · {{ $b->trip?->origin_text }} → {{ $b->trip?->destination_text }}</span>
                    <span style="color:#0f172a;">₦{{ number_format((float) $b->fare_paid, 2) }}</span>
                </div>
            @endforeach
        </div>
    @endif
@endsection
