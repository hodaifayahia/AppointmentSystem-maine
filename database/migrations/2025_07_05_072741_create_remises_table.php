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
 // Section 8: Exceptions & Logs
        Schema::create('remises', function (Blueprint $table) {
            $table->id(); // Primary key, auto-incrementing
            $table->foreignId('fiche_navette_item_id')->nullable()->constrained('fiche_navette_items')->onDelete('cascade'); // Nullable foreign key
            $table->foreignId('requester_id')->nullable()->constrained('users')->onDelete('set null'); // Nullable foreign key
            $table->foreignId('approver_id')->nullable()->constrained('users')->onDelete('set null'); // Nullable foreign key
            $table->string('policy_type')->comment('par-clinic, par-doctor, par-personel'); // Not null
            $table->decimal('amount', 15, 2); // Not null
            $table->string('status')->comment('pending-approval, approved, declined'); // Not null
            $table->text('justification')->nullable(); // Nullable text
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remises');
    }
};
