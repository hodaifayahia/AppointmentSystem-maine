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
        Schema::create('family_diseases', function (Blueprint $table) {
            $table->id();
            $table->string('disease_name');
            $table->string('relation');
            $table->text('notes')->nullable();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
                        $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_diseases');
    }
};
