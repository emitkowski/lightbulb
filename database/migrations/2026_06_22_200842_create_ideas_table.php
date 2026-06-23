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
        Schema::create('ideas', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Core content synthesised from clustered signals
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('signals_summary')->nullable();
            $table->unsignedInteger('source_signals_count')->default(0);

            // Specificity Gate (Section 6b)
            $table->string('specificity_gate_status')->nullable(); // null | passed | failed
            $table->json('specificity_gate_answers')->nullable();
            $table->text('specificity_gate_reasoning')->nullable();

            // Competition search
            $table->string('competition_query')->nullable();
            $table->json('competition_results')->nullable();
            $table->text('competition_summary')->nullable();

            // Dimension scores (0–100)
            $table->unsignedSmallInteger('score_problem_strength')->nullable();
            $table->unsignedSmallInteger('score_distribution_path')->nullable();
            $table->unsignedSmallInteger('score_competition_gap')->nullable();
            $table->unsignedSmallInteger('score_build_feasibility')->nullable();
            $table->unsignedSmallInteger('score_automability')->nullable();
            $table->unsignedSmallInteger('score_revenue_plausibility')->nullable();
            $table->unsignedSmallInteger('score_overall')->nullable();
            $table->json('score_reasoning')->nullable();

            // Kill conditions
            $table->string('kill_condition')->nullable();
            $table->text('kill_reasoning')->nullable();

            // Success pattern layer
            $table->unsignedSmallInteger('success_pattern_confidence')->nullable();
            $table->text('success_pattern_notes')->nullable();

            // Pipeline state
            $table->string('status')->default('pending'); // pending | gate_failed | scoring | scored | discarded
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();
            $table->index('status');
            $table->index('score_overall');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ideas');
    }
};
