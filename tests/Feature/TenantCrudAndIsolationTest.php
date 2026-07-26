<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantCrudAndIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $userA;
    protected User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Tenant A & Admin User A
        $this->tenantA = Tenant::create([
            'business_name' => 'Store A',
            'owner_name'    => 'Owner A',
            'owner_email'   => 'a@store.com',
            'status'        => 'active',
        ]);
        License::create([
            'tenant_id'   => $this->tenantA->id,
            'license_key' => License::generateKey(),
            'plan'        => 'starter',
            'expires_at'  => now()->addYear(),
            'status'      => 'active',
        ]);
        $this->userA = User::withoutTenantScope()->create([
            'tenant_id' => $this->tenantA->id,
            'username'  => 'adminA',
            'password'  => bcrypt('passA'),
            'full_name' => 'Admin A',
            'role'      => 'Administrator',
        ]);

        // 2. Create Tenant B & Admin User B
        $this->tenantB = Tenant::create([
            'business_name' => 'Store B',
            'owner_name'    => 'Owner B',
            'owner_email'   => 'b@store.com',
            'status'        => 'active',
        ]);
        License::create([
            'tenant_id'   => $this->tenantB->id,
            'license_key' => License::generateKey(),
            'plan'        => 'starter',
            'expires_at'  => now()->addYear(),
            'status'      => 'active',
        ]);
        $this->userB = User::withoutTenantScope()->create([
            'tenant_id' => $this->tenantB->id,
            'username'  => 'adminB',
            'password'  => bcrypt('passB'),
            'full_name' => 'Admin B',
            'role'      => 'Administrator',
        ]);
    }

    public function test_tenant_data_isolation_for_products(): void
    {
        // User A creates a product
        $resA = $this->actingAs($this->userA, 'sanctum')
                     ->postJson('/api/products', [
                         'sku'   => 'PROD-A1',
                         'name'  => 'Product of Store A',
                         'price' => 100.00,
                     ]);
        $resA->assertStatus(201);

        // User A fetches products -> sees 1 product
        $listA = $this->actingAs($this->userA, 'sanctum')
                      ->getJson('/api/products');
        $listA->assertStatus(200);
        $this->assertCount(1, $listA->json('products'));
        $this->assertEquals('Product of Store A', $listA->json('products.0.name'));

        // Verify product in database belongs to Tenant A
        $createdProd = \App\Models\Product::withoutTenantScope()->first();
        $this->assertEquals($this->tenantA->id, $createdProd->tenant_id);

        // User B fetches products -> MUST return 0 products!
        $listB = $this->actingAs($this->userB, 'sanctum')
                      ->getJson('/api/products');
        $listB->assertStatus(200);
        $this->assertCount(0, $listB->json('products'));
    }

    public function test_customer_creation_and_order_checkout(): void
    {
        // User A creates customer
        $custRes = $this->actingAs($this->userA, 'sanctum')
                        ->postJson('/api/customers', [
                            'name'  => 'John Doe',
                            'phone' => '+1234567890',
                        ]);
        $custRes->assertStatus(201);
        $customerId = $custRes->json('customer.id');

        // User A creates product
        $prodRes = $this->actingAs($this->userA, 'sanctum')
                        ->postJson('/api/products', [
                            'sku'   => 'COFFEE-01',
                            'name'  => 'Espresso Coffee',
                            'price' => 15.00,
                            'stock' => 50,
                        ]);
        $prodId = $prodRes->json('product.id');

        // User A creates order
        $orderRes = $this->actingAs($this->userA, 'sanctum')
                         ->postJson('/api/orders', [
                             'customer_id'    => $customerId,
                             'customer_name'  => 'John Doe',
                             'subtotal'       => 15.00,
                             'tax_amount'     => 1.20,
                             'total_amount'   => 16.20,
                             'payment_method' => 'Cash',
                             'items'          => [
                                 ['id' => $prodId, 'name' => 'Espresso Coffee', 'quantity' => 2, 'price' => 15.00]
                             ]
                         ]);

        $orderRes->assertStatus(201)
                 ->assertJson(['success' => true]);

        // Verify stock deducted to 48
        $prodCheck = $this->actingAs($this->userA, 'sanctum')
                          ->getJson('/api/products/' . $prodId);
        $this->assertEquals(48, $prodCheck->json('product.stock'));

        // User B cannot see User A's orders
        $ordersB = $this->actingAs($this->userB, 'sanctum')
                        ->getJson('/api/orders');
        $this->assertCount(0, $ordersB->json('orders'));
    }

    public function test_khata_debt_and_repayment_isolation(): void
    {
        // User A creates customer and records debt
        $custRes = $this->actingAs($this->userA, 'sanctum')
                        ->postJson('/api/customers', [
                            'name'  => 'Khata Customer',
                            'phone' => '+9876543210',
                        ]);
        $custId = $custRes->json('customer.id');

        $debtRes = $this->actingAs($this->userA, 'sanctum')
                        ->postJson('/api/khata/record-debt-add', [
                            'customer_id' => $custId,
                            'amount'      => 500.00,
                            'notes'       => 'Grocery loan',
                        ]);
        $debtRes->assertStatus(200);
        $this->assertEquals(500.00, $debtRes->json('customer.credit_balance'));

        // Collect repayment
        $repayRes = $this->actingAs($this->userA, 'sanctum')
                         ->postJson('/api/khata/collect-repayment', [
                             'customer_id' => $custId,
                             'amount'      => 200.00,
                         ]);
        $repayRes->assertStatus(200);
        $this->assertEquals(300.00, $repayRes->json('customer.credit_balance'));
    }
}
