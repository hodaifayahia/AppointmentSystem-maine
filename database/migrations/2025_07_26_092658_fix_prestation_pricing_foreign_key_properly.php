<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixPrestationPricingForeignKeyProperly extends Migration
{
    public function up()
    {
        // First, let's check and clean up any orphaned records
        DB::statement('DELETE FROM prestation_pricing WHERE annex_id NOT IN (SELECT id FROM annexes)');
        
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        Schema::table('prestation_pricing', function (Blueprint $table) {
            // Drop all existing foreign keys first
            try {
                $table->dropForeign('prestation_pricing_annex_id_foreign');
            } catch (\Exception $e) {
                // Foreign key might not exist
            }
            
            // Make sure the column types match exactly
            // Check what type the annexes.id column is and match it
            $table->unsignedBigInteger('annex_id')->change();
        });
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Now add the foreign key constraint
        Schema::table('prestation_pricing', function (Blueprint $table) {
            $table->foreign('annex_id')
                  ->references('id')
                  ->on('annexes')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::table('prestation_pricing', function (Blueprint $table) {
            $table->dropForeign(['annex_id']);
        });
    }
}
