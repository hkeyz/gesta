<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('business_locations')) {
            Schema::table('business_locations', function (Blueprint $table) {
                if (! Schema::hasColumn('business_locations', 'zatca_details')) {
                    $table->longText('zatca_details')->nullable()->after('default_payment_accounts');
                }

                if (! Schema::hasColumn('business_locations', 'zatca_response')) {
                    $table->longText('zatca_response')->nullable()->after('zatca_details');
                }

                if (! Schema::hasColumn('business_locations', 'zatca_sync_from_datetime')) {
                    $table->dateTime('zatca_sync_from_datetime')->nullable()->after('zatca_response');
                }
            });
        }

        if (Schema::hasTable('transactions') && ! Schema::hasColumn('transactions', 'zatca_status')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('zatca_status', 50)->nullable()->after('payment_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'zatca_status')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('zatca_status');
            });
        }

        if (Schema::hasTable('business_locations')) {
            Schema::table('business_locations', function (Blueprint $table) {
                $columns = [];

                foreach (['zatca_details', 'zatca_response', 'zatca_sync_from_datetime'] as $column) {
                    if (Schema::hasColumn('business_locations', $column)) {
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
