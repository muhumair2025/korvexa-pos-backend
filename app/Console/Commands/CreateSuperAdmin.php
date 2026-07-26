<?php

namespace App\Console\Commands;

use App\Models\SuperAdmin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdmin extends Command
{
    protected $signature = 'superadmin:create 
                            {name : Name of the platform administrator} 
                            {email : Email address for admin login} 
                            {password : Secure login password}';

    protected $description = 'Create a super-admin account for the ApexPOS platform web dashboard';

    public function handle(): int
    {
        $name = $this->argument('name');
        $email = strtolower(trim($this->argument('email')));
        $password = $this->argument('password');

        if (SuperAdmin::where('email', $email)->exists()) {
            $this->error("Super admin account with email {$email} already exists.");
            return Command::FAILURE;
        }

        $admin = SuperAdmin::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
        ]);

        $this->newLine();
        $this->info('====================================================');
        $this->info('           SUPER ADMIN CREATED SUCCESSFULLY         ');
        $this->info('====================================================');
        $this->line(" Name     : {$admin->name}");
        $this->line(" Email    : {$admin->email}");
        $this->line(" Access   : Web Admin Panel (/admin)");
        $this->info('====================================================');
        $this->newLine();

        return Command::SUCCESS;
    }
}
