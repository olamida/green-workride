<?php

namespace App\Services;

use App\Enums\TripStatus;
use App\Models\Trip;

/**
 * Minimal hand-rolled protobuf encoder for the Google GTFS-realtime
 * "transit_realtime" schema. Emits a VehiclePositions feed (FeedMessage)
 * from live (active) trips so Google's Transit partner service can poll
 * real-time positions. Wire format only — no protobuf runtime dependency.
 */
class GtfsRtService
{
    public function vehiclePositionsFeed(): string
    {
        $entities = '';

        Trip::query()
            ->where('status', TripStatus::Active)
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->get()
            ->each(function (Trip $trip) use (&$entities) {
                $entities .= $this->fieldString(2, $this->feedEntity($trip));
            });

        return $this->fieldString(1, $this->feedHeader()).$entities;
    }

    /**
     * A valid but empty FeedMessage (header only) for trip_updates — no delays
     * are modelled yet, so consumers get an empty snapshot.
     */
    public function tripUpdatesFeed(): string
    {
        return $this->fieldString(1, $this->feedHeader());
    }

    private function feedHeader(): string
    {
        return $this->fieldString(1, '2.0')
            .$this->fieldVarint(3, time());
    }

    private function feedEntity(Trip $trip): string
    {
        return $this->fieldString(1, 'WR-'.$trip->id)
            .$this->fieldString(4, $this->vehiclePosition($trip));
    }

    private function vehiclePosition(Trip $trip): string
    {
        $message = $this->fieldString(1, $this->tripDescriptor($trip));
        $message .= $this->fieldString(2, $this->position((float) $trip->current_lat, (float) $trip->current_lng));
        $message .= $this->fieldVarint(6, time());

        return $message;
    }

    private function tripDescriptor(Trip $trip): string
    {
        $message = $this->fieldString(1, 'WR-'.$trip->id);
        $message .= $this->fieldString(2, $trip->corridor->short());

        if ($trip->departure_time !== null) {
            $message .= $this->fieldString(4, $trip->departure_time->format('H:i:s'));
        }

        return $message;
    }

    private function position(float $lat, float $lng): string
    {
        return $this->fieldFloat(1, $lat).$this->fieldFloat(2, $lng);
    }

    private function fieldString(int $field, string $value): string
    {
        return $this->tag($field, 2).$this->varint(strlen($value)).$value;
    }

    private function fieldVarint(int $field, int $value): string
    {
        return $this->tag($field, 0).$this->varint($value);
    }

    private function fieldFloat(int $field, float $value): string
    {
        return $this->tag($field, 5).pack('V', unpack('V', pack('f', $value))[1]);
    }

    private function tag(int $field, int $wireType): string
    {
        return $this->varint(($field << 3) | $wireType);
    }

    private function varint(int $value): string
    {
        $bytes = '';

        do {
            $byte = $value & 0x7F;
            $value >>= 7;

            if ($value > 0) {
                $byte |= 0x80;
            }

            $bytes .= chr($byte);
        } while ($value > 0);

        return $bytes;
    }
}
