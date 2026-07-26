<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('license_key', 25)->unique(); // APEX-XXXX-XXXX-XXXX
            $table->enum('plan', ['starter', 'professional', 'enterprise'])->default('starter');
            $table->integer('max_counters')->default(0); // 0 = unlimited
            $table->integer('active_devices')->default(0);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['active', 'expired', 'revoked', 'pending'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('license_key');
            $table->index('status');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
