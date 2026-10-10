<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Command\Command as CommandCode;

class CreateAdministrator extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Create an administrator account from the server console';

    public function handle(): int
    {
        $name = trim((string) $this->ask('Administrator name'));
        $email = mb_strtolower(trim((string) $this->ask('Administrator email')));
        $password = (string) $this->secret('Administrator password (minimum 12 characters)');
        $passwordConfirmation = (string) $this->secret('Confirm administrator password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return CommandCode::FAILURE;
        }

        if (User::where('role', 'admin')->exists()
            && ! $this->confirm('An administrator already exists. Create an additional administrator?', false)) {
            $this->comment('No administrator account was created.');

            return CommandCode::SUCCESS;
        }

        if (! $this->confirm("Create active administrator account for {$email}?", false)) {
            $this->comment('No administrator account was created.');

            return CommandCode::SUCCESS;
        }

        DB::transaction(fn () => User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'status' => true,
        ]));

        $this->info("Administrator account created for {$email}. Sign in at /admin/login.");

        return CommandCode::SUCCESS;
    }
}
