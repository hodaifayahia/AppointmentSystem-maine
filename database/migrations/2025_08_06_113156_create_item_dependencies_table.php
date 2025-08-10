<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
     public function up()
    {
        Schema::create('item_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_item_id')->constrained('fiche_navette_items')->onDelete('cascade');
            $table->foreignId('dependent_item_id')->constrained('fiche_navette_items')->onDelete('cascade');
            $table->foreignId('patient_id')->nullable()->constrained('patients')->onDelete('set null');
            $table->foreignId('convention_id')->nullable()->constrained('conventions')->onDelete('set null');
            $table->enum('dependency_type', ['contraindication', 'prerequisite', 'alternative', 'required', 'optional']);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['parent_item_id', 'dependency_type']);
            $table->index('dependent_item_id');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_dependencies');
    }
};
