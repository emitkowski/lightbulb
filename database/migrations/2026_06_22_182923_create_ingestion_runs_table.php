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
        Schema::create('ingestion_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('source', 50);
            $table->string('query')->nullable();
            $table->integer('signals_found')->default(0);
            $table->integer('signals_inserted')->default(0);
            $table->integer('signals_skipped')->default(0);
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['source', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingestion_runs');
    }
};
