@extends('receipts.layout')

@section('label', 'Trip Booking Receipt')
@section('title', 'TRIP BOOKING RECEIPT')
@section('rows')
    @php $trip = $booking->trip; @endphp
    <div class="row"><span>Route</span><span>{{ $trip->origin_text }} → {{ $trip->destination_text }}</span></div>
    <div class="row"><span>Corridor</span><span>{{ strtoupper(str_replace('_', '-', $trip->corridor->value)) }}</span></div>
    <div class="row"><span>Departure</span><span>{{ $trip->departure_time->format('d M Y, H:i') }}</span></div>
    <div class="row"><span>Driver</span><span>{{ $trip->driver?->name ?? '—' }}</span></div>
    <div class="row"><span>Vehicle</span><span>{{ $trip->vehicle?->plate_number ?? '—' }}</span></div>
    <div class="row"><span>Seat fare</span><span>₦{{ number_format((float) $booking->fare_paid, 2) }}</span></div>
    <div class="row"><span>Payment method</span><span>{{ $booking->payment_method->label() }}</span></div>
    <div class="row"><span>Status</span><span>{{ $booking->status->label() }}</span></div>
    <div class="row"><span>Booked</span><span>{{ $booking->created_at->format('d M Y, H:i') }}</span></div>
    <div class="row"><span>Total paid</span><span>₦{{ number_format((float) $booking->fare_paid, 2) }}</span></div>
@endsection
