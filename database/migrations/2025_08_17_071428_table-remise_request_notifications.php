<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remise_request_notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('remise_request_id')->constrained('remise_requests')->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained('users')->onDelete('cascade');

            $table->enum('type', ['request', 'response']);
            $table->string('message')->nullable();

            $table->boolean('is_read')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remise_request_notifications');
    }
};
