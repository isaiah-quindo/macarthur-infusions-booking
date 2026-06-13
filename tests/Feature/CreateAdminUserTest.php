<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_an_admin_with_prompted_password(): void
    {
        $this->artisan('admin:create', ['--name' => 'Jane Nurse', '--email' => 'jane@example.com'])
            ->expectsQuestion('Password', 'a-strong-password-123')
            ->expectsQuestion('Confirm password', 'a-strong-password-123')
            ->assertSuccessful();

        $user = User::where('email', 'jane@example.com')->sole();
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertTrue(Hash::check('a-strong-password-123', $user->password));
    }

    public function test_rejects_mismatched_passwords(): void
    {
        $this->artisan('admin:create', ['--name' => 'Jane', '--email' => 'jane@example.com'])
            ->expectsQuestion('Password', 'one-password-here')
            ->expectsQuestion('Confirm password', 'a-different-one')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
    }

    public function test_promotes_an_existing_user_when_confirmed(): void
    {
        User::factory()->create(['email' => 'existing@example.com', 'role' => 'patient']);

        $this->artisan('admin:create', ['--name' => 'Existing', '--email' => 'existing@example.com'])
            ->expectsConfirmation('A user with existing@example.com already exists. Promote to admin and reset their password?', 'yes')
            ->expectsQuestion('Password', 'new-strong-password')
            ->expectsQuestion('Confirm password', 'new-strong-password')
            ->assertSuccessful();

        $this->assertSame(UserRole::Admin, User::where('email', 'existing@example.com')->sole()->role);
    }
}
