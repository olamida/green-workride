<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\TripStatus;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\RideRating;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mutual ride ratings (guide driver_scores + trust). Each party to a booking
 * rates the other exactly once after the trip completes; the driver's score
 * is the average rating received. Every rating is change-control audited.
 */
class RatingService
{
    /**
     * @param  array{rating: int, note?: string|null}  $data
     */
    public function rate(User $rater, Booking $booking, array $data): RideRating
    {
        $trip = $booking->trip;
        $ratee = $this->resolveRatee($booking, $rater);

        if ($booking->status !== BookingStatus::Completed && $booking->status !== BookingStatus::Boarded) {
            throw ValidationException::withMessages(['booking' => 'Rides can only be rated after the trip has been completed.']);
        }

        if ($trip->status !== TripStatus::Completed) {
            throw ValidationException::withMessages(['trip' => 'Rides can only be rated once the trip is completed.']);
        }

        $rating = (int) $data['rating'];
        if ($rating < 1 || $rating > 5) {
            throw ValidationException::withMessages(['rating' => 'Rating must be between 1 and 5 stars.']);
        }

        try {
            return DB::transaction(function () use ($booking, $trip, $rater, $ratee, $rating, $data) {
                if (RideRating::where('booking_id', $booking->id)->where('rater_id', $rater->id)->exists()) {
                    throw ValidationException::withMessages(['rating' => 'You have already rated this ride.']);
                }

                $created = RideRating::create([
                    'booking_id' => $booking->id,
                    'trip_id' => $trip->id,
                    'rater_id' => $rater->id,
                    'ratee_id' => $ratee->id,
                    'rating' => $rating,
                    'note' => isset($data['note']) && $data['note'] !== '' ? $data['note'] : null,
                ]);

                ActivityLog::log($rater, 'rated_ride', RideRating::class, $created->id, [
                    'booking_id' => $booking->id,
                    'trip_id' => $trip->id,
                    'ratee_id' => $ratee->id,
                    'rating' => $rating,
                ]);

                return $created;
            });
        } catch (QueryException $exception) {
            // MySQL reports the unique violation as 23000, SQLite as 19.
            if (in_array((string) $exception->getCode(), ['23000', '19'], true)) {
                throw ValidationException::withMessages(['rating' => 'You have already rated this ride.']);
            }

            throw $exception;
        }
    }

    /**
     * The other party to this booking — the passenger rates the driver, the
     * driver rates the passenger. Strangers and admins cannot rate.
     */
    private function resolveRatee(Booking $booking, User $rater): User
    {
        if ($booking->passenger_id === $rater->id) {
            $ratee = $booking->trip->driver;
        } elseif ($booking->trip->driver_id === $rater->id) {
            $ratee = $booking->passenger;
        } else {
            throw ValidationException::withMessages(['booking' => 'Only the driver and passenger on this ride can rate it.']);
        }

        if (! $ratee || $ratee->id === $rater->id) {
            throw ValidationException::withMessages(['booking' => 'This ride cannot be rated.']);
        }

        return $ratee;
    }

    public function averageFor(User $user): ?float
    {
        $avg = $user->ratingsReceived()->avg('rating');

        return $avg === null ? null : round((float) $avg, 2);
    }

    public function countFor(User $user): int
    {
        return $user->ratingsReceived()->count();
    }

    /**
     * Attach `driver_rating_count` / `driver_rating_avg` to a trip (or trips)
     * in ONE grouped query. The board and trip pages show the driver's score
     * without an N+1 and without the nested `withCount('driver.ratingsReceived')`
     * builder aggregate (unsupported for dot-notation in this framework).
     */
    public function attachDriverRating(Trip $trip): Trip
    {
        $this->attachDriverRatingToTrips(collect([$trip]));

        return $trip;
    }

    /**
     * @param  Collection<int, Trip>  $trips
     */
    public function attachDriverRatingToTrips($trips): void
    {
        $driverIds = $trips->pluck('driver_id')->filter()->unique()->values();

        if ($driverIds->isEmpty()) {
            return;
        }

        $aggregates = RideRating::query()
            ->selectRaw('ratee_id as driver_id, COUNT(*) as rating_count, AVG(rating) as rating_avg')
            ->whereIn('ratee_id', $driverIds)
            ->groupBy('ratee_id')
            ->get()
            ->keyBy('driver_id');

        $trips->each(function (Trip $trip) use ($aggregates) {
            $row = $trip->driver_id ? ($aggregates[$trip->driver_id] ?? null) : null;

            $trip->driver_rating_count = (int) ($row->rating_count ?? 0);
            $trip->driver_rating_avg = $row ? (float) $row->rating_avg : null;
        });
    }
}
