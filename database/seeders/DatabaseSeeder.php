<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['id' => 1],
            [
                'business_name' => 'Default Store',
                'owner_name'    => 'Store Owner',
                'owner_email'   => 'owner@example.com',
                'owner_phone'   => '03001234567',
                'status'        => 'active',
                'notes'         => 'Default initial store tenant',
            ]
        );

        User::firstOrCreate(
            ['tenant_id' => $tenant->id, 'username' => 'admin'],
            [
                'uuid'           => (string) Str::uuid(),
                'full_name'      => 'Admin User',
                'password'       => Hash::make('password123'),
                'role'           => 'Administrator',
                'permissions'    => [
                    'register', 'inventory', 'orders', 'customers',
                    'khata', 'suppliers', 'cash_drawer', 'overview',
                    'staff', 'settings',
                ],
                'shift_schedule' => 'Flexible / Full Day',
                'max_cash_limit' => 50000.00,
            ]
        );
    }
}
