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
        Schema::create('success_patterns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('product_name');
            $table->string('revenue_milestone')->nullable(); // e.g. "$1K MRR"
            $table->unsignedInteger('mrr_amount')->nullable(); // in dollars
            $table->string('category')->nullable(); // e.g. "developer-tools", "freelancer"
            $table->text('description')->nullable();
            $table->text('pain_solved')->nullable();
            $table->string('target_customer')->nullable();
            $table->string('pricing_model')->nullable(); // subscription | one-time | usage
            $table->text('key_insight')->nullable();
            $table->string('source_url')->nullable();
            $table->string('source')->nullable(); // indie_hackers | reddit | manual
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index('category');
            $table->index('mrr_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('success_patterns');
    }
};
