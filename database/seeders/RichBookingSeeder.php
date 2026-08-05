<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\Corridor;
use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Models\Booking;
use App\Models\Transaction;
use App\Models\Trip;
use App\Models\User;
use App\Models\Wallet;
use Database\Seeders\Concerns\InteractsWithDemoData;
use Illuminate\Database\Seeder;

/**
 * 150 bookings across the demo trips (guide §6 workflow 1). Seat-safe by
 * construction: each trip gets at most `total_seats` passengers and the
 * (trip_id, passenger_id) pair is unique. Payment methods mirror the real
 * wallet flow (wallet hold → capture, subsidy first, cash collected, free
 * volunteer); completed bookings write the matching capture transactions so
 * the Business dashboard revenue/charts are populated. A few no-shows and
 * cancelled rides give the driver-score and refund paths data too.
 */
class RichBookingSeeder extends Seeder
{
    use InteractsWithDemoData;

    public function run(): void
    {
        if ($this->demoSynced()) {
            $this->command?->warn('Rich demo data already present — skipping RichBookingSeeder.');

            return;
        }

        $passengers = User::query()
            ->where('email', 'like', 'demo%@workride.ng')
            ->whereNotIn('role', ['driver'])
            ->orderBy('id')
            ->get();

        if ($passengers->isEmpty()) {
            $this->command?->error('RichBookingSeeder needs demo passengers first.');

            return;
        }

        $created = 0;
        $captured = 0;

        // 1) Completed trips: full carriage, completed bookings + captures.
        foreach (Trip::query()->where('status', 'completed')->get() as $i => $trip) {
            $corridor = $trip->corridor instanceof Corridor ? $trip->corridor->value : (string) $trip->corridor;
            $seats = min((int) $trip->total_seats, 18);
            $taken = [];

            foreach (range(1, $seats) as $seatNo) {
                $passenger = $passengers[($i * 5 + $seatNo) % $passengers->count()];
                if (in_array($passenger->id, $taken, true)) {
                    continue;
                }
                $taken[] = $passenger->id;

                $volunteer = (bool) $trip->is_free_volunteer;
                $isNoShow = $seatNo % 13 === 0;
                $isCancelled = $seatNo % 17 === 0;
                $method = $this->methodFor($seatNo, $volunteer, $corridor);

                $booking = Booking::create([
                    'trip_id' => $trip->id,
                    'passenger_id' => $passenger->id,
                    'pickup_lat' => $trip->current_lat,
                    'pickup_lng' => $trip->current_lng,
                    'status' => $isCancelled
                        ? BookingStatus::Cancelled
                        : ($isNoShow ? BookingStatus::NoShow : BookingStatus::Completed),
                    'fare_paid' => $volunteer ? 0 : (float) $trip->fare_per_seat,
                    'payment_method' => $method,
                ]);

                if ($isNoShow) {
                    $this->writeHoldAndCapture($booking, $method, capturePercent: 50);
                } elseif (! $isCancelled && ! $volunteer && in_array($method, [PaymentMethod::Wallet, PaymentMethod::SubsidyCredit], true)) {
                    $this->writeHoldAndCapture($booking, $method);
                    $captured++;
                }
                $created++;
            }

            $trip->forceFill(['available_seats' => 0])->save();
        }

        // 2) Active trips: confirmed + boarded bookings, partial seats held.
        foreach (Trip::query()->where('status', 'active')->get() as $i => $trip) {
            $corridor = $trip->corridor instanceof Corridor ? $trip->corridor->value : (string) $trip->corridor;
            $bookCount = max((int) $trip->total_seats - (int) $trip->available_seats, 2);
            $taken = [];

            foreach (range(1, $bookCount) as $seatNo) {
                $passenger = $passengers[($i * 7 + $seatNo) % $passengers->count()];
                if (in_array($passenger->id, $taken, true)) {
                    continue;
                }
                $taken[] = $passenger->id;

                $volunteer = (bool) $trip->is_free_volunteer;
                $method = $this->methodFor($seatNo, $volunteer, $corridor);
                $boarded = $seatNo % 3 === 0;

                $booking = Booking::create([
                    'trip_id' => $trip->id,
                    'passenger_id' => $passenger->id,
                    'pickup_lat' => (float) $trip->current_lat,
                    'pickup_lng' => (float) $trip->current_lng,
                    'status' => $boarded ? BookingStatus::Boarded : BookingStatus::Confirmed,
                    'fare_paid' => $volunteer ? 0 : (float) $trip->fare_per_seat,
                    'payment_method' => $method,
                ]);

                if ($boarded && ! $volunteer && in_array($method, [PaymentMethod::Wallet, PaymentMethod::SubsidyCredit], true)) {
                    $this->writeHoldAndCapture($booking, $method);
                    $captured++;
                }
                $created++;
            }
        }

        // 3) Scheduled trips: a few confirmed seats + some cash requests.
        foreach (Trip::query()->where('status', 'scheduled')->get() as $i => $trip) {
            $corridor = $trip->corridor instanceof Corridor ? $trip->corridor->value : (string) $trip->corridor;
            $bookCount = 1 + ($i % 4);
            $taken = [];

            foreach (range(1, $bookCount) as $seatNo) {
                $passenger = $passengers[($i * 11 + $seatNo * 3) % $passengers->count()];
                if (in_array($passenger->id, $taken, true)) {
                    continue;
                }
                $taken[] = $passenger->id;

                $volunteer = (bool) $trip->is_free_volunteer;
                $method = $this->methodFor($seatNo, $volunteer, $corridor);

                Booking::create([
                    'trip_id' => $trip->id,
                    'passenger_id' => $passenger->id,
                    'pickup_lat' => (float) $trip->current_lat,
                    'pickup_lng' => (float) $trip->current_lng,
                    'status' => BookingStatus::Confirmed,
                    'fare_paid' => $volunteer ? 0 : (float) $trip->fare_per_seat,
                    'payment_method' => $method,
                ]);
                $created++;
            }

            $trip->forceFill([
                'available_seats' => max((int) $trip->total_seats - $bookCount, 0),
            ])->save();
        }

        // 4) Cancelled trips: mostly empty, a couple of refunded bookings.
        foreach (Trip::query()->where('status', 'cancelled')->get() as $i => $trip) {
            if ($i % 3 !== 0) {
                continue;
            }
            $passenger = $passengers[($i * 13) % $passengers->count()];
            $method = $i % 2 === 0 ? PaymentMethod::Wallet : PaymentMethod::Cash;

            $booking = Booking::create([
                'trip_id' => $trip->id,
                'passenger_id' => $passenger->id,
                'pickup_lat' => (float) $trip->current_lat,
                'pickup_lng' => (float) $trip->current_lng,
                'status' => BookingStatus::Cancelled,
                'fare_paid' => (float) $trip->fare_per_seat,
                'payment_method' => $method,
            ]);

            if ($method === PaymentMethod::Wallet) {
                $this->writeHoldAndRefund($booking, $method);
            }
            $created++;
        }

        $this->command?->info(sprintf('Rich demo bookings seeded: %d across completed/active/scheduled/cancelled trips (%d wallet captures).', $created, $captured));
    }

