<?php

namespace App\Console\Commands;

use App\Services\LicenseService;
use Illuminate\Console\Command;

class GenerateLicenseKey extends Command
{
    protected $signature = 'license:generate 
                            {business_name : Name of the business/mart} 
                            {owner_email : Email of the business owner} 
                            {--owner-name= : Name of the owner} 
                            {--owner-phone= : Phone number of the owner} 
                            {--plan=starter : Subscription plan (starter, professional, enterprise)} 
                            {--days=365 : License duration in days} 
                            {--counters=0 : Max counter devices allowed (0 = unlimited)}';

    protected $description = 'Generate a new ApexPOS client tenant and license key';

    public function handle(LicenseService $licenseService): int
    {
        $businessName = $this->argument('business_name');
        $ownerEmail = $this->argument('owner_email');
        $ownerName = $this->option('owner-name') ?? $businessName . ' Owner';
        $ownerPhone = $this->option('owner-phone');
        $plan = strtolower($this->option('plan'));
        $days = (int) $this->option('days');
        $counters = (int) $this->option('counters');

        $this->info("Creating client tenant: {$businessName}...");

        $result = $licenseService->createTenantWithLicense([
            'business_name' => $businessName,
            'owner_name'    => $ownerName,
            'owner_email'   => $ownerEmail,
            'owner_phone'   => $ownerPhone,
        ], $plan, $counters, $days);

        $tenant = $result['tenant'];
        $license = $result['license'];

        $this->newLine();
        $this->info('====================================================');
        $this->info('           APEX-POS LICENSE GENERATED               ');
        $this->info('====================================================');
        $this->line(" License Key    : <fg=yellow;options=bold>{$license->license_key}</>");
        $this->line(" Tenant ID      : {$tenant->id}");
        $this->line(" Business Name  : {$tenant->business_name}");
        $this->line(" Owner Email    : {$tenant->owner_email}");
        $this->line(" Plan           : " . strtoupper($license->plan));
        $this->line(" Max Counters   : " . ($license->max_counters === 0 ? 'UNLIMITED' : $license->max_counters));
        $this->line(" Expires At     : {$license->expires_at->toFormattedDateString()} ({$days} days)");
        $this->info('====================================================');
        $this->newLine();

        return Command::SUCCESS;
    }
}
