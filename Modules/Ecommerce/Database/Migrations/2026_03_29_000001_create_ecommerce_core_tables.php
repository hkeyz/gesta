<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ecom_stores')) {
            Schema::create('ecom_stores', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id');
                $table->unsignedInteger('location_id')->nullable();
                $table->string('slug')->unique();
                $table->boolean('is_enabled')->default(false);
                $table->longText('settings')->nullable();
                $table->timestamps();

                $table->unique('business_id');
                $table->index(['business_id', 'is_enabled']);
            });
        }

        if (! Schema::hasTable('ecom_product_listings')) {
            Schema::create('ecom_product_listings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('store_id');
                $table->unsignedInteger('product_id');
                $table->boolean('is_published')->default(false);
                $table->string('slug');
                $table->text('excerpt')->nullable();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['store_id', 'product_id']);
                $table->unique(['store_id', 'slug']);
                $table->index(['store_id', 'is_published']);
            });
        }

        if (! Schema::hasTable('ecom_api_settings')) {
            Schema::create('ecom_api_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id');
                $table->unsignedBigInteger('store_id')->nullable();
                $table->string('api_token')->unique();
                $table->string('shop_domain')->nullable();
                $table->boolean('is_active')->default(true);
                $table->longText('settings')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'is_active']);
            });
        }

        if (Schema::hasTable('business') && ! Schema::hasColumn('business', 'ecom_settings')) {
            Schema::table('business', function (Blueprint $table) {
                $table->longText('ecom_settings')->nullable()->after('email_settings');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('business') && Schema::hasColumn('business', 'ecom_settings')) {
            Schema::table('business', function (Blueprint $table) {
                $table->dropColumn('ecom_settings');
            });
        }

        Schema::dropIfExists('ecom_api_settings');
        Schema::dropIfExists('ecom_product_listings');
        Schema::dropIfExists('ecom_stores');
    }
};
