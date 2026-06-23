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
        Schema::create('raw_signals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('source', 50);
            $table->string('source_id')->nullable();
            $table->string('source_url')->nullable();
            $table->string('title')->nullable();
            $table->text('content');
            $table->string('author')->nullable();
            $table->integer('score')->default(0);
            $table->integer('comment_count')->default(0);
            $table->string('category')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('processed')->default(false);
            $table->boolean('flagged')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'source_id']);
            $table->index(['source', 'processed']);
            $table->index('published_at');
            $table->index('score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_signals');
    }
};
