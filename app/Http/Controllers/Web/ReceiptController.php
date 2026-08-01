<?php

namespace App\Http\Controllers\Web;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Sprint 7 — printable receipts (guide §14). Eight receipt types exist; the
 * CO₂/Fuel certificates and the FERMA road report already ship, so this
 * controller covers the five financial ones:
 *
 *  1. Trip Booking Receipt  (passenger)
 *  2. Driver Earnings Receipt
 *  3. Wallet Top-up Receipt
 *  4. Subsidy Credit Receipt (MDA audit)
 *  5. Monthly Commute Statement (salary deduction proof)
 *
 * Every receipt carries a QR that decodes to the public verify URL, so an
 * auditor can confirm a booking / top-up / subsidy without signing in.
 */
class ReceiptController extends Controller
{
    public function __construct(
        private readonly PricingService $pricing,
    ) {}

    /**
     * Trip Booking Receipt — the passenger (or trip driver/admin) can print it
     * as proof of payment for their ride.
     */
    public function booking(Booking $booking)
    {
        $this->authorizeBookingView($booking);

        $booking->load(['passenger', 'trip.driver', 'trip.vehicle', 'trip.waypoints']);

        return $this->render('booking', [
            'booking' => $booking,
            'holder' => $booking->passenger?->name ?? 'WorkRide passenger',
            'reference' => 'BK-'.$booking->id,
        ]);
    }

    /**
     * Driver Earnings Receipt — net driver earning for one ride after
     * commission, union fee and insurance are deducted.
     */
    public function earnings(Booking $booking)
    {
        $this->authorizeEarningsView($booking);

        $booking->load(['passenger', 'trip.driver', 'trip.vehicle']);

        $fare = (float) $booking->fare_paid;
        $earning = $this->pricing->driverEarning($fare);

        $earned = Transaction::where('type', TransactionType::Earned->value)
            ->where('reference', "EARN-{$booking->id}")
            ->first();

        return $this->render('earnings', [
            'booking' => $booking,
            'holder' => $booking->trip?->driver?->name ?? 'WorkRide driver',
            'fare' => $fare,
            'commission' => $this->pricing->commission($fare),
            'union_fee' => $this->pricing->unionFee($fare),
            'insurance' => (float) config('workride.insurance_per_trip'),
            'earning' => $earning,
            'reference' => 'EARN-'.$booking->id,
            'settled' => (bool) $earned,
            'paid_at' => $earned?->created_at ?? $booking->updated_at,
        ]);
    }

    /**
     * Wallet Top-up Receipt — Paystack-funded cash credit, keyed by the
     * idempotent `TOPUP-{txRef}` transaction.
     */
    public function topup(Transaction $transaction)
    {
        $this->authorizeTransactionView($transaction);

        $transaction->load('wallet.user');

        return $this->render('topup', [
            'transaction' => $transaction,
            'holder' => $transaction->wallet?->user?->name ?? 'WorkRide user',
            'user' => $transaction->wallet?->user,
            'reference' => $transaction->reference,
        ]);
    }

    /**
     * Subsidy Credit Receipt — the MDA audit trail for one palliative credit.
     */
    public function subsidy(Transaction $transaction)
    {
        $this->authorizeAdmin();

        $transaction->load('wallet.user');

        return $this->render('subsidy', [
            'transaction' => $transaction,
            'holder' => $transaction->wallet?->user?->name ?? 'WorkRide user',
            'user' => $transaction->wallet?->user,
            'workplace' => $transaction->wallet?->user?->workplace,
            'reference' => $transaction->reference,
        ]);
    }

