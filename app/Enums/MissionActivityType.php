<?php

namespace App\Enums;

/**
 * What a promoted mission measures. `auto` mission types are observed by the
 * app itself from real events (rides completed, potholes confirmed); `custom`
 * is a promoter-defined physical activity that needs photo/location proof.
 */
enum MissionActivityType: string
{
    case VolunteerRides = 'volunteer_rides';
    case PaidRides = 'paid_rides';
    case PassengerRides = 'passenger_rides';
    case PeakHourRides = 'peak_hour_rides';
    case PotholeReports = 'pothole_reports';
    case PotholesConfirmed = 'potholes_confirmed';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::VolunteerRides => 'Free volunteer rides given',
            self::PaidRides => 'Paid trips driven',
            self::PassengerRides => 'Rides taken as a passenger',
            self::PeakHourRides => 'Rides during peak hours',
            self::PotholeReports => 'Road hazards reported',
            self::PotholesConfirmed => 'Potholes you reported & confirmed',
            self::Custom => 'Promoter-defined activity',
        };
    }

    public function isAutoMeasured(): bool
    {
        return $this !== self::Custom;
    }
}
