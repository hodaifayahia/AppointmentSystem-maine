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
        Schema::table('services', function (Blueprint $table) {
           $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->decimal('agmentation', 10, 2);
            $table->boolean('is_active')->default(true);
        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            
            $table->dropColumn('start_date');
            $table->dropColumn('end_date');
            $table->dropColumn('agmentation');
            $table->dropColumn('is_active');
        });
    }
};