    private function methodFor(int $seatNo, bool $volunteer, string $corridor): PaymentMethod
    {
        if ($volunteer) {
            return PaymentMethod::Free;
        }

        return match ($seatNo % 5) {
            0 => PaymentMethod::Cash,
            1 => PaymentMethod::SubsidyCredit,
            2 => PaymentMethod::RideCredit,
            3 => PaymentMethod::Wallet,
            default => PaymentMethod::Wallet,
        };
    }

    private function writeHoldAndCapture(Booking $booking, PaymentMethod $method, int $capturePercent = 100): void
    {
        $wallet = $booking->passenger->wallet ?? Wallet::updateOrCreate(['user_id' => $booking->passenger_id], []);
        $fare = (float) $booking->fare_paid;
        if ($fare <= 0) {
            return;
        }

        Transaction::updateOrCreate(['reference' => $this->demoReference('HOLD', $booking->id)], [
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Hold,
            'amount' => $fare,
            'description' => 'Fare hold '.$booking->id,
            'meta' => ['booking_id' => $booking->id, 'demo' => true],
            'created_at' => $booking->created_at,
        ]);

        $capture = $capturePercent === 100 ? $fare : round($fare * $capturePercent / 100, 2);
        Transaction::updateOrCreate(['reference' => $this->demoReference('CAP', $booking->id)], [
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Capture,
            'amount' => $capture,
            'description' => 'Fare captured '.$booking->id,
            'meta' => ['booking_id' => $booking->id, 'demo' => true],
            'created_at' => $booking->created_at,
        ]);
    }

    private function writeHoldAndRefund(Booking $booking, PaymentMethod $method): void
    {
        $wallet = $booking->passenger->wallet ?? Wallet::updateOrCreate(['user_id' => $booking->passenger_id], []);
        $fare = (float) $booking->fare_paid;
        if ($fare <= 0) {
            return;
        }

        Transaction::updateOrCreate(['reference' => $this->demoReference('HOLD', $booking->id)], [
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Hold,
            'amount' => $fare,
            'description' => 'Fare hold '.$booking->id,
            'meta' => ['booking_id' => $booking->id, 'demo' => true],
            'created_at' => $booking->created_at,
        ]);

        Transaction::updateOrCreate(['reference' => $this->demoReference('REF', $booking->id)], [
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Refund,
            'amount' => $fare,
            'description' => 'Fare refunded '.$booking->id,
            'meta' => ['booking_id' => $booking->id, 'demo' => true],
            'created_at' => $booking->created_at,
        ]);
    }
}
