<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained('listings')->nullOnDelete();
            $table->text('message_text');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['recipient_id', 'is_read']);
            $table->index(['sender_id', 'recipient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_messages');
    }
};
