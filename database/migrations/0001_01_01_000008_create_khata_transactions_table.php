<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('khata_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id');
            $table->string('customer_name');
            $table->string('type'); // DEBT_ADD, REPAYMENT
            $table->decimal('amount', 14, 2);
            $table->decimal('previous_balance', 14, 2)->default(0);
            $table->decimal('new_balance', 14, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->json('items_json')->nullable();
            $table->json('receipt_json')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('khata_transactions');
    }
};
