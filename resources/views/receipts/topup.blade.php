@extends('receipts.layout')

@section('label', 'Wallet Top-up Receipt')
@section('title', 'WALLET TOP-UP RECEIPT')
@section('rows')
    <div class="row"><span>Amount credited</span><span>₦{{ number_format((float) $transaction->amount, 2) }}</span></div>
    <div class="row"><span>Balance used</span><span>Cash</span></div>
    <div class="row"><span>Gateway</span><span>Paystack</span></div>
    <div class="row"><span>Gateway reference</span><span>{{ $transaction->gateway_ref ?: $transaction->tx_ref ?: $transaction->reference }}</span></div>
    <div class="row"><span>Date</span><span>{{ $transaction->created_at->format('d M Y, H:i') }}</span></div>
    <div class="row"><span>Total credited</span><span>₦{{ number_format((float) $transaction->amount, 2) }}</span></div>
@endsection
