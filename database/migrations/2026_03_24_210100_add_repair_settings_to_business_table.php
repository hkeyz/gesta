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
        if (Schema::hasTable('business') && ! Schema::hasColumn('business', 'repair_settings')) {
            Schema::table('business', function (Blueprint $table) {
                $table->text('repair_settings')->nullable()->after('ref_no_prefixes');
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
        if (Schema::hasTable('business') && Schema::hasColumn('business', 'repair_settings')) {
            Schema::table('business', function (Blueprint $table) {
                $table->dropColumn('repair_settings');
            });
        }
    }
};
