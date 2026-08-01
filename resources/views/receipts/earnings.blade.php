@extends('receipts.layout')

@section('label', 'Driver Earnings Receipt')
@section('title', 'DRIVER EARNINGS RECEIPT')
@section('rows')
    @php $trip = $booking->trip; @endphp
    <div class="row"><span>Route</span><span>{{ $trip->origin_text }} → {{ $trip->destination_text }}</span></div>
    <div class="row"><span>Passenger</span><span>{{ $booking->passenger?->name ?? '—' }}</span></div>
    <div class="row"><span>Seat fare</span><span>₦{{ number_format($fare, 2) }}</span></div>
    <div class="row"><span>Commission ({{ config('workride.commission_rate') * 100 }}%)</span><span>−₦{{ number_format($commission, 2) }}</span></div>
    <div class="row"><span>Union fee ({{ config('workride.union_fee_rate') * 100 }}%)</span><span>−₦{{ number_format($union_fee, 2) }}</span></div>
    <div class="row"><span>Insurance</span><span>−₦{{ number_format($insurance, 2) }}</span></div>
    <div class="row"><span>Paid</span><span>{{ $paid_at->format('d M Y, H:i') }}</span></div>
    <div class="row"><span>Net earning</span><span>₦{{ number_format($earning, 2) }}</span></div>
@endsection
