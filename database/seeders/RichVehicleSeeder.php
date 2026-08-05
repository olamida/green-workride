<?php

namespace Database\Seeders;

use App\Enums\VehicleType;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\Concerns\InteractsWithDemoData;
use Illuminate\Database\Seeder;

/**
 * Fleet of registered demo vehicles (guide §4 vehicles table). 40 vehicles on
 * the 45 L3 (drivers + both) users: mostly coasters/16-18 seats on the heavy
 * Kubwa corridor, sedans on the lighter Lugbe line, and a couple of danfos on
 * Nyanya–Idu. Every plate is unique; papers_verified = true for the paid gates.
 */
class RichVehicleSeeder extends Seeder
{
    use InteractsWithDemoData;

    public function run(): void
    {
        if ($this->demoSynced()) {
            $this->command?->warn('Rich demo data already present — skipping RichVehicleSeeder.');

            return;
        }

        $l3 = User::query()
            ->where('email', 'like', 'demo%@workride.ng')
            ->where('verification_level', 3)
            ->orderBy('id')
            ->get();

        $specs = [
            // 14 coasters — Kubwa → CBD school runs.
            ...array_fill(0, 14, ['make' => 'Toyota', 'model' => 'Hiace Coaster', 'type' => VehicleType::Coaster, 'seats' => 18, 'color' => 'White']),
            // 8 staff buses / danfos — Nyanya → Idu high-capacity.
            ...array_fill(0, 4, ['make' => 'Toyota', 'model' => 'Hiace Bus', 'type' => VehicleType::StaffBus, 'seats' => 30, 'color' => 'Blue']),
            ...array_fill(0, 4, ['make' => 'Mercedes', 'model' => 'Sprinter', 'type' => VehicleType::Danfo, 'seats' => 22, 'color' => 'Yellow']),
            // 18 sedans — Lugbe → CBD and personal commute ferrying.
            ...array_fill(0, 18, ['make' => 'Toyota', 'model' => 'Corolla', 'type' => VehicleType::Sedan, 'seats' => 4, 'color' => 'Silver']),
        ];

        $created = 0;
        foreach ($l3->take(40) as $i => $user) {
            $spec = $specs[$i % count($specs)];
            $plate = sprintf('DEMO-%03d-ABJ', $i + 1);

            Vehicle::updateOrCreate(['plate_number' => $plate], [
                'user_id' => $user->id,
                'make' => $spec['make'],
                'model' => $spec['model'],
                'color' => $spec['color'],
                'seats' => $spec['seats'],
                'type' => $spec['type'],
                'papers_verified' => true,
            ]);
            $created++;
        }

        $this->command?->info(sprintf('Rich demo vehicles seeded: %d registered (coasters, staff buses, danfos, sedans).', $created));
    }
}
