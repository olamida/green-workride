<?php

namespace App\Services;

use App\Enums\VehicleType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Vehicle registration for drivers and employer fleets. Shared by the rider
 * self-service page and the Control Tower employer flow — one code path for
 * "my staff bus/coaster/danfo is now a WorkRide corridor vehicle".
 */
class VehicleService
{
    public function store(User $owner, array $data): Vehicle
    {
        $data['type'] = $data['type'] ?? VehicleType::Sedan->value;

        $validated = validator($data, [
            'plate_number' => ['required', 'string', 'max:30', Rule::unique('vehicles', 'plate_number')],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:50'],
            'seats' => ['required', 'integer', 'min:1', 'max:100'],
            'type' => ['required', Rule::enum(VehicleType::class)],
        ])->validate();

        return Vehicle::create($validated + [
            'user_id' => $owner->id,
            'papers_verified' => false,
        ]);
    }

    public function assertNotOwned(Vehicle $vehicle, User $user): void
    {
        if ($vehicle->user_id !== $user->id) {
            throw ValidationException::withMessages(['vehicle' => 'This vehicle is not yours.']);
        }
    }
}
