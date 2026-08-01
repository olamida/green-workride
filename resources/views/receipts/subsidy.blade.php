@extends('receipts.layout')

@section('label', 'Subsidy Credit Receipt')
@section('title', 'SUBSIDY CREDIT RECEIPT')
@section('rows')
    <div class="row"><span>Amount credited</span><span>₦{{ number_format((float) $transaction->amount, 2) }}</span></div>
    <div class="row"><span>Balance used</span><span>Subsidy credits</span></div>
    <div class="row"><span>Workplace</span><span>{{ $workplace?->name ?? '—' }}</span></div>
    <div class="row"><span>MDA reference</span><span>{{ $transaction->reference }}</span></div>
    <div class="row"><span>Type</span><span>Transport palliative (trackable)</span></div>
    <div class="row"><span>Date</span><span>{{ $transaction->created_at->format('d M Y, H:i') }}</span></div>
    <div class="row"><span>Total credited</span><span>₦{{ number_format((float) $transaction->amount, 2) }}</span></div>
@endsection
