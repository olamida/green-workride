<?php

namespace App\Enums;

/**
 * The action/achievement that unlocks a reward. Wired into the existing
 * event flows so rewards pay out automatically, never on trust.
 */
enum RewardTrigger: string
{
    case TripCompleted = 'trip_completed';
    case VolunteerRide = 'volunteer_ride';
    case WeeklyFiveRides = 'weekly_five_rides';
    case MonthlyTenRides = 'monthly_ten_rides';
    case PotholeConfirmed = 'pothole_confirmed';

    public function label(): string
    {
        return match ($this) {
            self::TripCompleted => 'Completed a trip',
            self::VolunteerRide => 'Gave a free volunteer ride',
            self::WeeklyFiveRides => '5 rides in a week',
            self::MonthlyTenRides => '10 rides in a month',
            self::PotholeConfirmed => 'Confirmed a pothole report',
        };
    }
}
