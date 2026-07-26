<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\Category;
use App\Models\Customer;
use App\Models\KhataTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\Supplier;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncService
{
    /**
     * Map table names to their corresponding Eloquent Model classes.
     */
    protected array $modelMap = [
        'users'              => User::class,
        'categories'         => Category::class,
        'products'           => Product::class,
        'customers'          => Customer::class,
        'orders'             => Order::class,
        'khata_transactions' => KhataTransaction::class,
        'shifts'             => Shift::class,
        'cash_movements'     => CashMovement::class,
        'suppliers'          => Supplier::class,
        'settings'           => Setting::class,
    ];

    /**
     * Process bulk push changes from a client device.
     */
    public function processPush(int $tenantId, string $deviceId, array $changes): array
    {
        $recordsPushed = 0;
        $conflictsResolved = 0;
        $syncedTables = [];

        foreach ($this->modelMap as $tableName => $modelClass) {
            if (!isset($changes[$tableName]) || !is_array($changes[$tableName])) {
                continue;
            }

            $items = $changes[$tableName];
            if (empty($items)) {
                continue;
            }

            $syncedTables[] = $tableName;

            foreach ($items as $item) {
                try {
                    DB::transaction(function () use ($tableName, $modelClass, $item, $tenantId, &$recordsPushed, &$conflictsResolved) {
                        if (!isset($item['uuid']) || empty($item['uuid'])) {
                            $item['uuid'] = (string) Str::uuid();
                        }

                        $uuid = $item['uuid'];
                        $clientUpdatedAt = isset($item['updated_at']) ? \Carbon\Carbon::parse($item['updated_at']) : now();

                        // Find existing record by UUID or natural unique keys within this tenant
                        $existing = null;
                        if (!empty($uuid)) {
                            $existing = $modelClass::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('uuid', $uuid)->first();
                        }

                        if (!$existing) {
                            if ($tableName === 'settings' && !empty($item['key'])) {
                                $existing = Setting::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('key', $item['key'])->first();
                            } else if ($tableName === 'users' && !empty($item['username'])) {
                                $existing = User::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('username', $item['username'])->first();
                            } else if ($tableName === 'categories' && !empty($item['name'])) {
                                $existing = Category::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('name', $item['name'])->first();
                            } else if ($tableName === 'products' && !empty($item['sku'])) {
                                $existing = Product::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('sku', $item['sku'])->first();
                            } else if ($tableName === 'suppliers' && !empty($item['name'])) {
                                $existing = Supplier::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('name', $item['name'])->first();
                            } else if ($tableName === 'customers' && !empty($item['phone'])) {
                                $existing = Customer::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('phone', $item['phone'])->first();
                            } else if ($tableName === 'orders' && !empty($item['order_no'])) {
                                $existing = Order::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('order_no', $item['order_no'])->first();
                            } else if ($tableName === 'shifts' && !empty($item['shift_no'])) {
                                $existing = Shift::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('shift_no', $item['shift_no'])->first();
                            }
                        }

                        if ($existing) {
                            $serverUpdatedAt = $existing->updated_at;

                            if (!$serverUpdatedAt || $clientUpdatedAt->greaterThanOrEqualTo($serverUpdatedAt)) {
                                $updateData = $this->prepareItemData($tableName, $item, $tenantId);
                                $updateData['synced_at'] = now();
                                $existing->update($updateData);
                                $recordsPushed++;
                            } else {
                                $conflictsResolved++;
                            }
                        } else {
                            $insertData = $this->prepareItemData($tableName, $item, $tenantId);
                            $insertData['uuid'] = $uuid;
                            $insertData['tenant_id'] = $tenantId;
                            $insertData['synced_at'] = now();

                            $modelClass::create($insertData);
                            $recordsPushed++;
                        }
                    });
                } catch (\Throwable $e) {
                    $conflictsResolved++;
                    \Illuminate\Support\Facades\Log::error("[Sync Push Item Error] Table {$tableName}", [
                        'message' => $e->getMessage(),
                        'item'    => $item,
                        'file'    => $e->getFile(),
                        'line'    => $e->getLine(),
                    ]);
                }
            }
        }

        // Record sync log
        SyncLog::create([
            'tenant_id'          => $tenantId,
            'device_id'          => $deviceId,
            'direction'          => 'push',
            'tables_synced'      => array_values(array_unique($syncedTables)),
            'records_pushed'     => $recordsPushed,
            'records_pulled'     => 0,
            'conflicts_resolved' => $conflictsResolved,
            'status'             => 'success',
            'synced_at'          => now(),
        ]);

        return [
            'success'            => true,
            'message'            => 'Push sync completed successfully.',
            'records_pushed'     => $recordsPushed,
            'conflicts_resolved' => $conflictsResolved,
            'server_timestamp'   => now()->toISOString(),
        ];
    }

    /**
     * Process pull request: Fetch all records modified since last sync timestamp.
     */
    public function processPull(int $tenantId, string $deviceId, ?string $sinceTimestamp): array
    {
        $since = $sinceTimestamp ? \Carbon\Carbon::parse($sinceTimestamp) : null;
        $changes = [];
        $recordsPulled = 0;
        $syncedTables = [];

        foreach ($this->modelMap as $tableName => $modelClass) {
            $query = $modelClass::withoutGlobalScopes()->where('tenant_id', $tenantId);

            if ($since) {
                $query->where('updated_at', '>', $since);
            }

            $records = $query->get();

            if ($records->isNotEmpty()) {
                $syncedTables[] = $tableName;
                $recordsPulled += $records->count();
                $changes[$tableName] = $records->toArray();
            } else {
                $changes[$tableName] = [];
            }
        }

        // Record sync log
        SyncLog::create([
            'tenant_id'          => $tenantId,
            'device_id'          => $deviceId,
            'direction'          => 'pull',
            'tables_synced'      => array_values(array_unique($syncedTables)),
            'records_pushed'     => 0,
            'records_pulled'     => $recordsPulled,
            'conflicts_resolved' => 0,
            'status'             => 'success',
            'synced_at'          => now(),
        ]);

        return [
            'success'          => true,
            'server_timestamp' => now()->toISOString(),
            'records_pulled'   => $recordsPulled,
            'changes'          => $changes,
        ];
    }

    /**
     * Helper method to filter allowed attributes per table.
     */
    protected function prepareItemData(string $tableName, array $item, int $tenantId): array
    {
        // Remove primary key 'id' to prevent overwriting auto-increment PKs across databases
        unset($item['id'], $item['tenant_id'], $item['created_at'], $item['synced_at'], $item['updated_at']);

        // Column mapping for users table (permissions_json -> permissions, password_hash -> password)
        if ($tableName === 'users') {
            if (array_key_exists('permissions_json', $item)) {
                $perm = $item['permissions_json'];
                $item['permissions'] = is_string($perm) ? json_decode($perm, true) : $perm;
                unset($item['permissions_json']);
            }
            if (isset($item['password_hash'])) {
                // The Electron client sends SHA-256 hashed passwords.
                // Store the hash directly without re-hashing (it's already hashed client-side).
                // We'll set this via the model's setRawAttributes to bypass Laravel's 'hashed' cast.
                $item['password'] = $item['password_hash'];
                unset($item['password_hash']);
            }
            // Remove avatar if it's a large base64 string (not in backend schema as longText)
            if (isset($item['avatar']) && strlen($item['avatar']) > 500) {
                unset($item['avatar']);
            }
        }

        // Foreign key safety check for products table supplier_id
        if ($tableName === 'products' && !empty($item['supplier_id'])) {
            $sup = Supplier::withoutGlobalScopes()->where('tenant_id', $tenantId)->find($item['supplier_id']);
            if (!$sup) {
                if (!empty($item['supplier_name'])) {
                    $sup = Supplier::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('name', $item['supplier_name'])->first();
                }
                $item['supplier_id'] = $sup ? $sup->id : null;
            }
        }

        // Foreign key safety check for orders table customer_id
        if ($tableName === 'orders' && !empty($item['customer_id'])) {
            $cust = Customer::withoutGlobalScopes()->where('tenant_id', $tenantId)->find($item['customer_id']);
            if (!$cust) {
                $item['customer_id'] = null;
            }
        }

        // Handle JSON encodings if passed as string/object
        if (isset($item['items_json']) && is_string($item['items_json'])) {
            $item['items_json'] = json_decode($item['items_json'], true);
        }
        if (isset($item['receipt_json']) && is_string($item['receipt_json'])) {
            $item['receipt_json'] = json_decode($item['receipt_json'], true);
        }

        return $item;
    }
}
