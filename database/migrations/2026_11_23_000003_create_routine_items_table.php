<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routine_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('budget_category_id')->constrained('budget_categories')->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedBigInteger('amount')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_paid')->default(false);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_items');
    }
};
