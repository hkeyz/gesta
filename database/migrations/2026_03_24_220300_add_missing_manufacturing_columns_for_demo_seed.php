<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if (! Schema::hasColumn('transactions', 'mfg_parent_production_purchase_id')) {
                    $table->unsignedInteger('mfg_parent_production_purchase_id')->nullable()->after('created_by');
                }

                if (! Schema::hasColumn('transactions', 'mfg_wasted_units')) {
                    $table->decimal('mfg_wasted_units', 22, 4)->nullable()->after('mfg_parent_production_purchase_id');
                }

                if (! Schema::hasColumn('transactions', 'mfg_production_cost')) {
                    $table->decimal('mfg_production_cost', 22, 4)->default(0)->after('mfg_wasted_units');
                }

                if (! Schema::hasColumn('transactions', 'mfg_is_final')) {
                    $table->boolean('mfg_is_final')->default(false)->after('mfg_production_cost');
                }
            });
        }

        if (Schema::hasTable('transaction_sell_lines') && ! Schema::hasColumn('transaction_sell_lines', 'mfg_waste_percent')) {
            Schema::table('transaction_sell_lines', function (Blueprint $table) {
                $table->decimal('mfg_waste_percent', 22, 4)->nullable()->after('quantity');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transaction_sell_lines') && Schema::hasColumn('transaction_sell_lines', 'mfg_waste_percent')) {
            Schema::table('transaction_sell_lines', function (Blueprint $table) {
                $table->dropColumn('mfg_waste_percent');
            });
        }

        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $columns = [];

                foreach ([
                    'mfg_parent_production_purchase_id',
                    'mfg_wasted_units',
                    'mfg_production_cost',
                    'mfg_is_final',
                ] as $column) {
                    if (Schema::hasColumn('transactions', $column)) {
                        $columns[] = $column;
                    }
                }

                if (! empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
