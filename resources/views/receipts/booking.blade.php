@extends('receipts.layout')

@section('label', 'Trip Booking Receipt')
@section('title', 'TRIP BOOKING RECEIPT')
@section('rows')
    @php $trip = $booking->trip; @endphp
    <div class="row"><span>Motor route</span><span>{{ $trip->origin_text }} → {{ $trip->destination_text }}</span></div>
    <div class="row"><span>Motor wey follow</span><span>{{ strtoupper(str_replace('_', '-', $trip->corridor->value)) }}</span></div>
    <div class="row"><span>When motor commot</span><span>{{ $trip->departure_time->format('d M Y, H:i') }}</span></div>
    <div class="row"><span>Driver name</span><span>{{ $trip->driver?->name ?? '—' }}</span></div>
    <div class="row"><span>Motor plate</span><span>{{ $trip->vehicle?->plate_number ?? '—' }}</span></div>
    <div class="row"><span>How Much?</span><span>₦{{ number_format((float) $booking->fare_paid, 2) }}</span></div>
    <div class="row"><span>How You Go Pay?</span><span>{{ $booking->payment_method->label() }}</span></div>
    <div class="row"><span>How e take happen?</span><span>{{ $booking->status->label() }}</span></div>
    <div class="row"><span>Booked</span><span>{{ $booking->created_at->format('d M Y, H:i') }}</span></div>
    <div class="row"><span>Total paid</span><span>₦{{ number_format((float) $booking->fare_paid, 2) }}</span></div>
@endsection
