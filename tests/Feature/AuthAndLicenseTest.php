<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndLicenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_ping_returns_online_status(): void
    {
        $response = $this->getJson('/api/ping');
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    public function test_license_activation_flow(): void
    {
        $tenant = Tenant::create([
            'business_name' => 'Metro Supermarket',
            'owner_name'    => 'Ali Khan',
            'owner_email'   => 'ali@metro.com',
            'status'        => 'active',
        ]);

        $licenseKey = License::generateKey();
        $license = License::create([
            'tenant_id'      => $tenant->id,
            'license_key'    => $licenseKey,
            'plan'           => 'professional',
            'max_counters'   => 0,
            'expires_at'     => now()->addYear(),
            'status'         => 'active',
        ]);

        // Activate key
        $response = $this->postJson('/api/license/activate', [
            'license_key' => $licenseKey,
            'device_id'   => 'COUNTER_01',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'has_users' => false,
                     'tenant' => [
                         'business_name' => 'Metro Supermarket',
                     ],
                 ]);

        $this->assertEquals(1, $license->fresh()->active_devices);
    }

    public function test_master_admin_registration_and_login_flow(): void
    {
        $tenant = Tenant::create([
            'business_name' => 'Apex Mart',
            'owner_name'    => 'Umair',
            'owner_email'   => 'umair@apex.com',
            'status'        => 'active',
        ]);

        $licenseKey = License::generateKey();
        License::create([
            'tenant_id'   => $tenant->id,
            'license_key' => $licenseKey,
            'plan'        => 'starter',
            'expires_at'  => now()->addYear(),
            'status'      => 'active',
        ]);

        // Step 1: Register Master Admin
        $regResponse = $this->postJson('/api/auth/register', [
            'license_key' => $licenseKey,
            'full_name'   => 'Muhammad Umair',
            'username'    => 'admin',
            'password'    => 'password123',
        ]);

        $regResponse->assertStatus(201)
                    ->assertJson([
                        'success' => true,
                        'user' => [
                            'username' => 'admin',
                            'role'     => 'Administrator',
                        ],
                    ]);

        $token = $regResponse->json('token');
        $this->assertNotEmpty($token);

        // Step 2: Attempt duplicate registration (should fail)
        $dupResponse = $this->postJson('/api/auth/register', [
            'license_key' => $licenseKey,
            'full_name'   => 'Second Admin',
            'username'    => 'admin2',
            'password'    => 'password123',
        ]);

        $dupResponse->assertStatus(400)
                    ->assertJson(['code' => 'ADMIN_EXISTS']);

        // Step 3: Login as registered admin on Counter #2
        $loginResponse = $this->postJson('/api/auth/login', [
            'license_key' => $licenseKey,
            'username'    => 'admin',
            'password'    => 'password123',
        ]);

        $loginResponse->assertStatus(200)
                      ->assertJson([
                          'success' => true,
                          'user' => [
                              'username' => 'admin',
                          ],
                      ]);
    }

    public function test_expired_license_is_rejected(): void
    {
        $tenant = Tenant::create([
            'business_name' => 'Expired Store',
            'owner_name'    => 'Test',
            'owner_email'   => 'test@expired.com',
            'status'        => 'active',
        ]);

        $licenseKey = License::generateKey();
        License::create([
            'tenant_id'   => $tenant->id,
            'license_key' => $licenseKey,
            'plan'        => 'starter',
            'expires_at'  => now()->subDay(), // Expired yesterday
            'status'      => 'active',
        ]);

        $response = $this->postJson('/api/license/activate', [
            'license_key' => $licenseKey,
        ]);

        $response->assertStatus(400)
                 ->assertJson(['code' => 'KEY_EXPIRED']);
    }
}