    /**
     * Monthly Commute Statement — every ride the user took in one month, with
     * totals. The receipt an employee submits for salary deduction proof.
     */
    public function statement(Request $request, string $month)
    {
        abort_unless(preg_match('/^\d{4}-\d{2}$/', $month), 422);

        $user = auth()->user();

        // Admins may fetch any user's statement via ?user=; default to self.
        if ($user->role->isAdmin() && $request->integer('user')) {
            $user = User::findOrFail($request->integer('user'));
        }

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $bookings = $user->bookings()
            ->with(['trip', 'trip.driver'])
            ->whereBetween('created_at', [$start, $end])
            ->whereNot('status', BookingStatus::Cancelled->value)
            ->orderBy('created_at')
            ->get();

        $totalFare = (float) $bookings->sum('fare_paid');
        $subsidyFare = (float) $bookings->where('payment_method', PaymentMethod::SubsidyCredit->value)->sum('fare_paid');
        $cashFare = (float) $bookings->where('payment_method', PaymentMethod::Cash->value)->sum('fare_paid');
        $walletFare = (float) $bookings->where('payment_method', PaymentMethod::Wallet->value)->sum('fare_paid');

        return $this->render('statement', [
            'user' => $user,
            'holder' => $user->name,
            'month' => $start,
            'monthKey' => $month,
            'bookings' => $bookings,
            'totalFare' => $totalFare,
            'subsidyFare' => $subsidyFare,
            'cashFare' => $cashFare,
            'walletFare' => $walletFare,
            'paidRides' => $bookings->where('fare_paid', '>', 0)->count(),
            'totalRides' => $bookings->count(),
            'reference' => 'ST-'.$user->id.'-'.$month,
        ]);
    }

    /**
     * Public verification page — the QR on every receipt decodes here.
     */
    public function verify(string $type, string $reference)
    {
        $record = match ($type) {
            'booking' => $this->verifyBooking($reference),
            'earnings' => $this->verifyEarnings($reference),
            'topup' => $this->verifyTopup($reference),
            'subsidy' => $this->verifySubsidy($reference),
            'statement' => $this->verifyStatement($reference),
            default => abort(404),
        };

        if (! $record) {
            abort(404);
        }

        return view('receipts.verify', ['type' => $type, ...$record]);
    }

    /* ------------------------------------------------------------------ */
    /* Verification resolvers */
    /* ------------------------------------------------------------------ */

