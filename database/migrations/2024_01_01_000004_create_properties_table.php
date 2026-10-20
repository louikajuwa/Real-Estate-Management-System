<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->enum('type', ['apartment', 'house', 'villa', 'land', 'commercial', 'office']);
            $table->decimal('price', 12, 2);
            $table->string('address');
            $table->string('city');
            $table->unsignedInteger('bedrooms')->nullable();
            $table->unsignedInteger('bathrooms')->nullable();
            $table->unsignedInteger('area_sqft')->nullable();
            $table->enum('status', ['available', 'reserved', 'sold'])->default('available');
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('city');
            $table->index('type');
            $table->index('agent_id');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
