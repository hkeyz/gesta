<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ecom_customers')) {
            Schema::create('ecom_customers', function (Blueprint $table) {
                $table->id();
                $table->string('first_name');
                $table->string('last_name')->nullable();
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->string('password');
                $table->timestamp('email_verified_at')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ecom_customer_business_contacts')) {
            Schema::create('ecom_customer_business_contacts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ecom_customer_id');
                $table->unsignedInteger('business_id');
                $table->unsignedInteger('contact_id');
                $table->timestamps();

                $table->unique(['ecom_customer_id', 'business_id'], 'ecom_customer_business_unique');
                $table->index(['business_id', 'contact_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ecom_customer_business_contacts');
        Schema::dropIfExists('ecom_customers');
    }
};
