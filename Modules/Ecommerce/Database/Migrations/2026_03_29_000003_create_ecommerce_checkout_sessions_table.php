<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ecom_checkout_sessions')) {
            Schema::create('ecom_checkout_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('store_id');
                $table->unsignedInteger('business_id');
                $table->unsignedBigInteger('ecom_customer_id')->nullable();
                $table->unsignedInteger('contact_id')->nullable();
                $table->unsignedInteger('transaction_id')->nullable();
                $table->string('token')->unique();
                $table->string('mode')->default('cart');
                $table->string('status')->default('pending');
                $table->string('stripe_session_id')->nullable()->unique();
                $table->string('stripe_payment_intent_id')->nullable();
                $table->decimal('amount', 22, 4)->default(0);
                $table->string('currency_code', 12)->nullable();
                $table->longText('cart_snapshot');
                $table->longText('checkout_context')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('failure_reason')->nullable();
                $table->timestamps();

                $table->index(['store_id', 'status']);
                $table->index(['business_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ecom_checkout_sessions');
    }
};
