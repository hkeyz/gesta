<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('business') && ! Schema::hasColumn('business', 'productcatalogue_settings')) {
            Schema::table('business', function (Blueprint $table) {
                $table->text('productcatalogue_settings')->nullable()->after('common_settings');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('business') && Schema::hasColumn('business', 'productcatalogue_settings')) {
            Schema::table('business', function (Blueprint $table) {
                $table->dropColumn('productcatalogue_settings');
            });
        }
    }
};
