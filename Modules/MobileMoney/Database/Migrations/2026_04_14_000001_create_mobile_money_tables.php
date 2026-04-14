<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mm_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->unique();
            $table->string('terminal_label')->nullable();
            $table->string('terminal_number')->nullable();
            $table->text('receipt_footer')->nullable();
            $table->boolean('auto_assign_reference')->default(true);
            $table->boolean('allow_manual_commission')->default(true);
            $table->timestamps();
        });

        Schema::create('mm_operators', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->string('color', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('mm_commission_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('operator_id')->index();
            $table->string('transaction_type', 30)->index();
            $table->decimal('min_amount', 22, 4)->default(0);
            $table->decimal('max_amount', 22, 4)->nullable();
            $table->string('commission_type', 20)->default('fixed');
            $table->decimal('commission_value', 22, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('mm_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('operator_id')->index();
            $table->unsignedInteger('commission_rule_id')->nullable()->index();
            $table->string('entry_no', 60)->nullable()->index();
            $table->string('type', 30)->index();
            $table->string('status', 30)->default('completed')->index();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone');
            $table->decimal('amount', 22, 4)->default(0);
            $table->decimal('commission', 22, 4)->default(0);
            $table->string('external_reference')->nullable();
            $table->dateTime('operation_datetime')->index();
            $table->text('note')->nullable();
            $table->unsignedInteger('created_by')->nullable()->index();
            $table->dateTime('cancelled_at')->nullable();
            $table->unsignedInteger('cancelled_by')->nullable()->index();
            $table->text('cancellation_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mm_transactions');
        Schema::dropIfExists('mm_commission_rules');
        Schema::dropIfExists('mm_operators');
        Schema::dropIfExists('mm_settings');
    }
};
