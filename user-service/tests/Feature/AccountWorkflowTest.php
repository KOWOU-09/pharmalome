<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AccountWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_creates_a_client_with_separate_names(): void
    {
        $response = $this->postJson('/api/register', [
            'nom' => 'Mensah',
            'prenom' => 'Ama',
            'email' => 'ama@example.com',
            'telephone' => '+228 90 00 00 00',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.role', 'client')
            ->assertJsonPath('user.name', 'Ama Mensah')
            ->assertJsonPath('user.nom', 'Mensah')
            ->assertJsonPath('user.prenom', 'Ama');

        $this->assertDatabaseHas('users', [
            'email' => 'ama@example.com',
            'role' => 'client',
            'actif' => true,
        ]);
    }

    public function test_public_registration_cannot_choose_a_professional_role(): void
    {
        $this->postJson('/api/register', [
            'nom' => 'Admin',
            'prenom' => 'Awa',
            'email' => 'admin@example.com',
            'telephone' => '+228 90 00 00 00',
            'role' => 'admin',
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
        ])->assertCreated()->assertJsonPath('user.role', 'client');
    }

    public function test_inactive_accounts_cannot_log_in(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'secure-pass-123',
            'actif' => false,
        ]);

        $this->postJson('/api/login', [
            'email' => 'inactive@example.com',
            'password' => 'secure-pass-123',
        ])->assertUnauthorized();
    }

    public function test_authenticated_users_can_update_their_profile_without_changing_their_role(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'email' => 'ama@example.com',
        ]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/me', [
                'nom' => 'Mensah',
                'prenom' => 'Ama',
                'email' => 'ama@example.com',
                'telephone' => '+228 91 00 00 00',
                'adresse' => 'Lomé',
                'role' => 'admin',
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Ama Mensah')
            ->assertJsonPath('user.role', 'client');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'telephone' => '+228 91 00 00 00',
            'adresse' => 'Lomé',
            'role' => 'client',
        ]);
    }

    public function test_only_an_administrator_can_create_staff_accounts_and_invites_are_sent(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'sanctum')
            ->withHeader('X-Internal-Key', (string) config('services.internal_key'))
            ->postJson('/api/users', [
                'nom' => 'Agbeko',
                'prenom' => 'Kossi',
                'email' => 'pharmacien@example.com',
                'telephone' => '+228 90 00 00 00',
                'role' => 'pharmacien',
                'qualification' => 'Pharmacien diplômé',
                'numero_ordre' => 'TG-12345',
            ])
            ->assertCreated()
            ->assertJsonPath('data.pharmacien_verifie', false);

        Notification::assertSentTo(
            User::where('email', 'pharmacien@example.com')->first(),
            ResetPassword::class,
        );
    }

    public function test_pharmacist_cannot_be_verified_without_professional_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pharmacist = User::factory()->create(['role' => 'pharmacien']);

        $this->actingAs($admin, 'sanctum')
            ->withHeader('X-Internal-Key', (string) config('services.internal_key'))
            ->patchJson("/api/users/{$pharmacist->id}", ['pharmacien_verifie' => true])
            ->assertUnprocessable();
    }

    public function test_admin_can_verify_a_pharmacist_after_professional_details_are_provided(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pharmacist = User::factory()->create([
            'role' => 'pharmacien',
            'qualification' => 'Pharmacien diplômé',
            'numero_ordre' => 'TG-12345',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->withHeader('X-Internal-Key', 'change-me-internal-key')
            ->patchJson("/api/users/{$pharmacist->id}", ['pharmacien_verifie' => true])
            ->assertOk()
            ->assertJsonPath('data.pharmacien_verifie', true);
    }

    public function test_changing_a_users_role_revokes_their_existing_tokens(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);
        $token = $client->createToken('client-session')->accessToken;

        $this->actingAs($admin, 'sanctum')
            ->withHeader('X-Internal-Key', 'change-me-internal-key')
            ->patchJson("/api/users/{$client->id}", [
                'role' => 'livreur',
                'actif' => true,
            ])
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
    }

    public function test_password_reset_does_not_reactivate_an_admin_disabled_account(): void
    {
        $user = User::factory()->create([
            'email' => 'disabled@example.com',
            'actif' => false,
        ]);
        $token = Password::createToken($user);

        $this->postJson('/api/password/reset', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-secure-pass-123',
            'password_confirmation' => 'new-secure-pass-123',
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'actif' => false,
        ]);
    }

    public function test_authenticated_password_change_requires_the_current_password_and_rotates_tokens(): void
    {
        $user = User::factory()->create(['password' => 'current-password-123']);
        $oldToken = $user->createToken('existing-session')->accessToken;

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/me/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-secure-pass-123',
                'password_confirmation' => 'new-secure-pass-123',
            ])
            ->assertUnprocessable();

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/me/password', [
                'current_password' => 'current-password-123',
                'password' => 'new-secure-pass-123',
                'password_confirmation' => 'new-secure-pass-123',
            ])
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $oldToken->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertIsString($response->json('token'));
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_non_admin_cannot_list_or_create_staff_accounts(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $this->actingAs($client, 'sanctum')
            ->withHeader('X-Internal-Key', 'change-me-internal-key')
            ->getJson('/api/users')
            ->assertForbidden();

        $this->actingAs($client, 'sanctum')
            ->withHeader('X-Internal-Key', 'change-me-internal-key')
            ->postJson('/api/users', [
                'nom' => 'Doe',
                'prenom' => 'Jane',
                'email' => 'staff@example.com',
                'telephone' => '+228 90 00 00 00',
                'role' => 'livreur',
            ])
            ->assertForbidden();
    }

    public function test_admin_user_listing_filters_by_search_and_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $matchingClient = User::factory()->create([
            'name' => 'Chantal AGBEKODO',
            'email' => 'chantal@example.com',
            'telephone' => '91581084',
            'role' => 'client',
        ]);
        User::factory()->create([
            'name' => 'Autre cliente',
            'email' => 'autre@example.com',
            'role' => 'client',
        ]);
        User::factory()->create([
            'name' => 'Chantal Pharmacienne',
            'email' => 'pharmacienne@example.com',
            'role' => 'pharmacien',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->withHeader('X-Internal-Key', (string) config('services.internal_key'))
            ->getJson('/api/users?search=91581084&role=client')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingClient->id);
    }
}
