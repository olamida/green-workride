<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ChatMessage;
use App\Models\ImpactStat;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\Concerns\InteractsWithDemoData;
use Illuminate\Database\Seeder;

/**
 * Chat + impact analytics demo (guide §2/§17): per-trip chat threads between
 * the driver and boarded passengers, and pre-computed ImpactStat rows so the
 * /impact page and CO₂/fuel certificates render before the first live
 * CalculateImpactJob run.
 */
class RichChatImpactSeeder extends Seeder
{
    use InteractsWithDemoData;

    public function run(): void
    {
        if ($this->demoSynced()) {
            $this->command?->warn('Rich demo data already present — skipping RichChatImpactSeeder.');

            return;
        }

        // --- Chat: one exchange per completed trip's driver/passenger pair. ---
        $chatCreated = 0;
        $tripIds = Trip::query()->where('status', 'completed')->pluck('id')->all();
        $greetings = [
            'Good morning, I am at the junction now.',
            'I can wait 5 minutes at the gate.',
            'Thanks for the ride, see you tomorrow.',
            'What time are you leaving today?',
            'Please drop me at Berger Junction.',
            'I will be there in a moment.',
            'Great, the car is full. See you!',
        ];

        foreach (array_slice($tripIds, 0, 30) as $tripId) {
            $trip = Trip::with('bookings.passenger')->find($tripId);
            if (! $trip) {
                continue;
            }

            $bookings = $trip->bookings
                ->whereIn('status', [BookingStatus::Boarded, BookingStatus::Completed])
                ->take(2);
            if ($bookings->isEmpty()) {
                continue;
            }

            foreach ($bookings as $booking) {
                $passenger = $booking->passenger;
                ChatMessage::create([
                    'trip_id' => $trip->id,
                    'sender_id' => $passenger->id,
                    'message' => $greetings[$chatCreated % count($greetings)],
                    'created_at' => now()->subHours(2 + $chatCreated % 5),
                ]);
                ChatMessage::create([
                    'trip_id' => $trip->id,
                    'sender_id' => $trip->driver_id,
                    'message' => 'I have reached the pickup, coming now.',
                    'created_at' => now()->subHours(1 + $chatCreated % 5),
                ]);
                $chatCreated += 2;
            }
        }

        // --- Impact stats: one per demo user with a completed ride. ---
        $impactCreated = 0;
        $users = User::query()
            ->where('email', 'like', 'demo%@workride.ng')
            ->withCount(['bookings as completed_bookings' => fn ($q) => $q->whereIn('status', ['boarded', 'completed'])])
            ->get();

        $bookings = Booking::query()->whereIn('status', ['boarded', 'completed'])->get();

        foreach ($users as $user) {
            $rides = (int) $user->completed_bookings;
            if ($rides < 1) {
                continue;
            }

            $myBookings = $bookings->where('passenger_id', $user->id);
            $distance = $myBookings->sum(fn (Booking $b) => $b->trip_id ? 10 + $b->trip_id % 15 : 0);

            $co2 = round($rides * 2.3 + $distance * 0.2, 2);
            $fuel = round($rides * 1.1 + $distance * 0.1, 2);
            $trees = round($co2 / 21, 2);

            ImpactStat::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'total_trips' => $rides,
                    'co2_saved_kg' => $co2,
                    'fuel_saved_litres' => $fuel,
                    'trees_equivalent' => $trees,
                    'level' => min(5, 1 + (int) floor($rides / 5)),
                ]
            );
            $impactCreated++;
        }

        $this->command?->info(sprintf('Rich demo chat + impact seeded: %d chat messages, %d impact stats.', $chatCreated, $impactCreated));

        // Last seeder in the chain — mark the whole suite complete so re-runs skip.
        $this->markSuiteSeeded();
    }
}
