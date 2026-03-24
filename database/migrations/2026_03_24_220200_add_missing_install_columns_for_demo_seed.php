<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('categories') && ! Schema::hasColumn('categories', 'woocommerce_cat_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('woocommerce_cat_id')->nullable()->after('slug');
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'woocommerce_media_id')) {
                    $table->string('woocommerce_media_id')->nullable()->after('image');
                }

                if (! Schema::hasColumn('products', 'repair_model_id')) {
                    $table->unsignedInteger('repair_model_id')->nullable()->after('warranty_id');
                }

                if (! Schema::hasColumn('products', 'woocommerce_product_id')) {
                    $table->string('woocommerce_product_id')->nullable()->after('not_for_selling');
                }

                if (! Schema::hasColumn('products', 'woocommerce_disable_sync')) {
                    $table->boolean('woocommerce_disable_sync')->default(false)->after('woocommerce_product_id');
                }
            });
        }

        if (Schema::hasTable('variations') && ! Schema::hasColumn('variations', 'woocommerce_variation_id')) {
            Schema::table('variations', function (Blueprint $table) {
                $table->string('woocommerce_variation_id')->nullable()->after('product_variation_id');
            });
        }

        if (Schema::hasTable('transaction_sell_lines') && ! Schema::hasColumn('transaction_sell_lines', 'woocommerce_line_items_id')) {
            Schema::table('transaction_sell_lines', function (Blueprint $table) {
                $table->string('woocommerce_line_items_id')->nullable()->after('res_line_order_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transaction_sell_lines') && Schema::hasColumn('transaction_sell_lines', 'woocommerce_line_items_id')) {
            Schema::table('transaction_sell_lines', function (Blueprint $table) {
                $table->dropColumn('woocommerce_line_items_id');
            });
        }

        if (Schema::hasTable('variations') && Schema::hasColumn('variations', 'woocommerce_variation_id')) {
            Schema::table('variations', function (Blueprint $table) {
                $table->dropColumn('woocommerce_variation_id');
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $columns = [];

                foreach (['woocommerce_media_id', 'repair_model_id', 'woocommerce_product_id', 'woocommerce_disable_sync'] as $column) {
                    if (Schema::hasColumn('products', $column)) {
                        $columns[] = $column;
                    }
                }

                if (! empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'woocommerce_cat_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('woocommerce_cat_id');
            });
        }
    }
};
