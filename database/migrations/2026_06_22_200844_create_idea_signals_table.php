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
        Schema::create('idea_signals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('idea_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('raw_signal_id')->constrained('raw_signals')->cascadeOnDelete();
            $table->decimal('weight', 3, 2)->default(1.00); // 0.00–1.00: how central this signal is to the idea
            $table->timestamps();
            $table->unique(['idea_id', 'raw_signal_id']);
            $table->index('raw_signal_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idea_signals');
    }
};
