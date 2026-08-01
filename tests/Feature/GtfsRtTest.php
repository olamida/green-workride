<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Services\GtfsRtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GtfsRtTest extends TestCase
{
    use RefreshDatabase;

    private function rt(): GtfsRtService
    {
        return $this->app->make(GtfsRtService::class);
    }

    public function test_vehicle_positions_feed_includes_only_active_trips_with_coordinates(): void
    {
        $active = Trip::factory()->create(['status' => 'active', 'current_lat' => 9.05, 'current_lng' => 7.45]);
        Trip::factory()->create(['status' => 'scheduled']);
        Trip::factory()->create(['status' => 'active', 'current_lat' => null, 'current_lng' => null]);

        $feed = $this->rt()->vehiclePositionsFeed();
        $message = $this->decode($feed);

        // FeedMessage.header = field 1, FeedMessage.entity = field 2.
        $entities = array_values(array_filter($message, fn ($f) => $f['field'] === 2 && $f['wire'] === 2));
        $this->assertCount(1, $entities);

        $entity = $this->decode($entities[0]['value']);
        $this->assertSame('WR-'.$active->id, $entity[0]['value']);

        // FeedEntity.vehicle must be field 4 (not 8) per the transit_realtime spec.
        $vehicle = array_values(array_filter($entity, fn ($f) => $f['field'] === 4 && $f['wire'] === 2));
        $this->assertCount(1, $vehicle);

        $vehicleMessage = $this->decode($vehicle[0]['value']);

        // VehiclePosition.trip = field 1 -> TripDescriptor.
        $tripDescriptor = $this->decode($vehicleMessage[0]['value']);
        $this->assertSame('WR-'.$active->id, $tripDescriptor[0]['value']);

        // TripDescriptor.start_time must be field 4 (not 5).
        $startTime = array_values(array_filter($tripDescriptor, fn ($f) => $f['field'] === 4 && $f['wire'] === 2));
        $this->assertCount(1, $startTime);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $startTime[0]['value']);

        // VehiclePosition.position = field 2 -> a nested Position message whose
        // two fields are little-endian float latitude/longitude.
        $positionMessage = array_values(array_filter($vehicleMessage, fn ($f) => $f['field'] === 2 && $f['wire'] === 2));
        $this->assertCount(1, $positionMessage);

        $coords = $this->decode($positionMessage[0]['value']);
        $this->assertCount(2, $coords);
        $this->assertEqualsWithDelta(9.05, $coords[0]['value'], 0.0001);
        $this->assertEqualsWithDelta(7.45, $coords[1]['value'], 0.0001);

        // VehiclePosition.timestamp = field 6 (varint).
        $this->assertNotEmpty(array_filter($vehicleMessage, fn ($f) => $f['field'] === 6 && $f['wire'] === 0));
    }

    public function test_feed_header_declares_version_2_and_a_timestamp(): void
    {
        $feed = $this->rt()->vehiclePositionsFeed();
        $message = $this->decode($feed);

        $this->assertSame(1, $message[0]['field']);

        $header = $this->decode($message[0]['value']);

        $this->assertSame('2.0', $header[0]['value']);
        $this->assertSame(3, $header[1]['field']);
        $this->assertIsInt($header[1]['value']);
        $this->assertGreaterThan(0, $header[1]['value']);
    }

    public function test_trip_updates_feed_is_an_empty_snapshot(): void
    {
        Trip::factory()->create(['status' => 'active']);

        $feed = $this->rt()->tripUpdatesFeed();
        $message = $this->decode($feed);

        $this->assertCount(1, $message);
        $this->assertSame(1, $message[0]['field']);
    }

    /**
     * Minimal protobuf wire-format decoder: returns [field, wire, value] tuples.
     * Length-delimited values are returned as raw bytes, varints as int, and
     * 32-bit fixed values as little-endian floats.
     *
     * @return array<int, array{field:int,wire:int,value:int|float|string}>
     */
    private function decode(string $data): array
    {
        $fields = [];
        $offset = 0;
        $length = strlen($data);

        while ($offset < $length) {
            $key = $this->readVarint($data, $offset);
            $field = $key >> 3;
            $wire = $key & 0x07;

            switch ($wire) {
                case 0:
                    $value = $this->readVarint($data, $offset);
                    break;

                case 2:
                    $size = $this->readVarint($data, $offset);
                    $value = substr($data, $offset, $size);
                    $offset += $size;
                    break;

                case 5:
                    $value = unpack('g', substr($data, $offset, 4))[1];
                    $offset += 4;
                    break;

                default:
                    return $fields;
            }

            $fields[] = ['field' => $field, 'wire' => $wire, 'value' => $value];
        }

        return $fields;
    }

    private function readVarint(string $data, int &$offset): int
    {
        $result = 0;
        $shift = 0;

        do {
            $byte = ord($data[$offset]);
            $offset++;
            $result |= ($byte & 0x7F) << $shift;
            $shift += 7;
        } while ($byte & 0x80);

        return $result;
    }
}
