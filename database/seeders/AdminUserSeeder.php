<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (User::query()->where('email', 'admin@laracheck.test')->exists()) {
            $this->command->warn('Admin user already exists. Skipping...');

            return;
        }

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@laracheck.test',
            'password' => 'Password123!',
            'is_admin' => true,
            'receive_email' => true,
            'email_verified_at' => now(),
            'date_format' => 'd/m/Y',
            'timezone' => 'UTC',
        ]);

        $this->command->info('Admin user created successfully!');
        $this->command->newLine();
        $this->command->table(
            ['Field', 'Value'],
            [
                ['Name', 'Admin User'],
                ['Email', 'admin@laracheck.test'],
                ['Password', 'Password123!'],
                ['Is Admin', 'Yes'],
            ]
        );
    }
}
