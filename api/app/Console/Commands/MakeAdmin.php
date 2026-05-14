<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class MakeAdmin extends Command
{
    protected $signature = 'make:admin
        {email : The admin email address}
        {--password= : Admin password (prompts if omitted)}
        {--role=admin : Role to assign (default: admin)}
        {--force : Promote an existing user to admin instead of failing}';

    protected $description = 'Create a new admin account (safe replacement for AdminSeeder in production).';

    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));
        $role  = (string) $this->option('role');
        $force = (bool)   $this->option('force');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email: {$email}");
            return self::FAILURE;
        }

        $existingUser  = User::where('email', $email)->first();
        $existingAdmin = Admin::where('email', $email)->first();

        if ($existingAdmin && ! $force) {
            $this->error("Admin with email {$email} already exists. Use --force to overwrite.");
            return self::FAILURE;
        }

        $password = $this->option('password') ?: $this->promptForPassword();
        if ($password === null) {
            return self::FAILURE;
        }

        $validation = Validator::make(
            ['password' => $password],
            ['password' => ['required', Password::min(10)->mixedCase()->numbers()->symbols()]]
        );
        if ($validation->fails()) {
            foreach ($validation->errors()->all() as $msg) {
                $this->error($msg);
            }
            return self::FAILURE;
        }

        $hashed = Hash::make($password);

        User::updateOrCreate(
            ['email' => $email],
            [
                'name'     => $existingUser->name ?? 'Admin',
                'password' => $hashed,
            ]
        );

        Admin::updateOrCreate(
            ['email' => $email],
            [
                'name'     => $existingAdmin->name ?? 'Admin',
                'password' => $hashed,
                'role'     => $role,
            ]
        );

        $this->info("Admin account ready: {$email} (role: {$role})");
        return self::SUCCESS;
    }

    private function promptForPassword(): ?string
    {
        $password = $this->secret('Password');
        $confirm  = $this->secret('Confirm password');

        if ($password !== $confirm) {
            $this->error('Passwords do not match.');
            return null;
        }

        return $password;
    }
}
