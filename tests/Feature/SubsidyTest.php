<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Workplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SubsidyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'verification_level' => VerificationLevel::DriverVerified,
        ]);
    }

    public function test_non_admin_cannot_access_subsidy_console(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/subsidies')->assertForbidden();
    }

    public function test_admin_can_view_subsidy_dashboard(): void
    {
        $admin = $this->admin();
        $workplace = Workplace::factory()->create(['name' => 'Federal Ministry of Works']);
        $staff = User::factory()->create(['workplace_id' => $workplace->id]);
        Wallet::create([
            'user_id' => $staff->id,
            'subsidy_credits' => 25000,
            'cash_balance' => 0,
        ]);

        $this->actingAs($admin)
            ->get('/admin/subsidies')
            ->assertOk()
            ->assertSee('Federal Ministry of Works')
            ->assertSee('25,000.00');
    }

    public function test_admin_can_bulk_credit_subsidies_from_csv(): void
    {
        $admin = $this->admin();
        $workplace = Workplace::factory()->create();
        $alice = User::factory()->create(['email' => 'alice@workride.ng', 'workplace_id' => $workplace->id]);
        $bob = User::factory()->create(['email' => 'bob@workride.ng', 'workplace_id' => $workplace->id]);

        $csv = "email,amount\n"
            ."alice@workride.ng,5000\n"
            ."bob@workride.ng,10000\n";

        $this->actingAs($admin)
            ->post('/admin/subsidies/credit', [
                'workplace_id' => $workplace->id,
                'csv' => UploadedFile::fake()->createWithContent('staff.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertEquals(5000.00, (float) Wallet::where('user_id', $alice->id)->first()->subsidy_credits);
        $this->assertEquals(10000.00, (float) Wallet::where('user_id', $bob->id)->first()->subsidy_credits);

        $this->assertDatabaseHas('transactions', [
            'type' => TransactionType::Subsidy->value,
            'amount' => 5000.00,
        ]);
        $this->assertDatabaseHas('transactions', [
            'type' => TransactionType::Subsidy->value,
            'amount' => 10000.00,
        ]);
    }

    public function test_bulk_credit_skips_unknown_emails_and_bad_rows(): void
    {
        $admin = $this->admin();
        $alice = User::factory()->create(['email' => 'alice@workride.ng']);

        $csv = "email,amount\n"
            ."alice@workride.ng,2000\n"
            ."ghost@nowhere.ng,3000\n"
            ."not-an-email\n";

        $this->actingAs($admin)
            ->post('/admin/subsidies/credit', [
                'csv' => UploadedFile::fake()->createWithContent('staff.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertEquals(2000.00, (float) Wallet::where('user_id', $alice->id)->first()->subsidy_credits);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_bulk_credit_requires_a_file(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/admin/subsidies/credit', [])
            ->assertSessionHasErrors('csv');
    }

    public function test_bulk_credit_rejects_non_csv_files(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/admin/subsidies/credit', [
                'csv' => UploadedFile::fake()->create('staff.pdf', 100),
            ])
            ->assertSessionHasErrors('csv');
    }
}
