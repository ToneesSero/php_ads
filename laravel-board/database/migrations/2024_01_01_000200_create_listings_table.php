<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 12, 2)->default(0);
            $table->string('status', 16)->default('active');
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('user_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
