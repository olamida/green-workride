<?php

namespace Database\Seeders;

use App\Enums\RideCreditStatus;
use App\Models\Booking;
use App\Models\RideCredit;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\Concerns\InteractsWithDemoData;
use Illuminate\Database\Seeder;

/**
 * Time-Bank (guide §3.5): 30 ride credits ("ride now, drive later") spread
 * across demo passengers. Owed credits are repaid by driving; overdue credits
 * block further booking; waived credits model a cancelled/no-show cleanup.
 * All reference a real trip/booking so the receipts and lifecycle demo live.
 */
class RichRideCreditSeeder extends Seeder
{
    use InteractsWithDemoData;

    public function run(): void
    {
        if ($this->demoSynced()) {
            $this->command?->warn('Rich demo data already present — skipping RichRideCreditSeeder.');

            return;
        }

        $bookings = Booking::query()
            ->with('trip')
            ->whereIn('status', ['completed', 'no_show', 'cancelled'])
            ->get();

        $credits = [
            ['status' => RideCreditStatus::Owed, 'repaid' => 0, 'due_offset_days' => 3],
            ['status' => RideCreditStatus::Owed, 'repaid' => 0, 'due_offset_days' => 5],
            ['status' => RideCreditStatus::Repaid, 'repaid' => 1, 'due_offset_days' => -2],
            ['status' => RideCreditStatus::Repaid, 'repaid' => 2, 'due_offset_days' => -5],
            ['status' => RideCreditStatus::Overdue, 'repaid' => 0, 'due_offset_days' => -8],
            ['status' => RideCreditStatus::Overdue, 'repaid' => 0, 'due_offset_days' => -12],
            ['status' => RideCreditStatus::Waived, 'repaid' => 0, 'due_offset_days' => -1],
            ['status' => RideCreditStatus::Waived, 'repaid' => 0, 'due_offset_days' => -3],
        ];

        $created = 0;
        for ($i = 0; $i < 30; $i++) {
            $spec = $credits[$i % count($credits)];
            $booking = $bookings[$i % $bookings->count()];

            if (! $booking || ! $booking->trip) {
                continue;
            }

            $seatsOwed = 1 + ($i % 2);
            $fareValue = (float) $booking->fare_paid > 0
                ? (float) $booking->fare_paid
                : $seatsOwed * (float) config('workride.time_bank.avg_fare_per_seat', 600);

            RideCredit::updateOrCreate(
                ['user_id' => $booking->passenger_id, 'booking_id' => $booking->id],
                [
                    'trip_id' => $booking->trip_id,
                    'seats_owed' => $seatsOwed,
                    'seats_repaid' => $spec['repaid'],
                    'fare_value' => $fareValue,
                    'due_date' => now()->addDays($spec['due_offset_days']),
                    'status' => $spec['status'],
                ]
            );
            $created++;
        }

        // Mark the users holding overdue credits so the booking gate blocks them.
        $overdueUsers = RideCredit::query()
            ->where('status', RideCreditStatus::Overdue)
            ->pluck('user_id');

        User::query()->whereIn('id', $overdueUsers)->update(['has_overdue_ride_credit' => true]);

        $this->command?->info(sprintf('Rich demo ride credits seeded: %d (owed/repaid/overdue/waived).', $created));
    }
}
