<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->text('message');
            $table->enum('status', ['new', 'contacted', 'viewing_scheduled', 'negotiation', 'closed'])->default('new');
            $table->timestamps();

            $table->index('status');
            $table->index('property_id');
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
