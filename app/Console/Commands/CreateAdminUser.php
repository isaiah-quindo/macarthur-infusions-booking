<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create
                            {--name= : The admin\'s name}
                            {--email= : The admin\'s email}
                            {--super : Create as super admin (does not receive booking emails)}';

    protected $description = 'Create (or promote) an admin user for the booking clinic';

    public function handle(): int
    {
        $name = $this->option('name') ?: text(
            label: 'Name',
            required: true,
        );

        $email = $this->option('email') ?: text(
            label: 'Email',
            required: true,
        );

        $validator = Validator::make(['name' => $name, 'email' => $email], [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $role = $this->option('super') ? UserRole::SuperAdmin : UserRole::Admin;

        $existing = User::where('email', $email)->first();

        if ($existing) {
            $verb = $role === UserRole::SuperAdmin ? 'super admin' : 'admin';
            if (! $this->confirm("A user with {$email} already exists. Promote to {$verb} and reset their password?", default: false)) {
                $this->warn('Aborted.');

                return self::FAILURE;
            }
        }

        // Password is prompted (never passed as a flag, so it stays out of
        // shell history) and confirmed.
        $plain = password(
            label: 'Password',
            required: true,
            validate: fn (string $value) => strlen($value) < 12
                ? 'The password must be at least 12 characters.'
                : null,
        );

        $confirm = password(label: 'Confirm password', required: true);

        if ($plain !== $confirm) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($plain),
                'role' => $role,
            ],
        );

        $label = $role === UserRole::SuperAdmin ? 'super admin' : 'admin';
        $this->info(($existing ? 'Updated' : 'Created')." {$label}: {$user->name} <{$user->email}>");
        $this->line('They can log in at /admin/login');
        if ($role === UserRole::SuperAdmin) {
            $this->line('Note: super admins do NOT receive booking notification emails.');
        }

        return self::SUCCESS;
    }
}
