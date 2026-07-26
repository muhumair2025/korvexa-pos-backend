<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('shift_no');
            $table->string('cashier_name')->default('Admin');
            $table->string('shift_schedule')->nullable();
            $table->string('terminal_id')->default('Terminal #01');
            $table->decimal('opening_float', 14, 2)->default(200.00);
            $table->decimal('cash_sales', 14, 2)->default(0);
            $table->decimal('card_sales', 14, 2)->default(0);
            $table->decimal('khata_repayments', 14, 2)->default(0);
            $table->decimal('pay_ins', 14, 2)->default(0);
            $table->decimal('pay_outs', 14, 2)->default(0);
            $table->decimal('expected_cash', 14, 2)->default(0);
            $table->decimal('actual_cash', 14, 2)->default(0);
            $table->decimal('difference', 14, 2)->default(0);
            $table->string('status')->default('OPEN'); // OPEN, CLOSED
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'shift_no']);
            $table->index('tenant_id');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
