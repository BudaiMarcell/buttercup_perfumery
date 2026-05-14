<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_data(): void
    {
        $payload = [
            'name'                  => 'Teszt Elek',
            'email'                 => 'teszt@example.hu',
            'password'              => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'email', 'email_verified'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'teszt@example.hu']);
    }

    public function test_register_rejects_weak_password(): void
    {
        $payload = [
            'name'                  => 'Teszt Elek',
            'email'                 => 'teszt@example.hu',
            'password'              => '12345',
            'password_confirmation' => '12345',
        ];

        $this->postJson('/api/register', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.hu']);

        $this->postJson('/api/register', [
            'name'                  => 'Másik',
            'email'                 => 'taken@example.hu',
            'password'              => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        User::factory()->create([
            'email'    => 'login@example.hu',
            'password' => Hash::make('Secret123!'),
        ]);

        $this->postJson('/api/login', [
            'email'    => 'login@example.hu',
            'password' => 'Secret123!',
        ])->assertOk()
          ->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'login@example.hu',
            'password' => Hash::make('Secret123!'),
        ]);

        $this->postJson('/api/login', [
            'email'    => 'login@example.hu',
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }
}