    private function verifyBooking(string $reference): ?array
    {
        if (! preg_match('/^BK-(\d+)$/', $reference, $m)) {
            return null;
        }

        $booking = Booking::with(['passenger', 'trip.driver', 'trip.vehicle'])
            ->find((int) $m[1]);

        if (! $booking) {
            return null;
        }

        return [
            'title' => 'Trip Booking Receipt',
            'reference' => $reference,
            'holder' => $booking->passenger?->name ?? 'WorkRide passenger',
            'rows' => [
                'Route' => "{$booking->trip->origin_text} → {$booking->trip->destination_text}",
                'Corridor' => strtoupper(str_replace('_', '-', $booking->trip->corridor->value)),
                'Driver' => $booking->trip->driver?->name ?? '—',
                'Fare' => '₦'.number_format((float) $booking->fare_paid, 2),
                'Payment' => $booking->payment_method->label(),
                'Status' => $booking->status->label(),
                'Booked' => $booking->created_at->format('d M Y, H:i'),
            ],
            'verified' => in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::Boarded, BookingStatus::Completed], true),
        ];
    }

    private function verifyEarnings(string $reference): ?array
    {
        if (! preg_match('/^EARN-(\d+)$/', $reference, $m)) {
            return null;
        }

        $booking = Booking::with(['trip.driver', 'trip.vehicle'])->find((int) $m[1]);

        if (! $booking) {
            return null;
        }

        $fare = (float) $booking->fare_paid;

        return [
            'title' => 'Driver Earnings Receipt',
            'reference' => $reference,
            'holder' => $booking->trip->driver?->name ?? 'WorkRide driver',
            'rows' => [
                'Route' => "{$booking->trip->origin_text} → {$booking->trip->destination_text}",
                'Fare' => '₦'.number_format($fare, 2),
                'Commission' => '₦'.number_format($this->pricing->commission($fare), 2),
                'Union fee' => '₦'.number_format($this->pricing->unionFee($fare), 2),
                'Insurance' => '₦'.number_format((float) config('workride.insurance_per_trip'), 2),
                'Net earning' => '₦'.number_format($this->pricing->driverEarning($fare), 2),
            ],
            'verified' => Transaction::where('reference', "EARN-{$booking->id}")->exists(),
        ];
    }

    private function verifyTopup(string $reference): ?array
    {
        if (! preg_match('/^TOPUP-(.+)$/', $reference, $m)) {
            return null;
        }

        $transaction = Transaction::with('wallet.user')
            ->where('reference', "TOPUP-{$m[1]}")
            ->first();

        if (! $transaction) {
            return null;
        }

        return [
            'title' => 'Wallet Top-up Receipt',
            'reference' => $transaction->reference,
            'holder' => $transaction->wallet?->user?->name ?? 'WorkRide user',
            'rows' => [
                'Amount' => '₦'.number_format((float) $transaction->amount, 2),
                'Gateway' => 'Paystack',
                'Gateway ref' => $transaction->gateway_ref ?: $transaction->tx_ref ?: '—',
                'Date' => $transaction->created_at->format('d M Y, H:i'),
            ],
            'verified' => true,
        ];
    }

    private function verifySubsidy(string $reference): ?array
    {
        $transaction = Transaction::with('wallet.user')->where('reference', $reference)->first();

        if (! $transaction || $transaction->type !== TransactionType::Subsidy) {
            return null;
        }

        return [
            'title' => 'Subsidy Credit Receipt',
            'reference' => $transaction->reference,
            'holder' => $transaction->wallet?->user?->name ?? 'WorkRide user',
            'rows' => [
                'Amount' => '₦'.number_format((float) $transaction->amount, 2),
                'Workplace' => $transaction->wallet?->user?->workplace?->name ?? '—',
                'Type' => 'Subsidy credit (palliative)',
                'Date' => $transaction->created_at->format('d M Y, H:i'),
            ],
            'verified' => true,
        ];
    }

    private function verifyStatement(string $reference): ?array
    {
        if (! preg_match('/^ST-(\d+)-(\d{4}-\d{2})$/', $reference, $m)) {
            return null;
        }

        $user = User::find((int) $m[1]);

        if (! $user) {
            return null;
        }

        $start = Carbon::createFromFormat('Y-m', $m[2])->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $count = $user->bookings()
            ->whereBetween('created_at', [$start, $end])
            ->whereNot('status', BookingStatus::Cancelled->value)
            ->count();

        return [
            'title' => 'Monthly Commute Statement',
            'reference' => $reference,
            'holder' => $user->name,
            'rows' => [
                'Month' => $start->format('F Y'),
                'Rides' => number_format($count),
                'Verified' => $count > 0 ? 'Yes' : 'No rides this month',
            ],
            'verified' => $count > 0,
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Helpers */
    /* ------------------------------------------------------------------ */

    private function render(string $type, array $data): View
    {
        $data['verifyUrl'] = route('receipts.verify', [$type, $data['reference']]);
        $data['qrDataUri'] = 'data:image/svg+xml;base64,'.base64_encode(
            QrCode::format('svg')->size(120)->generate($data['verifyUrl'])
        );
        $data['issuedAt'] = now();

        return view('receipts.'.$type, $data);
    }

    private function authorizeBookingView(Booking $booking): void
    {
        $user = auth()->user();

        $isPassenger = $booking->passenger_id === $user->id;
        $isDriver = $booking->trip?->driver_id === $user->id;

        abort_unless($isPassenger || $isDriver || $user->role->isAdmin(), 403);
    }

    private function authorizeEarningsView(Booking $booking): void
    {
        $user = auth()->user();
        abort_unless($booking->trip?->driver_id === $user->id || $user->role->isAdmin(), 403);
    }

    private function authorizeTransactionView(Transaction $transaction): void
    {
        $user = auth()->user();
        abort_unless($transaction->wallet?->user_id === $user->id || $user->role->isAdmin(), 403);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()->role === UserRole::Admin, 403);
    }
}
