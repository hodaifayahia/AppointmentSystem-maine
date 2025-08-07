<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fiche_navette_items', function (Blueprint $table) {
             $table->string('custom_name')->nullable()->after('prestation_id')->comment('Custom name for the prestation if needed');
            // convention id
  $table->foreignId('convention_id')
              ->nullable() // or not, depending on your needs
              ->constrained('conventions') // This links it to the 'conventions' table
              ->onDelete('set null'); // This is the action you want on delete
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiche_navette_items', function (Blueprint $table) {
            $table->dropColumn('custom_name');
            $table->dropForeign(['convention_id']);
            $table->dropColumn('convention_id');
        });
    }
};
