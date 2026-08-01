<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Workplace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_user_can_register_with_workplace_and_gets_wallet(): void
    {
        $workplace = Workplace::factory()->create();

        $response = $this->post('/register', [
            'name' => 'Ada Obi',
            'email' => 'ada@example.com',
            'phone' => '08012345678',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::Passenger->value,
            'workplace_id' => $workplace->id,
        ]);

        $response->assertRedirect('/dashboard');

        $user = User::where('email', 'ada@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals(UserRole::Passenger, $user->role);
        $this->assertEquals(0, $user->verification_level->value);
        $this->assertNotNull($user->wallet);

        // Selecting a workplace auto-submits a pending workplace verification.
        $this->assertDatabaseHas('verifications', [
            'user_id' => $user->id,
            'type' => 'workplace_id',
            'status' => 'pending',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_validates_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dupe@example.com']);

        $this->post('/register', [
            'name' => 'Dupe',
            'email' => 'dupe@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::Passenger->value,
        ])->assertSessionHasErrors('email');
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret123')]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_banned_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
            'is_banned' => true,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_register_rejects_invalid_role(): void
    {
        $this->post('/register', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'hacker@example.com']);
    }

    public function test_api_register_returns_sanctum_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'API Rider',
            'email' => 'api@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::Passenger->value,
        ]);

        $response->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'email']]);
        $this->assertDatabaseHas('wallets', [
            'user_id' => User::where('email', 'api@example.com')->first()->id,
        ]);
    }

    public function test_api_login_and_me(): void
    {
        User::factory()->create([
            'email' => 'rider@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'rider@example.com',
            'password' => 'secret123',
        ]);

        $login->assertOk()->assertJsonStructure(['token']);
        $token = $login->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'rider@example.com');
    }

    public function test_api_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('rider-pwa')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_api_login_rejects_bad_credentials(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong',
        ])->assertStatus(422);
    }
}
