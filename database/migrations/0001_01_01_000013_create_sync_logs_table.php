<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('device_id'); // unique per terminal/counter
            $table->enum('direction', ['push', 'pull']);
            $table->json('tables_synced'); // ["products", "orders"]
            $table->integer('records_pushed')->default(0);
            $table->integer('records_pulled')->default(0);
            $table->integer('conflicts_resolved')->default(0);
            $table->enum('status', ['success', 'partial', 'failed'])->default('success');
            $table->text('error_message')->nullable();
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'device_id']);
            $table->index('synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
