<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected SuperAdmin $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = SuperAdmin::create([
            'name'     => 'Platform Owner',
            'email'    => 'admin@apexpos.com',
            'password' => bcrypt('admin123456'),
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/admin/login');
    }

    public function test_super_admin_can_login_and_access_dashboard(): void
    {
        $response = $this->post('/admin/login', [
            'email'    => 'admin@apexpos.com',
            'password' => 'admin123456',
        ]);

        $response->assertRedirect('/admin/dashboard');

        $dashboardResponse = $this->actingAs($this->superAdmin, 'super_admin')
                                  ->get('/admin/dashboard');

        $dashboardResponse->assertStatus(200)
                         ->assertSee('Platform Control Dashboard');
    }

    public function test_super_admin_can_generate_license_key(): void
    {
        $response = $this->actingAs($this->superAdmin, 'super_admin')
                         ->post('/admin/licenses/create', [
                             'business_name' => 'Metro Hypermarket',
                             'owner_name'    => 'Ahmed Khan',
                             'owner_email'   => 'ahmed@metro.com',
                             'owner_phone'   => '+923001234567',
                             'plan'          => 'professional',
                             'duration_days' => 365,
                             'max_counters'  => 10,
                         ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('tenants', [
            'business_name' => 'Metro Hypermarket',
            'owner_email'   => 'ahmed@metro.com',
        ]);

        $tenant = Tenant::where('owner_email', 'ahmed@metro.com')->first();
        $this->assertNotNull($tenant);
        $this->assertDatabaseHas('licenses', [
            'tenant_id' => $tenant->id,
            'plan'      => 'professional',
        ]);
    }

    public function test_super_admin_can_toggle_tenant_status(): void
    {
        $tenant = Tenant::create([
            'business_name' => 'Test Mart',
            'owner_name'    => 'Test Owner',
            'owner_email'   => 'test@mart.com',
            'status'        => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin, 'super_admin')
                         ->post("/admin/tenants/{$tenant->id}/toggle-status");

        $response->assertStatus(302);
        $this->assertEquals('suspended', $tenant->fresh()->status);
    }
}
