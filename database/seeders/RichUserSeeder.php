<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\User;
use App\Models\Workplace;
use Database\Seeders\Concerns\InteractsWithDemoData;
use Illuminate\Database\Seeder;

/**
 * Rich demo user base (guide §7 role matrix): 5 workplace admins + 30 paid
 * drivers (Level 3) + 40 passengers (Level 1-2) + 15 driver-and-passenger
 * (Level 3) + 10 volunteers (Level 1). All phone-verified; L2+ carry a hashed
 * NIN; a few prefer women-only rides so the women-only board filter demos live.
 *
 * Emails use the `demoNNN@workride.ng` marker domain so InteractsWithDemoData
 * can make the whole suite re-runnable.
 */
class RichUserSeeder extends Seeder
{
    use InteractsWithDemoData;

    public function run(): void
    {
        $passwordHash = $this->demoPasswordHash();

        $workplaces = Workplace::query()->orderBy('id')->pluck('id')->all();
        if (count($workplaces) < 45) {
            $this->command?->warn('RichUserSeeder expects WorkplaceSeeder to have run (45 MDAs).');
        }
        $cbd = Workplace::query()->where('acronym', 'FMF')->value('id') ?? ($workplaces[1] ?? null);

        $people = [
            // 30 Level-3 paid drivers (Aisha, Musa, ...).
            ['name' => 'Musa Ibrahim', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Grace Emmanuel', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Tunde Adebayo', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Ngozi Okonkwo', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Ibrahim Danladi', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Joy Adamu', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Chidi Okafor', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Halima Sani', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Samuel Eze', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Kemi Adeyemi', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Yakubu Garba', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Blessing Uche', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Abdul Musa', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Mary James', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Emeka Obi', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Fatima Aliyu', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Bello Usman', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Stella Ojo', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Segun Olawale', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Rashida Bello', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Peter Nnaji', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Amina Yusuf', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'female'],
            ['name' => 'David Kalu', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Patricia Ani', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Suleiman Adam', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Efe Oghenekaro', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Mallam Nuhu', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Ifeoma Nwachukwu', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Kabiru Alhassan', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Linda Eze', 'role' => UserRole::Driver, 'level' => 3, 'gender' => 'female'],
            // 15 Level-3 driver + passenger (use own cars for commute, ferry neighbours).
            ['name' => 'Obinna Ibe', 'role' => UserRole::Both, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Zainab Mohammed', 'role' => UserRole::Both, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Femi Akin', 'role' => UserRole::Both, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Chioma Nwosu', 'role' => UserRole::Both, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Ahmed Shehu', 'role' => UserRole::Both, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Esther Bassey', 'role' => UserRole::Both, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Yusuf Mohammed', 'role' => UserRole::Both, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Adaeze Okafor', 'role' => UserRole::Both, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Gideon Ojo', 'role' => UserRole::Both, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Hauwa Ibrahim', 'role' => UserRole::Both, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Kenneth Ama', 'role' => UserRole::Both, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Bukola Adeyemi', 'role' => UserRole::Both, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Ishaku Danjuma', 'role' => UserRole::Both, 'level' => 3, 'gender' => 'male'],
            ['name' => 'Veronica Eze', 'role' => UserRole::Both, 'level' => 3, 'gender' => 'female'],
            ['name' => 'Tobi Adewale', 'role' => UserRole::Both, 'level' => 3, 'gender' => 'male'],
            // 10 Level-1 volunteers (free rides bootstrap).
            ['name' => 'John Paul', 'role' => UserRole::Volunteer, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Mercy Aderogba', 'role' => UserRole::Volunteer, 'level' => 1, 'gender' => 'female'],
            ['name' => 'Kingsley Ugwu', 'role' => UserRole::Volunteer, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Aishat Balogun', 'role' => UserRole::Volunteer, 'level' => 1, 'gender' => 'female'],
            ['name' => 'Nnamdi Eze', 'role' => UserRole::Volunteer, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Bimpe Alabi', 'role' => UserRole::Volunteer, 'level' => 1, 'gender' => 'female'],
            ['name' => 'Rilwan Yusuf', 'role' => UserRole::Volunteer, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Amaka Obi', 'role' => UserRole::Volunteer, 'level' => 1, 'gender' => 'female'],
            ['name' => 'Danladi Isah', 'role' => UserRole::Volunteer, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Ngozi Okafor', 'role' => UserRole::Volunteer, 'level' => 1, 'gender' => 'female'],
            // 40 Level 1-2 passengers (the working-class story).
            ['name' => 'Hassan Danjuma', 'role' => UserRole::Passenger, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Rahmat Abdullahi', 'role' => UserRole::Passenger, 'level' => 1, 'gender' => 'female'],
            ['name' => 'Taiwo Ogunleye', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'male'],
            ['name' => 'Yetunde Bello', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'female'],
            ['name' => 'Solomon Ade', 'role' => UserRole::Passenger, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Favour Okoro', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'female'],
            ['name' => 'Bashir Garba', 'role' => UserRole::Passenger, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Precious Daniel', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'female'],
            ['name' => 'Adamu Sani', 'role' => UserRole::Passenger, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Sade Williams', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'female'],
            ['name' => 'Chukwuma Eze', 'role' => UserRole::Passenger, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Nana Ama', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'female'],
            ['name' => 'Emeka Umeh', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'male'],
            ['name' => 'Lola Adewale', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'female'],
            ['name' => 'Haruna Kabir', 'role' => UserRole::Passenger, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Chioma Eze', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'female'],
            ['name' => 'Okon Etiowo', 'role' => UserRole::Passenger, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Funke Alao', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'female'],
            ['name' => 'Bala Audu', 'role' => UserRole::Passenger, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Uche Nnamdi', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'female'],
            ['name' => 'Philip Ayo', 'role' => UserRole::Passenger, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Kudi Musa', 'role' => UserRole::Passenger, 'level' => 1, 'gender' => 'female'],
            ['name' => 'Rotimi Ajayi', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'male'],
            ['name' => 'Bisola Adeleke', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'female'],
            ['name' => 'Daniel Okafor', 'role' => UserRole::Passenger, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Aisha Mohammed', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'female'],
            ['name' => 'Gerald Nweke', 'role' => UserRole::Passenger, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Tina Osei', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'female'],
            ['name' => 'Umar Farouk', 'role' => UserRole::Passenger, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Shade Bakare', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'female'],
            ['name' => 'Ehis Osagie', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'male'],
            ['name' => 'Yemi Ojo', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'female'],
            ['name' => 'Ibrahim Shehu', 'role' => UserRole::Passenger, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Mariam Adebayo', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'female'],
            ['name' => 'Kolawole Akin', 'role' => UserRole::Passenger, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Rebecca John', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'female'],
            ['name' => 'Jibrin Abubakar', 'role' => UserRole::Passenger, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Toyin Akinola', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'female'],
            ['name' => 'Ikenna Uzo', 'role' => UserRole::Passenger, 'level' => 1, 'gender' => 'male'],
            ['name' => 'Damilola Oseni', 'role' => UserRole::Passenger, 'level' => 2, 'gender' => 'female'],
        ];

        $created = 0;
        foreach ($people as $i => $person) {
            $seq = $i + 1;
            $email = sprintf('demo%03d@workride.ng', $seq);
            $nin = $this->ninFor($email);
            $workplaceId = ($workplaces[$seq % count($workplaces)] ?? $cbd) ?: null;

            User::updateOrCreate(['email' => $email], [
                'name' => $person['name'],
                'password' => $passwordHash,
                'phone' => $this->demoPhone($seq),
                'phone_verified_at' => now()->subDays($seq),
                'gender' => $person['gender'],
                'prefers_women_only' => $person['gender'] === 'female' && $seq % 5 === 0,
                'emergency_contact_name' => 'Emerg '.$person['name'],
                'emergency_contact_phone' => $this->demoPhone(9000 + $seq),
                'role' => $person['role'],
                'verification_level' => $person['level'],
                'workplace_id' => $workplaceId,
                'nin_hash' => $person['level'] >= 2 ? $nin['nin_hash'] : null,
                'nin_last4' => $person['level'] >= 2 ? $nin['nin_last4'] : null,
                'is_banned' => false,
                'has_overdue_ride_credit' => false,
                'green_points' => $person['role'] === UserRole::Volunteer ? (10 * $seq % 40) : 0,
            ]);
            $created++;
        }

        // 5 workplace admins (Level 1, one per flagship MDA).
        $adminWorkplaces = [
            ['name' => 'Mrs Obiageli Eze', 'acronym' => 'FMF'],
            ['name' => 'Mr Tunde Bakare', 'acronym' => 'FMW'],
            ['name' => 'Mrs Amina Sule', 'acronym' => 'FMOT'],
            ['name' => 'Mr Lawal Kolo', 'acronym' => 'NASS'],
            ['name' => 'Mrs Blessing Umeh', 'acronym' => 'CBN'],
        ];

        foreach ($adminWorkplaces as $i => $admin) {
            $seq = 100 + $i + 1;
            $email = sprintf('demo%03d@workride.ng', $seq);
            $workplaceId = Workplace::query()->where('acronym', $admin['acronym'])->value('id') ?? $cbd;
            $nin = $this->ninFor($email);

            User::updateOrCreate(['email' => $email], [
                'name' => $admin['name'],
                'password' => $passwordHash,
                'phone' => $this->demoPhone($seq),
                'phone_verified_at' => now()->subDays($seq),
                'gender' => str_contains($admin['name'], 'Mrs') ? 'female' : 'male',
                'prefers_women_only' => false,
                'role' => UserRole::WorkplaceAdmin,
                'verification_level' => VerificationLevel::WorkplaceVerified,
                'workplace_id' => $workplaceId,
                'nin_hash' => $nin['nin_hash'],
                'nin_last4' => $nin['nin_last4'],
                'is_banned' => false,
                'green_points' => 0,
            ]);
            $created++;
        }

        $this->command?->info(sprintf('Rich demo users seeded: %d accounts (30 drivers, 15 both, 10 volunteers, 40 passengers, 5 workplace admins).', $created));
    }
}
