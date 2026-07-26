<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\Product;
use App\Models\SyncLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SyncEngineTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'business_name' => 'Sync Store',
            'owner_name'    => 'Sync Owner',
            'owner_email'   => 'sync@owner.com',
            'status'        => 'active',
        ]);

        License::create([
            'tenant_id'   => $this->tenant->id,
            'license_key' => License::generateKey(),
            'plan'        => 'professional',
            'expires_at'  => now()->addYear(),
            'status'      => 'active',
        ]);

        $this->user = User::withoutTenantScope()->create([
            'tenant_id' => $this->tenant->id,
            'username'  => 'syncuser',
            'password'  => bcrypt('password'),
            'full_name' => 'Sync User',
            'role'      => 'Administrator',
        ]);
    }

    public function test_push_sync_batch_creation_and_logging(): void
    {
        $prodUuid1 = (string) Str::uuid();
        $prodUuid2 = (string) Str::uuid();

        $pushPayload = [
            'device_id' => 'COUNTER_POS_01',
            'changes'   => [
                'products' => [
                    [
                        'uuid'       => $prodUuid1,
                        'sku'        => 'BAR-1001',
                        'name'       => 'Pushed Coffee 250g',
                        'category'   => 'Beverages',
                        'price'      => 12.50,
                        'cost'       => 6.00,
                        'stock'      => 30,
                        'updated_at' => now()->toDateTimeString(),
                    ],
                    [
                        'uuid'       => $prodUuid2,
                        'sku'        => 'BAR-1002',
                        'name'       => 'Pushed Tea 100g',
                        'category'   => 'Beverages',
                        'price'      => 8.00,
                        'cost'       => 3.50,
                        'stock'      => 45,
                        'updated_at' => now()->toDateTimeString(),
                    ],
                ],
                'categories' => [
                    [
                        'uuid'       => (string) Str::uuid(),
                        'name'       => 'Beverages',
                        'updated_at' => now()->toDateTimeString(),
                    ]
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
                         ->postJson('/api/sync/push', $pushPayload);

        $response->assertStatus(200)
                 ->assertJson([
                     'success'        => true,
                     'records_pushed' => 3,
                 ]);

        // Verify products created in MySQL under tenant
        $this->assertDatabaseHas('products', [
            'tenant_id' => $this->tenant->id,
            'uuid'      => $prodUuid1,
            'sku'       => 'BAR-1001',
            'name'      => 'Pushed Coffee 250g',
        ]);

        // Verify sync log entry
        $this->assertDatabaseHas('sync_logs', [
            'tenant_id'      => $this->tenant->id,
            'device_id'      => 'COUNTER_POS_01',
            'direction'      => 'push',
            'records_pushed' => 3,
        ]);
    }

    public function test_pull_sync_returns_modified_records(): void
    {
        // Seed a product in MySQL
        $prod = Product::create([
            'uuid'       => (string) Str::uuid(),
            'tenant_id'  => $this->tenant->id,
            'sku'        => 'PULL-99',
            'name'       => 'Server Product for Pull',
            'category'   => 'General',
            'price'      => 25.00,
            'cost'       => 10.00,
            'stock'      => 100,
            'synced_at'  => now(),
        ]);

        // Pull request from another device
        $response = $this->actingAs($this->user, 'sanctum')
                         ->getJson('/api/sync/pull?device_id=COUNTER_POS_02');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $pulledProducts = $response->json('changes.products');
        $this->assertCount(1, $pulledProducts);
        $this->assertEquals('Server Product for Pull', $pulledProducts[0]['name']);
    }
}
